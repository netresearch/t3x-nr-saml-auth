<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\NrSamlAuth\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The plugin is a content element type of its own (CType), with its
 * FlexForm, on every supported TYPO3 version.
 */
final class PluginRegistrationTest extends FunctionalTestCase
{
    private const PLUGIN_SIGNATURE = 'nrsamlauth_authentication';

    protected array $testExtensionsToLoad = ['netresearch/nr-saml-auth'];

    #[Test]
    public function thePluginIsAContentElementType(): void
    {
        $values = array_column($this->getTtContentTca()['columns']['CType']['config']['items'], 'value');

        self::assertContains(self::PLUGIN_SIGNATURE, $values);
    }

    #[Test]
    public function theFormOfThePluginShowsTheFlexFormField(): void
    {
        $showItem = $this->getTtContentTca()['types'][self::PLUGIN_SIGNATURE]['showitem'] ?? null;

        self::assertIsString($showItem);
        self::assertStringContainsString('pi_flexform', $showItem);
    }

    /**
     * Resolves the data structure the way FormEngine and the Extbase
     * configuration manager do: TYPO3 13.4 through the ds_pointerField of
     * pi_flexform, TYPO3 14.3 through columnsOverrides of the record type.
     */
    #[Test]
    public function theFlexFormOfThePluginDefinesTheSettingsRecordField(): void
    {
        $tca           = $this->getTtContentTca();
        $flexFormTools = $this->get(FlexFormTools::class);
        // TYPO3 14.3 takes the schema as the fifth and second argument;
        // TYPO3 13.4 reads the TCA from $GLOBALS and ignores the argument.
        $schema = $this->get(TcaSchemaFactory::class)->get('tt_content');
        $row    = ['uid' => 1, 'pid' => 1, 'CType' => self::PLUGIN_SIGNATURE, 'list_type' => ''];

        $identifier    = $flexFormTools->getDataStructureIdentifier($tca['columns']['pi_flexform'], 'tt_content', 'pi_flexform', $row, $schema);
        $dataStructure = $flexFormTools->parseDataStructureByIdentifier($identifier, $schema);

        $field = $dataStructure['sheets']['sGeneral']['ROOT']['el']['settings.samlAuthSettings'] ?? null;
        self::assertIsArray($field);
        self::assertSame(
            'LLL:EXT:nr_saml_auth/Resources/Private/Language/locallang_flexform.xlf:tx_nrsamlauth.pi_flexform.label.selectSamlSettings',
            $field['label'] ?? null,
        );
        self::assertSame('tx_nrsamlauth_domain_model_settings', $field['config']['foreign_table'] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private function getTtContentTca(): array
    {
        $tca = $GLOBALS['TCA']['tt_content'] ?? null;
        self::assertIsArray($tca);

        return $tca;
    }
}
