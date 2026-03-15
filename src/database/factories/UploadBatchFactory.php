<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\UploadBatchStatus;
use App\Models\ClientCompany;
use App\Models\Consultancy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UploadBatchFactory extends Factory
{
    public function definition(): array
    {
        $consultancy = Consultancy::factory()->create();

        return [
            'consultancy_id' => $consultancy->id,
            'client_company_id' => ClientCompany::factory()->for($consultancy),
            'user_id' => User::factory()->consultant()->for($consultancy),
            'status' => UploadBatchStatus::Processing,
            'total_invoices' => 0,
            'processed_invoices' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(['status' => UploadBatchStatus::Completed]);
    }

    public function withErrors(): static
    {
        return $this->state(['status' => UploadBatchStatus::WithErrors]);
    }
}
