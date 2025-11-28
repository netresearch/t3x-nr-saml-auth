<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Controller;

use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use Netresearch\NrSamlAuth\Service\SamlService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Authentication\LoginType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * Frontend controller for SAML authentication plugin.
 */
class AuthController extends ActionController
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository,
        private readonly SamlService $samlService,
        private readonly Context $context,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Login action - initiates SAML SSO flow or displays logged-in state
     *
     * @throws \OneLogin\Saml2\Error
     */
    public function loginAction(): ResponseInterface
    {
        $this->samlService->setSettingsUid($this->getSamlSettingsUid());

        if ($this->isLogoutRequest()) {
            return $this->htmlResponse();
        }

        if ($this->isLoggedIn()) {
            $this->view->assign('isLoggedIn', 'true');
            $this->view->assign('feUser', $this->getFrontendUser());
        } else {
            $this->view->assign('isLoggedIn', 'false');
            $this->samlService->redirectUserToSSO();
        }

        return $this->htmlResponse();
    }

    /**
     * Receives SAML response callback
     */
    public function receiveSamlResponseAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    private function isLoggedIn(): bool
    {
        try {
            return (bool)$this->context->getPropertyFromAspect('frontend.user', 'isLoggedIn');
        } catch (\Exception $exception) {
            $this->logger->error('Error checking login state', ['exception' => $exception->getMessage()]);
            return false;
        }
    }

    private function isLogoutRequest(): bool
    {
        $request = $this->request;
        $queryParams = $request->getQueryParams();

        return ($queryParams['logintype'] ?? '') === LoginType::LOGOUT;
    }

    /**
     * Returns the current frontend user data from the request.
     *
     * @return array<string, mixed>|null
     */
    private function getFrontendUser(): ?array
    {
        $frontendUser = $this->request->getAttribute('frontend.user');
        if ($frontendUser instanceof FrontendUserAuthentication) {
            return $frontendUser->user ?? null;
        }

        return null;
    }

    private function getSamlSettingsUid(): int
    {
        if (isset($this->settings['samlAuthSettings'])) {
            return (int)$this->settings['samlAuthSettings'];
        }

        return 1;
    }
}
