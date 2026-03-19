@php
    $actionClass = $class ?? 'btn btn-violet-light';
    $actionText = $text ?? '<i class="bi bi-envelope-fill"></i>';
    $actionLinkTitle = $linktitle ?? __('mfw-accounts::mailer/account_welcome.action');
    $actionConfirm = $confirm ?? __('mfw-accounts::ui.Send');
@endphp

<x-mfw::simple-modal id="send_account_welcome" :class="$actionClass"
    title="{{ __('mfw-accounts::mailer/account_welcome.modal_title') }}"
    body="{{ __('mfw-accounts::mailer/account_welcome.modal_question') }}"
    confirmclass="btn-success confirm-send-account-welcome" :confirm="$actionConfirm"
    callback="bindSendAccountWelcomeByMail" :modelid="$account->id" identifier="{{ $account->id }}"
    :linktitle="$actionLinkTitle" :text="$actionText" />
