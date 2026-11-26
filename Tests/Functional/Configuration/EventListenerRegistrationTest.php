<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Configuration;

use Netresearch\NrSamlAuth\EventListener\AfterUserLoggedOutEventListener;
use Netresearch\NrSamlAuth\EventListener\BeforeUserLogoutEventListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedOutEvent;
use TYPO3\CMS\Core\Authentication\Event\BeforeUserLogoutEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The event listeners are registered through their #[AsEventListener]
 * attribute, under their identifier and for their event.
 */
final class EventListenerRegistrationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['netresearch/nr-saml-auth'];

    /**
     * @return array<string, array{class-string, string, class-string}>
     */
    public static function listenerDataProvider(): array
    {
        return [
            'before logout' => [BeforeUserLogoutEvent::class, 'nr-saml-auth/before-user-logout', BeforeUserLogoutEventListener::class],
            'after logout'  => [AfterUserLoggedOutEvent::class, 'nr-saml-auth/after-user-logged-out', AfterUserLoggedOutEventListener::class],
        ];
    }

    /**
     * @param class-string $event
     * @param class-string $listener
     */
    #[Test]
    #[DataProvider('listenerDataProvider')]
    public function theListenerIsRegisteredForItsEvent(string $event, string $identifier, string $listener): void
    {
        $listeners = $this->get(ListenerProvider::class)->getAllListenerDefinitions();

        self::assertSame($listener, $listeners[$event][$identifier]['service'] ?? null);
    }
}
