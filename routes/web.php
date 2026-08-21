<?php

use Illuminate\Support\Facades\Route;

// Shared Incident Data Function for Simulation
if (!function_exists('getIncidents')) {
    function getIncidents() {
        $incidents = [
            ['id' => 'INC-1001', 'sub' => 'Streetlight Issue - Gate 2', 'type' => 'Maintenance', 'res' => 'Anonymous', 'desc' => 'Flickering street light at entrance since last night. Safety hazard.', 'img' => 'https://media.istockphoto.com/id/157291129/photo/snapped-power-pole.jpg?s=612x612&w=0&k=20&c=Nl5fTf4y_uWlS5Q4A1_M8Jd3qI1eXWv_d7d1HnO1S8I='],
            ['id' => 'INC-1002', 'sub' => 'Water Leak Near Clubhouse', 'type' => 'Utilities', 'res' => 'Block A, Lot 5', 'desc' => 'Large puddle forming on the sidewalk near the pool entrance.', 'img' => 'https://images.unsplash.com/photo-1542013976693-6b573b925b4b'],
            ['id' => 'INC-1003', 'sub' => 'Loud Noise - Phase 2', 'type' => 'Noise', 'res' => 'Anonymous', 'desc' => 'Loud music past 11 PM near Lot 45. Disruption of peace.', 'img' => 'https://plus.unsplash.com/premium_photo-1661605330310-09a15ab2aece'],
            ['id' => 'INC-1004', 'sub' => 'Stray Dogs Near Playground', 'type' => 'Pet Warning', 'res' => 'Block C, Lot 12', 'desc' => 'Several aggressive stray dogs spotted near the children\'s park.', 'img' => 'https://images.unsplash.com/photo-1544568100-847a948585b9'],
            ['id' => 'INC-1005', 'sub' => 'Broken Bench in Park', 'type' => 'Amenities', 'res' => 'Anonymous', 'desc' => 'One of the park benches is loose and unsafe for residents.', 'img' => 'https://images.unsplash.com/photo-1518173946687-a4c8a9b749f5'],
            ['id' => 'INC-1006', 'sub' => 'Suspicious Person in Phase 1', 'type' => 'Security', 'res' => 'Block B, Lot 22', 'desc' => 'Person taking photos of houses and staying in the same area for too long.', 'img' => 'https://images.unsplash.com/photo-1590362891991-f776e747a588'],
            ['id' => 'INC-1007', 'sub' => 'Garbage Collection Delayed', 'type' => 'Sanitation', 'res' => 'Anonymous', 'desc' => 'Trash not picked up today at Phase 1. Odor causing concerns.', 'img' => 'https://images.unsplash.com/photo-1530587191325-3db32d826c18'],
            ['id' => 'INC-1008', 'sub' => 'Clogged Drainage - Block B', 'type' => 'Maintenance', 'res' => 'Block B, Lot 8', 'desc' => 'Water not flowing correctly during rains, causing flooding.', 'img' => 'https://images.unsplash.com/photo-1590458113401-4993172828a2'],
            ['id' => 'INC-1009', 'sub' => 'Speeding Vehicle Warning', 'type' => 'Safety', 'res' => 'Anonymous', 'desc' => 'Silver sedan speeding frequently through the residence area.', 'img' => 'https://images.unsplash.com/photo-1593941707882-a5bba149ff92'],
            ['id' => 'INC-1010', 'sub' => 'Gym AC Maintenance', 'type' => 'Amenities', 'res' => 'Clubhouse Admin', 'desc' => 'AC in gym needs immediate repair due to unusual noises.', 'img' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48'],
            ['id' => 'INC-1011', 'sub' => 'Pool Chlorine Level Check', 'type' => 'Safety', 'res' => 'Anonymous', 'desc' => 'Pool water looks murky and needs a chemical balance test.', 'img' => 'https://images.unsplash.com/photo-1576013551627-0cc20b96c2a7'],
            ['id' => 'INC-1012', 'sub' => 'Internet Line Restoration', 'type' => 'Utilities', 'res' => 'Block D, Lot 4', 'desc' => 'Fiber line cut during excavation near the park area.', 'img' => 'https://images.unsplash.com/photo-1614064641938-3bbee529424d'],
            ['id' => 'INC-1013', 'sub' => 'Fence Repair - Perimeter South', 'type' => 'Security', 'res' => 'Staff Report', 'desc' => 'Fixed small hole in the perimeter fence near Phase 2.', 'img' => 'https://images.unsplash.com/photo-1533035350223-af686a066807'],
            ['id' => 'INC-1014', 'sub' => 'Tennis Court Net Replaced', 'type' => 'Amenities', 'res' => 'Anonymous', 'desc' => 'Old torn tennis net replaced with a brand new one.', 'img' => 'https://images.unsplash.com/photo-1595435064212-36263f6844b9'],
            ['id' => 'INC-1015', 'sub' => 'Tree Trimming - Main Road', 'type' => 'Maintenance', 'res' => 'Block A, Lot 10', 'desc' => 'Low hanging branches removed to ensure clear view of drivers.', 'img' => 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09'],
            ['id' => 'INC-1016', 'sub' => 'Security Gate Sensor Fixed', 'type' => 'Safety', 'res' => 'Gate Security', 'desc' => 'Optical sensors at the main gate were replaced.', 'img' => 'https://images.unsplash.com/photo-1558002038-1055907df827'],
            ['id' => 'INC-1017', 'sub' => 'Hydrant Inspection Complete', 'type' => 'Utilities', 'res' => 'Anonymous', 'desc' => 'All fire hydrants checked and pressurized. Compliance achieved.', 'img' => 'https://images.unsplash.com/photo-1516733959040-0080345097b6'],
            ['id' => 'INC-1018', 'sub' => 'Signage Repainted', 'type' => 'Maintenance', 'res' => 'Block B, Lot 15', 'desc' => 'Speed limit and direction signs repainted for better visibility.', 'img' => 'https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af'],
            ['id' => 'INC-1019', 'sub' => 'Lobby Furniture Cleaned', 'type' => 'Amenities', 'res' => 'Anonymous', 'desc' => 'Deep cleaning of clubhouse sofas and lounge chairs completed.', 'img' => 'https://images.unsplash.com/photo-1556228453-efd6c1ff04f6'],
            ['id' => 'INC-1020', 'sub' => 'Illegal Parking Notice Issued', 'type' => 'Regulation', 'res' => 'Anonymous', 'desc' => 'Vehicle blocking the fire lane has been issued a formal warning.', 'img' => 'https://images.unsplash.com/photo-1510903117032-f1596c321647'],
        ];

        $statuses = ['pending', 'progress', 'resolved'];
        foreach ($incidents as &$inc) {
            $inc['status'] = $statuses[array_rand($statuses)];
        }
        return $incidents;
    }
}

if (!function_exists('getAnnouncements')) {
    function getAnnouncements() {
        return [
            ['id' => 1, 'title' => 'Annual General Meeting 2026', 'cat' => 'Event', 'status' => 'published', 'date' => '2026-04-15', 'content' => 'Join us for the AGM to discuss the subdivision\'s budget and infrastructure plans.', 'author' => 'Board Secretary'],
            ['id' => 2, 'title' => 'Emergency Water Maintenance', 'cat' => 'Emergency', 'status' => 'published', 'date' => '2026-04-05', 'content' => 'Urgent water pipe repair starting at 10:00 PM. Expect water interruption for 4 hours.', 'author' => 'Maintenance Admin'],
            ['id' => 3, 'title' => 'Garbage Collection Schedule Change', 'cat' => 'Maintenance', 'status' => 'scheduled', 'date' => '2026-04-10', 'content' => 'Standard pickup will move to 8:00 AM instead of 7:00 AM starting next week.', 'author' => 'Sanitation Head'],
            ['id' => 4, 'title' => 'Summer Pool Party 2026', 'cat' => 'Event', 'status' => 'draft', 'date' => '2026-05-01', 'content' => 'Get ready for our annual subdivision pool party! Details to follow.', 'author' => 'Social Committee'],
            ['id' => 5, 'title' => 'New Security Protocols', 'cat' => 'General', 'status' => 'published', 'date' => '2026-03-30', 'content' => 'Please note that new QR code scans will be required for all guest vehicles.', 'author' => 'Security Chief'],
        ];
    }
}

if (!function_exists('getElectricalBills')) {
    function getElectricalBills() {
        $base = [
            ['id' => 'EB-001', 'lot' => 'B1 L5', 'block' => '1', 'resident' => 'Juan Dela Cruz', 'amount' => 1250.50, 'usage' => '150 kWh', 'status' => 'unpaid', 'due' => '2026-05-10', 'paid_date' => null, 'method' => null, 'usage_history' => [140, 155, 160, 145, 130, 150, 155, 165, 170, 160, 150, 155], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1200, 'status' => 'Paid', 'date' => '26-03-02'], ['month' => 'Feb 2026', 'amount' => 1150, 'status' => 'Paid', 'date' => '26-02-05']]],
            ['id' => 'EB-002', 'lot' => 'B2 L12', 'block' => '2', 'resident' => 'Maria Santos', 'amount' => 2100.00, 'usage' => '245 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [230, 240, 255, 260, 245, 235, 240, 250, 260, 255, 245, 250], 'at_risk' => true, 'payment_history' => [['month' => 'March 2026', 'amount' => 2000, 'status' => 'Paid', 'date' => '26-03-12'], ['month' => 'Feb 2026', 'amount' => 2100, 'status' => 'Late', 'date' => '26-02-20']]],
            ['id' => 'EB-003', 'lot' => 'B3 L8', 'block' => '3', 'resident' => 'Ricardo Reyes', 'amount' => 890.75, 'usage' => '110 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-04-05', 'method' => 'GCash', 'usage_history' => [100, 115, 120, 110, 105, 110, 115, 120, 110, 105, 110, 115], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 850, 'status' => 'Paid', 'date' => '26-03-05']]],
            ['id' => 'EB-004', 'lot' => 'B4 L22', 'block' => '4', 'resident' => 'Elena Gomez', 'amount' => 3400.20, 'usage' => '420 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [400, 410, 430, 440, 420, 410, 420, 430, 440, 430, 420, 425], 'at_risk' => true, 'payment_history' => [['month' => 'March 2026', 'amount' => 3200, 'status' => 'Paid', 'date' => '26-03-15']]],
            ['id' => 'EB-005', 'lot' => 'B5 L4', 'block' => '5', 'resident' => 'Antonio Luna', 'amount' => 1560.00, 'usage' => '180 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-03-28', 'method' => 'GCash', 'usage_history' => [170, 185, 190, 180, 175, 180, 185, 190, 180, 175, 180, 185], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1500, 'status' => 'Paid', 'date' => '26-03-28']]],
            ['id' => 'EB-006', 'lot' => 'B1 L15', 'block' => '1', 'resident' => 'Andres Bonifacio', 'amount' => 1100.00, 'usage' => '130 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [120, 130, 140, 125, 110, 130, 135, 145, 150, 140, 130, 135], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1050, 'status' => 'Paid', 'date' => '26-03-10']]],
            ['id' => 'EB-007', 'lot' => 'B2 L5', 'block' => '2', 'resident' => 'Jose Rizal', 'amount' => 2500.00, 'usage' => '310 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-04-02', 'method' => 'Office', 'usage_history' => [290, 310, 320, 305, 280, 310, 315, 325, 330, 320, 310, 315], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 2400, 'status' => 'Paid', 'date' => '26-03-30']]],
            ['id' => 'EB-008', 'lot' => 'B3 L22', 'block' => '3', 'resident' => 'Apolinario Mabini', 'amount' => 1780.40, 'usage' => '210 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [190, 210, 220, 205, 180, 210, 215, 225, 230, 220, 210, 215], 'at_risk' => true, 'payment_history' => [['month' => 'March 2026', 'amount' => 1700, 'status' => 'Paid', 'date' => '26-03-12']]],
            ['id' => 'EB-009', 'lot' => 'B4 L8', 'block' => '4', 'resident' => 'Emilio Aguinaldo', 'amount' => 1350.75, 'usage' => '165 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-04-04', 'method' => 'GCash', 'usage_history' => [150, 165, 175, 160, 145, 165, 170, 180, 185, 175, 165, 170], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1300, 'status' => 'Paid', 'date' => '26-03-05']]],
            ['id' => 'EB-010', 'lot' => 'B5 L12', 'block' => '5', 'resident' => 'Marcelo Del Pilar', 'amount' => 2900.20, 'usage' => '350 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [330, 350, 360, 345, 320, 350, 355, 365, 370, 360, 350, 355], 'at_risk' => true, 'payment_history' => [['month' => 'March 2026', 'amount' => 2800, 'status' => 'Paid', 'date' => '26-03-15']]],
            ['id' => 'EB-011', 'lot' => 'B1 L8', 'block' => '1', 'resident' => 'Melchora Aquino', 'amount' => 950.00, 'usage' => '115 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-03-30', 'method' => 'Office', 'usage_history' => [100, 115, 125, 110, 95, 115, 120, 130, 135, 125, 115, 120], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 900, 'status' => 'Paid', 'date' => '26-03-05']]],
            ['id' => 'EB-012', 'lot' => 'B2 L22', 'block' => '2', 'resident' => 'Gabriela Silang', 'amount' => 3100.50, 'usage' => '380 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [360, 380, 390, 375, 350, 380, 385, 395, 400, 390, 380, 385], 'at_risk' => true, 'payment_history' => [['month' => 'March 2026', 'amount' => 3000, 'status' => 'Paid', 'date' => '26-03-18']]],
            ['id' => 'EB-013', 'lot' => 'B3 L5', 'block' => '3', 'resident' => 'Gregorio Del Pilar', 'amount' => 1420.00, 'usage' => '175 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-04-01', 'method' => 'Office', 'usage_history' => [160, 175, 185, 170, 155, 175, 180, 190, 195, 185, 175, 180], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1400, 'status' => 'Paid', 'date' => '26-03-30']]],
            ['id' => 'EB-014', 'lot' => 'B4 L12', 'block' => '4', 'resident' => 'Juan Luna', 'amount' => 2150.00, 'usage' => '260 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [240, 260, 270, 255, 230, 260, 265, 275, 280, 270, 260, 265], 'at_risk' => true, 'payment_history' => [['month' => 'March 2026', 'amount' => 2000, 'status' => 'Paid', 'date' => '26-03-12']]],
            ['id' => 'EB-015', 'lot' => 'B5 L8', 'block' => '5', 'resident' => 'Mariano Ponce', 'amount' => 1180.75, 'usage' => '145 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-04-05', 'method' => 'GCash', 'usage_history' => [130, 145, 155, 140, 125, 145, 150, 160, 165, 155, 145, 150], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1100, 'status' => 'Paid', 'date' => '26-03-05']]],
            ['id' => 'EB-016', 'lot' => 'B1 L22', 'block' => '1', 'resident' => 'Diego Silang', 'amount' => 3800.20, 'usage' => '470 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [450, 470, 480, 465, 440, 470, 475, 485, 490, 480, 470, 475], 'at_risk' => true, 'payment_history' => [['month' => 'March 2026', 'amount' => 3600, 'status' => 'Paid', 'date' => '26-03-20']]],
            ['id' => 'EB-017', 'lot' => 'B2 L8', 'block' => '2', 'resident' => 'Teresa Magbanua', 'amount' => 1250.00, 'usage' => '155 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-03-29', 'method' => 'Office', 'usage_history' => [140, 155, 165, 150, 135, 155, 160, 170, 175, 165, 155, 160], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1200, 'status' => 'Paid', 'date' => '26-03-25']]],

            ['id' => 'EB-018', 'lot' => 'B3 L12', 'block' => '3', 'resident' => 'Francisco Balagtas', 'amount' => 2400.50, 'usage' => '295 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [280, 295, 305, 290, 265, 295, 300, 310, 315, 305, 295, 300], 'at_risk' => true, 'payment_history' => [['month' => 'March 2026', 'amount' => 2300, 'status' => 'Paid', 'date' => '26-03-12']]],
            ['id' => 'EB-019', 'lot' => 'B4 L5', 'block' => '4', 'resident' => 'Julian Felipe', 'amount' => 1560.00, 'usage' => '190 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-04-02', 'method' => 'Office', 'usage_history' => [175, 190, 200, 185, 170, 190, 195, 205, 210, 200, 190, 195], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1500, 'status' => 'Paid', 'date' => '26-03-30']]],
            ['id' => 'EB-020', 'lot' => 'B5 L22', 'block' => '5', 'resident' => 'Felipe Agoncillo', 'amount' => 4100.00, 'usage' => '510 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [490, 510, 520, 505, 480, 510, 515, 525, 530, 520, 510, 515], 'at_risk' => true, 'payment_history' => [['month' => 'March 2026', 'amount' => 3900, 'status' => 'Paid', 'date' => '26-03-15']]],
            ['id' => 'EB-021', 'lot' => 'B1 L12', 'block' => '1', 'resident' => 'Leon Ma. Guerrero', 'amount' => 1650.75, 'usage' => '200 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-04-03', 'method' => 'GCash', 'usage_history' => [185, 200, 210, 195, 180, 200, 205, 215, 220, 210, 200, 205], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1600, 'status' => 'Paid', 'date' => '26-03-05']]],
            ['id' => 'EB-022', 'lot' => 'B6 L3', 'block' => '6', 'resident' => 'Emilio Jacinto', 'amount' => 1950.00, 'usage' => '220 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-04-02', 'method' => 'GCash', 'usage_history' => [200, 215, 225, 210, 195, 215, 220, 230, 235, 225, 215, 220], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1900, 'status' => 'Paid', 'date' => '26-03-05']]],
            ['id' => 'EB-023', 'lot' => 'B6 L10', 'block' => '6', 'resident' => 'Gomburza Family', 'amount' => 2800.00, 'usage' => '320 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [300, 315, 325, 310, 295, 315, 320, 330, 335, 325, 315, 320], 'at_risk' => true, 'payment_history' => [['month' => 'March 2026', 'amount' => 2700, 'status' => 'Paid', 'date' => '26-03-12']]],
            ['id' => 'EB-024', 'lot' => 'B7 L4', 'block' => '7', 'resident' => 'Macario Sakay', 'amount' => 1350.00, 'usage' => '150 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-03-30', 'method' => 'Office', 'usage_history' => [130, 145, 155, 140, 125, 145, 150, 160, 165, 155, 145, 150], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1300, 'status' => 'Paid', 'date' => '26-03-02']]],
            ['id' => 'EB-025', 'lot' => 'B7 L11', 'block' => '7', 'resident' => 'Miguel Malvar', 'amount' => 2450.00, 'usage' => '280 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [260, 275, 285, 270, 255, 275, 280, 290, 295, 285, 275, 280], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 2400, 'status' => 'Paid', 'date' => '26-03-10']]],
            ['id' => 'EB-026', 'lot' => 'B8 L2', 'block' => '8', 'resident' => 'Marcelo Agoncillo', 'amount' => 1700.00, 'usage' => '190 kWh', 'status' => 'paid', 'due' => '2026-04-05', 'paid_date' => '2026-04-01', 'method' => 'GCash', 'usage_history' => [170, 185, 195, 180, 165, 185, 190, 200, 205, 195, 185, 190], 'at_risk' => false, 'payment_history' => [['month' => 'March 2026', 'amount' => 1650, 'status' => 'Paid', 'date' => '26-03-05']]],
            ['id' => 'EB-027', 'lot' => 'B8 L9', 'block' => '8', 'resident' => 'Gregoria de Jesus', 'amount' => 3100.00, 'usage' => '360 kWh', 'status' => 'unpaid', 'due' => '2026-04-10', 'paid_date' => null, 'method' => null, 'usage_history' => [340, 355, 365, 350, 335, 355, 360, 370, 375, 365, 355, 360], 'at_risk' => true, 'payment_history' => [['month' => 'March 2026', 'amount' => 3000, 'status' => 'Paid', 'date' => '26-03-15']]],
        ];
        
        foreach ($base as &$bill) {
            $bill['usage_kwh'] = (int) filter_var($bill['usage'], FILTER_SANITIZE_NUMBER_INT);
            $bill['base_amount'] = $bill['usage_kwh'] * 10;
            
            foreach ($bill['payment_history'] as &$hist) {
                // Generate a consistent pseudo-random TRN from the Resident's name and month
                $trnHash = strtoupper(substr(md5($bill['resident'] . $hist['month']), 0, 8));
                $hist['trn'] = 'TRN-' . $trnHash;
            }
            
            $bill['audit_log'] = [
                ['action' => 'Statement Generated', 'date' => '2026-03-25 08:00 AM', 'user' => 'System']
            ];
            
            // If they are unpaid and due date is approaching or past, log notifications
            if ($bill['status'] == 'unpaid' && $bill['at_risk']) {
                $bill['audit_log'][] = ['action' => 'Automated Warning Sent - Email', 'date' => date('Y-m-d H:i A', strtotime('-1 day')), 'user' => 'System'];
            }
        }
        
        usort($base, function($a, $b) {
            $blockA = (int) $a['block'];
            $blockB = (int) $b['block'];
            if ($blockA === $blockB) {
                // Extract lot numbers
                preg_match('/L(\d+)/', $a['lot'], $matchesA);
                preg_match('/L(\d+)/', $b['lot'], $matchesB);
                $lotA = isset($matchesA[1]) ? (int) $matchesA[1] : 0;
                $lotB = isset($matchesB[1]) ? (int) $matchesB[1] : 0;
                return $lotA <=> $lotB;
            }
            return $blockA <=> $blockB;
        });

        return $base;
    }
}

