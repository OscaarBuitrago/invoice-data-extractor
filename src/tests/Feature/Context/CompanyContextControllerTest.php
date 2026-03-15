<?php

declare(strict_types=1);

use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\User;

it('user can select a client company from their consultancy', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($user)->post(route('context.store'), [
        'client_company_id' => $company->id,
    ]);

    // Assert
    $response->assertRedirect(route('dashboard'));
    expect(session('active_company_id'))->toBe($company->id);
});

it('user cannot select a client company from another consultancy', function (): void {
    // Arrange
    $consultancyA = Consultancy::factory()->create();
    $consultancyB = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancyA)->create();
    $company = ClientCompany::factory()->for($consultancyB)->create();

    // Act
    $response = $this->actingAs($user)->post(route('context.store'), [
        'client_company_id' => $company->id,
    ]);

    // Assert
    $response->assertForbidden();
});
