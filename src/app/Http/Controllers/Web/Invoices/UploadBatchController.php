<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Invoices;

use App\Actions\Invoices\CreateUploadBatchAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoices\StoreUploadBatchRequest;
use App\Jobs\ProcessInvoiceOcrJob;
use App\Models\UploadBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class UploadBatchController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Invoices/Upload');
    }

    public function store(StoreUploadBatchRequest $request, CreateUploadBatchAction $action): RedirectResponse
    {
        $batch = $action->handle($request->file('files'), $request->validated()['type']);

        foreach ($batch->invoices as $invoice) {
            ProcessInvoiceOcrJob::dispatch($invoice);
        }

        return redirect()->route('invoices.batches.progress', $batch->id);
    }

    public function progress(UploadBatch $batch): Response
    {
        $firstInvoice = $batch->invoices()
            ->where('ocr_status', \App\Enums\OcrStatus::Completed)
            ->orderBy('created_at')
            ->first();

        $duplicateFiles = $batch->invoices()
            ->where('ocr_status', \App\Enums\OcrStatus::Duplicate)
            ->pluck('file_name');

        return Inertia::render('Invoices/Progress', [
            'batch' => array_merge(
                $batch->only(['id', 'total_invoices', 'processed_invoices', 'status']),
                ['duplicate_files' => $duplicateFiles],
            ),
            'firstInvoiceId' => $firstInvoice?->id,
        ]);
    }

    public function status(string $batchId): JsonResponse
    {
        $batch = UploadBatch::withoutGlobalScopes()->findOrFail($batchId);

        if ($batch->consultancy_id !== auth()->user()->consultancy_id) {
            abort(403);
        }

        $duplicates = $batch->invoices()
            ->where('ocr_status', \App\Enums\OcrStatus::Duplicate)
            ->pluck('file_name');

        return response()->json([
            'id' => $batch->id,
            'status' => $batch->status,
            'total_invoices' => $batch->total_invoices,
            'processed_invoices' => $batch->processed_invoices,
            'duplicate_files' => $duplicates,
        ]);
    }
}
