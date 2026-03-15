<?php

declare(strict_types=1);

use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\User;

it('middleware redirects when no company context is set', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($user)
        ->withHeaders(['X-Inertia' => 'true'])
        ->get(route('dashboard'));

    // Assert
    $response->assertRedirect(route('context.select'));
});

it('middleware allows access when company context is set', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($user)
        ->withSession(['active_company_id' => $company->id])
        ->withHeaders(['X-Inertia' => 'true'])
        ->get(route('dashboard'));

    // Assert
    $response->assertOk();
});
