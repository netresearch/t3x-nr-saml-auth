<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Helper;

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
final class TestIdentityProvider
{
    private readonly string $privateKey;

    private readonly string $certificate;

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
        $this->privateKey = $privateKeyPem;
    }

    public function getCertificate(): string
    {
        return $this->certificate;
    }

    /**
     * Returns the response XML with an enveloped signature on the Response element, base64-encoded
     */
    public function sign(SamlResponseBuilder $builder): string
    {
        return base64_encode(Utils::addSign($builder->build(), $this->privateKey, $this->certificate));
    }
}
