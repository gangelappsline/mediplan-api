<?php

namespace App\Services\Dashboards;

use App\Enums\AppointmentStatus;
use App\Enums\BusinessStatus;
use App\Enums\LeadStatus;
use App\Enums\RoleName;
use App\Http\Resources\BusinessResource;
use App\Http\Resources\UserResource;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Métricas globales del panel de administración de la plataforma.
 */
class AdminDashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $now = now();

        $usersTotal = User::query()->count();
        $usersInactive = User::query()->where('is_active', false)->count();
        $usersNewLastMonth = User::query()
            ->where('created_at', '>=', $now->copy()->subMonth())
            ->count();

        $roleIds = Role::query()->pluck('id', 'name');

        $usersByRole = [];
        foreach (RoleName::cases() as $role) {
            $roleId = $roleIds[$role->value] ?? null;

            $usersByRole[] = [
                'name' => $role->value,
                'label' => $role->label(),
                'total' => $roleId === null
                    ? 0
                    : User::query()->whereHas('roles', fn ($query) => $query->where('roles.id', $roleId))->count(),
            ];
        }

        $businessesByStatus = [];
        foreach (BusinessStatus::cases() as $status) {
            $businessesByStatus[] = [
                'name' => $status->value,
                'label' => $status->label(),
                'total' => Business::query()->where('status', $status)->count(),
            ];
        }

        $leadsOpen = Lead::query()->whereIn('status', LeadStatus::openValues())->count();
        $leadsWon = Lead::query()->where('status', LeadStatus::Won)->count();

        /** @var Collection<int, User> $recentUsers */
        $recentUsers = User::query()->with('roles')->latest()->limit(5)->get();

        /** @var Collection<int, Business> $recentBusinesses */
        $recentBusinesses = Business::query()->with('user')->latest()->limit(5)->get();

        return [
            'users' => [
                'total' => $usersTotal,
                'inactive' => $usersInactive,
                'new_last_month' => $usersNewLastMonth,
                'by_role' => $usersByRole,
            ],
            'businesses' => [
                'total' => Business::query()->count(),
                'by_status' => $businessesByStatus,
                'new_last_month' => Business::query()
                    ->where('created_at', '>=', $now->copy()->subMonth())
                    ->count(),
            ],
            'clients' => [
                'total' => Client::query()->count(),
                'new_last_month' => Client::query()
                    ->where('created_at', '>=', $now->copy()->subMonth())
                    ->count(),
            ],
            'leads' => [
                'total' => Lead::query()->count(),
                'open' => $leadsOpen,
                'won' => $leadsWon,
                'conversion_rate' => $this->conversionRate($leadsWon),
            ],
            'appointments' => [
                'total' => Appointment::query()->count(),
                'today' => Appointment::query()
                    ->whereBetween('starts_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
                    ->whereIn('status', AppointmentStatus::bookedValues())
                    ->count(),
                'upcoming' => Appointment::query()
                    ->where('starts_at', '>=', $now->copy())
                    ->whereIn('status', AppointmentStatus::bookedValues())
                    ->count(),
                'completed' => Appointment::query()->where('status', AppointmentStatus::Completed)->count(),
            ],
            'recent_users' => UserResource::collection($recentUsers)->resolve(),
            'recent_businesses' => BusinessResource::collection($recentBusinesses)->resolve(),
        ];
    }

    private function conversionRate(int $won): float
    {
        $closed = $won + Lead::query()->where('status', LeadStatus::Lost)->count();

        return $closed > 0 ? round($won / $closed * 100, 1) : 0.0;
    }
}
