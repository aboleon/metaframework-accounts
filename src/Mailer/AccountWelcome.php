<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Mailer;

use Illuminate\Mail\Mailables\Address;
use MetaFramework\Accounts\Mailer\Concerns\MailableCommons;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Support\AccountModel;
use MetaFramework\Accounts\Support\AccountWelcomePasswordStore;
use MetaFramework\Mailer\Mailer\MailerAbstract;

class AccountWelcome extends MailerAbstract
{
    use MailableCommons;

    protected ?Account $account = null;

    protected string $locale;

    protected ?string $token = null;

    protected ?string $hashedPassword = null;

    protected function preferredLocaleFromIdentifier(string $identifier): ?string
    {
        return AccountModel::className()::query()->whereKey((int) $identifier)->value('locale');
    }

    public function setData(): self
    {
        $accountClass = AccountModel::className();
        $this->account = $this->model instanceof Account
            ? $this->model
            : $accountClass::query()->find((int) $this->identifier);

        if (!$this->account) {
            $this->responseError(__('mfw-accounts::ui.client.not_found'));

            return $this;
        }

        $this->token = trim((string) $this->getRequestData('token'));
        $passwordData = $this->token !== ''
            ? (new AccountWelcomePasswordStore)->retrieve($this->account, $this->token)
            : null;

        if (!$passwordData) {
            $this->responseError(__('mfw-accounts::mailer/account_welcome.password_expired'));

            return $this;
        }

        if (empty($this->account->email) || filter_var((string) $this->account->email, FILTER_VALIDATE_EMAIL) === false) {
            $this->responseError(__('mfw-accounts::ui.ClientInvalidEmail'));

            return $this;
        }

        $this->hashedPassword = $passwordData['hashed_password'];

        $loginUrl = trim((string) config('mfw-accounts.frontend.welcome_url', ''));
        $loginUrl = $loginUrl !== '' ? $loginUrl : (string) config('app.url');

        $this->setViewData('account', $this->account);
        $this->setViewData('client', $this->account);
        $this->setViewData('locale', $this->locale);
        $this->setViewData('email', $this->account->email);
        $this->setViewData('password', $passwordData['password']);
        $this->setViewData('login_url', $loginUrl);
        $this->setViewData('app_url', (string) config('app.url'));

        return $this;
    }

    public function email(): string|array|Address
    {
        $email = trim((string) ($this->account?->email ?? ''));
        $name = trim((string) ($this->account?->first_name ?? '') . ' ' . (string) ($this->account?->last_name ?? ''));

        if ($email === '') {
            return '';
        }

        return $name === '' ? $email : new Address($email, $name);
    }

    public function subject(): string
    {
        return __('mfw-accounts::mailer/account_welcome.subject', [], $this->locale);
    }

    public function view(): string
    {
        return 'mfw-accounts::mails.account_welcome';
    }

    public function textView(): string
    {
        return 'mfw-accounts::mails.account_welcome_plain';
    }

    public function whenSent(): void
    {
        if ($this->account && $this->hashedPassword) {
            $this->account->password = $this->hashedPassword;
            $this->account->save();
        }

        if ($this->token) {
            (new AccountWelcomePasswordStore)->forget($this->token);
        }
    }

    public function successMessage(): string
    {
        return __('mfw-accounts::mailer/account_welcome.success', [], $this->locale);
    }

    public function failureMessage(): string
    {
        return __('mfw-accounts::mailer/account_welcome.failure', [], $this->locale);
    }
}
