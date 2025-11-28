<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\EventListener;

use Netresearch\NrSamlAuth\Session\SamlSession;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\Event\BeforeUserLogoutEvent;

/**
 * Listens to BeforeUserLogoutEvent to preserve SAML session data
 * needed for the logout process.
 */
#[AsEventListener(
    identifier: 'nr-saml-auth/before-user-logout',
    event: BeforeUserLogoutEvent::class
)]
final class BeforeUserLogoutEventListener
{
    /**
     * Static storage for session data between pre and post logout events
     */
    private static array $sessionData = [];

    public function __construct(
        private readonly SamlSession $samlSession,
    ) {}

    public function __invoke(BeforeUserLogoutEvent $event): void
    {
        $user = $event->getUser();
        $this->samlSession->setUser($user);
        self::$sessionData = $this->samlSession->getSessionData();
    }

    public static function getSessionData(): array
    {
        return self::$sessionData;
    }
}
