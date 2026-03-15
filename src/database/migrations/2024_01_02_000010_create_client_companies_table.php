<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('consultancy_id');
            $table->string('name');
            $table->string('tax_id')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->foreign('consultancy_id')
                ->references('id')
                ->on('consultancies')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_companies');
    }
};
