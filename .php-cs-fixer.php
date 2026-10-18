<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */

declare(strict_types=1);

// Shared org PHP-CS-Fixer config (netresearch/typo3-ci-workflows
// config/php-cs-fixer/config.php): rule set, header and finder.
// Resources/Private/Libs holds the third-party library build for non-composer
// installs and stays excluded, as before.
$createConfig = require __DIR__ . '/.Build/vendor/netresearch/typo3-ci-workflows/config/php-cs-fixer/config.php';

return $createConfig(
    <<<'EOF'
        SPDX-License-Identifier: GPL-2.0-or-later
        SPDX-FileCopyrightText: Netresearch DTT GmbH
        EOF,
    __DIR__,
    [],
    ['Resources/Private/Libs'],
);
