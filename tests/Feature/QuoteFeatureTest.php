<?php

use App\Models\ConfigurationSession;
use App\Models\QuotationRequest;
use App\Models\User;
use App\Notifications\QuotationStatusUpdated;
use App\Notifications\QuotationSubmitted;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('submitting a quotation notifies the configuration owner', function () {
    Notification::fake();

    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('customer');
    $session = ConfigurationSession::factory()->forUser($owner)->create();

    $this->actingAs($owner)->post(route('configurator.quotation.store', $session->ulid), [
        'company' => 'Acme Corp',
        'contact_person' => 'Jane Doe',
        'email' => 'jane@example.com',
        'quantity' => 2,
    ])->assertRedirect(route('configurator.show', $session->ulid));

    Notification::assertSentTo($owner, QuotationSubmitted::class);
});

test('updating a quotation status notifies the submitter', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('super_admin');
    $submitter = User::factory()->create(['is_active' => true]);
    $submitter->assignRole('customer');

    $quotation = QuotationRequest::factory()->forUser($submitter)->create(['status' => 'pending']);

    $this->actingAs($admin)
        ->patch(route('admin.quotations.status', $quotation), ['status' => 'reviewed'])
        ->assertRedirect();

    Notification::assertSentTo($submitter, QuotationStatusUpdated::class);
});

test('a no-op status update sends no notification', function () {
    Notification::fake();

    $admin = User::factory()->create(['is_active' => true]);
    $admin->assignRole('super_admin');
    $submitter = User::factory()->create(['is_active' => true]);

    $quotation = QuotationRequest::factory()->forUser($submitter)->create(['status' => 'pending']);

    $this->actingAs($admin)
        ->patch(route('admin.quotations.status', $quotation), ['status' => 'pending']);

    Notification::assertNothingSent();
});

test('the quotation owner can download the quote pdf', function () {
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('customer');
    $session = ConfigurationSession::factory()->forUser($owner)->create();
    $quotation = QuotationRequest::factory()->forUser($owner)->create([
        'configuration_session_id' => $session->id,
    ]);

    $response = $this->actingAs($owner)->get(route('configurator.quotation.pdf', $quotation->ulid));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

test('a non-owner customer cannot download the quote pdf', function () {
    $owner = User::factory()->create(['is_active' => true]);
    $stranger = User::factory()->create(['is_active' => true]);
    $stranger->assignRole('customer');
    $quotation = QuotationRequest::factory()->forUser($owner)->create();

    $this->actingAs($stranger)
        ->get(route('configurator.quotation.pdf', $quotation->ulid))
        ->assertForbidden();
});
