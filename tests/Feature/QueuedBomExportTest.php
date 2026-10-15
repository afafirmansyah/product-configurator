<?php

use App\Jobs\GenerateBomExport;
use App\Models\ConfigurationSession;
use App\Models\Product;
use App\Models\User;
use App\Notifications\BomExportReady;
use App\Services\Bom\BomDocumentRenderer;
use App\Services\Bom\BomGeneratorService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array<int, array<string, mixed>>
 */
function sampleBomSnapshot(): array
{
    return [[
        'item_no' => '01',
        'component_id' => 1,
        'part_number' => 'PN-001',
        'description' => 'Sample component',
        'long_description' => null,
        'type' => 'Component',
        'manufacturer' => 'Acme',
        'manufacturer_part_no' => 'ACME-1',
        'quantity' => 2,
        'unit' => 'EA',
        'unit_price' => 10.0,
        'total_price' => 20.0,
        'currency' => 'EUR',
        'is_mandatory' => true,
        'remarks' => null,
    ]];
}

function configurationWithBom(User $owner): ConfigurationSession
{
    return ConfigurationSession::factory()->forUser($owner)->create([
        'product_id' => Product::factory()->create()->id,
        'bom_status' => 'generated',
        'bom_snapshot' => sampleBomSnapshot(),
    ]);
}

test('queueing an export dispatches the generation job', function () {
    Bus::fake();

    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('sales');
    $session = configurationWithBom($owner);

    $this->actingAs($owner)
        ->post(route('sales.bom.queue', [$session, 'pdf']))
        ->assertRedirect();

    Bus::assertDispatched(GenerateBomExport::class, function (GenerateBomExport $job) use ($session, $owner) {
        return $job->configurationId === $session->id
            && $job->format === 'pdf'
            && $job->userId === $owner->id;
    });
});

test('queueing an unsupported format is rejected', function () {
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('sales');
    $session = configurationWithBom($owner);

    $this->actingAs($owner)
        ->post(route('sales.bom.queue', [$session, 'docx']))
        ->assertNotFound();
});

test('the generation job stores the file and notifies the user', function () {
    Storage::fake('local');
    Notification::fake();

    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('sales');
    $session = configurationWithBom($owner);

    (new GenerateBomExport($session->id, 'pdf', $owner->id, true))
        ->handle(app(BomDocumentRenderer::class), app(BomGeneratorService::class));

    $files = Storage::disk('local')->files(GenerateBomExport::DIRECTORY);
    expect($files)->toHaveCount(1);

    Notification::assertSentTo($owner, BomExportReady::class);
});

test('a stored export is only downloadable via a signed url', function () {
    Storage::fake('local');

    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('sales');
    $this->actingAs($owner);

    $path = GenerateBomExport::DIRECTORY.'/sample.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 fake');

    // Unsigned request is rejected by the 'signed' middleware.
    $this->get(route('sales.bom.download', ['path' => $path]))->assertForbidden();

    // A properly signed URL downloads the file.
    $signed = URL::temporarySignedRoute('sales.bom.download', now()->addDay(), ['path' => $path]);
    $this->get($signed)->assertOk();
});
