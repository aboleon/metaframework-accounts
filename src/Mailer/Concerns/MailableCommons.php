<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Mailer\Concerns;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Support\Facades\Mail;
use Throwable;

trait MailableCommons
{
    public function __construct()
    {
        $this->applyLocale(app()->getLocale());
    }

    public function setModel(object $model): self
    {
        $this->model = $model;
        $this->applyLocale($this->preferredLocaleFromModel($model));

        return $this;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        $preferred = $this->preferredLocaleFromIdentifier($identifier);

        if ($preferred) {
            $this->applyLocale($preferred);
        }

        return $this;
    }

    public function from(): string|array|Address
    {
        $name = __('mfw.mailer.from_name');

        if ($name === 'mfw.mailer.from_name') {
            $name = config('app.name');
        }

        return new Address((string) config('mail.from.address'), (string) $name);
    }

    protected function preferredLocaleFromModel(object $model): ?string
    {
        return $model->pdf_locale ?? $model->locale ?? null;
    }

    protected function preferredLocaleFromIdentifier(string $identifier): ?string
    {
        return null;
    }

    protected function applyLocale(?string $preferred): void
    {
        $this->locale = $this->resolveLocale($preferred);
        app()->setLocale($this->locale);
    }

    protected function resolveLocale(?string $preferred): string
    {
        $activeLocales = config('mfw.translatable.active_locales', []);
        $activeLocales = is_array($activeLocales) ? $activeLocales : [];
        $candidate = strtolower(trim((string) ($preferred ?? '')));

        if ($candidate !== '' && in_array($candidate, $activeLocales, true)) {
            return $candidate;
        }

        $appLocale = app()->getLocale();

        if (in_array($appLocale, $activeLocales, true)) {
            return $appLocale;
        }

        $fallback = config('mfw.translatable.fallback_locale');

        if (is_string($fallback) && in_array($fallback, $activeLocales, true)) {
            return $fallback;
        }

        return $appLocale;
    }

    public function composeLogData(?Throwable $throwable = null): array
    {
        $viewData = method_exists($this, 'getViewData') ? $this->getViewData() : [];
        $requestData = method_exists($this, 'getRequestData') ? $this->getRequestData() : [];

        $model = $this->model ?? null;
        $accountId = null;

        if (is_object($model)) {
            $accountId = $model->account_id ?? null;

            if ($accountId === null && method_exists($model, 'getAttribute')) {
                $accountId = $model->getAttribute('account_id');
            }
        }

        if ($accountId === null && isset($viewData['client']) && is_object($viewData['client'])) {
            $accountId = $viewData['client']->id ?? null;
        }

        $authUser = auth()->user();
        $mailTo = null;
        $subject = null;
        $view = null;

        try {
            $mailTo = $this->email();
        } catch (Throwable) {
        }

        try {
            $subject = $this->subject();
        } catch (Throwable) {
        }

        try {
            $view = $this->view();
        } catch (Throwable) {
        }

        return [
            'timestamp' => now()->toIso8601String(),
            'mailer' => static::class,
            'identifier' => $this->identifier ?? null,
            'model_class' => is_object($model) ? $model::class : null,
            'account_id' => $accountId,
            'auth_user_id' => $authUser?->id,
            'auth_user_email' => $authUser?->email,
            'mail_to' => $mailTo,
            'subject' => $subject,
            'view' => $view,
            'locale' => app()->getLocale(),
            'request_data' => $requestData,
            'view_data' => $viewData,
            'exception' => $throwable ? [
                'class' => $throwable::class,
                'message' => $throwable->getMessage(),
                'code' => $throwable->getCode(),
            ] : null,
        ];
    }

    public function logToFile(?Throwable $throwable = null): void
    {
        $path = storage_path('logs/mail-failures.log');
        $payload = json_encode($this->composeLogData($throwable), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($payload === false) {
            $payload = '{"error":"unable_to_encode_mail_failure"}';
        }

        file_put_contents($path, $payload . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public function mailLog(?Throwable $throwable = null): void
    {
        $support = config('mail.support', []);
        $recipients = is_array($support) ? $support : [$support];
        $recipients = array_values(array_filter($recipients));

        if ($recipients === []) {
            return;
        }

        $data = $this->composeLogData($throwable);
        $subject = 'Mail failure: ' . $data['mailer'];
        $body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($body === false) {
            $body = 'Unable to encode mail failure payload.';
        }

        try {
            Mail::raw($body, function ($message) use ($recipients, $subject) {
                $message->to($recipients)->subject($subject);
            });
        } catch (Throwable) {
        }
    }
}