if (!function_exists('getWaterBills')) {
    function getWaterBills() {
        $base = getElectricalBills(); // Clone the base layout for residents to maintain data structure
        foreach ($base as &$bill) {
            $bill['id'] = str_replace('EB-', 'WB-', $bill['id']);
            // Convert simulated kwh usage into m3 usage roughly (much smaller numbers)
            $bill['usage_m3'] = ceil($bill['usage_kwh'] / 5);
            $bill['usage_cbm'] = $bill['usage_m3']; // alias for analytics
            $bill['usage'] = $bill['usage_m3'] . ' m³';
            $bill['base_amount'] = $bill['usage_m3'] * 15; // Simulated 15 pesos per cubic meter base
            $bill['amount'] = $bill['base_amount'];
            
            foreach ($bill['payment_history'] as &$hist) {
                // Generate a different TRN for water based on WB prefix
                $trnHash = strtoupper(substr(md5('WATER'.$bill['resident'] . $hist['month']), 0, 8));
                $hist['trn'] = 'TRN-' . $trnHash;
            }
        }
        return $base;
    }
}

if (!function_exists('getReservationFees')) {
    function getReservationFees() {
        return [
            ['id' => 'RESV-1001', 'buyer' => 'Mark Spencer', 'contact' => '0917-123-1111', 'block' => '5', 'lot' => '10', 'amount' => 20000, 'date' => '2026-04-10', 'status' => 'Reserved', 'agent' => 'Admin Jane', 'notes' => 'Client is processing bank loan.'],
            ['id' => 'RESV-1002', 'buyer' => 'Lucy Fernandez', 'contact' => '0918-222-3333', 'block' => '2', 'lot' => '4', 'amount' => 20000, 'date' => '2026-04-15', 'status' => 'Converted', 'agent' => 'Admin Mark', 'notes' => 'Converted to full downpayment.'],
            ['id' => 'RESV-1003', 'buyer' => 'Eduardo Reyes', 'contact' => '0919-456-7890', 'block' => '1', 'lot' => '12', 'amount' => 20000, 'date' => '2026-04-20', 'status' => 'Reserved', 'agent' => 'Admin Jane', 'notes' => 'Awaiting secondary valid ID.'],
            ['id' => 'RESV-1004', 'buyer' => 'Samantha Cruz', 'contact' => '0922-555-8888', 'block' => '3', 'lot' => '8', 'amount' => 20000, 'date' => '2026-04-22', 'status' => 'Cancelled', 'agent' => 'Admin Mark', 'notes' => 'Client backed out, refund requested.'],
            ['id' => 'RESV-1005', 'buyer' => 'Julian Alba', 'contact' => '0917-888-2222', 'block' => '4', 'lot' => '15', 'amount' => 20000, 'date' => '2026-04-28', 'status' => 'Reserved', 'agent' => 'Admin Jane', 'notes' => 'Paid via check, pending clearing.'],
            ['id' => 'RESV-1006', 'buyer' => 'Patricia Lim', 'contact' => '0920-111-2233', 'block' => '6', 'lot' => '2', 'amount' => 20000, 'date' => '2026-05-01', 'status' => 'Reserved', 'agent' => 'Admin Mark', 'notes' => 'Interested in adjacent lot as well.'],
        ];
    }
}

