<!-- mfw-accounts Module Nav -->
<li class="pnav-item">
    <a href="{{ route('mfw-accounts.dashboard.index') }}" class="pnav-link">
        <i class="bi bi-graph-up"></i>
        <span>{!! __('mfw-accounts::ui.dashboard') !!}</span>
    </a>
</li>
<li class="pnav-item">
    <a href="{{ route('mfw-accounts.clients.index') }}" class="pnav-link">
        <i class="bi bi-people-fill"></i>
        <span>{!! trans_choice('mfw-accounts::ui.Client', 2) !!}</span>
    </a>
</li>
<li class="pnav-item">
    <a href="{{ route('mfw-accounts.invoices.index') }}" class="pnav-link">
        <i class="bi bi-file-earmark-text"></i>
        <span>{!! Str::ucfirst(trans_choice('mfw-accounts::ui.invoice', 2)) !!}</span>
    </a>
</li>


