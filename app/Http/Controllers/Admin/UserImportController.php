<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Lot;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Jobs\SendNewUserCredentialsJob;
use Illuminate\Support\Facades\Log;

class UserImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $file = fopen($path, 'r');
        $headers = fgetcsv($file);

        if (!$headers) {
            return response()->json(['success' => false, 'message' => 'Empty CSV file']);
        }

        // Clean headers
        $headers = array_map('strtolower', array_map('trim', $headers));
        // Remove BOM if exists on first header
        $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);

        $required = ['first_name', 'last_name', 'email', 'role'];
        foreach ($required as $req) {
            if (!in_array($req, $headers)) {
                return response()->json(['success' => false, 'message' => "Missing required column: $req"]);
            }
        }

        $processed = 0;
        $created = 0;
        $errors = [];

        while (($row = fgetcsv($file)) !== false) {
            $processed++;
            // Pad row if missing columns
            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }
            $data = array_combine($headers, array_slice($row, 0, count($headers)));
            
            if (empty($data['email']) || empty($data['first_name']) || empty($data['last_name']) || empty($data['role'])) {
                $errors[] = "Row $processed: Missing required fields (first_name, last_name, email, or role).";
                continue;
            }

            // Skip if Resident and missing block/lot
            $role = trim($data['role']);
            $isResident = !in_array($role, ['Security Guard', 'Finance Officer']);
            if ($isResident && (empty($data['block']) || empty($data['lot']))) {
                $errors[] = "Row $processed: Missing block or lot for Resident.";
                continue;
            }

            // Strict check: Prevent multiple active residents on the same lot
            if ($isResident) {
                $existingLot = Lot::where('block', $data['block'])->where('lot_number', $data['lot'])->first();
                if ($existingLot) {
                    $hasActiveUser = $existingLot->users()->where(function($q) {
                        $q->where('status', '!=', 'Archived')->orWhereNull('status');
                    })->exists();

                    if ($hasActiveUser) {
                        $errors[] = "Row $processed: Block {$data['block']} Lot {$data['lot']} is already occupied by an active account.";
                        continue;
                    }
                }
            }

            // Check if user exists
            if (User::where('email', $data['email'])->exists()) {
                $errors[] = "Row $processed: Email " . $data['email'] . " already exists.";
                continue;
            }

            $password = 'Alth' . strtoupper(Str::random(6)) . '!';

            try {
                $user = clone User::create([
                    'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                    'email' => $data['email'],
                    'contact_number' => $data['contact_number'] ?? null,
                    'password' => Hash::make($password),
                    'status' => 'Active'
                ]);
                
                // Fetch again to ensure everything is initialized properly for relations
                $user = User::find($user->id);

                $role = $data['role'];
                if (in_array($role, ['Resident', 'Security Guard', 'Finance Officer'])) {
                    $user->assignRole($role);
                } else {
                    $user->assignRole('Resident');
                }

                // Attach Lot if Resident
                if ($user->hasRole('Resident') && !empty($data['block']) && !empty($data['lot'])) {
                    $lot = Lot::firstOrCreate([
                        'block' => $data['block'],
                        'lot_number' => $data['lot']
                    ]);
                    $user->lots()->attach($lot->id);
                    $lot->update(['status' => 'Occupied']);
                }

                // Dispatch email job
                SendNewUserCredentialsJob::dispatch($user, $password);
                $created++;

            } catch (\Exception $e) {
                Log::error("Import error on row $processed: " . $e->getMessage());
                $errors[] = "Row $processed: Internal error - " . $e->getMessage();
            }
        }

        fclose($file);

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'created' => $created,
            'errors' => $errors
        ]);
    }
}
