<?php

namespace CamInv\EInvoice\UBL;

/**
 * Strips characters that are illegal in XML 1.0 from text values.
 *
 * Text nodes created via DOMDocument::createTextNode() will properly
 * escape XML-special characters (&, <, >, etc.), but illegal Unicode
 * control characters must be removed before the value enters the DOM.
 */
class XmlSanitizer
{
    /**
     * Strip characters that are illegal in XML 1.0.
     *
     * Safe characters (&, <, >, etc.) are left alone and will be
     * properly escaped by createTextNode().
     */
    public static function sanitize(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        // XML 1.0 legal characters:
        // #x9 | #xA | #xD | [#x20-#xD7FF] | [#xE000-#xFFFD] | [#x10000-#x10FFFF]
        return preg_replace(
            '/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u',
            '',
            (string) $value
        );
    }

    /**
     * Append a text element to a parent, with XML sanitization applied.
     */
    public static function appendTextElement(\DOMDocument $doc, \DOMElement $parent, string $elementName, ?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $el = $doc->createElement($elementName);
        $el->appendChild($doc->createTextNode(self::sanitize($value)));
        $parent->appendChild($el);
    }
}
