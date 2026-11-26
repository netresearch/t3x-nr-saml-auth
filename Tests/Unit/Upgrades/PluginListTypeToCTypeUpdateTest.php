<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Unit\Upgrades;

use Netresearch\NrSamlAuth\Upgrades\PluginListTypeToCTypeUpdate;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The wizard's own data. The migration of records is covered by the
 * functional PluginListTypeToCTypeUpdateTest.
 */
final class PluginListTypeToCTypeUpdateTest extends UnitTestCase
{
    private PluginListTypeToCTypeUpdate $subject;

    protected function setUp(): void
    {
        parent::setUp();

        // The constructor validates title, description and mapping
        $this->subject = new PluginListTypeToCTypeUpdate($this->createStub(ConnectionPool::class));
    }

    #[Test]
    public function theTitleNamesTheExtensionAndTheMigration(): void
    {
        self::assertSame(
            'SAML Authentication: migrate the plugin to its own content element type',
            $this->subject->getTitle(),
        );
    }

    #[Test]
    public function theDescriptionNamesThePluginAndTheContentElementType(): void
    {
        $description = $this->subject->getDescription();

        self::assertStringContainsString('list_type nrsamlauth_authentication', $description);
        self::assertStringContainsString('content element type nrsamlauth_authentication', $description);
        self::assertStringContainsString('backend user group permissions', $description);
    }

    #[Test]
    public function thePluginIsMappedToTheContentElementTypeOfTheSameName(): void
    {
        self::assertSame(
            ['nrsamlauth_authentication' => 'nrsamlauth_authentication'],
            (new ReflectionMethod($this->subject, 'getListTypeToCTypeMapping'))->invoke($this->subject),
        );
    }
}
