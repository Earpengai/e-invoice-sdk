<?php

namespace CamInv\EInvoice\UBL;

use DOMDocument;
use DOMElement;

/**
 * Sanitizes text for safe inclusion in XML documents.
 *
 * Handles:
 *  - Stripping characters illegal in XML 1.0 / 1.1
 *  - Smart ampersand escaping that avoids double-encoding
 *  - Convenient element-building helpers (with or without namespaces)
 */
class XmlSanitizer
{
    /** XML 1.0 legal character range (exclusive pattern). */
    private const XML10_FORBIDDEN =
        '/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u';

    /** XML 1.1 legal character range (excludes C0 / C1 but allows #x1-#x1F except TAB/LF/CR). */
    private const XML11_FORBIDDEN =
        '/[^\x01-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u';

    /**
     * Characters that must be escaped in XML text content.
     */
    private const XML_ENTITIES = [
        '&'  => '&amp;',
        '<'  => '&lt;',
        '>'  => '&gt;',
        '"'  => '&quot;',
        "'"  => '&apos;',
    ];

    // ──────────────────────── CORE SANITIZATION ────────────────────────

    /**
     * Strip characters that are illegal in XML 1.0.
     *
     * Safe characters (&, <, >, etc.) are left for createTextNode()
     * or escapeEntities() to handle.
     */
    public static function sanitize(?string $value): string
    {
        return self::stripForbidden($value, self::XML10_FORBIDDEN);
    }

    /**
     * Strip characters that are illegal in XML 1.1.
     *
     * XML 1.1 allows all Unicode characters except the surrogate
     * block (D800-DFFF) and the two restriction characters
     * (#xFFFE and #xFFFF).
     */
    public static function sanitizeForXml11(?string $value): string
    {
        return self::stripForbidden($value, self::XML11_FORBIDDEN);
    }

    /**
     * Strip illegal chars using the given regex pattern.
     */
    private static function stripForbidden(?string $value, string $pattern): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $result = preg_replace($pattern, '', (string) $value);

        return $result ?? '';
    }

    // ──────────────────────── SMART AMPERSAND ESCAPING ────────────────────

    /**
     * Escape bare XML entities (&, <, >, ", ') without double-encoding
     * already-valid entity references.
     *
     * Mimics the behaviour of Python's xml-sanitizer:
     *  "Jack & Tom"  →  "Jack &amp; Tom"
     *  "Jack &amp; Tom"  →  "Jack &amp; Tom"  (unchanged)
     */
    public static function escapeEntities(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // Protect already-valid entity references so they are not re-escaped.
        // We temporarily replace them with placeholders, escape the rest, then restore.
        $placeholders = [];
        $counter = 0;

        $protected = preg_replace_callback(
            '/&(?:amp|lt|gt|quot|apos|#\d+|#x[0-9a-fA-F]+);/',
            static function (array $m) use (&$placeholders, &$counter) {
                $key = "\x00XMLENT{$counter}\x00";
                $placeholders[$key] = $m[0];
                $counter++;

                return $key;
            },
            (string) $value
        );

        // Escape remaining bare ampersands (must be first) then other chars.
        $escaped = strtr($protected, self::XML_ENTITIES);

        // Restore placeholder-protected entities.
        return strtr($escaped, $placeholders);
    }

    /**
     * Check whether the value contains bare XML special characters
     * that would require escaping.
     */
    public static function needsEscaping(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return self::escapeEntities((string) $value) !== $value;
    }

    // ──────────────────────── COMPLETE CLEANUP ────────────────────────

    /**
     * Full cleanup: strip illegal XML 1.0 characters AND escape
     * bare XML entities in one pass.  Suitable for processing
     * raw text that will be placed directly in XML (not via
     * createTextNode).
     */
    public static function cleanup(?string $value): string
    {
        return self::escapeEntities(self::sanitize($value));
    }

    // ──────────────────────── DOM HELPERS ────────────────────────

    /**
     * Append a text element node to a parent, sanitizing the value
     * against XML 1.0 before it enters the DOM.
     *
     * Uses createTextNode() so XML-special characters are
     * automatically escaped; only illegal control characters are
     * stripped.
     */
    public static function appendTextElement(DOMDocument $doc, DOMElement $parent, string $elementName, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $el = $doc->createElement($elementName);
        $el->appendChild($doc->createTextNode(self::sanitize($value)));
        $parent->appendChild($el);
    }

    /**
     * Append a namespace-qualified text element node to a parent.
     *
     * Same as appendTextElement but uses createElementNS so the
     * element belongs to a specific XML namespace (e.g.
     * "urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2").
     */
    public static function appendTextElementNS(DOMDocument $doc, DOMElement $parent, string $namespaceURI, string $qualifiedName, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $el = $doc->createElementNS($namespaceURI, $qualifiedName);
        $el->appendChild($doc->createTextNode(self::sanitize($value)));
        $parent->appendChild($el);
    }
}
