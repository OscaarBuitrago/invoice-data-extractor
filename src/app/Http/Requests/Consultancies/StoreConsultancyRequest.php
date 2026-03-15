<?php

declare(strict_types=1);

namespace App\Http\Requests\Consultancies;

use App\Models\Consultancy;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Consultancy::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['required', 'string', 'max:20', 'unique:consultancies,tax_id'],
        ];
    }
}
