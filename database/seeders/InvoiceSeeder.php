<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Seed the invoices and invoice_items tables for paid/completed orders.
     */
    public function run(): void
    {
        // Get all paid and completed orders that are NOT free_trial
        $paidOrders = Order::whereIn('status', ['paid', 'completed'])
            ->where('type', '!=', 'free_trial')
            ->orderBy('id')
            ->get();

        $sdiStatuses = ['pending', 'sent', 'delivered', 'accepted'];
        $invoiceNumber = 1;

        foreach ($paidOrders as $index => $order) {
            $issuedAt = $order->paid_at ?? now()->subDays(rand(5, 50));
            $dueAt = $issuedAt->copy()->addDays(30);

            // Make the last one a credit note
            $isCreditNote = $index === $paidOrders->count() - 1;

            $sdiStatus = $sdiStatuses[array_rand($sdiStatuses)];

            $invoice = Invoice::create([
                'order_id' => $order->id,
                'invoice_number' => sprintf('FT-2026-%04d', $invoiceNumber),
                'type' => $isCreditNote ? 'credit_note' : 'invoice',
                'fatture_cloud_id' => (string) rand(100000, 999999),
                'sdi_status' => $sdiStatus,
                'sdi_message' => $sdiStatus === 'accepted' ? 'Fattura accettata dal destinatario' : ($sdiStatus === 'delivered' ? 'Fattura consegnata al destinatario' : null),
                'subtotal' => $order->subtotal,
                'vat_rate' => $order->vat_rate,
                'vat_amount' => $order->vat_amount,
                'total' => $isCreditNote ? -$order->total : $order->total,
                'billing_data' => $order->billing_snapshot,
                'notes' => $isCreditNote ? 'Nota di credito per ordine ' . $order->order_number : null,
                'issued_at' => $issuedAt,
                'due_at' => $dueAt,
                'sent_at' => in_array($sdiStatus, ['sent', 'delivered', 'accepted']) ? $issuedAt->copy()->addHours(rand(1, 24)) : null,
            ]);

            // Update order with invoice number
            $order->update(['invoice_number' => $invoice->invoice_number]);

            // Create invoice items from order items
            $orderItems = OrderItem::where('order_id', $order->id)->get();

            foreach ($orderItems as $orderItem) {
                $description = 'Lead';
                if ($orderItem->lead_id) {
                    $modeLabel = match ($orderItem->acquisition_mode->value) {
                        'exclusive' => 'esclusivo',
                        'shared' => 'condiviso',
                        'free' => 'gratuito',
                        default => $orderItem->acquisition_mode->value,
                    };
                    $description = "Lead {$modeLabel} #{$orderItem->lead_id}";
                } elseif ($orderItem->package_id) {
                    $description = "Pacchetto #{$orderItem->package_id}";
                }

                $lineTotal = $orderItem->line_total;
                $itemVatRate = $order->vat_rate;
                $itemVatAmount = round($lineTotal * $itemVatRate / 100, 2);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'description' => $description,
                    'quantity' => $orderItem->quantity,
                    'unit_price' => $orderItem->unit_price,
                    'line_total' => $lineTotal,
                    'vat_rate' => $itemVatRate,
                    'vat_amount' => $itemVatAmount,
                ]);
            }

            $invoiceNumber++;
        }
    }
}
