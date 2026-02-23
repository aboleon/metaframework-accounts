<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MetaFramework\Accounts\Enum\UserType;
use MetaFramework\Support\UserTypes;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        $column = UserTypes::column();

        if (!Schema::hasColumn('users', $column)) {
            Schema::table('users', function (Blueprint $table) use ($column) {
                $table->string($column, 32)
                    ->default(UserType::SYSTEM->value);
            });
        }

        DB::table('users')
            ->where(function ($query) use ($column) {
                $query
                    ->whereNull($column)
                    ->orWhere($column, '');
            })
            ->update([$column => UserType::SYSTEM->value]);
    }

    public function down(): void
    {
        // Intentionally left as no-op: host apps may already rely on this column.
    }
};
