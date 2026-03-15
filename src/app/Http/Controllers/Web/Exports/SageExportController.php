<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Exports;

use App\Actions\Invoices\ExportInvoicesToSageAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SageExportController extends Controller
{
    public function store(Request $request, ExportInvoicesToSageAction $action): BinaryFileResponse
    {
        $request->validate([
            'invoice_ids' => ['required', 'array', 'min:1'],
            'invoice_ids.*' => ['required', 'string'],
        ]);

        return $action->handle($request->input('invoice_ids'));
    }
}
