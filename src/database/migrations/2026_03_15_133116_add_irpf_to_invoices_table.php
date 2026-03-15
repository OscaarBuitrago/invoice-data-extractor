<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('irpf_percentage', 5, 2)->nullable()->after('vat_amount');
            $table->decimal('irpf_amount', 12, 2)->nullable()->after('irpf_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropColumn(['irpf_percentage', 'irpf_amount']);
        });
    }
};
