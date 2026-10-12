<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string|null $external_id
 * @property string|null $first_name
 * @property string|null $middle_name
 * @property string|null $last_name
 * @property string $name
 * @property string $email
 * @property string|null $contact_number
 * @property string|null $password
 * @property string|null $status
 * @property \Illuminate\Support\Carbon|null $joined_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles {
        assignRole as spatieAssignRole;
        syncRoles as spatieSyncRoles;
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'external_id',
        'name',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'contact_number',
        'password',
        'status',
        'joined_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'name',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'joined_at' => 'date',
        ];
    }

    public function getNameAttribute(): string
    {
        return trim(implode(' ', array_filter([
            $this->attributes['first_name'] ?? null,
            $this->attributes['middle_name'] ?? null,
            $this->attributes['last_name'] ?? null,
        ], fn ($part) => $part !== null && $part !== '')));
    }

    public function setNameAttribute(?string $value): void
    {
        $parts = preg_split('/\s+/', trim((string) $value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $first = array_shift($parts) ?? '';
        $last = $parts !== [] ? array_pop($parts) : '';
        $middle = $parts !== [] ? implode(' ', $parts) : null;

        $this->attributes['first_name'] = $first;
        $this->attributes['middle_name'] = $middle;
        $this->attributes['last_name'] = $last !== '' ? $last : null;
    }

    public function getPaymentBehaviorAttribute(): string
    {
        return $this->resident?->payment_behavior ?? 'Not available yet';
    }

    public function setPaymentBehaviorAttribute(?string $value): void
    {
        $resident = $this->resident;
        if (! $resident) {
            return;
        }

        $resident->payment_behavior = $value ?: 'Not available yet';
        $resident->save();
    }

    public function assignRole(...$roles)
    {
        $this->refuseRoleChange($this->roleNamesFrom($roles));
        $result = $this->spatieAssignRole(...$roles);
        $this->syncRoleProfile();

        return $result;
    }

    public function syncRoles(...$roles)
    {
        $this->refuseRoleChange($this->roleNamesFrom($roles));
        $result = $this->spatieSyncRoles(...$roles);
        $this->syncRoleProfile();

        return $result;
    }

    /**
     * A buyer or resident who still has contract, lot, bill, or incident records keeps that role.
     */
    public function refusalForRoleChange(string $newRole): ?string
    {
        $current = $this->roles()->pluck('name');
        if ($current->isEmpty() || $current->contains($newRole)) {
            return null;
        }

        if ($current->contains('Buyer') && $this->buyerProfileHasDependents()) {
            return 'This person is a buyer with a reservation or financing record. Keep the buyer role. Do not change them into a guard, admin, resident, or finance officer.';
        }

        if ($current->contains('Resident') && $this->residentProfileHasDependents()) {
            return 'This person is a resident with a lot, a bill, or an incident. Keep the resident role. Do not change them into a guard, admin, buyer, or finance officer.';
        }

        return null;
    }

    /**
     * Keep the role table in step with Spatie. Staff rows can be removed.
     * Buyer and resident rows stay when reservations, financing, lots, bills, or incidents still point at them.
     */
    public function syncRoleProfile(): void
    {
        $this->unsetRelation('roles');
        $roleNames = $this->roles()->pluck('name');

        $map = [
            'Admin' => AdminAccount::class,
            'Finance Officer' => FinanceOfficer::class,
            'Security Guard' => GuardAccount::class,
            'Resident' => Resident::class,
            'Buyer' => BuyerMasterList::class,
        ];

        foreach ($map as $name => $class) {
            if ($roleNames->contains($name)) {
                if ($class === BuyerMasterList::class) {
                    $row = $class::withTrashed()->firstOrCreate(['user_id' => $this->id]);
                    if ($row->trashed()) {
                        $row->restore();
                    }
                } else {
                    $class::query()->firstOrCreate(['user_id' => $this->id]);
                }
                continue;
            }

            if ($name === 'Buyer' && $this->buyerProfileHasDependents()) {
                continue;
            }
            if ($name === 'Resident' && $this->residentProfileHasDependents()) {
                continue;
            }

            $class::query()->where('user_id', $this->id)->delete();
        }
    }

    public function lots(): BelongsToMany
    {
        return $this->belongsToMany(Lot::class, 'resident_lots', 'resident_id', 'lot_id')->withTimestamps();
    }

    public function utilityBills(): HasMany
    {
        return $this->hasMany(UtilityBill::class, 'resident_id');
    }

    public function adminAccount(): HasOne
    {
        return $this->hasOne(AdminAccount::class, 'user_id');
    }

    public function financeOfficer(): HasOne
    {
        return $this->hasOne(FinanceOfficer::class, 'user_id');
    }

    public function guardAccount(): HasOne
    {
        return $this->hasOne(GuardAccount::class, 'user_id');
    }

    public function resident(): HasOne
    {
        return $this->hasOne(Resident::class, 'user_id');
    }

    public function buyerMasterList(): HasOne
    {
        return $this->hasOne(BuyerMasterList::class, 'user_id');
    }

    /**
     * @param  list<string>  $roleNames
     */
    private function refuseRoleChange(array $roleNames): void
    {
        foreach ($roleNames as $roleName) {
            $reason = $this->refusalForRoleChange($roleName);
            if ($reason) {
                throw new \RuntimeException($reason);
            }
        }
    }

    /**
     * @param  array<int, mixed>  $roles
     * @return list<string>
     */
    private function roleNamesFrom(array $roles): array
    {
        $names = [];
        $walk = function (mixed $item) use (&$walk, &$names): void {
            if ($item instanceof \Illuminate\Support\Collection) {
                $item = $item->all();
            }
            if (is_array($item)) {
                foreach ($item as $child) {
                    $walk($child);
                }

                return;
            }
            if ($item instanceof \Spatie\Permission\Models\Role) {
                $names[] = $item->name;

                return;
            }
            if ($item instanceof \BackedEnum) {
                $names[] = (string) $item->value;

                return;
            }
            if (is_string($item) || is_int($item)) {
                $names[] = (string) $item;
            }
        };
        $walk($roles);

        return $names;
    }

    private function buyerProfileHasDependents(): bool
    {
        $buyerIds = BuyerMasterList::withTrashed()->where('user_id', $this->id)->pluck('id');
        if ($buyerIds->isEmpty()) {
            return false;
        }

        return Reservation::withTrashed()->whereIn('buyer_id', $buyerIds)->exists()
            || FinancingRecord::query()->whereIn('buyer_id', $buyerIds)->exists();
    }

    private function residentProfileHasDependents(): bool
    {
        return $this->lots()->exists()
            || UtilityBill::query()->where('resident_id', $this->id)->exists()
            || Incident::query()->where('resident_id', $this->id)->exists();
    }
}
