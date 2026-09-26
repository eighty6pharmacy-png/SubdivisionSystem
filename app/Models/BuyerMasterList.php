<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;

class BuyerMasterList extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'first_name', 'middle_name', 'last_name', 'civil_status', 'spouse_name',
        'present_address', 'postal_address', 'contact_number', 'proof_of_id', 'pagibig_number',
        'financing_method', // Newly added
        'block_no', 'lot_no', 'tct_no', 'pid', 'tax_dec_no', 'lot_area', 'floor_area', 'description',
        'contract_amount', 'improvement_amount', 'equity', 'loan_base', 'loan_term',
        'monthly_amortization', 'mri_sri', 'insurance', 'mri_ds_1time', 'nonlife_1time',
        'retention', 'total_deductions'
    ];
    
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
