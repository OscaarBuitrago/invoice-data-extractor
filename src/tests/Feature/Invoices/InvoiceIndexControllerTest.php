<?php

declare(strict_types=1);

use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\Invoice;
use App\Models\UploadBatch;
use App\Models\User;

it('requires authentication to view invoice list', function (): void {
    $this->get(route('invoices.index'))->assertRedirect(route('login'));
});

it('requires company context to view invoice list', function (): void {
    $user = User::factory()->consultant()->create();
    $this->actingAs($user);

    $this->get(route('invoices.index'))->assertRedirect(route('context.select'));
});

it('renders invoice index page', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $this->withHeaders(['X-Inertia' => 'true'])
        ->get(route('invoices.index'))
        ->assertOk()
        ->assertJson(['component' => 'Invoices/Index']);
});

it('does not return invoices from another consultancy', function (): void {
    $consultancyA = Consultancy::factory()->create();
    $consultancyB = Consultancy::factory()->create();
    $userA = User::factory()->consultant()->for($consultancyA)->create();
    $companyA = ClientCompany::factory()->for($consultancyA)->create();
    $companyB = ClientCompany::factory()->for($consultancyB)->create();
    $userB = User::factory()->consultant()->for($consultancyB)->create();
    $batchB = UploadBatch::factory()->for($consultancyB)->for($companyB)->for($userB)->create();

    Invoice::factory()->count(3)->for($consultancyB)->for($companyB)->for($batchB, 'uploadBatch')->for($userB)->completed()->create();

    $this->actingAs($userA);
    session(['active_company_id' => $companyA->id]);

    $response = $this->withHeaders(['X-Inertia' => 'true'])
        ->get(route('invoices.index'))
        ->assertOk();

    expect($response->json('props.invoices.data'))->toBeEmpty();
});
