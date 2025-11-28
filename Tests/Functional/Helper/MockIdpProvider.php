<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Helper;

/**
 * Mock Identity Provider for SAML integration testing.
 *
 * Simulates IdP responses without requiring a real SAML IdP.
 */
final class MockIdpProvider
{
    private string $entityId = 'https://mock-idp.example.com';
    private string $ssoUrl = 'https://mock-idp.example.com/sso';
    private string $sloUrl = 'https://mock-idp.example.com/slo';
    private ?string $certificate = null;
    private array $defaultAttributes = [];
    private array $userDatabase = [];

    public function __construct()
    {
        $this->defaultAttributes = [
            'email' => 'user@example.com',
            'firstName' => 'Mock',
            'lastName' => 'User',
        ];
    }

    public function withEntityId(string $entityId): self
    {
        $clone = clone $this;
        $clone->entityId = $entityId;
        return $clone;
    }

    public function withSsoUrl(string $ssoUrl): self
    {
        $clone = clone $this;
        $clone->ssoUrl = $ssoUrl;
        return $clone;
    }

    public function withSloUrl(string $sloUrl): self
    {
        $clone = clone $this;
        $clone->sloUrl = $sloUrl;
        return $clone;
    }

    public function withCertificate(string $certificate): self
    {
        $clone = clone $this;
        $clone->certificate = $certificate;
        return $clone;
    }

    public function withDefaultAttributes(array $attributes): self
    {
        $clone = clone $this;
        $clone->defaultAttributes = $attributes;
        return $clone;
    }

    /**
     * Register a mock user that can authenticate.
     */
    public function registerUser(string $username, array $attributes): self
    {
        $clone = clone $this;
        $clone->userDatabase[$username] = $attributes;
        return $clone;
    }

    /**
     * Simulate an authentication request and return a SAML Response.
     */
    public function authenticate(
        string $username,
        string $acsUrl,
        string $spEntityId,
        ?string $relayState = null
    ): array {
        $attributes = $this->userDatabase[$username] ?? $this->defaultAttributes;

        $responseBuilder = (new SamlResponseBuilder())
            ->withIssuer($this->entityId)
            ->withDestination($acsUrl)
            ->withAudience($spEntityId)
            ->withNameId($username)
            ->withAttributes($attributes);

        return [
            'SAMLResponse' => $responseBuilder->buildBase64Encoded(),
            'RelayState' => $relayState,
        ];
    }

    /**
     * Simulate a failed authentication.
     */
    public function authenticateFailure(
        string $acsUrl,
        string $spEntityId,
        string $statusCode = 'urn:oasis:names:tc:SAML:2.0:status:AuthnFailed',
        ?string $relayState = null
    ): array {
        $responseBuilder = (new SamlResponseBuilder())
            ->withIssuer($this->entityId)
            ->withDestination($acsUrl)
            ->withAudience($spEntityId)
            ->withFailedStatus($statusCode);

        return [
            'SAMLResponse' => $responseBuilder->buildBase64Encoded(),
            'RelayState' => $relayState,
        ];
    }

    /**
     * Get IdP metadata for configuration.
     */
    public function getMetadata(): array
    {
        return [
            'idp' => [
                'entityId' => $this->entityId,
                'singleSignOnService' => [
                    'url' => $this->ssoUrl,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'singleLogoutService' => [
                    'url' => $this->sloUrl,
                    'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
                ],
                'x509cert' => $this->certificate ?? $this->getDefaultCertificate(),
            ],
        ];
    }

    /**
     * Generate IdP metadata XML.
     */
    public function getMetadataXml(): string
    {
        $cert = $this->certificate ?? $this->getDefaultCertificate();
        $certClean = str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r"], '', $cert);

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<EntityDescriptor xmlns="urn:oasis:names:tc:SAML:2.0:metadata"
                  entityID="{$this->entityId}">
    <IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <KeyDescriptor use="signing">
            <KeyInfo xmlns="http://www.w3.org/2000/09/xmldsig#">
                <X509Data>
                    <X509Certificate>{$certClean}</X509Certificate>
                </X509Data>
            </KeyInfo>
        </KeyDescriptor>
        <SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                             Location="{$this->sloUrl}"/>
        <NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</NameIDFormat>
        <SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                             Location="{$this->ssoUrl}"/>
    </IDPSSODescriptor>
</EntityDescriptor>
XML;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getSsoUrl(): string
    {
        return $this->ssoUrl;
    }

    public function getSloUrl(): string
    {
        return $this->sloUrl;
    }

    /**
     * Creates a preconfigured mock IdP for testing.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Creates a mock IdP with common test users.
     */
    public static function createWithTestUsers(): self
    {
        return (new self())
            ->registerUser('admin@example.com', [
                'email' => 'admin@example.com',
                'firstName' => 'Admin',
                'lastName' => 'User',
                'groups' => ['admins', 'users'],
            ])
            ->registerUser('user@example.com', [
                'email' => 'user@example.com',
                'firstName' => 'Regular',
                'lastName' => 'User',
                'groups' => ['users'],
            ])
            ->registerUser('editor@example.com', [
                'email' => 'editor@example.com',
                'firstName' => 'Content',
                'lastName' => 'Editor',
                'groups' => ['editors', 'users'],
            ]);
    }

    /**
     * Get a default self-signed certificate for testing.
     */
    private function getDefaultCertificate(): string
    {
        return <<<CERT
-----BEGIN CERTIFICATE-----
MIICpDCCAYwCCQDU+pQ4P3UtbzANBgkqhkiG9w0BAQsFADAUMRIwEAYDVQQDDAls
b2NhbGhvc3QwHhcNMjQwMTAxMDAwMDAwWhcNMjUwMTAxMDAwMDAwWjAUMRIwEAYD
VQQDDAlsb2NhbGhvc3QwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQC7
o5e7XvJmFHBQowg3OH4wYZP+RRZ4h3rO9aLZvM8KZzWDd7VJWHhESpRYmPu0bnPe
xEoH3sN+pPvCugxUoI7GORw3sYr4IVj8JQQqC3+WmgCqLxu5pGaK0ycvZGbIz0qp
kL6bR9F7E1VH0lMRjCcQnSRoGEVjP3WXH1VlpKCFz3bvaKR5B7qLQXWJC8vVFJyU
bXE0xN3W2xvLTB8F0VPiNDXpXh5LM3GxLWHN5fMaNqPfE+JjF7mC7DXeCI/y6dnZ
QQP0JF8yMFP5DAcOPfvlSqEAgUpK9IypLe/z0HJr7LFmBcXKMxLNNQY/BZ2UT0YG
T7KBpxh1aPFM9kq5r4l1AgMBAAEwDQYJKoZIhvcNAQELBQADggEBAHVKSbDddMi8
Y7zK5aEXL4ZKKLbUF4TGHjlJE1VIu9WW+K7BgQc2RPbSXAqrWTn/UEzYCXqGEC/2
KRAKiFg5Vmk4rIpZBPR1VH6HT+kvHk5fPhDM3GxQiRK6K+7e0u4Lp5zjzMV4EUoX
v0C5XVCpk0v3ON6S0tnVnJXDQGIwW2lFbnfzP4tXk8VgR7F9GhsH7K4PdGdLgQrr
ehIJBJPpS7BN9LBad96u4mS9MSPW3VaReAPxvmS1aphJGsBhXe7MnYzNJQGyC8By
k8G/fDvl2G0PG5nKLCVoF4TdFQ9R7BfT3WmBvIXwFHRey3VThgQoSt8U3RVx4tCi
mL5PAm5KI7Y=
-----END CERTIFICATE-----
CERT;
    }
}
