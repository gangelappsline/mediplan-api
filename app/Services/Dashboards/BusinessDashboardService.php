<?php

namespace App\Services\Dashboards;

use App\Enums\AppointmentStatus;
use App\Enums\ClientStatus;
use App\Enums\LeadStatus;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\ClientResource;
use App\Http\Resources\LeadResource;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Client;
use App\Models\Lead;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Métricas del panel de control de un negocio.
 */
class BusinessDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function for(Business $business): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $clientsTotal = $business->clients()->count();
        $clientsActive = $business->clients()->where('status', ClientStatus::Active)->count();
        $clientsNewThisMonth = $business->clients()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $leadsTotal = $business->leads()->count();
        $leadsByStatus = $this->countByStatus($business->leads()->select('status')->get()->pluck('status'));
        $leadsOpen = collect(LeadStatus::openValues())->sum(fn (string $status) => $leadsByStatus[$status] ?? 0);
        $leadsWon = $leadsByStatus[LeadStatus::Won->value] ?? 0;
        $leadsLost = $leadsByStatus[LeadStatus::Lost->value] ?? 0;
        $closed = $leadsWon + $leadsLost;

        // Citas con fecha de hoy, sin importar su estado final.
        $appointmentsToday = $business->appointments()
            ->whereBetween('starts_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->count();

        $appointmentsNextWeek = $business->appointments()
            ->whereBetween('starts_at', [$now->copy(), $now->copy()->addWeek()])
            ->whereIn('status', AppointmentStatus::bookedValues())
            ->count();

        $appointmentsCompletedThisMonth = $business->appointments()
            ->whereBetween('starts_at', [$startOfMonth, $endOfMonth])
            ->where('status', AppointmentStatus::Completed)
            ->count();

        $appointmentsCancelledThisMonth = $business->appointments()
            ->whereBetween('starts_at', [$startOfMonth, $endOfMonth])
            ->where('status', AppointmentStatus::Cancelled)
            ->count();

        $revenueThisMonth = (float) $business->appointments()
            ->whereBetween('starts_at', [$startOfMonth, $endOfMonth])
            ->where('status', AppointmentStatus::Completed)
            ->sum('price');

        /** @var Collection<int, Appointment> $upcoming */
        $upcoming = $business->appointments()
            ->with('client')
            ->where('starts_at', '>=', $now->copy())
            ->whereIn('status', AppointmentStatus::bookedValues())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        /** @var Collection<int, Lead> $recentLeads */
        $recentLeads = $business->leads()
            ->with('assignedTo')
            ->latest()
            ->limit(5)
            ->get();

        /** @var Collection<int, Client> $recentClients */
        $recentClients = $business->clients()
            ->latest()
            ->limit(5)
            ->get();

        return [
            'business' => [
                'id' => $business->getKey(),
                'name' => $business->name,
                'status' => [
                    'name' => $business->status?->value,
                    'label' => $business->status?->label(),
                ],
            ],
            'clients' => [
                'total' => $clientsTotal,
                'active' => $clientsActive,
                'new_this_month' => $clientsNewThisMonth,
            ],
            'leads' => [
                'total' => $leadsTotal,
                'open' => $leadsOpen,
                'by_status' => $leadsByStatus,
                'conversion_rate' => $closed > 0 ? round($leadsWon / $closed * 100, 1) : 0.0,
            ],
            'appointments' => [
                'today' => $appointmentsToday,
                'next_week' => $appointmentsNextWeek,
                'completed_this_month' => $appointmentsCompletedThisMonth,
                'cancelled_this_month' => $appointmentsCancelledThisMonth,
                'revenue_this_month' => round($revenueThisMonth, 2),
                'monthly_activity' => $this->monthlyActivity($business, $now),
            ],
            'next_appointments' => AppointmentResource::collection($upcoming)->resolve(),
            'recent_leads' => LeadResource::collection($recentLeads)->resolve(),
            'recent_clients' => ClientResource::collection($recentClients)->resolve(),
        ];
    }

    /**
     * Actividad de citas de los últimos seis meses, agrupada en PHP para no
     * depender de funciones de fecha específicas de cada motor de base de datos.
     *
     * @return array<int, array<string, mixed>>
     */
    private function monthlyActivity(Business $business, Carbon $now): array
    {
        $from = $now->copy()->startOfMonth()->subMonths(5);

        /** @var Collection<int, Appointment> $appointments */
        $appointments = $business->appointments()
            ->where('starts_at', '>=', $from)
            ->get(['id', 'starts_at', 'status']);

        $months = [];

        for ($offset = 5; $offset >= 0; $offset--) {
            $month = $now->copy()->startOfMonth()->subMonths($offset);
            $months[$month->format('Y-m')] = [
                'month' => $month->format('Y-m'),
                'label' => $month->translatedFormat('F Y'),
                'total' => 0,
                'completed' => 0,
                'cancelled' => 0,
            ];
        }

        foreach ($appointments as $appointment) {
            $key = $appointment->starts_at?->format('Y-m');

            if ($key === null || ! isset($months[$key])) {
                continue;
            }

            $months[$key]['total']++;

            if ($appointment->status === AppointmentStatus::Completed) {
                $months[$key]['completed']++;
            }

            if ($appointment->status === AppointmentStatus::Cancelled) {
                $months[$key]['cancelled']++;
            }
        }

        return array_values($months);
    }

    /**
     * @param  Collection<int, string|LeadStatus>  $statuses
     * @return array<string, int>
     */
    private function countByStatus(Collection $statuses): array
    {
        $counts = array_fill_keys(LeadStatus::values(), 0);

        foreach ($statuses as $status) {
            $value = $status instanceof LeadStatus ? $status->value : (string) $status;

            if (array_key_exists($value, $counts)) {
                $counts[$value]++;
            }
        }

        return $counts;
    }
}
