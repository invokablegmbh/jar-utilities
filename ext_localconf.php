<?php

use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use Jar\Utilities\Hooks\TCEmainHook;

call_user_func(function () {
    if (!array_key_exists('jar_utilities_reflection', $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['jar_utilities_reflection'] = [
            'frontend' => VariableFrontend::class,
            'options' => [
                'defaultLifetime' => 804600
            ],
            'groups' => ['pages']
        ];
    }

    // Register hook for TCEmain to update the parents when child is changed (Support for reflection service cache)
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['j77_template_ctntfix'] = TCEmainHook::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['j77_template_ctntfix'] = TCEmainHook::class;
});
