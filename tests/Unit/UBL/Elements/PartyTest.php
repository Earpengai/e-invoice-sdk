<?php

namespace CamInv\EInvoice\Tests\Unit\UBL\Elements;

use CamInv\EInvoice\UBL\Elements\Party;
use CamInv\EInvoice\Tests\TestCase;
use DOMDocument;

class PartyTest extends TestCase
{
    public function test_full_party(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        Party::append($doc, $root, [
            'endpoint_id' => 'KHUID00001234',
            'scheme_id' => 'KHM',
            'party_name' => 'Test Company Ltd.',
            'postal_address' => [
                'street_name' => '123 Main St',
                'additional_street_name' => 'Floor 2',
                'city_name' => 'Phnom Penh',
                'postal_zone' => '12000',
                'country_subentity' => 'Phnom Penh',
                'country' => [
                    'identification_code' => 'KH',
                    'name' => 'Cambodia',
                ],
            ],
            'party_tax_scheme' => [
                'company_id' => 'L001123456789',
                'tax_scheme_id' => 'VAT',
            ],
            'party_legal_entity' => [
                'registration_name' => 'Test Company Ltd.',
                'company_id' => 'MOC-001',
            ],
            'contact' => [
                'name' => 'John Doe',
                'telephone' => '012345678',
                'email' => 'john@company.com',
            ],
        ]);

        $xml = $doc->saveXML();

        $this->assertStringContainsString('<cac:Party>', $xml);
        $this->assertStringContainsString('<cbc:EndpointID schemeID="KHM">KHUID00001234</cbc:EndpointID>', $xml);
        $this->assertStringContainsString('<cbc:Name>Test Company Ltd.</cbc:Name>', $xml);
        $this->assertStringContainsString('<cbc:StreetName>123 Main St</cbc:StreetName>', $xml);
        $this->assertStringContainsString('<cbc:AdditionalStreetName>Floor 2</cbc:AdditionalStreetName>', $xml);
        $this->assertStringContainsString('<cbc:CityName>Phnom Penh</cbc:CityName>', $xml);
        $this->assertStringContainsString('<cbc:PostalZone>12000</cbc:PostalZone>', $xml);
        $this->assertStringContainsString('<cbc:CountrySubentity>Phnom Penh</cbc:CountrySubentity>', $xml);
        $this->assertStringContainsString('<cbc:IdentificationCode>KH</cbc:IdentificationCode>', $xml);
        $this->assertStringContainsString('<cbc:Name>Cambodia</cbc:Name>', $xml);
        $this->assertStringContainsString('<cbc:CompanyID>L001123456789</cbc:CompanyID>', $xml);
        $this->assertStringContainsString('<cbc:RegistrationName>Test Company Ltd.</cbc:RegistrationName>', $xml);
        $this->assertStringContainsString('<cbc:Telephone>012345678</cbc:Telephone>', $xml);
        $this->assertStringContainsString('<cbc:ElectronicMail>john@company.com</cbc:ElectronicMail>', $xml);
    }

    public function test_minimal_party(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        Party::append($doc, $root, [
            'party_name' => 'Minimal Co.',
        ]);

        $xml = $doc->saveXML();

        $this->assertStringContainsString('<cbc:Name>Minimal Co.</cbc:Name>', $xml);
        $this->assertStringNotContainsString('EndpointID', $xml);
        $this->assertStringNotContainsString('PostalAddress', $xml);
    }

    public function test_party_without_endpoint_id(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $root = $doc->createElement('Root');
        $doc->appendChild($root);

        Party::append($doc, $root, [
            'party_name' => 'No Endpoint Co.',
            'postal_address' => [
                'country' => 'KH',
            ],
        ]);

        $xml = $doc->saveXML();

        $this->assertStringContainsString('<cbc:IdentificationCode>KH</cbc:IdentificationCode>', $xml);
        $this->assertStringNotContainsString('EndpointID', $xml);
    }
}
