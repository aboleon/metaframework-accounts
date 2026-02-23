<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $legacyTables = [
            'thesaurus_cashflow_doc_types',
            'thesaurus_currencies',
            'thesaurus_vat',
            'thesaurus_sell_channels',
            'thesaurus_pay_means',
            'thesaurus_company',
            'thesaurus_providers',
            'thesaurus_bank_accounts',
        ];

        foreach ($legacyTables as $legacyTable) {
            if (Schema::hasTable($legacyTable)) {
                return;
            }
        }

        $targetTables = [
            'mfw_accounts_cashflow_doc_types',
            'mfw_accounts_currencies',
            'mfw_accounts_vat',
            'mfw_accounts_sell_channels',
            'mfw_accounts_pay_means',
            'mfw_accounts_company',
            'mfw_accounts_providers',
            'mfw_accounts_bank_accounts',
        ];

        $allTargetsExist = true;
        foreach ($targetTables as $targetTable) {
            if (!Schema::hasTable($targetTable)) {
                $allTargetsExist = false;
                break;
            }
        }

        if ($allTargetsExist) {
            return;
        }

        Schema::create('mfw_accounts_cashflow_doc_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('slug');
            $table->unsignedTinyInteger('accountable')->default(1);
            $table->unsignedTinyInteger('default')->default(0);
            $table->tinyInteger('inventory')->default(0);
            $table->longText('admin_name')->nullable();
            $table->longText('name')->nullable();
            $table->enum('numerotation', ['generic', 'own'])->default('generic');
        });

        Schema::create('mfw_accounts_currencies', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->text('name');
            $table->string('code', 3);
            $table->tinyText('sign');
            $table->unsignedTinyInteger('default');
            $table->decimal('conversion_rate', 10, 5)->nullable();
        });

        Schema::create('mfw_accounts_vat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('rate')->unique('vat_rate_unique');
            $table->boolean('default')->nullable()->index('vat_default_index');
            $table->softDeletes();
            $table->index('deleted_at', 'vat_softdeleted');
        });

        Schema::create('mfw_accounts_sell_channels', function (Blueprint $table) {
            $table->increments('id');
            $table->mediumText('name');
            $table->char('default', 1)->nullable()->index('mfw_accounts_sell_channels_default_index');
        });

        Schema::create('mfw_accounts_pay_means', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type', 50)->nullable();
            $table->longText('name')->nullable();
        });

        Schema::create('mfw_accounts_company', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->tinyText('EIN');
            $table->tinyText('VAT');
            $table->string('bilan_start', 5);
            $table->string('bilan_end', 5);
            $table->mediumText('website');
            $table->mediumText('email');
            $table->mediumText('phone');
        });

        Schema::create('mfw_accounts_providers', function (Blueprint $table) {
            $table->increments('id');
            $table->text('name');
            $table->text('adresse');
            $table->unsignedInteger('location');
        });

        Schema::create('mfw_accounts_bank_accounts', function (Blueprint $table) {
            $table->unsignedInteger('id')->primary();
            $table->text('BIC');
            $table->string('IBAN', 22);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfw_accounts_bank_accounts');
        Schema::dropIfExists('mfw_accounts_providers');
        Schema::dropIfExists('mfw_accounts_company');
        Schema::dropIfExists('mfw_accounts_pay_means');
        Schema::dropIfExists('mfw_accounts_sell_channels');
        Schema::dropIfExists('mfw_accounts_vat');
        Schema::dropIfExists('mfw_accounts_currencies');
        Schema::dropIfExists('mfw_accounts_cashflow_doc_types');
    }
};
