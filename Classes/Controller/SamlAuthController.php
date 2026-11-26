<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Controller;

use Netresearch\NrSamlAuth\Domain\Repository\SettingsRepository;
use Netresearch\NrSamlAuth\Service\SamlService;
use OneLogin\Saml2\Error;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Backend controller for SAML configuration and metadata display.
 */
class SamlAuthController extends ActionController
{
    public function __construct(
        private readonly SettingsRepository $settingsRepository,
        private readonly SamlService $samlService,
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {}

    /**
     * Displays SAML metadata for configured service providers, in the
     * backend's module layout (ModuleTemplate).
     *
     * @throws Error
     */
    public function metadataAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->assign('samlSettings', $this->settingsRepository->findAll());

        $samlUid = $this->getArgumentSamlSettings();
        if ($samlUid !== null) {
            $this->samlService->setSettingsUid($samlUid);
            $metadataXml = $this->samlService->getMetadata();

            $moduleTemplate->assign(
                'SamlMetaData',
                htmlentities($metadataXml, ENT_QUOTES, 'UTF-8', false),
            );
        }

        return $moduleTemplate->renderResponse('SamlAuth/Metadata');
    }

    private function getArgumentSamlSettings(): ?int
    {
        if ($this->request->hasArgument('samlUid')) {
            $uid = $this->request->getArgument('samlUid');

            return is_int($uid) || (is_string($uid) && is_numeric($uid)) ? (int) $uid : 0;
        }

        return null;
    }
}
