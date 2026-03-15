<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Invoices\ProcessInvoiceOcrAction;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessInvoiceOcrJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;

    public function __construct(
        public readonly Invoice $invoice,
    ) {}

    public function handle(ProcessInvoiceOcrAction $action): void
    {
        $action->handle($this->invoice);
    }
}