if (!function_exists('getDownpaymentFees')) {
    function getDownpaymentFees() {
        return [
            ['id' => 'DP-2001', 'buyer' => 'Lucy Fernandez', 'block' => '2', 'lot' => '4', 'total_dp' => 300000, 'paid_amount' => 60000, 'monthly_amortization' => 15000, 'months_paid' => 4, 'total_months' => 20, 'status' => 'Good Standing', 'next_due' => '2026-05-15', 'last_payment' => '2026-04-15'],
            ['id' => 'DP-2002', 'buyer' => 'Ramon Bautista', 'block' => '1', 'lot' => '5', 'total_dp' => 450000, 'paid_amount' => 450000, 'monthly_amortization' => 25000, 'months_paid' => 18, 'total_months' => 18, 'status' => 'Fully Paid', 'next_due' => 'N/A', 'last_payment' => '2026-03-10'],
            ['id' => 'DP-2003', 'buyer' => 'Sofia Alcantara', 'block' => '3', 'lot' => '12', 'total_dp' => 240000, 'paid_amount' => 24000, 'monthly_amortization' => 10000, 'months_paid' => 2, 'total_months' => 24, 'status' => 'Delinquent', 'next_due' => '2026-04-05', 'last_payment' => '2026-03-05'],
            ['id' => 'DP-2004', 'buyer' => 'Miguel Cortez', 'block' => '5', 'lot' => '20', 'total_dp' => 360000, 'paid_amount' => 180000, 'monthly_amortization' => 15000, 'months_paid' => 12, 'total_months' => 24, 'status' => 'Good Standing', 'next_due' => '2026-05-20', 'last_payment' => '2026-04-20'],
            ['id' => 'DP-2005', 'buyer' => 'Carla Mendoza', 'block' => '4', 'lot' => '8', 'total_dp' => 600000, 'paid_amount' => 500000, 'monthly_amortization' => 50000, 'months_paid' => 10, 'total_months' => 12, 'status' => 'Good Standing', 'next_due' => '2026-05-10', 'last_payment' => '2026-04-10'],
            ['id' => 'DP-2006', 'buyer' => 'Dennis Chua', 'block' => '7', 'lot' => '1', 'total_dp' => 200000, 'paid_amount' => 0, 'monthly_amortization' => 10000, 'months_paid' => 0, 'total_months' => 20, 'status' => 'Good Standing', 'next_due' => '2026-05-30', 'last_payment' => 'N/A'],
        ];
    }
}



