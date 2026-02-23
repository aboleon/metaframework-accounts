<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\View\View;
use MetaFramework\Accounts\Http\Controllers\PayMeansChannelController;
use MetaFramework\Accounts\Models\PayMeansChannels;
use MetaFramework\Accounts\Models\PayMeansChannelsData;
use MetaFramework\Accounts\Tests\Feature\Concerns\InteractsWithAccountsControllerData;
use MetaFramework\Accounts\Tests\TestCase;

class PayMeansChannelControllerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithAccountsControllerData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableMultilangForTests();
    }

    public function test_index_returns_filtered_channels_for_selected_pay_mean(): void
    {
        $payMeanA = $this->seedPayMean(['name' => ['fr' => 'Virement']]);
        $payMeanB = $this->seedPayMean(['name' => ['fr' => 'Carte']]);

        $channelA = PayMeansChannels::query()->create(['pay_mean_id' => $payMeanA->id]);
        $channelB = PayMeansChannels::query()->create(['pay_mean_id' => $payMeanB->id]);

        PayMeansChannelsData::query()->create([
            'pay_channel_id' => $channelA->id,
            'lg' => 'fr',
            'name' => 'FR A',
            'description' => 'Desc A',
        ]);
        PayMeansChannelsData::query()->create([
            'pay_channel_id' => $channelB->id,
            'lg' => 'fr',
            'name' => 'FR B',
            'description' => 'Desc B',
        ]);

        $view = (new PayMeansChannelController)->index($payMeanA->id);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('mfw-accounts::PayMeansChannels.index', $view->name());
        $data = $view->getData();

        $this->assertSame(2, $data['payMeans']->count());
        $this->assertSame(1, $data['data']->count());
        $this->assertSame($channelA->id, $data['data']->first()->id);
        $this->assertSame($payMeanA->id, (int) $data['payMeanChannel']->pay_mean_id);
    }

    public function test_store_creates_channel_and_translation_rows_for_all_project_locales(): void
    {
        $this->actingAs($this->createSystemUser());
        $payMean = $this->seedPayMean(['name' => ['fr' => 'Bank']]);

        $response = $this->post(route('mfw-accounts.pay-mean-channels.store'), [
            'category' => $payMean->id,
        ]);

        $channel = PayMeansChannels::query()->latest('id')->first();

        $this->assertNotNull($channel);
        $response->assertRedirect(route('mfw-accounts.pay-mean-channels.edit', $channel));
        $response->assertSessionHas('session_response');

        $translations = PayMeansChannelsData::query()
            ->where('pay_channel_id', $channel->id)
            ->orderBy('lg')
            ->pluck('lg')
            ->all();

        $this->assertSame(['bg', 'en', 'fr'], $translations);
    }

    public function test_store_validates_category(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->from(route('mfw-accounts.pay-mean-channels.index'))
            ->post(route('mfw-accounts.pay-mean-channels.store'), [
                'category' => 999999,
            ]);

        $response->assertRedirect(route('mfw-accounts.pay-mean-channels.index'));
        $response->assertSessionHasErrors(['category']);
    }

    public function test_edit_get_returns_view_with_loaded_channel(): void
    {
        $payMean = $this->seedPayMean(['name' => ['fr' => 'Bank']]);
        $channel = PayMeansChannels::query()->create(['pay_mean_id' => $payMean->id]);
        $this->seedBankAccount();

        PayMeansChannelsData::query()->create([
            'pay_channel_id' => $channel->id,
            'lg' => 'fr',
            'name' => 'Channel FR',
        ]);

        $request = Request::create('/fake', 'GET');
        $view = (new PayMeansChannelController)->edit($request, $channel);

        $this->assertInstanceOf(View::class, $view);
        $this->assertSame('mfw-accounts::PayMeansChannels.edit', $view->name());
        $this->assertSame($channel->id, $view->getData()['data']->id);
    }

    public function test_edit_post_updates_channel_and_translations(): void
    {
        $this->actingAs($this->createSystemUser());
        $payMean = $this->seedPayMean(['name' => ['fr' => 'Bank']]);
        $channel = PayMeansChannels::query()->create(['pay_mean_id' => $payMean->id]);
        $bankAccountId = $this->seedBankAccount(['id' => 7]);

        $response = $this->post(route('mfw-accounts.pay-mean-channels.edit', $channel), [
            'bank_account_id' => $bankAccountId,
            'data' => [
                'fr' => [
                    'name' => 'Compte FR',
                    'description' => 'Description FR',
                ],
                'bg' => [
                    'name' => 'BG',
                    'description' => 'Desc BG',
                ],
            ],
        ]);

        $response->assertRedirect(route('mfw-accounts.pay-mean-channels.edit', $channel));
        $response->assertSessionHas('session_response');

        $channel->refresh();
        $this->assertSame($bankAccountId, (int) $channel->bank_account_id);

        $this->assertSame(3, PayMeansChannelsData::query()->where('pay_channel_id', $channel->id)->count());
        $this->assertDatabaseHas('mfw_accounts_pay_means_channels_data', [
            'pay_channel_id' => $channel->id,
            'lg' => 'fr',
            'name' => 'Compte FR',
            'description' => 'Description FR',
        ]);
        $this->assertDatabaseHas('mfw_accounts_pay_means_channels_data', [
            'pay_channel_id' => $channel->id,
            'lg' => 'en',
        ]);
    }

    public function test_edit_post_validates_payload(): void
    {
        $this->actingAs($this->createSystemUser());
        $payMean = $this->seedPayMean(['name' => ['fr' => 'Bank']]);
        $channel = PayMeansChannels::query()->create(['pay_mean_id' => $payMean->id]);

        $response = $this->from(route('mfw-accounts.pay-mean-channels.edit', $channel))
            ->post(route('mfw-accounts.pay-mean-channels.edit', $channel), [
                'bank_account_id' => 99999,
                'data' => [
                    'fr' => 'invalid',
                ],
            ]);

        $response->assertRedirect(route('mfw-accounts.pay-mean-channels.edit', $channel));
        $response->assertSessionHasErrors(['bank_account_id', 'data.fr']);
    }

    public function test_destroy_deletes_channel_and_translations(): void
    {
        $this->actingAs($this->createSystemUser());
        $payMean = $this->seedPayMean(['name' => ['fr' => 'Bank']]);
        $channel = PayMeansChannels::query()->create(['pay_mean_id' => $payMean->id]);
        PayMeansChannelsData::query()->create([
            'pay_channel_id' => $channel->id,
            'lg' => 'fr',
            'name' => 'X',
        ]);

        $response = $this->delete(route('mfw-accounts.pay-mean-channels.destroy', $channel));

        $response->assertRedirect(route('mfw-accounts.pay-mean-channels.index', ['payMean' => $payMean->id]));
        $response->assertSessionHas('session_response');
        $this->assertDatabaseMissing('mfw_accounts_pay_means_channels', ['id' => $channel->id]);
        $this->assertDatabaseMissing('mfw_accounts_pay_means_channels_data', ['pay_channel_id' => $channel->id]);
    }
}
