<?php

namespace Tests\Unit\UBL\Traits;

use CamInv\EInvoice\UBL\Traits\XmlSanitizer;
use PHPUnit\Framework\TestCase;

class XmlSanitizerTest extends TestCase
{
    use XmlSanitizer;

    /**
     * Test that normal text is preserved.
     */
    public function test_normal_text_is_preserved(): void
    {
        $input = 'This is normal text';
        $expected = 'This is normal text';
        $this->assertEquals($expected, $this->sanitizeXml($input));
    }

    /**
     * Test that null values return empty string.
     */
    public function test_null_returns_empty_string(): void
    {
        $this->assertEquals('', $this->sanitizeXml(null));
    }

    /**
     * Test that empty strings are returned as-is.
     */
    public function test_empty_string_returns_empty(): void
    {
        $this->assertEquals('', $this->sanitizeXml(''));
    }

    /**
     * Test that XML-safe characters are preserved.
     * These should be left alone and will be escaped by createTextNode().
     */
    public function test_xml_safe_characters_preserved(): void
    {
        $input = 'Tom & Jerry < > "quotes" \'apostrophe\'';
        $expected = 'Tom & Jerry < > "quotes" \'apostrophe\'';
        $this->assertEquals($expected, $this->sanitizeXml($input));
    }

    /**
     * Test that control characters are removed.
     * Control characters like \x00, \x08, \x0B are illegal in XML 1.0.
     */
    public function test_control_characters_removed(): void
    {
        $input = "Valid\x00text\x08with\x0Bcontrol";
        $expected = 'Validtextwithcontrol';
        $this->assertEquals($expected, $this->sanitizeXml($input));
    }

    /**
     * Test that valid whitespace is preserved.
     * Tab (\x09), LF (\x0A), CR (\x0D) are legal in XML 1.0.
     */
    public function test_valid_whitespace_preserved(): void
    {
        $input = "Line1\x0ALine2\x0DLine3\x09Tabbed";
        $expected = "Line1\x0ALine2\x0DLine3\x09Tabbed";
        $this->assertEquals($expected, $this->sanitizeXml($input));
    }

    /**
     * Test that surrogate pairs (unpaired surrogates) are removed.
     * These are in the range \xD800-\xDFFF which are illegal in XML 1.0.
     */
    public function test_unpaired_surrogates_removed(): void
    {
        // This creates an invalid UTF-8 sequence that should be removed
        $input = "Valid\xED\xA0\x80Invalid";
        $result = $this->sanitizeXml($input);
        // The surrogate should be removed
        $this->assertStringNotContainsString("\xED\xA0\x80", $result);
    }

    /**
     * Test that multibyte UTF-8 characters are preserved.
     * This includes Khmer script used in Cambodia.
     */
    public function test_multibyte_utf8_preserved(): void
    {
        // Khmer script: កម្ពុជា (Cambodia)
        $input = 'Company name: កម្ពុជា Ltd.';
        $expected = 'Company name: កម្ពុជា Ltd.';
        $this->assertEquals($expected, $this->sanitizeXml($input));
    }

    /**
     * Test that emojis and supplementary planes are preserved.
     * These are in the range \x10000-\x10FFFF.
     */
    public function test_emojis_and_supplementary_planes_preserved(): void
    {
        $input = 'Invoice ✓ Status: ✅ 2026';
        $expected = 'Invoice ✓ Status: ✅ 2026';
        $this->assertEquals($expected, $this->sanitizeXml($input));
    }

    /**
     * Test mixed valid and invalid characters.
     */
    public function test_mixed_valid_invalid_characters(): void
    {
        $input = "Product: ABC\x00DEF\x08GHI-001";
        $expected = 'Product: ABCDEFGHI-001';
        $this->assertEquals($expected, $this->sanitizeXml($input));
    }

    /**
     * Test real-world invoice scenario with special characters.
     */
    public function test_real_world_invoice_description(): void
    {
        $input = 'Professional Services - Consulting & Support (2026)';
        $expected = 'Professional Services - Consulting & Support (2026)';
        $this->assertEquals($expected, $this->sanitizeXml($input));
    }

    /**
     * Test form data with potentially malicious control characters.
     */
    public function test_form_data_with_control_characters(): void
    {
        $input = "Company Name\x00\x08Secret Data\x0B";
        $expected = 'Company NameSecret Data';
        $this->assertEquals($expected, $this->sanitizeXml($input));
    }
}
