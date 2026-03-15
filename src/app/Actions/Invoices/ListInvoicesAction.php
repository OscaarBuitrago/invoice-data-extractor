<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Data\InvoiceFiltersData;
use App\Enums\OcrStatus;
use App\Models\Invoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListInvoicesAction
{
    public function handle(InvoiceFiltersData $filters, int $perPage = 25): LengthAwarePaginator
    {
        $clientCompanyId = session('active_company_id');

        return Invoice::query()
            ->where('client_company_id', $clientCompanyId)
            ->where('ocr_status', OcrStatus::Completed)
            ->when($filters->type, fn ($q, $v) => $q->where('type', $v))
            ->when($filters->validationStatus, fn ($q, $v) => $q->where('validation_status', $v))
            ->when($filters->operationType, fn ($q, $v) => $q->where('operation_type', $v))
            ->when($filters->dateFrom, fn ($q, $v) => $q->whereDate('invoice_date', '>=', $v))
            ->when($filters->dateTo, fn ($q, $v) => $q->whereDate('invoice_date', '<=', $v))
            ->when($filters->exportedToSage !== null, fn ($q) => $q->where('exported_to_sage', $filters->exportedToSage))
            ->orderByDesc('invoice_date')
            ->paginate($perPage);
    }
}
