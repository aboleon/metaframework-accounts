<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Mailer;

use App\Mailer\Traits\MailableCommons;
use MetaFramework\Mailer\Mailer\MailerAbstract;
use MetaFramework\Accounts\Models\Invoice as InvoiceModel;

class Invoice extends MailerAbstract
{
    use MailableCommons;

    protected ?InvoiceModel $invoice = null;

    protected string $locale;

    protected function preferredLocaleFromIdentifier(string $identifier): ?string
    {
        return InvoiceModel::where('hash', $identifier)->value('pdf_locale');
    }

    public function setData(): self
    {
        $this->invoice = $this->model ?: InvoiceModel::where('hash', $this->identifier)->first();

        if (!$this->invoice) {
            $this->responseError(__('mfw-accounts::mailer/invoice.not_found'));

            return $this;
        }

        $this->invoice->load(['client', 'details', 'currencyType']);

        if (empty($this->invoice->client?->email)) {
            $this->responseError(__('mfw-accounts::ui.clientMailIsInvalide'));

            return $this;
        }

        $this->setViewData('invoice', $this->invoice);
        $this->setViewData('client', $this->invoice->client);
        $this->setViewData('pdf_url', url('mfw-accounts/pdf/' . $this->invoice->hash));
        $this->setViewData('locale', $this->locale);

        return $this;
    }

    public function email(): string|array
    {
        return $this->invoice->client->email;
    }

    public function subject(): string
    {
        return __('mfw-accounts::mailer/invoice.subject', [], $this->locale);
    }

    public function view(): string
    {
        return 'mfw-accounts::mails.invoice';
    }

    public function whenSent(): void
    {
        $sentAt = now();

        if ($this->invoice) {
            $this->invoice->sent_at = $sentAt;
            $this->invoice->save();

            return;
        }

        if ($this->identifier) {
            InvoiceModel::where('hash', $this->identifier)->update(['sent_at' => $sentAt]);
        }
    }

    public function successMessage(): string
    {
        return __('mfw-accounts::mailer/invoice.success', [], $this->locale);
    }

    public function failureMessage(): string
    {
        return __('mfw-accounts::mailer/invoice.failure', [], $this->locale);
    }
}
