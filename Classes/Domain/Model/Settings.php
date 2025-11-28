<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Domain\Model;

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * SAML authentication settings model.
 *
 * Represents the configuration for a SAML Service Provider (SP)
 * and Identity Provider (IdP) pair.
 */
class Settings extends AbstractEntity
{
    protected string $name = '';

    protected string $redirectUrl = '';

    protected string $spEntityId = '';

    #[Extbase\Validate(['validator' => 'Url'])]
    protected string $spCustomerServiceUrl = '';

    protected string $spCustomerServiceBinding = '';

    protected string $spNameIdFormat = '';

    protected string $spCert = '';

    protected string $spKey = '';

    protected string $idpEntityId = '';

    protected string $idpSsoUrl = '';

    protected string $idpSsoBinding = '';

    protected string $idpLogoutUrl = '';

    protected string $idpCert = '';

    protected string $usernamePrefix = '';

    protected int $usersPid = 0;

    protected string $usergroup = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    public function setRedirectUrl(string $redirectUrl): self
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    public function getSpEntityId(): string
    {
        return $this->spEntityId;
    }

    public function setSpEntityId(string $spEntityId): self
    {
        $this->spEntityId = $spEntityId;
        return $this;
    }

    public function getSpCustomerServiceUrl(): string
    {
        return $this->spCustomerServiceUrl;
    }

    public function setSpCustomerServiceUrl(string $spCustomerServiceUrl): self
    {
        $this->spCustomerServiceUrl = $spCustomerServiceUrl;
        return $this;
    }

    public function getSpCustomerServiceBinding(): string
    {
        return $this->spCustomerServiceBinding;
    }

    public function setSpCustomerServiceBinding(string $spCustomerServiceBinding): self
    {
        $this->spCustomerServiceBinding = $spCustomerServiceBinding;
        return $this;
    }

    public function getSpNameIdFormat(): string
    {
        return $this->spNameIdFormat;
    }

    public function setSpNameIdFormat(string $spNameIdFormat): self
    {
        $this->spNameIdFormat = $spNameIdFormat;
        return $this;
    }

    public function getSpCert(): string
    {
        return $this->spCert;
    }

    public function setSpCert(string $spCert): self
    {
        $this->spCert = $spCert;
        return $this;
    }

    public function getSpKey(): string
    {
        return $this->spKey;
    }

    public function setSpKey(string $spKey): self
    {
        $this->spKey = $spKey;
        return $this;
    }

    public function getIdpEntityId(): string
    {
        return $this->idpEntityId;
    }

    public function setIdpEntityId(string $idpEntityId): self
    {
        $this->idpEntityId = $idpEntityId;
        return $this;
    }

    public function getIdpSsoUrl(): string
    {
        return $this->idpSsoUrl;
    }

    public function setIdpSsoUrl(string $idpSsoUrl): self
    {
        $this->idpSsoUrl = $idpSsoUrl;
        return $this;
    }

    public function getIdpSsoBinding(): string
    {
        return $this->idpSsoBinding;
    }

    public function setIdpSsoBinding(string $idpSsoBinding): self
    {
        $this->idpSsoBinding = $idpSsoBinding;
        return $this;
    }

    public function getIdpLogoutUrl(): string
    {
        return $this->idpLogoutUrl;
    }

    public function setIdpLogoutUrl(string $idpLogoutUrl): self
    {
        $this->idpLogoutUrl = $idpLogoutUrl;
        return $this;
    }

    public function getIdpCert(): string
    {
        return $this->idpCert;
    }

    public function setIdpCert(string $idpCert): self
    {
        $this->idpCert = $idpCert;
        return $this;
    }

    public function getUsernamePrefix(): string
    {
        return $this->usernamePrefix;
    }

    public function setUsernamePrefix(string $usernamePrefix): self
    {
        $this->usernamePrefix = $usernamePrefix;
        return $this;
    }

    public function getUsersPid(): int
    {
        return $this->usersPid;
    }

    public function setUsersPid(int $usersPid): self
    {
        $this->usersPid = $usersPid;
        return $this;
    }

    public function getUsergroup(): string
    {
        return $this->usergroup;
    }

    public function setUsergroup(string $usergroup): self
    {
        $this->usergroup = $usergroup;
        return $this;
    }
}
