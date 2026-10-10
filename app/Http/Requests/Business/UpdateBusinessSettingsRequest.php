<?php

namespace App\Http\Requests\Business;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBusinessSettingsRequest extends FormRequest
{
    /**
     * Días de la semana aceptados en working_hours.
     *
     * @var list<string>
     */
    public const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'timezone' => ['sometimes', 'required', 'string', 'max:60', 'timezone:all'],
            'appointment_duration_minutes' => ['sometimes', 'required', 'integer', 'min:5', 'max:480'],
            'slot_interval_minutes' => ['sometimes', 'required', 'integer', 'min:5', 'max:240'],
            'min_notice_minutes' => ['sometimes', 'required', 'integer', 'min:0', 'max:10080'],
            'max_advance_days' => ['sometimes', 'required', 'integer', 'min:1', 'max:365'],
            'auto_confirm_appointments' => ['sometimes', 'boolean'],
            'allow_online_booking' => ['sometimes', 'boolean'],
            'currency' => ['sometimes', 'required', 'string', 'size:3'],
            'working_hours' => ['sometimes', 'array'],
            'working_hours.*' => ['array'],
            'working_hours.*.open' => ['required_with:working_hours.*', 'date_format:H:i'],
            'working_hours.*.close' => ['required_with:working_hours.*', 'date_format:H:i'],
            'working_hours.*.closed' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Rechaza claves de working_hours que no sean días de la semana.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $workingHours = $this->input('working_hours');

            if (! is_array($workingHours)) {
                return;
            }

            foreach (array_keys($workingHours) as $day) {
                if (! in_array($day, self::DAYS, true)) {
                    $validator->errors()->add(
                        'working_hours',
                        'El día "'.(string) $day.'" no es válido. Usa: '.implode(', ', self::DAYS).'.',
                    );
                }
            }

            foreach ($workingHours as $day => $schedule) {
                if (! is_array($schedule)) {
                    continue;
                }

                $open = $schedule['open'] ?? null;
                $close = $schedule['close'] ?? null;
                $closed = (bool) ($schedule['closed'] ?? false);

                if (! $closed
                    && is_string($open)
                    && is_string($close)
                    && strtotime($close) <= strtotime($open)) {
                    $validator->errors()->add(
                        'working_hours',
                        'La hora de cierre debe ser posterior a la hora de apertura en "'.$day.'".',
                    );
                }
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'timezone.required' => 'La zona horaria es obligatoria.',
            'timezone.timezone' => 'La zona horaria no es válida.',

            'appointment_duration_minutes.integer' => 'La duración de la cita debe ser un número entero.',
            'appointment_duration_minutes.min' => 'La duración mínima de una cita es de :min minutos.',
            'appointment_duration_minutes.max' => 'La duración máxima de una cita es de :max minutos.',

            'slot_interval_minutes.integer' => 'El intervalo entre espacios debe ser un número entero.',
            'slot_interval_minutes.min' => 'El intervalo mínimo entre espacios es de :min minutos.',
            'slot_interval_minutes.max' => 'El intervalo máximo entre espacios es de :max minutos.',

            'min_notice_minutes.integer' => 'La anticipación mínima debe ser un número entero.',
            'min_notice_minutes.min' => 'La anticipación mínima no puede ser negativa.',
            'min_notice_minutes.max' => 'La anticipación mínima no puede ser mayor a :max minutos.',

            'max_advance_days.integer' => 'Los días de anticipación deben ser un número entero.',
            'max_advance_days.min' => 'Debes permitir al menos :min día de anticipación.',
            'max_advance_days.max' => 'La anticipación máxima no puede superar los :max días.',

            'currency.size' => 'La moneda debe tener exactamente 3 caracteres (por ejemplo MXN).',

            'working_hours.*.open.required_with' => 'La hora de apertura es obligatoria.',
            'working_hours.*.open.date_format' => 'La hora de apertura debe tener el formato HH:MM.',
            'working_hours.*.close.required_with' => 'La hora de cierre es obligatoria.',
            'working_hours.*.close.date_format' => 'La hora de cierre debe tener el formato HH:MM.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'timezone' => 'zona horaria',
            'appointment_duration_minutes' => 'duración de la cita',
            'slot_interval_minutes' => 'intervalo entre espacios',
            'min_notice_minutes' => 'anticipación mínima',
            'max_advance_days' => 'días máximos de anticipación',
            'currency' => 'moneda',
            'working_hours' => 'horarios de atención',
        ];
    }
}
