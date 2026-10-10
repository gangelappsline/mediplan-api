<?php

namespace App\Services\Dashboards;

use App\Enums\AppointmentStatus;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Métricas del panel de control de un usuario cliente.
 */
class ClientDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function for(User $user): array
    {
        $now = now();

        /** @var Collection<int, Appointment> $upcoming */
        $upcoming = $user->appointments()
            ->with(['business', 'client'])
            ->where('starts_at', '>=', $now->copy())
            ->whereIn('status', AppointmentStatus::bookedValues())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        $completed = $user->appointments()->where('status', AppointmentStatus::Completed)->count();
        $cancelled = $user->appointments()->where('status', AppointmentStatus::Cancelled)->count();

        /** @var Collection<int, Client> $profiles */
        $profiles = $user->clientProfiles()->with('business')->get();

        return [
            'user' => [
                'id' => $user->getKey(),
                'name' => $user->name,
            ],
            'appointments' => [
                'upcoming_count' => $user->appointments()
                    ->where('starts_at', '>=', $now->copy())
                    ->whereIn('status', AppointmentStatus::bookedValues())
                    ->count(),
                'completed' => $completed,
                'cancelled' => $cancelled,
                'total' => $user->appointments()->count(),
                'next' => $upcoming->first() !== null
                    ? (new AppointmentResource($upcoming->first()))->resolve()
                    : null,
            ],
            'upcoming_appointments' => AppointmentResource::collection($upcoming)->resolve(),
            'businesses' => [
                'registered_in' => $profiles->count(),
                'available' => Business::query()->active()->count(),
            ],
        ];
    }
}
