<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoices;

use Illuminate\Foundation\Http\FormRequest;

class StoreUploadBatchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
