<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuyerMasterList extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'email', 'civil_status', 'spouse_name',
        'present_address', 'postal_address', 'contact_number', 'proof_of_id', 'pagibig_number',
        'financing_method', // Newly added
        'lot_id', 'block_no', 'lot_no', 'tct_no', 'pid', 'tax_dec_no', 'lot_area', 'floor_area', 'description',
        'contract_amount', 'improvement_amount', 'equity', 'loan_base', 'loan_term',
        'monthly_amortization', 'mri_sri', 'insurance', 'mri_ds_1time', 'nonlife_1time',
        'retention', 'total_deductions'
    ];
    
    public function reservations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(User::class, 'buyer_master_list_id');
    }

    public function financingRecord(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FinancingRecord::class, 'buyer_master_list_id');
    }

    public function lot(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }
}
