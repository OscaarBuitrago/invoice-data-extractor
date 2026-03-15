<?php

declare(strict_types=1);

use App\Actions\Users\CreateUserAction;
use App\Enums\UserRole;
use App\Models\Consultancy;
use App\Models\User;

it('creates a user with hashed password', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancy)->create();
    $this->actingAs($admin);

    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'role' => UserRole::Consultant,
    ];

    // Act
    $user = app(CreateUserAction::class)->handle($data);

    // Assert
    expect($user)->toBeInstanceOf(User::class);
    expect($user->name)->toBe('John Doe');
    expect($user->email)->toBe('john@example.com');
    expect($user->password)->not->toBe('password');
    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
});

it('assigns consultancy_id from auth user not from request', function (): void {
    // Arrange
    $consultancyA = Consultancy::factory()->create();
    $consultancyB = Consultancy::factory()->create();
    $admin = User::factory()->admin()->for($consultancyA)->create();
    $this->actingAs($admin);

    $data = [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password',
        'role' => UserRole::Consultant,
        'consultancy_id' => $consultancyB->id,
    ];

    // Act
    $user = app(CreateUserAction::class)->handle($data);

    // Assert — consultancy_id comes from auth user, not from payload
    expect($user->consultancy_id)->toBe($consultancyA->id);
});
