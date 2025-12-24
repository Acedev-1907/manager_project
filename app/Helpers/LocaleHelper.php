<?php

namespace App\Helpers;

use App\Models\User;
use Illuminate\Http\Request;

class LocaleHelper
{
    /**
     * Get locale from user, request, or default
     * 
     * @param User|null $user
     * @param Request|null $request
     * @return string
     */
    public static function getLocale(?User $user = null, ?Request $request = null): string
    {
        // Priority 1: User's locale preference
        if ($user && $user->locale) {
            return $user->locale;
        }

        // Priority 2: Request header (Accept-Language)
        if ($request) {
            $acceptLanguage = $request->header('Accept-Language');
            if ($acceptLanguage) {
                $locale = self::parseAcceptLanguage($acceptLanguage);
                if ($locale) {
                    return $locale;
                }
            }
        }

        // Priority 3: App default locale
        return config('app.locale', 'vi');
    }

    /**
     * Parse Accept-Language header
     * 
     * @param string $acceptLanguage
     * @return string|null
     */
    protected static function parseAcceptLanguage(string $acceptLanguage): ?string
    {
        $languages = explode(',', $acceptLanguage);
        $supportedLocales = ['vi', 'en'];

        foreach ($languages as $lang) {
            $lang = trim(explode(';', $lang)[0]);
            $lang = strtolower($lang);
            
            // Check exact match
            if (in_array($lang, $supportedLocales)) {
                return $lang;
            }
            
            // Check language code (e.g., 'vi-VN' -> 'vi')
            $langCode = explode('-', $lang)[0];
            if (in_array($langCode, $supportedLocales)) {
                return $langCode;
            }
        }

        return null;
    }

    /**
     * Set locale for current request
     * 
     * @param string $locale
     * @return void
     */
    public static function setLocale(string $locale): void
    {
        app()->setLocale($locale);
    }
}

