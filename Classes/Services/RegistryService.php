<?php

declare(strict_types=1);

namespace Jar\Utilities\Services;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Services 
 * Simple Memory Cache Class, handy for use before TYPO3 native Caches are available (they can not be injected/instantiated during ext_localconf.php)
 **/

class RegistryService  implements \TYPO3\CMS\Core\SingletonInterface
{

	/**
	 * @var array
	 */
	protected array $entries = [];



	/**
	 * @param string $entryName 
	 * @param string $key 
	 * @param mixed $value 
	 * @TODO: Add Mixed parameter declaration in PHP 8
	 * @return RegistryService 
	 */
	public function set(string $entryName, string $key, $value): RegistryService
	{
		if (!is_array($this->entries[$entryName])) {
			$this->entries[$entryName] = [];
		}

		$this->entries[$entryName][$key] = $value;

		return $this;
	}


	/**
	 * @param string $entryName
	 * @param string $key	
	 * @throws Exception
	 * @return \mixed 
	 * @TODO: Add Mixed return value in PHP 8
	 */
	public function get(string $entryName, string $key)
	{
		if(empty($this->entries[$entryName][$key])) {
			return false;
		}
		return $this->entries[$entryName][$key];
	}



	/**
	 * @param string $entryName	 
	 * @throws Exception
	 * @return array
	 */
	public function getWholeEntry(string $entryName): array
	{
		return $this->entries[$entryName];
	}
}
