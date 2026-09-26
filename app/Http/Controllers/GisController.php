<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lot;

class GisController extends Controller
{
    public function getLotsData()
    {
        $lots = Lot::with(['users' => function($q) {
            $q->where('status', '!=', 'Archived');
        }, 'utilityBills'])->get();
        
        $lotData = [];
        foreach ($lots as $lot) {
            $isNotConnected = $lot->users->isEmpty();
            $user = $isNotConnected ? null : $lot->users->first();
            $owner = $user ? $user->name : 'Unassigned';
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
                'status' => $lot->status,
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
