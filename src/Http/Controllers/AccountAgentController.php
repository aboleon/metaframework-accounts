<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use MetaFramework\Accounts\Actions\AccountAgentActions;
use MetaFramework\Accounts\Http\Requests\SaveAccountAgentRequest;
use MetaFramework\Accounts\Models\Account;
use MetaFramework\Accounts\Models\AccountAgent;
use MetaFramework\Support\Traits\Ajax;

class AccountAgentController
{
    use Ajax;

    public function handle(Request $request, Account $client, AccountAgentActions $actions): array
    {
        $this->ajaxMode();

        abort_if($client->isAgent(), 404);
        abort_unless($client->isCompany(), 404);

        match ($request->string('agent_action')->toString()) {
            'create' => $this->persistFromHandle($request, $client, new AccountAgent, $actions, action: 'create'),
            'update' => $this->persistFromHandle(
                $request,
                $client,
                AccountAgent::query()->findOrFail((int) $request->input('agent_id')),
                $actions,
                action: 'update',
            ),
            'delete' => $this->destroyFromHandle(
                $client,
                AccountAgent::query()->findOrFail((int) $request->input('agent_id')),
            ),
            default => $this->responseError(__('ui.error')),
        };

        return $this->fetchResponse();
    }

    protected function persistFromHandle(
        Request $request,
        Account $client,
        AccountAgent $agent,
        AccountAgentActions $actions,
        string $action,
    ): void {
        $formRequest = app(SaveAccountAgentRequest::class);
        $validator = Validator::make(
            $request->all(),
            $formRequest->rules(),
            [],
            $formRequest->attributes(),
        );

        try {
            $validated = $validator->validate();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->responseError((string) $message);
                }
            }

            return;
        }

        if ($agent->exists) {
            abort_unless((int) $agent->company_id === (int) $client->id, 404);
        }

        $actions->persist($client, $agent, $validated);
        $this->responseSuccess(__('mfw-accounts::ui.agent.saved'));
        $this->responseElement('callback', 'handleClientAgentActionResult');
        $this->responseElement('agent_action', $action);
        $this->responseElement('agent_id', $agent->id);
        $this->responseElement('no_agents_text', __('mfw-accounts::ui.no_agents'));
        $this->responseElement('agent_html', view('mfw-accounts::clients.partials.agent_card', [
            'agent' => $agent,
            'data' => $client,
        ])->render());
    }

    protected function destroyFromHandle(Account $client, AccountAgent $agent): void
    {
        abort_if($client->isAgent(), 404);
        abort_unless((int) $agent->company_id === (int) $client->id, 404);

        $agentId = $agent->id;
        $agent->delete();
        $this->responseSuccess(__('mfw-accounts::ui.agent.deleted'));
        $this->responseElement('callback', 'handleClientAgentActionResult');
        $this->responseElement('agent_action', 'delete');
        $this->responseElement('agent_id', $agentId);
        $this->responseElement('no_agents_text', __('mfw-accounts::ui.no_agents'));
    }

    public function store(SaveAccountAgentRequest $request, Account $client, AccountAgentActions $actions): RedirectResponse
    {
        abort_if($client->isAgent(), 404);
        abort_unless($client->isCompany(), 404);

        $actions->persist($client, new AccountAgent, $request->validated());

        return redirect()
            ->route('mfw-accounts.clients.edit', $client)
            ->with('session_message', __('mfw-accounts::ui.agent.saved'));
    }

    public function update(
        SaveAccountAgentRequest $request,
        Account $client,
        AccountAgent $agent,
        AccountAgentActions $actions,
    ): RedirectResponse {
        abort_if($client->isAgent(), 404);
        abort_unless($client->isCompany(), 404);
        abort_unless((int) $agent->company_id === (int) $client->id, 404);

        $actions->persist($client, $agent, $request->validated());

        return redirect()
            ->route('mfw-accounts.clients.edit', $client)
            ->with('session_message', __('mfw-accounts::ui.agent.saved'));
    }

    public function destroy(Account $client, AccountAgent $agent): RedirectResponse
    {
        abort_if($client->isAgent(), 404);
        abort_unless((int) $agent->company_id === (int) $client->id, 404);

        $agent->delete();

        return redirect()
            ->route('mfw-accounts.clients.edit', $client)
            ->with('session_message', __('mfw-accounts::ui.agent.deleted'));
    }
}
