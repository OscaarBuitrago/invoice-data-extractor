<?php

declare(strict_types=1);

use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\User;

it('admin can list client companies in their consultancy', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancy)->create();
    ClientCompany::factory()->count(3)->for($consultancy)->create();

    // Act
    $response = $this->actingAs($admin)
        ->withHeaders(['X-Inertia' => 'true'])
        ->get(route('client-companies.index'));

    // Assert
    $response->assertOk();
});

it('consultant can list client companies in their consultancy', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $consultant = User::factory()->consultant()->for($consultancy)->create();
    ClientCompany::factory()->count(2)->for($consultancy)->create();

    // Act
    $response = $this->actingAs($consultant)
        ->withHeaders(['X-Inertia' => 'true'])
        ->get(route('client-companies.index'));

    // Assert
    $response->assertOk();
});

it('admin cannot list client companies from another consultancy', function (): void {
    // Arrange
    $consultancyA = Consultancy::factory()->create();
    $consultancyB = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancyA)->create();
    ClientCompany::factory()->count(3)->for($consultancyB)->create();

    // Act
    $response = $this->actingAs($admin)
        ->withHeaders(['X-Inertia' => 'true'])
        ->get(route('client-companies.index'));

    // Assert — scope filters to zero companies from other consultancy
    $response->assertOk();
    $data = $response->json('props.clientCompanies.data');
    expect($data)->toHaveCount(0);
});

it('admin can create a client company', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($admin)->post(route('client-companies.store'), [
        'name' => 'Nueva Empresa S.L.',
        'tax_id' => 'B99887766',
    ]);

    // Assert
    $response->assertRedirect();
    $this->assertDatabaseHas('client_companies', [
        'tax_id' => 'B99887766',
        'consultancy_id' => $consultancy->id,
    ]);
});

it('consultant cannot create a client company', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $consultant = User::factory()->consultant()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($consultant)->post(route('client-companies.store'), [
        'name' => 'Nueva Empresa S.L.',
        'tax_id' => 'B99887766',
    ]);

    // Assert
    $response->assertForbidden();
});

it('client company creation validates required fields', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($admin)->post(route('client-companies.store'), []);

    // Assert
    $response->assertSessionHasErrors(['name', 'tax_id']);
});
