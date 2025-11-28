<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Helper;

/**
 * Factory for creating SAML assertions for testing.
 *
 * Provides pre-configured assertion scenarios for various test cases.
 */
final class SamlAssertionFactory
{
    /**
     * Creates a valid assertion with standard user attributes.
     */
    public static function createValidAssertion(array $attributes = []): SamlResponseBuilder
    {
        $defaultAttributes = [
            'email' => 'testuser@example.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'displayName' => 'Test User',
        ];

        return SamlResponseBuilder::validResponse()
            ->withAttributes(array_merge($defaultAttributes, $attributes));
    }

    /**
     * Creates an assertion for a specific user.
     */
    public static function createForUser(
        string $email,
        string $firstName,
        string $lastName,
        array $groups = [],
        array $additionalAttributes = []
    ): SamlResponseBuilder {
        $attributes = array_merge([
            'email' => $email,
            'firstName' => $firstName,
            'lastName' => $lastName,
            'displayName' => $firstName . ' ' . $lastName,
        ], $additionalAttributes);

        if (!empty($groups)) {
            $attributes['groups'] = $groups;
        }

        return (new SamlResponseBuilder())
            ->withNameId($email)
            ->withAttributes($attributes);
    }

    /**
     * Creates an assertion with an expired timestamp.
     */
    public static function createExpiredAssertion(): SamlResponseBuilder
    {
        return SamlResponseBuilder::expiredResponse();
    }

    /**
     * Creates an assertion that is not yet valid.
     */
    public static function createFutureAssertion(): SamlResponseBuilder
    {
        return (new SamlResponseBuilder())->notYetValid();
    }

    /**
     * Creates an assertion with wrong audience restriction.
     */
    public static function createWrongAudienceAssertion(): SamlResponseBuilder
    {
        return SamlResponseBuilder::wrongAudienceResponse();
    }

    /**
     * Creates an assertion with a failed status.
     */
    public static function createFailedAssertion(
        string $statusCode = 'urn:oasis:names:tc:SAML:2.0:status:Responder'
    ): SamlResponseBuilder {
        return (new SamlResponseBuilder())->withFailedStatus($statusCode);
    }

    /**
     * Creates an assertion without any attributes (minimal response).
     */
    public static function createMinimalAssertion(): SamlResponseBuilder
    {
        return SamlResponseBuilder::minimalResponse();
    }

    /**
     * Creates an assertion with TYPO3-specific attribute mapping.
     *
     * Maps common SAML attributes to TYPO3 frontend user fields.
     */
    public static function createTypo3FrontendUserAssertion(
        string $username,
        string $email,
        string $firstName,
        string $lastName,
        array $usergroups = [],
        array $additionalAttributes = []
    ): SamlResponseBuilder {
        $attributes = array_merge([
            'uid' => $username,
            'mail' => $email,
            'givenName' => $firstName,
            'sn' => $lastName,
            'cn' => $firstName . ' ' . $lastName,
        ], $additionalAttributes);

        if (!empty($usergroups)) {
            $attributes['memberOf'] = $usergroups;
        }

        return (new SamlResponseBuilder())
            ->withNameId($username)
            ->withAttributes($attributes);
    }

    /**
     * Creates an assertion with TYPO3 backend user attributes.
     */
    public static function createTypo3BackendUserAssertion(
        string $username,
        string $email,
        string $realName,
        bool $isAdmin = false,
        array $additionalAttributes = []
    ): SamlResponseBuilder {
        $attributes = array_merge([
            'uid' => $username,
            'mail' => $email,
            'displayName' => $realName,
            'admin' => $isAdmin ? 'true' : 'false',
        ], $additionalAttributes);

        return (new SamlResponseBuilder())
            ->withNameId($username)
            ->withAttributes($attributes);
    }

    /**
     * Creates a batch of test assertions for various scenarios.
     *
     * @return array<string, SamlResponseBuilder>
     */
    public static function createTestBatch(): array
    {
        return [
            'valid_standard' => self::createValidAssertion(),
            'valid_minimal' => self::createMinimalAssertion(),
            'expired' => self::createExpiredAssertion(),
            'future' => self::createFutureAssertion(),
            'wrong_audience' => self::createWrongAudienceAssertion(),
            'failed_responder' => self::createFailedAssertion('urn:oasis:names:tc:SAML:2.0:status:Responder'),
            'failed_authn' => self::createFailedAssertion('urn:oasis:names:tc:SAML:2.0:status:AuthnFailed'),
        ];
    }
}
