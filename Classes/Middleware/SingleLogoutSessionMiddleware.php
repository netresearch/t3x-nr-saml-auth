<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Middleware;

use Netresearch\NrSamlAuth\Service\SamlService;
use Netresearch\NrSamlAuth\Session\SamlSession;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Stores the data that single logout (SLO) needs in the frontend session
 * after a login with a SAML response.
 *
 * The data are the uid of the settings record, the ID of the assertion and
 * the NameID. AfterUserLoggedOutEventListener sends them to the identity
 * provider when the user logs out.
 *
 * Only a response that validates is used: AuthenticationService has accepted
 * the response of a login request that logged a user in, except on TYPO3 12.4
 * where a user who was logged in before stays logged in when the response is
 * rejected.
 *
 * This is a middleware and not a listener for AfterUserLoggedInEvent: TYPO3
 * 12.4 dispatches that event for backend logins only, and TYPO3 13.4 and
 * later dispatch it inside the frontend authentication middleware, before
 * $GLOBALS['TYPO3_REQUEST'] is set. The middleware runs right after the
 * frontend authentication middleware on every supported version; the
 * authentication middleware writes the session data after the response is
 * built.
 */
final class SingleLogoutSessionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SamlSession $samlSession,
        private readonly ConnectionPool $connectionPool,
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $frontendUser = $request->getAttribute('frontend.user');
        $samlResponse = $this->getSamlResponse($request);

        if ($samlResponse !== '' && $this->isLoginRequest($request)
            && $frontendUser instanceof FrontendUserAuthentication
            && (int)($frontendUser->user['uid'] ?? 0) > 0
        ) {
            $this->storeSingleLogoutData($request, $frontendUser, $samlResponse);
        }

        return $handler->handle($request);
    }

    private function storeSingleLogoutData(
        ServerRequestInterface $request,
        FrontendUserAuthentication $frontendUser,
        string $samlResponse,
    ): void {
        try {
            $samlId = $this->getSamlId($request);
            // Fetched here and not injected: SamlService needs the Extbase
            // repository, which cannot be built for every frontend request
            // (cached pages have no TypoScript setup), and this middleware
            // is built for every frontend request.
            /** @var SamlService $samlService */
            $samlService = $this->container->get(SamlService::class);
            $samlService->setSettingsUid($samlId);

            // The response is validated again: on TYPO3 12.4 a user who is
            // already logged in stays logged in when the response of a
            // login request is rejected, and a response that is not valid
            // must not decide where the logout goes.
            $response = $samlService->getResponse($samlResponse, $request);
            if (!$response->isValid()) {
                return;
            }

            $this->samlSession->setUser($frontendUser);
            $this->samlSession->setSessionData([
                'id' => $samlId,
                'AssertionId' => $response->getAssertionId(),
                'nameId' => $response->getNameId(),
            ]);
        } catch (Throwable $exception) {
            $this->logger->error('Failed to store SAML session data', [
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    private function getSamlResponse(ServerRequestInterface $request): string
    {
        $parsedBody = $request->getParsedBody();
        $samlResponse = is_array($parsedBody) ? ($parsedBody['SAMLResponse'] ?? '') : '';

        return is_string($samlResponse) ? $samlResponse : '';
    }

    /**
     * The authentication service handles a SAML response only for a login
     * request.
     */
    private function isLoginRequest(ServerRequestInterface $request): bool
    {
        $parsedBody = $request->getParsedBody();
        $loginType = is_array($parsedBody) ? ($parsedBody['logintype'] ?? null) : null;

        return ($loginType ?? $request->getQueryParams()['logintype'] ?? '') === 'login';
    }

    /**
     * The settings record the same way AuthenticationService picks it: by the
     * saml_id query parameter, else by an SP entity ID equal to the scheme and
     * host of the request, else uid 1.
     *
     * The Extbase repository is not used: its configuration is not available
     * before the page is resolved.
     */
    private function getSamlId(ServerRequestInterface $request): int
    {
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['saml_id'])) {
            return (int)$queryParams['saml_id'];
        }

        $uri = $request->getUri();
        $table = 'tx_nrsamlauth_domain_model_settings';
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($table);
        $uid = $queryBuilder
            ->select('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'sp_entity_id',
                    $queryBuilder->createNamedParameter($uri->getScheme() . '://' . $uri->getHost() . '/')
                )
            )
            ->executeQuery()
            ->fetchOne();

        return is_numeric($uid) ? (int)$uid : 1;
    }
}
