<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Visitor;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class VisitorController extends Controller
{
    // For Admin / Guard viewing all visitors, or Resident viewing their own
    public function index()
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $query = Visitor::with('host.lots')->orderBy('updated_at', 'desc');
        
        if (auth()->user()->hasRole('Resident')) {
            $query->where('resident_id', auth()->id());
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
            } elseif ($vis->host_name) {
                $hostName = $vis->host_name;
            }

            return [
                'id' => $vis->id,
                'db_id' => $vis->id,
                'pin' => $vis->pin,
                'visitor' => $vis->visitor_name,
                'contact_number' => $vis->contact_number,
                'access_token' => $vis->access_token,
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
            'contact_number' => 'nullable|string|max:30',
            'purpose' => 'required|string',
            'validity' => 'required|string',
            'type' => 'required|string',
            'visitor_address' => 'nullable|string',
        ]);

        $isWalkIn = $request->type === 'Walk-in';

        $data = [
            'resident_id' => $isWalkIn ? null : auth()->id(),
            'host_name' => $isWalkIn ? ($request->input('host_name') ?: 'Main Gate Walk-in') : (auth()->user()?->name ?? 'Resident Host'),
            'visitor_name' => $request->visitor_name,
            'contact_number' => $request->contact_number,
            'access_token' => Str::random(32),
            'purpose' => $request->purpose,
            'validity' => $request->validity,
            'type' => $request->type,
            'plate_number' => $request->input('plate_number') ?: 'N/A',
            'visitor_address' => $request->visitor_address,
            'status' => $isWalkIn ? 'Entered' : 'Pending',
            'arrival_time' => $isWalkIn ? \Carbon\Carbon::now()->setTimezone(config('app.timezone', 'Asia/Manila'))->format('h:i A') : null,
        ];

        if ($isWalkIn && auth()->check()) {
            $data['guard_name'] = auth()->user()->name;
            if (auth()->user()->guardAccount) {
                $data['guard_id'] = auth()->user()->id;
            }
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

        return response()->json(['success' => true, 'id' => $visitor->id]);
    }

    // For Admin approving a code
    public function approve($id)
    {
        if (!auth()->check() || !auth()->user()->hasRole('Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Only administrators can approve visitor requests.'], 403);
        }

        $visitor = Visitor::whereKey($id)->with('host')->firstOrFail();
        
        if ($visitor->status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'Already processed']);
        }

        $pin = (string)rand(100000, 999999);
        $token = $visitor->access_token ?: Str::random(32);

        $visitor->update([
            'status' => 'Approved',
            'pin' => $pin,
            'access_token' => $token,
        ]);

        $passUrl = url('/v/' . $token);
        $smsPassUrl = preg_replace('#^https?://#', '', $passUrl);

        // Send SMS and Push Notification to Resident
        if ($visitor->host) {
            if ($visitor->host->contact_number) {
                $hostName = $visitor->host->name;
                $visName = $visitor->visitor_name;
                $message = "Althesa Subd: Visitor request for {$visName} is Approved. PIN: {$pin}. Pass & route link: {$smsPassUrl}. Please forward to your visitor.";
                try {
                    \App\Helpers\SmsHelper::sendSms($visitor->host->contact_number, $message);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Failed sending host SMS: ' . $e->getMessage());
                }
            }

            $visitor->host->notify(new \App\Notifications\WebAndPushNotification(
                'Visitor Approved',
                "Your visitor request for {$visitor->visitor_name} has been approved. PIN: {$pin}",
                'visitor'
            ));
        }

        // Send SMS directly to Visitor if contact_number was provided
        if (!empty($visitor->contact_number)) {
            $visName = $visitor->visitor_name;
            $hostName = $visitor->host ? $visitor->host->name : ($visitor->host_name ?: 'Resident Host');
            $visitorMessage = "Althesa Subd: Hello {$visName}, your visitor pass from {$hostName} is Approved! Gate PIN: {$pin}. Show this PIN to the guard upon arrival. View your pass & route map: {$smsPassUrl}";

            try {
                \App\Helpers\SmsHelper::sendSms($visitor->contact_number, $visitorMessage);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed sending visitor SMS: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'pin' => $pin,
            'pass_url' => $passUrl,
            'access_token' => $token,
        ]);
    }

    // Direct Access Route for Visitors (from SMS Link - no PIN input required)
    public function showDirectPass(string $token)
    {
        $visitor = Visitor::where('access_token', $token)->with('host.lots')->first();

        if (!$visitor) {
            return response()->view('visitor.pass', [
                'notFound' => true,
                'visitor' => null,
                'token' => $token,
                'destination' => 'Unknown',
                'endNode' => null,
                'hostName' => 'Unknown',
            ], 404);
        }

        $dest = 'Unknown';
        $endNode = null;
        if ($visitor->host && $visitor->host->lots->count() > 0) {
            $lot = $visitor->host->lots->first();
            $dest = 'Block ' . $lot->block . ', Lot ' . $lot->lot_number;
            $endNode = 'B' . $lot->block . ' L' . $lot->lot_number;
        }

        return view('visitor.pass', [
            'notFound' => false,
            'visitor' => $visitor,
            'token' => $token,
            'destination' => $dest,
            'endNode' => $endNode,
            'hostName' => $visitor->host?->name ?? ($visitor->host_name ?: 'Subdivision Resident'),
        ]);
    }

    // Polling Endpoint to check if Guard has validated the PIN
    public function checkDirectPassStatus(string $token)
    {
        $visitor = Visitor::where('access_token', $token)->with('host.lots')->first();

        if (!$visitor) {
            return response()->json(['success' => false, 'message' => 'Visitor pass not found.'], 404);
        }

        $dest = 'Unknown';
        $endNode = null;
        if ($visitor->host && $visitor->host->lots->count() > 0) {
            $lot = $visitor->host->lots->first();
            $dest = 'Block ' . $lot->block . ', Lot ' . $lot->lot_number;
            $endNode = 'B' . $lot->block . ' L' . $lot->lot_number;
        }

        return response()->json([
            'success' => true,
            'status' => $visitor->status,
            'visitor_name' => $visitor->visitor_name,
            'destination' => $dest,
            'endNode' => $endNode,
            'pin' => $visitor->pin,
            'validity' => $visitor->validity,
            'host_name' => $visitor->host?->name ?? ($visitor->host_name ?: 'Subdivision Resident'),
            'arrival_time' => $visitor->arrival_time,
        ]);
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
            $block = 'N/A';
            $lotNum = 'N/A';
            $hostName = 'Unknown';

            if ($visitor->host) {
                $hostName = $visitor->host->name;
                if ($visitor->host->lots->count() > 0) {
                    $lot = $visitor->host->lots->first();
                    $block = (string)$lot->block;
                    $lotNum = (string)$lot->lot_number;
                    $dest = 'Block ' . $block . ', Lot ' . $lotNum;
                }
            } elseif ($visitor->host_name) {
                $hostName = $visitor->host_name;
                $dest = $visitor->host_name;
            }

            return response()->json([
                'success' => true,
                'is_appointment' => false,
                'visitor_id' => $visitor->id,
                'db_id' => $visitor->id,
                'visitor_name' => $visitor->visitor_name,
                'host_name' => $hostName,
                'block' => $block,
                'lot' => $lotNum,
                'destination' => $dest,
                'status' => $visitor->status,
                'arrival_time' => $visitor->arrival_time,
                'plate_number' => $visitor->plate_number,
                'purpose' => $visitor->purpose,
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

            $existingVis = \App\Models\Visitor::where('pin', $appointment->pin)->first();

            return response()->json([
                'success' => true,
                'is_appointment' => true,
                'visitor_id' => $existingVis?->id ?? $appointment->id,
                'db_id' => $existingVis?->id ?? $appointment->id,
                'appointment_id' => $appointment->id,
                'visitor_name' => $appointment->client_name,
                'host_name' => 'Management Office',
                'block' => 'N/A',
                'lot' => 'N/A',
                'destination' => 'Management Office',
                'status' => $appointment->status === 'Completed' ? 'Entered' : 'Approved',
                'arrival_time' => $existingVis?->arrival_time,
                'plate_number' => $existingVis?->plate_number ?? 'N/A',
                'appointment_type' => $appointment->type,
                'appointment_time' => $appointment->time,
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid or expired PIN.']);
    }
    
    // For Guard marking visitor as Entered
    public function markEntered(Request $request, $id)
    {
        if (!auth()->check() || !auth()->user()->hasAnyRole(['Admin', 'Security Guard'])) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Only guards or administrators can record visitor entry.'], 403);
        }

        $visitor = Visitor::where('id', $id)->firstOrFail();
        $isFirstEntry = ($visitor->status !== 'Entered');
        $updateData = [];

        if ($isFirstEntry) {
            $updateData['status'] = 'Entered';
            $updateData['arrival_time'] = \Carbon\Carbon::now()->setTimezone(config('app.timezone', 'Asia/Manila'))->format('h:i A');
        }
        
        if ($request->filled('plate_number') && $request->plate_number !== 'N/A') {
            $updateData['plate_number'] = $request->plate_number;
        }

        if (auth()->check() && empty($visitor->guard_id)) {
            $updateData['guard_id'] = auth()->user()->id;
            $updateData['guard_name'] = auth()->user()->name;
        }

        if (!empty($updateData)) {
            $visitor->update($updateData);
        }

        // Notify Admins only on first entry
        if ($isFirstEntry) {
            $admins = User::role('Admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new \App\Notifications\WebAndPushNotification(
                    'Visitor Entered',
                    "Visitor {$visitor->visitor_name} has entered the gate.",
                    'visitor'
                ));
            }
        }

        return response()->json([
            'success' => true,
            'status' => $visitor->status,
            'arrival_time' => $visitor->arrival_time,
            'plate_number' => $visitor->plate_number,
        ]);
    }

    // For Admin rejecting a code
    public function reject($id)
    {
        if (!auth()->check() || !auth()->user()->hasRole('Admin')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Only administrators can reject visitor requests.'], 403);
        }

        $visitor = Visitor::whereKey($id)->firstOrFail();
        
        if ($visitor->status !== 'Pending') {
            return response()->json(['success' => false, 'message' => 'Already processed']);
        }

        $visitor->update([
            'status' => 'Rejected'
        ]);

        return response()->json(['success' => true]);
    }
}
