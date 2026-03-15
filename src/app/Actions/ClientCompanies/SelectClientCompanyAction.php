<?php

declare(strict_types=1);

namespace App\Actions\ClientCompanies;

use App\Models\ClientCompany;
use Illuminate\Auth\Access\AuthorizationException;

class SelectClientCompanyAction
{
    public function handle(string $clientCompanyId): void
    {
        $company = ClientCompany::withoutGlobalScopes()->findOrFail($clientCompanyId);

        if ($company->consultancy_id !== auth()->user()->consultancy_id) {
            throw new AuthorizationException;
        }

        session(['active_company_id' => $company->id]);
    }
}
