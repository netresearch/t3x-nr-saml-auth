<?php

/**
 * This middleware handles the redirect of the user during login/logout process during saml authentication
 * I relies on that the target for redirecting is passed via RelayState parameter during ACS call from saml server towards
 * TYPO3.
 *
 * Only targets on the host of the current request are followed: a path
 * starting with a single slash, or an absolute http(s) URL whose scheme, host
 * and port equal those of the request. Any other RelayState is ignored and the
 * request is handled as usual.
 */

namespace Netresearch\NrSamlAuth\Middleware;


use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RedirectResponse;


class DeepLinkSsoMiddleware implements MiddlewareInterface
{

    /**
     * Process the redirect after login/logout if necessary.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isResponsible($request)) {
            return  $handler->handle($request);
        }

        $target = $this->getRedirectTarget($request);
        if ($target === '' || !$this->isTargetOnCurrentHost($target, $request)) {
            return $handler->handle($request);
        }

        // This middleware runs after the frontend authentication middleware,
        // which has already sent the session cookie header.
        return new RedirectResponse($target, 303);
    }

    /**
     * Returns true, if we handle a saml login or logout request.
     *
     * @param ServerRequestInterface $request
     * @return bool
     */
    private function isResponsible(ServerRequestInterface $request)
    {
        if ($this->isSamlLoginRequest($request)) {
            return true;
        }

        if ($this->isSamlLogoutRequest($request)) {
            return true;
        }
        return false;
    }

    /**
     * Returns the passed redirect target, from the POST body (login) or the query (logout).
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    private function getRedirectTarget(ServerRequestInterface $request): string
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $relayState = $parsedBody['RelayState'] ?? $queryParams['RelayState'] ?? '';

        return is_string($relayState) ? $relayState : '';
    }

    private function isTargetOnCurrentHost(string $target, ServerRequestInterface $request): bool
    {
        // Control characters and backslashes are interpreted differently by browsers
        if (preg_match('/[\x00-\x1F\x7F\\\\]/', $target) === 1) {
            return false;
        }

        if (strpos($target, '/') === 0) {
            return strpos($target, '//') !== 0;
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

        list($requestScheme, $requestHost, $requestPort) = $this->getRequestOrigin($request);

        return $scheme === $requestScheme
            && $host === $requestHost
            && (int)($parts['port'] ?? $this->getDefaultPort($scheme)) === $requestPort;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array [scheme, host, port]
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

    /**
     * Returns true, if the current request is sent from saml server towards TYPO3 as login request.
     *
     * @param ServerRequestInterface $request the request
     * @return bool
     */
    private function isSamlLoginRequest(ServerRequestInterface $request)
    {
        $parsedBody = $request->getParsedBody();

        return $request->getMethod() == 'POST' && isset($parsedBody['RelayState']);
    }

    /**
     * Returns true, if the current request is sent from saml server towards TYPO3 as logout request.
     *
     * @param ServerRequestInterface $request the request
     * @return bool
     */
    private function isSamlLogoutRequest(ServerRequestInterface $request)
    {
        $queryParams = $request->getQueryParams();

        return ($queryParams['logintype'] ?? '') === 'logout'
            && isset($queryParams['RelayState'])
            && isset($queryParams['SAMLResponse']);
    }
}
