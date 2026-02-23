<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use MetaFramework\Polyglote\Interfaces\TranslatableInterface;
use MetaFramework\Polyglote\Traits\Translation;

class Company extends Model implements TranslatableInterface
{
    use Translation;

    protected $table = 'mfw_accounts_company';

    public $incrementing = false;

    public $timestamps = false;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    public static function info(?string $lang = null): Collection
    {
        $lang ??= app()->getLocale();

        $company = self::query()->first() ?? new self(self::defaultSettingsAttributes());
        $companyId = (int) ($company->id ?: 1);

        $company->setRelation('data', CompanyData::query()->where('company_id', $companyId)->where('lg', $lang)->get());

        return collect(['company' => $company]);
    }

    public function setTranslatables(): array
    {
        return [
            'name' => [
                'label' => __('mfw-accounts::ui.CompanyName'),
                'class' => 'col-sm-6',
            ],
            'owner' => [
                'label' => __('mfw-accounts::ui.CompanyOwner'),
                'class' => 'col-sm-6',
            ],
            'adresse' => [
                'label' => __('mfw-accounts::ui.Adress'),
                'class' => 'col-12',
            ],
            'licence' => [
                'label' => 'N° ' . __('mfw-accounts::ui.licence'),
                'class' => 'col-sm-4',
            ],
        ];
    }

    public function data(): HasMany
    {
        return $this->hasMany(CompanyData::class, 'company_id');
    }

    public static function scalarSettingsFields(): array
    {
        return [
            'bilan_start',
            'bilan_end',
            'EIN',
            'VAT',
            'website',
            'email',
            'phone',
        ];
    }

    public static function defaultSettingsAttributes(): array
    {
        return [
            'id' => 1,
            'EIN' => '',
            'VAT' => '',
            'bilan_start' => '',
            'bilan_end' => '',
            'website' => '',
            'email' => '',
            'phone' => '',
        ];
    }

    public function hydrateLegacyTranslatables(): static
    {
        $fields = array_keys($this->getTranslatableProperties());

        foreach ($fields as $field) {
            $translations = [];

            foreach ($this->data as $row) {
                if (!isset($row->lg)) {
                    continue;
                }

                $translations[(string) $row->lg] = $row->{$field} ?? null;
            }

            $this->setAttribute($field, $translations);
        }

        return $this;
    }
}


