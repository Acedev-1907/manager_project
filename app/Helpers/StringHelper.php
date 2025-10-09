<?php

namespace App\Helpers;

/**
 * String Helper
 * 
 * Utility class cho các thao tác xử lý chuỗi
 * Tối ưu performance với memoization pattern
 */
class StringHelper
{
    /**
     * Cache cho accent mapping
     * 
     * @var array
     */
    private static array $accentMap = [];

    /**
     * Khởi tạo accent mapping
     * 
     * @return void
     */
    private static function initAccentMap(): void
    {
        if (!empty(self::$accentMap)) {
            return;
        }

        $accents = [
            'a' => ['á', 'à', 'ả', 'ã', 'ạ', 'ă', 'ắ', 'ằ', 'ẳ', 'ẵ', 'ặ', 'â', 'ấ', 'ầ', 'ẩ', 'ẫ', 'ậ'],
            'e' => ['é', 'è', 'ẻ', 'ẽ', 'ẹ', 'ê', 'ế', 'ề', 'ể', 'ễ', 'ệ'],
            'i' => ['í', 'ì', 'ỉ', 'ĩ', 'ị'],
            'o' => ['ó', 'ò', 'ỏ', 'õ', 'ọ', 'ô', 'ố', 'ồ', 'ổ', 'ỗ', 'ộ', 'ơ', 'ớ', 'ờ', 'ở', 'ỡ', 'ợ'],
            'u' => ['ú', 'ù', 'ủ', 'ũ', 'ụ', 'ư', 'ứ', 'ừ', 'ử', 'ữ', 'ự'],
            'y' => ['ý', 'ỳ', 'ỷ', 'ỹ', 'ỵ'],
            'd' => ['đ'],
        ];

        foreach ($accents as $nonAccent => $accentChars) {
            foreach ($accentChars as $char) {
                self::$accentMap[$char] = $nonAccent;
                self::$accentMap[mb_strtoupper($char)] = mb_strtoupper($nonAccent);
            }
        }
    }

    /**
     * Remove Vietnamese accents from a string
     * Sử dụng strtr() thay vì str_replace() để tối ưu performance
     * 
     * @param string $str
     * @return string
     */
    public static function removeAccents(string $str): string
    {
        if (empty($str)) {
            return $str;
        }

        self::initAccentMap();
        return strtr($str, self::$accentMap);
    }

    /**
     * Generate friend code from user name and ID
     * 
     * @param string $name
     * @param int $id
     * @return string
     */
    public static function generateFriendCode(string $name, int $id): string
    {
        $cleanName = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(self::removeAccents($name)));
        return $cleanName . '-' . $id;
    }

    /**
     * Generate slug from string
     * 
     * @param string $str
     * @param string $separator
     * @return string
     */
    public static function slug(string $str, string $separator = '-'): string
    {
        $str = self::removeAccents($str);
        $str = strtolower(trim($str));
        $str = preg_replace('/[^a-z0-9]+/', $separator, $str);
        $str = trim($str, $separator);
        
        return $str;
    }

    /**
     * Truncate string với ellipsis
     * 
     * @param string $str
     * @param int $length
     * @param string $suffix
     * @return string
     */
    public static function truncate(string $str, int $length = 100, string $suffix = '...'): string
    {
        if (mb_strlen($str) <= $length) {
            return $str;
        }

        return mb_substr($str, 0, $length) . $suffix;
    }

    /**
     * Sanitize HTML tags
     * 
     * @param string $str
     * @param array $allowedTags
     * @return string
     */
    public static function sanitize(string $str, array $allowedTags = []): string
    {
        return strip_tags($str, $allowedTags);
    }
}


