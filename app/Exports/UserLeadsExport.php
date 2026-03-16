<?php

namespace App\Exports;

use App\Models\UserLead;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Database\Eloquent\Builder;

class UserLeadsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    protected int $userId;
    protected ?int $categoryId;
    protected ?string $contactStatus;

    public function __construct(int $userId, ?int $categoryId = null, ?string $contactStatus = null)
    {
        $this->userId = $userId;
        $this->categoryId = $categoryId;
        $this->contactStatus = $contactStatus;
    }

    public function query(): Builder
    {
        $query = UserLead::query()
            ->where('user_id', $this->userId)
            ->with(['lead.category', 'lead.province']);

        if ($this->categoryId) {
            $query->whereHas('lead', function ($q) {
                $q->where('category_id', $this->categoryId);
            });
        }

        if ($this->contactStatus) {
            $query->where('contact_status', $this->contactStatus);
        }

        return $query->orderByDesc('purchased_at');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nome',
            'Cognome',
            'Email',
            'Telefono',
            'Categoria',
            'Provincia',
            'Richiesta',
            'Tipo Acquisizione',
            'Stato Contatto',
            'Prezzo Acquisto',
            'Data Acquisto',
            'Note',
        ];
    }

    public function map($userLead): array
    {
        return [
            $userLead->id,
            $userLead->lead?->first_name ?? '',
            $userLead->lead?->last_name ?? '',
            $userLead->lead?->email ?? '',
            $userLead->lead?->phone ?? '',
            $userLead->lead?->category?->name ?? '',
            $userLead->lead?->province?->name ?? '',
            $userLead->lead?->request_text ?? '',
            $userLead->acquisition_type?->value ?? '',
            $userLead->contact_status?->value ?? '',
            $userLead->purchase_price,
            $userLead->purchased_at?->format('d/m/Y H:i') ?? '',
            $userLead->notes ?? '',
        ];
    }
}
