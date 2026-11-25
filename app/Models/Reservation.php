<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reservation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'lot_id',
        'buyer_id',
        'buyer_master_list_id',
        'status',
        'reservation_date',
        'deadline_date',
        'expiry_reminder_5d_sent_at',
        'expired_reminder_sent_at',
        'amount',
        'notes',
        'receipt_data',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
            'deadline_date' => 'date',
            'expiry_reminder_5d_sent_at' => 'datetime',
            'expired_reminder_sent_at' => 'datetime',
            'receipt_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class);
    }

    public function downpayments(): HasMany
    {
        return $this->hasMany(Downpayment::class);
    }

    public function setBuyerMasterListIdAttribute($value): void
    {
        $this->attributes['buyer_id'] = $value;
        unset($this->attributes['buyer_master_list_id']);
    }

    public function getBuyerMasterListIdAttribute(): mixed
    {
        return $this->attributes['buyer_id'] ?? null;
    }

    public function buyerMasterList(): BelongsTo
    {
        return $this->belongsTo(BuyerMasterList::class, 'buyer_id', 'id');
    }

    public function buyer(): BelongsTo
    {
        return $this->buyerMasterList();
    }

    /**
     * Automatically cancel reservations past their deadline if no downpayment
     * installment has been paid yet, and release their lots back to Available.
     */
    public static function cancelExpiredUnpaid(): int
    {
        $expired = static::with(['lot', 'downpayments'])
            ->whereIn('status', [
                \App\Enums\ReservationStatus::Pending->value,
                \App\Enums\ReservationStatus::Reserved->value,
                'Converted',
            ])
            ->whereNotNull('deadline_date')
            ->whereDate('deadline_date', '<', now()->toDateString())
            ->whereDoesntHave('downpayments', function ($q) {
                $q->where('amount', '>', 0);
            })
            ->get();

        if ($expired->isEmpty()) {
            return 0;
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($expired) {
            $count = 0;
            foreach ($expired as $res) {
                $res->status = \App\Enums\ReservationStatus::Cancelled->value;
                $res->save();

                foreach ($res->downpayments as $dp) {
                    if ((float) $dp->amount <= 0) {
                        $dp->delete();
                    }
                }

                if ($res->lot && $res->lot->status === \App\Enums\LotStatus::Reserved->value) {
                    $hasOtherActive = static::where('lot_id', $res->lot_id)
                        ->whereKeyNot($res->id)
                        ->whereIn('status', \App\Enums\ReservationStatus::activeValues())
                        ->exists();
                    if (! $hasOtherActive) {
                        $res->lot->update(['status' => \App\Enums\LotStatus::Available->value]);
                    }
                }

                $count++;
            }

            return $count;
        });
    }
}
