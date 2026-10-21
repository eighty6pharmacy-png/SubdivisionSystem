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
    private const ALLOWED_ROLES = ['Resident', 'Security Guard', 'Finance Officer'];

    private const STAFF_ROLES = ['Security Guard', 'Finance Officer'];

    /**
     * @var array<string, bool>
     */
    private array $dnsCache = [];

    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'first_name',
            'middle_name',
            'last_name',
            'email',
            'contact_number',
            'role',
            'block',
            'lot',
        ];

        return response()->streamDownload(function () use ($headers) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            fputcsv($out, [
                'Juan',
                '',
                'Dela Cruz',
                'juan.delacruz@example.com',
                '09171234567',
                'Resident',
                '5',
                '12',
            ]);
            fputcsv($out, [
                'Maria',
                '',
                'Santos',
                'maria.santos@example.com',
                '09181234567',
                'Security Guard',
                '',
                '',
            ]);
            fclose($out);
        }, 'users_import_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(Request $request)
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('csv_file')->getRealPath();

        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return response()->json(['success' => false, 'message' => 'Empty CSV file']);
        }

        // Convert Windows-1252 / ISO-8859-1 or other encodings to UTF-8
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252, ISO-8859-1, ASCII');
        }
        // Remove UTF-8 BOM if present
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

        $file = fopen('php://temp', 'r+');
        fwrite($file, $raw);
        rewind($file);

        $headers = fgetcsv($file);

        if (!$headers) {
            fclose($file);
            return response()->json(['success' => false, 'message' => 'Empty CSV file']);
        }

        $headers = array_map('strtolower', array_map('trim', $headers));
        $headers[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $headers[0]);

        $required = ['first_name', 'last_name', 'email', 'contact_number', 'role'];
        foreach ($required as $req) {
            if (!in_array($req, $headers, true)) {
                fclose($file);
                return response()->json(['success' => false, 'message' => "Missing required column: {$req}"]);
            }
        }

        $processed = 0;
        $created = 0;
        $updated = 0;
        $errors = [];
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

            $result = $this->processRow($processed, $data, $seenEmails);
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

        $safeErrors = array_map(function ($err) {
            return mb_convert_encoding((string) $err, 'UTF-8', 'UTF-8');
        }, $errors);

        return response()->json([
            'success' => true,
            'processed' => $processed,
            'created' => $created,
            'updated' => $updated,
            'errors' => $safeErrors,
        ]);
    }

    /**
     * @param  array<string, string>  $data
     * @param  array<string, true>  $seenEmails
     * @return array{error: string|null, created: bool, updated: bool}
     */
    private function processRow(int $rowNumber, array $data, array &$seenEmails): array
    {
        $firstName = trim((string) ($data['first_name'] ?? ''));
        $middleName = trim((string) ($data['middle_name'] ?? ''));
        $lastName = trim((string) ($data['last_name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $contact = $this->normalizePhone((string) ($data['contact_number'] ?? ''));
        $role = trim((string) ($data['role'] ?? ''));

        if ($firstName === '' || $lastName === '' || $email === '' || $contact === '' || $role === '') {
            return ['error' => "Row {$rowNumber}: Missing required fields (first_name, last_name, email, contact_number, or role).", 'created' => false, 'updated' => false];
        }

        if (isset($seenEmails[$email])) {
            return ['error' => "Row {$rowNumber}: Duplicate email \"{$email}\" in CSV.", 'created' => false, 'updated' => false];
        }
        $seenEmails[$email] = true;

        if (!in_array($role, self::ALLOWED_ROLES, true)) {
            return ['error' => "Row {$rowNumber}: Invalid role \"{$role}\". Allowed: ".implode(', ', self::ALLOWED_ROLES).'. Buyers are added from the buyer master list.', 'created' => false, 'updated' => false];
        }

        $block = $this->normalizeLotComponent($data['block'] ?? null);
        $lot = $this->normalizeLotComponent($data['lot'] ?? null);

        if ($role === 'Resident' && ($block === null || $lot === null)) {
            return ['error' => "Row {$rowNumber}: block and lot are required for a resident.", 'created' => false, 'updated' => false];
        }

        $accountUser = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if ($accountUser) {
            $currentRoles = $accountUser->roles()->pluck('name');
            if ($currentRoles->contains('Buyer')) {
                return ['error' => "Row {$rowNumber}: {$email} belongs to a buyer account. Add buyers from the buyer master list.", 'created' => false, 'updated' => false];
            }
            if ($currentRoles->contains('Admin')) {
                return ['error' => "Row {$rowNumber}: {$email} belongs to an admin account.", 'created' => false, 'updated' => false];
            }
            if ($accountUser->status === 'Archived') {
                return ['error' => "Row {$rowNumber}: {$email} belongs to an archived account.", 'created' => false, 'updated' => false];
            }
            $roleRefusal = $accountUser->refusalForRoleChange($role);
            if ($roleRefusal) {
                return ['error' => "Row {$rowNumber}: {$roleRefusal}", 'created' => false, 'updated' => false];
            }
        }

        $validator = Validator::make(
            [
                'email' => $email,
                'contact_number' => $contact,
            ],
            [
                'email' => [
                    'required',
                    'email:rfc',
                    Rule::unique('users', 'email')->whereNull('deleted_at')->ignore($accountUser?->id),
                    function (string $attribute, mixed $value, \Closure $fail) {
                        if (app()->environment('testing')) {
                            return;
                        }
                        $domain = substr(strrchr(strtolower((string) $value), '@') ?: '', 1);
                        if ($domain === '') {
                            $fail('That email domain does not appear to accept mail.');
                            return;
                        }
                        if (!array_key_exists($domain, $this->dnsCache)) {
                            $this->dnsCache[$domain] = checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
                        }
                        if (!$this->dnsCache[$domain]) {
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

        if ($role === 'Resident') {
            $lotConflict = $this->lotOccupancyError($rowNumber, $block, $lot, $accountUser?->id);
            if ($lotConflict) {
                return ['error' => $lotConflict, 'created' => false, 'updated' => false];
            }
        }

        try {
            if ($accountUser) {
                $passwordWasNull = $accountUser->password === null;
                $this->updateExistingUser($accountUser, $firstName, $middleName, $lastName, $email, $contact, $role, $block, $lot, $passwordWasNull);

                return ['error' => null, 'created' => false, 'updated' => true];
            }

            $password = 'Alth'.strtoupper(Str::random(6)).'!';

            $user = User::create([
                'first_name' => $firstName,
                'middle_name' => $middleName !== '' ? $middleName : null,
                'last_name' => $lastName,
                'email' => $email,
                'contact_number' => $contact,
                'password' => Hash::make($password),
                'status' => 'Active',
            ]);

            Role::findOrCreate($role, 'web');
            $user->syncRoles([$role]);
            $this->syncUserLot($user, $role, $block, $lot);

            SendNewUserCredentialsJob::dispatch($user, $password);

            return ['error' => null, 'created' => true, 'updated' => false];
        } catch (\Throwable $e) {
            $safeMsg = mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8');
            Log::error("Import error on row {$rowNumber}: ".$safeMsg);

            return ['error' => "Row {$rowNumber}: Internal error - ".$safeMsg, 'created' => false, 'updated' => false];
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

    private function lotOccupancyError(int $rowNumber, ?string $block, ?string $lot, int|string|null $ignoreUserId): ?string
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

        $buyerOnLot = BuyerMasterList::query()
            ->where('lot_id', $existingLot->id)
            ->when($ignoreUserId, fn ($q) => $q->where('user_id', '!=', $ignoreUserId))
            ->exists();
        if ($buyerOnLot) {
            return "Row {$rowNumber}: Block {$block} Lot {$lot} is already occupied by an active account.";
        }

        return null;
    }

    private function updateExistingUser(
        User $user,
        string $firstName,
        string $middleName,
        string $lastName,
        string $email,
        string $contact,
        string $role,
        ?string $block,
        ?string $lot,
        bool $setPassword = false
    ): void {
        $user->first_name = $firstName;
        $user->middle_name = $middleName !== '' ? $middleName : null;
        $user->last_name = $lastName;
        $user->email = $email;
        $user->contact_number = $contact;

        $plainPassword = null;
        if ($setPassword) {
            $plainPassword = 'Alth'.strtoupper(Str::random(6)).'!';
            $user->password = Hash::make($plainPassword);
        }
        $user->save();

        Role::findOrCreate($role, 'web');
        $user->syncRoles([$role]);
        $this->syncUserLot($user, $role, $block, $lot);

        if ($plainPassword) {
            SendNewUserCredentialsJob::dispatch($user, $plainPassword);
        }
    }

    private function syncUserLot(User $user, string $role, ?string $block, ?string $lot): void
    {
        if (in_array($role, self::STAFF_ROLES, true)) {
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
