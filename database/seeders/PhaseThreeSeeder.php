<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Lot;
use App\Models\Reservation;
use App\Models\Downpayment;

class PhaseThreeSeeder extends Seeder
{
    public function run(): void
    {
        $mockReservations = [
            ['buyer' => 'Mark Spencer', 'block' => '5', 'lot_number' => '10', 'amount' => 20000, 'date' => '2026-04-10', 'status' => 'Reserved', 'notes' => 'Client is processing bank loan.'],
            ['buyer' => 'Lucy Fernandez', 'block' => '2', 'lot_number' => '4', 'amount' => 20000, 'date' => '2026-04-15', 'status' => 'Converted', 'notes' => 'Converted to full downpayment.'],
            ['buyer' => 'Eduardo Reyes', 'block' => '1', 'lot_number' => '12', 'amount' => 20000, 'date' => '2026-04-20', 'status' => 'Reserved', 'notes' => 'Awaiting secondary valid ID.'],
            ['buyer' => 'Samantha Cruz', 'block' => '3', 'lot_number' => '8', 'amount' => 20000, 'date' => '2026-04-22', 'status' => 'Cancelled', 'notes' => 'Client backed out, refund requested.'],
            ['buyer' => 'Julian Alba', 'block' => '4', 'lot_number' => '15', 'amount' => 20000, 'date' => '2026-04-28', 'status' => 'Reserved', 'notes' => 'Paid via check, pending clearing.'],
            ['buyer' => 'Patricia Lim', 'block' => '6', 'lot_number' => '2', 'amount' => 20000, 'date' => '2026-05-01', 'status' => 'Reserved', 'notes' => 'Interested in adjacent lot as well.'],
        ];

        $mockDownpayments = [
            ['buyer' => 'Lucy Fernandez', 'block' => '2', 'lot_number' => '4', 'total_dp' => 300000, 'paid_amount' => 60000, 'status' => 'Good Standing', 'next_due' => '2026-05-15'],
            ['buyer' => 'Ramon Bautista', 'block' => '1', 'lot_number' => '5', 'total_dp' => 450000, 'paid_amount' => 450000, 'status' => 'Fully Paid', 'next_due' => 'N/A'],
            ['buyer' => 'Sofia Alcantara', 'block' => '3', 'lot_number' => '12', 'total_dp' => 240000, 'paid_amount' => 24000, 'status' => 'Delinquent', 'next_due' => '2026-04-05'],
            ['buyer' => 'Miguel Cortez', 'block' => '5', 'lot_number' => '20', 'total_dp' => 360000, 'paid_amount' => 180000, 'status' => 'Good Standing', 'next_due' => '2026-05-20'],
            ['buyer' => 'Carla Mendoza', 'block' => '4', 'lot_number' => '8', 'total_dp' => 600000, 'paid_amount' => 500000, 'status' => 'Good Standing', 'next_due' => '2026-05-10'],
            ['buyer' => 'Dennis Chua', 'block' => '7', 'lot_number' => '1', 'total_dp' => 200000, 'paid_amount' => 0, 'status' => 'Good Standing', 'next_due' => '2026-05-30'],
        ];

        // Seed Reservations
        $reservationMap = [];
        foreach ($mockReservations as $resData) {
            $lot = Lot::firstOrCreate(
                ['block' => $resData['block'], 'lot_number' => $resData['lot_number']],
                ['status' => $resData['status'] === 'Cancelled' ? 'Available' : 'Reserved', 'provider_managed' => true]
            );

            $reservationMap[$resData['buyer']] = Reservation::create([
                'user_id' => null,
                'lot_id' => $lot->id,
                'status' => $resData['status'],
                'reservation_date' => $resData['date'],
                'amount' => $resData['amount'],
                'notes' => $resData['buyer'], // Saving name here temporarily to simulate guest
            ]);
        }

        // Seed Downpayments
        foreach ($mockDownpayments as $dpData) {
            // Check if reservation exists, else mock one
            if (!isset($reservationMap[$dpData['buyer']])) {
                $lot = Lot::firstOrCreate(
                    ['block' => $dpData['block'], 'lot_number' => $dpData['lot_number']],
                    ['status' => 'Reserved', 'provider_managed' => true]
                );
                $reservationMap[$dpData['buyer']] = Reservation::create([
                    'user_id' => null,
                    'lot_id' => $lot->id,
                    'status' => 'Converted',
                    'reservation_date' => '2026-01-01',
                    'amount' => 20000,
                    'notes' => $dpData['buyer'],
                ]);
            }

            Downpayment::create([
                'reservation_id' => $reservationMap[$dpData['buyer']]->id,
                'amount' => $dpData['paid_amount'],
                'due_date' => $dpData['next_due'] === 'N/A' ? null : $dpData['next_due'],
                'status' => $dpData['status'],
                'balance' => $dpData['total_dp'] - $dpData['paid_amount'],
            ]);
        }
    }
}
