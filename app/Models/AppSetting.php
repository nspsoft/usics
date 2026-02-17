<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'label',
        'type',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        $setting = Cache::remember("app_setting_{$key}", 3600, function () use ($key) {
            return self::where('key', $key)->first();
        });

        if (!$setting) {
            return $default;
        }

        $val = $setting->value;
        
        // If value is stored as array with single 'value' key, extract it
        if (is_array($val) && array_key_exists('value', $val)) {
            return $val['value'];
        }

        return $val ?? $default;
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, ?string $group = null, ?string $label = null): self
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => ['value' => $value],
                'group' => $group ?? 'general',
                'label' => $label,
            ]
        );

        Cache::forget("app_setting_{$key}");

        return $setting;
    }

    /**
     * Get settings by group
     */
    public static function byGroup(string $group)
    {
        return self::where('group', $group)->get();
    }
}
