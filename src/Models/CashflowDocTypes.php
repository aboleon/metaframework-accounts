<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MetaFramework\Polyglote\Interfaces\TranslatableInterface;
use MetaFramework\Polyglote\Traits\Translation;

class CashflowDocTypes extends Model implements TranslatableInterface
{

    use Translation;

    public $timestamps = false;

    protected $table = 'mfw_accounts_cashflow_doc_types';

    protected $fillable = [
        'slug',
        'admin_name',
        'name',
        'default',
        'numerotation',
    ];

    public function setTranslatables(): array
    {
        return [
            'admin_name' => [
                'label' => __('mfw-accounts::ui.Name') . ' (admin)',
            ],
            'name'       => [
                'label' => __('mfw-accounts::ui.Name'),
            ],
        ];
    }

    public static function select_form(string|int|null $value = null, bool $null_value = false, array $exclude = []): string
    {
        $query   = self::query();
        $against = is_string($value) ? 'slug' : 'id';

        if ($exclude) {
            $query->whereNotIn('slug', $exclude);
        }

        $docs = $query->get();

        $html   = "<select class='form-control' name='doc_type'>";

        if ($null_value) {
            $html .= "<option value='0'>" . __('ui.filters.all') . '</option>';
        }

        foreach ($docs as $doc) {
            $label = $doc->admin_name ?: $doc->name;
            $selected = $value
                ? ($value == $doc->{$against} ? ' selected' : null)
                : (!$null_value && $doc->default ? ' selected' : null);

            $html .= "<option value='{$doc->id}'{$selected}>{$label}</option>";
        }

        $html .= '</select>';

        return $html;
    }

    public static function simpleList(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        return self::query()
            ->select('id', 'admin_name', 'name')
            ->get()
            ->mapWithKeys(function (self $docType) use ($locale) {
                return [
                    $docType->id => [
                        'admin_name' => $docType->translation('admin_name', $locale),
                        'name'       => $docType->translation('name', $locale),
                    ],
                ];
            })
            ->toArray();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'doc_type');
    }
}
