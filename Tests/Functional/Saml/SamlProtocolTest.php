<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Saml;

use Netresearch\NrSamlAuth\Tests\Functional\Helper\MockIdpProvider;
use Netresearch\NrSamlAuth\Tests\Functional\Helper\SamlAssertionFactory;
use Netresearch\NrSamlAuth\Tests\Functional\Helper\SamlResponseBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for SAML protocol handling.
 *
 * Tests various SAML response scenarios to ensure proper processing.
 */
final class SamlProtocolTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['netresearch/nr-saml-auth'];

    private string $fixturesPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixturesPath = __DIR__ . '/../Fixtures/SamlResponses/';
    }

    #[Test]
    public function validResponseFixtureIsWellFormedXml(): void
    {
        $xml = file_get_contents($this->fixturesPath . 'valid_response_user_attributes.xml');

        $dom = new \DOMDocument();
        $result = @$dom->loadXML($xml);

        self::assertTrue($result, 'Fixture XML should be well-formed');
    }

    #[Test]
    public function validResponseFixtureContainsRequiredElements(): void
    {
        $xml = file_get_contents($this->fixturesPath . 'valid_response_user_attributes.xml');
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
        $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

        // Check required elements exist
        self::assertCount(1, $xpath->query('//samlp:Response'));
        self::assertCount(1, $xpath->query('//saml:Assertion'));
        self::assertCount(1, $xpath->query('//saml:Subject'));
        self::assertCount(1, $xpath->query('//saml:NameID'));
        self::assertCount(1, $xpath->query('//saml:Conditions'));
        self::assertCount(1, $xpath->query('//saml:AudienceRestriction'));
    }

    #[Test]
    public function validResponseFixtureContainsExpectedAttributes(): void
    {
        $xml = file_get_contents($this->fixturesPath . 'valid_response_user_attributes.xml');
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

        $attributes = $xpath->query('//saml:Attribute/@Name');
        $attributeNames = [];
        foreach ($attributes as $attr) {
            $attributeNames[] = $attr->nodeValue;
        }

        self::assertContains('email', $attributeNames);
        self::assertContains('firstName', $attributeNames);
        self::assertContains('lastName', $attributeNames);
        self::assertContains('groups', $attributeNames);
    }

    #[Test]
    public function expiredAssertionFixtureHasPastTimestamps(): void
    {
        $xml = file_get_contents($this->fixturesPath . 'expired_assertion.xml');
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

        $conditions = $xpath->query('//saml:Conditions')->item(0);
        $notOnOrAfter = new \DateTimeImmutable($conditions->getAttribute('NotOnOrAfter'));

        self::assertLessThan(
            new \DateTimeImmutable(),
            $notOnOrAfter,
            'Expired assertion should have NotOnOrAfter in the past'
        );
    }

    #[Test]
    public function wrongAudienceFixtureHasIncorrectAudience(): void
    {
        $xml = file_get_contents($this->fixturesPath . 'wrong_audience.xml');
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

        $audience = $xpath->query('//saml:Audience')->item(0)->nodeValue;

        self::assertSame('https://wrong-sp.example.com', $audience);
        self::assertNotSame('https://sp.example.com', $audience);
    }

    #[Test]
    public function invalidSignatureFixtureContainsTamperedSignature(): void
    {
        $xml = file_get_contents($this->fixturesPath . 'invalid_signature.xml');

        self::assertStringContainsString('INVALID_SIGNATURE_VALUE', $xml);
        self::assertStringContainsString('INVALID_DIGEST_VALUE', $xml);
    }

    #[Test]
    public function failedStatusFixtureContainsErrorStatus(): void
    {
        $xml = file_get_contents($this->fixturesPath . 'failed_status.xml');
        $dom = new \DOMDocument();
        $dom->loadXML($xml);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');

        $statusCode = $xpath->query('//samlp:StatusCode/@Value')->item(0)->nodeValue;
        self::assertSame('urn:oasis:names:tc:SAML:2.0:status:Responder', $statusCode);

        // Should not contain an assertion
        $assertions = $xpath->query('//saml:Assertion');
        self::assertCount(0, $assertions);
    }

    #[Test]
    public function samlResponseBuilderCreatesEquivalentToFixture(): void
    {
        $builder = SamlResponseBuilder::validResponse()
            ->withIssuer('https://idp.example.com')
            ->withDestination('https://sp.example.com/acs')
            ->withAudience('https://sp.example.com')
            ->withNameId('john.doe@example.com');

        $xml = $builder->build();

        $dom = new \DOMDocument();
        $result = $dom->loadXML($xml);

        self::assertTrue($result);

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');

        $nameId = $xpath->query('//saml:NameID')->item(0)->nodeValue;
        self::assertSame('john.doe@example.com', $nameId);
    }

    #[Test]
    public function mockIdpProviderGeneratesValidResponses(): void
    {
        $idp = MockIdpProvider::createWithTestUsers();

        $response = $idp->authenticate(
            'admin@example.com',
            'https://sp.example.com/acs',
            'https://sp.example.com'
        );

        self::assertArrayHasKey('SAMLResponse', $response);

        $decoded = base64_decode($response['SAMLResponse'], true);
        self::assertNotFalse($decoded);

        $dom = new \DOMDocument();
        self::assertTrue(@$dom->loadXML($decoded));
    }

    #[Test]
    public function mockIdpProviderIncludesUserAttributes(): void
    {
        $idp = MockIdpProvider::createWithTestUsers();

        $response = $idp->authenticate(
            'admin@example.com',
            'https://sp.example.com/acs',
            'https://sp.example.com'
        );

        $decoded = base64_decode($response['SAMLResponse'], true);

        self::assertStringContainsString('admin@example.com', $decoded);
        self::assertStringContainsString('Admin', $decoded);
    }

    #[Test]
    public function mockIdpProviderGeneratesFailureResponses(): void
    {
        $idp = MockIdpProvider::create();

        $response = $idp->authenticateFailure(
            'https://sp.example.com/acs',
            'https://sp.example.com',
            'urn:oasis:names:tc:SAML:2.0:status:AuthnFailed'
        );

        $decoded = base64_decode($response['SAMLResponse'], true);

        self::assertStringContainsString('AuthnFailed', $decoded);
    }

    #[Test]
    public function mockIdpProviderGeneratesValidMetadata(): void
    {
        $idp = MockIdpProvider::create()
            ->withEntityId('https://custom-idp.example.com')
            ->withSsoUrl('https://custom-idp.example.com/sso');

        $metadata = $idp->getMetadataXml();

        $dom = new \DOMDocument();
        self::assertTrue(@$dom->loadXML($metadata));

        self::assertStringContainsString('https://custom-idp.example.com', $metadata);
        self::assertStringContainsString('IDPSSODescriptor', $metadata);
    }

    #[Test]
    public function samlAssertionFactoryCreatesTypo3FrontendUserAssertion(): void
    {
        $builder = SamlAssertionFactory::createTypo3FrontendUserAssertion(
            'johndoe',
            'john@example.com',
            'John',
            'Doe',
            ['fe_users']
        );

        $xml = $builder->build();

        self::assertStringContainsString('johndoe', $xml);
        self::assertStringContainsString('john@example.com', $xml);
        self::assertStringContainsString('John', $xml);
        self::assertStringContainsString('Doe', $xml);
        self::assertStringContainsString('fe_users', $xml);
    }

    #[Test]
    public function samlAssertionFactoryCreatesTestBatch(): void
    {
        $batch = SamlAssertionFactory::createTestBatch();

        self::assertArrayHasKey('valid_standard', $batch);
        self::assertArrayHasKey('valid_minimal', $batch);
        self::assertArrayHasKey('expired', $batch);
        self::assertArrayHasKey('future', $batch);
        self::assertArrayHasKey('wrong_audience', $batch);
        self::assertArrayHasKey('failed_responder', $batch);
        self::assertArrayHasKey('failed_authn', $batch);

        foreach ($batch as $name => $builder) {
            self::assertInstanceOf(
                SamlResponseBuilder::class,
                $builder,
                "Batch item '$name' should be a SamlResponseBuilder"
            );
        }
    }

    #[Test]
    #[DataProvider('fixtureFilesProvider')]
    public function allFixtureFilesAreWellFormedXml(string $filename): void
    {
        $xml = file_get_contents($this->fixturesPath . $filename);

        $dom = new \DOMDocument();
        $result = @$dom->loadXML($xml);

        self::assertTrue($result, "Fixture '$filename' should be well-formed XML");
    }

    public static function fixtureFilesProvider(): array
    {
        return [
            'valid_response_user_attributes' => ['valid_response_user_attributes.xml'],
            'valid_response_minimal' => ['valid_response_minimal.xml'],
            'expired_assertion' => ['expired_assertion.xml'],
            'wrong_audience' => ['wrong_audience.xml'],
            'invalid_signature' => ['invalid_signature.xml'],
            'failed_status' => ['failed_status.xml'],
        ];
    }
}
