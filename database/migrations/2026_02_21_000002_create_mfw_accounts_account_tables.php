<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mfw_accounts_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyText('prenom')->nullable();
            $table->tinyText('nom')->nullable();
            $table->tinyText('adresse')->nullable();
            $table->text('prenom_alt')->nullable();
            $table->text('nom_alt')->nullable();
            $table->text('adresse_alt')->nullable();
            $table->unsignedInteger('localisation')->nullable();
            $table->unsignedInteger('country')->nullable();
            $table->string('phone', 128)->nullable();
            $table->string('email', 128)->nullable();
            $table->tinyText('societe')->nullable();
            $table->tinyText('societe_alt')->nullable();
            $table->tinyText('tva')->nullable();
            $table->tinyText('siret')->nullable();
            $table->enum('civ', ['M', 'Mme', 'Mlle'])->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('mfw_accounts_account_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('billing')->default(true);
            $table->string('street_number')->nullable();
            $table->longText('route')->nullable();
            $table->longText('locality')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country_code')->nullable();
            $table->string('place_id')->nullable();
            $table->longText('text_address')->nullable();
            $table->decimal('lat', 16, 13)->nullable();
            $table->decimal('lon', 16, 13)->nullable();
            $table->string('company')->nullable();
            $table->text('complementary')->nullable();
            $table->longText('administrative_area_level_1')->nullable();
            $table->longText('administrative_area_level_2')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'mfw_accounts_account_address_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::create('mfw_accounts_account_business', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->longText('name')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('reg_number')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'mfw_accounts_account_business_user_id_foreign')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mfw_accounts_account_business');
        Schema::dropIfExists('mfw_accounts_account_address');
        Schema::dropIfExists('mfw_accounts_accounts');
    }
};
