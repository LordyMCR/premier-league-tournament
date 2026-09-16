<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class AppSettings
{
    public const REGISTRATION_INVITE = 'invite';

    public const REGISTRATION_PUBLIC = 'public';

    public static function get(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        if (app()->environment('testing')) {
            return Setting::query()->where('key', $key)->value('value') ?? $default;
        }

        return Cache::remember("app_setting.{$key}", 60, function () use ($key, $default) {
            return Setting::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        if (! Schema::hasTable('settings')) {
            throw new \RuntimeException('Settings table is missing. Run migrations.');
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget("app_setting.{$key}");
    }

    public static function registrationMode(): string
    {
        $mode = self::get('registration_mode', self::REGISTRATION_INVITE);

        return in_array($mode, [self::REGISTRATION_INVITE, self::REGISTRATION_PUBLIC], true)
            ? $mode
            : self::REGISTRATION_INVITE;
    }

    public static function isInviteOnly(): bool
    {
        return self::registrationMode() === self::REGISTRATION_INVITE;
    }

    public static function isPublicRegistration(): bool
    {
        return self::registrationMode() === self::REGISTRATION_PUBLIC;
    }

    public static function setRegistrationMode(string $mode): void
    {
        if (! in_array($mode, [self::REGISTRATION_INVITE, self::REGISTRATION_PUBLIC], true)) {
            throw new \InvalidArgumentException('Invalid registration mode.');
        }

        self::set('registration_mode', $mode);
    }
}
