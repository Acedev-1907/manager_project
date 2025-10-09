<?php

namespace App\Helpers;

class StringHelper
{
    /**
     * Remove Vietnamese accents from a string
     * 
     * @param string $str
     * @return string
     */
    public static function removeAccents(string $str): string
    {
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
            $str = str_replace($accentChars, $nonAccent, $str);
            $str = str_replace(array_map('mb_strtoupper', $accentChars), strtoupper($nonAccent), $str);
        }
        
        return $str;
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
}

