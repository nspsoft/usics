<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappContactLabel extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'label',
        'color',
    ];

    /**
     * Available label presets
     */
    public static function presets(): array
    {
        return [
            ['label' => 'Hot Lead', 'color' => 'red'],
            ['label' => 'Warm Lead', 'color' => 'orange'],
            ['label' => 'Menunggu Bayar', 'color' => 'yellow'],
            ['label' => 'Komplain', 'color' => 'rose'],
            ['label' => 'VIP', 'color' => 'purple'],
            ['label' => 'Follow Up', 'color' => 'blue'],
            ['label' => 'Pending', 'color' => 'slate'],
            ['label' => 'Selesai', 'color' => 'emerald'],
        ];
    }

    public function scopeByPhone($query, string $phone)
    {
        return $query->where('phone', $phone);
    }
}
