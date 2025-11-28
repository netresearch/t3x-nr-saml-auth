<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Unit\Session;

use Netresearch\NrSamlAuth\Session\SamlSession;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SamlSessionTest extends UnitTestCase
{
    private SamlSession $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SamlSession();
    }

    #[Test]
    public function getUserReturnsNullByDefault(): void
    {
        self::assertNull($this->subject->getUser());
    }

    #[Test]
    public function setUserStoresUser(): void
    {
        $frontendUser = $this->createMock(FrontendUserAuthentication::class);
        $this->subject->setUser($frontendUser);

        self::assertSame($frontendUser, $this->subject->getUser());
    }

    #[Test]
    public function isSessionAvailableReturnsTrueForFrontendUser(): void
    {
        $frontendUser = $this->createMock(FrontendUserAuthentication::class);
        $this->subject->setUser($frontendUser);

        self::assertTrue($this->subject->isSessionAvailable());
    }

    #[Test]
    public function isSessionAvailableReturnsFalseForBackendUser(): void
    {
        $backendUser = $this->createMock(BackendUserAuthentication::class);
        $this->subject->setUser($backendUser);

        self::assertFalse($this->subject->isSessionAvailable());
    }

    #[Test]
    public function isSessionAvailableReturnsFalseWhenNoUser(): void
    {
        self::assertFalse($this->subject->isSessionAvailable());
    }

    #[Test]
    public function getSessionDataReturnsNullWhenNoFrontendUser(): void
    {
        self::assertNull($this->subject->getSessionData());
    }

    #[Test]
    public function setSessionDataReturnsFalseWhenNoFrontendUser(): void
    {
        self::assertFalse($this->subject->setSessionData(['test' => 'data']));
    }

    #[Test]
    public function getSessionDataReturnsSessionDataFromUser(): void
    {
        $expectedData = ['id' => 1, 'AssertionId' => 'abc123', 'nameId' => 'user@example.com'];

        $frontendUser = $this->getMockBuilder(FrontendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->addMethods(['fetchUserSession'])
            ->onlyMethods(['getSessionData'])
            ->getMock();
        $frontendUser->expects(self::once())
            ->method('fetchUserSession');
        $frontendUser->expects(self::once())
            ->method('getSessionData')
            ->with('NrSamlAuth')
            ->willReturn($expectedData);

        $this->subject->setUser($frontendUser);

        self::assertSame($expectedData, $this->subject->getSessionData());
    }

    #[Test]
    public function setSessionDataStoresDataInSession(): void
    {
        $testData = ['id' => 1, 'AssertionId' => 'xyz789', 'nameId' => 'test@example.com'];

        $frontendUser = $this->getMockBuilder(FrontendUserAuthentication::class)
            ->disableOriginalConstructor()
            ->addMethods(['fetchUserSession'])
            ->onlyMethods(['setSessionData', 'storeSessionData'])
            ->getMock();
        $frontendUser->expects(self::once())
            ->method('fetchUserSession');
        $frontendUser->expects(self::once())
            ->method('setSessionData')
            ->with('NrSamlAuth', $testData);
        $frontendUser->expects(self::once())
            ->method('storeSessionData');

        $this->subject->setUser($frontendUser);

        self::assertTrue($this->subject->setSessionData($testData));
    }
}