if (!function_exists('getLotStatus')) {
    function getLotStatus() {
        return [
            ['lot' => 'B1 L5', 'managed' => true, 'provider' => 'Subdivision'],
            ['lot' => 'B2 L12', 'managed' => true, 'provider' => 'Subdivision'],
            ['lot' => 'B3 L8', 'managed' => true, 'provider' => 'Subdivision'],
            ['lot' => 'A1 L4', 'managed' => false, 'provider' => 'CASURECO'],
            ['lot' => 'A2 L1', 'managed' => false, 'provider' => 'CASURECO'],
            ['lot' => 'C1 L10', 'managed' => true, 'provider' => 'Subdivision'],
        ];
    }
}

Route::get('/', function () {
    return view('welcome');
});

Route::get('/appointment', function () {
    return view('appointment');
});

Route::get('/login', function () {
    return view('login');
});

if (!function_exists('getVisitorPins')) {
    function getVisitorPins() {
        $base = [
            ['id' => 'VIS-3001', 'pin' => '482910', 'visitor' => 'Alex Mendez', 'host' => 'Juan Dela Cruz', 'block' => '1', 'lot' => '5', 'purpose' => 'Plumbing Repair', 'validity' => 'Today', 'status' => 'Pending'],
            ['id' => 'VIS-3002', 'pin' => '910234', 'visitor' => 'Grab Delivery', 'host' => 'Maria Santos', 'block' => '2', 'lot' => '12', 'purpose' => 'Food Delivery', 'validity' => 'Today', 'status' => 'Entered', 'arrival_time' => '10:15 AM'],
            ['id' => 'VIS-3003', 'pin' => '551029', 'visitor' => 'Sarah Connor', 'host' => 'Ricardo Reyes', 'block' => '3', 'lot' => '8', 'purpose' => 'Family Visit', 'validity' => 'May 25, 2026', 'status' => 'Pending'],
        ];

        // Simulate persistence via session
        $overrides = session('visitor_overrides', []);
        foreach ($base as &$vis) {
            if (isset($overrides[$vis['id']])) {
                $vis = array_merge($vis, $overrides[$vis['id']]);
            }
        }
        return $base;
    }
}

