<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use MetaFramework\Accounts\Support\DateFormat;
use MetaFramework\Polyglote\Interfaces\TranslatableInterface;
use MetaFramework\Polyglote\Traits\Translation;

class Account extends AccountUser implements TranslatableInterface
{
    use Translation;

    protected $table = 'users';

    protected static function boot()
    {
        parent::boot();
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

        $date1 = DateFormat::convert((string) $date, 'd/m/Y', 'Y-m-d');
        $date2 = DateFormat::convert((string) $date2, 'd/m/Y', 'Y-m-d');

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
        $query->clientType(isset($filters['client_type']) ? (int) $filters['client_type'] : null);
        $query->dateRange($filters['date_operator'] ?? null, $filters['date'] ?? null, $filters['date2'] ?? null);
        $query->clientId(isset($filters['account_id']) ? (int) $filters['account_id'] : null);

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

    public function setTranslatables(): array
    {
        return [
            'first_name' => [
                'label' => __('mfw-accounts::ui.FirstName'),
                'class' => 'col-md-6',
            ],
            'last_name' => [
                'label' => __('mfw-accounts::ui.LastName'),
                'class' => 'col-md-6',
            ],
        ];
    }
}
