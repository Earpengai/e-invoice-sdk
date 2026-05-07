<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

/**
 * Builds cac:Party UBL element with postal address, tax scheme, legal entity, and contact.
 */
class Party
{
    public static function append(
        DOMDocument $doc,
        DOMElement $parent,
        array $data,
        string $endpointIdTag = 'cbc:EndpointID',
        string $partyWrapper = 'cac:Party',
    ): DOMElement {
        $wrapper = $doc->createElement($partyWrapper);

        if (! empty($data['endpoint_id'])) {
            $el = $doc->createElement($endpointIdTag, $data['endpoint_id']);
            if (! empty($data['scheme_id'])) {
                $el->setAttribute('schemeID', $data['scheme_id']);
            }
            $wrapper->appendChild($el);
        }

        if (! empty($data['party_name'])) {
            $partyName = $doc->createElement('cac:PartyName');
            $name = $doc->createElement('cbc:Name', $data['party_name']);
            $partyName->appendChild($name);
            $wrapper->appendChild($partyName);
        }

        if (! empty($data['postal_address'])) {
            $wrapper->appendChild(self::buildPostalAddress($doc, $data['postal_address']));
        }

        if (! empty($data['party_tax_scheme'])) {
            $wrapper->appendChild(self::buildPartyTaxScheme($doc, $data['party_tax_scheme']));
        }

        if (! empty($data['party_legal_entity'])) {
            $wrapper->appendChild(self::buildPartyLegalEntity($doc, $data['party_legal_entity']));
        }

        if (! empty($data['contact'])) {
            $wrapper->appendChild(self::buildContact($doc, $data['contact']));
        }

        $parent->appendChild($wrapper);

        return $wrapper;
    }

    protected static function buildPostalAddress(DOMDocument $doc, array $data): DOMElement
    {
        $address = $doc->createElement('cac:PostalAddress');

        if (! empty($data['floor'])) {
            $address->appendChild($doc->createElement('cbc:Floor', $data['floor']));
        }

        if (! empty($data['room'])) {
            $address->appendChild($doc->createElement('cbc:Room', $data['room']));
        }

        if (! empty($data['street_name'])) {
            $address->appendChild($doc->createElement('cbc:StreetName', $data['street_name']));
        }

        if (! empty($data['additional_street_name'])) {
            $address->appendChild($doc->createElement('cbc:AdditionalStreetName', $data['additional_street_name']));
        }

        if (! empty($data['building_name'])) {
            $address->appendChild($doc->createElement('cbc:BuildingName', $data['building_name']));
        }

        if (! empty($data['city_name'])) {
            $address->appendChild($doc->createElement('cbc:CityName', $data['city_name']));
        }

        if (! empty($data['postal_zone'])) {
            $address->appendChild($doc->createElement('cbc:PostalZone', $data['postal_zone']));
        }

        if (! empty($data['country_subentity'])) {
            $address->appendChild($doc->createElement('cbc:CountrySubentity', $data['country_subentity']));
        }

        if (! empty($data['country'])) {
            $country = $doc->createElement('cac:Country');
            $country->appendChild($doc->createElement('cbc:IdentificationCode', $data['country']['identification_code'] ?? $data['country']));
            if (! empty($data['country']['name'])) {
                $country->appendChild($doc->createElement('cbc:Name', $data['country']['name']));
            }
            $address->appendChild($country);
        }

        return $address;
    }

    protected static function buildPartyTaxScheme(DOMDocument $doc, array $data): DOMElement
    {
        $taxScheme = $doc->createElement('cac:PartyTaxScheme');

        if (! empty($data['company_id'])) {
            $taxScheme->appendChild($doc->createElement('cbc:CompanyID', $data['company_id']));
        }

        $scheme = $doc->createElement('cac:TaxScheme');
        $scheme->appendChild($doc->createElement('cbc:ID', $data['tax_scheme_id'] ?? 'S'));
        $taxScheme->appendChild($scheme);

        return $taxScheme;
    }

    protected static function buildPartyLegalEntity(DOMDocument $doc, array $data): DOMElement
    {
        $entity = $doc->createElement('cac:PartyLegalEntity');

        if (! empty($data['registration_name'])) {
            $entity->appendChild($doc->createElement('cbc:RegistrationName', $data['registration_name']));
        }

        if (! empty($data['company_id'])) {
            $entity->appendChild($doc->createElement('cbc:CompanyID', $data['company_id']));
        }

        return $entity;
    }

    protected static function buildContact(DOMDocument $doc, array $data): DOMElement
    {
        $contact = $doc->createElement('cac:Contact');

        if (! empty($data['name'])) {
            $contact->appendChild($doc->createElement('cbc:Name', $data['name']));
        }

        if (! empty($data['telephone'])) {
            $contact->appendChild($doc->createElement('cbc:Telephone', $data['telephone']));
        }

        if (! empty($data['email'])) {
            $contact->appendChild($doc->createElement('cbc:ElectronicMail', $data['email']));
        }

        return $contact;
    }
}
