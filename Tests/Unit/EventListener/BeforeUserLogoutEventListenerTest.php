<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Unit\EventListener;

use Netresearch\NrSamlAuth\EventListener\BeforeUserLogoutEventListener;
use Netresearch\NrSamlAuth\Session\SamlSession;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BeforeUserLogoutEventListenerTest extends UnitTestCase
{
    #[Test]
    public function getSessionDataReturnsEmptyArrayByDefault(): void
    {
        $samlSession = $this->createMock(SamlSession::class);
        $logger = new NullLogger();

        $subject = new BeforeUserLogoutEventListener($samlSession, $logger);

        self::assertSame([], $subject->getSessionData());
    }

    #[Test]
    public function constructorAcceptsDependencies(): void
    {
        $samlSession = $this->createMock(SamlSession::class);
        $logger = new NullLogger();

        $subject = new BeforeUserLogoutEventListener($samlSession, $logger);

        self::assertInstanceOf(BeforeUserLogoutEventListener::class, $subject);
    }
}
