<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use App\Models\AccountUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MetaFramework\Polyglote\Interfaces\TranslatableInterface;

final class Account extends AccountUser implements TranslatableInterface
{
    protected $table = 'users';

    protected static function boot()
    {
        parent::boot();
    }

    public static function Form($value = null): string
    {
        $data = self::select('id', 'first_name', 'last_name')
            ->with('business')
            ->orderBy('last_name')
            ->get();

        $html = "<select class='form-control' name='clients'>";
        $html .= "<option value='0'>" . __('ui.optionChoose') . '</option>';
        foreach ($data as $virgo) {
            $businessName = $virgo->business?->name;
            $html .= "<option value='" . $virgo->id . "'";
            if (!empty($value) && $value == $virgo->id) {
                $html .= ' selected';
            }
            $html .= '>' . $virgo->last_name . ' ' . $virgo->first_name;
            if ($businessName) {
                $html .= ' ' . strtoupper($businessName);
            }
            $html .= '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    public function currencyType(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency');
    }

    public function documents(): HasMany
    {
        return $this->hasMany('MetaFramework\Accounts\Models\Invoice', 'account_id')->whereNull('duplicata');
    }

    public function duplicata(): HasMany
    {
        return $this->hasMany('MetaFramework\Accounts\Models\Invoice', 'account_id')->whereNotNull('duplicata');
    }

    public function identity(): string
    {
        return $this->isCompany() ? $this->business->name : $this->names();
    }

    public function getClient(int $client): ?self
    {
        return Account::whereId($client)->with(['address', 'business'])->first();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'account_id')->whereNull('duplicata')->whereIn('doc_type', [1, 5]);
    }

    public function scopeClientId(Builder $query, ?int $clientId = null): Builder
    {
        if (!empty($clientId)) {
            return $query->where('id', $clientId);
        }

        return $query;
    }

    public function scopeClientType(Builder $query, ?int $clientType = null): Builder
    {
        if (!empty($clientType)) {
            if ($clientType === 1) {
                // Individual (no business record)
                return $query->whereDoesntHave('business');
            }

            // Company (has business record)
            return $query->whereHas('business');
        }

        return $query;
    }

    public function scopeCompany(Builder $query, ?string $company = null): Builder
    {
        if (!empty($company)) {
            return $query->whereHas('business', function ($q) use ($company) {
                $q->where('name', 'like', '%' . $company . '%');
            });
        }

        return $query;
    }

    public function isCompany(): bool
    {
        return $this->business !== null;
    }

    public function scopeDateRange(Builder $query, ?string $operator = null, ?string $date = null, ?string $date2 = null): Builder
    {
        if (empty($operator)) {
            return $query;
        }

        $date1 = \App\Helpers\Helpers::kvasir_dateFormat((string)$date, 'd/m/Y', 'Y-m-d');
        $date2 = \App\Helpers\Helpers::kvasir_dateFormat((string)$date2, 'd/m/Y', 'Y-m-d');

        if (!is_null($date1)) {
            match ($operator) {
                'equal' => $query->whereDate('created_at', $date1),
                'greater' => $query->where('created_at', '>', $date1),
                'less' => $query->where('created_at', '<', $date1),
                default => null,
            };
        }

        if ($operator === 'between' && !is_null($date1) && !is_null($date2)) {
            return $query->whereBetween('created_at', [$date1, $date2]);
        }

        return $query;
    }

    public function scopeFilters(Builder $query, array $filters = []): Builder
    {
        $query->clientType(isset($filters['client_type']) ? (int)$filters['client_type'] : null);
        $query->dateRange($filters['date_operator'] ?? null, $filters['date'] ?? null, $filters['date2'] ?? null);
        $query->clientId(isset($filters['account_id']) ? (int)$filters['account_id'] : null);

        return $query;
    }

    public function address(): HasMany
    {
        return $this->hasMany(AccountAddress::class, 'user_id');
    }

    public function business(): HasOne
    {
        return $this->hasOne(AccountBusiness::class, 'user_id');
    }
}
