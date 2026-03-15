<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('consultancy_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('client_company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('upload_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('type')->default('received');
            $table->string('operation_type')->default('normal');
            $table->string('ocr_status')->default('pending');
            $table->string('validation_status')->default('pending');
            $table->decimal('ocr_confidence', 5, 4)->nullable();
            $table->date('invoice_date')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('issuer_tax_id')->nullable();
            $table->string('issuer_name')->nullable();
            $table->decimal('taxable_base', 12, 2)->nullable();
            $table->decimal('vat_percentage', 5, 2)->nullable();
            $table->decimal('vat_amount', 12, 2)->nullable();
            $table->decimal('total', 12, 2)->nullable();
            $table->json('ocr_raw')->nullable();
            $table->text('validation_notes')->nullable();
            $table->boolean('exported_to_sage')->default(false);
            $table->timestamp('exported_to_sage_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
