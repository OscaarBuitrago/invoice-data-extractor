<?php

declare(strict_types=1);

namespace App\Http\Requests\ClientCompanies;

use App\Models\ClientCompany;
use Illuminate\Foundation\Http\FormRequest;

class StoreClientCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ClientCompany::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'max:20', 'unique:client_companies,tax_id'],
        ];
    }
}
