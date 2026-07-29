<x-front-mail :title="__('mfw-accounts::mailer/invoice.subject', [], $locale)" :preheader="__('mfw-accounts::mailer/invoice.intro', [], $locale)" :locale="$locale">
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
    @endphp

    <p>
        {{ __('mfw-accounts::mailer/invoice.greeting', ['name' => $clientName], $locale) }}
    </p>

    <p>{{ __('mfw-accounts::mailer/invoice.intro', [], $locale) }}</p>

    @php
        $currency = $invoice->currencyType ?: $currencies?->firstWhere('id', $invoice->currency);
        $currencyLabel =
            $currency?->symbol ??
            ($currency?->code ?? \MetaFramework\Accounts\Models\Invoice::reportingCurrencyLabel());
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
    @endphp

    <div style="background:#f7f7fb; border:1px solid #ececf3; border-radius:14px; padding:16px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size:14px;">
            <tr>
                <td style="padding:6px 0; color:#6b7280;">
                    {{ __('mfw-accounts::mailer/invoice.invoice_number', [], $locale) }}
                </td>
                <td style="padding:6px 0; text-align:right; font-weight:700; color:#111827;">
                    {{ $invoice->document_id }}
                </td>
            </tr>
            <tr>
                <td style="padding:6px 0; color:#6b7280;">
                    {{ __('mfw-accounts::mailer/invoice.invoice_date', [], $locale) }}
                </td>
                <td style="padding:6px 0; text-align:right; font-weight:700; color:#111827;">
                    {{ $invoice->invoice_date }}
                </td>
            </tr>
            @if ($invoice->title)
                <tr>
                    <td style="padding:6px 0; color:#6b7280;">
                        {{ __('mfw-accounts::mailer/invoice.invoice_title', [], $locale) }}
                    </td>
                    <td style="padding:6px 0; text-align:right; font-weight:700; color:#111827;">
                        {{ $invoice->title }}
                    </td>
                </tr>
            @endif
            <tr>
                <td style="padding:6px 0; color:#6b7280;">
                    {{ __('mfw-accounts::mailer/invoice.amount', [], $locale) }}
                </td>
                <td style="padding:6px 0; text-align:right; font-weight:700; color:#111827;">
                    {{ $amountDisplay }}
                </td>
            </tr>
        </table>
    </div>

    <div style="text-align:center; margin:24px 0 16px;">
        <a href="{{ $pdf_url }}" target="_blank"
            style="display:inline-block; background:#4CAF50; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:12px;">
            {{ __('mfw-accounts::mailer/invoice.download_pdf', [], $locale) }}
        </a>
    </div>

    <p>{{ __('mfw-accounts::mailer/invoice.closing', [], $locale) }}</p>

    <p>
        {{ __('mfw-accounts::ui.MailEnd', [], $locale) }}<br>
        {{ $signatureName }}
    </p>

</x-front-mail>
