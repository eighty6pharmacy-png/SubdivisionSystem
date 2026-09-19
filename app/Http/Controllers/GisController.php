<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lot;

class GisController extends Controller
{
    public function getLotsData()
    {
        $lots = Lot::with(['users', 'utilityBills'])->get();
        
        $lotData = [];
        foreach ($lots as $lot) {
            $isNotConnected = $lot->users->isEmpty();
            $user = $isNotConnected ? null : $lot->users->first();
            $owner = $user ? $user->name : 'Unassigned';
            $userId = $user ? $user->id : null;
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
                'electricity_status' => $electricityBill ? $electricityBill->status : 'unpaid',
                'water_status' => $waterBill ? $waterBill->status : 'unpaid',
            ];
        }
        
        return response()->json($lotData);
    }
}
