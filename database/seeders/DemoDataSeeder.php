<?php

namespace Database\Seeders;

use App\Enums\AppointmentStatus;
use App\Enums\LeadStatus;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Datos de demostración para probar los paneles sin capturar información a mano.
 *
 * Ejecutar con: php artisan db:seed --class=DemoDataSeeder
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->where('email', 'negocio@mediplan.com')->first();

        if ($owner === null) {
            $this->command?->warn('Ejecuta primero AdminUserSeeder para crear negocio@mediplan.com.');

            return;
        }

        /** @var Business $business */
        $business = $owner->business ?? Business::query()->create([
            'user_id' => $owner->getKey(),
            'name' => $owner->name,
            'email' => $owner->email,
            'status' => 'active',
        ]);

        $business->setting()->firstOrCreate(
            ['business_id' => $business->getKey()],
            BusinessSetting::defaults(),
        );

        if ($business->clients()->exists()) {
            $this->command?->info('El negocio de demostración ya tiene clientes; no se generaron datos nuevos.');

            return;
        }

        $clients = $this->seedClients($business);
        $this->seedLeads($business);
        $this->seedAppointments($business, $clients);

        $this->command?->info(sprintf(
            'Datos de demostración creados: %d clientes, %d leads, %d citas para el negocio #%d.',
            $clients->count(),
            $business->leads()->count(),
            $business->appointments()->count(),
            $business->getKey(),
        ));
    }

    /**
     * @return Collection<int, Client>
     */
    private function seedClients(Business $business)
    {
        $names = [
            'María López', 'Juan Hernández', 'Laura Martínez', 'Carlos Sánchez',
            'Ana Ramírez', 'Pedro Flores', 'Sofía Castro', 'Luis Torres',
            'Valeria Ortiz', 'Diego Mendoza', 'Fernanda Ruiz', 'Ricardo Vega',
        ];

        $clients = collect();

        foreach ($names as $index => $name) {
            $clients->push(Client::factory()->create([
                'business_id' => $business->getKey(),
                'name' => $name,
                'email' => 'cliente'.($index + 1).'@ejemplo.com',
                'last_appointment_at' => now()->subDays($index + 1),
            ]));
        }

        return $clients;
    }

    private function seedLeads(Business $business): void
    {
        $sources = ['facebook', 'instagram', 'referencia', 'sitio_web', 'whatsapp'];
        $statuses = [
            LeadStatus::New, LeadStatus::New, LeadStatus::Contacted, LeadStatus::Contacted,
            LeadStatus::Qualified, LeadStatus::Proposal, LeadStatus::Won, LeadStatus::Lost,
        ];

        foreach ($statuses as $index => $status) {
            /** @var Lead $lead */
            $lead = Lead::factory()->status($status)->create([
                'business_id' => $business->getKey(),
                'source' => $sources[$index % count($sources)],
                'follow_up_at' => now()->addDays($index + 1)->setTime(10, 0),
                'assigned_to_user_id' => $business->user_id,
            ]);

            if ($status === LeadStatus::Won) {
                $client = Client::factory()->create([
                    'business_id' => $business->getKey(),
                    'name' => $lead->name,
                    'email' => $lead->email,
                    'phone' => $lead->phone,
                ]);

                $lead->forceFill([
                    'converted_client_id' => $client->getKey(),
                    'converted_at' => now(),
                ])->save();
            }
        }
    }

    /**
     * @param  Collection<int, Client>  $clients
     */
    private function seedAppointments(Business $business, $clients): void
    {
        $client = $clients->first();

        if ($client === null) {
            return;
        }

        // Citas del día en curso
        foreach ([9, 11, 13, 16] as $hour) {
            Appointment::factory()->create([
                'business_id' => $business->getKey(),
                'client_id' => $client->getKey(),
                'starts_at' => now()->setTime($hour, 0),
                'ends_at' => now()->setTime($hour, 30),
                'status' => AppointmentStatus::Confirmed,
            ]);
        }

        // Histórico de los últimos tres meses
        foreach (range(1, 30) as $day) {
            $startsAt = now()->subDays($day * 3)->setTime(10, 0);

            Appointment::factory()->create([
                'business_id' => $business->getKey(),
                'client_id' => $clients->random()->getKey(),
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes(30),
                'status' => $day % 7 === 0 ? AppointmentStatus::Cancelled : AppointmentStatus::Completed,
                'cancelled_at' => $day % 7 === 0 ? $startsAt->copy()->subDay() : null,
            ]);
        }

        // Próximas citas
        foreach (range(1, 12) as $day) {
            $startsAt = now()->addDays($day)->setTime(12, 0);

            Appointment::factory()->create([
                'business_id' => $business->getKey(),
                'client_id' => $clients->random()->getKey(),
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMinutes(30),
                'status' => $day % 2 === 0 ? AppointmentStatus::Confirmed : AppointmentStatus::Scheduled,
            ]);
        }
    }
}
