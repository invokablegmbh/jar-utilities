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
	protected array $store = [];



	/**
	 * Put a item in a store.
	 * 
	 * @param string $storeName Name of the store.
	 * @param string $key Key of the item.
	 * @param mixed $value Value of the item.
	 * @TODO: Add Mixed parameter declaration in PHP 8
	 * @return RegistryService 
	 */
	public function set(string $storeName, string $key, $value): RegistryService
	{
		if (!is_array($this->store[$storeName])) {
			$this->store[$storeName] = [];
		}

		$this->store[$storeName][$key] = $value;

		return $this;
	}


	/**
	 * Returns a value out of a store.
	 * @param string $storeName
	 * @param string $key	
	 * @throws Exception
	 * @return \mixed 
	 * @TODO: Add Mixed return value in PHP 8
	 */
	public function get(string $storeName, string $key)
	{
		if(empty($this->store[$storeName][$key])) {
			return false;
		}
		return $this->store[$storeName][$key];
	}



	/**
	 * Returns the whole content of a store
	 * @param string $storeName	 
	 * @throws Exception
	 * @return array
	 */
	public function getWholeStore(string $storeName): array
	{
		return $this->store[$storeName];
	}
}
