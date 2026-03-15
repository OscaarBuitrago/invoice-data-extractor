<?php

declare(strict_types=1);

namespace App\Actions\Invoices;

use App\Enums\ValidationStatus;
use App\Exports\SageExport;
use App\Models\Invoice;
use App\Models\SageExport as SageExportModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportInvoicesToSageAction
{
    public function handle(array $invoiceIds): BinaryFileResponse
    {
        $user = auth()->user();
        $clientCompanyId = session('active_company_id');

        $invoices = Invoice::whereIn('id', $invoiceIds)
            ->where('validation_status', ValidationStatus::Validated)
            ->get();

        if ($invoices->isEmpty()) {
            throw ValidationException::withMessages([
                'invoices' => 'No hay facturas validadas entre las seleccionadas.',
            ]);
        }

        DB::transaction(function () use ($invoices, $user, $clientCompanyId): void {
            $export = SageExportModel::create([
                'consultancy_id' => $user->consultancy_id,
                'client_company_id' => $clientCompanyId,
                'user_id' => $user->id,
                'total_invoices' => $invoices->count(),
            ]);

            $export->invoices()->attach($invoices->pluck('id'));

            Invoice::whereIn('id', $invoices->pluck('id'))->update([
                'exported_to_sage' => true,
                'exported_to_sage_at' => now(),
            ]);
        });

        $filename = 'sage_export_'.now()->format('Ymd_His').'.xlsx';

        return Excel::download(new SageExport($invoices), $filename);
    }
}
