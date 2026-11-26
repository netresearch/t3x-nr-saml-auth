<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Service;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Cookie;
use TYPO3\CMS\Core\Http\NormalizedParams;

/**
 * Remembers in the browser which AuthnRequests it sent to the identity
 * provider, so that the Assertion Consumer Service accepts only responses
 * to those requests (no login CSRF with a response of another browser).
 *
 * The identity provider posts the response cross-site, so a cookie with
 * SameSite=Lax, which is how TYPO3 sets the frontend session cookie, is not
 * sent with it. Over HTTPS this cookie is SameSite=None and Secure. Over plain
 * HTTP a browser rejects SameSite=None without Secure, so the cookie is
 * SameSite=Lax there and comes back only from an identity provider on the
 * same site; for any other, SP-initiated login fails closed.
 *
 * The value holds up to MAX_PENDING entries of settings uid, request ID and
 * expiry time, one per login started in this browser within LIFETIME
 * seconds (for example in several tabs), and an HMAC over them with TYPO3's
 * encryption key. TYPO3\CMS\Core\Crypto\HashService::hmac() computes
 * SHA-1 only on TYPO3 13.4 (the algorithm argument exists from 14.0 on),
 * so the HMAC is computed the way it computes it, with SHA-256.
 */
final readonly class AuthnRequestCookie
{
    public const NAME = 'nr_saml_auth_request';

    /**
     * Seconds a started login stays valid.
     */
    public const LIFETIME = 600;

    /**
     * Logins started at the same time in one browser; older ones are dropped.
     */
    public const MAX_PENDING = 5;

    private const HMAC_SECRET = 'nr_saml_auth_authn_request';

    /**
     * @param int|null $now Current time, null for time(); tests set it
     */
    public function __construct(private ?int $now = null) {}

    /**
     * The cookie for the browser of $request after it starts a login with
     * the AuthnRequest $requestId for the settings record $settingsUid.
     * Logins the browser started before and that have not expired are kept.
     */
    public function withRequest(ServerRequestInterface $request, int $settingsUid, string $requestId): Cookie
    {
        $entries   = $this->readEntries($request);
        $entries[] = [$settingsUid, $requestId, $this->now() + self::LIFETIME];

        return $this->createCookie($request, array_slice($entries, -self::MAX_PENDING));
    }

    /**
     * The cookie for the browser of $request after the login with the
     * AuthnRequest $requestId finished; null if the cookie holds no such
     * request. A cookie without entries is deleted.
     */
    public function withoutRequest(ServerRequestInterface $request, int $settingsUid, string $requestId): ?Cookie
    {
        $entries   = $this->readEntries($request);
        $remaining = array_values(array_filter(
            $entries,
            static fn (array $entry): bool => $entry[0] !== $settingsUid || $entry[1] !== $requestId,
        ));
        if (count($remaining) === count($entries)) {
            return null;
        }

        return $this->createCookie($request, $remaining);
    }

    /**
     * IDs of the AuthnRequests for the settings record that the browser of
     * $request started and that have not expired; empty if the cookie is
     * missing or its HMAC does not match.
     *
     * @return list<string>
     */
    public function getPendingRequestIds(ServerRequestInterface $request, int $settingsUid): array
    {
        $ids = [];
        foreach ($this->readEntries($request) as [$uid, $requestId]) {
            if ($uid === $settingsUid) {
                $ids[] = $requestId;
            }
        }

        return $ids;
    }

    /**
     * Entries of the cookie that are signed and have not expired.
     *
     * @return list<array{0: int, 1: string, 2: int}>
     */
    private function readEntries(ServerRequestInterface $request): array
    {
        $value = $request->getCookieParams()[self::NAME] ?? null;
        if (!is_string($value) || substr_count($value, '.') !== 1) {
            return [];
        }

        [$payload, $hmac] = explode('.', $value);
        $expectedHmac     = $this->hmac($payload);
        if ($expectedHmac === null || !hash_equals($expectedHmac, $hmac)) {
            return [];
        }

        $decoded = base64_decode(strtr($payload, '-_', '+/'), true);
        $entries = is_string($decoded) ? json_decode($decoded, true) : null;
        if (!is_array($entries)) {
            return [];
        }

        $valid = [];
        foreach ($entries as $entry) {
            if (is_array($entry)
                && is_int($entry[0] ?? null)
                && is_string($entry[1] ?? null)
                && is_int($entry[2] ?? null)
                && $entry[2] > $this->now()
            ) {
                $valid[] = [$entry[0], $entry[1], $entry[2]];
            }
        }

        return $valid;
    }

    /**
     * @param list<array{0: int, 1: string, 2: int}> $entries
     */
    private function createCookie(ServerRequestInterface $request, array $entries): Cookie
    {
        $value   = '';
        $expires = 1;
        if ($entries !== []) {
            $payload = rtrim(strtr(base64_encode((string) json_encode($entries)), '+/', '-_'), '=');
            $hmac    = $this->hmac($payload);
            if ($hmac !== null) {
                $value   = $payload . '.' . $hmac;
                $expires = $this->now() + self::LIFETIME;
            }
        }

        $normalizedParams = $request->getAttribute('normalizedParams');
        $isHttps          = $normalizedParams instanceof NormalizedParams && $normalizedParams->isHttps();

        return Cookie::create(
            self::NAME,
            $value,
            $expires,
            '/',
            null,
            $isHttps,
            true,
            false,
            $isHttps ? Cookie::SAMESITE_NONE : Cookie::SAMESITE_LAX,
        );
    }

    /**
     * Null without an encryption key: nothing is signed or accepted then.
     */
    private function hmac(string $payload): ?string
    {
        $confVars      = $GLOBALS['TYPO3_CONF_VARS'] ?? null;
        $system        = is_array($confVars) ? ($confVars['SYS'] ?? null) : null;
        $encryptionKey = is_array($system) ? ($system['encryptionKey'] ?? null) : null;
        if (!is_string($encryptionKey) || $encryptionKey === '') {
            return null;
        }

        return hash_hmac('sha256', $payload, $encryptionKey . self::HMAC_SECRET);
    }

    private function now(): int
    {
        return $this->now ?? time();
    }
}
