<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BuyerMasterList;
use App\Models\Lot;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BuyerMasterListController extends Controller
{
    public function index(Request $request)
    {
        $query = BuyerMasterList::with('reservations');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('block')) {
            $query->where('block_no', $request->block);
        }

        $buyers = $query->orderBy('created_at', 'desc')->get();
        $availableLots = Lot::where('status', 'Available')->orderBy('block')->orderBy('lot_number')->get();
        return view('admin.buyer_master_list.index', compact('buyers', 'availableLots'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Personal Info Validation
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'civil_status' => 'nullable|string|max:50',
            'spouse_name' => 'nullable|string|max:255',
            'present_address' => 'nullable|string|max:1000',
            'postal_address' => 'nullable|string|max:1000',
            'contact_number' => 'nullable|string|max:50',
            'proof_of_id' => 'nullable|string|max:255',
            'pagibig_number' => 'nullable|string|max:255',
            'financing_method' => 'nullable|string|max:50',

            // Property Info Validation
            'block_no' => 'nullable|string|max:50',
            'lot_no' => 'nullable|string|max:50',
            'tct_no' => 'nullable|string|max:255',
            'pid' => 'nullable|string|max:255',
            'tax_dec_no' => 'nullable|string|max:255',
            'lot_area' => 'nullable|numeric|min:0',
            'floor_area' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',

            // Financial Info Validation
            'contract_amount' => 'nullable|numeric|min:0',
            'improvement_amount' => 'nullable|numeric|min:0',
            'equity' => 'nullable|numeric|min:0',
            'loan_base' => 'nullable|numeric|min:0',
            'loan_term' => 'nullable|integer|min:0|max:100', // Years
            'monthly_amortization' => 'nullable|numeric|min:0',
            'mri_sri' => 'nullable|numeric|min:0',
            'insurance' => 'nullable|numeric|min:0',
            'mri_ds_1time' => 'nullable|numeric|min:0',
            'nonlife_1time' => 'nullable|numeric|min:0',
            'retention' => 'nullable|numeric|min:0',
            'total_deductions' => 'nullable|numeric|min:0',
        ], [
            'first_name.required' => 'The First Name is mandatory for the master list.',
            'last_name.required' => 'The Last Name is mandatory for the master list.',
            'loan_term.max' => 'Loan term cannot exceed 100 years.'
        ]);

        BuyerMasterList::create($validated);

        return response()->json(['success' => true, 'message' => 'Buyer added successfully to the Master List.']);
    }

    public function update(Request $request, $id)
    {
        $buyer = BuyerMasterList::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'civil_status' => 'nullable|string|max:50',
            'spouse_name' => 'nullable|string|max:255',
            'present_address' => 'nullable|string|max:1000',
            'postal_address' => 'nullable|string|max:1000',
            'contact_number' => 'nullable|string|max:50',
            'proof_of_id' => 'nullable|string|max:255',
            'pagibig_number' => 'nullable|string|max:255',
            'financing_method' => 'nullable|string|max:50',

            'block_no' => 'nullable|string|max:50',
            'lot_no' => 'nullable|string|max:50',
            'tct_no' => 'nullable|string|max:255',
            'pid' => 'nullable|string|max:255',
            'tax_dec_no' => 'nullable|string|max:255',
            'lot_area' => 'nullable|numeric|min:0',
            'floor_area' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',

            'contract_amount' => 'nullable|numeric|min:0',
            'improvement_amount' => 'nullable|numeric|min:0',
            'equity' => 'nullable|numeric|min:0',
            'loan_base' => 'nullable|numeric|min:0',
            'loan_term' => 'nullable|integer|min:0|max:100',
            'monthly_amortization' => 'nullable|numeric|min:0',
            'mri_sri' => 'nullable|numeric|min:0',
            'insurance' => 'nullable|numeric|min:0',
            'mri_ds_1time' => 'nullable|numeric|min:0',
            'nonlife_1time' => 'nullable|numeric|min:0',
            'retention' => 'nullable|numeric|min:0',
            'total_deductions' => 'nullable|numeric|min:0',
        ]);

        $buyer->update($validated);

        return response()->json(['success' => true, 'message' => 'Buyer record updated securely.']);
    }

    public function destroy($id)
    {
        $buyer = BuyerMasterList::findOrFail($id);

        // Prevent deletion if they have active reservations linked
        if ($buyer->reservations()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete buyer. They have active reservations or downpayments linked to their account.'
            ], 403);
        }

        $buyer->delete();
        return response()->json(['success' => true, 'message' => 'Buyer record removed safely.']);
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
        $buyer = BuyerMasterList::findOrFail($id);

        if ($request->query('format') === 'pdf') {
            $pdf = app('dompdf.wrapper');
            $pdf->loadView('admin.buyer_master_list.exports.housing_loan', compact('buyer'));
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
        $templateProcessor->setValue('proof_of_id', strtoupper($buyer->proof_of_id ?? ''));
        $templateProcessor->setValue('financing_method', strtoupper($buyer->financing_method ?? ''));
        
        // Map all property fields
        $templateProcessor->setValue('block_no', strtoupper($buyer->block_no ?? ''));
        $templateProcessor->setValue('lot_no', strtoupper($buyer->lot_no ?? ''));
        $templateProcessor->setValue('tct_no', strtoupper($buyer->tct_no ?? ''));
        $templateProcessor->setValue('pid', strtoupper($buyer->pid ?? ''));
        $templateProcessor->setValue('tax_dec_no', strtoupper($buyer->tax_dec_no ?? ''));
        $templateProcessor->setValue('lot_area', $buyer->lot_area ?? '');
        $templateProcessor->setValue('floor_area', $buyer->floor_area ?? '');
        $templateProcessor->setValue('description', strtoupper($buyer->description ?? ''));
        
        // Map all financial fields
        $templateProcessor->setValue('contract_amount', $buyer->contract_amount ?? '');
        $templateProcessor->setValue('equity', $buyer->equity ?? '');
        $templateProcessor->setValue('loan_base', $buyer->loan_base ?? '');
        $templateProcessor->setValue('loan_term', $buyer->loan_term ?? '');
        $templateProcessor->setValue('monthly_amortization', $buyer->monthly_amortization ?? '');
        $templateProcessor->setValue('improvement_amount', $buyer->improvement_amount ?? '');
        $templateProcessor->setValue('mri_sri', $buyer->mri_sri ?? '');
        $templateProcessor->setValue('insurance', $buyer->insurance ?? '');
        $templateProcessor->setValue('mri_ds_1time', $buyer->mri_ds_1time ?? '');
        $templateProcessor->setValue('nonlife_1time', $buyer->nonlife_1time ?? '');
        $templateProcessor->setValue('retention', $buyer->retention ?? '');
        $templateProcessor->setValue('total_deductions', $buyer->total_deductions ?? '');

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
        $templateProcessor->setValue('proof_of_id', strtoupper($buyer->proof_of_id ?? ''));

        $fileName = 'Buyer_Conformity_' . $buyer->last_name . '_' . $buyer->first_name . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'PHPWord');
        $templateProcessor->saveAs($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
