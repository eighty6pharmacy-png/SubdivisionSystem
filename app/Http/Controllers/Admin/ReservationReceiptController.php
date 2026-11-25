<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancingRecord;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReservationReceiptController extends Controller
{
    /**
     * Return complete GIS-linked reservation receipt data as JSON for editing.
     */
    public function showData(int|string $id): JsonResponse
    {
        $reservation = $this->loadReservation($id);

        return response()->json([
            'success' => true,
            'data' => $this->buildReceiptData($reservation),
        ]);
    }

    /**
     * Save editable reservation details, buyer information, and GIS lot specifications.
     */
    public function updateData(Request $request, int|string $id): JsonResponse
    {
        $reservation = $this->loadReservation($id);

        $validated = $request->validate([
            'reservation_date' => ['nullable', 'date'],
            'deadline_date' => ['nullable', 'date'],
            'amount' => ['nullable', 'numeric', 'min:0'],

            // Buyer information
            'buyer_first_name' => ['nullable', 'string', 'max:100'],
            'buyer_middle_name' => ['nullable', 'string', 'max:100'],
            'buyer_last_name' => ['nullable', 'string', 'max:100'],
            'civil_status' => ['nullable', 'string', 'max:50'],
            'spouse_name' => ['nullable', 'string', 'max:255'],
            'postal_address' => ['nullable', 'string', 'max:500'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'contact_number' => ['nullable', 'string', 'max:50'],

            // GIS Lot / Property specification
            'lot_area' => ['nullable', 'numeric', 'min:0'],
            'floor_area' => ['nullable', 'numeric', 'min:0'],
            'house_model' => ['nullable', 'string', 'max:500'],
            'total_contract_price' => ['nullable', 'numeric', 'min:0'],
            'tct_no' => ['nullable', 'string', 'max:150'],
            'pid' => ['nullable', 'string', 'max:150'],
            'tax_dec_no' => ['nullable', 'string', 'max:150'],

            // Receipt / Application specific fields
            'or_number' => ['nullable', 'string', 'max:100'],
            'net_equity_or_number' => ['nullable', 'string', 'max:100'],
            'amount_in_words' => ['nullable', 'string', 'max:255'],
            'tel_nos' => ['nullable', 'string', 'max:100'],
            'project_name' => ['nullable', 'string', 'max:150'],
            'loan_req_deadline' => ['nullable', 'date'],
            'pdc_deadline' => ['nullable', 'date'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'loanable_pagibig' => ['nullable', 'numeric', 'min:0'],
            'loanable_df' => ['nullable', 'numeric', 'min:0'],
            'equity_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'required_equity' => ['nullable', 'numeric', 'min:0'],
            'net_equity_downpayment' => ['nullable', 'numeric', 'min:0'],
            'term_of_equity' => ['nullable', 'string', 'max:100'],
            'monthly_amortization_equity' => ['nullable', 'numeric', 'min:0'],
            'equity_period_count' => ['nullable', 'integer', 'min:1', 'max:240'],
            'equity_start_date' => ['nullable', 'string', 'max:100'],
            'loan_balance_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'loan_balance_amount' => ['nullable', 'numeric', 'min:0'],
            'loan_term' => ['nullable', 'string', 'max:100'],
            'loan_interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'loan_monthly_amortization' => ['nullable', 'numeric', 'min:0'],
            'loan_period_from' => ['nullable', 'string', 'max:100'],
            'loan_period_to' => ['nullable', 'string', 'max:100'],
            'financing_type' => ['nullable', 'string', 'in:PAG-IBIG FUND,DIRECT FINANCING,END BUYER\'S BANK FINANCING'],
            'building_model_label' => ['nullable', 'string', 'max:150'],
            'witness_name' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($reservation, $validated, $request) {
            // 1. Update Reservation core fields
            if ($request->filled('reservation_date')) {
                $reservation->reservation_date = Carbon::parse($validated['reservation_date'])->toDateString();
            }
            if ($request->filled('deadline_date')) {
                $reservation->deadline_date = Carbon::parse($validated['deadline_date'])->toDateString();
            }
            if ($request->has('amount') && $validated['amount'] !== null) {
                $reservation->amount = (float) $validated['amount'];
            }

            // 2. Sync Buyer information back to BuyerMasterList & User if linked
            $buyer = $reservation->buyerMasterList;
            if ($buyer) {
                $buyerUpdates = array_filter([
                    'first_name' => $validated['buyer_first_name'] ?? null,
                    'middle_name' => $validated['buyer_middle_name'] ?? null,
                    'last_name' => $validated['buyer_last_name'] ?? null,
                    'civil_status' => $validated['civil_status'] ?? null,
                    'spouse_name' => $validated['spouse_name'] ?? null,
                    'postal_address' => $validated['postal_address'] ?? null,
                    'contact_number' => $validated['contact_number'] ?? null,
                ], fn ($v) => $v !== null);

                if ($request->has('spouse_name')) {
                    $buyerUpdates['spouse_name'] = $validated['spouse_name'] ?? '';
                }
                if ($request->has('buyer_middle_name')) {
                    $buyerUpdates['middle_name'] = $validated['buyer_middle_name'] ?? '';
                }

                if ($buyerUpdates !== []) {
                    $buyer->update($buyerUpdates);
                }
            }

            // 3. Sync GIS Property Specification back to Lot
            $lot = $reservation->lot;
            if ($lot) {
                $lotUpdates = [];
                if ($request->has('lot_area')) {
                    $lotUpdates['lot_area'] = $validated['lot_area'];
                }
                if ($request->has('floor_area')) {
                    $lotUpdates['floor_area'] = $validated['floor_area'];
                }
                if ($request->has('house_model')) {
                    $lotUpdates['description'] = $validated['house_model'];
                }
                if ($request->has('total_contract_price')) {
                    $lotUpdates['contract_price'] = $validated['total_contract_price'];
                }
                if ($request->has('tct_no')) {
                    $lotUpdates['tct_no'] = $validated['tct_no'];
                }
                if ($request->has('pid')) {
                    $lotUpdates['pid'] = $validated['pid'];
                }
                if ($request->has('tax_dec_no')) {
                    $lotUpdates['tax_dec_no'] = $validated['tax_dec_no'];
                }

                if ($lotUpdates !== []) {
                    $lot->update($lotUpdates);
                }
            }

            // 4. Store receipt-specific & override fields in reservations.receipt_data
            $existing = is_array($reservation->receipt_data) ? $reservation->receipt_data : [];
            $receiptKeys = [
                'or_number',
                'net_equity_or_number',
                'amount_in_words',
                'tel_nos',
                'project_name',
                'loan_req_deadline',
                'pdc_deadline',
                'discount_percent',
                'discount_amount',
                'loanable_pagibig',
                'loanable_df',
                'equity_percent',
                'required_equity',
                'net_equity_downpayment',
                'term_of_equity',
                'monthly_amortization_equity',
                'equity_period_count',
                'equity_start_date',
                'loan_balance_percent',
                'loan_balance_amount',
                'loan_term',
                'loan_interest_rate',
                'loan_monthly_amortization',
                'loan_period_from',
                'loan_period_to',
                'financing_type',
                'building_model_label',
                'witness_name',
                'billing_address',
                'buyer_first_name',
                'buyer_middle_name',
                'buyer_last_name',
                'civil_status',
                'spouse_name',
                'postal_address',
                'contact_number',
            ];

            foreach ($receiptKeys as $key) {
                if ($request->has($key)) {
                    $existing[$key] = $validated[$key] ?? null;
                }
            }

            $reservation->receipt_data = $existing;
            $reservation->save();
        });

        $fresh = $this->loadReservation($id);

        return response()->json([
            'success' => true,
            'message' => 'Reservation receipt details updated and synced with GIS & Buyer records.',
            'data' => $this->buildReceiptData($fresh),
        ]);
    }

    /**
     * Stream (viewable first) or download the Reservation Application Receipt PDF.
     */
    public function pdf(Request $request, int|string $id)
    {
        $reservation = $this->loadReservation($id);
        $data = $this->buildReceiptData($reservation);
        $download = $request->boolean('download');

        $pdf = app('dompdf.wrapper');
        $pdf->setPaper('a4', 'portrait');
        $pdf->loadView('admin.reservation-fee.receipt_pdf', ['r' => $data]);

        $fileName = 'Reservation_Receipt_B' . ($data['block'] ?: 'X') . '_L' . ($data['lot_number'] ?: 'X') . '_' . $reservation->id . '.pdf';

        return $download ? $pdf->download($fileName) : $pdf->stream($fileName);
    }

    protected function loadReservation(int|string $id): Reservation
    {
        return Reservation::with([
            'lot',
            'buyerMasterList.user',
            'buyerMasterList.financingRecord',
            'downpayments',
        ])->findOrFail($id);
    }

    /**
     * Merge Reservation, Buyer, GIS Lot, Downpayment, FinancingRecord, and saved receipt_data.
     *
     * @return array<string, mixed>
     */
    protected function buildReceiptData(Reservation $res): array
    {
        $buyer = $res->buyerMasterList;
        $user = $buyer?->user;
        $lot = $res->lot;
        $dp = $res->downpayments->sortByDesc('id')->first();
        $financing = $buyer?->financingRecord
            ?? ($lot ? FinancingRecord::where('lot_id', $lot->id)->latest('id')->first() : null);

        $saved = is_array($res->receipt_data) ? $res->receipt_data : [];

        $resDate = $res->reservation_date
            ? Carbon::parse($res->reservation_date)
            : ($res->created_at ? Carbon::parse($res->created_at) : now());

        $default90d = $res->deadline_date
            ? Carbon::parse($res->deadline_date)->toDateString()
            : $resDate->copy()->addDays(90)->toDateString();

        $default3m = $res->deadline_date
            ? Carbon::parse($res->deadline_date)->toDateString()
            : $resDate->copy()->addMonthsNoOverflow(3)->toDateString();

        $amount = (float) ($res->amount ?? 20000);

        // Buyer details
        $firstName = $saved['buyer_first_name'] ?? $buyer?->first_name ?? $user?->first_name ?? '';
        $middleName = $saved['buyer_middle_name'] ?? $buyer?->middle_name ?? $user?->middle_name ?? '';
        $lastName = $saved['buyer_last_name'] ?? $buyer?->last_name ?? $user?->last_name ?? '';
        $civilStatus = $saved['civil_status'] ?? $buyer?->civil_status ?? 'Single';
        $spouseName = $saved['spouse_name'] ?? $buyer?->spouse_name ?? '';
        $postalAddress = $saved['postal_address'] ?? $buyer?->postal_address ?? $buyer?->present_address ?? '';
        $billingAddress = $saved['billing_address'] ?? $buyer?->present_address ?? $postalAddress;
        $contactNumber = $saved['contact_number'] ?? $buyer?->contact_number ?? $user?->contact_number ?? '';

        // GIS Property Specification (from Lot, falling back to FinancingRecord when lot price is empty/zero)
        $lotTcp = $lot?->contract_price !== null ? (float) $lot->contract_price : 0.0;
        $finTcp = $financing?->contract_price !== null ? (float) $financing->contract_price : 0.0;
        $tcp = $lotTcp > 0 ? $lotTcp : $finTcp;
        $discountPercent = (float) ($saved['discount_percent'] ?? 0);
        $discountAmount = array_key_exists('discount_amount', $saved) && $saved['discount_amount'] !== null
            ? (float) $saved['discount_amount']
            : round($tcp * ($discountPercent / 100), 2);
        $ntp = max(0, round($tcp - $discountAmount, 2));

        // Financing / Equity — synced strictly from actual Downpayment / FinancingRecord when present
        $financingMethodRaw = strtoupper((string) ($financing?->financing_method ?? 'PAG-IBIG FUND'));
        $defaultFinancingType = match (true) {
            str_contains($financingMethodRaw, 'DIRECT') => 'DIRECT FINANCING',
            str_contains($financingMethodRaw, 'BANK') => "END BUYER'S BANK FINANCING",
            default => 'PAG-IBIG FUND',
        };
        $financingType = $saved['financing_type'] ?? $defaultFinancingType;

        $hasManualDpOverride = (array_key_exists('required_equity', $saved) && $saved['required_equity'] !== null)
            || (array_key_exists('net_equity_downpayment', $saved) && $saved['net_equity_downpayment'] !== null)
            || (array_key_exists('equity_percent', $saved) && $saved['equity_percent'] !== null);
        $hasDownpayment = $dp !== null || $hasManualDpOverride;

        if ($dp !== null) {
            // Downpayment record is the source of truth: amount (paid so far) + balance (remaining) = net equity DP
            $dpTotalNet = round((float) $dp->amount + (float) $dp->balance, 2);
            $dpGrossEquity = round($dpTotalNet + $amount, 2);
            $computedEquityPct = ($ntp > 0 && $dpGrossEquity > 0)
                ? round(($dpGrossEquity / $ntp) * 100, 2)
                : null;

            $equityPercent = $computedEquityPct;
            $requiredEquity = $dpGrossEquity;
            $netEquityDownpayment = $dpTotalNet;
            $equityMonths = $dp->months_to_pay ? (int) $dp->months_to_pay : null;
            $monthlyEquityAmort = $dp->monthly_amortization !== null
                ? (float) $dp->monthly_amortization
                : ($equityMonths && $equityMonths > 0 ? round($netEquityDownpayment / $equityMonths, 2) : null);

            $loanBalancePercent = $equityPercent !== null ? max(0, round(100.0 - $equityPercent, 2)) : null;

            $finLoanBase = $financing?->loan_base !== null ? (float) $financing->loan_base : 0.0;
            $loanBalanceAmount = $finLoanBase > 0
                ? $finLoanBase
                : max(0, round($ntp - $requiredEquity, 2));

            $loanablePagibig = $financingType === 'PAG-IBIG FUND' ? $loanBalanceAmount : 0.0;
            $loanableDf = $financingType === 'DIRECT FINANCING' ? $loanBalanceAmount : 0.0;
        } elseif ($hasManualDpOverride) {
            $equityPercent = array_key_exists('equity_percent', $saved) && $saved['equity_percent'] !== null
                ? (float) $saved['equity_percent']
                : null;
            $loanBalancePercent = array_key_exists('loan_balance_percent', $saved) && $saved['loan_balance_percent'] !== null
                ? (float) $saved['loan_balance_percent']
                : ($equityPercent !== null ? max(0, round(100.0 - $equityPercent, 2)) : null);
            $requiredEquity = array_key_exists('required_equity', $saved) && $saved['required_equity'] !== null
                ? (float) $saved['required_equity']
                : ($equityPercent !== null ? round($ntp * ($equityPercent / 100), 2) : null);
            $netEquityDownpayment = array_key_exists('net_equity_downpayment', $saved) && $saved['net_equity_downpayment'] !== null
                ? (float) $saved['net_equity_downpayment']
                : ($requiredEquity !== null ? max(0, round($requiredEquity - $amount, 2)) : null);
            $equityMonths = array_key_exists('equity_period_count', $saved) && $saved['equity_period_count'] !== null
                ? (int) $saved['equity_period_count']
                : null;
            $monthlyEquityAmort = array_key_exists('monthly_amortization_equity', $saved) && $saved['monthly_amortization_equity'] !== null
                ? (float) $saved['monthly_amortization_equity']
                : ($netEquityDownpayment !== null && $equityMonths && $equityMonths > 0 ? round($netEquityDownpayment / $equityMonths, 2) : null);
            $loanBalanceAmount = array_key_exists('loan_balance_amount', $saved) && $saved['loan_balance_amount'] !== null
                ? (float) $saved['loan_balance_amount']
                : ($requiredEquity !== null ? max(0, round($ntp - $requiredEquity, 2)) : null);
            $loanablePagibig = array_key_exists('loanable_pagibig', $saved) && $saved['loanable_pagibig'] !== null
                ? (float) $saved['loanable_pagibig']
                : ($financingType === 'PAG-IBIG FUND' ? $loanBalanceAmount : 0.0);
            $loanableDf = array_key_exists('loanable_df', $saved) && $saved['loanable_df'] !== null
                ? (float) $saved['loanable_df']
                : ($financingType === 'DIRECT FINANCING' ? $loanBalanceAmount : 0.0);
        } else {
            // No downpayment created yet: do not fabricate downpayment/equity figures on the receipt
            $equityPercent = null;
            $requiredEquity = null;
            $netEquityDownpayment = null;
            $equityMonths = null;
            $monthlyEquityAmort = null;
            $loanBalancePercent = null;
            $loanBalanceAmount = null;
            $loanablePagibig = null;
            $loanableDf = null;
        }

        $amountInWords = ! empty($saved['amount_in_words'])
            ? strtoupper((string) $saved['amount_in_words'])
            : $this->amountToWords($amount);

        $orNumber = ! empty($saved['or_number'])
            ? (string) $saved['or_number']
            : ('OR-RES-' . str_pad((string) $res->id, 5, '0', STR_PAD_LEFT));

        $houseModel = $lot?->description ?: '';

        return [
            'id' => $res->id,
            'status' => $res->status,
            'reservation_date' => $resDate->toDateString(),
            'reservation_date_formatted' => $resDate->format('F d, Y'),
            'deadline_date' => $res->deadline_date ? Carbon::parse($res->deadline_date)->toDateString() : $default90d,
            'or_number' => $orNumber,
            'net_equity_or_number' => $saved['net_equity_or_number'] ?? '',
            'amount' => $amount,
            'amount_in_words' => $amountInWords,
            'tel_nos' => $saved['tel_nos'] ?? '',
            'project_name' => $saved['project_name'] ?? 'ALTHESA VILLAGE',
            'loan_req_deadline' => $saved['loan_req_deadline'] ?? $default90d,
            'pdc_deadline' => $saved['pdc_deadline'] ?? $default3m,
            'witness_name' => $saved['witness_name'] ?? 'Admin / Sales Staff',

            // Buyer info
            'buyer_first_name' => $firstName,
            'buyer_middle_name' => $middleName,
            'buyer_last_name' => $lastName,
            'buyer_full_name' => trim("{$firstName} {$middleName} {$lastName}"),
            'civil_status' => $civilStatus,
            'spouse_name' => $spouseName,
            'postal_address' => $postalAddress,
            'billing_address' => $billingAddress,
            'contact_number' => $contactNumber,

            // GIS Lot / Property Specification
            'lot_id' => $lot?->id,
            'block' => $lot?->block ?? '',
            'lot_number' => $lot?->lot_number ?? '',
            'lot_area' => $lot?->lot_area !== null ? (float) $lot->lot_area : null,
            'floor_area' => $lot?->floor_area !== null ? (float) $lot->floor_area : null,
            'house_model' => $houseModel,
            'building_model_label' => $saved['building_model_label'] ?? ($lot?->floor_area !== null ? 'Floor Area: ' . number_format((float) $lot->floor_area, 2) . ' sqm' : ''),
            'total_contract_price' => $tcp,
            'tct_no' => $lot?->tct_no ?? '',
            'pid' => $lot?->pid ?? '',
            'tax_dec_no' => $lot?->tax_dec_no ?? '',

            // Financial / Installment Plan
            'has_downpayment' => $hasDownpayment,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount,
            'net_contract_price' => $ntp,
            'loanable_pagibig' => $loanablePagibig,
            'loanable_df' => $loanableDf,
            'equity_percent' => $equityPercent,
            'required_equity' => $requiredEquity,
            'net_equity_downpayment' => $netEquityDownpayment,
            'term_of_equity' => $saved['term_of_equity'] ?? ($equityMonths ? ($equityMonths . ' Months') : ''),
            'monthly_amortization_equity' => $monthlyEquityAmort,
            'equity_period_count' => $equityMonths,
            'equity_start_date' => $saved['equity_start_date'] ?? ($dp?->due_date ? Carbon::parse($dp->due_date)->format('M d, Y') : ''),
            'loan_balance_percent' => $loanBalancePercent,
            'loan_balance_amount' => $loanBalanceAmount,
            'loan_term' => $saved['loan_term'] ?? ($financing?->loan_term ?? ''),
            'loan_interest_rate' => $saved['loan_interest_rate'] ?? '',
            'loan_monthly_amortization' => array_key_exists('loan_monthly_amortization', $saved) && $saved['loan_monthly_amortization'] !== null
                ? (float) $saved['loan_monthly_amortization']
                : ($financing?->monthly_amortization !== null ? (float) $financing->monthly_amortization : 0),
            'loan_period_from' => $saved['loan_period_from'] ?? '',
            'loan_period_to' => $saved['loan_period_to'] ?? '',
            'financing_type' => $financingType,
        ];
    }

    /**
     * Convert a peso amount into uppercase English words (e.g. TWENTY THOUSAND PESOS ONLY).
     */
    protected function amountToWords(float $amount): string
    {
        $pesos = (int) floor($amount);
        $centavos = (int) round(($amount - $pesos) * 100);

        $words = $this->integerToWords($pesos) . ' PESOS';
        if ($centavos > 0) {
            $words .= ' AND ' . str_pad((string) $centavos, 2, '0', STR_PAD_LEFT) . '/100';
        }

        return trim($words . ' ONLY');
    }

    protected function integerToWords(int $number): string
    {
        if ($number === 0) {
            return 'ZERO';
        }

        $ones = [
            '', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE',
            'TEN', 'ELEVEN', 'TWELVE', 'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN',
            'SEVENTEEN', 'EIGHTEEN', 'NINETEEN',
        ];
        $tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];
        $scales = ['', 'THOUSAND', 'MILLION', 'BILLION'];

        $parts = [];
        $scaleIndex = 0;

        while ($number > 0) {
            $chunk = $number % 1000;
            if ($chunk > 0) {
                $chunkWords = [];
                $hundreds = intdiv($chunk, 100);
                $remainder = $chunk % 100;

                if ($hundreds > 0) {
                    $chunkWords[] = $ones[$hundreds] . ' HUNDRED';
                }

                if ($remainder >= 20) {
                    $ten = intdiv($remainder, 10);
                    $unit = $remainder % 10;
                    $chunkWords[] = $tens[$ten] . ($unit > 0 ? '-' . $ones[$unit] : '');
                } elseif ($remainder > 0) {
                    $chunkWords[] = $ones[$remainder];
                }

                if ($scales[$scaleIndex] !== '') {
                    $chunkWords[] = $scales[$scaleIndex];
                }

                array_unshift($parts, implode(' ', $chunkWords));
            }
            $number = intdiv($number, 1000);
            $scaleIndex++;
        }

        return implode(' ', $parts);
    }
}
