<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The FlexForm of the plugin as TYPO3 processes it for the backend form.
 */
final class PluginFlexFormTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['netresearch/nr-saml-auth'];

    #[Test]
    public function fieldThatSelectsTheSettingsRecordHasItsLabelAndConfiguration(): void
    {
        // A service from TYPO3 13 on; TYPO3 12.4 does not register it
        $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
        $identifier    = $flexFormTools->getDataStructureIdentifier(
            $GLOBALS['TCA']['tt_content']['columns']['pi_flexform'],
            'tt_content',
            'pi_flexform',
            ['CType' => 'list', 'list_type' => 'nrsamlauth_authentication'],
        );

        $dataStructure = $flexFormTools->parseDataStructureByIdentifier($identifier);
        $field         = $dataStructure['sheets']['sGeneral']['ROOT']['el']['settings.samlAuthSettings'] ?? null;

        self::assertIsArray($field);
        self::assertSame(
            'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_flexform.xlf:tx_nrsamlauth.pi_flexform.label.selectSamlSettings',
            $field['label'] ?? null,
        );
        self::assertSame('select', $field['config']['type'] ?? null);
        self::assertSame('tx_nrsamlauth_domain_model_settings', $field['config']['foreign_table'] ?? null);
        self::assertSame('Standard elements', $dataStructure['sheets']['sGeneral']['ROOT']['sheetTitle'] ?? null);
    }
}
