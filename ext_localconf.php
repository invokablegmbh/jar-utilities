<?php

call_user_func(function () {
    if (!array_key_exists('jar_utilities_reflection', $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'])) {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['jar_utilities_reflection'] = [
            'frontend' => 'TYPO3\\CMS\\Core\\Cache\\Frontend\\VariableFrontend',
            'options' => [
                'defaultLifetime' => 804600
            ],
            'groups' => ['pages']
        ];
    }
});