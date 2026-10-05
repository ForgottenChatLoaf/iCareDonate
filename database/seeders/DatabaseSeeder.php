<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Donation;
use App\Models\DonationImage;
use App\Models\Faq;
use App\Models\Notification;
use App\Models\ResourceMatch;
use App\Models\ResourceRequest;
use App\Models\SupportTicket;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Admin User
        $admin = User::create([
            'name' => 'System Administrator',
            'first_name' => 'System',
            'last_name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@icaredonate.org',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'status' => 'active',
            'phone' => '+63 917 123 4567',
            'barangay' => 'San Lorenzo',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'email_verified_at' => now(),
            'last_login_at' => now(),
        ]);

        // 2. Create Regular Users
        $donor1 = User::create([
            'name' => 'Maria Elena Santos',
            'first_name' => 'Maria Elena',
            'last_name' => 'Santos',
            'username' => 'maria.santos',
            'email' => 'maria@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'status' => 'active',
            'phone' => '+63 920 456 7890',
            'address' => '12 Acacia Street',
            'barangay' => 'Bel-Air',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'email_verified_at' => now(),
            'last_login_at' => now()->subHours(2),
        ]);

        $requester1 = User::create([
            'name' => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'username' => 'juan.delacruz',
            'email' => 'juan@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'status' => 'active',
            'phone' => '+63 918 876 5432',
            'address' => '45 Bangkal Extension',
            'barangay' => 'Bangkal',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'email_verified_at' => now(),
            'last_login_at' => now()->subDay(),
        ]);

        $donor2 = User::create([
            'name' => 'Elena Ramos',
            'first_name' => 'Elena',
            'last_name' => 'Ramos',
            'username' => 'elena.ramos',
            'email' => 'elena@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'status' => 'active',
            'phone' => '+63 915 222 3344',
            'address' => 'Block 4 Lot 8 St. Jude Subd.',
            'barangay' => 'Poblacion',
            'city' => 'Pasig',
            'province' => 'Metro Manila',
            'email_verified_at' => now(),
        ]);

        $requester2 = User::create([
            'name' => 'Carlos Mendoza',
            'first_name' => 'Carlos',
            'last_name' => 'Mendoza',
            'username' => 'carlos.mendoza',
            'email' => 'carlos@example.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'status' => 'active',
            'phone' => '+63 927 999 1122',
            'address' => '78 Riverside Drive',
            'barangay' => 'Maybunga',
            'city' => 'Pasig',
            'province' => 'Metro Manila',
            'email_verified_at' => now(),
        ]);

        // 3. Seed Categories
        $categoriesData = [
            [
                'name' => 'Food & Nutrition',
                'slug' => 'food',
                'description' => 'Non-perishable canned food, rice grains, noodles, powdered milk, and infant formula.',
                'icon' => 'utensils',
                'sort_order' => 1,
            ],
            [
                'name' => 'School Supplies',
                'slug' => 'school-supplies',
                'description' => 'Backpacks, notebooks, writing materials, textbooks, calculators, and art supplies for students.',
                'icon' => 'book-open',
                'sort_order' => 2,
            ],
            [
                'name' => 'Clothing & Wear',
                'slug' => 'clothing',
                'description' => 'Clean wearable clothes, jackets, uniforms, baby clothes, and everyday footwear.',
                'icon' => 'shirt',
                'sort_order' => 3,
            ],
            [
                'name' => 'Medical & Health',
                'slug' => 'medical',
                'description' => 'First aid kits, blood pressure monitors, crutches, wheelchairs, masks, and sanitizers.',
                'icon' => 'heart-pulse',
                'sort_order' => 4,
            ],
            [
                'name' => 'Household & Hygiene',
                'slug' => 'household',
                'description' => 'Beddings, blankets, hygiene soap kits, cookware, solar emergency lamps, and mats.',
                'icon' => 'home',
                'sort_order' => 5,
            ],
            [
                'name' => 'Educational Electronics',
                'slug' => 'electronics',
                'description' => 'Working refurbished laptops, study tablets, scientific calculators, and charging kits.',
                'icon' => 'laptop',
                'sort_order' => 6,
            ],
        ];

        $categories = [];
        foreach ($categoriesData as $data) {
            $cat = Category::create($data);
            $categories[$data['slug']] = $cat;
        }

        // 4. Seed Donations
        $donation1 = Donation::create([
            'donor_id' => $donor1->id,
            'category_id' => $categories['school-supplies']->id,
            'title' => 'Complete Elementary School Supply Packs (50 Sets)',
            'description' => 'Brand new elementary supply sets with 5 notebooks each, pens, pencils, eraser, ruler, and a durable backpack. Ready for students in need.',
            'quantity_total' => 50,
            'quantity_reserved' => 10,
            'quantity_claimed' => 0,
            'unit' => 'sets',
            'condition_type' => 'new',
            'expiration_date' => null,
            'pickup_method' => 'both',
            'pickup_address' => 'Makati Community Center, Ground Floor',
            'pickup_barangay' => 'Bel-Air',
            'pickup_city' => 'Makati',
            'pickup_province' => 'Metro Manila',
            'pickup_notes' => 'Available Monday to Friday, 9:00 AM - 4:00 PM. Look for Guard desk or call donor.',
            'pickup_schedule' => 'Mon-Fri 9AM-4PM',
            'donor_name' => $donor1->name,
            'contact_number' => $donor1->phone,
            'status' => 'approved',
            'submitted_at' => now()->subDays(5),
            'approved_at' => now()->subDays(4),
            'approved_by' => $admin->id,
        ]);

        $donation2 = Donation::create([
            'donor_id' => $donor1->id,
            'category_id' => $categories['food']->id,
            'title' => 'Assorted Canned Goods & Premium Rice Sacks (25kg)',
            'description' => '10 sacks of 25kg quality whole grain rice, plus 120 cans of tuna and corned beef. Sealed and strictly non-expired.',
            'quantity_total' => 10,
            'quantity_reserved' => 2,
            'quantity_claimed' => 0,
            'unit' => 'sacks',
            'condition_type' => 'new',
            'expiration_date' => Carbon::now()->addMonths(6)->toDateString(),
            'pickup_method' => 'pickup',
            'pickup_address' => 'Community Pantry Warehouse, 88 Buendia Ave',
            'pickup_barangay' => 'Pio Del Pilar',
            'pickup_city' => 'Makati',
            'pickup_province' => 'Metro Manila',
            'pickup_notes' => 'Best to bring a sturdy vehicle for loading the 25kg sacks.',
            'pickup_schedule' => 'Tuesdays and Thursdays 10AM-3PM',
            'donor_name' => $donor1->name,
            'contact_number' => $donor1->phone,
            'status' => 'approved',
            'submitted_at' => now()->subDays(3),
            'approved_at' => now()->subDays(2),
            'approved_by' => $admin->id,
        ]);

        $donation3 = Donation::create([
            'donor_id' => $donor2->id,
            'category_id' => $categories['medical']->id,
            'title' => 'Hospital Grade Adult Wheelchairs & Walking Aids',
            'description' => 'Two foldable adult wheelchairs in like-new condition, thoroughly cleaned and sanitized, plus 4 pairs of adjustable aluminum crutches.',
            'quantity_total' => 6,
            'quantity_reserved' => 0,
            'quantity_claimed' => 0,
            'unit' => 'units',
            'condition_type' => 'like_new',
            'expiration_date' => null,
            'pickup_method' => 'pickup',
            'pickup_address' => 'Pasig City Medical Depot',
            'pickup_barangay' => 'Poblacion',
            'pickup_city' => 'Pasig',
            'pickup_province' => 'Metro Manila',
            'pickup_notes' => 'Direct coordinate via SMS before arriving.',
            'pickup_schedule' => 'Mon-Sat 1PM-5PM',
            'donor_name' => $donor2->name,
            'contact_number' => $donor2->phone,
            'status' => 'approved',
            'submitted_at' => now()->subDays(2),
            'approved_at' => now()->subDay(),
            'approved_by' => $admin->id,
        ]);

        $donation4 = Donation::create([
            'donor_id' => $donor2->id,
            'category_id' => $categories['clothing']->id,
            'title' => 'Clean Youth Jackets & Raincoats (30 pcs)',
            'description' => 'Gently used clean jackets, windbreakers, and raincoats for children ages 6-14. Individually bagged and laundered.',
            'quantity_total' => 30,
            'quantity_reserved' => 0,
            'quantity_claimed' => 0,
            'unit' => 'pcs',
            'condition_type' => 'good',
            'expiration_date' => null,
            'pickup_method' => 'both',
            'pickup_address' => 'Barangay Hall Multi-purpose Desk',
            'pickup_barangay' => 'Maybunga',
            'pickup_city' => 'Pasig',
            'pickup_province' => 'Metro Manila',
            'pickup_notes' => 'Look for Kagawad On-Duty.',
            'pickup_schedule' => 'Daily 8AM-5PM',
            'donor_name' => $donor2->name,
            'contact_number' => $donor2->phone,
            'status' => 'pending_verification',
            'submitted_at' => now()->subHours(6),
        ]);

        // 5. Seed Requests
        $request1 = ResourceRequest::create([
            'requester_id' => $requester1->id,
            'donation_id' => $donation1->id,
            'category_id' => $categories['school-supplies']->id,
            'item_name' => 'Elementary School Supply Packs',
            'quantity_requested' => 10,
            'quantity_matched' => 10,
            'purpose' => 'For 10 underprivileged elementary school pupils in our barangay tutorial center whose parents lost jobs during recent factory relocations.',
            'urgency_level' => 'high',
            'status' => 'approved',
            'contact_number' => $requester1->phone,
            'delivery_address' => '45 Bangkal Extension, Makati City',
            'submitted_at' => now()->subDays(3),
            'reviewed_at' => now()->subDays(2),
            'reviewed_by' => $admin->id,
            'admin_notes' => 'Valid barangay certificate and pupil masterlist verified.',
        ]);

        $request2 = ResourceRequest::create([
            'requester_id' => $requester2->id,
            'donation_id' => $donation2->id,
            'category_id' => $categories['food']->id,
            'item_name' => 'Rice Sacks for Community Calamity Relief',
            'quantity_requested' => 2,
            'quantity_matched' => 2,
            'purpose' => 'Emergency ration pack preparation for 15 riverside families evacuated due to flash flooding.',
            'urgency_level' => 'high',
            'status' => 'approved',
            'contact_number' => $requester2->phone,
            'delivery_address' => 'Evacuation Staging Site, Maybunga, Pasig',
            'submitted_at' => now()->subDays(2),
            'reviewed_at' => now()->subDay(),
            'reviewed_by' => $admin->id,
            'admin_notes' => 'Evacuation incident verified with barangay disaster council.',
        ]);

        $request3 = ResourceRequest::create([
            'requester_id' => $requester1->id,
            'donation_id' => $donation3->id,
            'category_id' => $categories['medical']->id,
            'item_name' => 'Adult Wheelchair',
            'quantity_requested' => 1,
            'quantity_matched' => 0,
            'purpose' => 'For an 72-year-old stroke patient requiring daily mobility therapy.',
            'urgency_level' => 'medium',
            'status' => 'submitted',
            'contact_number' => $requester1->phone,
            'delivery_address' => '45 Bangkal Extension, Makati City',
            'submitted_at' => now()->subHours(8),
        ]);

        // 6. Seed Matches & Transactions
        $match1 = ResourceMatch::create([
            'donation_id' => $donation1->id,
            'request_id' => $request1->id,
            'created_by' => $admin->id,
            'quantity' => 10,
            'match_notes' => 'Allocated 10 school supply packs from donation #1.',
            'status' => 'active',
        ]);

        $claimCode1 = 'CLAIM-' . strtoupper(Str::random(6));
        $transaction1 = Transaction::create([
            'match_id' => $match1->id,
            'donation_id' => $donation1->id,
            'request_id' => $request1->id,
            'donor_id' => $donor1->id,
            'requester_id' => $requester1->id,
            'quantity' => 10,
            'claim_code' => $claimCode1,
            'status' => 'pending_claim',
            'pickup_method' => 'pickup',
            'pickup_date' => Carbon::now()->addDays(2)->toDateString(),
            'pickup_time' => '10:00 AM',
            'pickup_address' => 'Makati Community Center, Ground Floor, Bel-Air, Makati',
            'claim_deadline' => Carbon::now()->addDays(5),
            'admin_notes' => 'Transaction generated. Claim slip issued.',
        ]);

        // 7. Seed Notifications
        Notification::create([
            'user_id' => $donor1->id,
            'type' => 'DONATION_APPROVED',
            'title' => 'Donation Approved!',
            'message' => 'Your donation "Complete Elementary School Supply Packs" has been approved and is now visible to the public.',
            'related_type' => 'donation',
            'related_id' => $donation1->id,
            'is_read' => true,
            'read_at' => now()->subDays(4),
            'created_at' => now()->subDays(4),
        ]);

        Notification::create([
            'user_id' => $requester1->id,
            'type' => 'MATCH_CREATED',
            'title' => 'Resource Request Matched!',
            'message' => "Your request for 10 school packs has been matched. Claim Code: {$claimCode1}. Please claim before the deadline.",
            'related_type' => 'transaction',
            'related_id' => $transaction1->id,
            'is_read' => false,
            'created_at' => now()->subDays(2),
        ]);

        // 8. Seed FAQs
        $faqs = [
            [
                'category' => 'General',
                'question' => 'What is iCareDonate?',
                'answer' => 'iCareDonate is a verified, community-driven resource sharing platform connecting generous donors of physical items with individuals and organizations in need. We do not accept or process direct cash donations.',
                'sort_order' => 1,
            ],
            [
                'category' => 'Donating',
                'question' => 'How does the donation verification process work?',
                'answer' => 'When you submit a donation, our administrators verify item condition, quantity, validity, and safety guidelines. Once approved, your donation is listed on the Browse page for verified requesters.',
                'sort_order' => 2,
            ],
            [
                'category' => 'Requesting',
                'question' => 'Who can request donations on the platform?',
                'answer' => 'Any verified user who provides genuine proof of need, reason, and valid contact details. Requests undergo administrative review and matching to ensure resources reach those who need them most.',
                'sort_order' => 3,
            ],
            [
                'category' => 'Claims & Pickup',
                'question' => 'How does claiming a resource work?',
                'answer' => 'Once an administrator matches your request with a donation, a unique Claim Code and pickup schedule are generated. Present this code at the designated location to receive your item.',
                'sort_order' => 4,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }

        // 9. Seed Audit Logs
        AuditLog::create([
            'actor_id' => $admin->id,
            'actor_name' => 'System Administrator',
            'actor_role' => 'admin',
            'action' => 'SYSTEM_INITIALIZED',
            'target_type' => 'system',
            'target_id' => 1,
            'details' => 'Platform initialized with initial categories, verified demo donations, and users.',
            'ip_address' => '127.0.0.1',
        ]);

        AuditLog::create([
            'actor_id' => $admin->id,
            'actor_name' => 'System Administrator',
            'actor_role' => 'admin',
            'action' => 'DONATION_APPROVED',
            'target_type' => 'donation',
            'target_id' => $donation1->id,
            'details' => 'Admin verified and approved donation #1: Elementary School Supply Packs.',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
