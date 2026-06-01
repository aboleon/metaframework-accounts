<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MetaFramework\Accounts\Enum\UserType;
use MetaFramework\Support\UserTypes;

return new class extends Migration
{
    public function up(): void
    {
        if (!$this->canModifyUserTypeEnum()) {
            return;
        }

        $column = UserTypes::column();

        DB::statement(sprintf(
            "ALTER TABLE users MODIFY %s ENUM('%s','%s','%s','%s') NOT NULL DEFAULT '%s'",
            $column,
            UserType::ACCOUNT->value,
            UserType::SYSTEM->value,
            UserType::COMPANY->value,
            UserType::AGENT->value,
            UserType::SYSTEM->value,
        ));
    }

    public function down(): void
    {
        if (!$this->canModifyUserTypeEnum()) {
            return;
        }

        DB::statement(sprintf(
            "ALTER TABLE users MODIFY %s ENUM('%s','%s') NOT NULL DEFAULT '%s'",
            UserTypes::column(),
            UserType::ACCOUNT->value,
            UserType::SYSTEM->value,
            UserType::SYSTEM->value,
        ));
    }

    private function canModifyUserTypeEnum(): bool
    {
        return DB::connection()->getDriverName() === 'mysql'
            && Schema::hasTable('users')
            && Schema::hasColumn('users', UserTypes::column());
    }
};
