<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Session;

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Handles SAML session data storage for single logout (SLO) support.
 *
 * @internal This class is not part of the public API
 */
class SamlSession implements SingletonInterface
{
    private const KEY_NAME = 'NrSamlAuth';

    private ?AbstractUserAuthentication $user = null;

    public function setUser(AbstractUserAuthentication $userAuthentication): void
    {
        $this->user = $userAuthentication;
    }

    public function getUser(): ?AbstractUserAuthentication
    {
        return $this->user;
    }

    public function isSessionAvailable(): bool
    {
        return $this->getUser() instanceof FrontendUserAuthentication;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSessionData(): ?array
    {
        if (!$this->isSessionAvailable()) {
            return null;
        }

        $user = $this->getUser();
        if ($user === null) {
            return null;
        }

        $user->fetchUserSession();
        $data = $user->getSessionData(self::KEY_NAME);

        return is_array($data) ? $data : [];
    }

    /**
     * @param array<string, mixed>|null $sessionData
     */
    public function setSessionData(?array $sessionData = null): bool
    {
        if (!$this->isSessionAvailable()) {
            return false;
        }

        $user = $this->getUser();
        if ($user === null) {
            return false;
        }

        $user->fetchUserSession();
        $user->setSessionData(self::KEY_NAME, $sessionData);
        $user->storeSessionData();

        return true;
    }
}
