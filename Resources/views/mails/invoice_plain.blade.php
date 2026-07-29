@php
    $civilityTranslationKey = [
        'A' => 'M',
        'B' => 'Mme',
        'C' => 'Mlle',
        'M' => 'M',
        'Mme' => 'Mme',
        'Mlle' => 'Mlle',
    ][(string) ($client->civ ?? '')] ?? [
        'A' => 'M',
        'B' => 'Mme',
        'C' => 'Mlle',
    ][(string) config('mfw-accounts.client.default_civility', 'A')] ?? null;
    $civility = $civilityTranslationKey
        ? trim((string) __('mfw-accounts::ui.mail_civility.' . $civilityTranslationKey, [], $locale))
        : '';
    if ($civilityTranslationKey && $civility === 'mfw-accounts::ui.mail_civility.' . $civilityTranslationKey) {
        $civility = '';
    }
    $clientName = html_entity_decode(trim(trim($civility . ' ' . (string) $client->last_name)), ENT_QUOTES);
    $signatureName = auth()->user()?->names();

    if (empty($signatureName)) {
        $translationKey = trim((string) config('mfw.mailer.from.translation_key', 'mfw::mfw.mailer.from_name'));
        $strictLocaleTranslation = (bool) config('mfw.mailer.from.strict_locale_translation', true);

        if ($translationKey !== '') {
            $hasForLocale =
                $locale !== ''
                    ? \Illuminate\Support\Facades\Lang::hasForLocale($translationKey, $locale)
                    : \Illuminate\Support\Facades\Lang::has($translationKey);

            if ($hasForLocale) {
                $translated = trim((string) __($translationKey, [], $locale));

                if ($translated !== '' && $translated !== $translationKey) {
                    $signatureName = $translated;
                }
            } elseif (!$strictLocaleTranslation) {
                $translated = trim((string) __($translationKey));

                if ($translated !== '' && $translated !== $translationKey) {
                    $signatureName = $translated;
                }
            }
        }

        if (empty($signatureName)) {
            $signatureName = trim((string) config('mfw.mailer.from.name', ''));
        }

        if ($signatureName === '') {
            $signatureName = trim((string) config('mail.from.name', ''));
        }

        if ($signatureName === '') {
            $signatureName = (string) config('app.name');
        }
    }

    $currency = $invoice->currencyType ?: $currencies?->firstWhere('id', $invoice->currency);
    $currencyLabel =
        $currency?->symbol ?? ($currency?->code ?? \MetaFramework\Accounts\Models\Invoice::reportingCurrencyLabel());
    $isDetailed = $invoice->details && $invoice->details->isNotEmpty();
    $amountTotal = 0.0;

    if ($isDetailed) {
        foreach ($invoice->details as $detail) {
            $lineQuantity = is_numeric($detail->quantity ?? null) ? (float) $detail->quantity : 1.0;
            $lineTotal = (float) ($detail->amount ?? 0) * $lineQuantity;
            $amountTotal += $lineTotal + (float) ($detail->vat ?? 0);
        }
    } else {
        $quantity = is_numeric($invoice->quantity ?? null) ? (float) $invoice->quantity : 1.0;
        $amountTotal = (float) ($invoice->amount ?? 0) * $quantity + (float) ($invoice->vat ?? 0);
    }

    $amountTotal = round($amountTotal, 2);
    $showDecimals = abs($amountTotal - round($amountTotal)) > 0.00001;
    $amountDisplay = \MetaFramework\Accessors\Prices::readableFormat(
        $amountTotal,
        $currencyLabel,
        ',',
        ' ',
        $showDecimals,
    );
    $textFooterView = trim((string) config('mfw.mailer.text.footer_view', ''));
    $textFooterSeparator = config('mfw.mailer.text.footer_separator');
    $hasTextFooterView = $textFooterView !== '' && view()->exists($textFooterView);
    $textFooterSeparator = is_string($textFooterSeparator) ? trim($textFooterSeparator) : '';
@endphp
{{ __('mfw-accounts::mailer/invoice.subject', [], $locale) }}

{{ __('mfw-accounts::mailer/invoice.greeting', ['name' => $clientName], $locale) }}

{{ __('mfw-accounts::mailer/invoice.intro', [], $locale) }}

{{ __('mfw-accounts::mailer/invoice.invoice_number', [], $locale) }}: {{ $invoice->document_id }}
{{ __('mfw-accounts::mailer/invoice.invoice_date', [], $locale) }}: {{ $invoice->invoice_date }}
@if ($invoice->title)
    {{ __('mfw-accounts::mailer/invoice.invoice_title', [], $locale) }}: {{ $invoice->title }}
@endif
{{ __('mfw-accounts::mailer/invoice.amount', [], $locale) }}: {{ $amountDisplay }}

{{ __('mfw-accounts::mailer/invoice.download_pdf', [], $locale) }}:
{{ $pdf_url }}

{{ __('mfw-accounts::mailer/invoice.closing', [], $locale) }}

{{ __('mfw-accounts::ui.MailEnd', [], $locale) }}
{{ $signatureName }}

@if ($hasTextFooterView)
    @php
        if ($textFooterSeparator !== '') {
            echo $textFooterSeparator . PHP_EOL . PHP_EOL;
        }
    @endphp
    @include($textFooterView, [
        'locale' => $locale,
        'invoice' => $invoice,
        'client' => $client,
        'pdf_url' => $pdf_url,
    ])
@endif