// Guard Security Portal Routes
Route::prefix('guard')->group(function () {
    Route::get('/dashboard', function () {
        return view('guard.dashboard', ['pins' => getVisitorPins(), 'users' => getUsers()]);
    });
    Route::get('/history', function () {
        return view('guard.history', ['pins' => getVisitorPins()]);
    });
});

// Simulation Helper Route
Route::post('/simulate/visitor-entry/{id}', function ($id) {
    $overrides = session('visitor_overrides', []);
    $overrides[$id] = [
        'status' => 'Entered',
        'arrival_time' => date('h:i A'),
        'plate_number' => request('plate_number', 'N/A')
    ];
    session(['visitor_overrides' => $overrides]);
    return response()->json(['success' => true]);
});

Route::post('/simulate/walk-in', function () {
    $overrides = session('visitor_overrides', []);
    $id = 'WALK-' . rand(1000, 9999);
    $overrides[$id] = [
        'id' => $id,
        'visitor' => request('name'),
        'purpose' => request('purpose'),
        'visitor_address' => request('visitor_address', 'N/A'),
        'status' => 'Entered',
        'arrival_time' => date('h:i A'),
        'plate_number' => request('plate_number', 'N/A'),
        'type' => 'Walk-in'
    ];
    session(['visitor_overrides' => $overrides]);
    return response()->json(['success' => true]);
});

