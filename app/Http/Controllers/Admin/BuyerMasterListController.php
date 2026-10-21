<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LotStatus;
use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Models\BuyerMasterList;
use App\Models\Lot;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BuyerMasterListController extends Controller
{
    public function index(Request $request)
    {
        $query = BuyerMasterList::query();

        if ($request->status === 'cancelled') {
            $query->onlyTrashed()->with(['user', 'lot', 'reservations.lot' => fn ($q) => $q->withTrashed(), 'financingRecord']);
        } else {
            $query->with(['user', 'lot', 'reservations.lot', 'financingRecord']);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('block')) {
            $query->where(function ($q) use ($request) {
                $q->whereHas('reservations.lot', fn ($inner) => $inner->where('block', $request->block))
                    ->orWhereHas('lot', fn ($inner) => $inner->where('block', $request->block));
            });
        }

        $buyers = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();
        $totalBuyers = BuyerMasterList::count();
        $cancelledBuyersCount = BuyerMasterList::onlyTrashed()->count();
        $availableLots = Lot::where('status', 'Available')->orderBy('block')->orderBy('lot_number')->get();
        return view('admin.buyer_master_list.index', compact('buyers', 'availableLots', 'totalBuyers', 'cancelledBuyersCount'));
    }

    public function store(Request $request)
    {
        $this->prepareBuyerContactEmail($request);
        $validated = $request->validate($this->buyerFormRules(), $this->buyerFormMessages());

        $existingBuyer = BuyerMasterList::whereHas('user', function ($q) use ($validated) {
            $q->where('first_name', $validated['first_name'])
                ->where('last_name', $validated['last_name']);
        })->first();

        if ($existingBuyer) {
            return response()->json([
                'success' => false,
                'message' => 'A buyer with the same first and last name already exists in the Master List.'
            ], 409);
        }

        if (User::where('email', $validated['email'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'That email address is already used by an account.',
            ], 422);
        }

        $lotId = null;
        if (!empty($validated['block_no']) && !empty($validated['lot_no'])) {
            $lotId = $this->matchingLotId($validated['block_no'], $validated['lot_no']);
            if ($message = $this->lotAssignmentConflictMessage($lotId, null)) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
        }

        try {
            $buyer = DB::transaction(function () use ($validated, $lotId) {
                if ($lotId) {
                    $validated['lot_id'] = $lotId;
                }

                return BuyerMasterList::create($validated);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'That email address is already used by a buyer account.',
            ], 422);
        }

        $buyer->unsetRelation('lot');

        return response()->json(['success' => true, 'message' => 'Buyer added successfully to the Master List.']);
    }

    public function update(Request $request, $id)
    {
        $buyer = BuyerMasterList::findOrFail($id);

        $this->prepareBuyerContactEmail($request);
        $validated = $request->validate($this->buyerFormRules(), $this->buyerFormMessages());

        if (!empty($validated['block_no']) && !empty($validated['lot_no'])) {
            $lotId = $this->matchingLotId($validated['block_no'], $validated['lot_no']);
            $lotChanged = ($validated['block_no'] ?? null) != $buyer->block_no || ($validated['lot_no'] ?? null) != $buyer->lot_no;
            if ($lotId && $lotChanged) {
                if ($message = $this->lotAssignmentConflictMessage($lotId, (int) $buyer->id)) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }
                $validated['lot_id'] = $lotId;
            }
        }

        $buyer->update($validated);

        return response()->json(['success' => true, 'message' => 'Buyer record updated securely.']);
    }

    private function matchingLotId(?string $block, ?string $lotNumber): ?string
    {
        if (blank($block) || blank($lotNumber)) {
            return null;
        }

        $existing = Lot::where('block', $block)->where('lot_number', $lotNumber)->first();
        $lot = $existing ?? Lot::create([
            'block' => $block,
            'lot_number' => $lotNumber,
            'status' => LotStatus::VacantLot->value,
            'provider_managed' => false,
        ]);


        return (string) $lot->id;
    }

    private function lotAssignmentConflictMessage(?string $lotId, ?int $exceptBuyerId): ?string
    {
        if ($lotId === null || $lotId === '') {
            return null;
        }

        $otherBuyer = BuyerMasterList::where('lot_id', $lotId)
            ->when($exceptBuyerId, fn ($q) => $q->where('id', '!=', $exceptBuyerId))
            ->exists();
        if ($otherBuyer) {
            return 'That block and lot is already assigned to another buyer on the Master List. Choose a different lot or archive the other buyer first.';
        }

        $reservationBlocks = Reservation::where('lot_id', $lotId)
            ->whereIn('status', ReservationStatus::activeValues())
            ->when($exceptBuyerId, fn ($q) => $q->where(fn ($inner) => $inner
                ->whereNull('buyer_id')
                ->orWhere('buyer_id', '!=', $exceptBuyerId)))
            ->exists();
        if ($reservationBlocks) {
            return 'That lot has an active reservation. Cancel or convert it before assigning another buyer.';
        }

        return null;
    }

    private function cancelReservationsOnArchive(BuyerMasterList $buyer): void
    {
        $preserveStatuses = $buyer->financingRecord !== null;

        foreach ($buyer->reservations as $reservation) {
            if ($preserveStatuses || $reservation->downpayments->isNotEmpty()) {
                continue;
            }

            if (! in_array($reservation->status, ReservationStatus::activeValues(), true)) {
                continue;
            }

            $reservation->update(['status' => ReservationStatus::Cancelled->value]);
            $this->releaseLotIfIdle($reservation->lot_id);
        }
    }

    private function releaseLotIfIdle(?string $lotId): void
    {
        if ($lotId === null || $lotId === '') {
            return;
        }

        $stillHeld = BuyerMasterList::where('lot_id', $lotId)->exists()
            || Reservation::where('lot_id', $lotId)
                ->whereIn('status', ReservationStatus::activeValues())
                ->exists();

        if ($stillHeld) {
            return;
        }

        Lot::whereKey($lotId)->update(['status' => LotStatus::Available->value]);
    }

    private function prepareBuyerContactEmail(Request $request): void
    {
        $request->merge([
            'contact_number' => preg_replace('/[\s\-().]/', '', (string) $request->input('contact_number')),
            'email' => trim((string) $request->input('email')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buyerFormRules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'email:rfc,dns', 'max:255', function (string $attribute, mixed $value, \Closure $fail) {
                $domain = substr(strrchr(strtolower((string) $value), '@') ?: '', 1);
                if ($domain === '' || (! checkdnsrr($domain, 'MX') && ! checkdnsrr($domain, 'A'))) {
                    $fail('That email domain does not appear to accept mail. Please use a real, active email address.');
                }
            }],
            'civil_status' => 'required|string|max:50',
            'spouse_name' => 'required_if:civil_status,Married|nullable|string|max:255',
            'present_address' => 'required|string|max:1000',
            'postal_address' => 'required|string|max:1000',
            'contact_number' => ['required', 'regex:/^(09\d{9}|\+639\d{9}|639\d{9})$/'],
            'id_type' => ['required', 'string', Rule::in(BuyerMasterList::ID_TYPES)],
            'proof_of_id' => 'required|string|max:255',
            'pagibig_number' => 'nullable|string|max:255',

            'block_no' => 'nullable|string|max:50',
            'lot_no' => 'nullable|string|max:50',
            'tct_no' => 'nullable|string|max:255',
            'pid' => 'nullable|string|max:255',
            'tax_dec_no' => 'nullable|string|max:255',
            'lot_area' => 'nullable|numeric|min:0',
            'floor_area' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buyerFormMessages(): array
    {
        return [
            'first_name.required' => 'The First Name is mandatory for the master list.',
            'last_name.required' => 'The Last Name is mandatory for the master list.',
            'email.required' => 'Email Address is required.',
            'email.email' => 'Please enter a real email address on a domain that can receive mail (Gmail, Outlook, company mail, etc.).',
            'contact_number.required' => 'Contact / Mobile Number is required.',
            'contact_number.regex' => 'Use a Philippine mobile number: 09XXXXXXXXX or +639XXXXXXXXX.',
            'id_type.required' => 'Select the type of ID.',
            'id_type.in' => 'Select a valid ID type.',
            'proof_of_id.required' => 'The ID number is required.',
        ];
    }

    private function proofOfIdForDocument(BuyerMasterList $buyer): string
    {
        return strtoupper(trim(implode(' ', array_filter([
            $buyer->id_type,
            $buyer->proof_of_id,
        ]))));
    }

    public function destroy($id)
    {
        $buyer = BuyerMasterList::with(['reservations.downpayments', 'financingRecord'])->findOrFail($id);

        if ($buyer->trashed()) {
            return response()->json(['success' => false, 'message' => 'Buyer is already archived.']);
        }

        DB::transaction(function () use ($buyer) {
            $this->cancelReservationsOnArchive($buyer);

            $buyer->user?->delete();
            $buyer->delete();
        });

        return response()->json(['success' => true, 'message' => 'Buyer archived. Reservation and payment records were kept on file.']);
    }

    public function restore($id)
    {
        $buyer = BuyerMasterList::onlyTrashed()->findOrFail($id);

        $duplicate = BuyerMasterList::where('user_id', '!=', $buyer->user_id)
            ->whereHas('user', function ($q) use ($buyer) {
                $q->where('first_name', $buyer->first_name)
                    ->where('last_name', $buyer->last_name);
            })
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot restore: an active buyer with the same first and last name already exists. Merge or rename one record first.',
            ], 409);
        }

        $notes = DB::transaction(function () use ($buyer) {
            $buyer->restore();
            $notes = [];

            $trashedUser = User::onlyTrashed()->where('id', $buyer->user_id)->first();
            if ($trashedUser) {
                $emailTaken = User::where('email', $trashedUser->email)->where('id', '!=', $trashedUser->id)->exists();
                if ($emailTaken) {
                    $notes[] = 'Login was not restored because that email is already in use. Generate a new account after updating the email.';
                } else {
                    $trashedUser->restore();
                    if ($trashedUser->status === 'Archived') {
                        $trashedUser->update(['status' => 'Active']);
                    }
                    $notes[] = 'Login account restored.';
                }
            }

            $cancelledReservations = Reservation::where('buyer_id', $buyer->id)
                ->where('status', ReservationStatus::Cancelled->value)
                ->orderByDesc('created_at')
                ->get();

            $restoredReservations = 0;
            $lotBlocked = false;
            foreach ($cancelledReservations as $reservation) {
                $lotHeld = Reservation::where('lot_id', $reservation->lot_id)
                    ->whereIn('status', ReservationStatus::activeValues())
                    ->exists();
                if ($lotHeld || $this->lotAssignmentConflictMessage((string) $reservation->lot_id, (int) $buyer->id)) {
                    $lotBlocked = true;
                    continue;
                }

                $updates = ['status' => ReservationStatus::Reserved->value];
                $deadline = $reservation->deadline_date;
                if ($deadline && \Carbon\Carbon::parse($deadline)->startOfDay()->lt(now()->startOfDay())) {
                    $updates['deadline_date'] = now()->addDays(30)->toDateString();
                }
                $reservation->update($updates);
                if ($reservation->lot) {
                    $reservation->lot->update(['status' => LotStatus::Reserved->value]);
                }
                $restoredReservations++;
            }

            if ($lotBlocked) {
                $notes[] = 'Reservation was not restored because the lot is already reserved or occupied. Edit the buyer to assign a new lot.';
            } elseif ($restoredReservations > 0) {
                $notes[] = 'Reservation restored. A lapsed deadline was extended by 30 days.';
            }

            return $notes;
        });

        $message = 'Buyer restored.';
        if ($notes !== []) {
            $message .= ' '.implode(' ', $notes);
        }

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function generateAccount($id)
    {
        $buyer = BuyerMasterList::findOrFail($id);
        
        // Ensure role exists
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Buyer']);
        
        $email = request()->input('account_email');
        if (empty($email)) {
            $email = $buyer->email;
        }
        if (empty($email)) {
            $email = strtolower($buyer->first_name . '.' . $buyer->last_name . '@buyer.althesa.com');
            $email = str_replace(' ', '', $email);
        }
        
        $password = \Illuminate\Support\Str::random(8); // Random secure password
        
        $user = $buyer->user;
        if (! $user) {
            return back()->with('error', 'This buyer has no user account to activate.');
        }
        if ($user->password) {
            return back()->with('error', 'This buyer already has an account associated with email: ' . $user->email);
        }

        $currentEmail = $user->email;
        if (empty($email) || str_ends_with((string) $currentEmail, '@buyers.invalid')) {
            if (empty(request()->input('account_email'))) {
                $email = strtolower($buyer->first_name.'.'.$buyer->last_name.'@buyer.althesa.com');
                $email = str_replace(' ', '', $email);
            }
        }

        if (\App\Models\User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
            return back()->with('error', 'Another account already uses the email: ' . $email);
        }

        $user->email = $email;
        $user->password = bcrypt($password);
        $user->contact_number = $user->contact_number ?: $buyer->contact_number;
        $user->status = 'Active';
        $user->joined_at = $user->joined_at ?: now();
        $user->save();

        if (!$user->hasRole('Buyer')) {
            $user->assignRole('Buyer');
        }

        // Send email if it's a real email address (not auto-generated @buyer.althesa.com)
        if (!str_contains($email, '@buyer.althesa.com') && !str_contains($email, '@buyers.invalid')) {
            try {
                \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($email, $buyer, $password) {
                    $message->to($email)
                        ->subject('Your Althesa Subdivision Buyer Account')
                        ->html("<h2>Welcome, {$buyer->first_name}!</h2>
                                <p>Your buyer account has been successfully created. Here are your login credentials:</p>
                                <p><strong>Email:</strong> {$email}<br>
                                <strong>Password:</strong> {$password}</p>
                                <p>Please login to the portal and change your password as soon as possible.</p>");
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send email to new buyer: ' . $e->getMessage());
            }
        }
        
        return back()->with('success', 'Buyer Account Generated! Email: ' . $email . ' | Password: ' . $password);
    }

    public function exportMsvs(Request $request, $id)
    {
        $buyer = BuyerMasterList::findOrFail($id);


        if ($request->query('format') === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.buyer_master_list.exports.msvs', compact('buyer'));
            return $pdf->download('MSVS_' . $buyer->last_name . '_' . $buyer->first_name . '.pdf');
        }

        $templatePath = storage_path('app/templates/msvs_template.docx');

        if (!file_exists($templatePath)) {
            return response()->json(['success' => false, 'message' => 'MSVS template file not found. Please upload msvs_template.docx'], 404);
        }

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

        $middle_initial = !empty($buyer->middle_name) ? strtoupper(substr(trim($buyer->middle_name), 0, 1)) . '.' : '';
        $name_parts = array_filter([
            ucwords(strtolower($buyer->first_name ?? '')),
            $middle_initial,
            ucwords(strtolower($buyer->last_name ?? ''))
        ]);
        $full_name_with_initial = implode(' ', $name_parts);
        $templateProcessor->setValue('name', $full_name_with_initial);

        $templateProcessor->setValue('first_name', strtoupper($buyer->first_name));
        $templateProcessor->setValue('last_name', strtoupper($buyer->last_name));
        $templateProcessor->setValue('middle_name', strtoupper($buyer->middle_name ?? ''));
        $templateProcessor->setValue('pagibig_no', strtoupper($buyer->pagibig_number ?? ''));
        $templateProcessor->setValue('contact_no', $buyer->contact_number ?? '');
        $templateProcessor->setValue('civil_status', strtoupper($buyer->civil_status ?? ''));

        $fileName = 'MSVS_' . $buyer->last_name . '_' . $buyer->first_name . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'PHPWord');
        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function exportBvs(Request $request, $id)
    {
        $buyer = BuyerMasterList::findOrFail($id);

        if ($request->query('format') === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.buyer_master_list.exports.bvs', compact('buyer'));
            return $pdf->download('Borrowers_Validation_' . $buyer->last_name . '_' . $buyer->first_name . '.pdf');
        }

        $templatePath = storage_path('app/templates/borrowers_validation.docx');

        if (!file_exists($templatePath)) {
            return response()->json(['success' => false, 'message' => 'BVS template file not found. Please upload borrowers_validation.docx'], 404);
        }

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

        $middle_initial = !empty($buyer->middle_name) ? strtoupper(substr(trim($buyer->middle_name), 0, 1)) . '.' : '';
        $name_parts = array_filter([
            ucwords(strtolower($buyer->first_name ?? '')),
            $middle_initial,
            ucwords(strtolower($buyer->last_name ?? ''))
        ]);
        $full_name_with_initial = implode(' ', $name_parts);
        $templateProcessor->setValue('name', $full_name_with_initial);

        $templateProcessor->setValue('first_name', strtoupper($buyer->first_name ?? ''));
        $templateProcessor->setValue('last_name', strtoupper($buyer->last_name ?? ''));
        $templateProcessor->setValue('middle_name', strtoupper($buyer->middle_name ?? ''));
        
        $templateProcessor->setValue('present_address', strtoupper($buyer->present_address ?? ''));
        $templateProcessor->setValue('pagibig_number', strtoupper($buyer->pagibig_number ?? ''));
        $templateProcessor->setValue('address', strtoupper($buyer->address ?? '')); // keeping for backwards compatibility if needed
        $templateProcessor->setValue('contact_no', $buyer->contact_number ?? '');
        $templateProcessor->setValue('email', $buyer->email ?? '');

        $fileName = 'Borrowers_Validation_' . $buyer->last_name . '_' . $buyer->first_name . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'PHPWord');
        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function exportHousingLoan(Request $request, $id)
    {
        $buyer = BuyerMasterList::with('financingRecord')->findOrFail($id);
        $finance = $buyer->financingRecord;

        if ($request->query('format') === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.buyer_master_list.exports.housing_loan', compact('buyer', 'finance'));
            return $pdf->download('Housing_Loan_' . $buyer->last_name . '_' . $buyer->first_name . '.pdf');
        }

        $templatePath = storage_path('app/templates/housing_loan_application.docx');

        if (!file_exists($templatePath)) {
            return response()->json(['success' => false, 'message' => 'Housing Loan template file not found. Please upload housing_loan_application.docx'], 404);
        }

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

        $middle_initial = !empty($buyer->middle_name) ? strtoupper(substr(trim($buyer->middle_name), 0, 1)) . '.' : '';
        $name_parts = array_filter([
            ucwords(strtolower($buyer->first_name ?? '')),
            $middle_initial,
            ucwords(strtolower($buyer->last_name ?? ''))
        ]);
        $full_name_with_initial = implode(' ', $name_parts);
        $templateProcessor->setValue('name', $full_name_with_initial);

        // Map all personal fields (with fallbacks to blank)
        $templateProcessor->setValue('first_name', strtoupper($buyer->first_name ?? ''));
        $templateProcessor->setValue('last_name', strtoupper($buyer->last_name ?? ''));
        $templateProcessor->setValue('middle_name', strtoupper($buyer->middle_name ?? ''));
        $templateProcessor->setValue('middle_initial', $middle_initial);
        $templateProcessor->setValue('civil_status', strtoupper($buyer->civil_status ?? ''));
        $templateProcessor->setValue('spouse_name', strtoupper($buyer->spouse_name ?? ''));
        $templateProcessor->setValue('present_address', strtoupper($buyer->present_address ?? ''));
        $templateProcessor->setValue('postal_address', strtoupper($buyer->postal_address ?? ''));
        $templateProcessor->setValue('contact_number', $buyer->contact_number ?? '');
        $templateProcessor->setValue('contact_no', $buyer->contact_number ?? ''); // alias for old MSVS compatibility
        $templateProcessor->setValue('pagibig_number', strtoupper($buyer->pagibig_number ?? ''));
        $templateProcessor->setValue('pagibig_no', strtoupper($buyer->pagibig_number ?? '')); // alias for old MSVS compatibility
        $templateProcessor->setValue('id_type', strtoupper($buyer->id_type ?? ''));
        $templateProcessor->setValue('proof_of_id', $this->proofOfIdForDocument($buyer));
        $templateProcessor->setValue('financing_method', strtoupper($finance?->financing_method ?? ''));
        
        // Map all property fields
        $templateProcessor->setValue('block_no', strtoupper($buyer->block_no ?? ''));
        $templateProcessor->setValue('lot_no', strtoupper($buyer->lot_no ?? ''));
        $templateProcessor->setValue('tct_no', strtoupper($buyer->tct_no ?? ''));
        $templateProcessor->setValue('pid', strtoupper($buyer->pid ?? ''));
        $templateProcessor->setValue('tax_dec_no', strtoupper($buyer->tax_dec_no ?? ''));
        $templateProcessor->setValue('lot_area', $buyer->lot_area ?? '');
        $templateProcessor->setValue('floor_area', $buyer->floor_area ?? '');
        $templateProcessor->setValue('description', strtoupper($buyer->description ?? ''));
        
        // Financial placeholders come from the buyer's financing record.
        $templateProcessor->setValue('contract_amount', $finance?->contract_price ?? '');
        $templateProcessor->setValue('equity', $finance?->paid_by_vendee_equity ?? '');
        $templateProcessor->setValue('loan_base', $finance?->loan_base ?? '');
        $templateProcessor->setValue('loan_term', $finance?->loan_term ?? '');
        $templateProcessor->setValue('monthly_amortization', $finance?->monthly_amortization ?? '');
        $templateProcessor->setValue('improvement_amount', $finance?->improvements_fee ?? '');
        $templateProcessor->setValue('mri_sri', $finance?->sri_mri ?? '');
        $templateProcessor->setValue('insurance', $finance?->pag_ibig_non_life ?? '');
        $templateProcessor->setValue('mri_ds_1time', $finance?->interim_mri ?? '');
        $templateProcessor->setValue('nonlife_1time', $finance?->pag_ibig_non_life ?? '');
        $templateProcessor->setValue('retention', $finance?->retention_fee ?? '');
        $templateProcessor->setValue('total_deductions', $finance?->total_amt_due ?? '');

        $fileName = 'Housing_Loan_' . $buyer->last_name . '_' . $buyer->first_name . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'PHPWord');
        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    public function exportBuyerConformity(Request $request, $id)
    {
        $buyer = BuyerMasterList::findOrFail($id);

        if ($request->query('format') === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.buyer_master_list.exports.buyer_conformity', compact('buyer'));
            return $pdf->download('Buyer_Conformity_' . $buyer->last_name . '_' . $buyer->first_name . '.pdf');
        }

        $templatePath = storage_path('app/templates/buyer_conformity.docx');

        if (!file_exists($templatePath)) {
            return response()->json(['success' => false, 'message' => 'Buyer Conformity template file not found. Please upload buyer_conformity.docx'], 404);
        }

        $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($templatePath);

        $middle_initial = !empty($buyer->middle_name) ? strtoupper(substr(trim($buyer->middle_name), 0, 1)) . '.' : '';
        $name_parts = array_filter([
            ucwords(strtolower($buyer->first_name ?? '')),
            $middle_initial,
            ucwords(strtolower($buyer->last_name ?? ''))
        ]);
        $full_name_with_initial = implode(' ', $name_parts);
        $templateProcessor->setValue('name', $full_name_with_initial);

        // Required fields with standard casing
        $templateProcessor->setValue('first_name', ucwords(strtolower($buyer->first_name ?? '')));
        $templateProcessor->setValue('middle_name', ucwords(strtolower($buyer->middle_name ?? '')));
        $templateProcessor->setValue('last_name', ucwords(strtolower($buyer->last_name ?? '')));
        $templateProcessor->setValue('spouse_name', ucwords(strtolower($buyer->spouse_name ?? '')));
        
        // Required fields with uppercase
        $templateProcessor->setValue('FIRST_NAME', strtoupper($buyer->first_name ?? ''));
        $templateProcessor->setValue('MIDDLE_NAME', strtoupper($buyer->middle_name ?? ''));
        $templateProcessor->setValue('LAST_NAME', strtoupper($buyer->last_name ?? ''));
        
        // Property details
        $templateProcessor->setValue('block_no', strtoupper($buyer->block_no ?? ''));
        $templateProcessor->setValue('lot_no', strtoupper($buyer->lot_no ?? ''));
        $templateProcessor->setValue('tct_no', strtoupper($buyer->tct_no ?? ''));
        $templateProcessor->setValue('id_type', strtoupper($buyer->id_type ?? ''));
        $templateProcessor->setValue('proof_of_id', $this->proofOfIdForDocument($buyer));

        $fileName = 'Buyer_Conformity_' . $buyer->last_name . '_' . $buyer->first_name . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'PHPWord');
        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
