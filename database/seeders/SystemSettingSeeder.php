<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Seed the system_settings table with platform configuration.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'default_free_trial_leads',
                'value' => 5,
                'description' => 'Numero di lead gratuiti per il periodo di prova',
            ],
            [
                'key' => 'default_vat_rate',
                'value' => 22,
                'description' => 'Aliquota IVA predefinita in percentuale',
            ],
            [
                'key' => 'order_number_prefix',
                'value' => 'ORD',
                'description' => 'Prefisso per i numeri degli ordini',
            ],
            [
                'key' => 'invoice_number_prefix',
                'value' => 'FT',
                'description' => 'Prefisso per i numeri delle fatture',
            ],
            [
                'key' => 'sender_email',
                'value' => 'noreply@qualeadfied.com',
                'description' => 'Indirizzo email mittente per le comunicazioni automatiche',
            ],
            [
                'key' => 'sender_name',
                'value' => 'Qualeadfied',
                'description' => 'Nome mittente per le comunicazioni automatiche',
            ],
            [
                'key' => 'platform_name',
                'value' => 'Qualeadfied',
                'description' => 'Nome della piattaforma',
            ],
            [
                'key' => 'support_email',
                'value' => 'supporto@qualeadfied.com',
                'description' => 'Indirizzo email per il supporto clienti',
            ],
            [
                'key' => 'max_lead_shares',
                'value' => 3,
                'description' => 'Numero massimo di condivisioni per lead (default)',
            ],
            [
                'key' => 'lead_expiry_days',
                'value' => 90,
                'description' => 'Giorni di validita di un lead dalla generazione',
            ],
            [
                'key' => 'smtp_host',
                'value' => 'smtp.example.com',
                'description' => 'Host del server SMTP per invio email',
            ],
            [
                'key' => 'smtp_port',
                'value' => 587,
                'description' => 'Porta del server SMTP',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::create($setting);
        }
    }
}
