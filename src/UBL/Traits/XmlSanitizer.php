<?php

namespace CamInv\EInvoice\UBL\Traits;

/**
 * Provides XML sanitization utilities for safe text node creation.
 * 
 * Ensures user-provided content doesn't break XML structure by removing
 * characters that are illegal in XML 1.0 while preserving escapable
 * characters like &, <, >, and quotes for proper XML entity encoding.
 */
trait XmlSanitizer
{
    /**
     * Strip characters that are illegal in XML 1.0.
     * 
     * Safe characters (&, <, >, etc.) are left alone and will be
     * properly escaped by createTextNode().
     *
     * @param string|null $value Input string to sanitize
     * @return string Sanitized string with illegal XML characters removed
     * 
     * @see https://www.w3.org/TR/XML/#charsets
     */
    public static function sanitizeXml(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // XML 1.0 legal characters:
        // #x9 (tab) | #xA (LF) | #xD (CR) | [#x20-#xD7FF] | [#xE000-#xFFFD] | [#x10000-#x10FFFF]
        // Removes: control characters, surrogates, and other invalid code points
        return preg_replace(
            '/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',
            '',
            (string) $value
        );
    }
}
