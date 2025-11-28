<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Helper;

/**
 * Builds SAML Response XML for testing purposes.
 *
 * This builder creates valid SAML 2.0 responses that can be customized
 * for various test scenarios including valid responses, expired assertions,
 * wrong audience, and other edge cases.
 */
final class SamlResponseBuilder
{
    private string $issuer = 'https://idp.example.com';
    private string $destination = 'https://sp.example.com/acs';
    private string $audience = 'https://sp.example.com';
    private string $nameId = 'user@example.com';
    private string $nameIdFormat = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';
    private string $sessionIndex = '_session_index_12345';
    private array $attributes = [];
    private ?\DateTimeImmutable $issueInstant = null;
    private ?\DateTimeImmutable $notBefore = null;
    private ?\DateTimeImmutable $notOnOrAfter = null;
    private string $responseId;
    private string $assertionId;
    private string $statusCode = 'urn:oasis:names:tc:SAML:2.0:status:Success';
    private bool $includeSignature = false;
    private bool $encrypted = false;

    public function __construct()
    {
        $this->responseId = '_' . bin2hex(random_bytes(16));
        $this->assertionId = '_' . bin2hex(random_bytes(16));
        $this->issueInstant = new \DateTimeImmutable();
        $this->notBefore = $this->issueInstant->modify('-5 minutes');
        $this->notOnOrAfter = $this->issueInstant->modify('+5 minutes');
    }

    public function withIssuer(string $issuer): self
    {
        $clone = clone $this;
        $clone->issuer = $issuer;
        return $clone;
    }

    public function withDestination(string $destination): self
    {
        $clone = clone $this;
        $clone->destination = $destination;
        return $clone;
    }

    public function withAudience(string $audience): self
    {
        $clone = clone $this;
        $clone->audience = $audience;
        return $clone;
    }

    public function withNameId(string $nameId, ?string $format = null): self
    {
        $clone = clone $this;
        $clone->nameId = $nameId;
        if ($format !== null) {
            $clone->nameIdFormat = $format;
        }
        return $clone;
    }

    public function withAttribute(string $name, string|array $value, ?string $nameFormat = null): self
    {
        $clone = clone $this;
        $clone->attributes[$name] = [
            'value' => is_array($value) ? $value : [$value],
            'nameFormat' => $nameFormat ?? 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic',
        ];
        return $clone;
    }

    public function withAttributes(array $attributes): self
    {
        $clone = clone $this;
        foreach ($attributes as $name => $value) {
            $clone = $clone->withAttribute($name, $value);
        }
        return $clone;
    }

    public function withIssueInstant(\DateTimeImmutable $instant): self
    {
        $clone = clone $this;
        $clone->issueInstant = $instant;
        return $clone;
    }

    public function withNotBefore(\DateTimeImmutable $notBefore): self
    {
        $clone = clone $this;
        $clone->notBefore = $notBefore;
        return $clone;
    }

    public function withNotOnOrAfter(\DateTimeImmutable $notOnOrAfter): self
    {
        $clone = clone $this;
        $clone->notOnOrAfter = $notOnOrAfter;
        return $clone;
    }

    public function expired(): self
    {
        $clone = clone $this;
        $clone->issueInstant = new \DateTimeImmutable('-1 hour');
        $clone->notBefore = $clone->issueInstant->modify('-5 minutes');
        $clone->notOnOrAfter = $clone->issueInstant->modify('+5 minutes');
        return $clone;
    }

    public function notYetValid(): self
    {
        $clone = clone $this;
        $clone->issueInstant = new \DateTimeImmutable('+1 hour');
        $clone->notBefore = $clone->issueInstant;
        $clone->notOnOrAfter = $clone->issueInstant->modify('+5 minutes');
        return $clone;
    }

    public function withFailedStatus(string $statusCode = 'urn:oasis:names:tc:SAML:2.0:status:Responder'): self
    {
        $clone = clone $this;
        $clone->statusCode = $statusCode;
        return $clone;
    }

    public function withSessionIndex(string $sessionIndex): self
    {
        $clone = clone $this;
        $clone->sessionIndex = $sessionIndex;
        return $clone;
    }

    public function signed(): self
    {
        $clone = clone $this;
        $clone->includeSignature = true;
        return $clone;
    }

