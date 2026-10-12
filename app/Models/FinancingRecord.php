<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancingRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'buyer_master_list_id',
        'lot_id',
        'financing_method',
        'loan_base',
        'loan_term',
        'monthly_amortization',
        'contract_price',
        'mbrdc_add_fees',
        'other_exp_annotation',
        'improvements_fee',
        'sri_mri',
        'pag_ibig_non_life',
        'interim_mri',
        'inspection_fee',
        'retention_fee',
        'retention_on_hold',
        'total_amt_due',
        'mbrdc_turn_over_fee',
        'total_consideration',
        'paid_by_vendee_equity',
        'mbrdc_amt_due_for_financing',
        'additional_bill_of_materials',
        'loan_release',
        'net_loan_proceeds',
        'receivables',
        'amount_paid',
        'balance',
        'rbgi_ap',
        'mbrdc_ap_in_house',
        'account_receivables',
        'remarks',
        'status',
        'loan_release_date',
        'paymongo_checkout_id',
    ];

    protected $casts = [
        'loan_release_date' => 'date',
    ];

    public function setBuyerMasterListIdAttribute($value): void
    {
        $this->attributes['buyer_id'] = $value;
        unset($this->attributes['buyer_master_list_id']);
    }

    public function getBuyerMasterListIdAttribute(): mixed
    {
        return $this->attributes['buyer_id'] ?? null;
    }

    public function buyerMasterList(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BuyerMasterList::class, 'buyer_id', 'id');
    }

    public function lot(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(FinancingPaymentHistory::class, 'financing_record_id');
    }
}
