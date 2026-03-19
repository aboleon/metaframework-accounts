<x-front-mail :title="__('mfw-accounts::mailer/account_welcome.subject', [], $locale)" :preheader="__('mfw-accounts::mailer/account_welcome.intro', [], $locale)" :locale="$locale">
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
            $translationKey = trim((string) config('mfw.mailer.from.translation_key', 'mfw.mailer.from_name'));
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

    <p>{{ __('mfw-accounts::mailer/account_welcome.greeting', ['name' => $clientName], $locale) }}</p>

    <p>{{ __('mfw-accounts::mailer/account_welcome.intro', [], $locale) }}</p>

    <div style="background:#f7f7fb; border:1px solid #ececf3; border-radius:14px; padding:16px;">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="font-size:14px;">
            <tr>
                <td style="padding:6px 0; color:#6b7280;">
                    {{ __('mfw-accounts::mailer/account_welcome.email_label', [], $locale) }}
                </td>
                <td style="padding:6px 0; text-align:right; font-weight:700; color:#111827;">
                    {{ $email }}
                </td>
            </tr>
            <tr>
                <td style="padding:6px 0; color:#6b7280;">
                    {{ __('mfw-accounts::mailer/account_welcome.password_label', [], $locale) }}
                </td>
                <td style="padding:6px 0; text-align:right; font-weight:700; color:#111827;">
                    {{ $password }}
                </td>
            </tr>
        </table>
    </div>

    <div style="text-align:center; margin:32px 0 24px;">
        <a href="{{ $login_url }}" target="_blank"
            style="display:inline-block; background:#4CAF50; color:#ffffff; text-decoration:none; padding:12px 18px; border-radius:8px;">
            {{ __('mfw-accounts::mailer/account_welcome.login_button', [], $locale) }}
        </a>
    </div>

    <p>{{ __('mfw-accounts::mailer/account_welcome.change_password_notice', [], $locale) }}</p>

    <p>
        {!! __('mfw-accounts::mailer/account_welcome.account_notice', [
            'url' => e($app_url),
            'link' => '<a href="' . e($login_url) . '" target="_blank" style="color:#153e75; font-weight:700;">' . e($app_url) . '</a>',
        ], $locale) !!}
    </p>

    <p>{{ __('mfw-accounts::mailer/account_welcome.closing', [], $locale) }}</p>

    <p>
        {{ __('mfw-accounts::ui.MailEnd', [], $locale) }}<br>
        {{ $signatureName }}
    </p>
</x-front-mail>
