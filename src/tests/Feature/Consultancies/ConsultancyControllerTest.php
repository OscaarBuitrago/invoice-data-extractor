<?php

declare(strict_types=1);

use App\Models\Consultancy;
use App\Models\User;

it('superadmin can list all consultancies', function (): void {
    // Arrange
    Consultancy::factory()->count(3)->create();
    $superAdmin = User::factory()->superAdmin()->create();

    // Act
    $response = $this->actingAs($superAdmin)
        ->withHeaders(['X-Inertia' => 'true'])
        ->get(route('consultancies.index'));

    // Assert
    $response->assertOk();
});

it('admin cannot list consultancies', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($admin)
        ->withHeaders(['X-Inertia' => 'true'])
        ->get(route('consultancies.index'));

    // Assert
    $response->assertForbidden();
});

it('consultant cannot access consultancies', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $consultant = User::factory()->consultant()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($consultant)
        ->withHeaders(['X-Inertia' => 'true'])
        ->get(route('consultancies.index'));

    // Assert
    $response->assertForbidden();
});

it('superadmin can create a consultancy', function (): void {
    // Arrange
    $superAdmin = User::factory()->superAdmin()->create();

    // Act
    $response = $this->actingAs($superAdmin)->post(route('consultancies.store'), [
        'name' => 'New Consultancy',
        'tax_id' => 'B98765432',
    ]);

    // Assert
    $response->assertRedirect();
    $this->assertDatabaseHas('consultancies', ['tax_id' => 'B98765432']);
});

it('admin cannot create a consultancy', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($admin)->post(route('consultancies.store'), [
        'name' => 'New Consultancy',
        'tax_id' => 'B98765432',
    ]);

    // Assert
    $response->assertForbidden();
});

it('consultancy creation validates required fields', function (): void {
    // Arrange
    $superAdmin = User::factory()->superAdmin()->create();

    // Act
    $response = $this->actingAs($superAdmin)->post(route('consultancies.store'), []);

    // Assert
    $response->assertSessionHasErrors(['name', 'tax_id']);
});
