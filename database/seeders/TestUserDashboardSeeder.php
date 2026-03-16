<?php

namespace Database\Seeders;

use App\Models\CartItem;
use App\Models\ClientNotification;
use App\Models\Lead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\UserLead;
use App\Enums\AcquisitionMode;
use App\Enums\AcquisitionType;
use App\Enums\ContactStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PurchaseMode;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TestUserDashboardSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'test@qualeadfied.com')->firstOrFail();
        $userId = $user->id;

        // Get some leads for our test data
        $freeLeads = Lead::where('status', 'free')->limit(20)->get();
        $allLeads = Lead::limit(40)->get();

        // =====================================================================
        // 1. ORDERS (5 orders: 3 paid, 1 completed, 1 pending)
        // =====================================================================

        // Order 1 - Paid, 2 months ago
        $order1 = Order::create([
            'user_id' => $userId,
            'order_number' => 'ORD-2026-00001',
            'type' => OrderType::Single,
            'payment_method' => PaymentMethod::Card,
            'subtotal' => 105.00,
            'vat_rate' => 22,
            'vat_amount' => 23.10,
            'total' => 128.10,
            'status' => OrderStatus::Paid,
            'billing_snapshot' => [
                'company_name' => 'Test Company Srl',
                'vat_number' => 'IT12345678901',
                'address' => 'Via Roma 1',
                'city' => 'Milano',
                'province' => 'MI',
                'zip' => '20100',
                'country' => 'IT',
                'sdi_code' => null,
                'pec_email' => null,
            ],
            'payment_id' => 'pi_test_' . md5('order1'),
            'paid_at' => now()->subDays(60),
            'created_at' => now()->subDays(60),
        ]);

        // Order items for order 1 (3 leads)
        foreach ($allLeads->slice(0, 3) as $lead) {
            OrderItem::create([
                'order_id' => $order1->id,
                'lead_id' => $lead->id,
                'acquisition_mode' => AcquisitionMode::Exclusive,
                'unit_price' => 35.00,
                'quantity' => 1,
                'line_total' => 35.00,
            ]);
        }

        // Order 2 - Paid, 3 weeks ago
        $order2 = Order::create([
            'user_id' => $userId,
            'order_number' => 'ORD-2026-00002',
            'type' => OrderType::Single,
            'payment_method' => PaymentMethod::Card,
            'subtotal' => 60.00,
            'vat_rate' => 22,
            'vat_amount' => 13.20,
            'total' => 73.20,
            'status' => OrderStatus::Paid,
            'billing_snapshot' => $order1->billing_snapshot,
            'payment_id' => 'pi_test_' . md5('order2'),
            'paid_at' => now()->subDays(21),
            'created_at' => now()->subDays(21),
        ]);

        foreach ($allLeads->slice(3, 4) as $lead) {
            OrderItem::create([
                'order_id' => $order2->id,
                'lead_id' => $lead->id,
                'acquisition_mode' => AcquisitionMode::Shared,
                'unit_price' => 15.00,
                'quantity' => 1,
                'line_total' => 15.00,
            ]);
        }

        // Order 3 - Paid, 5 days ago (this month)
        $order3 = Order::create([
            'user_id' => $userId,
            'order_number' => 'ORD-2026-00003',
            'type' => OrderType::Single,
            'payment_method' => PaymentMethod::Sepa,
            'subtotal' => 70.00,
            'vat_rate' => 22,
            'vat_amount' => 15.40,
            'total' => 85.40,
            'status' => OrderStatus::Paid,
            'billing_snapshot' => $order1->billing_snapshot,
            'payment_id' => 'pi_test_' . md5('order3'),
            'paid_at' => now()->subDays(5),
            'created_at' => now()->subDays(5),
        ]);

        foreach ($allLeads->slice(7, 2) as $lead) {
            OrderItem::create([
                'order_id' => $order3->id,
                'lead_id' => $lead->id,
                'acquisition_mode' => AcquisitionMode::Exclusive,
                'unit_price' => 35.00,
                'quantity' => 1,
                'line_total' => 35.00,
            ]);
        }

        // Order 4 - Completed (free trial), 6 weeks ago
        $order4 = Order::create([
            'user_id' => $userId,
            'order_number' => 'ORD-2026-00004',
            'type' => OrderType::FreeTrial,
            'payment_method' => PaymentMethod::Card,
            'subtotal' => 0,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => 0,
            'status' => OrderStatus::Completed,
            'billing_snapshot' => null,
            'paid_at' => now()->subDays(42),
            'created_at' => now()->subDays(42),
        ]);

        OrderItem::create([
            'order_id' => $order4->id,
            'lead_id' => $allLeads[9]->id,
            'acquisition_mode' => AcquisitionMode::Shared,
            'unit_price' => 0,
            'quantity' => 1,
            'line_total' => 0,
        ]);

        // Order 5 - Pending (today)
        $order5 = Order::create([
            'user_id' => $userId,
            'order_number' => 'ORD-2026-00005',
            'type' => OrderType::Single,
            'payment_method' => PaymentMethod::Card,
            'subtotal' => 45.00,
            'vat_rate' => 22,
            'vat_amount' => 9.90,
            'total' => 54.90,
            'status' => OrderStatus::Pending,
            'billing_snapshot' => $order1->billing_snapshot,
            'payment_id' => 'pi_test_' . md5('order5'),
            'created_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order5->id,
            'lead_id' => $allLeads[10]->id,
            'acquisition_mode' => AcquisitionMode::Exclusive,
            'unit_price' => 45.00,
            'quantity' => 1,
            'line_total' => 45.00,
        ]);

        // =====================================================================
        // 2. USER LEADS (10 purchased leads with various statuses)
        // =====================================================================

        $contactStatuses = [
            ContactStatus::New,
            ContactStatus::Contacted,
            ContactStatus::InProgress,
            ContactStatus::Converted,
            ContactStatus::NotInterested,
            ContactStatus::New,
            ContactStatus::Contacted,
            ContactStatus::InProgress,
            ContactStatus::New,
            ContactStatus::Converted,
        ];

        $acquisitionTypes = [
            AcquisitionType::Exclusive,
            AcquisitionType::Exclusive,
            AcquisitionType::Shared,
            AcquisitionType::Exclusive,
            AcquisitionType::Shared,
            AcquisitionType::FreeTrial,
            AcquisitionType::Exclusive,
            AcquisitionType::Shared,
            AcquisitionType::Exclusive,
            AcquisitionType::Exclusive,
        ];

        $notes = [
            null,
            'Chiamato, molto interessato. Vuole sopralluogo settimana prossima.',
            'Inviato preventivo via email, attendo risposta.',
            'Contratto firmato! Lavori iniziano il 20 marzo.',
            'Non risponde al telefono da 3 tentativi.',
            null,
            'Ha richiesto informazioni aggiuntive sulle detrazioni fiscali.',
            'Primo contatto positivo, secondo appuntamento fissato.',
            null,
            'Cliente acquisito, impianto da 6kW installato con successo.',
        ];

        for ($i = 0; $i < 10; $i++) {
            $lead = $allLeads[$i];
            $daysAgo = match (true) {
                $i < 3 => rand(50, 70),     // older
                $i < 6 => rand(15, 40),     // medium
                default => rand(1, 14),      // recent
            };

            UserLead::create([
                'user_id' => $userId,
                'lead_id' => $lead->id,
                'order_id' => match (true) {
                    $i < 3 => $order1->id,
                    $i < 7 => $order2->id,
                    $i == 9 => $order4->id,
                    default => $order3->id,
                },
                'acquisition_type' => $acquisitionTypes[$i],
                'purchase_price' => $acquisitionTypes[$i] === AcquisitionType::FreeTrial ? 0 : ($acquisitionTypes[$i] === AcquisitionType::Exclusive ? 35.00 : 15.00),
                'contact_status' => $contactStatuses[$i],
                'notes' => $notes[$i],
                'last_contacted_at' => $contactStatuses[$i] !== ContactStatus::New ? now()->subDays(rand(1, $daysAgo)) : null,
                'purchased_at' => now()->subDays($daysAgo),
            ]);
        }

        // =====================================================================
        // 3. CART ITEMS (3 items in cart)
        // =====================================================================

        $cartLeads = $freeLeads->slice(10, 3);
        $cartPrices = [35.00, 15.00, 35.00];
        $cartModes = [PurchaseMode::Exclusive, PurchaseMode::Shared, PurchaseMode::Exclusive];

        $idx = 0;
        foreach ($cartLeads as $lead) {
            CartItem::create([
                'user_id' => $userId,
                'lead_id' => $lead->id,
                'purchase_mode' => $cartModes[$idx],
                'price' => $cartPrices[$idx],
                'added_at' => now()->subHours(rand(1, 48)),
            ]);
            $idx++;
        }

        // =====================================================================
        // 4. NOTIFICATIONS (6 notifications, mix of read/unread)
        // =====================================================================

        ClientNotification::create([
            'user_id' => $userId,
            'type' => 'new_leads',
            'title' => 'Nuovi lead disponibili',
            'message' => 'Sono disponibili 5 nuovi lead nella categoria Fotovoltaico per la tua zona.',
            'link' => '/catalogo?category=1',
            'read_at' => now()->subDays(2),
            'created_at' => now()->subDays(3),
        ]);

        ClientNotification::create([
            'user_id' => $userId,
            'type' => 'order_completed',
            'title' => 'Ordine completato',
            'message' => 'Il tuo ordine ORD-2026-00003 è stato confermato. Puoi visualizzare i contatti dei lead acquistati.',
            'link' => '/ordini/' . $order3->id,
            'read_at' => now()->subDays(4),
            'created_at' => now()->subDays(5),
        ]);

        ClientNotification::create([
            'user_id' => $userId,
            'type' => 'info',
            'title' => 'Aggiornamento prezzi',
            'message' => 'I prezzi della categoria Climatizzazione sono stati aggiornati. Controlla le nuove tariffe.',
            'link' => '/catalogo?category=3',
            'read_at' => null,
            'created_at' => now()->subDays(1),
        ]);

        ClientNotification::create([
            'user_id' => $userId,
            'type' => 'new_leads',
            'title' => '3 nuovi lead Infissi',
            'message' => 'Nuovi lead per Infissi e Serramenti disponibili nelle province di Milano e Roma.',
            'link' => '/catalogo?category=2',
            'read_at' => null,
            'created_at' => now()->subHours(6),
        ]);

        ClientNotification::create([
            'user_id' => $userId,
            'type' => 'info',
            'title' => 'Prova gratuita attiva',
            'message' => 'Hai ancora 2 lead gratuiti disponibili nella tua prova gratuita. Approfittane!',
            'link' => '/prova-gratuita',
            'read_at' => null,
            'created_at' => now()->subHours(2),
        ]);

        ClientNotification::create([
            'user_id' => $userId,
            'type' => 'new_leads',
            'title' => 'Lead urgenti Caldaie',
            'message' => '2 nuovi lead urgenti nella categoria Caldaie. Acquista prima degli altri!',
            'link' => '/catalogo?category=4',
            'read_at' => null,
            'created_at' => now()->subMinutes(30),
        ]);

        // Update trial: 1 lead claimed, 2 remaining
        $user->clientProfile->update([
            'free_trial_leads_remaining' => 2,
        ]);

        $this->command->info('Dashboard test data created for test@qualeadfied.com');
        $this->command->info('  - 5 orders (3 paid, 1 completed, 1 pending)');
        $this->command->info('  - 10 user leads (various contact statuses)');
        $this->command->info('  - 3 cart items');
        $this->command->info('  - 6 notifications (2 read, 4 unread)');
    }
}
