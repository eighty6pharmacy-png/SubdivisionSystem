<?php

namespace App\Http\Controllers;

use App\Models\BuyerMasterList;
use App\Models\Lot;

class GisController extends Controller
{
    public function getLotsData()
    {
        $lots = Lot::with(['users' => function($q) {
            $q->where('status', '!=', 'Archived');
        }, 'buyer.user', 'reservations.buyer', 'utilityBills'])->get();
        
        $lotData = [];
        foreach ($lots as $lot) {
            $user = $lot->users->first();
            $activeReservation = $lot->reservations->whereIn('status', \App\Enums\ReservationStatus::activeValues())->first();
            $hasActiveBuyer = BuyerMasterList::where('lot_id', $lot->id)->exists() || $activeReservation !== null;
            $isNotConnected = $user === null && ! $hasActiveBuyer;

            $status = $lot->status;
            if ($user !== null) {
                $status = in_array($lot->status, ['Available', 'Vacant Lot', 'Unassigned'], true) ? 'Occupied' : $lot->status;
                $owner = $user->name;
            } elseif ($activeReservation !== null || $lot->status === \App\Enums\LotStatus::Reserved->value) {
                $status = 'Reserved';
                $resBuyer = $activeReservation?->buyer ?? $lot->buyer;
                $owner = $resBuyer ? ('Reserved (' . trim($resBuyer->first_name . ' ' . $resBuyer->last_name) . ')') : 'Reserved';
            } else {
                $owner = 'Unassigned';
            }

            $userId = $user ? $user->id : null;
            $userEmail = $user ? $user->email : '';
            $userContact = $user ? $user->contact_number : '';
            $userRole = $user ? ($user->roles->first()->name ?? 'Resident') : '';
            $userJoined = $user ? $user->created_at->format('M d, Y') : '';
            $electricityBill = $lot->utilityBills->where('type', 'electricity')->sortByDesc('created_at')->first();
            $waterBill = $lot->utilityBills->where('type', 'water')->sortByDesc('created_at')->first();
            
            $lotData[] = [
                'id' => $lot->id,
                'block' => $lot->block,
                'lot_number' => $lot->lot_number,
                'status' => $status,
                'contract_price' => $lot->contract_price !== null ? (float) $lot->contract_price : null,
                'reservation_amount' => $activeReservation ? (float) $activeReservation->amount : null,
                'lot_area' => $lot->lot_area,
                'floor_area' => $lot->floor_area,
                'description' => $lot->description,
                'tct_no' => $lot->tct_no,
                'pid' => $lot->pid,
                'tax_dec_no' => $lot->tax_dec_no,
                'hasActiveBuyer' => $hasActiveBuyer,
                'isNotConnected' => $isNotConnected,
                'owner' => $owner,
                'user_id' => $userId,
                'user_email' => $userEmail,
                'user_contact' => $userContact,
                'user_role' => $userRole,
                'user_joined' => $userJoined,
                'electricity_status' => $electricityBill ? $electricityBill->status : 'unpaid',
                'water_status' => $waterBill ? $waterBill->status : 'unpaid',
            ];
        }
        
        return response()->json($lotData);
    }
}
