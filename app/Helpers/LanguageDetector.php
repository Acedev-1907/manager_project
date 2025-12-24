<?php

namespace App\Helpers;

class LanguageDetector
{
    /**
     * Detect language from text
     * 
     * @param string $text
     * @return string 'vi' or 'en'
     */
    public static function detect(string $text): string
    {
        if (empty(trim($text))) {
            return 'vi'; // Default
        }

        $text = mb_strtolower(trim($text));
        
        // Vietnamese indicators
        $vietnamesePatterns = [
            // Common Vietnamese words
            '/\b(được|không|của|với|cho|từ|về|này|đó|nào|đã|sẽ|đang|bạn|tôi|mình|chúng|họ|nó)\b/u',
            // Vietnamese diacritics (ă, â, đ, ê, ô, ơ, ư)
            '/[àáảãạăắằẳẵặâấầẩẫậèéẻẽẹêếềểễệìíỉĩịòóỏõọôốồổỗộơớờởỡợùúủũụưứừửữựỳýỷỹỵđ]/u',
            // Common Vietnamese phrases
            '/\b(tạo|dự án|task|nhiệm vụ|thành viên|tiến độ|hoàn thành|đang làm|sắp|chưa)\b/u',
        ];

        // English indicators
        $englishPatterns = [
            // Common English words
            '/\b(the|is|are|was|were|have|has|had|will|would|can|could|should|this|that|these|those)\b/i',
            // Common English phrases
            '/\b(create|project|task|member|progress|complete|doing|going|not|yes|no|ok|okay)\b/i',
        ];

        $vietnameseScore = 0;
        $englishScore = 0;

        // Check Vietnamese patterns
        foreach ($vietnamesePatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $vietnameseScore++;
            }
        }

        // Check English patterns
        foreach ($englishPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $englishScore++;
            }
        }

        // Count Vietnamese diacritics
        $vietnameseDiacritics = preg_match_all('/[àáảãạăắằẳẵặâấầẩẫậèéẻẽẹêếềểễệìíỉĩịòóỏõọôốồổỗộơớờởỡợùúủũụưứừửữựỳýỷỹỵđ]/u', $text);
        if ($vietnameseDiacritics > 0) {
            $vietnameseScore += $vietnameseDiacritics * 2; // Higher weight for diacritics
        }

        // If Vietnamese score is significantly higher, return 'vi'
        if ($vietnameseScore > $englishScore && $vietnameseScore > 0) {
            return 'vi';
        }

        // If English score is higher or equal, return 'en'
        if ($englishScore >= $vietnameseScore && $englishScore > 0) {
            return 'en';
        }

        // Default to Vietnamese if no clear indicator
        return 'vi';
    }

    /**
     * Detect and update user locale if needed
     * 
     * @param \App\Models\User $user
     * @param string $text
     * @return string Detected locale
     */
    public static function detectAndUpdateUserLocale(\App\Models\User $user, string $text): string
    {
        $detectedLocale = self::detect($text);
        
        // Update user locale if not set or different
        if (empty($user->locale) || $user->locale !== $detectedLocale) {
            $user->update(['locale' => $detectedLocale]);
        }
        
        return $detectedLocale;
    }
}