if (!function_exists('getUsers')) {
    function getUsers() {
        return [
            // Residents
            ['id' => 'USR-1001', 'name' => 'Juan Dela Cruz', 'role' => 'Resident', 'email' => 'juan@gmail.com', 'status' => 'Active', 'joined' => '2024-01-15', 'meta' => 'Block 1, Lot 5', 'pin' => '884219'],
            ['id' => 'USR-1002', 'name' => 'Maria Santos', 'role' => 'Resident', 'email' => 'maria@gmail.com', 'status' => 'Active', 'joined' => '2024-02-20', 'meta' => 'Block 2, Lot 12', 'pin' => '729104'],
            ['id' => 'USR-1003', 'name' => 'Ricardo Reyes', 'role' => 'Resident', 'email' => 'ricardo@gmail.com', 'status' => 'Active', 'joined' => '2024-03-05', 'meta' => 'Block 3, Lot 8'],
            
            // Security Guards
            ['id' => 'USR-2001', 'name' => 'Sgt. Robert Miller', 'role' => 'Security Guard', 'email' => 'robert.guard@althesa.com', 'status' => 'Active', 'joined' => '2023-10-10', 'meta' => 'Badge #042'],
            ['id' => 'USR-2002', 'name' => 'Officer Jane Doe', 'role' => 'Security Guard', 'email' => 'jane.guard@althesa.com', 'status' => 'Active', 'joined' => '2023-12-05', 'meta' => 'Badge #088'],
            
            // Finance Officer (Single Lead)
            ['id' => 'USR-3001', 'name' => 'CPA Michael Tan', 'role' => 'Finance Officer', 'email' => 'michael.finance@althesa.com', 'status' => 'Active', 'joined' => '2023-11-20', 'meta' => 'Chief Accountant'],
            
            ['id' => 'USR-1004', 'name' => 'Elena Gomez', 'role' => 'Resident', 'email' => 'elena@gmail.com', 'status' => 'Archived', 'joined' => '2023-11-12', 'meta' => 'Block 1, Lot 22'],
        ];
    }
}

if (!function_exists('getAppointments')) {
    function getAppointments() {
        return [
            ['id' => 'APT-1001', 'client' => 'Michael Chen', 'contact' => '0917-555-1021', 'date' => '2026-05-15', 'time' => '10:00 AM', 'status' => 'Pending', 'type' => 'Lot Viewing / Site Visit', 'notes' => 'Interested in corner lot only.'],
            ['id' => 'APT-1002', 'client' => 'Sarah V.', 'contact' => '0918-444-9988', 'date' => '2026-05-18', 'time' => '02:00 PM', 'status' => 'Scheduled', 'type' => 'Pricing & Payment Terms', 'notes' => 'Bringing my architect along to check the terrain.'],
            ['id' => 'APT-1003', 'client' => 'David Ocampo', 'contact' => '0922-333-7766', 'date' => '2026-05-02', 'time' => '11:00 AM', 'status' => 'Completed', 'type' => 'Lot Reservation', 'notes' => 'Ready to pay if we agree on the price.', 'report' => 'Client visited the site and was very impressed with the location. Proceeding to draft the initial reservation contract and will follow up on Monday.'],
            ['id' => 'APT-1004', 'client' => 'Perez Family', 'contact' => '0919-222-5544', 'date' => '2026-05-20', 'time' => '09:00 AM', 'status' => 'Pending', 'type' => 'Lot Viewing / Site Visit', 'notes' => 'Wants near the clubhouse.'],
            ['id' => 'APT-1005', 'client' => 'Luis Martinez', 'contact' => '0917-111-3322', 'date' => '2026-05-10', 'time' => '04:00 PM', 'status' => 'Cancelled', 'type' => 'General Inquiry', 'notes' => 'Client went with another dev.']
        ];
    }
}

// Admin Routes
Route::prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    });

    // User Management System
    Route::get('/users', function () { 
        return view('admin.users.index', ['users' => getUsers()]); 
    });
    Route::get('/residents', function () { 
        return redirect('/admin/users'); 
    });
    Route::get('/billing', function () {
        $bills = getElectricalBills();
        $lots = getLotStatus();
        
        // Consistent Simulated Statistical Data for Graphing (Reliable)
        $yearly_stats = [
            '2025' => [
                'May' => 10200, 'Jun' => 11500, 'Jul' => 12800, 'Aug' => 14000, 
                'Sep' => 13500, 'Oct' => 12000, 'Nov' => 11000, 'Dec' => 15500,
                'Jan' => 16500, 'Feb' => 14000, 'Mar' => 13200, 'Apr' => 15000
            ],
            '2026' => [
                'May' => 11000, 'Jun' => 12500, 'Jul' => 13500, 'Aug' => 14500, 
                'Sep' => 14000, 'Oct' => 13000, 'Nov' => 12500, 'Dec' => 16000,
                'Jan' => 17000, 'Feb' => 15500, 'Mar' => 14200, 'Apr' => 16500
            ],
            'today_paid' => 4550.00
        ];

        return view('admin.billing.index', [
            'bills' => $bills,
            'lots' => $lots,
            'stats' => $yearly_stats
        ]);
    });
    
    Route::get('/water', function () {
        // Reuse identical statistical graphs for simulation 
        $yearly_stats = [
            '2026' => [
                'May' => 3100, 'Jun' => 4500, 'Jul' => 5500, 'Aug' => 6500, 
                'Sep' => 6000, 'Oct' => 5000, 'Nov' => 4500, 'Dec' => 7000,
                'Jan' => 8000, 'Feb' => 7500, 'Mar' => 6200, 'Apr' => 8500
            ],
            'today_paid' => 1250.00
        ];
        return view('admin.water.index', [
            'bills' => getWaterBills(),
            'stats' => $yearly_stats
        ]);
    });

    Route::get('/reservation-fee', function () {
        $yearly_stats = [
            '2026' => [
                'May' => 400000, 'Jun' => 400000, 'Jul' => 400000, 'Aug' => 400000, 
                'Sep' => 400000, 'Oct' => 400000, 'Nov' => 400000, 'Dec' => 400000,
                'Jan' => 400000, 'Feb' => 400000, 'Mar' => 400000, 'Apr' => 400000
            ],
            'today_paid' => 40000.00
        ];
        return view('admin.reservation-fee.index', [
            'bills' => getReservationFees(),
            'stats' => $yearly_stats
        ]);
    });

    Route::get('/downpayment-fee', function () {
        $yearly_stats = [
            '2026' => [
                'May' => 300000, 'Jun' => 300000, 'Jul' => 300000, 'Aug' => 300000, 
                'Sep' => 300000, 'Oct' => 300000, 'Nov' => 300000, 'Dec' => 300000,
                'Jan' => 300000, 'Feb' => 300000, 'Mar' => 300000, 'Apr' => 300000
            ],
            'today_paid' => 15000.00
        ];
        return view('admin.downpayment-fee.index', [
            'bills' => getDownpaymentFees(),
            'stats' => $yearly_stats
        ]);
    });
    Route::get('/appointments', function () { 
        return view('admin.appointments.index', ['appointments' => getAppointments()]); 
    });
    Route::get('/incidents', function () {
        return view('admin.incidents.index', ['incidents' => getIncidents()]);
    });
    Route::get('/incidents/{id}', function ($id) {
        $incident = collect(getIncidents())->firstWhere('id', $id);
        if (!$incident) abort(404);
        return view('admin.incidents.show', ['inc' => $incident, 'id' => $id]);
    });
    Route::get('/announcements', function () {
        return view('admin.announcements.index', ['announcements' => getAnnouncements()]);
    });
    Route::get('/visitors', function () {
        return view('admin.visitors.index', ['pins' => getVisitorPins()]);
    });
    Route::get('/gis', function () { return view('admin.gis.index'); });
});

