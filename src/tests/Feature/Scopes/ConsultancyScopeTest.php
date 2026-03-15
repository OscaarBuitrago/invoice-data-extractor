<?php

declare(strict_types=1);

use App\Models\Consultancy;
use App\Models\User;

it('consultancy scope filters records by consultancy', function (): void {
    // Arrange
    $consultancyA = Consultancy::factory()->create();
    $consultancyB = Consultancy::factory()->create();

    $userA = User::factory()->admin()->for($consultancyA)->create();
    User::factory()->admin()->for($consultancyB)->create();

    // Act
    $this->actingAs($userA);
    $scoped = Consultancy::query()->get();

    // Assert
    expect($scoped)->toHaveCount(1);
    expect($scoped->first()->id)->toBe($consultancyA->id);
});

it('superadmin sees all consultancies without scope restriction', function (): void {
    // Arrange
    Consultancy::factory()->count(3)->create();
    $superAdmin = User::factory()->superAdmin()->create();

    // Act
    $this->actingAs($superAdmin);
    $result = Consultancy::query()->get();

    // Assert
    expect($result)->toHaveCount(3);
});
