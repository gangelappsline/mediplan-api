<?php

namespace Tests\Feature\Business;

use App\Enums\ClientStatus;
use App\Enums\LeadStatus;
use App\Enums\RoleName;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\Concerns\CreatesApiUsers;
use Tests\TestCase;

class LeadPipelineTest extends TestCase
{
    use CreatesApiUsers;
    use RefreshDatabase;

    public function test_business_can_list_and_filter_its_leads(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        [, $otherBusiness] = $this->makeBusinessOwner();

        Lead::factory()->status(LeadStatus::New)->count(2)->create(['business_id' => $business->getKey()]);
        Lead::factory()->status(LeadStatus::Won)->create(['business_id' => $business->getKey()]);
        Lead::factory()->create(['business_id' => $otherBusiness->getKey()]);

        Passport::actingAs($owner);

        $this->getJson('/api/business/leads')
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonStructure(['data' => [['id', 'name', 'status' => ['name', 'label', 'is_open']]]]);

        $this->getJson('/api/business/leads?status=won')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status.name', LeadStatus::Won->value);

        $this->getJson('/api/business/leads?open=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_business_can_create_a_lead(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        Passport::actingAs($owner);

        $this->postJson('/api/business/leads', [
            'name' => 'Carlos Ramírez',
            'email' => 'carlos@example.com',
            'source' => 'facebook',
            'estimated_value' => 4500,
            'follow_up_at' => '2026-11-01T10:00:00.000000Z',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Lead creado correctamente.')
            ->assertJsonPath('data.business_id', $business->getKey())
            ->assertJsonPath('data.status.name', LeadStatus::New->value)
            ->assertJsonPath('data.status.label', LeadStatus::New->label())
            ->assertJsonPath('data.status.is_open', true)
            ->assertJsonPath('data.estimated_value', 4500);

        $this->assertDatabaseHas('leads', ['email' => 'carlos@example.com']);
    }

    public function test_a_lead_requires_at_least_one_contact_channel(): void
    {
        [$owner] = $this->makeBusinessOwner();

        Passport::actingAs($owner);

        $this->postJson('/api/business/leads', ['name' => 'Sin contacto'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Debes proporcionar al menos un correo electrónico o un teléfono.');
    }

    public function test_moving_a_lead_records_the_contact_date(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $lead = Lead::factory()->status(LeadStatus::New)->create(['business_id' => $business->getKey()]);

        $this->assertNull($lead->contacted_at);

        Passport::actingAs($owner);

        $this->patchJson("/api/business/leads/{$lead->getKey()}/status", [
            'status' => LeadStatus::Contacted->value,
            'notes' => 'Se envió cotización.',
        ])
            ->assertOk()
            ->assertJsonPath('data.status.name', LeadStatus::Contacted->value)
            ->assertJsonPath('data.notes', 'Se envió cotización.');

        $this->assertNotNull($lead->refresh()->contacted_at);
    }

    public function test_lead_status_must_be_a_known_value(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $lead = Lead::factory()->create(['business_id' => $business->getKey()]);

        Passport::actingAs($owner);

        $this->patchJson("/api/business/leads/{$lead->getKey()}/status", ['status' => 'inventado'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    }

    public function test_converting_a_lead_creates_a_client_and_marks_it_as_won(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        $lead = Lead::factory()->status(LeadStatus::Qualified)->create([
            'business_id' => $business->getKey(),
            'name' => 'Prospecto Ganador',
            'email' => 'ganador@example.com',
            'phone' => '5500000001',
        ]);

        Passport::actingAs($owner);

        $response = $this->postJson("/api/business/leads/{$lead->getKey()}/convert")
            ->assertCreated()
            ->assertJsonPath('message', 'Lead convertido en cliente correctamente.')
            ->assertJsonPath('data.name', 'Prospecto Ganador')
            ->assertJsonPath('data.business_id', $business->getKey())
            ->assertJsonPath('data.status.name', ClientStatus::Active->value);

        $lead->refresh();

        $this->assertSame(LeadStatus::Won, $lead->status);
        $this->assertNotNull($lead->converted_at);
        $this->assertSame($response->json('data.id'), $lead->converted_client_id);
        $this->assertDatabaseHas('clients', ['email' => 'ganador@example.com']);
    }

    public function test_a_lead_cannot_be_converted_twice(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();

        $lead = Lead::factory()->create(['business_id' => $business->getKey()]);
        $client = Client::factory()->create(['business_id' => $business->getKey()]);

        $lead->forceFill([
            'converted_client_id' => $client->getKey(),
            'converted_at' => now(),
            'status' => LeadStatus::Won,
        ])->save();

        Passport::actingAs($owner);

        $this->postJson("/api/business/leads/{$lead->getKey()}/convert")
            ->assertStatus(422)
            ->assertJsonPath('errors.lead.0', 'Este lead ya fue convertido en cliente.');
    }

    public function test_conversion_accepts_override_data(): void
    {
        [$owner, $business] = $this->makeBusinessOwner();
        $lead = Lead::factory()->create([
            'business_id' => $business->getKey(),
            'name' => 'Nombre original',
            'email' => 'original@example.com',
        ]);

        Passport::actingAs($owner);

        $this->postJson("/api/business/leads/{$lead->getKey()}/convert", [
            'name' => 'Nombre corregido',
            'phone' => '5599998888',
        ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Nombre corregido')
            ->assertJsonPath('data.email', 'original@example.com')
            ->assertJsonPath('data.phone', '5599998888');
    }

    public function test_leads_are_isolated_between_businesses(): void
    {
        [$owner] = $this->makeBusinessOwner();
        [, $otherBusiness] = $this->makeBusinessOwner();
        $foreignLead = Lead::factory()->create(['business_id' => $otherBusiness->getKey()]);

        Passport::actingAs($owner);

        $this->getJson("/api/business/leads/{$foreignLead->getKey()}")->assertNotFound();
        $this->deleteJson("/api/business/leads/{$foreignLead->getKey()}")->assertNotFound();
        $this->postJson("/api/business/leads/{$foreignLead->getKey()}/convert")->assertNotFound();
        $this->assertDatabaseHas('leads', ['id' => $foreignLead->getKey()]);
    }

    public function test_lead_endpoints_require_the_business_role(): void
    {
        $admin = $this->makeUser([], RoleName::Admin);

        Passport::actingAs($admin);

        $this->getJson('/api/business/leads')->assertForbidden();
    }
}