// Resident Portal Routes
Route::prefix('resident')->group(function () {
    Route::get('/dashboard', function () {
        $elecBills = getElectricalBills();
        $waterBills = getWaterBills();
        $elecBill = collect($elecBills)->firstWhere('resident', 'Juan Dela Cruz');
        $waterBill = collect($waterBills)->firstWhere('resident', 'Juan Dela Cruz');
        return view('resident.dashboard', ['elecBill' => $elecBill, 'waterBill' => $waterBill]);
    });
    Route::get('/profile', function () {
        return view('resident.profile');
    });
    Route::get('/visitors', function () {
        $pins = collect(getVisitorPins())->where('host', 'Juan Dela Cruz')->values()->all();
        return view('resident.visitors.index', ['pins' => $pins]);
    });
    Route::get('/electricity', function () {
        $elecBills = getElectricalBills();
        $elecBill = collect($elecBills)->firstWhere('resident', 'Juan Dela Cruz');
        return view('resident.electricity', ['elecBill' => $elecBill]);
    });
    Route::get('/water', function () {
        $waterBills = getWaterBills();
        $waterBill = collect($waterBills)->firstWhere('resident', 'Juan Dela Cruz');
        return view('resident.water', ['waterBill' => $waterBill]);
    });
    Route::get('/incidents', function () {
        $all = getIncidents();
        // Juan Dela Cruz is Block 1 Lot 5. Filter for him specifically or related lot issues.
        $reports = collect($all)->filter(function ($inc) {
            return $inc['res'] === 'Juan Dela Cruz' || 
                   $inc['res'] === 'Block A, Lot 5' || 
                   ($inc['res'] === 'Anonymous' && str_contains(strtolower($inc['sub']), 'gate 1'));
        })->values()->all();
        return view('resident.incidents', ['incidents' => $reports]);
    });
    Route::post('/incidents', function () {
        return response()->json(['success' => true]);
    });
    Route::get('/notifications', function () {
        return view('resident.notifications');
    });
});

Route::get('/visitor-pin', function () {
    return view('visitor-pin');
});

Route::get('/routing-guide', function () {
    return view('visitor.routing');
});

// Finance Officer Portal Routes
Route::prefix('finance')->group(function () {
    Route::get('/dashboard', function () {
        // Generate mock data for 8 blocks, with 8 houses each
        $houses = [];
        for ($b = 1; $b <= 8; $b++) {
            for ($l = 1; $l <= 8; $l++) {
                // Determine electricity state
                $elec_status = rand(1, 100) > 70 ? 'Billed' : 'Pending';
                $prev_elec = rand(100, 300);
                $curr_elec = $elec_status === 'Billed' ? $prev_elec + rand(10, 50) : null;

                // Determine water state
                $water_status = rand(1, 100) > 80 ? 'Billed' : 'Pending';
                $prev_water = rand(10, 50);
                $curr_water = $water_status === 'Billed' ? $prev_water + rand(2, 10) : null;

                $houses[] = [
                    'block' => $b,
                    'lot' => $l,
                    'elec_status' => $elec_status,
                    'water_status' => $water_status,
                    'prev_elec' => $prev_elec,
                    'curr_elec' => $curr_elec,
                    'prev_water' => $prev_water,
                    'curr_water' => $curr_water,
                    'resident' => 'Resident ' . chr(64 + $b) . $l
                ];
            }
        }

        return view('finance.dashboard', ['houses' => $houses]);
    });
});

/* =========================================================================
   Live Gmail Email Dispatch API Routes
   ========================================================================= */
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

