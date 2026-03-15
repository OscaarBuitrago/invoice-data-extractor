<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sage_exports', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('consultancy_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('client_company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('total_invoices');
            $table->timestamps();
        });

        Schema::create('sage_export_invoice', function (Blueprint $table): void {
            $table->foreignUlid('sage_export_id')->constrained('sage_exports')->cascadeOnDelete();
            $table->foreignUlid('invoice_id')->constrained()->cascadeOnDelete();
            $table->primary(['sage_export_id', 'invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sage_export_invoice');
        Schema::dropIfExists('sage_exports');
    }
};
