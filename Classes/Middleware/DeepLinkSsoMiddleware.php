<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Middleware;

use OneLogin\Saml2\Error;
use OneLogin\Saml2\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware for handling SAML RelayState redirects during login/logout.
 *
 * This middleware handles the redirect of the user during login/logout process
 * during SAML authentication. It relies on the target for redirecting being
 * passed via RelayState parameter during ACS call from SAML server towards TYPO3.
 */
final class DeepLinkSsoMiddleware implements MiddlewareInterface
{
    /**
     * Process the redirect after login/logout if necessary.
     *
     * @throws Error
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isResponsible($request)) {
            return $handler->handle($request);
        }

        $this->handleSamlRedirectIfRequired($request);

        return $handler->handle($request);
    }

    private function isResponsible(ServerRequestInterface $request): bool
    {
        return $this->isSamlLoginRequest($request) || $this->isSamlLogoutRequest($request);
    }

    private function getRedirectTarget(ServerRequestInterface $request): ?string
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Check POST body first (login), then query params (logout)
        $relayState = $parsedBody['RelayState'] ?? $queryParams['RelayState'] ?? null;

        return is_string($relayState) ? $relayState : null;
    }

    /**
     * @throws Error
     */
    private function handleSamlRedirectIfRequired(ServerRequestInterface $request): void
    {
        $target = $this->getRedirectTarget($request);

        if ($target === null || $target === '') {
            return;
        }

        Utils::redirect($target);
    }

    /**
     * Check if request is from SAML server towards TYPO3 as login request.
     */
    private function isSamlLoginRequest(ServerRequestInterface $request): bool
    {
        if ($request->getMethod() !== 'POST') {
            return false;
        }

        $parsedBody = $request->getParsedBody();
        return isset($parsedBody['RelayState']);
    }

    /**
     * Check if request is from SAML server towards TYPO3 as logout request.
     */
    private function isSamlLogoutRequest(ServerRequestInterface $request): bool
    {
        $queryParams = $request->getQueryParams();

        return ($queryParams['logintype'] ?? '') === 'logout'
            && isset($queryParams['RelayState'])
            && isset($queryParams['SAMLResponse']);
    }
}
