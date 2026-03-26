<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mfw_accounts_account_business', function (Blueprint $table): void {
            $table->boolean('is_seller')->default(false)->after('reg_number');
            $table->string('seller_slug')->nullable()->after('is_seller');
        });
    }

    public function down(): void
    {
        Schema::table('mfw_accounts_account_business', function (Blueprint $table): void {
            $table->dropColumn(['is_seller', 'seller_slug']);
        });
    }
};
