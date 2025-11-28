<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\EventListener;

use Netresearch\NrSamlAuth\Service\SamlService;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedOutEvent;

/**
 * Listens to AfterUserLoggedOutEvent to perform SAML single logout (SLO).
 */
#[AsEventListener(
    identifier: 'nr-saml-auth/after-user-logged-out',
    event: AfterUserLoggedOutEvent::class
)]
final class AfterUserLoggedOutEventListener
{
    public function __construct(
        private readonly SamlService $samlService,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(AfterUserLoggedOutEvent $event): void
    {
        try {
            $sessionData = BeforeUserLogoutEventListener::getSessionData();

            $samlId = $sessionData['id'] ?? null;
            $assertionId = $sessionData['AssertionId'] ?? null;
            $nameId = $sessionData['nameId'] ?? null;

            if (empty($samlId) || empty($assertionId)) {
                return;
            }

            $this->samlService->setSettingsUid((int)$samlId);
            $this->samlService->redirectUserToLogout($nameId, $assertionId);
        } catch (\Exception $exception) {
            $this->logger->error('SAML logout failed', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
