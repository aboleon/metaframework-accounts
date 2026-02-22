<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mfw_accounts_cashflow', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('invoice_id')->nullable();
            $table->unsignedInteger('account_id');
            $table->text('title');
            $table->decimal('amount', 10, 2);
            $table->date('transaction_date')->nullable();
            $table->date('bank_transaction_date')->nullable();
            $table->unsignedInteger('pay_mean')->nullable();
            $table->string('pay_id');
            $table->text('link');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('account_id', 'mfw_accounts_cashflow_account_id_index');

            $table->foreign('account_id', 'FK_mfw_accounts_cashflow_mfw_accounts_accounts')
                ->references('id')
                ->on('mfw_accounts_accounts')
                ->onUpdate('no action');
        });

        Schema::create('mfw_accounts_cashflow_structure', function (Blueprint $table) {
            $table->unsignedInteger('cashflow_id');
            $table->unsignedInteger('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfw_accounts_cashflow_structure');
        Schema::dropIfExists('mfw_accounts_cashflow');
    }
};
