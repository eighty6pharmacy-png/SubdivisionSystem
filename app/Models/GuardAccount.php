<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardAccount extends Model
{
    protected $table = 'guards';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'schedule_days',
        'shift_type',
        'shift_start',
        'shift_end',
    ];

    protected $casts = [
        'schedule_days' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Resolves the shift configuration for a specific day of the week.
     * Supports both per-day shift maps ('Monday' => 'day', 'Tuesday' => 'night')
     * and uniform schedule lists (['Monday', 'Tuesday'] with global shift_type).
     *
     * @return array{shift: string, start: string, end: string}|null
     */
    public function getShiftForDay(string $dayName): ?array
    {
        $raw = $this->schedule_days;
        if (!is_array($raw) || empty($raw)) {
            return null;
        }

        // Check if schedule_days is an associative map (e.g. ['Monday' => 'day', 'Tuesday' => 'night'])
        $isAssoc = array_keys($raw) !== range(0, count($raw) - 1);
        if ($isAssoc) {
            if (!isset($raw[$dayName])) {
                return null;
            }
            $config = $raw[$dayName];
            if ($config === 'off' || empty($config)) {
                return null;
            }
            if (is_string($config)) {
                if ($config === 'day') {
                    return ['shift' => 'day', 'start' => '06:00:00', 'end' => '18:00:00'];
                }
                if ($config === 'night') {
                    return ['shift' => 'night', 'start' => '18:00:00', 'end' => '06:00:00'];
                }
                if ($config === '24_hours') {
                    return ['shift' => '24_hours', 'start' => '00:00:00', 'end' => '23:59:00'];
                }
            }
            if (is_array($config)) {
                if (!empty($config['off']) || ($config['shift'] ?? '') === 'off') {
                    return null;
                }
                $shift = $config['shift'] ?? 'day';
                $defaultStart = $shift === 'night' ? '18:00:00' : ($shift === '24_hours' ? '00:00:00' : '06:00:00');
                $defaultEnd = $shift === 'night' ? '06:00:00' : ($shift === '24_hours' ? '23:59:00' : '18:00:00');
                return [
                    'shift' => $shift,
                    'start' => !empty($config['start']) ? date('H:i:s', strtotime($config['start'])) : $defaultStart,
                    'end' => !empty($config['end']) ? date('H:i:s', strtotime($config['end'])) : $defaultEnd,
                ];
            }
            return null;
        }

        // Uniform list of days (e.g. ['Monday', 'Wednesday'])
        if (in_array($dayName, $raw, true)) {
            $shift = $this->shift_type ?? 'day';
            $start = $this->shift_start ? date('H:i:s', strtotime($this->shift_start)) : ($shift === 'night' ? '18:00:00' : '06:00:00');
            $end = $this->shift_end ? date('H:i:s', strtotime($this->shift_end)) : ($shift === 'night' ? '06:00:00' : '18:00:00');
            return ['shift' => $shift, 'start' => $start, 'end' => $end];
        }

        return null;
    }

    /**
     * Resolves the currently active shift configuration if on duty, or null if off duty.
     *
     * @return array{shift: string, start: string, end: string, is_rollover: bool}|null
     */
    public function getActiveDutyShift(?\Carbon\Carbon $time = null): ?array
    {
        $time = $time ? $time->copy()->setTimezone('Asia/Manila') : now('Asia/Manila');
        $currentDay = $time->format('l'); // e.g. "Wednesday"
        $currentTime = $time->format('H:i:s');

        // 1. Check if today's scheduled shift is active
        $todayShift = $this->getShiftForDay($currentDay);
        if ($todayShift) {
            $start = $todayShift['start'];
            $end = $todayShift['end'];
            $shiftType = $todayShift['shift'];

            // 24-Hour Duty
            if ($shiftType === '24_hours' || ($start === '00:00:00' && $end === '23:59:00') || ($start === $end)) {
                return array_merge($todayShift, ['is_rollover' => false]);
            }

            // Standard daytime shift (e.g. 06:00 to 18:00)
            if ($start < $end && $currentTime >= $start && $currentTime < $end) {
                return array_merge($todayShift, ['is_rollover' => false]);
            }

            // Overnight shift evening portion (e.g. 18:00 to 06:00)
            if ($start > $end && $currentTime >= $start) {
                return array_merge($todayShift, ['is_rollover' => false]);
            }
        }

        // 2. Check if yesterday had an overnight shift that is still active this morning (< end)
        $yesterday = $time->copy()->subDay()->format('l');
        $yesterdayShift = $this->getShiftForDay($yesterday);
        if ($yesterdayShift) {
            $start = $yesterdayShift['start'];
            $end = $yesterdayShift['end'];
            if ($start > $end && $currentTime < $end) {
                return array_merge($yesterdayShift, ['is_rollover' => true]);
            }
        }

        return null;
    }

    /**
     * Checks if this guard is currently on active scheduled duty.
     * Supports per-day alternating shifts (day/night depending on the day),
     * standard daytime shifts, and overnight cross-midnight shifts.
     */
    public function isOnDuty(?\Carbon\Carbon $time = null): bool
    {
        return $this->getActiveDutyShift($time) !== null;
    }

    /**
     * Returns a human-friendly string for the guard's schedule.
     */
    public function getFormattedScheduleAttribute(): string
    {
        $raw = $this->schedule_days;
        if (!is_array($raw) || empty($raw)) {
            return 'No Schedule Set';
        }

        $order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $groups = [];

        foreach ($order as $day) {
            $shiftInfo = $this->getShiftForDay($day);
            if (!$shiftInfo) {
                continue;
            }
            $shift = $shiftInfo['shift'] ?? 'day';
            $start = !empty($shiftInfo['start']) ? date('h:i A', strtotime($shiftInfo['start'])) : '06:00 AM';
            $end = !empty($shiftInfo['end']) ? date('h:i A', strtotime($shiftInfo['end'])) : '06:00 PM';
            $key = "{$shift}|{$start}|{$end}";

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'shift' => $shift,
                    'start' => $start,
                    'end' => $end,
                    'days' => [],
                ];
            }
            $groups[$key]['days'][] = substr($day, 0, 3);
        }

        if (empty($groups)) {
            return 'Off Duty (All Week)';
        }

        if (count($groups) === 1) {
            $group = reset($groups);
            $daysStr = implode(', ', $group['days']);
            $shiftLabel = $group['shift'] === '24_hours' ? '24 Hours' : ucfirst($group['shift']) . ' Shift';
            return "{$daysStr} • {$shiftLabel} ({$group['start']} - {$group['end']})";
        }

        $parts = [];
        foreach ($groups as $group) {
            $daysStr = implode(', ', $group['days']);
            $shiftLabel = $group['shift'] === '24_hours' ? '24h' : ucfirst($group['shift']);
            $parts[] = "{$daysStr}: {$shiftLabel} ({$group['start']} - {$group['end']})";
        }

        return implode(' • ', $parts);
    }
}
