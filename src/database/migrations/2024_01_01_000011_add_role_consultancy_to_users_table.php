<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->ulid('consultancy_id')->nullable()->after('id');
            $table->string('role')->default(UserRole::Consultant->value)->after('consultancy_id');

            $table->foreign('consultancy_id')
                ->references('id')
                ->on('consultancies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['consultancy_id']);
            $table->dropColumn(['consultancy_id', 'role']);
        });
    }
};
