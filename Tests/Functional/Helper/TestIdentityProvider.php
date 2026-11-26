<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Helper;

use DOMDocument;
use DOMElement;
use OneLogin\Saml2\Constants;
use OneLogin\Saml2\Utils;
use RuntimeException;

/**
 * Signs SAML responses like an identity provider does.
 *
 * The key pair and the self-signed certificate are generated when the object
 * is created, so no key material is stored in the repository. Two instances
 * have different keys: a response signed by one does not validate against the
 * certificate of the other.
 */
final readonly class TestIdentityProvider
{
    private string $privateKey;

    private string $certificate;

    public function __construct()
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        if ($key === false) {
            throw new RuntimeException('Cannot create a test key pair', 1759750001);
        }

        $csr = openssl_csr_new(['commonName' => 'idp.example.com'], $key, ['digest_alg' => 'sha256']);
        if ($csr === false || $csr === true) {
            throw new RuntimeException('Cannot create a test certificate request', 1759750002);
        }

        $certificate = openssl_csr_sign($csr, null, $key, 1, ['digest_alg' => 'sha256']);
        if ($certificate === false
            || !openssl_x509_export($certificate, $certificatePem)
            || !openssl_pkey_export($key, $privateKeyPem)
        ) {
            throw new RuntimeException('Cannot export the test certificate', 1759750003);
        }

        $this->certificate = $certificatePem;
        $this->privateKey  = $privateKeyPem;
    }

    public function getCertificate(): string
    {
        return $this->certificate;
    }

    /**
     * Returns the response XML with an enveloped signature on the Response element, base64-encoded.
     */
    public function sign(SamlResponseBuilder $builder): string
    {
        return base64_encode(Utils::addSign($builder->build(), $this->privateKey, $this->certificate));
    }

    /**
     * Returns the response XML with an enveloped signature on the Assertion
     * element only, base64-encoded. The Response element around it is not
     * signed, so its attributes can be changed without breaking the
     * signature, as many identity providers issue responses.
     */
    public function signAssertion(SamlResponseBuilder $builder): string
    {
        $response = new DOMDocument();
        if (!$response->loadXML($builder->build())) {
            throw new RuntimeException('The response to sign is not well-formed XML', 1764190001);
        }

        $assertion = $response->getElementsByTagNameNS(Constants::NS_SAML, 'Assertion')->item(0);
        if (!$assertion instanceof DOMElement) {
            throw new RuntimeException('The response to sign has no assertion', 1764190002);
        }

        // Utils::addSign() signs the document element, so the assertion is
        // signed on its own document
        $signed = new DOMDocument();
        $signed->appendChild($signed->importNode($assertion, true));
        $signed = Utils::addSign($signed, $this->privateKey, $this->certificate);

        $signedAssertion = new DOMDocument();
        if (!$signedAssertion->loadXML($signed) || !$signedAssertion->documentElement instanceof DOMElement) {
            throw new RuntimeException('Cannot sign the assertion', 1764190003);
        }

        // addSign() puts the signature first, the schema wants it after the
        // Issuer; the enveloped signature does not cover its own position
        $signature = $signedAssertion->getElementsByTagNameNS(Constants::NS_DS, 'Signature')->item(0);
        $issuer    = $signedAssertion->getElementsByTagNameNS(Constants::NS_SAML, 'Issuer')->item(0);
        if ($signature === null || $issuer === null) {
            throw new RuntimeException('The signed assertion has no signature or no issuer', 1764190004);
        }
        $signedAssertion->documentElement->insertBefore($signature, $issuer->nextSibling);

        $assertion->parentNode?->replaceChild($response->importNode($signedAssertion->documentElement, true), $assertion);

        return base64_encode((string) $response->saveXML());
    }
}
