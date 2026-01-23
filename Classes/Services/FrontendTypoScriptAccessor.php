<?php

declare(strict_types=1);

namespace Jar\Utilities\Services;

use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;

final class FrontendTypoScriptAccessor
{
    public function __construct(
        private readonly FrontendTypoScriptFactory $factory
    ) {}

    /**
     * Baut das vollständige Setup-Array für eine Seite.
     */
    public function buildSetupArray(SiteInterface $site, array $sysTemplateRows): array
    {
        // Schritt 1: Settings + Condition-Listen berechnen
        $frontendTypoScript = $this->factory->createSettingsAndSetupConditions(
            $site,
            $sysTemplateRows,
            [],
            null
        );

        // Schritt 2: Volles Setup inkl. setupArray berechnen (type "0" als Standard)
        $frontendTypoScript = $this->factory->createSetupConfigOrFullSetup(
            true,                // needsFullSetup
            $frontendTypoScript,
            $site,
            $sysTemplateRows,
            [],                  // expressionMatcherVariables
            '0',                 // type
            null,                // typoScriptCache
            null                 // request
        );

        return $frontendTypoScript->getSetupArray() ?? [];
    }
}