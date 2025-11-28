<?php

namespace Netresearch\NrSamlAuth\Domain\Model;

use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class Settings
 *
 * @category   Authentication
 * @author     Axel Seemann <axel.seemann@netresearch.de>
 * @license    Netresearch License
 * @link       https://www.netresearch.de
 */
class Settings extends AbstractEntity
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $redirectUrl;

    /**
     * @var string
     */
    protected $spEntityId;

    /**
     * @var string
     * @Validate("Url")
     */
    protected $spCustomerServiceUrl;

    /**
     * @var string
     */
    protected $spCustomerServiceBinding;

    /**
     * @var string
     */
    protected $spNameIdFormat;

    /**
     * @var string
     */
    protected $spCert;

    /**
     * @var string
     */
    protected $spKey;

    /**
     * @var string
     */
    protected $idpEntityId;

    /**
     * @var string
     */
    protected $idpSsoUrl;

    /**
     * @var string
     */
    protected $idpSsoBinding;

    /**
     * @var string
     */
    protected $idpLogoutUrl;

    /**
     * @var string
     */
    protected $idpCert;

    /**
     * @var string
     */
    protected $usernamePrefix;

    /**
     * @var int
     */
    protected $usersPid;

    /**
     * @var string
     */
    protected $usergroup;

    /**
     * Returns the property name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the property name.
     *
     * @param string $name The value for name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Returns the property redirectUrl.
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }

    /**
     * Sets the property redirectUrl.
     *
     * @param string $redirectUrl The value for redirectUrl
     *
     * @return $this
     */
    public function setRedirectUrl($redirectUrl)
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * Returns the property spEntityId.
     *
     * @return string
     */
    public function getSpEntityId()
    {
        return $this->spEntityId;
    }

    /**
     * Sets the property spEntityId.
     *
     * @param string $spEntityId The value for spEntityId
     *
     * @return $this
     */
    public function setSpEntityId($spEntityId)
    {
        $this->spEntityId = $spEntityId;
        return $this;
    }

    /**
     * Returns the property spCustomerServiceUrl.
     *
     * @return string
     */
    public function getSpCustomerServiceUrl()
    {
        return $this->spCustomerServiceUrl;
    }

    /**
     * Sets the property spCustomerServiceUrl.
     *
     * @param string $spCustomerServiceUrl The value for spCustomerServiceUrl
     *
     * @return $this
     */
    public function setSpCustomerServiceUrl($spCustomerServiceUrl)
    {
        $this->spCustomerServiceUrl = $spCustomerServiceUrl;
        return $this;
    }

    /**
     * Returns the property spCustomerServiceBinding.
     *
     * @return string
     */
    public function getSpCustomerServiceBinding()
    {
        return $this->spCustomerServiceBinding;
    }

    /**
     * Sets the property spCustomerServiceBinding.
     *
     * @param string $spCustomerServiceBinding The value for spCustomerServiceBinding
     *
     * @return $this
     */
    public function setSpCustomerServiceBinding($spCustomerServiceBinding)
    {
        $this->spCustomerServiceBinding = $spCustomerServiceBinding;
        return $this;
    }

    /**
     * Returns the property spNameIdFormat.
     *
     * @return string
     */
    public function getSpNameIdFormat()
    {
        return $this->spNameIdFormat;
    }

    /**
     * Sets the property spNameIdFormat.
     *
     * @param string $spNameIdFormat The value for spNameIdFormat
     *
     * @return $this
     */
    public function setSpNameIdFormat($spNameIdFormat)
    {
        $this->spNameIdFormat = $spNameIdFormat;
        return $this;
    }

    /**
     * Returns the property spCert.
     *
     * @return string
     */
    public function getSpCert()
    {
        return $this->spCert;
    }

    /**
     * Sets the property spCert.
     *
     * @param string $spCert The value for spCert
     *
     * @return $this
     */
    public function setSpCert($spCert)
    {
        $this->spCert = $spCert;
        return $this;
    }

    /**
     * Returns the property spKey.
     *
     * @return string
     */
    public function getSpKey()
    {
        return $this->spKey;
    }

    /**
     * Sets the property spKey.
     *
     * @param string $spKey The value for spKey
     *
     * @return $this
     */
    public function setSpKey($spKey)
    {
        $this->spKey = $spKey;
        return $this;
    }

    /**
     * Returns the property idpEntityId.
     *
     * @return string
     */
    public function getIdpEntityId()
    {
        return $this->idpEntityId;
    }

    /**
     * Sets the property idpEntityId.
     *
     * @param string $idpEntityId The value for idpEntityId
     *
     * @return $this
     */
    public function setIdpEntityId($idpEntityId)
    {
        $this->idpEntityId = $idpEntityId;
        return $this;
    }

    /**
     * Returns the property idpSsoUrl.
     *
     * @return string
     */
    public function getIdpSsoUrl()
    {
        return $this->idpSsoUrl;
    }

    /**
     * Sets the property idpSsoUrl.
     *
     * @param string $idpSsoUrl The value for idpSsoUrl
     *
     * @return $this
     */
    public function setIdpSsoUrl($idpSsoUrl)
    {
        $this->idpSsoUrl = $idpSsoUrl;
        return $this;
    }

    /**
     * Returns the property idpSsoBinding.
     *
     * @return string
     */
    public function getIdpSsoBinding()
    {
        return $this->idpSsoBinding;
    }

    /**
     * Sets the property idpSsoBinding.
     *
     * @param string $idpSsoBinding The value for idpSsoBinding
     *
     * @return $this
     */
    public function setIdpSsoBinding($idpSsoBinding)
    {
        $this->idpSsoBinding = $idpSsoBinding;
        return $this;
    }

    /**
     * Returns the property idpCert.
     *
     * @return string
     */
    public function getIdpCert()
    {
        return $this->idpCert;
    }

    /**
     * Returns the property idpLogoutUrl.
     *
     * @return string
     */
    public function getIdpLogoutUrl()
    {
        return $this->idpLogoutUrl;
    }

    /**
     * Sets the property idpLogoutUrl.
     *
     * @param string $idpLogoutUrl The value for idpLogoutUrl
     *
     * @return $this
     */
    public function setIdpLogoutUrl($idpLogoutUrl)
    {
        $this->idpLogoutUrl = $idpLogoutUrl;
        return $this;
    }

    /**
     * Sets the property idpCert.
     *
     * @param string $idpCert The value for idpCert
     *
     * @return $this
     */
    public function setIdpCert($idpCert)
    {
        $this->idpCert = $idpCert;
        return $this;
    }

    /**
     * Returns the property usernamePrefix.
     *
     * @return string
     */
    public function getUsernamePrefix()
    {
        return $this->usernamePrefix;
    }

    /**
     * Sets the property usernamePrefix.
     *
     * @param string $usernamePrefix The value for usernamePrefix
     *
     * @return $this
     */
    public function setUsernamePrefix($usernamePrefix)
    {
        $this->usernamePrefix = $usernamePrefix;
        return $this;
    }

    /**
     * Returns the property usersPid.
     *
     * @return int
     */
    public function getUsersPid()
    {
        return $this->usersPid;
    }

    /**
     * Sets the property usersPid.
     *
     * @param int $usersPid The value for usersPid
     *
     * @return $this
     */
    public function setUsersPid($usersPid)
    {
        $this->usersPid = $usersPid;
        return $this;
    }

    /**
     * Returns the property usergroup.
     *
     * @return string
     */
    public function getUsergroup()
    {
        return $this->usergroup;
    }

    /**
     * Sets the property usergroup.
     *
     * @param string $usergroup The value for usergroup
     *
     * @return $this
     */
    public function setUsergroup($usergroup)
    {
        $this->usergroup = $usergroup;
        return $this;
    }
}
