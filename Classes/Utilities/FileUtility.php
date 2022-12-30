<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use InvalidArgumentException;
use RuntimeException;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use UnexpectedValueException;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Handle files and their references.
 **/

class FileUtility
{
	/**
	 * Loads \TYPO3\CMS\Core\Resource\FileReference object from sys_file_reference table via uid.
	 * 
	 * @param int $uid UID of the sys_file_reference record.
	 * @return null|FileReference Return null if resource doesn't exist or file is missing.
	 * @throws InvalidArgumentException 
	 */
	public static function getFileReferenceByUid(int $uid): ?FileReference
	{		
		$fileRepository = GeneralUtility::makeInstance(FileRepository::class);
		try {
			$fileReference = $fileRepository->findFileReferenceByUid($uid);		
		} catch(ResourceDoesNotExistException $e) {
			return null;
		}

		if ($fileReference->isMissing()) {
			return null;
		}
		return $fileReference;
	}




	/**
	 * Shorthand for FileUtility::buildFileArrayBySysFileReference(FileUtility::getFileReferenceByUid($uid)),
	 * accepts the UID of an FileReference instead of using the FileReference object directly.
	 * 
	 * @param int $uid UID of the sys_file_reference record.
	 * @param null|array $configuration See Manual
	 * @return null|array File-information array or ``null`` if resource doesn't exist or file is missing.
	 * @throws InvalidArgumentException 
	 * @throws RuntimeException 
	 */
	public static function buildFileArrayBySysFileReferenceUid(int $uid, ?array $configuration = null): ?array
	{
		return self::buildFileArrayBySysFileReference(self::getFileReferenceByUid($uid), $configuration);
	}



	/**
	 * Preparation of files and images in a simple array structure. Very helpful in image preparation and cropping.
	 * 
	 * @param null|FileReference $fileReference
	 * @param null|array $configuration See Manual 	
	 * @return null|array File-information array or ``null`` if resource doesn't exist or file is missing.
	 * @throws InvalidArgumentException 
	 * @throws RuntimeException 
	 * @throws UnexpectedValueException 
	 */
	public static function buildFileArrayBySysFileReference(?FileReference $fileReference, ?array $configuration = []): ?array
	{
		
		if ($fileReference === null || $fileReference->isMissing()) {
			return null;
		}


		$setup = [
			'showDetailedInformations' => false,
			'tcaCropVariants' => [],
			'processingConfigurationForCrop' => []
		];

		ArrayUtility::mergeRecursiveWithOverrule($setup, $configuration ?? []);

		$result = [];

		$url = $fileReference->getPublicUrl();

		$result = array(
			'uid' => $fileReference->getUid(),
			'url' =>  $url,
			'alt' => $fileReference->getAlternative(),
			'title' => $fileReference->getTitle(),
			'description' => $fileReference->getDescription(),
			'link' => FormatUtility::buildLinkArray($fileReference->getLink()),
		);

		if ($setup['showDetailedInformations']) {
			$result['name'] = $fileReference->getName();
			$result['extension'] = $fileReference->getExtension();
			$result['type'] = $fileReference->getType();
			$result['mimetype'] = $fileReference->getMimeType();
			$result['size'] = $fileReference->getSize();
			$result['basename'] = $fileReference->getNameWithoutExtension();
		}


		
		$file = $fileReference->getOriginalFile();
		if ($file->isImage()) {
			
			// part for creating cropped image urls
			if(!empty($setup['tcaCropVariants']) && $fileReference->hasProperty('crop')) {

				$cropVariantCollection = CropVariantCollection::create((string) $fileReference->getProperty('crop'));
				$cropped = [];
				foreach ($setup['tcaCropVariants'] as $cropName => $cropVariant) {
					$cropSettings = $cropVariantCollection->getCropArea($cropName);
					$processingInstructions = $setup['processingConfigurationForCrop'][$cropName] ?? [];
					ArrayUtility::mergeRecursiveWithOverrule($processingInstructions, [
						'crop' => $cropSettings->makeAbsoluteBasedOnFile($file),
					]);

					// special case: if cropping "default" is active, use this cropped image directly as result
					$croppedUrl = $file->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingInstructions)->getPublicUrl();

					if($cropName === 'default') {
						$result['url'] = $croppedUrl;
					} else {
						$cropped[$cropName] = $croppedUrl;
					}
					
				}

				if(count($cropped)) {
					$result['cropped'] = $cropped;
				}
			}

			// Store Focuspoint
			if(ExtensionManagementUtility::isLoaded('focuspoint')) {
				$result['has_focuspoint'] = (!empty($file->getProperty('focus_point_x')) && !empty($file->getProperty('focus_point_y')));

				if($result['has_focuspoint']) {
					$result['focuspoint'] = [
						'x' => $file->getProperty('focus_point_x'),
						'y' => $file->getProperty('focus_point_y'),
						'w' => $file->getProperty('width'),
						'h' => $file->getProperty('height'),
					];
				}
			}
		}

		return $result;
	}


	/**
	 * Returns the File object to a given path.
	 * 
	 * @param string $path Path to the file
	 * @return null|File File objectay or ``null`` if resource doesn't exist or file is missing.
	 * @throws InvalidArgumentException 
	 */
	public static function getFileByPath(string $path): ?File {

		$resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);

		try {
			$file = $resourceFactory->retrieveFileOrFolderObject($path);
		} catch (\TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException $e) {
			$file = null;
		}

		return $file;
	}



	/**
	 * @param null|File $file The file object
	 * @param null|array $configuration See Manual
	 * @return null|array File-information array or ``null`` if resource doesn't exist or file is missing.
	 * @throws InvalidArgumentException 
	 * @throws RuntimeException 
	 * @throws UnexpectedValueException 
	 */
	public static function buildFileArrayByFile(?File $file, ?array $configuration = []): ?array
	{	
		if(empty($file)) {
			return null;
		}

		$resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
		$fileReference = $resourceFactory->createFileReferenceObject(
			[
				'uid_local' => $file->getUid(),
				'uid_foreign' => uniqid('NEW_'),
				'uid' => uniqid('NEW_'),
				'crop' => json_encode([
					'default' => [
						'cropArea' => [
							'x' => 0,
							'y' => 0,
							'width' => 1,
							'height' => 1,
						]
					]
				]),
				'link' => null,
			]
		);

		return static::buildFileArrayBySysFileReference($fileReference, $configuration);
	}


	/**
	 * @param null|string $path The file path
	 * @param null|array $configuration See Manual
	 * @return null|array File-information array or ``null`` if resource doesn't exist or file is missing.
	 * @throws InvalidArgumentException 
	 * @throws RuntimeException 
	 * @throws UnexpectedValueException 
	 */
	public static function buildFileArrayByPath(?string $path, ?array $configuration = []): ?array
	{
		return static::buildFileArrayByFile(static::getFileByPath($path), $configuration);
	}




	/**
	 * Converts filesizes in a human readable format.
	 * 
	 * @param int $bytes Size of file in bytes.
	 * @param int $decimals (optional) Length of decimals.
	 * @return string Filesize in human readable format.
	 */
	public static function humanFilesize(int $bytes, int $decimals = 2): string
	{
		$size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$factor = floor((strlen((string) $bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
	}
}
