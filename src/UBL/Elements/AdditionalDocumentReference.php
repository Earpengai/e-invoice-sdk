<?php

namespace CamInv\EInvoice\UBL\Elements;

use DOMDocument;
use DOMElement;

class AdditionalDocumentReference
{
    public static function build(DOMDocument $doc, DOMElement $parent, array $refs): void
    {
        foreach ($refs as $data) {
            $ref = $doc->createElement('cac:AdditionalDocumentReference');

            if (! empty($data['id'])) {
                $ref->appendChild($doc->createElement('cbc:ID', $data['id']));
            }

            if (! empty($data['document_description'])) {
                $ref->appendChild($doc->createElement('cbc:DocumentDescription', $data['document_description']));
            }

            if (! empty($data['attachment'])) {
                $attachment = $doc->createElement('cac:Attachment');

                if (! empty($data['attachment']['embedded_document_binary_object'])) {
                    $binary = $data['attachment']['embedded_document_binary_object'];
                    $emb = $doc->createElement('cbc:EmbeddedDocumentBinaryObject', $binary['content'] ?? '');
                    if (! empty($binary['mime_code'])) {
                        $emb->setAttribute('mimeCode', $binary['mime_code']);
                    }
                    if (! empty($binary['filename'])) {
                        $emb->setAttribute('filename', $binary['filename']);
                    }
                    $attachment->appendChild($emb);
                }

                if (! empty($data['attachment']['external_reference'])) {
                    $erData = $data['attachment']['external_reference'];
                    $er = $doc->createElement('cac:ExternalReference');
                    if (! empty($erData['uri'])) {
                        $er->appendChild($doc->createElement('cbc:URI', $erData['uri']));
                    }
                    $attachment->appendChild($er);
                }

                $ref->appendChild($attachment);
            }

            $parent->appendChild($ref);
        }
    }
}
