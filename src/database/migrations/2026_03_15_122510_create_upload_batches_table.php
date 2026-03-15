<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_batches', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('consultancy_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('client_company_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('processing');
            $table->unsignedInteger('total_invoices')->default(0);
            $table->unsignedInteger('processed_invoices')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_batches');
    }
};
