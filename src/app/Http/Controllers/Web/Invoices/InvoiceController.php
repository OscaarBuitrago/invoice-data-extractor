<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Invoices;

use App\Actions\Invoices\GetNextPendingInvoiceAction;
use App\Actions\Invoices\ListInvoicesAction;
use App\Actions\Invoices\RejectInvoiceAction;
use App\Actions\Invoices\ValidateInvoiceAction;
use App\Data\InvoiceFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\UpdateInvoiceRequest;
use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class InvoiceController extends Controller
{
    public function index(Request $request, ListInvoicesAction $action): Response
    {
        $filters = InvoiceFiltersData::fromArray($request->only([
            'type', 'validation_status', 'operation_type', 'date_from', 'date_to', 'exported_to_sage',
        ]));

        return Inertia::render('Invoices/Index', [
            'invoices' => $action->handle($filters)->withQueryString(),
            'filters' => $filters,
        ]);
    }

    public function show(Invoice $invoice): Response
    {
        $batch = $invoice->uploadBatch;

        $batchInvoices = Invoice::withoutGlobalScopes()
            ->where('upload_batch_id', $batch->id)
            ->orderBy('created_at')
            ->get(['id', 'file_name', 'validation_status', 'ocr_confidence', 'ocr_status']);

        return Inertia::render('Invoices/Validation', [
            'invoice' => [
                ...$invoice->only([
                    'id', 'file_name', 'ocr_confidence', 'ocr_status', 'validation_status',
                    'invoice_date', 'invoice_number', 'issuer_tax_id', 'issuer_name',
                    'taxable_base', 'vat_percentage', 'vat_amount',
                    'irpf_percentage', 'irpf_amount', 'total',
                    'type', 'operation_type', 'validation_notes',
                ]),
                'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
            ],
            'pdfUrl' => route('invoices.pdf', $invoice),
            'batch' => $batch->only(['id', 'total_invoices']),
            'batchInvoices' => $batchInvoices,
        ]);
    }

    public function pdf(Invoice $invoice): BinaryFileResponse
    {
        $path = Storage::disk('local')->path($invoice->file_path);

        return response()->file($path, ['Content-Type' => 'application/pdf']);
    }

    public function update(
        UpdateInvoiceRequest $request,
        Invoice $invoice,
        ValidateInvoiceAction $validateAction,
        RejectInvoiceAction $rejectAction,
        GetNextPendingInvoiceAction $nextAction,
    ): RedirectResponse {
        if ($request->input('action') === 'reject') {
            $rejectAction->handle($invoice, $request->input('validation_notes'));
        } else {
            $validateAction->handle($invoice, $request->except('action'));
        }

        $next = $nextAction->handle($invoice->uploadBatch, $invoice);

        return $next instanceof Invoice
            ? redirect()->route('invoices.show', $next)
            : redirect()->route('dashboard');
    }
}
