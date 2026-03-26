<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Actions;

use Illuminate\Support\Str;
use MetaFramework\Accounts\Enum\UserType;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Models\AccountAgent;

class AccountAgentActions
{
    public function persist(Account $company, AccountAgent $agent, array $data): AccountAgent
    {
        $agent->first_name = $data['first_name'] ?? $agent->first_name;
        $agent->last_name = $data['last_name'] ?? $agent->last_name;
        $agent->email = $data['email'] ?? $agent->email;
        $agent->phone = $data['phone'] ?? $agent->phone;
        $agent->civ = $data['civ'] ?? $agent->civ;
        $agent->locale = $data['locale'] ?? $agent->locale ?? $company->locale ?? app()->getLocale();
        $agent->company_id = $company->id;
        $agent->type = UserType::AGENT->value;

        if (!$agent->exists && empty($agent->password)) {
            $agent->password = Str::random(40);
        }

        $agent->save();

        return $agent;
    }
}
