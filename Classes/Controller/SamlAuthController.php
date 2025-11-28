<?php

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Controller;

use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use Netresearch\NrSamlAuth\Service\SamlService;
use OneLogin\Saml2\Error;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Backend controller for SAML configuration and metadata display.
 */
class SamlAuthController extends ActionController
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository,
        private readonly SamlService $samlService,
    ) {}

    /**
     * Displays SAML metadata for configured service providers.
     *
     * @throws Error
     */
    public function metadataAction(): ResponseInterface
    {
        $settings = $this->settingsRepository->findAll();
        $this->view->assign('samlSettings', $settings);

        $samlUid = $this->getArgumentSamlSettings();
        if ($samlUid !== null) {
            $this->samlService->setSettingsUid($samlUid);
            $metadataXml = $this->samlService->getMetadata();

            $this->view->assign(
                'SamlMetaData',
                htmlentities($metadataXml, ENT_QUOTES, 'UTF-8', false)
            );
        }

        return $this->htmlResponse();
    }

    private function getArgumentSamlSettings(): ?int
    {
        if ($this->request->hasArgument('samlUid')) {
            return (int)$this->request->getArgument('samlUid');
        }

        return null;
    }
}
