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
    $clientName = trim($civility . ' ' . (string) $client->last_name);
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

    $textFooterView = trim((string) config('mfw.mailer.text.footer_view', ''));
    $textFooterSeparator = config('mfw.mailer.text.footer_separator');
    $hasTextFooterView = $textFooterView !== '' && view()->exists($textFooterView);
    $textFooterSeparator = is_string($textFooterSeparator) ? trim($textFooterSeparator) : '';
@endphp

{{ __('mfw-accounts::mailer/account_welcome.greeting', ['name' => $clientName], $locale) }}

{{ __('mfw-accounts::mailer/account_welcome.intro', [], $locale) }}

{{ __('mfw-accounts::mailer/account_welcome.email_label', [], $locale) }}: {{ $email }}
{{ __('mfw-accounts::mailer/account_welcome.password_label', [], $locale) }}: {{ $password }}

{{ __('mfw-accounts::mailer/account_welcome.login_url', [], $locale) }}: {{ $login_url }}

{{ __('mfw-accounts::mailer/account_welcome.change_password_notice', [], $locale) }}

{{ __('mfw-accounts::mailer/account_welcome.account_notice', ['link' => $app_url], $locale) }}

{{ __('mfw-accounts::mailer/account_welcome.closing', [], $locale) }}

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
        'client' => $client,
        'account' => $account ?? $client,
        'login_url' => $login_url,
        'app_url' => $app_url,
    ])
@endif
