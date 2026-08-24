<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Incident;
use App\Models\Visitor;
use App\Models\Announcement;
use App\Models\Appointment;
use App\Models\User;

class PhaseFourSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Announcements
        $announcements = [
            ['title' => 'Annual General Meeting 2026', 'category' => 'Event', 'status' => 'published', 'date' => '2026-04-15', 'content' => 'Join us for the AGM to discuss the subdivision\'s budget and infrastructure plans.', 'author' => 'Board Secretary'],
            ['title' => 'Emergency Water Maintenance', 'category' => 'Emergency', 'status' => 'published', 'date' => '2026-04-05', 'content' => 'Urgent water pipe repair starting at 10:00 PM. Expect water interruption for 4 hours.', 'author' => 'Maintenance Admin'],
            ['title' => 'Garbage Collection Schedule Change', 'category' => 'Maintenance', 'status' => 'scheduled', 'date' => '2026-04-10', 'content' => 'Standard pickup will move to 8:00 AM instead of 7:00 AM starting next week.', 'author' => 'Sanitation Head'],
            ['title' => 'Summer Pool Party 2026', 'category' => 'Event', 'status' => 'draft', 'date' => '2026-05-01', 'content' => 'Get ready for our annual subdivision pool party! Details to follow.', 'author' => 'Social Committee'],
            ['title' => 'New Security Protocols', 'category' => 'General', 'status' => 'published', 'date' => '2026-03-30', 'content' => 'Please note that new QR code scans will be required for all guest vehicles.', 'author' => 'Security Chief'],
        ];

        foreach ($announcements as $a) {
            Announcement::create([
                'title' => $a['title'],
                'category' => $a['category'],
                'content' => $a['content'],
                'author' => $a['author'],
                'status' => $a['status'],
                'publish_date' => $a['date'],
            ]);
        }

        // 2. Seed Appointments
        $appointments = [
            ['client' => 'Michael Chen', 'contact' => '0917-555-1021', 'date' => '2026-05-15', 'time' => '10:00 AM', 'status' => 'Pending', 'type' => 'Lot Viewing / Site Visit', 'notes' => 'Interested in corner lot only.', 'report' => null],
            ['client' => 'Sarah V.', 'contact' => '0918-444-9988', 'date' => '2026-05-18', 'time' => '02:00 PM', 'status' => 'Scheduled', 'type' => 'Pricing & Payment Terms', 'notes' => 'Bringing my architect along to check the terrain.', 'report' => null],
            ['client' => 'David Ocampo', 'contact' => '0922-333-7766', 'date' => '2026-05-02', 'time' => '11:00 AM', 'status' => 'Completed', 'type' => 'Lot Reservation', 'notes' => 'Ready to pay if we agree on the price.', 'report' => 'Client visited the site and was very impressed with the location. Proceeding to draft the initial reservation contract and will follow up on Monday.'],
            ['client' => 'Perez Family', 'contact' => '0919-222-5544', 'date' => '2026-05-20', 'time' => '09:00 AM', 'status' => 'Pending', 'type' => 'Lot Viewing / Site Visit', 'notes' => 'Wants near the clubhouse.', 'report' => null],
            ['client' => 'Luis Martinez', 'contact' => '0917-111-3322', 'date' => '2026-05-10', 'time' => '04:00 PM', 'status' => 'Cancelled', 'type' => 'General Inquiry', 'notes' => 'Client went with another dev.', 'report' => null]
        ];

        foreach ($appointments as $apt) {
            Appointment::create([
                'client_name' => $apt['client'],
                'contact_number' => $apt['contact'],
                'type' => $apt['type'],
                'date' => $apt['date'],
                'time' => $apt['time'],
                'notes' => $apt['notes'],
                'report' => $apt['report'],
                'status' => $apt['status'],
            ]);
        }

        // 3. Seed Incidents
        $incidents = [
            ['sub' => 'Streetlight Issue - Gate 2', 'type' => 'Maintenance', 'desc' => 'Flickering street light at entrance since last night. Safety hazard.', 'img' => 'https://media.istockphoto.com/id/157291129/photo/snapped-power-pole.jpg?s=612x612&w=0&k=20&c=Nl5fTf4y_uWlS5Q4A1_M8Jd3qI1eXWv_d7d1HnO1S8I=', 'status' => 'pending'],
            ['sub' => 'Water Leak Near Clubhouse', 'type' => 'Utilities', 'desc' => 'Large puddle forming on the sidewalk near the pool entrance.', 'img' => 'https://images.unsplash.com/photo-1542013976693-6b573b925b4b', 'status' => 'progress'],
            ['sub' => 'Loud Noise - Phase 2', 'type' => 'Noise', 'desc' => 'Loud music past 11 PM near Lot 45. Disruption of peace.', 'img' => 'https://plus.unsplash.com/premium_photo-1661605330310-09a15ab2aece', 'status' => 'resolved'],
            ['sub' => 'Stray Dogs Near Playground', 'type' => 'Pet Warning', 'desc' => 'Several aggressive stray dogs spotted near the children\'s park.', 'img' => 'https://images.unsplash.com/photo-1544568100-847a948585b9', 'status' => 'pending'],
            ['sub' => 'Broken Bench in Park', 'type' => 'Amenities', 'desc' => 'One of the park benches is loose and unsafe for residents.', 'img' => 'https://images.unsplash.com/photo-1518173946687-a4c8a9b749f5', 'status' => 'progress'],
        ];

        // Assign all incidents to Juan Dela Cruz for testing
        $juan = User::where('name', 'Juan Dela Cruz')->first();

        foreach ($incidents as $inc) {
            Incident::create([
                'user_id' => $juan ? $juan->id : null,
                'subject' => $inc['sub'],
                'type' => $inc['type'],
                'description' => $inc['desc'],
                'image_url' => $inc['img'],
                'status' => $inc['status'],
            ]);
        }

        // 4. Seed Visitors
        $visitors = [
            ['visitor' => 'Alex Mendez', 'host' => 'Juan Dela Cruz', 'purpose' => 'Plumbing Repair', 'validity' => 'Today', 'status' => 'Pending', 'arrival_time' => null],
            ['visitor' => 'Grab Delivery', 'host' => 'Maria Santos', 'purpose' => 'Food Delivery', 'validity' => 'Today', 'status' => 'Entered', 'arrival_time' => '10:15 AM'],
            ['visitor' => 'Sarah Connor', 'host' => 'Ricardo Reyes', 'purpose' => 'Family Visit', 'validity' => 'May 25, 2026', 'status' => 'Pending', 'arrival_time' => null],
        ];

        foreach ($visitors as $v) {
            $host = User::where('name', $v['host'])->first();
            Visitor::create([
                'host_id' => $host ? $host->id : null,
                'visitor_name' => $v['visitor'],
                'pin' => (string)rand(100000, 999999),
                'purpose' => $v['purpose'],
                'validity' => $v['validity'],
                'arrival_time' => $v['arrival_time'],
                'status' => $v['status'],
                'plate_number' => null,
                'type' => 'Pre-registered',
            ]);
        }
    }
}
