<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\EventListener;

use Netresearch\NrSamlAuth\Service\SamlService;
use Netresearch\NrSamlAuth\Session\SamlSession;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Authentication\Event\AfterUserLoggedInEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Listens to AfterUserLoggedInEvent to store SAML session data
 * needed for single logout (SLO).
 */
#[AsEventListener(
    identifier: 'nr-saml-auth/after-user-logged-in',
    event: AfterUserLoggedInEvent::class
)]
final class AfterUserLoggedInEventListener
{
    public function __construct(
        private readonly SamlService $samlService,
        private readonly SamlSession $samlSession,
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger,
    ) {}

    public function __invoke(AfterUserLoggedInEvent $event): void
    {
        $request = $this->getRequest();
        if ($request === null) {
            return;
        }

        $parsedBody = $request->getParsedBody();
        $samlResponse = $parsedBody['SAMLResponse'] ?? null;

        if (empty($samlResponse)) {
            return;
        }

        try {
            $samlId = $this->getSamlId($request);
            $this->samlService->setSettingsUid($samlId);

            $response = $this->samlService->getResponse($samlResponse);

            $sessionData = [
                'id' => $samlId,
                'AssertionId' => $response->getAssertionId(),
                'nameId' => $response->getNameId(),
            ];

            $this->samlSession->setUser($event->getUser());
            $this->samlSession->setSessionData($sessionData);
        } catch (\Exception $exception) {
            $this->logger->error('Failed to store SAML session data', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function getRequest(): ?ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'] ?? null;
    }

    private function getSamlId(ServerRequestInterface $request): int
    {
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['saml_id'])) {
            return (int)$queryParams['saml_id'];
        }

        $uri = $request->getUri();
        $host = $uri->getScheme() . '://' . $uri->getHost() . '/';

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(
            'tx_nrsamlauth_domain_model_settings'
        );
        $result = $queryBuilder
            ->select('uid')
            ->from('tx_nrsamlauth_domain_model_settings')
            ->where(
                $queryBuilder->expr()->eq(
                    'sp_entity_id',
                    $queryBuilder->createNamedParameter($host)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        return (int)($result['uid'] ?? 1);
    }
}
