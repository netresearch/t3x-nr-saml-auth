<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Unit\Middleware;

use Netresearch\NrSamlAuth\Middleware\SingleLogoutSessionMiddleware;
use Netresearch\NrSamlAuth\Service\SamlService;
use Netresearch\NrSamlAuth\Session\SamlSession;
use Netresearch\NrSamlAuth\Sv\AuthenticationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use RuntimeException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use WeakMap;

/**
 * The cases in which the middleware looks for the settings record, which is
 * the first step of storing the single logout data, and the cases in which
 * it passes the request on untouched. Storing the data is covered by
 * SamlLoginTest, which sends a SAML response through the frontend.
 */
final class SingleLogoutSessionMiddlewareTest extends UnitTestCase
{
    private const SAML_RESPONSE = ['SAMLResponse' => 'response'];

    protected function tearDown(): void
    {
        // AuthenticationService keeps the resolved users in a static map
        (new ReflectionProperty(AuthenticationService::class, 'userResolvedForRequest'))->setValue(null, null);

        parent::tearDown();
    }

    /**
     * @return array<string, array{0: array<string, mixed>|null, 1: array<string, mixed>|null, 2: int|null}>
     */
    public static function requestsThatStoreNothingDataProvider(): array
    {
        return [
            'no SAML response' => [[], ['uid' => 5], 5],
            'empty SAML response' => [['SAMLResponse' => ''], ['uid' => 5], 5],
            'no parsed body' => [null, ['uid' => 5], 5],
            'no user resolved from the response' => [self::SAML_RESPONSE, ['uid' => 5], null],
            'another user than the one resolved' => [self::SAML_RESPONSE, ['uid' => 5], 6],
            'no frontend user logged in' => [self::SAML_RESPONSE, [], 5],
            'no frontend user' => [self::SAML_RESPONSE, null, 5],
        ];
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, mixed>|null $user
     */
    #[Test]
    #[DataProvider('requestsThatStoreNothingDataProvider')]
    public function requestIsPassedOnWithoutLookingForTheSettingsRecord(?array $body, ?array $user, ?int $resolvedUid): void
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects($this->never())->method('getQueryBuilderForTable');
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');
        $samlSession = $this->createMock(SamlSession::class);
        $samlSession->expects($this->never())->method('setSessionData');

        $request = $this->createRequest($body, [], $user, $resolvedUid);
        $handler = $this->createHandler($request);

        $response = $this->createSubject($connectionPool, $samlSession, $container)->process($request, $handler);

        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    #[Test]
    public function userResolvedFromTheResponseLooksForTheSettingsRecord(): void
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects($this->once())
            ->method('getQueryBuilderForTable')
            ->with('tx_nrsamlauth_domain_model_settings')
            ->willThrowException(new RuntimeException('no database in a unit test'));
        $samlSession = $this->createMock(SamlSession::class);
        $samlSession->expects($this->never())->method('setSessionData');

        $request = $this->createRequest(self::SAML_RESPONSE, [], ['uid' => 5], 5);
        $handler = $this->createHandler($request);

        $response = $this->createSubject($connectionPool, $samlSession, null, true)->process($request, $handler);

        self::assertInstanceOf(ResponseInterface::class, $response, 'A failure to store the data does not fail the request');
    }

    #[Test]
    public function settingsRecordNamedInTheQueryIsUsedWithoutLookup(): void
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects($this->never())->method('getQueryBuilderForTable');
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(SamlService::class)
            ->willThrowException(new RuntimeException('no SamlService in a unit test'));
        $samlSession = $this->createMock(SamlSession::class);
        $samlSession->expects($this->never())->method('setSessionData');

        $request = $this->createRequest(self::SAML_RESPONSE, ['saml_id' => '2'], ['uid' => 5], 5);
        $handler = $this->createHandler($request);

        $response = $this->createSubject($connectionPool, $samlSession, $container, true)->process($request, $handler);

        self::assertInstanceOf(ResponseInterface::class, $response);
    }

    private function createSubject(
        ConnectionPool $connectionPool,
        SamlSession $samlSession,
        ?ContainerInterface $container = null,
        bool $expectFailureToBeLogged = false,
    ): SingleLogoutSessionMiddleware {
        $logger = $this->createMock(LoggerInterface::class);
        if ($expectFailureToBeLogged) {
            $logger->expects($this->once())->method('error');
        }

        return new SingleLogoutSessionMiddleware(
            $samlSession,
            $connectionPool,
            $container ?? $this->createMock(ContainerInterface::class),
            $logger,
        );
    }

    /**
     * @param array<string, mixed>|null $body
     * @param array<string, mixed> $query
     * @param array<string, mixed>|null $user
     */
    private function createRequest(?array $body, array $query, ?array $user, ?int $resolvedUid): ServerRequestInterface
    {
        $request = (new ServerRequest('https://sp.example.com/', 'POST'))
            ->withParsedBody($body)
            ->withQueryParams($query);

        if ($user !== null) {
            $frontendUser = new FrontendUserAuthentication();
            $frontendUser->user = $user;
            $request = $request->withAttribute('frontend.user', $frontendUser);
        }

        if ($resolvedUid !== null) {
            // What AuthenticationService::getUser() records for the request
            $property = new ReflectionProperty(AuthenticationService::class, 'userResolvedForRequest');
            /** @var WeakMap<object, int> $resolved */
            $resolved = new WeakMap();
            $resolved[$request] = $resolvedUid;
            $property->setValue(null, $resolved);
        }

        return $request;
    }

    private function createHandler(ServerRequestInterface $expectedRequest): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($expectedRequest)
            ->willReturn($this->createMock(ResponseInterface::class));

        return $handler;
    }
}
