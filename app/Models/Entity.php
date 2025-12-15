<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Entity extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_name',
        'entity_description',
        'trade_license_renewal_date',
        'est_card_renewal_date',
        'warehouse_ejari_renewal_date',
        'camp_ejari_renewal_date',
        'workman_insurance_expiry_date',
        'trade_license_document',
        'est_card_document',
        'warehouse_ejari_document',
        'camp_ejari_document',
        'workman_insurance_document',
        'status',
    ];

    protected $casts = [
        'trade_license_renewal_date' => 'date',
        'est_card_renewal_date' => 'date',
        'warehouse_ejari_renewal_date' => 'date',
        'camp_ejari_renewal_date' => 'date',
        'workman_insurance_expiry_date' => 'date',
    ];

    public function getDocumentStatus($date)
    {
        if (!$date) return null;

        $daysUntil = Carbon::today()->diffInDays($date, false);

        if ($daysUntil < 0) {
            return ['label' => 'Expired', 'class' => 'danger', 'days' => $daysUntil];
        } elseif ($daysUntil <= 20) {
            return ['label' => 'Critical', 'class' => 'danger', 'days' => $daysUntil];
        } elseif ($daysUntil <= 60) {
            return ['label' => 'Warning', 'class' => 'warning', 'days' => $daysUntil];
        } else {
            return ['label' => 'Valid', 'class' => 'success', 'days' => $daysUntil];
        }
    }
}
