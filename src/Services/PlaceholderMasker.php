declare(strict_types=1);

namespace LaravelGoogleSheetI18n\Services;

class PlaceholderMasker
{
    /**
     * Regex to find Laravel style placeholders like :name, :count, :ATTRIBUTE.
     */
    private const PLACEHOLDER_REGEX = '/:([a-zA-Z_]\w*)/';

    /**
     * Mask placeholders in the given text with tokens like [[T_0]], [[T_1]].
     *
     * @param string $text
     * @return array{masked_text: string, placeholders: array<int, string>}
     */
    public function mask(string $text): array
    {
        $placeholders = [];
        $callback = function ($matches) use (&$placeholders) {
            $index = count($placeholders);
            $token = "[[T_{$index}]]";
            $placeholders[] = $matches[0]; // Store original :name
            return $token;
        };

        $maskedText = preg_replace_callback(self::PLACEHOLDER_REGEX, $callback, $text);

        // preg_replace_callback can return null on error, though unlikely here with basic string input
        return [
            'masked_text' => (string)$maskedText,
            'placeholders' => $placeholders,
        ];
    }

    /**
     * Unmask tokens back to their original placeholders.
     *
     * @param string $maskedText
     * @param array<int, string> $originalPlaceholders
     * @return string
     */
    public function unmask(string $maskedText, array $originalPlaceholders): string
    {
        foreach ($originalPlaceholders as $index => $originalPlaceholder) {
            // Use specific replacement to avoid replacing unintended parts
            // We look for the exact token [[T_N]]
            
            // Should be case-insensitive just in case translation messed with casing, 
            // though usually brackets survive ok. String replace is safer.
            // However, Google Translate might add spaces like [[ T_0 ]] or [[T_0]].
            // Let's try a robust regex for the token.
            $pattern = '/\[\[\s*T_' . $index . '\s*\]\]/i';

            $result = preg_replace($pattern, $originalPlaceholder, $maskedText);
            if (is_string($result)) {
                $maskedText = $result;
            }
        }

        return $maskedText;
    }
}
