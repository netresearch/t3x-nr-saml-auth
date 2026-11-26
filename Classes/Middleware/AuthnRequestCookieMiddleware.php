<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Middleware;

use Netresearch\NrSamlAuth\Service\AuthnRequestCookie;
use Netresearch\NrSamlAuth\Sv\AuthenticationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Removes the AuthnRequest that a SAML response answered from the
 * AuthnRequestCookie after the login with that response, so that the
 * request is answered once. Logins the browser started in other tabs stay.
 *
 * Registered right after the frontend authentication middleware, so that
 * it also adds the cookie to the redirect of DeepLinkSsoMiddleware.
 */
final readonly class AuthnRequestCookieMiddleware implements MiddlewareInterface
{
    public function __construct(private AuthnRequestCookie $authnRequestCookie) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $answered = AuthenticationService::getAuthnRequestAnsweredInRequest($request);
        if ($answered === null) {
            return $response;
        }

        $cookie = $this->authnRequestCookie->withoutRequest($request, $answered[0], $answered[1]);

        return $cookie instanceof Cookie ? $response->withAddedHeader('Set-Cookie', (string) $cookie) : $response;
    }
}
