<?php

declare(strict_types=1);

use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\UploadBatch;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('local');
    Queue::fake();
});

it('requires authentication to upload', function (): void {
    $this->post(route('invoices.upload.store'))->assertRedirect(route('login'));
});

it('requires company context to upload', function (): void {
    $user = User::factory()->consultant()->create();
    $this->actingAs($user);

    $this->post(route('invoices.upload.store'))
        ->assertRedirect(route('context.select'));
});

it('validates at least one file is required', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $this->post(route('invoices.upload.store'), [])
        ->assertSessionHasErrors('files');
});

it('validates maximum 20 files', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $files = array_fill(0, 21, UploadedFile::fake()->create('f.pdf', 100, 'application/pdf'));

    $this->post(route('invoices.upload.store'), ['files' => $files])
        ->assertSessionHasErrors('files');
});

it('validates maximum file size of 10mb', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $files = [UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf')];

    $this->post(route('invoices.upload.store'), ['files' => $files])
        ->assertSessionHasErrors('files.0');
});

it('validates files must be pdfs', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $files = [UploadedFile::fake()->create('image.jpg', 100, 'image/jpeg')];

    $this->post(route('invoices.upload.store'), ['files' => $files])
        ->assertSessionHasErrors('files.0');
});

it('creates batch and dispatches jobs on valid upload', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $this->actingAs($user);
    session(['active_company_id' => $company->id]);

    $files = [
        UploadedFile::fake()->create('factura1.pdf', 100, 'application/pdf'),
        UploadedFile::fake()->create('factura2.pdf', 100, 'application/pdf'),
    ];

    $response = $this->post(route('invoices.upload.store'), ['files' => $files]);

    $batch = UploadBatch::withoutGlobalScopes()->latest()->first();

    $response->assertRedirect(route('invoices.batches.progress', $batch->id));

    expect($batch->total_invoices)->toBe(2);
});

it('returns batch progress as json', function (): void {
    $consultancy = Consultancy::factory()->create();
    $user = User::factory()->consultant()->for($consultancy)->create();
    $company = ClientCompany::factory()->for($consultancy)->create();
    $batch = UploadBatch::factory()->for($consultancy)->for($company)->for($user)->create([
        'total_invoices' => 5,
        'processed_invoices' => 3,
    ]);
    $this->actingAs($user);

    $response = $this->getJson(route('invoices.batches.status', $batch->id));

    $response->assertOk()->assertJson([
        'total_invoices' => 5,
        'processed_invoices' => 3,
    ]);
});

it('returns 403 if batch belongs to another consultancy', function (): void {
    $consultancyA = Consultancy::factory()->create();
    $consultancyB = Consultancy::factory()->create();
    $userA = User::factory()->consultant()->for($consultancyA)->create();
    $companyB = ClientCompany::factory()->for($consultancyB)->create();
    $userB = User::factory()->consultant()->for($consultancyB)->create();
    $batch = UploadBatch::factory()->for($consultancyB)->for($companyB)->for($userB)->create();

    $this->actingAs($userA);

    $this->getJson(route('invoices.batches.status', $batch->id))
        ->assertForbidden();
});
