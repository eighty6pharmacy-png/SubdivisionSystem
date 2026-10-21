<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class BuyerMasterList extends Model
{
    use SoftDeletes;

    /**
     * Government and commonly accepted IDs for a buyer record.
     *
     * @var list<string>
     */
    public const ID_TYPES = [
        'Philippine National ID',
        'Driver\'s License',
        'Passport',
        'UMID',
        'SSS ID',
        'GSIS eCard',
        'PRC ID',
        'Postal ID',
        'Voter\'s ID',
        'PhilHealth ID',
        'TIN ID',
        'Senior Citizen ID',
        'Pag-IBIG Loyalty Card',
        'Company ID',
        'School ID',
        'Barangay ID',
    ];

    protected $table = 'buyers';

    protected $with = ['user', 'lot'];

    /**
     * These values live on the linked user account or lot. The master-list modal reads them from the buyer payload.
     *
     * @var list<string>
     */
    protected $appends = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'contact_number',
        'block_no',
        'lot_no',
    ];

    /**
     * Identity columns are not stored here. They are read and written through the user account.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'civil_status',
        'spouse_name',
        'present_address',
        'postal_address',
        'id_type',
        'proof_of_id',
        'pagibig_number',
        'lot_id',
        'tct_no',
        'pid',
        'tax_dec_no',
        'lot_area',
        'floor_area',
        'description',
    ];

    /**
     * @var list<string>
     */
    private array $identityPending = [];

    protected static function booted(): void
    {
        static::saving(function (BuyerMasterList $buyer) {
            if ($buyer->identityPending === []) {
                return;
            }

            $user = $buyer->relationLoaded('user') ? $buyer->user : $buyer->user()->first();
            if ($user) {
                $user->fill($buyer->identityPending)->save();
                $buyer->unsetRelation('user');
            }
            $buyer->identityPending = [];
        });
    }

    public static function create(array $attributes = [])
    {
        return DB::transaction(function () use ($attributes) {
            $userKeys = ['first_name', 'middle_name', 'last_name', 'email', 'contact_number', 'password', 'name', 'status', 'joined_at'];
            $userAttributes = array_intersect_key($attributes, array_flip($userKeys));
            $buyerAttributes = array_diff_key($attributes, array_flip(array_merge($userKeys, ['id'])));

            $email = $userAttributes['email'] ?? null;
            if ($email === null || $email === '') {
                throw new \InvalidArgumentException('A buyer needs an email address. The buyer form requires one.');
            }

            unset($userAttributes['password']);
            $userAttributes['email'] = $email;
            $userAttributes['status'] = $userAttributes['status'] ?? 'Active';

            $user = User::create($userAttributes);
            Role::findOrCreate('Buyer', 'web');
            $user->assignRole('Buyer');

            $buyer = static::query()->where('user_id', $user->id)->first() ?? new static(['user_id' => $user->id]);
            $buyer->user_id = $user->id;
            $buyer->fill($buyerAttributes);
            $buyer->save();
            $buyer->setRelation('user', $user->fresh());

            return $buyer;
        });
    }

    public function update(array $attributes = [], array $options = [])
    {
        $userKeys = ['first_name', 'middle_name', 'last_name', 'email', 'contact_number', 'password', 'name'];
        $userAttributes = array_intersect_key($attributes, array_flip($userKeys));
        $buyerAttributes = array_diff_key($attributes, array_flip(array_merge($userKeys, ['block_no', 'lot_no'])));

        if ($userAttributes !== []) {
            $user = $this->user()->first();
            if ($user) {
                $user->update($userAttributes);
                $this->unsetRelation('user');
            }
        }

        if ($buyerAttributes === []) {
            return true;
        }

        return parent::update($buyerAttributes, $options);
    }

    public function getBlockNoAttribute(): ?string
    {
        return $this->lot?->block
            ?? $this->reservations->where('status', '!=', \App\Enums\ReservationStatus::Cancelled->value)->first()?->lot?->block
            ?? ($this->attributes['block_no'] ?? null);
    }

    public function setBlockNoAttribute(mixed $value): void
    {
    }

    public function getLotNoAttribute(): ?string
    {
        return $this->lot?->lot_number
            ?? $this->reservations->where('status', '!=', \App\Enums\ReservationStatus::Cancelled->value)->first()?->lot?->lot_number
            ?? ($this->attributes['lot_no'] ?? null);
    }

    public function setLotNoAttribute(mixed $value): void
    {
    }

    protected function resolveActiveLot(): ?Lot
    {
        return $this->lot
            ?? $this->reservations->where('status', '!=', \App\Enums\ReservationStatus::Cancelled->value)->first()?->lot;
    }

    public function getLotAreaAttribute($value)
    {
        return $this->resolveActiveLot()?->lot_area ?? $value;
    }

    public function getFloorAreaAttribute($value)
    {
        return $this->resolveActiveLot()?->floor_area ?? $value;
    }

    public function getDescriptionAttribute($value)
    {
        return $this->resolveActiveLot()?->description ?? $value;
    }

    public function getTctNoAttribute($value)
    {
        return $this->resolveActiveLot()?->tct_no ?? $value;
    }

    public function getPidAttribute($value)
    {
        return $this->resolveActiveLot()?->pid ?? $value;
    }

    public function getTaxDecNoAttribute($value)
    {
        return $this->resolveActiveLot()?->tax_dec_no ?? $value;
    }

    public function getFirstNameAttribute(): ?string
    {
        return $this->identityPending['first_name'] ?? $this->user?->first_name;
    }

    public function setFirstNameAttribute(?string $value): void
    {
        $this->identityPending['first_name'] = $value;
    }

    public function getMiddleNameAttribute(): ?string
    {
        return $this->identityPending['middle_name'] ?? $this->user?->middle_name;
    }

    public function setMiddleNameAttribute(?string $value): void
    {
        $this->identityPending['middle_name'] = $value;
    }

    public function getLastNameAttribute(): ?string
    {
        return $this->identityPending['last_name'] ?? $this->user?->last_name;
    }

    public function setLastNameAttribute(?string $value): void
    {
        $this->identityPending['last_name'] = $value;
    }

    public function getEmailAttribute(): ?string
    {
        return $this->identityPending['email'] ?? $this->user?->email;
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->identityPending['email'] = $value;
    }

    public function getContactNumberAttribute(): ?string
    {
        return $this->identityPending['contact_number'] ?? $this->user?->contact_number;
    }

    public function setContactNumberAttribute(?string $value): void
    {
        $this->identityPending['contact_number'] = $value;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'buyer_id', 'id');
    }

    public function financingRecord(): HasOne
    {
        return $this->hasOne(FinancingRecord::class, 'buyer_id', 'id');
    }
}
