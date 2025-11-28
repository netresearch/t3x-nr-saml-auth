<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Unit\Domain\Model;

use Netresearch\NrSamlAuth\Domain\Model\Settings;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SettingsTest extends UnitTestCase
{
    private Settings $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new Settings();
    }

    #[Test]
    public function getNameReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getName());
    }

    #[Test]
    public function setNameSetsName(): void
    {
        $this->subject->setName('Test Configuration');
        self::assertSame('Test Configuration', $this->subject->getName());
    }

    #[Test]
    public function setNameReturnsSelf(): void
    {
        $result = $this->subject->setName('Test');
        self::assertSame($this->subject, $result);
    }

    #[Test]
    public function getRedirectUrlReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getRedirectUrl());
    }

    #[Test]
    public function setRedirectUrlSetsRedirectUrl(): void
    {
        $this->subject->setRedirectUrl('https://example.com/redirect');
        self::assertSame('https://example.com/redirect', $this->subject->getRedirectUrl());
    }

    #[Test]
    public function getSpEntityIdReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getSpEntityId());
    }

    #[Test]
    public function setSpEntityIdSetsSpEntityId(): void
    {
        $this->subject->setSpEntityId('https://sp.example.com');
        self::assertSame('https://sp.example.com', $this->subject->getSpEntityId());
    }

    #[Test]
    public function getSpCustomerServiceUrlReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getSpCustomerServiceUrl());
    }

    #[Test]
    public function setSpCustomerServiceUrlSetsSpCustomerServiceUrl(): void
    {
        $this->subject->setSpCustomerServiceUrl('https://sp.example.com/acs');
        self::assertSame('https://sp.example.com/acs', $this->subject->getSpCustomerServiceUrl());
    }

    #[Test]
    public function getSpCustomerServiceBindingReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getSpCustomerServiceBinding());
    }

    #[Test]
    public function setSpCustomerServiceBindingSetsSpCustomerServiceBinding(): void
    {
        $binding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST';
        $this->subject->setSpCustomerServiceBinding($binding);
        self::assertSame($binding, $this->subject->getSpCustomerServiceBinding());
    }

    #[Test]
    public function getSpNameIdFormatReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getSpNameIdFormat());
    }

    #[Test]
    public function setSpNameIdFormatSetsSpNameIdFormat(): void
    {
        $format = 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress';
        $this->subject->setSpNameIdFormat($format);
        self::assertSame($format, $this->subject->getSpNameIdFormat());
    }

    #[Test]
    public function getSpCertReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getSpCert());
    }

    #[Test]
    public function setSpCertSetsSpCert(): void
    {
        $cert = '-----BEGIN CERTIFICATE-----\nMIIC...\n-----END CERTIFICATE-----';
        $this->subject->setSpCert($cert);
        self::assertSame($cert, $this->subject->getSpCert());
    }

    #[Test]
    public function getSpKeyReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getSpKey());
    }

    #[Test]
    public function setSpKeySetsSpKey(): void
    {
        $key = '-----BEGIN PRIVATE KEY-----\nMIIE...\n-----END PRIVATE KEY-----';
        $this->subject->setSpKey($key);
        self::assertSame($key, $this->subject->getSpKey());
    }

    #[Test]
    public function getIdpEntityIdReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getIdpEntityId());
    }

    #[Test]
    public function setIdpEntityIdSetsIdpEntityId(): void
    {
        $this->subject->setIdpEntityId('https://idp.example.com');
        self::assertSame('https://idp.example.com', $this->subject->getIdpEntityId());
    }

    #[Test]
    public function getIdpSsoUrlReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getIdpSsoUrl());
    }

    #[Test]
    public function setIdpSsoUrlSetsIdpSsoUrl(): void
    {
        $this->subject->setIdpSsoUrl('https://idp.example.com/sso');
        self::assertSame('https://idp.example.com/sso', $this->subject->getIdpSsoUrl());
    }

    #[Test]
    public function getIdpSsoBindingReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getIdpSsoBinding());
    }

    #[Test]
    public function setIdpSsoBindingSetsIdpSsoBinding(): void
    {
        $binding = 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect';
        $this->subject->setIdpSsoBinding($binding);
        self::assertSame($binding, $this->subject->getIdpSsoBinding());
    }

    #[Test]
    public function getIdpLogoutUrlReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getIdpLogoutUrl());
    }

    #[Test]
    public function setIdpLogoutUrlSetsIdpLogoutUrl(): void
    {
        $this->subject->setIdpLogoutUrl('https://idp.example.com/logout');
        self::assertSame('https://idp.example.com/logout', $this->subject->getIdpLogoutUrl());
    }

    #[Test]
    public function getIdpCertReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getIdpCert());
    }

    #[Test]
    public function setIdpCertSetsIdpCert(): void
    {
        $cert = '-----BEGIN CERTIFICATE-----\nMIIC...\n-----END CERTIFICATE-----';
        $this->subject->setIdpCert($cert);
        self::assertSame($cert, $this->subject->getIdpCert());
    }

    #[Test]
    public function getUsernamePrefixReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getUsernamePrefix());
    }

    #[Test]
    public function setUsernamePrefixSetsUsernamePrefix(): void
    {
        $this->subject->setUsernamePrefix('saml_');
        self::assertSame('saml_', $this->subject->getUsernamePrefix());
    }

    #[Test]
    public function getUsersPidReturnsInitiallyZero(): void
    {
        self::assertSame(0, $this->subject->getUsersPid());
    }

    #[Test]
    public function setUsersPidSetsUsersPid(): void
    {
        $this->subject->setUsersPid(123);
        self::assertSame(123, $this->subject->getUsersPid());
    }

    #[Test]
    public function getUsergroupReturnsInitiallyEmptyString(): void
    {
        self::assertSame('', $this->subject->getUsergroup());
    }

    #[Test]
    public function setUsergroupSetsUsergroup(): void
    {
        $this->subject->setUsergroup('1,2,3');
        self::assertSame('1,2,3', $this->subject->getUsergroup());
    }

    #[Test]
    public function fluentInterfaceWorks(): void
    {
        $result = $this->subject
            ->setName('Test')
            ->setSpEntityId('https://sp.example.com')
            ->setIdpEntityId('https://idp.example.com')
            ->setUsersPid(42);

        self::assertSame($this->subject, $result);
        self::assertSame('Test', $this->subject->getName());
        self::assertSame('https://sp.example.com', $this->subject->getSpEntityId());
        self::assertSame('https://idp.example.com', $this->subject->getIdpEntityId());
        self::assertSame(42, $this->subject->getUsersPid());
    }
}
