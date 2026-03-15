<?php

declare(strict_types=1);

namespace App\Actions\ClientCompanies;

use App\Models\ClientCompany;
use Illuminate\Support\Facades\DB;

class CreateClientCompanyAction
{
    public function handle(array $data): ClientCompany
    {
        return DB::transaction(fn (): ClientCompany => ClientCompany::create([
            'consultancy_id' => auth()->user()->consultancy_id,
            'name' => $data['name'],
            'tax_id' => $data['tax_id'],
            'active' => true,
        ]));
    }
}
