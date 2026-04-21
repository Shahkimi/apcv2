<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'type', 'description'])]
class SystemSetting extends Model
{
    protected $table = 'system_settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->find($key);

        if ($setting === null) {
            return $default;
        }

        return match ($setting->type) {
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOL),
            'integer' => is_numeric($setting->value) ? (int) $setting->value : 0,
            'json' => json_decode((string) $setting->value, true),
            default => $setting->value,
        };
    }

    public static function setValue(string $key, mixed $value): void
    {
        $setting = static::query()->findOrNew($key);
        $setting->key = $key;
        $setting->value = is_array($value) ? json_encode($value) : (string) $value;

        if (! $setting->exists) {
            $setting->type = match (true) {
                is_bool($value) => 'boolean',
                is_int($value) => 'integer',
                is_array($value) => 'json',
                default => 'string',
            };
        }

        $setting->save();
    }
}
