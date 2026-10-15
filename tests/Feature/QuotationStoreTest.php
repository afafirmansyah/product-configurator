<?php

use App\Models\ConfigurationSession;
use App\Models\QuotationRequest;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

/**
 * @return array<string, mixed>
 */
function quotationPayload(): array
{
    return [
        'company' => 'Acme Corp',
        'contact_person' => 'Jane Doe',
        'email' => 'jane@example.com',
        'quantity' => 2,
    ];
}

test('the owner can submit a quotation request', function () {
    $owner = User::factory()->create(['is_active' => true]);
    $owner->assignRole('customer');
    $session = ConfigurationSession::factory()->forUser($owner)->create();

    $this->actingAs($owner)
        ->post(route('configurator.quotation.store', $session->ulid), quotationPayload())
        ->assertRedirect(route('configurator.show', $session->ulid));

    expect(QuotationRequest::where('configuration_session_id', $session->id)->exists())->toBeTrue();
});

test('a non-owner customer is forbidden from submitting a quotation request', function () {
    $owner = User::factory()->create(['is_active' => true]);
    $stranger = User::factory()->create(['is_active' => true]);
    $stranger->assignRole('customer');
    $session = ConfigurationSession::factory()->forUser($owner)->create();

    $this->actingAs($stranger)
        ->post(route('configurator.quotation.store', $session->ulid), quotationPayload())
        ->assertForbidden();

    expect(QuotationRequest::where('configuration_session_id', $session->id)->exists())->toBeFalse();
});

test('an internal user can submit a quotation for a configuration they do not own', function () {
    $owner = User::factory()->create(['is_active' => true]);
    $engineer = User::factory()->create(['is_active' => true]);
    $engineer->assignRole('engineering');
    $session = ConfigurationSession::factory()->forUser($owner)->create();

    $this->actingAs($engineer)
        ->post(route('configurator.quotation.store', $session->ulid), quotationPayload())
        ->assertRedirect(route('configurator.show', $session->ulid));

    expect(QuotationRequest::where('configuration_session_id', $session->id)->exists())->toBeTrue();
});