    public function encrypted(): self
    {
        $clone = clone $this;
        $clone->encrypted = true;
        return $clone;
    }

    public function build(): string
    {
        $issueInstant = $this->issueInstant->format('Y-m-d\TH:i:s\Z');
        $notBefore = $this->notBefore->format('Y-m-d\TH:i:s\Z');
        $notOnOrAfter = $this->notOnOrAfter->format('Y-m-d\TH:i:s\Z');

        $attributeStatements = $this->buildAttributeStatement();
        $signature = $this->includeSignature ? $this->buildSignaturePlaceholder() : '';

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                ID="{$this->responseId}"
                Version="2.0"
                IssueInstant="{$issueInstant}"
                Destination="{$this->destination}">
    <saml:Issuer>{$this->issuer}</saml:Issuer>
    <samlp:Status>
        <samlp:StatusCode Value="{$this->statusCode}"/>
    </samlp:Status>
    <saml:Assertion xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                    xmlns:xs="http://www.w3.org/2001/XMLSchema"
                    ID="{$this->assertionId}"
                    Version="2.0"
                    IssueInstant="{$issueInstant}">
        <saml:Issuer>{$this->issuer}</saml:Issuer>
        {$signature}
        <saml:Subject>
            <saml:NameID Format="{$this->nameIdFormat}">{$this->nameId}</saml:NameID>
            <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
                <saml:SubjectConfirmationData NotOnOrAfter="{$notOnOrAfter}"
                                               Recipient="{$this->destination}"/>
            </saml:SubjectConfirmation>
        </saml:Subject>
        <saml:Conditions NotBefore="{$notBefore}" NotOnOrAfter="{$notOnOrAfter}">
            <saml:AudienceRestriction>
                <saml:Audience>{$this->audience}</saml:Audience>
            </saml:AudienceRestriction>
        </saml:Conditions>
        <saml:AuthnStatement AuthnInstant="{$issueInstant}" SessionIndex="{$this->sessionIndex}">
            <saml:AuthnContext>
                <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef>
            </saml:AuthnContext>
        </saml:AuthnStatement>
        {$attributeStatements}
    </saml:Assertion>
</samlp:Response>
XML;

        return $xml;
    }

    public function buildBase64Encoded(): string
    {
        return base64_encode($this->build());
    }

    private function buildAttributeStatement(): string
    {
        if (empty($this->attributes)) {
            return '';
        }

        $statements = '<saml:AttributeStatement>';
        foreach ($this->attributes as $name => $config) {
            $nameFormat = $config['nameFormat'];
            $values = $config['value'];

            $statements .= sprintf(
                '<saml:Attribute Name="%s" NameFormat="%s">',
                htmlspecialchars($name, ENT_XML1),
                htmlspecialchars($nameFormat, ENT_XML1)
            );

            foreach ($values as $value) {
                $statements .= sprintf(
                    '<saml:AttributeValue xsi:type="xs:string">%s</saml:AttributeValue>',
                    htmlspecialchars((string)$value, ENT_XML1)
                );
            }

            $statements .= '</saml:Attribute>';
        }
        $statements .= '</saml:AttributeStatement>';

        return $statements;
    }

    private function buildSignaturePlaceholder(): string
    {
        return <<<XML
<ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
            <ds:SignedInfo>
                <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
                <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256"/>
                <ds:Reference URI="#{$this->assertionId}">
                    <ds:Transforms>
                        <ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature"/>
                        <ds:Transform Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#"/>
                    </ds:Transforms>
                    <ds:DigestMethod Algorithm="http://www.w3.org/2001/04/xmlenc#sha256"/>
                    <ds:DigestValue>PLACEHOLDER_DIGEST</ds:DigestValue>
                </ds:Reference>
            </ds:SignedInfo>
            <ds:SignatureValue>PLACEHOLDER_SIGNATURE</ds:SignatureValue>
        </ds:Signature>
XML;
    }

    public static function validResponse(): self
    {
        return (new self())
            ->withAttributes([
                'email' => 'user@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'groups' => ['users', 'admins'],
            ]);
    }

    public static function minimalResponse(): self
    {
        return new self();
    }

    public static function expiredResponse(): self
    {
        return (new self())->expired();
    }

    public static function wrongAudienceResponse(): self
    {
        return (new self())->withAudience('https://wrong-audience.example.com');
    }
}
