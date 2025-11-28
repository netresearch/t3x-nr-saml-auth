<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Unit\Sv;

use Netresearch\NrSamlAuth\Sv\AuthenticationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AuthenticationServiceTest extends UnitTestCase
{
    private AuthenticationService $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new AuthenticationService();
    }

    #[Test]
    public function authUserReturns200(): void
    {
        $user = ['uid' => 1, 'username' => 'testuser'];
        self::assertSame(200, $this->subject->authUser($user));
    }

    #[Test]
    public function authUserReturns200ForEmptyUser(): void
    {
        self::assertSame(200, $this->subject->authUser([]));
    }

    #[Test]
    public function getUsernameReturnsEmptyStringForEmptyArray(): void
    {
        $result = $this->invokePrivateMethod('getUsername', [[]]);
        self::assertSame('', $result);
    }

    #[Test]
    public function getUsernameReturnsEmptyStringForMissingUsernameKey(): void
    {
        $result = $this->invokePrivateMethod('getUsername', [['email' => ['test@example.com']]]);
        self::assertSame('', $result);
    }

    #[Test]
    public function getUsernameReturnsUsernameFromArray(): void
    {
        $result = $this->invokePrivateMethod('getUsername', [['username' => ['johndoe']]]);
        self::assertSame('johndoe', $result);
    }

    #[Test]
    public function getUsernameConcatenatesMultipleValues(): void
    {
        $result = $this->invokePrivateMethod('getUsername', [['username' => ['john', 'doe']]]);
        self::assertSame('johndoe', $result);
    }

    #[Test]
    #[DataProvider('samlAttributeDataProvider')]
    public function getValueFromAttributeHandlesVariousTypes(array $attributes, string $key, string $expected): void
    {
        $result = $this->invokePrivateMethod('getValueFromAttribute', [$attributes, $key]);
        self::assertSame($expected, $result);
    }

    /**
     * @return array<string, array{attributes: array<string, mixed>, key: string, expected: string}>
     */
    public static function samlAttributeDataProvider(): array
    {
        return [
            'string value' => [
                'attributes' => ['name' => 'John Doe'],
                'key' => 'name',
                'expected' => 'John Doe',
            ],
            'array with single value' => [
                'attributes' => ['mail' => ['john@example.com']],
                'key' => 'mail',
                'expected' => 'john@example.com',
            ],
            'array with multiple values returns first' => [
                'attributes' => ['groups' => ['admin', 'user', 'editor']],
                'key' => 'groups',
                'expected' => 'admin',
            ],
            'integer value' => [
                'attributes' => ['count' => 123],
                'key' => 'count',
                'expected' => '123',
            ],
            'float value' => [
                'attributes' => ['score' => 98.5],
                'key' => 'score',
                'expected' => '98.5',
            ],
            'missing key returns empty' => [
                'attributes' => ['exists' => 'value'],
                'key' => 'missing',
                'expected' => '',
            ],
            'empty array returns empty' => [
                'attributes' => ['empty' => []],
                'key' => 'empty',
                'expected' => '',
            ],
        ];
    }

    /**
     * Invoke a private method on the subject
     *
     * @param array<int, mixed> $arguments
     */
    private function invokePrivateMethod(string $methodName, array $arguments = []): mixed
    {
        $reflection = new \ReflectionClass($this->subject);
        $method = $reflection->getMethod($methodName);

        return $method->invokeArgs($this->subject, $arguments);
    }
}
