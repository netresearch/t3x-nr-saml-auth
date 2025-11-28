<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Unit\Middleware;

use Netresearch\NrSamlAuth\Middleware\DeepLinkSsoMiddleware;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DeepLinkSsoMiddlewareTest extends UnitTestCase
{
    private DeepLinkSsoMiddleware $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new DeepLinkSsoMiddleware();
    }

    #[Test]
    public function processPassesThroughNonSamlRequests(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getParsedBody')->willReturn(null);
        $request->method('getQueryParams')->willReturn([]);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->subject->process($request, $handler);

        self::assertSame($expectedResponse, $result);
    }

    #[Test]
    public function processHandlesPostRequestWithoutRelayState(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getParsedBody')->willReturn(['SAMLResponse' => 'test']);
        $request->method('getQueryParams')->willReturn([]);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->subject->process($request, $handler);

        self::assertSame($expectedResponse, $result);
    }

    #[Test]
    public function processIdentifiesSamlLoginRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getParsedBody')->willReturn(['RelayState' => '']);
        $request->method('getQueryParams')->willReturn([]);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->subject->process($request, $handler);

        self::assertSame($expectedResponse, $result);
    }

    #[Test]
    public function processIdentifiesSamlLogoutRequest(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getParsedBody')->willReturn(null);
        $request->method('getQueryParams')->willReturn([
            'logintype' => 'logout',
            'RelayState' => '',
            'SAMLResponse' => 'test',
        ]);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->subject->process($request, $handler);

        self::assertSame($expectedResponse, $result);
    }

    #[Test]
    public function processIgnoresLogoutWithoutSamlResponse(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('GET');
        $request->method('getParsedBody')->willReturn(null);
        $request->method('getQueryParams')->willReturn([
            'logintype' => 'logout',
            'RelayState' => 'https://example.com',
        ]);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->subject->process($request, $handler);

        self::assertSame($expectedResponse, $result);
    }

    #[Test]
    public function processIgnoresNonStringRelayState(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getMethod')->willReturn('POST');
        $request->method('getParsedBody')->willReturn(['RelayState' => ['invalid' => 'array']]);
        $request->method('getQueryParams')->willReturn([]);

        $expectedResponse = $this->createMock(ResponseInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $result = $this->subject->process($request, $handler);

        self::assertSame($expectedResponse, $result);
    }
}
