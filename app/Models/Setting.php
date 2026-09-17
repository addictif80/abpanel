<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /** Setting keys holding a third-party credential/secret — encrypted at rest. */
    public const SENSITIVE_KEYS = [
        'proxmox_password',
        'cyberpanel_password',
        'ldap_bind_password',
        'synology_password',
        'npm_password',
        'mail.mailers.smtp.password',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'tailscale_oauth_client_secret',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever("setting_{$key}", function () use ($key) {
            $setting = static::where('key', $key)->first();
            if (!$setting) return null;

            return static::isSensitive($key) ? static::tryDecrypt($setting->value) : $setting->value;
        });

        return $value ?? $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        $stored = (static::isSensitive($key) && $value !== null && $value !== '')
            ? Crypt::encryptString((string) $value)
            : $value;

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'group' => $group]
        );
        Cache::forget("setting_{$key}");
    }

    public static function group(string $group): array
    {
        return static::where('group', $group)->get()
            ->mapWithKeys(fn($s) => [$s->key => static::isSensitive($s->key) ? static::tryDecrypt($s->value) : $s->value])
            ->toArray();
    }

    public static function isSensitive(string $key): bool
    {
        return in_array($key, self::SENSITIVE_KEYS, true);
    }

    /** Decrypts a sensitive value; falls back to the raw value for legacy plaintext rows written before encryption was added. */
    public static function tryDecrypt(?string $value): ?string
    {
        if ($value === null || $value === '') return $value;

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }
}
