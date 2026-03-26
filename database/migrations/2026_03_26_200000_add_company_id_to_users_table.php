<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'company_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('company_id')->nullable()->after('type');
            $table->foreign('company_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'company_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
