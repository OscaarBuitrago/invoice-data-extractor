<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\Consultancy;
use App\Models\User;

it('admin can create a user in their own consultancy', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'New Consultant',
        'email' => 'consultant@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => UserRole::Consultant->value,
    ]);

    // Assert
    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'email' => 'consultant@example.com',
        'consultancy_id' => $consultancy->id,
    ]);
});

it('admin cannot create a user in another consultancy', function (): void {
    // Arrange
    $consultancyA = Consultancy::factory()->create();
    $consultancyB = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancyA)->create();

    // Act
    $this->actingAs($admin)->post(route('users.store'), [
        'name' => 'New Consultant',
        'email' => 'consultant@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => UserRole::Consultant->value,
        'consultancy_id' => $consultancyB->id,
    ]);

    // Assert — user is always assigned to admin's own consultancy
    $this->assertDatabaseMissing('users', [
        'email' => 'consultant@example.com',
        'consultancy_id' => $consultancyB->id,
    ]);
});

it('consultant cannot create users', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $consultant = User::factory()->consultant()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($consultant)->post(route('users.store'), [
        'name' => 'New User',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => UserRole::Consultant->value,
    ]);

    // Assert
    $response->assertForbidden();
});

it('superadmin can create a user in any consultancy', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();

    // Act
    $response = $this->actingAs($superAdmin)->post(route('users.store'), [
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role' => UserRole::Admin->value,
        'consultancy_id' => $consultancy->id,
    ]);

    // Assert
    $response->assertRedirect();
    $this->assertDatabaseHas('users', [
        'email' => 'admin@example.com',
        'consultancy_id' => $consultancy->id,
    ]);
});

it('admin can update a user in their own consultancy', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancy)->create();
    $target = User::factory()->consultant()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($admin)->put(route('users.update', $target), [
        'name' => 'Updated Name',
        'email' => $target->email,
        'role' => UserRole::Consultant->value,
    ]);

    // Assert
    $response->assertRedirect();
    $this->assertDatabaseHas('users', ['id' => $target->id, 'name' => 'Updated Name']);
});

it('admin cannot update a user in another consultancy', function (): void {
    // Arrange
    $consultancyA = Consultancy::factory()->create();
    $consultancyB = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancyA)->create();
    $target = User::factory()->consultant()->for($consultancyB)->create();

    // Act
    $response = $this->actingAs($admin)->put(route('users.update', $target), [
        'name' => 'Hacked Name',
        'email' => $target->email,
        'role' => UserRole::Consultant->value,
    ]);

    // Assert
    $response->assertForbidden();
});

it('user creation validates required fields and role', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancy)->create();

    // Act
    $response = $this->actingAs($admin)->post(route('users.store'), []);

    // Assert
    $response->assertSessionHasErrors(['name', 'email', 'password', 'role']);
});
