<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Domain\Repository;

use Netresearch\NrSamlAuth\Domain\Model\Settings;
use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SettingsRepositoryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/nr-saml-auth',
    ];

    private SettingsRepository $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importTestData();
        $this->subject = $this->get(SettingsRepository::class);
    }

    private function importTestData(): void
    {
        $connection = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tx_nrsamlauth_domain_model_settings');

        $connection->insert('tx_nrsamlauth_domain_model_settings', [
            'uid' => 1,
            'pid' => 1,
            'name' => 'Test Settings 1',
            'sp_entity_id' => 'https://example.com/',
            'sp_customer_service_url' => 'https://example.com/acs',
            'sp_customer_service_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'sp_name_id_format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:transient',
            'sp_cert' => 'test-cert-1',
            'sp_key' => 'test-key-1',
            'idp_entity_id' => 'urn:idp:example',
            'idp_sso_url' => 'https://idp.example.com/sso',
            'idp_sso_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'idp_logout_url' => 'https://idp.example.com/logout',
            'idp_cert' => 'idp-cert-1',
            'username_prefix' => 'sso-',
            'users_pid' => 10,
            'usergroup' => '1,2',
        ]);

        $connection->insert('tx_nrsamlauth_domain_model_settings', [
            'uid' => 2,
            'pid' => 1,
            'name' => 'Test Settings 2',
            'sp_entity_id' => 'https://other.example.org/',
            'sp_customer_service_url' => 'https://other.example.org/acs',
            'sp_customer_service_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            'sp_name_id_format' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:emailAddress',
            'sp_cert' => 'test-cert-2',
            'sp_key' => 'test-key-2',
            'idp_entity_id' => 'urn:idp:other',
            'idp_sso_url' => 'https://idp.other.org/sso',
            'idp_sso_binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            'idp_logout_url' => 'https://idp.other.org/logout',
            'idp_cert' => 'idp-cert-2',
            'username_prefix' => 'auth-',
            'users_pid' => 20,
            'usergroup' => '3',
        ]);
    }

    #[Test]
    public function findAllReturnsAllRecords(): void
    {
        $result = $this->subject->findAll();

        self::assertCount(2, $result);
    }

    #[Test]
    public function findAllReturnsSettingsInstances(): void
    {
        $result = $this->subject->findAll();

        foreach ($result as $settings) {
            self::assertInstanceOf(Settings::class, $settings);
        }
    }

    #[Test]
    public function findByUidReturnsCorrectRecord(): void
    {
        $result = $this->subject->findByUid(1);

        self::assertInstanceOf(Settings::class, $result);
        self::assertSame('Test Settings 1', $result->getName());
        self::assertSame('https://example.com/', $result->getSpEntityId());
        self::assertSame('sso-', $result->getUsernamePrefix());
        self::assertSame(10, $result->getUsersPid());
    }

    #[Test]
    public function findByUidReturnsSecondRecord(): void
    {
        $result = $this->subject->findByUid(2);

        self::assertInstanceOf(Settings::class, $result);
        self::assertSame('Test Settings 2', $result->getName());
        self::assertSame('https://other.example.org/', $result->getSpEntityId());
        self::assertSame('auth-', $result->getUsernamePrefix());
        self::assertSame(20, $result->getUsersPid());
    }

    #[Test]
    public function findByUidReturnsNullForNonExistingUid(): void
    {
        $result = $this->subject->findByUid(999);

        self::assertNull($result);
    }

    #[Test]
    public function findEntityIdByHostReturnsMatchingSettings(): void
    {
        $result = $this->subject->findEntityIdByHost('https://example.com/');

        self::assertInstanceOf(Settings::class, $result);
        self::assertSame(1, $result->getUid());
        self::assertSame('Test Settings 1', $result->getName());
    }

    #[Test]
    public function findEntityIdByHostReturnsSecondMatchingSettings(): void
    {
        $result = $this->subject->findEntityIdByHost('https://other.example.org/');

        self::assertInstanceOf(Settings::class, $result);
        self::assertSame(2, $result->getUid());
        self::assertSame('Test Settings 2', $result->getName());
    }

    #[Test]
    public function findEntityIdByHostReturnsNullForUnknownHost(): void
    {
        $result = $this->subject->findEntityIdByHost('https://unknown.example.net/');

        self::assertNull($result);
    }

    #[Test]
    public function findEntityIdByHostReturnsNullForPartialMatch(): void
    {
        $result = $this->subject->findEntityIdByHost('https://example.com');

        self::assertNull($result);
    }

    #[Test]
    public function repositoryIgnoresStoragePage(): void
    {
        $result = $this->subject->findAll();

        self::assertCount(2, $result);
        foreach ($result as $settings) {
            self::assertSame(1, $settings->getPid());
        }
    }
}
