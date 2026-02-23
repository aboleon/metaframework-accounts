<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $legacyTables = [
            'thesaurus_invoices',
            'thesaurus_invoices_structure',
            'thesaurus_invoice_expense_associations',
        ];

        foreach ($legacyTables as $legacyTable) {
            if (Schema::hasTable($legacyTable)) {
                return;
            }
        }

        $targetTables = [
            'mfw_accounts_invoices',
            'mfw_accounts_invoices_structure',
            'mfw_accounts_invoice_expense_associations',
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

        Schema::create('mfw_accounts_invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('document_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedInteger('sale_id')->nullable();
            $table->text('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->text('content')->nullable();
            $table->text('notes')->nullable();
            $table->integer('amount')->default(0);
            $table->text('amount_text')->nullable();
            $table->unsignedInteger('quantity')->nullable()->default(1);
            $table->integer('vat')->default(0);
            $table->unsignedInteger('expenses')->nullable()->default(0);
            $table->boolean('no_expenses')->default(false);
            $table->integer('net_gain')->nullable()->default(0)->comment('Computed from expenses/associations; do not edit manually.');
            $table->integer('net_gain_percent')->nullable()->default(0)->comment('Computed net gain percent from net_gain and total amount.');
            $table->string('expense_protocol_ref')->nullable();
            $table->unsignedInteger('vat_id')->nullable();
            $table->unsignedInteger('currency');
            $table->unsignedTinyInteger('sell_channel')->nullable();
            $table->string('paid', 1)->nullable();
            $table->unsignedInteger('pay_mean')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('doc_type');
            $table->string('hash', 40);
            $table->tinyText('pdf_locale')->nullable();
            $table->date('invoice_date');
            $table->date('transaction_date')->nullable();
            $table->date('date_paid')->nullable();
            $table->date('date_before')->nullable();
            $table->unsignedInteger('user');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->string('duplicata', 1)->nullable();
            $table->unsignedInteger('bank_account')->nullable();
            $table->unsignedInteger('attached_to')->nullable();

            $table->index('id', 'invoice_id');
            $table->index('pay_mean', 'pay_mean_invoice');
            $table->index('currency', 'currency_invoice');
            $table->index('doc_type', 'cashflow_doctype');
            $table->index('account_id', 'mfw_accounts_invoices_account_id_index');

            $table->foreign('account_id', 'FK_mfw_accounts_invoices_users')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::create('mfw_accounts_invoices_structure', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('invoice_id');
            $table->text('content');
            $table->unsignedInteger('quantity');
            $table->integer('amount');
            $table->integer('vat');
            $table->unsignedBigInteger('vat_id')->nullable();

            $table->unique('id', 'id');
            $table->index('invoice_id', 'call_id');

            $table->foreign('invoice_id', 'mfw_accounts_invoices_structure_invoice_id_fk')
                ->references('id')
                ->on('mfw_accounts_invoices')
                ->cascadeOnDelete();
            $table->foreign('vat_id', 'mfw_accounts_invoices_structure_vat_id_foreign')
                ->references('id')
                ->on('mfw_accounts_vat');
        });

        Schema::create('mfw_accounts_invoice_expense_associations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('parent_invoice_id');
            $table->unsignedInteger('associated_invoice_id');

            $table->unique('associated_invoice_id', 'expense_assoc_invoice_unique');
            $table->index('parent_invoice_id', 'expense_assoc_parent_index');

            $table->foreign('associated_invoice_id', 'mfw_accounts_invoice_expense_associations_associated_invoice_fk')
                ->references('id')
                ->on('mfw_accounts_invoices')
                ->cascadeOnDelete();
            $table->foreign('parent_invoice_id', 'mfw_accounts_invoice_expense_associations_parent_invoice_fk')
                ->references('id')
                ->on('mfw_accounts_invoices')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfw_accounts_invoice_expense_associations');
        Schema::dropIfExists('mfw_accounts_invoices_structure');
        Schema::dropIfExists('mfw_accounts_invoices');
    }
};
