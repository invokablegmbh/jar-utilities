<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use InvalidArgumentException;
use RuntimeException;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use UnexpectedValueException;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Utility Class for handling Files
 **/

class FileUtility
{
	/**
	 * @param int $uid 
	 * @return null|FileReference 
	 * @throws InvalidArgumentException 
	 */
	public static function getFileReferenceByUid(int $uid): ?FileReference
	{
		$fileRepository = GeneralUtility::makeInstance(FileRepository::class);
		$fileReference = $fileRepository->findFileReferenceByUid($uid);
		if ($fileReference->isMissing()) {
			return null;
		}
		return $fileReference;
	}




	/**
	 * @param int $uid 
	 * @param null|array $configuration See Manual
	 * @return null|array 
	 * @throws InvalidArgumentException 
	 * @throws RuntimeException 
	 */
	public static function buildFileArrayBySysFileReferenceUid(int $uid, ?array $configuration = null): ?array
	{
		return self::buildFileArrayBySysFileReference(self::getFileReferenceByUid($uid), $configuration);
	}



	/**
	 * @param null|FileReference $fileReference
	 * @param null|array $configuration See Manual 	
	 * @return null|array 
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

		ArrayUtility::mergeRecursiveWithOverrule($setup, $configuration);

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
		}


		// part for creating cropped image urls
		$file = $fileReference->getOriginalFile();
		if ($file->isImage() && !empty($setup['tcaCropVariants']) && $fileReference->hasProperty('crop')) {

			$cropVariantCollection = CropVariantCollection::create((string) $fileReference->getProperty('crop'));

			$cropped = [];
			foreach ($setup['tcaCropVariants'] as $cropName => $cropVariant) {
				$cropSettings = $cropVariantCollection->getCropArea($cropName);
				$processingInstructions = $setup['processingConfigurationForCrop'][$cropName] ?? [];
				ArrayUtility::mergeRecursiveWithOverrule($processingInstructions, [
					'crop' => $cropSettings->makeAbsoluteBasedOnFile($file),
				]);
				$cropped[$cropName] = $file->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $processingInstructions)->getPublicUrl();
			}

			$result['cropped'] = $cropped;
		}

		return $result;
	}

	/**
	 * @param int $bytes 
	 * @param int $decimals 
	 * @return string 
	 */
	public static function humanFilesize(int $bytes, int $decimals = 2): string
	{
		$size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$factor = floor((strlen((string) $bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
	}
}
