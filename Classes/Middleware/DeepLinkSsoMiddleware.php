<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RedirectResponse;

/**
 * Middleware for handling SAML RelayState redirects during login/logout.
 *
 * This middleware handles the redirect of the user during login/logout process
 * during SAML authentication. It relies on the target for redirecting being
 * passed via RelayState parameter during ACS call from SAML server towards TYPO3.
 *
 * Only targets on the host of the current request are followed: a path
 * starting with a single slash, or an absolute http(s) URL whose scheme, host
 * and port equal those of the request. Any other RelayState is ignored and
 * the request is handled as usual.
 */
final class DeepLinkSsoMiddleware implements MiddlewareInterface
{
    /**
     * Process the redirect after login/logout if necessary.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isResponsible($request)) {
            return $handler->handle($request);
        }

        $target = $this->getRedirectTarget($request);
        if ($target === null || $target === '' || !$this->isTargetOnCurrentHost($target, $request)) {
            return $handler->handle($request);
        }

        // This middleware runs after the frontend authentication middleware,
        // which adds the session cookie to the response returned here.
        return new RedirectResponse($target, 303);
    }

    private function isTargetOnCurrentHost(string $target, ServerRequestInterface $request): bool
    {
        // Control characters and backslashes are interpreted differently by browsers
        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $target) === 1) {
            return false;
        }

        if (str_starts_with($target, '/')) {
            return !str_starts_with($target, '//');
        }

        $parts = parse_url($target);
        if ($parts === false || isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');
        if (!in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        [$requestScheme, $requestHost, $requestPort] = $this->getRequestOrigin($request);

        return $scheme === $requestScheme
            && $host === $requestHost
            && ($parts['port'] ?? $this->getDefaultPort($scheme)) === $requestPort;
    }

    /**
     * @return array{0: string, 1: string, 2: int}
     */
    private function getRequestOrigin(ServerRequestInterface $request): array
    {
        $normalizedParams = $request->getAttribute('normalizedParams');
        if ($normalizedParams instanceof NormalizedParams) {
            $scheme = $normalizedParams->isHttps() ? 'https' : 'http';
            $port = $normalizedParams->getRequestPort();

            return [
                $scheme,
                strtolower($normalizedParams->getRequestHostOnly()),
                $port > 0 ? $port : $this->getDefaultPort($scheme),
            ];
        }

        $uri = $request->getUri();
        $scheme = strtolower($uri->getScheme());

        return [$scheme, strtolower($uri->getHost()), $uri->getPort() ?? $this->getDefaultPort($scheme)];
    }

    private function getDefaultPort(string $scheme): int
    {
        return $scheme === 'https' ? 443 : 80;
    }

    private function isResponsible(ServerRequestInterface $request): bool
    {
        if ($this->isSamlLoginRequest($request)) {
            return true;
        }

        return $this->isSamlLogoutRequest($request);
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
