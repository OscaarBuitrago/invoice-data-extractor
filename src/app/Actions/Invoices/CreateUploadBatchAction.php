<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\OcrStatus;
use App\Enums\UploadBatchStatus;
use App\Enums\ValidationStatus;
use App\Models\Invoice;
use App\Models\UploadBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateUploadBatchAction
{
    public function handle(array $files): UploadBatch
    {
        $user = auth()->user();
        $clientCompanyId = session('active_company_id');

        return DB::transaction(function () use ($files, $user, $clientCompanyId): UploadBatch {
            $batch = UploadBatch::create([
                'consultancy_id' => $user->consultancy_id,
                'client_company_id' => $clientCompanyId,
                'user_id' => $user->id,
                'status' => UploadBatchStatus::Processing,
                'total_invoices' => count($files),
                'processed_invoices' => 0,
            ]);

            foreach ($files as $file) {
                /** @var UploadedFile $file */
                $path = $file->store("invoices/{$clientCompanyId}");

                Invoice::create([
                    'consultancy_id' => $user->consultancy_id,
                    'client_company_id' => $clientCompanyId,
                    'upload_batch_id' => $batch->id,
                    'user_id' => $user->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'ocr_status' => OcrStatus::Pending,
                    'validation_status' => ValidationStatus::Pending,
                ]);
            }

            return $batch->load('invoices');
        });
    }
}
