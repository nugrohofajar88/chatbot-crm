<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['code', 'value', 'group', 'description'];

    public const CACHE_KEY = 'app.settings';

    /** Buang cache settings tiap ada perubahan (jadi tak perlu config:clear). */
    protected static function booted(): void
    {
        $bust = static fn () => Cache::forget(self::CACHE_KEY);
        static::saved($bust);
        static::deleted($bust);
    }

    /** Seluruh setting sebagai map code => value (di-cache). */
    public static function map(): array
    {
        return Cache::rememberForever(
            self::CACHE_KEY,
            static fn () => static::query()->pluck('value', 'code')->all(),
        );
    }

    public static function get(string $code, ?string $default = null): ?string
    {
        $value = static::map()[$code] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function put(string $code, ?string $value): void
    {
        static::updateOrCreate(['code' => $code], ['value' => $value]);
    }
}
