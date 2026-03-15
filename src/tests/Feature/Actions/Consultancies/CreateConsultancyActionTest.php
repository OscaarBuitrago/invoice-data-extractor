<?php

declare(strict_types=1);

use App\Actions\Consultancies\CreateConsultancyAction;
use App\Models\Consultancy;

it('creates a consultancy with correct data', function (): void {
    // Arrange
    $data = [
        'name' => 'Acme Consulting',
        'tax_id' => 'B12345678',
    ];

    // Act
    $consultancy = app(CreateConsultancyAction::class)->handle($data);

    // Assert
    expect($consultancy)->toBeInstanceOf(Consultancy::class);
    expect($consultancy->name)->toBe('Acme Consulting');
    expect($consultancy->tax_id)->toBe('B12345678');
    expect($consultancy->active)->toBeTrue();
    $this->assertDatabaseHas('consultancies', ['tax_id' => 'B12345678']);
});
