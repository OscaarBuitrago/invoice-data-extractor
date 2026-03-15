<?php

declare(strict_types=1);

use App\Actions\ClientCompanies\SelectClientCompanyAction;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

it('stores selected company id in session', function (): void {
    // Arrange
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $this->actingAs($user);

    // Act
    app(SelectClientCompanyAction::class)->handle($company->id);

    // Assert
    expect(session('active_company_id'))->toBe($company->id);
});

it('rejects company from another consultancy', function (): void {
    // Arrange
    $consultancyA = Consultancy::factory()->create();
    $consultancyB = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancyA)->create();
    $company = ClientCompany::factory()->for($consultancyB)->create();
    $this->actingAs($user);

    // Act & Assert
    expect(fn () => app(SelectClientCompanyAction::class)->handle($company->id))
        ->toThrow(AuthorizationException::class);
});
