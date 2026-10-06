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
        $query = Visitor::with('host.lots')->orderBy('updated_at', 'desc');
        
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
                'guard_name' => $vis->guard_name,
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

        $data = [
            'host_id' => $isWalkIn ? null : auth()->id(),
            'visitor_name' => $request->visitor_name,
            'purpose' => $request->purpose,
            'validity' => $request->validity,
            'type' => $request->type,
            'plate_number' => $request->plate_number,
            'visitor_address' => $request->visitor_address,
            'status' => $isWalkIn ? 'Entered' : 'Pending',
            'arrival_time' => $isWalkIn ? \Carbon\Carbon::now()->setTimezone(config('app.timezone', 'Asia/Manila'))->format('h:i A') : null,
        ];

        if ($isWalkIn && auth()->check()) {
            $data['guard_id'] = auth()->user()->id;
            $data['guard_name'] = auth()->user()->name;
        }

        $visitor = Visitor::create($data);

        // Notify Admins if it is a pending request (from a resident)
        if (!$isWalkIn) {
            $admins = User::role('Admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\WebAndPushNotification(
                    'New Visitor Request',
                    'A new visitor request has been submitted by ' . (auth()->user()->name ?? 'a resident') . ' for ' . $visitor->visitor_name,
                    'visitor'
                ));
            }
        }

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

        // Send SMS and Push Notification to Resident
        if ($visitor->host) {
            if ($visitor->host->contact_number) {
                $hostName = $visitor->host->name;
                $visName = $visitor->visitor_name;
                $message = "Althesa Subd: Visitor request for {$visName} is Approved. PIN: {$pin}. Please share with them.";
                \App\Helpers\SmsHelper::sendSms($visitor->host->contact_number, $message);
            }

            $visitor->host->notify(new \App\Notifications\WebAndPushNotification(
                'Visitor Approved',
                "Your visitor request for {$visitor->visitor_name} has been approved. PIN: {$pin}",
                'visitor'
            ));
        }

        return response()->json(['success' => true]);
    }
    
    // For Guard / Routing page validating PIN
    public function validatePin(Request $request)
    {
        $request->validate(['pin' => 'required|string']);
        
        $source = $request->input('source', 'visitor');
        $today = now()->setTimezone(config('app.timezone', 'Asia/Manila'))->startOfDay();

        $visitor = Visitor::where('pin', $request->pin)
            ->whereIn('status', ['Approved', 'Entered']) // Can view route even if already entered
            ->with('host.lots')
            ->first();

        if ($visitor) {
            if ($source === 'visitor' && $visitor->status !== 'Entered') {
                return response()->json(['success' => false, 'message' => 'PIN not yet validated by the guard.']);
            }

            try {
                if ($visitor->validity !== 'Today') {
                    $validDate = \Carbon\Carbon::parse($visitor->validity)->startOfDay();
                    if (!$validDate->equalTo($today)) {
                        if ($today->isBefore($validDate)) {
                            return response()->json(['success' => false, 'message' => 'This PIN is only valid on ' . $visitor->validity . '.']);
                        } else {
                            return response()->json(['success' => false, 'message' => 'This PIN has expired (was valid only on ' . $visitor->validity . ').']);
                        }
                    }
                }
            } catch (\Exception $e) {
                // Ignore parsing errors, assume valid if unparseable
            }
            
            $dest = 'Unknown';
            if ($visitor->host && $visitor->host->lots->count() > 0) {
                $lot = $visitor->host->lots->first();
                $dest = 'Block ' . $lot->block . ', Lot ' . $lot->lot_number;
            }

            return response()->json([
                'success' => true,
                'is_appointment' => false,
                'visitor_id' => substr($visitor->id, 0, 8),
                'db_id' => $visitor->id,
                'visitor_name' => $visitor->visitor_name,
                'destination' => $dest,
                'status' => $visitor->status,
            ]);
        }

        $appointment = \App\Models\Appointment::where('pin', $request->pin)
            ->whereIn('status', ['Scheduled', 'Completed'])
            ->first();

        if ($appointment) {
            if ($source === 'visitor' && $appointment->status !== 'Completed') { // For appointments, 'Completed' means they have entered
                return response()->json(['success' => false, 'message' => 'PIN not yet validated by the guard.']);
            }

            try {
                $appointmentDate = \Carbon\Carbon::parse($appointment->date)->startOfDay();
                if (!$appointmentDate->equalTo($today)) {
                    if ($today->isBefore($appointmentDate)) {
                        return response()->json(['success' => false, 'message' => 'This PIN is only valid on ' . $appointmentDate->format('M d, Y') . '.']);
                    } else {
                        return response()->json(['success' => false, 'message' => 'This PIN has expired (was valid only on ' . $appointmentDate->format('M d, Y') . ').']);
                    }
                }
            } catch (\Exception $e) {
                // Ignore parsing errors, assume valid if unparseable
            }

            return response()->json([
                'success' => true,
                'is_appointment' => true,
                'visitor_id' => substr($appointment->id, 0, 8),
                'db_id' => $appointment->id,
                'visitor_name' => $appointment->client_name,
                'destination' => 'Management Office',
                'status' => $appointment->status === 'Completed' ? 'Entered' : 'Approved',
                'appointment_type' => $appointment->type,
                'appointment_time' => $appointment->time,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid or expired PIN.']);
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

        if (auth()->check()) {
            $updateData['guard_id'] = auth()->user()->id;
            $updateData['guard_name'] = auth()->user()->name;
        }

        $visitor->update($updateData);

        // Notify Admins that visitor entered
        $admins = User::role('Admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\WebAndPushNotification(
                'Visitor Entered',
                "Visitor {$visitor->visitor_name} has entered the gate.",
                'visitor'
            ));
        }

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
