<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Visitor;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VisitorController extends Controller
{
    // For Admin / Guard viewing all visitors
    public function index()
    {
        $query = Visitor::with('host.lots')->orderBy('created_at', 'desc');
        
        if (auth()->check() && auth()->user()->hasRole('Resident')) {
            $query->where('host_id', auth()->id());
        }

        $visitors = $query->get()->map(function ($vis) {
            $block = 'N/A';
            $lotNum = 'N/A';
            $hostName = 'Unknown';
            
            if ($vis->host) {
                $hostName = $vis->host->name;
                if ($vis->host->lots->count() > 0) {
                    $lot = $vis->host->lots->first();
                    $block = $lot->block;
                    $lotNum = $lot->lot_number;
                }
            }

            return [
                'id' => substr($vis->id, 0, 8),
                'db_id' => $vis->id,
                'pin' => $vis->pin,
                'visitor' => $vis->visitor_name,
                'host' => $hostName,
                'block' => $block,
                'lot' => $lotNum,
                'purpose' => $vis->purpose,
                'validity' => $vis->validity,
                'status' => $vis->status,
                'arrival_time' => $vis->arrival_time,
                'type' => $vis->type,
                'plate_number' => $vis->plate_number,
                'visitor_address' => $vis->visitor_address,
                'date' => $vis->updated_at->format('Y-m-d'),
                'date_formatted' => $vis->updated_at->format('M d, Y'),
            ];
        })->toArray();

        return response()->json($visitors);
    }

    // For Resident requesting a code or Guard logging Walk-in
    public function store(Request $request)
    {
        $request->validate([
            'visitor_name' => 'required|string|max:255',
            'purpose' => 'required|string',
            'validity' => 'required|string',
            'type' => 'required|string',
            'visitor_address' => 'nullable|string',
        ]);

        $isWalkIn = $request->type === 'Walk-in';

        $visitor = Visitor::create([
            'host_id' => $isWalkIn ? null : auth()->id(),
            'visitor_name' => $request->visitor_name,
            'purpose' => $request->purpose,
            'validity' => $request->validity,
            'type' => $request->type,
            'plate_number' => $request->plate_number,
            'visitor_address' => $request->visitor_address,
            'status' => $isWalkIn ? 'Entered' : 'Pending',
            'arrival_time' => $isWalkIn ? \Carbon\Carbon::now()->setTimezone(config('app.timezone', 'Asia/Manila'))->format('h:i A') : null,
        ]);

        return response()->json(['success' => true, 'id' => substr($visitor->id, 0, 8)]);
    }

    // For Admin approving a code
    public function approve($id)
    {
        $visitor = Visitor::where('id', 'like', $id . '%')->with('host')->firstOrFail();
        
        if ($visitor->status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'Already processed']);
        }

        $pin = (string)rand(100000, 999999);
        $visitor->update([
            'status' => 'Approved',
            'pin' => $pin
        ]);

        // Send Email to Resident
        if ($visitor->host && $visitor->host->email) {
            try {
                $hostName = $visitor->host->name;
                $visName = $visitor->visitor_name;
                Mail::send([], [], function ($message) use ($visitor, $hostName, $visName, $pin) {
                    $message->to($visitor->host->email)
                        ->subject('🎉 Visitor Access Approved — Althesa Subdivision')
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                                <div style='background: #059669; padding: 20px; border-radius: 12px; text-align: center;'>
                                    <h2 style='color: #ffffff; margin: 0;'>Althesa Subdivision</h2>
                                    <p style='color: #ecfdf5; margin: 4px 0 0 0; font-size: 13px;'>Visitor Code Approved</p>
                                </div>
                                <div style='padding: 20px 0;'>
                                    <p style='font-size: 16px; color: #0f172a;'>Hello <strong>{$hostName}</strong>,</p>
                                    <p style='color: #475569;'>Your visitor request for <strong>{$visName}</strong> has been approved. Please share this PIN with them.</p>
                                    <div style='background: #ecfdf5; border: 2px dashed #059669; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0;'>
                                        <span style='font-size: 13px; color: #047857; text-transform: uppercase; font-weight: 700;'>Visitor Entry PIN</span>
                                        <div style='font-size: 36px; font-weight: 800; color: #047857; letter-spacing: 6px; margin-top: 8px;'>{$pin}</div>
                                    </div>
                                    <p style='color: #475569;'>They can enter this PIN on the landing page for routing instructions or present it at the guard house.</p>
                                </div>
                            </div>
                        ");
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send visitor approval email: ' . $e->getMessage());
            }
        }

        return response()->json(['success' => true]);
    }
    
    // For Guard / Routing page validating PIN
    public function validatePin(Request $request)
    {
        $request->validate(['pin' => 'required|string']);
        
        $visitor = Visitor::where('pin', $request->pin)
            ->whereIn('status', ['Approved', 'Entered']) // Can view route even if already entered
            ->with('host.lots')
            ->first();

        if (!$visitor) {
            return response()->json(['success' => false, 'message' => 'Invalid or expired PIN.']);
        }
        
        $dest = 'Unknown';
        if ($visitor->host && $visitor->host->lots->count() > 0) {
            $lot = $visitor->host->lots->first();
            $dest = 'Block ' . $lot->block . ', Lot ' . $lot->lot_number;
        }

        return response()->json([
            'success' => true,
            'visitor_id' => substr($visitor->id, 0, 8),
            'db_id' => $visitor->id,
            'visitor_name' => $visitor->visitor_name,
            'destination' => $dest,
            'status' => $visitor->status,
        ]);
    }
    
    // For Guard marking visitor as Entered
    public function markEntered(Request $request, $id)
    {
        $visitor = Visitor::where('id', $id)->firstOrFail();
        $updateData = [
            'status' => 'Entered',
            'arrival_time' => \Carbon\Carbon::now()->setTimezone(config('app.timezone', 'Asia/Manila'))->format('h:i A')
        ];
        
        if ($request->has('plate_number') && $request->plate_number !== 'N/A') {
            $updateData['plate_number'] = $request->plate_number;
        }

        $visitor->update($updateData);
        return response()->json(['success' => true]);
    }

    // For Admin rejecting a code
    public function reject($id)
    {
        $visitor = Visitor::where('id', 'like', $id . '%')->firstOrFail();
        
        if ($visitor->status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'Already processed']);
        }

        $visitor->update([
            'status' => 'Rejected'
        ]);

        return response()->json(['success' => true]);
    }
}
