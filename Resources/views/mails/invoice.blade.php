<x-front-mail
    :title="__('mfw-accounts::mailer/invoice.subject', [], $locale)"
    :preheader="__('mfw-accounts::mailer/invoice.intro', [], $locale)"
    :locale="$locale"
>
    @php
        $clientName = html_entity_decode(trim($client->first_name.' '.$client->last_name), ENT_QUOTES);
        $signatureName = auth()->user()?->names() ?? 'Andrian MIHAILOV';
    @endphp

    <p>
        {{ __('mfw-accounts::mailer/invoice.greeting', ['name' => $clientName], $locale) }}
    </p>

    <p>{{ __('mfw-accounts::mailer/invoice.intro', [], $locale) }}</p>

    @php
        $currency = $invoice->currencyType ?: $currencies?->firstWhere('id', $invoice->currency);
        $currencyLabel = $currency?->symbol ?? $currency?->code ?? \MetaFramework\Accounts\Models\Invoice::reportingCurrencyLabel();
        $isDetailed = $invoice->details && $invoice->details->isNotEmpty();
        $amountTotalCents = 0;

        if ($isDetailed) {
            foreach ($invoice->details as $detail) {
                $lineTotal = (float) $detail->amount * (float) $detail->quantity;
                $amountTotalCents += \MetaFramework\Accessors\Prices::toInteger($lineTotal) + (int) ($detail->vat ?? 0);
            }
        } else {
            $amountTotalCents = (int) ($invoice->amount ?? 0) + (int) ($invoice->vat ?? 0);
        }

        $showDecimals = $amountTotalCents % 100 !== 0;
        $amountTotal = \MetaFramework\Accessors\Prices::fromInteger($amountTotalCents);
        $amountDisplay = \MetaFramework\Accessors\Prices::readableFormat($amountTotal, $currencyLabel, ',', ' ', $showDecimals);
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
            @if($invoice->title)
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
        <a href="{{ $pdf_url }}" target="_blank" style="display:inline-block; background:#4CAF50; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:12px;">
            {{ __('mfw-accounts::mailer/invoice.download_pdf', [], $locale) }}
        </a>
    </div>

    <p>{{ __('mfw-accounts::mailer/invoice.closing', [], $locale) }}</p>

    <p>
        {{ __('mfw-accounts::ui.MailEnd', [], $locale) }}<br>
        {{ $signatureName }}
    </p>

</x-front-mail>
