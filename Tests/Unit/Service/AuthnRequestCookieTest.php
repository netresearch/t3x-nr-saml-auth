<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Unit\Service;

use Netresearch\NrSamlAuth\Service\AuthnRequestCookie;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AuthnRequestCookieTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'unit-test-encryption-key';
    }

    #[Test]
    public function keepsTheNewestPendingRequests(): void
    {
        $subject = new AuthnRequestCookie();
        $request = $this->request(true);
        for ($i = 1; $i <= AuthnRequestCookie::MAX_PENDING + 2; ++$i) {
            $request = $this->withCookie($request, $subject->withRequest($request, 1, '_request_' . $i));
        }

        self::assertSame(
            ['_request_3', '_request_4', '_request_5', '_request_6', '_request_7'],
            $subject->getPendingRequestIds($request, 1),
        );
    }

    #[Test]
    public function withoutRequestRemovesOnlyThatRequestAndDeletesAnEmptyCookie(): void
    {
        $subject = new AuthnRequestCookie();
        $request = $this->request(true);
        $request = $this->withCookie($request, $subject->withRequest($request, 1, '_first'));
        $request = $this->withCookie($request, $subject->withRequest($request, 2, '_first'));

        self::assertNull($subject->withoutRequest($request, 1, '_unknown'));

        $request = $this->withCookie($request, $subject->withoutRequest($request, 1, '_first'));
        self::assertSame([], $subject->getPendingRequestIds($request, 1));
        self::assertSame(['_first'], $subject->getPendingRequestIds($request, 2));

        $deleted = $subject->withoutRequest($request, 2, '_first');
        self::assertInstanceOf(Cookie::class, $deleted);
        self::assertTrue($deleted->isCleared());
    }

    #[Test]
    public function withoutEncryptionKeyNothingIsRemembered(): void
    {
        $subject = new AuthnRequestCookie();
        $request = $this->request(true);
        $cookie  = $subject->withRequest($request, 1, '_request');

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '';
        $withoutKey                                         = $subject->withRequest($request, 1, '_request');

        self::assertSame([], $subject->getPendingRequestIds($this->withCookie($request, $cookie), 1));
        self::assertTrue($withoutKey->isCleared());
    }

    #[Test]
    public function overPlainHttpTheCookieIsLaxAndNotSecure(): void
    {
        $cookie = (new AuthnRequestCookie())->withRequest($this->request(false), 1, '_request');

        self::assertSame(AuthnRequestCookie::NAME, $cookie->getName());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        self::assertFalse($cookie->isSecure());
        self::assertTrue($cookie->isHttpOnly());
    }

    /**
     * A browser accepts a cookie named __Host-… only from a secure origin,
     * with Secure, Path=/ and without Domain; this holds for the cookie that
     * remembers a login and for the one that deletes it.
     */
    #[Test]
    public function overHttpsTheCookieHasTheHostPrefixAndItsAttributes(): void
    {
        $subject = new AuthnRequestCookie();
        $request = $this->request(true);
        $cookie  = $subject->withRequest($request, 1, '_request');
        $deleted = $subject->withoutRequest($this->withCookie($request, $cookie), 1, '_request');
        self::assertInstanceOf(Cookie::class, $deleted);
        self::assertTrue($deleted->isCleared());

        foreach ([$cookie, $deleted] as $each) {
            self::assertSame('__Host-nr_saml_auth_request', $each->getName());
            self::assertSame(Cookie::SAMESITE_NONE, $each->getSameSite());
            self::assertTrue($each->isSecure());
            self::assertTrue($each->isHttpOnly());
            self::assertSame('/', $each->getPath());
            self::assertNull($each->getDomain());

            $header = (string) $each;
            self::assertStringStartsWith('__Host-nr_saml_auth_request=', $header);
            self::assertStringContainsString('; path=/;', $header);
            self::assertStringContainsString('; secure', $header);
            self::assertStringNotContainsStringIgnoringCase('domain=', $header);
        }
    }

    /**
     * Over HTTPS a cookie under the name without prefix is not read, also if
     * the site signed it: it may have come from a sibling subdomain or a
     * plain-HTTP response.
     */
    #[Test]
    public function overHttpsACookieWithoutTheHostPrefixIsNotRead(): void
    {
        $subject = new AuthnRequestCookie();
        $request = $this->request(true);
        $value   = (string) $subject->withRequest($request, 1, '_request')->getValue();

        self::assertSame([], $subject->getPendingRequestIds($request->withCookieParams([AuthnRequestCookie::NAME => $value]), 1));
        self::assertSame(['_request'], $subject->getPendingRequestIds($request->withCookieParams([AuthnRequestCookie::NAME_HTTPS => $value]), 1));
    }

    #[Test]
    public function overPlainHttpTheCookieWithoutPrefixIsRead(): void
    {
        $subject = new AuthnRequestCookie();
        $request = $this->request(false);
        $value   = (string) $subject->withRequest($request, 1, '_request')->getValue();

        self::assertSame(['_request'], $subject->getPendingRequestIds($request->withCookieParams([AuthnRequestCookie::NAME => $value]), 1));
        self::assertSame([], $subject->getPendingRequestIds($request->withCookieParams([AuthnRequestCookie::NAME_HTTPS => $value]), 1));
    }

    private function request(bool $https): ServerRequest
    {
        $serverParams = [
            'HTTP_HOST'   => 'sp.example.com',
            'REQUEST_URI' => '/login',
            'SCRIPT_NAME' => '/index.php',
        ] + ($https ? ['HTTPS' => 'on', 'SERVER_PORT' => '443'] : ['SERVER_PORT' => '80']);
        $request = new ServerRequest(($https ? 'https' : 'http') . '://sp.example.com/login', 'GET', null, [], $serverParams);

        return $request->withAttribute(
            'normalizedParams',
            NormalizedParams::createFromServerParams($serverParams, ['reverseProxyIP' => '', 'reverseProxyHeaderMultiValue' => 'none', 'reverseProxySSL' => '', 'reverseProxyPrefix' => '', 'reverseProxyPrefixSSL' => '', 'trustedHostsPattern' => '.*']),
        );
    }

    private function withCookie(ServerRequest $request, ?Cookie $cookie): ServerRequest
    {
        self::assertInstanceOf(Cookie::class, $cookie);

        return $request->withCookieParams([$cookie->getName() => (string) $cookie->getValue()]);
    }
}
