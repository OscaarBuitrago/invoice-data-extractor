<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoices;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->input('action') === 'reject') {
            return [
                'action' => ['required', 'in:validate,reject'],
                'validation_notes' => ['nullable', 'string', 'max:1000'],
            ];
        }

        $isReceived = $this->input('type') === 'received';

        return [
            'action' => ['required', 'in:validate,reject'],
            'invoice_date' => ['required', 'date'],
            'invoice_number' => ['required', 'string', 'max:100'],
            'issuer_tax_id' => [$isReceived ? 'required' : 'nullable', 'string', 'max:20'],
            'issuer_name' => ['nullable', 'string', 'max:255'],
            'recipient_tax_id' => [$isReceived ? 'nullable' : 'required', 'string', 'max:20'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'taxable_base' => ['required', 'numeric', 'min:0'],
            'vat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vat_amount' => ['nullable', 'numeric', 'min:0'],
            'irpf_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'irpf_amount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric'],
            'type' => ['required', 'in:issued,received'],
            'operation_type' => ['required', 'in:normal,intra_community,reverse_charge,import,not_subject'],
            'validation_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
