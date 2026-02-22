<?php

declare(strict_types=1);

namespace MetaFramework\Accounts\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use MetaFramework\Accounts\Tests\TestCase;

class CompanySettingsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_company_edit_page_renders_without_seeded_company(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->get(route('mfw-accounts.company.edit'));

        $response->assertOk();
        $response->assertViewIs('mfw-accounts::company.edit');
        $response->assertSee('name="id" value="1"', false);
    }

    public function test_company_update_creates_company_and_translations_from_component_payload(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->put(route('mfw-accounts.company.update'), [
            'id' => 1,
            'EIN' => 'BE0123456789',
            'VAT' => 'VAT-001',
            'bilan_start' => '0101',
            'bilan_end' => '3112',
            'website' => 'https://example.test',
            'email' => 'company@example.test',
            'phone' => '+3200000000',
            'locales' => [
                'name' => [
                    'fr' => 'Societe FR',
                    'bg' => 'Company BG',
                ],
                'owner' => [
                    'fr' => 'Owner FR',
                    'bg' => 'Owner BG',
                ],
                'adresse' => [
                    'fr' => 'Rue FR',
                    'bg' => 'BG Street',
                ],
                'licence' => [
                    'fr' => 'LIC-FR',
                    'bg' => 'LIC-BG',
                ],
            ],
        ]);

        $response->assertRedirect(route('mfw-accounts.company.edit'));

        $this->assertDatabaseHas('mfw_accounts_company', [
            'id' => 1,
            'EIN' => 'BE0123456789',
            'VAT' => 'VAT-001',
            'bilan_start' => '0101',
            'bilan_end' => '3112',
            'website' => 'https://example.test',
            'email' => 'company@example.test',
            'phone' => '+3200000000',
        ]);

        $this->assertDatabaseHas('mfw_accounts_company_data', [
            'company_id' => 1,
            'lg' => 'fr',
            'name' => 'Societe FR',
            'owner' => 'Owner FR',
            'adresse' => 'Rue FR',
            'licence' => 'LIC-FR',
        ]);

        $this->assertDatabaseHas('mfw_accounts_company_data', [
            'company_id' => 1,
            'lg' => 'bg',
            'name' => 'Company BG',
            'owner' => 'Owner BG',
            'adresse' => 'BG Street',
            'licence' => 'LIC-BG',
        ]);
    }

    public function test_company_update_accepts_legacy_locales_payload_shape(): void
    {
        $this->actingAs($this->createSystemUser());

        $response = $this->put(route('mfw-accounts.company.update'), [
            'id' => 1,
            'locales' => [
                'fr' => [
                    'name' => 'Legacy FR',
                    'owner' => 'Legacy Owner FR',
                    'adresse' => 'Legacy FR Address',
                    'licence' => 'LEG-FR',
                    'ignored_field' => 'should-not-be-saved',
                ],
            ],
        ]);

        $response->assertRedirect(route('mfw-accounts.company.edit'));

        $this->assertDatabaseHas('mfw_accounts_company_data', [
            'company_id' => 1,
            'lg' => 'fr',
            'name' => 'Legacy FR',
            'owner' => 'Legacy Owner FR',
            'adresse' => 'Legacy FR Address',
            'licence' => 'LEG-FR',
        ]);
    }

    private function createSystemUser(): User
    {
        return User::query()->create([
            'type' => 'system',
            'email' => 'mfw-accounts-company-' . Str::uuid() . '@example.com',
            'password' => 'password',
            'first_name' => 'mfw-accounts',
            'last_name' => 'System',
        ]);
    }
}
