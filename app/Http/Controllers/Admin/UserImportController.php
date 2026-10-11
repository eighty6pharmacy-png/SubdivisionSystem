<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendNewUserCredentialsJob;
use App\Models\BuyerMasterList;
use App\Models\Lot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserImportController extends Controller
{
    private const ALLOWED_ROLES = ['Resident', 'Security Guard', 'Finance Officer', 'Buyer'];

    private const STAFF_ROLES = ['Security Guard', 'Finance Officer'];

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'external_id',
            'first_name',
            'last_name',
            'email',
            'contact_number',
            'role',
            'block',
            'lot',
            'buyer_master_list_id',
        ];

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, [
                '1001',
                'Juan',
                'Dela Cruz',
                'juan.delacruz@example.com',
                '09171234567',
                'Resident',
                '5',
                '12',
                '',
            ]);
            fputcsv($out, [
                '2001',
                'Maria',
                'Santos',
                'maria.santos@example.com',
                '09181234567',
                'Buyer',
                '3',
                '7',
                '',
            ]);
            fclose($out);
        }, 'users_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('csv_file')->getRealPath();
        $file = fopen($path, 'r');
        $headers = fgetcsv($file);

        if (!$headers) {
            return response()->json(['success' => false, 'message' => 'Empty CSV file']);
        }

        $headers = array_map('strtolower', array_map('trim', $headers));
        $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);

        $required = ['external_id', 'first_name', 'last_name', 'email', 'contact_number', 'role'];
        foreach ($required as $req) {
            if (!in_array($req, $headers, true)) {
                return response()->json(['success' => false, 'message' => "Missing required column: {$req}"]);
            }
        }

        $processed = 0;
        $created = 0;
        $updated = 0;
        $errors = [];
        $seenExternalIds = [];
        $seenEmails = [];

        while (($row = fgetcsv($file)) !== false) {
            $processed++;
            if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }
            $data = array_combine($headers, array_slice($row, 0, count($headers)));
            if ($data === false) {
                $errors[] = "Row {$processed}: Could not parse row.";
                continue;
            }

            $result = $this->processRow($processed, $data, $seenExternalIds, $seenEmails);
            if ($result['error']) {
                $errors[] = $result['error'];
                continue;
            }
            if ($result['created']) {
                $created++;
            }
            if ($result['updated']) {
                $updated++;
            }
        }

        fclose($file);

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ]);
    }

    /**
     * @param  array<string, string>  $data
     * @param  array<string, true>  $seenExternalIds
     * @param  array<string, true>  $seenEmails
     * @return array{error: string|null, created: bool, updated: bool}
     */
    private function processRow(int $rowNumber, array $data, array &$seenExternalIds, array &$seenEmails): array
    {
        $externalId = trim((string) ($data['external_id'] ?? ''));
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $contact = $this->normalizePhone((string) ($data['contact_number'] ?? ''));
        $role = trim((string) ($data['role'] ?? ''));
        $buyerMasterListId = trim((string) ($data['buyer_master_list_id'] ?? ''));

        if ($externalId === '' || $firstName === '' || $lastName === '' || $email === '' || $contact === '' || $role === '') {
            return ['error' => "Row {$rowNumber}: Missing required fields (external_id, first_name, last_name, email, contact_number, or role).", 'created' => false, 'updated' => false];
        }

        if (isset($seenExternalIds[$externalId])) {
            return ['error' => "Row {$rowNumber}: Duplicate external_id \"{$externalId}\" in CSV.", 'created' => false, 'updated' => false];
        }
        $seenExternalIds[$externalId] = true;

        if (isset($seenEmails[$email])) {
            return ['error' => "Row {$rowNumber}: Duplicate email \"{$email}\" in CSV.", 'created' => false, 'updated' => false];
        }
        $seenEmails[$email] = true;

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            return ['error' => "Row {$rowNumber}: Invalid role \"{$role}\". Allowed: ".implode(', ', self::ALLOWED_ROLES).'.', 'created' => false, 'updated' => false];
        }

        $block = $this->normalizeLotComponent($data['block'] ?? null);
        $lot = $this->normalizeLotComponent($data['lot'] ?? null);
        $needsLot = $role === 'Resident' || ($role === 'Buyer' && $buyerMasterListId === '');

        if ($needsLot && ($block === null || $lot === null)) {
            return ['error' => "Row {$rowNumber}: block and lot are required for {$role} when buyer_master_list_id is empty.", 'created' => false, 'updated' => false];
        }

        $existingByExternal = User::where('external_id', $externalId)->first();
        $validator = Validator::make(
            [
                'email' => $email,
                'contact_number' => $contact,
            ],
            [
                'email' => [
                    'required',
                    'email:rfc',
                    Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($existingByExternal?->id),
                    function (string $attribute, mixed $value, \Closure $fail) {
                        if (app()->environment('testing')) {
                            return;
                        }
                        $domain = substr(strrchr(strtolower((string) $value), '@') ?: '', 1);
                        if ($domain === '' || (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A'))) {
                            $fail('That email domain does not appear to accept mail.');
                        }
                    },
                ],
                'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9}|639\d{9})$/'],
            ],
            [
                'contact_number.regex' => 'Use a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
            ]
        );

        if ($validator->fails()) {
            return ['error' => "Row {$rowNumber}: ".$validator->errors()->first(), 'created' => false, 'updated' => false];
        }

        $buyer = null;
        if ($role === 'Buyer') {
            $buyerResult = $this->resolveBuyer($rowNumber, $buyerMasterListId, $block, $lot);
            if ($buyerResult['error']) {
                return ['error' => $buyerResult['error'], 'created' => false, 'updated' => false];
            }
            $buyer = $buyerResult['buyer'];
        }

        if ($role === 'Resident' || $role === 'Buyer') {
            $lotConflict = $this->lotOccupancyError($rowNumber, $block, $lot, $existingByExternal?->id);
            if ($lotConflict) {
                return ['error' => $lotConflict, 'created' => false, 'updated' => false];
            }
        }

        try {
            if ($existingByExternal) {
                $this->updateExistingUser($existingByExternal, $firstName, $lastName, $email, $contact, $role, $block, $lot, $buyer);

                return ['error' => null, 'created' => false, 'updated' => true];
            }

            if (User::where('email', $email)->exists()) {
                return ['error' => "Row {$rowNumber}: Email {$email} already exists.", 'created' => false, 'updated' => false];
            }

            if ($buyer && User::where('buyer_master_list_id', $buyer->id)->exists()) {
                return ['error' => "Row {$rowNumber}: Buyer master list record already has a linked user account.", 'created' => false, 'updated' => false];
            }

            $password = 'Alth'.strtoupper(Str::random(6)).'!';

            $user = User::create([
                'external_id' => $externalId,
                'name' => trim("{$firstName} {$lastName}"),
                'email' => $email,
                'contact_number' => $contact,
                'password' => Hash::make($password),
                'status' => 'Active',
                'buyer_master_list_id' => $buyer?->id,
            ]);

            Role::findOrCreate($role, 'web');
            $user->syncRoles([$role]);
            $this->syncUserLot($user, $role, $block, $lot, $buyer);

            SendNewUserCredentialsJob::dispatch($user, $password);

            return ['error' => null, 'created' => true, 'updated' => false];
        } catch (\Exception $e) {
            Log::error("Import error on row {$rowNumber}: ".$e->getMessage());

            return ['error' => "Row {$rowNumber}: Internal error - ".$e->getMessage(), 'created' => false, 'updated' => false];
        }
    }

    private function normalizePhone(string $raw): string
    {
        return preg_replace('/[\s\-().]/', '', $raw) ?? '';
    }

    private function normalizeLotComponent(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }

        return (string) (int) $value;
    }

    /**
     * @return array{error: string|null, buyer: BuyerMasterList|null}
     */
    private function resolveBuyer(int $rowNumber, string $buyerMasterListId, ?string $block, ?string $lot): array
    {
        if ($buyerMasterListId !== '') {
            $buyer = BuyerMasterList::find($buyerMasterListId);
            if (!$buyer) {
                return ['error' => "Row {$rowNumber}: buyer_master_list_id not found.", 'buyer' => null];
            }

            return ['error' => null, 'buyer' => $buyer];
        }

        if ($block === null || $lot === null) {
            return ['error' => "Row {$rowNumber}: Provide buyer_master_list_id or block and lot to match a buyer.", 'buyer' => null];
        }

        $buyer = BuyerMasterList::query()
            ->where('block_no', $block)
            ->where('lot_no', $lot)
            ->first();

        if (!$buyer) {
            return ['error' => "Row {$rowNumber}: No buyer master list record for block {$block} lot {$lot}.", 'buyer' => null];
        }

        return ['error' => null, 'buyer' => $buyer];
    }

    private function lotOccupancyError(int $rowNumber, ?string $block, ?string $lot, ?string $ignoreUserId): ?string
    {
        if ($block === null || $lot === null) {
            return null;
        }

        $existingLot = Lot::where('block', $block)->where('lot_number', $lot)->first();
        if (!$existingLot) {
            return null;
        }

        $query = $existingLot->users()->where(function ($q) {
            $q->where('status', '!=', 'Archived')->orWhereNull('status');
        });

        if ($ignoreUserId) {
            $query->where('users.id', '!=', $ignoreUserId);
        }

        if ($query->exists()) {
            return "Row {$rowNumber}: Block {$block} Lot {$lot} is already occupied by an active account.";
        }

        return null;
    }

    private function updateExistingUser(
        User $user,
        string $firstName,
        string $lastName,
        string $email,
        string $contact,
        string $role,
        ?string $block,
        ?string $lot,
        ?BuyerMasterList $buyer
    ): void {
        if ($buyer && $user->buyer_master_list_id && $user->buyer_master_list_id !== $buyer->id) {
            throw new \InvalidArgumentException('Cannot reassign user to a different buyer master list record.');
        }

        if ($buyer) {
            $other = User::where('buyer_master_list_id', $buyer->id)
                ->where('id', '!=', $user->id)
                ->exists();
            if ($other) {
                throw new \InvalidArgumentException('Buyer master list record already has a linked user account.');
            }
        }

        $user->name = trim("{$firstName} {$lastName}");
        $user->email = $email;
        $user->contact_number = $contact;
        if ($buyer) {
            $user->buyer_master_list_id = $buyer->id;
        }
        $user->save();

        Role::findOrCreate($role, 'web');
        $user->syncRoles([$role]);
        $this->syncUserLot($user, $role, $block, $lot, $buyer);
    }

    private function syncUserLot(User $user, string $role, ?string $block, ?string $lot, ?BuyerMasterList $buyer): void
    {
        if (in_array($role, self::STAFF_ROLES, true)) {
            return;
        }

        if ($buyer?->lot_id) {
            $user->lots()->sync([$buyer->lot_id]);
            Lot::whereKey($buyer->lot_id)->update(['status' => 'Occupied']);

            return;
        }

        if ($block !== null && $lot !== null) {
            $lotModel = Lot::firstOrCreate([
                'block' => $block,
                'lot_number' => $lot,
            ]);
            $user->lots()->sync([$lotModel->id]);
            $lotModel->update(['status' => 'Occupied']);
        }
    }
}
