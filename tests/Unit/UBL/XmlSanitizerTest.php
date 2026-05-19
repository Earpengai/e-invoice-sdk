<?php

namespace CamInv\EInvoice\Tests\Unit\UBL;

use CamInv\EInvoice\UBL\XmlSanitizer;
use CamInv\EInvoice\Tests\TestCase;
use DOMDocument;

class XmlSanitizerTest extends TestCase
{
    public function test_null_returns_empty_string(): void
    {
        $this->assertSame('', XmlSanitizer::sanitize(null));
    }

    public function test_empty_string_returns_empty_string(): void
    {
        $this->assertSame('', XmlSanitizer::sanitize(''));
    }

    public function test_valid_text_passes_through(): void
    {
        $input = 'Hello World 123';
        $this->assertSame($input, XmlSanitizer::sanitize($input));
    }

    public function test_xml_special_chars_are_preserved(): void
    {
        $input = 'Price < 10 & Qty > 5 is "ok"';
        $this->assertSame($input, XmlSanitizer::sanitize($input));
    }

    public function test_valid_control_chars_are_preserved(): void
    {
        $input = "Line1\tLine2\nLine3\rLine4";
        $this->assertSame($input, XmlSanitizer::sanitize($input));
    }

    public function test_illegal_control_characters_are_stripped(): void
    {
        // \x00-\x08 are illegal in XML 1.0
        $input = "Hello\x00World\x01Test\x08End";
        $this->assertSame('HelloWorldTestEnd', XmlSanitizer::sanitize($input));
    }

    public function test_illegal_chars_between_x0b_and_x1f_stripped(): void
    {
        // \x0B, \x0C, \x0E-\x1F are illegal
        $input = "A\x0B\x0C\x1FZ";
        $this->assertSame('AZ', XmlSanitizer::sanitize($input));
    }

    public function test_multibyte_utf8_preserved(): void
    {
        $input = 'ភាសាខ្មែរ 日本語 한국어';
        $this->assertSame($input, XmlSanitizer::sanitize($input));
    }

    public function test_emoji_are_preserved(): void
    {
        $input = 'Test ✅ Done';
        $this->assertSame($input, XmlSanitizer::sanitize($input));
    }

    public function test_mixed_valid_and_invalid_chars(): void
    {
        $input = "Valid\x00Text\x02With\x1FInvalid";
        $this->assertSame('ValidTextWithInvalid', XmlSanitizer::sanitize($input));
    }

    public function test_append_text_element_skips_null(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        XmlSanitizer::appendTextElement($doc, $root, 'cbc:Name', null);

        $xml = $doc->saveXML();
        $this->assertStringContainsString('<Root/>', $xml);
    }

    public function test_append_text_element_skips_empty(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        XmlSanitizer::appendTextElement($doc, $root, 'cbc:Name', '');

        $xml = $doc->saveXML();
        $this->assertStringContainsString('<Root/>', $xml);
    }

    public function test_append_text_element_sanitizes_and_appends(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        XmlSanitizer::appendTextElement($doc, $root, 'cbc:Name', "Test\x00Name");

        $xml = $doc->saveXML();
        $this->assertStringContainsString('<cbc:Name>TestName</cbc:Name>', $xml);
    }

    public function test_append_text_element_escapes_xml_special_chars(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        XmlSanitizer::appendTextElement($doc, $root, 'cbc:Description', 'A < B & C');

        $xml = $doc->saveXML();
        $this->assertStringContainsString('<cbc:Description>A &lt; B &amp; C</cbc:Description>', $xml);
    }
}
