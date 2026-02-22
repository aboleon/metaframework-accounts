<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mfw_accounts_company_data', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->text('licence')->nullable();
            $table->text('name')->nullable();
            $table->text('adresse')->nullable();
            $table->text('siege')->nullable();
            $table->text('owner')->nullable();
            $table->string('lg', 2);

            $table->index(['company_id', 'lg'], 'company_id_lg');
        });

        Schema::create('mfw_accounts_bank_accounts_data', function (Blueprint $table) {
            $table->unsignedInteger('id');
            $table->unsignedInteger('account_id');
            $table->text('name');
            $table->text('bank');
            $table->text('address');
            $table->string('lg', 2);
        });

        Schema::create('mfw_accounts_pay_means_channels', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pay_mean_id')->nullable();
            $table->unsignedInteger('bank_account_id')->nullable();

            $table->index('pay_mean_id', 'FK_mfw_accounts_pay_means_channels_mfw_accounts_pay_means');
            $table->index('bank_account_id', 'FK_mfw_accounts_pay_means_channels_mfw_accounts_bank_accounts');

            $table->foreign('bank_account_id', 'FK_mfw_accounts_pay_means_channels_mfw_accounts_bank_accounts')
                ->references('id')
                ->on('mfw_accounts_bank_accounts')
                ->nullOnDelete()
                ->onUpdate('no action');
            $table->foreign('pay_mean_id', 'FK_mfw_accounts_pay_means_channels_mfw_accounts_pay_means')
                ->references('id')
                ->on('mfw_accounts_pay_means')
                ->cascadeOnDelete()
                ->onUpdate('no action');
        });

        Schema::create('mfw_accounts_pay_means_channels_data', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pay_channel_id')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('lg', 2)->nullable();

            $table->index('pay_channel_id', 'FK_mfw_accounts_pay_means_channels_data_master');
            $table->foreign('pay_channel_id', 'FK_mfw_accounts_pay_means_channels_data_master')
                ->references('id')
                ->on('mfw_accounts_pay_means_channels')
                ->cascadeOnDelete()
                ->onUpdate('no action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfw_accounts_pay_means_channels_data');
        Schema::dropIfExists('mfw_accounts_pay_means_channels');
        Schema::dropIfExists('mfw_accounts_bank_accounts_data');
        Schema::dropIfExists('mfw_accounts_company_data');
    }
};