Route::post('/api/send-appointment-email', function (Request $request) {
    $email = $request->input('email', 'eighty6pharmacy@gmail.com');
    $name = $request->input('name', 'Valued Client');
    $date = $request->input('date', date('Y-m-d'));
    $time = $request->input('time', '10:00 AM');
    $type = $request->input('type', 'Site Viewing & Consultation');
    $notes = $request->input('notes', 'No notes provided.');

    try {
        Mail::send([], [], function ($message) use ($email, $name, $date, $time, $type, $notes) {
            $message->to($email)
                ->subject('📅 Appointment Received — Althesa Subdivision')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                        <div style='background: #0f172a; padding: 20px; border-radius: 12px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Althesa Subdivision</h2>
                            <p style='color: #94a3b8; margin: 4px 0 0 0; font-size: 13px;'>Appointment Confirmation Request</p>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #0f172a;'>Dear <strong>{$name}</strong>,</p>
                            <p style='color: #475569;'>Thank you for scheduling an appointment with Althesa Subdivision! We have received your request and your booking details are below:</p>
                            <div style='background: #f8fafc; padding: 16px; border-radius: 12px; border-left: 4px solid #10b981; margin: 20px 0;'>
                                <p style='margin: 4px 0;'><strong>📅 Date:</strong> {$date}</p>
                                <p style='margin: 4px 0;'><strong>⏰ Time:</strong> {$time}</p>
                                <p style='margin: 4px 0;'><strong>📋 Inquiry Type:</strong> {$type}</p>
                                <p style='margin: 4px 0;'><strong>📝 Notes:</strong> {$notes}</p>
                            </div>
                            <p style='color: #475569;'>Our administration team will review your booking shortly. If approved, you will receive a follow-up email with your Gate Viewing Access PIN.</p>
                        </div>
                        <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Althesa Subdivision Management Office &bull; Contact: +63 (02) 8912-3456
                        </div>
                    </div>
                ");
        });
        return response()->json(['success' => true, 'message' => 'Confirmation email sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::post('/api/send-approval-email', function (Request $request) {
    $email = $request->input('email', 'eighty6pharmacy@gmail.com');
    $name = $request->input('name', 'Valued Client');
    $pin = $request->input('pin', rand(100000, 999999));
    $date = $request->input('date', date('Y-m-d'));

    try {
        Mail::send([], [], function ($message) use ($email, $name, $pin, $date) {
            $message->to($email)
                ->subject('🎉 Appointment Approved & Gate Access PIN — Althesa Subdivision')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                        <div style='background: #059669; padding: 20px; border-radius: 12px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Althesa Subdivision</h2>
                            <p style='color: #ecfdf5; margin: 4px 0 0 0; font-size: 13px;'>Appointment Approved</p>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #0f172a;'>Hello <strong>{$name}</strong>,</p>
                            <p style='color: #475569;'>Great news! Your appointment request for <strong>{$date}</strong> has been approved by the subdivision management office.</p>
                            <div style='background: #ecfdf5; border: 2px dashed #059669; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0;'>
                                <span style='font-size: 13px; color: #047857; text-transform: uppercase; font-weight: 700;'>Your Gate Entry Viewing PIN</span>
                                <div style='font-size: 36px; font-weight: 800; color: #047857; letter-spacing: 6px; margin-top: 8px;'>{$pin}</div>
                            </div>
                            <p style='color: #475569;'>Please present this 6-digit PIN to the security guard at Gate 1 upon your arrival.</p>
                        </div>
                        <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Althesa Subdivision Security & Management
                        </div>
                    </div>
                ");
        });
        return response()->json(['success' => true, 'message' => 'Approval email with PIN sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::post('/api/send-cancellation-email', function (Request $request) {
    $email = $request->input('email', 'eighty6pharmacy@gmail.com');
    $name = $request->input('name', 'Valued Client');
    $reason = $request->input('reason', 'Schedule conflict / fully booked.');

    try {
        Mail::send([], [], function ($message) use ($email, $name, $reason) {
            $message->to($email)
                ->subject('⚠️ Appointment Cancellation Notice — Althesa Subdivision')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                        <div style='background: #dc2626; padding: 20px; border-radius: 12px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Althesa Subdivision</h2>
                            <p style='color: #fef2f2; margin: 4px 0 0 0; font-size: 13px;'>Appointment Status Update</p>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #0f172a;'>Dear <strong>{$name}</strong>,</p>
                            <p style='color: #475569;'>We regret to inform you that your appointment request has been cancelled by the administration.</p>
                            <div style='background: #fef2f2; padding: 16px; border-radius: 12px; border-left: 4px solid #dc2626; margin: 20px 0;'>
                                <p style='margin: 0; color: #991b1b;'><strong>Reason:</strong> {$reason}</p>
                            </div>
                            <p style='color: #475569;'>If you would like to reschedule, please visit our website and submit a new booking request.</p>
                        </div>
                        <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Althesa Subdivision Management Office
                        </div>
                    </div>
                ");
        });
        return response()->json(['success' => true, 'message' => 'Cancellation notice sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::post('/api/send-visitor-pin-email', function (Request $request) {
    $email = $request->input('email', 'eighty6pharmacy@gmail.com');
    $visitorName = $request->input('visitor_name', 'Guest Visitor');
    $residentName = $request->input('resident_name', 'Resident Host');
    $pin = $request->input('pin', rand(100000, 999999));
    $date = $request->input('date', date('Y-m-d'));

    try {
        Mail::send([], [], function ($message) use ($email, $visitorName, $residentName, $pin, $date) {
            $message->to($email)
                ->subject('🎟️ Visitor Gate Entry Pass PIN — Althesa Subdivision')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                        <div style='background: #0284c7; padding: 20px; border-radius: 12px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Althesa Gate Security</h2>
                            <p style='color: #e0f2fe; margin: 4px 0 0 0; font-size: 13px;'>Visitor Authorization Code</p>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #0f172a;'>Hello <strong>{$visitorName}</strong>,</p>
                            <p style='color: #475569;'>Your visitor pass requested by <strong>{$residentName}</strong> for visit date <strong>{$date}</strong> has been approved!</p>
                            <div style='background: #e0f2fe; border: 2px dashed #0284c7; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0;'>
                                <span style='font-size: 13px; color: #0369a1; text-transform: uppercase; font-weight: 700;'>6-Digit Gate Entry PIN</span>
                                <div style='font-size: 36px; font-weight: 800; color: #0369a1; letter-spacing: 6px; margin-top: 8px;'>{$pin}</div>
                            </div>
                            <p style='color: #475569;'>Show this PIN to the security guard at Gate Control upon arrival for instant validation.</p>
                        </div>
                        <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Althesa Subdivision Gate Security System
                        </div>
                    </div>
                ");
        });
        return response()->json(['success' => true, 'message' => 'Visitor pass PIN email sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::post('/api/send-billing-warning-email', function (Request $request) {
    $email = $request->input('email', 'eighty6pharmacy@gmail.com');
    $residentName = $request->input('resident_name', 'Resident');
    $billId = $request->input('bill_id', 'EB-002');
    $amount = $request->input('amount', '2,100.00');

    try {
        Mail::send([], [], function ($message) use ($email, $residentName, $billId, $amount) {
            $message->to($email)
                ->subject('⚡ Urgent Notice: Past Due Utility Bill Warning — Althesa Subdivision')
                ->html("
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 16px; background: #ffffff;'>
                        <div style='background: #b91c1c; padding: 20px; border-radius: 12px; text-align: center;'>
                            <h2 style='color: #ffffff; margin: 0;'>Althesa Billing Office</h2>
                            <p style='color: #fee2e2; margin: 4px 0 0 0; font-size: 13px;'>Past Due Disconnection Warning</p>
                        </div>
                        <div style='padding: 20px 0;'>
                            <p style='font-size: 16px; color: #0f172a;'>Dear <strong>{$residentName}</strong>,</p>
                            <p style='color: #475569;'>This is an official notice regarding your past due account statement <strong>{$billId}</strong> in the amount of <strong>₱{$amount}</strong>.</p>
                            <div style='background: #fee2e2; padding: 16px; border-radius: 12px; border-left: 4px solid #b91c1c; margin: 20px 0;'>
                                <p style='margin: 0; color: #991b1b; font-weight: 700;'>⚠️ Action Required within 48 Hours</p>
                                <p style='margin: 6px 0 0 0; color: #7f1d1d; font-size: 13px;'>Please settle your balance at the Subdivision Administration Office or via GCash/Online Banking to prevent service disconnection.</p>
                            </div>
                        </div>
                        <div style='border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 12px; color: #94a3b8; text-align: center;'>
                            Althesa Subdivision Treasury & Financial Office
                        </div>
                    </div>
                ");
        });
        return response()->json(['success' => true, 'message' => 'Billing warning email sent successfully!']);
    } catch (\Exception $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});
