<?php

namespace Tests\Feature\Business;

use App\Enums\AppointmentStatus;
use App\Enums\LeadStatus;
use App\Enums\RoleName;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\CreatesApiUsers;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use CreatesApiUsers;
    use RefreshDatabase;

    public function test_business_dashboard_returns_the_expected_indicators(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        Client::factory()->count(3)->create(['business_id' => $business->getKey()]);
        Client::factory()->inactive()->create(['business_id' => $business->getKey()]);

        Lead::factory()->status(LeadStatus::New)->create(['business_id' => $business->getKey()]);
        Lead::factory()->status(LeadStatus::Won)->create(['business_id' => $business->getKey()]);
        Lead::factory()->status(LeadStatus::Lost)->create(['business_id' => $business->getKey()]);

        $todayClient = Client::factory()->create(['business_id' => $business->getKey()]);

        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $todayClient->getKey(),
            'starts_at' => now()->copy()->startOfDay()->setTime(9, 0),
            'ends_at' => now()->copy()->startOfDay()->setTime(9, 30),
            'status' => AppointmentStatus::Completed,
            'price' => 1000,
        ]);

        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $todayClient->getKey(),
            'starts_at' => now()->copy()->startOfDay()->setTime(11, 0),
            'ends_at' => now()->copy()->startOfDay()->setTime(11, 30),
            'status' => AppointmentStatus::Confirmed,
            'price' => 500,
        ]);

        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $todayClient->getKey(),
            'starts_at' => now()->copy()->addDay()->setTime(10, 0),
            'ends_at' => now()->copy()->addDay()->setTime(10, 30),
            'status' => AppointmentStatus::Scheduled,
        ]);

        Passport::actingAs($owner);

        $response = $this->getJson('/api/business/dashboard');

        $response->assertOk()
            ->assertJsonPath('message', 'Panel del negocio generado correctamente.')
            ->assertJsonPath('data.business.id', $business->getKey())
            ->assertJsonPath('data.business.name', $business->name)
            ->assertJsonPath('data.clients.total', 5)
            ->assertJsonPath('data.clients.active', 4)
            ->assertJsonPath('data.leads.total', 3)
            ->assertJsonPath('data.leads.open', 1)
            ->assertJsonPath('data.leads.conversion_rate', 50)
            ->assertJsonPath('data.leads.by_status.won', 1)
            ->assertJsonPath('data.appointments.today', 2)
            ->assertJsonPath('data.appointments.next_week', 1)
            ->assertJsonPath('data.appointments.completed_this_month', 1)
            ->assertJsonPath('data.appointments.revenue_this_month', 1000)
            ->assertJsonStructure([
                'data' => [
                    'business' => ['id', 'name', 'status' => ['name', 'label']],
                    'clients' => ['total', 'active', 'new_this_month'],
                    'leads' => ['total', 'open', 'by_status', 'conversion_rate'],
                    'appointments' => [
                        'today',
                        'next_week',
                        'completed_this_month',
                        'cancelled_this_month',
                        'revenue_this_month',
                        'monthly_activity',
                    ],
                    'next_appointments',
                    'recent_leads',
                    'recent_clients',
                ],
            ]);

        $this->assertCount(6, $response->json('data.appointments.monthly_activity'));
        $this->assertCount(1, $response->json('data.next_appointments'));
    }

    public function test_business_dashboard_reports_six_months_of_activity(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        $client = Client::factory()->create(['business_id' => $business->getKey()]);

        Appointment::factory()->create([
            'business_id' => $business->getKey(),
            'client_id' => $client->getKey(),
            'starts_at' => now()->copy()->startOfMonth()->subMonths(2)->setTime(9, 0),
            'ends_at' => now()->copy()->startOfMonth()->subMonths(2)->setTime(9, 30),
            'status' => AppointmentStatus::Completed,
        ]);

        Passport::actingAs($owner);

        $response = $this->getJson('/api/business/dashboard')->assertOk();

        $months = collect($response->json('data.appointments.monthly_activity'));

        $this->assertSame(6, $months->count());
        $this->assertSame(
            now()->copy()->startOfMonth()->subMonths(2)->format('Y-m'),
            $months->firstWhere('total', 1)['month'] ?? null,
        );
        $this->assertSame(
            0,
            $months->firstWhere('month', now()->format('Y-m'))['total'],
        );
    }

    public function test_business_dashboard_is_blocked_for_other_roles(): void
    {
        $clientUser = $this->makeUser([], RoleName::Client);

        Passport::actingAs($clientUser);

        $this->getJson('/api/business/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'No tienes permiso para realizar esta acción.');
    }

    public function test_business_dashboard_requires_authentication(): void
    {
        $this->getJson('/api/business/dashboard')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'No autenticado.');
    }

    public function test_clients_are_not_shared_between_businesses(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        [, $otherBusiness] = $this->makeBusinessOwner();

        Client::factory()->count(2)->create(['business_id' => $business->getKey()]);
        Client::factory()->create(['business_id' => $otherBusiness->getKey()]);

        Passport::actingAs($owner);

        $this->getJson('/api/business/dashboard')
            ->assertOk()
            ->assertJsonPath('data.clients.total', 2);
    }
}
