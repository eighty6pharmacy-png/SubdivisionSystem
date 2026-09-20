<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UtilityBill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExportController extends Controller
{
    public function exportSOA(Request $request)
    {
        // Get all residents with lots
        $residents = User::role('Resident')->with(['lots'])->get()->filter(function ($r) {
            return $r->lots->count() > 0;
        });

        if ($residents->isEmpty()) {
            return redirect()->back()->with('error', 'No residents found.');
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Remove default sheet
        $spreadsheet->getDefaultStyle()->getFont()->setName('MingLiU_HKSCS');

        foreach ($residents as $index => $resident) {
            $lot = $resident->lots->first();
            $address = "Althesa Main, Block {$lot->block};\nLot {$lot->lot_number}; Z1, Tagbong, Pili, Camarines Sur";

            // Create a new sheet for the resident
            $sheet = $spreadsheet->createSheet();
            // Max length for sheet name is 31 characters
            $sheetTitle = substr(preg_replace('/[^a-zA-Z0-9\s]/', '', $resident->name), 0, 31);
            $sheet->setTitle($sheetTitle ?: "Resident {$resident->id}");

            // Setup column widths
            $sheet->getColumnDimension('A')->setWidth(26);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(15);
            $sheet->getColumnDimension('D')->setWidth(5); // Spacer
            $sheet->getColumnDimension('E')->setWidth(26);
            $sheet->getColumnDimension('F')->setWidth(20);
            $sheet->getColumnDimension('G')->setWidth(15);

            // Format Name: Last, First
            $nameParts = explode(' ', trim($resident->name));
            $lastName = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);
            $formattedName = $firstName ? "{$lastName}, {$firstName}" : $lastName;

            // Fetch Bills
            $elecBill = $resident->utilityBills()->where('type', 'electricity')->orderBy('created_at', 'desc')->first();
            $waterBill = $resident->utilityBills()->where('type', 'water')->orderBy('created_at', 'desc')->first();

            // Setup Layout
            // --- ELECTRICITY (A-C) ---
            $sheet->mergeCells('A1:C1');
            $sheet->setCellValue('A1', 'Mayon Builders Realty & Development Corporation');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A2:C2');
            $sheet->setCellValue('A2', 'Zone 1, Tagbong, Pili, Camarines Sur');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A3:C3');
            $sheet->setCellValue('A3', 'STATEMENT OF ACCOUNT');
            $sheet->mergeCells('A4:C4');
            $sheet->setCellValue('A4', 'ELECTRICITY BILL');
            $sheet->getStyle('A3:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // --- WATER (E-G) ---
            $sheet->mergeCells('E1:G1');
            $sheet->setCellValue('E1', 'Mayon Builders Realty & Development Corporation');
            $sheet->getStyle('E1')->getFont()->setBold(true);
            $sheet->getStyle('E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('E2:G2');
            $sheet->setCellValue('E2', 'Zone 1, Tagbong, Pili, Camarines Sur');
            $sheet->getStyle('E2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('E3:G3');
            $sheet->setCellValue('E3', 'STATEMENT OF ACCOUNT');
            $sheet->mergeCells('E4:G4');
            $sheet->setCellValue('E4', 'WATER BILL');
            $sheet->getStyle('E3:E4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Customer Details (Row 6, 7)
            $sheet->mergeCells('A6:C6');
            $sheet->setCellValue('A6', "Name: {$formattedName}");
            $sheet->getStyle('A6')->getFont()->setBold(true);

            $sheet->mergeCells('A7:C8');
            $sheet->setCellValue('A7', "Address: {$address}");
            $sheet->getStyle('A7')->getAlignment()->setWrapText(true);

            $sheet->mergeCells('E6:G6');
            $sheet->setCellValue('E6', "Name: {$formattedName}");
            $sheet->getStyle('E6')->getFont()->setBold(true);

            $sheet->mergeCells('E7:G8');
            $sheet->setCellValue('E7', "Address: {$address}");
            $sheet->getStyle('E7')->getAlignment()->setWrapText(true);

            // Billing Notice Header
            $currentMonth = date('F Y');
            if ($elecBill) $currentMonth = $elecBill->created_at->format('F Y');
            elseif ($waterBill) $currentMonth = $waterBill->created_at->format('F Y');

            $sheet->mergeCells('A9:C9');
            $sheet->setCellValue('A9', '---------------------------------------------------');
            $sheet->mergeCells('E9:G9');
            $sheet->setCellValue('E9', '---------------------------------------------------');

            $sheet->mergeCells('A10:C10');
            $sheet->setCellValue('A10', 'BILLING NOTICE');
            $sheet->mergeCells('A11:C11');
            $sheet->setCellValue('A11', strtoupper($currentMonth));
            $sheet->getStyle('A10:A11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('E10:G10');
            $sheet->setCellValue('E10', 'BILLING NOTICE');
            $sheet->mergeCells('E11:G11');
            $sheet->setCellValue('E11', strtoupper($currentMonth));
            $sheet->getStyle('E10:E11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A12:C12');
            $sheet->setCellValue('A12', '---------------------------------------------------');
            $sheet->mergeCells('E12:G12');
            $sheet->setCellValue('E12', '---------------------------------------------------');

            // Table Headers
            $sheet->setCellValue('B13', 'Date');
            $sheet->setCellValue('C13', 'Reading');
            $sheet->getStyle('B13:C13')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B13:C13')->getFont()->setBold(true);

            $sheet->setCellValue('F13', 'Date');
            $sheet->setCellValue('G13', 'Reading');
            $sheet->getStyle('F13:G13')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F13:G13')->getFont()->setBold(true);

            // Calculate Dates & Readings
            // Electricity
            $elecPrevDate = 'N/A';
            $elecCurrDate = 'N/A';
            $elecPrevRead = 'N/A';
            $elecCurrRead = 'N/A';
            $elecUsage = 'N/A';
            $elecRate = \App\Models\Setting::find('elec_rate')?->value ?? 10;
            $elecAmount = 'N/A';
            $elecPrevBal = 'N/A';
            $elecPenalty = 'N/A';
            $elecTotalBefore = 'N/A';
            $elecTotalAfter = 'N/A';
            $elecIssued = 'N/A';
            $elecDue = 'N/A';

            if ($elecBill) {
                // Find previous bill
                $elecPrevBill = $resident->utilityBills()->where('type', 'electricity')->where('id', '!=', $elecBill->id)->orderBy('created_at', 'desc')->first();
                $elecPrevDate = $elecPrevBill ? $elecPrevBill->created_at->format('n/j/Y') : $elecBill->created_at->copy()->subMonth()->format('n/j/Y');
                $elecCurrDate = $elecBill->created_at->format('n/j/Y');
                $elecPrevRead = $elecBill->previous_reading;
                $elecCurrRead = $elecBill->current_reading;
                $elecUsage = $elecBill->usage_value;
                $elecAmount = $elecBill->amount;
                $elecPrevBal = $elecBill->previous_balance;
                $penaltyRate = \App\Models\Setting::find('electricity_penalty')->value ?? 5;
                $elecPenalty = ($elecAmount + $elecPrevBal) * ($penaltyRate / 100);
                $elecTotalBefore = $elecAmount + $elecPrevBal;
                $elecTotalAfter = $elecTotalBefore + $elecPenalty;
                $elecIssued = $elecBill->created_at->format('m/d/Y');
                $elecDue = $elecBill->due_date ? \Carbon\Carbon::parse($elecBill->due_date)->format('m/d/Y') : 'N/A';
            }

            // Water
            $watPrevDate = 'N/A';
            $watCurrDate = 'N/A';
            $watPrevRead = 'N/A';
            $watCurrRead = 'N/A';
            $watUsage = 'N/A';
            $watMinM3 = \App\Models\Setting::find('water_min_m3')?->value ?? 10;
            $watMinRate = \App\Models\Setting::find('water_min_rate')?->value ?? 250;
            $watExcessRate = \App\Models\Setting::find('water_rate')?->value ?? 30;
            $watAmount = 'N/A';
            $watPrevBal = 'N/A';
            $watPenalty = 'N/A';
            $watTotalBefore = 'N/A';
            $watTotalAfter = 'N/A';
            $watIssued = 'N/A';
            $watDue = 'N/A';
            $watExcess = 0;

            if ($waterBill) {
                $watPrevBill = $resident->utilityBills()->where('type', 'water')->where('id', '!=', $waterBill->id)->orderBy('created_at', 'desc')->first();
                $watPrevDate = $watPrevBill ? $watPrevBill->created_at->format('n/j/Y') : $waterBill->created_at->copy()->subMonth()->format('n/j/Y');
                $watCurrDate = $waterBill->created_at->format('n/j/Y');
                $watPrevRead = $waterBill->previous_reading;
                $watCurrRead = $waterBill->current_reading;
                $watUsage = $waterBill->usage_value;
                $watExcess = $watUsage > $watMinM3 ? ($watUsage - $watMinM3) * $watExcessRate : 0;
                $watAmount = $waterBill->amount;
                $watPrevBal = $waterBill->previous_balance;
                $watPenalty = ($watAmount + $watPrevBal) * 0.05;
                $watTotalBefore = $watAmount + $watPrevBal;
                $watTotalAfter = $watTotalBefore + $watPenalty;
                $watIssued = $waterBill->created_at->format('m/d/Y');
                $watDue = $waterBill->due_date ? \Carbon\Carbon::parse($waterBill->due_date)->format('m/d/Y') : 'N/A';
            }

            // Write Data Row 14, 15
            $sheet->setCellValue('A14', 'Previous');
            $sheet->setCellValue('B14', $elecPrevDate);
            $sheet->setCellValue('C14', $elecPrevRead);
            $sheet->getStyle('B14:C14')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue('A15', 'Present');
            $sheet->setCellValue('B15', $elecCurrDate);
            $sheet->setCellValue('C15', $elecCurrRead);
            $sheet->getStyle('B15:C15')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue('E14', 'Previous');
            $sheet->setCellValue('F14', $watPrevDate);
            $sheet->setCellValue('G14', $watPrevRead);
            $sheet->getStyle('F14:G14')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue('E15', 'Present');
            $sheet->setCellValue('F15', $watCurrDate);
            $sheet->setCellValue('G15', $watCurrRead);
            $sheet->getStyle('F15:G15')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->mergeCells('A16:C16');
            $sheet->setCellValue('A16', '---------------------------------------------------');
            $sheet->mergeCells('E16:G16');
            $sheet->setCellValue('E16', '---------------------------------------------------');

            // Consumption
            $sheet->setCellValue('A17', 'Consumption');
            $sheet->setCellValue('B17', ':');
            $sheet->setCellValue('C17', $elecUsage);
            $sheet->getStyle('B17:C17')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue('E17', 'Consumption');
            $sheet->setCellValue('F17', ':');
            $sheet->setCellValue('G17', $watUsage);
            $sheet->getStyle('F17:G17')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->mergeCells('A18:C18');
            $sheet->setCellValue('A18', '---------------------------------------------------');
            $sheet->mergeCells('E18:G18');
            $sheet->setCellValue('E18', '---------------------------------------------------');

            // Summary of Charges
            $sheet->mergeCells('A19:C19');
            $sheet->setCellValue('A19', 'SUMMARY OF CHARGES');
            $sheet->getStyle('A19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A19')->getFont()->setBold(true);

            $sheet->setCellValue('A20', 'Description');
            $sheet->setCellValue('B20', 'Computation');
            $sheet->setCellValue('C20', 'Amount');
            $sheet->getStyle('A20:C20')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A20:C20')->getFont()->setBold(true);

            $sheet->mergeCells('E19:G19');
            $sheet->setCellValue('E19', 'SUMMARY OF CHARGES');
            $sheet->getStyle('E19')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E19')->getFont()->setBold(true);

            $sheet->setCellValue('E20', 'Description');
            $sheet->setCellValue('F20', 'Computation');
            $sheet->setCellValue('G20', 'Amount');
            $sheet->getStyle('E20:G20')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E20:G20')->getFont()->setBold(true);

            // Rows 21, 22, 23, 24
            $sheet->setCellValue('A21', 'Consumption');
            $comp = $elecUsage !== 'N/A' ? "{$elecUsage} x {$elecRate}" : 'N/A';
            $sheet->setCellValue('B21', $comp);
            $sheet->setCellValue('C21', $elecAmount !== 'N/A' ? number_format((float)$elecAmount, 2) : 'N/A');

            $sheet->setCellValue('A22', 'Previous Unpaid Bill');
            $sheet->setCellValue('B22', 'Arrears');
            $sheet->setCellValue('C22', $elecPrevBal !== 'N/A' && $elecPrevBal > 0 ? number_format((float)$elecPrevBal, 2) : '0.00');

            $sheet->setCellValue('A23', 'Late Payment Bill');
            $sheet->setCellValue('B23', '5%');
            $sheet->setCellValue('C23', $elecPenalty !== 'N/A' ? number_format((float)$elecPenalty, 2) : '0.00');

            $sheet->setCellValue('E21', 'Current Bill (Minimum)');
            $sheet->setCellValue('F21', "First {$watMinM3}m3");
            $sheet->setCellValue('G21', $waterBill ? number_format((float)$watMinRate, 2) : 'N/A');

            $sheet->setCellValue('E22', 'Excess Consumption');
            $sheet->setCellValue('F22', "x{$watExcessRate}");
            $sheet->setCellValue('G22', $waterBill && $watExcess > 0 ? number_format((float)$watExcess, 2) : '0.00');

            $sheet->setCellValue('E23', 'Previous Unpaid Bill');
            $sheet->setCellValue('F23', 'Arrears');
            $sheet->setCellValue('G23', $watPrevBal !== 'N/A' && $watPrevBal > 0 ? number_format((float)$watPrevBal, 2) : '0.00');

            $sheet->setCellValue('E24', 'Late Payment Bill');
            $sheet->setCellValue('F24', '5%');
            $sheet->setCellValue('G24', $watPenalty !== 'N/A' ? number_format((float)$watPenalty, 2) : '0.00');

            // Alignments for computation (center) and amount (right)
            $sheet->getStyle('B21:B23')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C21:C23')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('F21:F24')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G21:G24')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->mergeCells('A25:C25');
            $sheet->setCellValue('A25', '---------------------------------------------------');
            $sheet->mergeCells('E25:G25');
            $sheet->setCellValue('E25', '---------------------------------------------------');

            // Amounts Before and After
            $sheet->setCellValue('A26', "Amount Before {$elecDue}");
            $sheet->mergeCells('B26:C26');
            $sheet->setCellValue('B26', $elecTotalBefore !== 'N/A' ? number_format((float)$elecTotalBefore, 2) : 'N/A');
            $sheet->getStyle('A26:B26')->getFont()->setBold(true);
            $sheet->getStyle('B26')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue('A27', "Amount After {$elecDue}");
            $sheet->mergeCells('B27:C27');
            $sheet->setCellValue('B27', $elecTotalAfter !== 'N/A' ? number_format((float)$elecTotalAfter, 2) : 'N/A');
            $sheet->getStyle('A27:B27')->getFont()->setBold(true);
            $sheet->getStyle('B27')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue('E26', "Amount Before {$watDue}");
            $sheet->mergeCells('F26:G26');
            $sheet->setCellValue('F26', $watTotalBefore !== 'N/A' ? number_format((float)$watTotalBefore, 2) : 'N/A');
            $sheet->getStyle('E26:F26')->getFont()->setBold(true);
            $sheet->getStyle('F26')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue('E27', "Amount After {$watDue}");
            $sheet->mergeCells('F27:G27');
            $sheet->setCellValue('F27', $watTotalAfter !== 'N/A' ? number_format((float)$watTotalAfter, 2) : 'N/A');
            $sheet->getStyle('E27:F27')->getFont()->setBold(true);
            $sheet->getStyle('F27')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Empty row 28
            $sheet->setCellValue('A29', 'Date Issued:');
            $sheet->mergeCells('B29:C29');
            $sheet->setCellValue('B29', $elecIssued);
            $sheet->getStyle('B29')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue('A30', 'Due Date:');
            $sheet->mergeCells('B30:C30');
            $sheet->setCellValue('B30', $elecDue);
            $sheet->getStyle('A30:B30')->getFont()->getColor()->setARGB('FFFF0000'); // Red
            $sheet->getStyle('B30')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue('E29', 'Date Issued:');
            $sheet->mergeCells('F29:G29');
            $sheet->setCellValue('F29', $watIssued);
            $sheet->getStyle('F29')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->setCellValue('E30', 'Due Date:');
            $sheet->mergeCells('F30:G30');
            $sheet->setCellValue('F30', $watDue);
            $sheet->getStyle('E30:F30')->getFont()->getColor()->setARGB('FFFF0000'); // Red
            $sheet->getStyle('F30')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Notes
            $sheet->setCellValue('A31', 'Note:');
            $sheet->getStyle('A31')->getFont()->setBold(true);
            $sheet->setCellValue('A32', 'Please settle your payable on/');
            $sheet->setCellValue('A33', 'before the due date. Thank you!');

            $sheet->setCellValue('E31', 'Note:');
            $sheet->getStyle('E31')->getFont()->setBold(true);
            $sheet->setCellValue('E32', 'Please settle your payable on/');
            $sheet->setCellValue('E33', 'before the due date. Thank you!');

            // Conforme
            $sheet->setCellValue('A36', 'Conforme:');
            $sheet->getStyle('A36')->getFont()->setBold(true);

            $sheet->mergeCells('B38:C38');
            $sheet->setCellValue('B38', strtoupper($formattedName));
            $sheet->getStyle('B38')->getFont()->setBold(true);
            $sheet->getStyle('B38')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('B39:C39');
            $sheet->setCellValue('B39', 'Owner');
            $sheet->getStyle('B39')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue('E36', 'Conforme:');
            $sheet->getStyle('E36')->getFont()->setBold(true);

            $sheet->mergeCells('F38:G38');
            $sheet->setCellValue('F38', strtoupper($formattedName));
            $sheet->getStyle('F38')->getFont()->setBold(true);
            $sheet->getStyle('F38')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('F39:G39');
            $sheet->setCellValue('F39', 'Owner');
            $sheet->getStyle('F39')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Add borders to grids where appropriate
            $borderStyle = [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['argb' => 'FF000000'],
                    ],
                ],
            ];
            
            // Set font size for all
            $sheet->getStyle('A1:G40')->getFont()->setSize(11);
        }

        // Output to browser
        $filename = 'Bulk_SOA_Export_' . date('Y_m_d_His') . '.xlsx';
        
        // redirect output to client browser
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'. $filename .'"');
        header('Cache-Control: max-age=0');
        
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
