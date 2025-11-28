<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Unit\Helper;

use Netresearch\NrSamlAuth\Tests\Functional\Helper\SamlResponseBuilder;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for the SamlResponseBuilder helper class.
 */
final class SamlResponseBuilderTest extends UnitTestCase
{
    #[Test]
    public function buildCreatesValidXml(): void
    {
        $builder = new SamlResponseBuilder();
        $xml = $builder->build();

        $dom = new \DOMDocument();
        $result = $dom->loadXML($xml);

        self::assertTrue($result, 'Generated XML should be valid');
    }

    #[Test]
    public function buildContainsCorrectIssuer(): void
    {
        $builder = (new SamlResponseBuilder())
            ->withIssuer('https://custom-idp.example.com');

        $xml = $builder->build();

        self::assertStringContainsString(
            '<saml:Issuer>https://custom-idp.example.com</saml:Issuer>',
            $xml
        );
    }

    #[Test]
    public function buildContainsCorrectDestination(): void
    {
        $builder = (new SamlResponseBuilder())
            ->withDestination('https://my-sp.example.com/acs');

        $xml = $builder->build();

        self::assertStringContainsString(
            'Destination="https://my-sp.example.com/acs"',
            $xml
        );
    }

    #[Test]
    public function buildContainsCorrectAudience(): void
    {
        $builder = (new SamlResponseBuilder())
            ->withAudience('https://my-audience.example.com');

        $xml = $builder->build();

        self::assertStringContainsString(
            '<saml:Audience>https://my-audience.example.com</saml:Audience>',
            $xml
        );
    }

    #[Test]
    public function buildContainsCorrectNameId(): void
    {
        $builder = (new SamlResponseBuilder())
            ->withNameId('testuser@example.com');

        $xml = $builder->build();

        self::assertStringContainsString(
            '>testuser@example.com</saml:NameID>',
            $xml
        );
    }

    #[Test]
    public function buildContainsAttributes(): void
    {
        $builder = (new SamlResponseBuilder())
            ->withAttribute('email', 'user@example.com')
            ->withAttribute('firstName', 'John');

        $xml = $builder->build();

        self::assertStringContainsString('Name="email"', $xml);
        self::assertStringContainsString('Name="firstName"', $xml);
        self::assertStringContainsString('>user@example.com</saml:AttributeValue>', $xml);
        self::assertStringContainsString('>John</saml:AttributeValue>', $xml);
    }

    #[Test]
    public function buildContainsMultipleAttributeValues(): void
    {
        $builder = (new SamlResponseBuilder())
            ->withAttribute('groups', ['admins', 'users', 'editors']);

        $xml = $builder->build();

        self::assertStringContainsString('>admins</saml:AttributeValue>', $xml);
        self::assertStringContainsString('>users</saml:AttributeValue>', $xml);
        self::assertStringContainsString('>editors</saml:AttributeValue>', $xml);
    }

    #[Test]
    public function expiredSetsTimestampsInPast(): void
    {
        $builder = (new SamlResponseBuilder())->expired();
        $xml = $builder->build();

        // The NotOnOrAfter should be in the past
        preg_match('/NotOnOrAfter="([^"]+)"/', $xml, $matches);
        self::assertNotEmpty($matches[1]);

        $notOnOrAfter = new \DateTimeImmutable($matches[1]);
        self::assertLessThan(new \DateTimeImmutable(), $notOnOrAfter);
    }

    #[Test]
    public function notYetValidSetsTimestampsInFuture(): void
    {
        $builder = (new SamlResponseBuilder())->notYetValid();
        $xml = $builder->build();

        preg_match('/NotBefore="([^"]+)"/', $xml, $matches);
        self::assertNotEmpty($matches[1]);

        $notBefore = new \DateTimeImmutable($matches[1]);
        self::assertGreaterThan(new \DateTimeImmutable(), $notBefore);
    }

    #[Test]
    public function withFailedStatusSetsCorrectStatusCode(): void
    {
        $builder = (new SamlResponseBuilder())
            ->withFailedStatus('urn:oasis:names:tc:SAML:2.0:status:AuthnFailed');

        $xml = $builder->build();

        self::assertStringContainsString(
            'Value="urn:oasis:names:tc:SAML:2.0:status:AuthnFailed"',
            $xml
        );
    }

    #[Test]
    public function buildBase64EncodedReturnsValidBase64(): void
    {
        $builder = new SamlResponseBuilder();
        $encoded = $builder->buildBase64Encoded();

        $decoded = base64_decode($encoded, true);
        self::assertNotFalse($decoded);
        self::assertStringContainsString('<samlp:Response', $decoded);
    }

    #[Test]
    public function validResponseFactoryMethodCreatesResponseWithAttributes(): void
    {
        $builder = SamlResponseBuilder::validResponse();
        $xml = $builder->build();

        self::assertStringContainsString('Name="email"', $xml);
        self::assertStringContainsString('Name="firstName"', $xml);
        self::assertStringContainsString('Name="lastName"', $xml);
    }

    #[Test]
    public function minimalResponseFactoryMethodCreatesResponseWithoutAttributes(): void
    {
        $builder = SamlResponseBuilder::minimalResponse();
        $xml = $builder->build();

        self::assertStringNotContainsString('<saml:AttributeStatement>', $xml);
    }

    #[Test]
    public function wrongAudienceResponseFactoryMethodSetsWrongAudience(): void
    {
        $builder = SamlResponseBuilder::wrongAudienceResponse();
        $xml = $builder->build();

        self::assertStringContainsString(
            '<saml:Audience>https://wrong-audience.example.com</saml:Audience>',
            $xml
        );
    }

    #[Test]
    public function signedAddsSignaturePlaceholder(): void
    {
        $builder = (new SamlResponseBuilder())->signed();
        $xml = $builder->build();

        self::assertStringContainsString('<ds:Signature', $xml);
        self::assertStringContainsString('PLACEHOLDER_SIGNATURE', $xml);
    }

    #[Test]
    public function withSessionIndexSetsSessionIndex(): void
    {
        $builder = (new SamlResponseBuilder())
            ->withSessionIndex('_custom_session_123');

        $xml = $builder->build();

        self::assertStringContainsString('SessionIndex="_custom_session_123"', $xml);
    }

    #[Test]
    public function xmlEscapesSpecialCharacters(): void
    {
        $builder = (new SamlResponseBuilder())
            ->withAttribute('company', 'Foo & Bar <Inc>');

        $xml = $builder->build();

        self::assertStringContainsString('Foo &amp; Bar &lt;Inc&gt;', $xml);
    }
}
