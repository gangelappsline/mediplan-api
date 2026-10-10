<?php

namespace Tests\Feature\Admin;

use App\Enums\BusinessStatus;
use App\Enums\LeadStatus;
use App\Enums\RoleName;
use App\Models\Business;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\CreatesApiUsers;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use CreatesApiUsers;
    use RefreshDatabase;

    public function test_admin_dashboard_returns_platform_wide_metrics(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);

        $this->makeUser([], RoleName::Client);
        $this->makeUser([], RoleName::Client);
        [$businessOwner, $business] = $this->makeBusinessOwner();

        Business::factory()->pending()->create();

        Client::factory()->count(3)->create(['business_id' => $business->getKey()]);

        Lead::factory()->status(LeadStatus::New)->create(['business_id' => $business->getKey()]);
        Lead::factory()->status(LeadStatus::Won)->create(['business_id' => $business->getKey()]);

        Passport::actingAs($admin);

        $this->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('message', 'Panel de administración generado correctamente.')
            ->assertJsonPath('data.users.total', 5)
            ->assertJsonPath('data.users.inactive', 0)
            ->assertJsonPath('data.businesses.total', 2)
            ->assertJsonPath('data.clients.total', 3)
            ->assertJsonPath('data.leads.total', 2)
            ->assertJsonPath('data.leads.open', 1)
            ->assertJsonPath('data.leads.won', 1)
            ->assertJsonPath('data.leads.conversion_rate', 100)
            ->assertJsonStructure([
                'data' => [
                    'users' => ['total', 'inactive', 'new_last_month', 'by_role' => [['name', 'label', 'total']]],
                    'businesses' => ['total', 'new_last_month', 'by_status' => [['name', 'label', 'total']]],
                    'clients' => ['total', 'new_last_month'],
                    'leads' => ['total', 'open', 'won', 'conversion_rate'],
                    'appointments' => ['total', 'today', 'upcoming', 'completed'],
                    'recent_users',
                    'recent_businesses',
                ],
            ]);
    }

    public function test_admin_dashboard_counts_users_by_role(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);

        $this->makeUser([], RoleName::Client);
        $this->makeUser([], RoleName::Business);

        Passport::actingAs($admin);

        $response = $this->getJson('/api/admin/dashboard')->assertOk();

        $byRole = collect($response->json('data.users.by_role'))->keyBy('name');

        $this->assertSame(1, $byRole['client']['total']);
        $this->assertSame(1, $byRole['business']['total']);
        $this->assertSame(1, $byRole['admin']['total']);
        $this->assertSame('Cliente', $byRole['client']['label']);
    }

    public function test_admin_dashboard_reports_business_status_breakdown(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);

        Business::factory()->count(2)->create(['status' => BusinessStatus::Active]);
        Business::factory()->create(['status' => BusinessStatus::Suspended]);

        Passport::actingAs($admin);

        $response = $this->getJson('/api/admin/dashboard')->assertOk();

        $byStatus = collect($response->json('data.businesses.by_status'))->keyBy('name');

        $this->assertSame(2, $byStatus['active']['total']);
        $this->assertSame(1, $byStatus['suspended']['total']);
        $this->assertSame(0, $byStatus['pending']['total']);
    }

    public function test_admin_dashboard_requires_the_admin_role(): void
    {
        [$businessOwner] = $this->makeBusinessOwner();

        Passport::actingAs($businessOwner);

        $this->getJson('/api/admin/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'No tienes permiso para realizar esta acción.');
    }

    public function test_admin_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/admin/dashboard')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado.');
    }
}
