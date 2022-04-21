<?php

declare(strict_types=1);

namespace Jar\Utilities\Utilities;

use InvalidArgumentException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Utilities 
 * Collection of string helpers.
 **/

class StringUtility
{


	/**
	 * Crops a string.
	 * 
	 * @param string $value The string to crop.
	 * @param int $maxCharacters Length of cropping.
	 * @return string The cropped string.
	 * @throws InvalidArgumentException 
	 */
	public static function crop(string $value, int $maxCharacters = 150): string
	{
		$contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$content = $contentObject->cropHTML($value, $maxCharacters . '|...|1');
		return $content;
	}


	/**
	 * Same as "strip_tags" but leaves spaces at the position of removed tags.
	 * 
	 * @param string $string The string to strip.
	 * @return string The stripped string.
	 */
	public static function ripTags($string): string
	{
		$string = preg_replace('/<[^>]*>/', ' ', $string);
		$string = str_replace("\r", '', $string);
		$string = str_replace("\n", ' ', $string);
		$string = str_replace("\t", ' ', $string);
		$string = trim(preg_replace('/ {2,}/', ' ', $string));
		return $string;
	}


	/**
	 * Simple sanitizing of strings, no complex handling of umlauts like "äöü".
	 * 
	 * @param string $string The string to sanitize.
	 * @param bool $toLowerCase Activate transform to lower case.
	 * @return string The sanitized string.
	 */
	public static function fastSanitize($string, $toLowerCase = true): string
	{
		if ($toLowerCase) {
			$string = strtolower($string);
		}
		$pattern = '/([^a-z0-9]){1,}/i';
		$replaced = preg_replace($pattern, '_', $string);
		$replaced = trim($replaced, '_');
		return empty($replaced) ? md5($string) : $replaced;
	}

	/**
	 * More complex sanitizing of strings, also handles of umlauts like "äöü".
	 * based on the VHS Extension (https://github.com/FluidTYPO3/vhs)
	 * 
	 * @param string $string The string to sanitize.
	 * @param bool $toLowerCase Activate transform to lower case.
	 * @return string The sanitized string.
	 */
	public static function sanitize($string, $toLowerCase = true): string
	{
		$characterMap = [
			'¹' => 1, '²' => 2, '³' => 3, '°' => 0, '€' => 'eur', 'æ' => 'ae', 'ǽ' => 'ae', 'À' => 'A',
			'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Å' => 'AA', 'Ǻ' => 'A', 'Ă' => 'A', 'Ǎ' => 'A', 'Æ' => 'AE',
			'Ǽ' => 'AE', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'aa', 'ǻ' => 'a', 'ă' => 'a',
			'ǎ' => 'a', 'ª' => 'a', '@' => 'at', 'Ĉ' => 'C', 'Ċ' => 'C', 'ĉ' => 'c', 'ċ' => 'c', '©' => 'c',
			'Ð' => 'Dj', 'Đ' => 'D', 'ð' => 'dj', 'đ' => 'd', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
			'Ĕ' => 'E', 'Ė' => 'E', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ĕ' => 'e', 'ė' => 'e',
			'ƒ' => 'f', 'Ĝ' => 'G', 'Ġ' => 'G', 'ĝ' => 'g', 'ġ' => 'g', 'Ĥ' => 'H', 'Ħ' => 'H', 'ĥ' => 'h',
			'ħ' => 'h', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ĩ' => 'I', 'Ĭ' => 'I', 'Ǐ' => 'I',
			'Į' => 'I', 'Ĳ' => 'IJ', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ĩ' => 'i', 'ĭ' => 'i',
			'ǐ' => 'i', 'į' => 'i', 'ĳ' => 'ij', 'Ĵ' => 'J', 'ĵ' => 'j', 'Ĺ' => 'L', 'Ľ' => 'L', 'Ŀ' => 'L',
			'ĺ' => 'l', 'ľ' => 'l', 'ŀ' => 'l', 'Ñ' => 'N', 'ñ' => 'n', 'ŉ' => 'n', 'Ò' => 'O', 'Ô' => 'O',
			'Õ' => 'O', 'Ō' => 'O', 'Ŏ' => 'O', 'Ǒ' => 'O', 'Ő' => 'O', 'Ơ' => 'O', 'Ø' => 'OE', 'Ǿ' => 'O',
			'Œ' => 'OE', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ō' => 'o', 'ŏ' => 'o', 'ǒ' => 'o', 'ő' => 'o',
			'ơ' => 'o', 'ø' => 'oe', 'ǿ' => 'o', 'º' => 'o', 'œ' => 'oe', 'Ŕ' => 'R', 'Ŗ' => 'R', 'ŕ' => 'r',
			'ŗ' => 'r', 'Ŝ' => 'S', 'Ș' => 'S', 'ŝ' => 's', 'ș' => 's', 'ſ' => 's', 'Ţ' => 'T', 'Ț' => 'T',
			'Ŧ' => 'T', 'Þ' => 'TH', 'ţ' => 't', 'ț' => 't', 'ŧ' => 't', 'þ' => 'th', 'Ù' => 'U', 'Ú' => 'U',
			'Û' => 'U', 'Ũ' => 'U', 'Ŭ' => 'U', 'Ű' => 'U', 'Ų' => 'U', 'Ư' => 'U', 'Ǔ' => 'U', 'Ǖ' => 'U',
			'Ǘ' => 'U', 'Ǚ' => 'U', 'Ǜ' => 'U', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ũ' => 'u', 'ŭ' => 'u',
			'ű' => 'u', 'ų' => 'u', 'ư' => 'u', 'ǔ' => 'u', 'ǖ' => 'u', 'ǘ' => 'u', 'ǚ' => 'u', 'ǜ' => 'u',
			'Ŵ' => 'W', 'ŵ' => 'w', 'Ý' => 'Y', 'Ÿ' => 'Y', 'Ŷ' => 'Y', 'ý' => 'y', 'ÿ' => 'y', 'ŷ' => 'y',
			'Ъ' => '', 'Ь' => '', 'А' => 'A', 'Б' => 'B', 'Ц' => 'C', 'Ч' => 'Ch', 'Д' => 'D', 'Е' => 'E',
			'Ё' => 'E', 'Э' => 'E', 'Ф' => 'F', 'Г' => 'G', 'Х' => 'H', 'И' => 'I', 'Й' => 'J', 'Я' => 'Ja',
			'Ю' => 'Ju', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O', 'П' => 'P', 'Р' => 'R',
			'С' => 'S', 'Ш' => 'Sh', 'Щ' => 'Shch', 'Т' => 'T', 'У' => 'U', 'В' => 'V', 'Ы' => 'Y', 'З' => 'Z',
			'Ж' => 'Zh', 'ъ' => '', 'ь' => '', 'а' => 'a', 'б' => 'b', 'ц' => 'c', 'ч' => 'ch', 'д' => 'd',
			'е' => 'e', 'ё' => 'e', 'э' => 'e', 'ф' => 'f', 'г' => 'g', 'х' => 'h', 'и' => 'i', 'й' => 'j',
			'я' => 'ja', 'ю' => 'ju', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o', 'п' => 'p',
			'р' => 'r', 'с' => 's', 'ш' => 'sh', 'щ' => 'shch', 'т' => 't', 'у' => 'u', 'в' => 'v', 'ы' => 'y',
			'з' => 'z', 'ж' => 'zh', 'Ä' => 'AE', 'Ö' => 'OE', 'Ü' => 'UE', 'ß' => 'ss', 'ä' => 'ae', 'ö' => 'oe',
			'ü' => 'ue', 'Ç' => 'C', 'Ğ' => 'G', 'İ' => 'I', 'Ş' => 'S', 'ç' => 'c', 'ğ' => 'g', 'ı' => 'i',
			'ş' => 's', 'Ā' => 'A', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'I', 'Ķ' => 'K', 'Ļ' => 'L', 'Ņ' => 'N',
			'Ū' => 'U', 'ā' => 'a', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
			'ū' => 'u', 'Ґ' => 'G', 'І' => 'I', 'Ї' => 'Ji', 'Є' => 'Ye', 'ґ' => 'g', 'і' => 'i', 'ї' => 'ji',
			'є' => 'ye', 'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T',
			'Ů' => 'U', 'Ž' => 'Z', 'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's',
			'ť' => 't', 'ů' => 'u', 'ž' => 'z', 'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'E', 'Ł' => 'L', 'Ń' => 'N',
			'Ó' => 'O', 'Ś' => 'S', 'Ź' => 'Z', 'Ż' => 'Z', 'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l',
			'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z', 'ż' => 'z', 'Α' => 'A', 'Β' => 'B', 'Γ' => 'G',
			'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'E', 'Θ' => 'Th', 'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L',
			'Μ' => 'M', 'Ν' => 'N', 'Ξ' => 'X', 'Ο' => 'O', 'Π' => 'P', 'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T',
			'Υ' => 'Y', 'Φ' => 'Ph', 'Χ' => 'Ch', 'Ψ' => 'Ps', 'Ω' => 'O', 'Ϊ' => 'I', 'Ϋ' => 'Y', 'ά' => 'a',
			'έ' => 'e', 'ή' => 'e', 'ί' => 'i', 'ΰ' => 'Y', 'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd',
			'ε' => 'e', 'ζ' => 'z', 'η' => 'e', 'θ' => 'th', 'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm',
			'ν' => 'n', 'ξ' => 'x', 'ο' => 'o', 'π' => 'p', 'ρ' => 'r', 'ς' => 's', 'σ' => 's', 'τ' => 't',
			'υ' => 'y', 'φ' => 'ph', 'χ' => 'ch', 'ψ' => 'ps', 'ω' => 'o', 'ϊ' => 'i', 'ϋ' => 'y', 'ό' => 'o',
			'ύ' => 'y', 'ώ' => 'o', 'ϐ' => 'b', 'ϑ' => 'th', 'ϒ' => 'Y', 'أ' => 'a', 'ب' => 'b', 'ت' => 't',
			'ث' => 'th', 'ج' => 'g', 'ح' => 'h', 'خ' => 'kh', 'د' => 'd', 'ذ' => 'th', 'ر' => 'r', 'ز' => 'z',
			'س' => 's', 'ش' => 'sh', 'ص' => 's', 'ض' => 'd', 'ط' => 't', 'ظ' => 'th', 'ع' => 'aa', 'غ' => 'gh',
			'ف' => 'f', 'ق' => 'k', 'ك' => 'k', 'ل' => 'l', 'م' => 'm', 'ن' => 'n', 'ه' => 'h', 'و' => 'o',
			'ي' => 'y', 'ạ' => 'a', 'ả' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a',
			'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e',
			'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ị' => 'i', 'ỉ' => 'i', 'ọ' => 'o',
			'ỏ' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ờ' => 'o', 'ớ' => 'o',
			'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ụ' => 'u', 'ủ' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u',
			'ử' => 'u', 'ữ' => 'u', 'ỳ' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'Ạ' => 'A', 'Ả' => 'A',
			'Ầ' => 'A', 'Ấ' => 'A', 'Ậ' => 'A', 'Ẩ' => 'A', 'Ẫ' => 'A', 'Ằ' => 'A', 'Ắ' => 'A', 'Ặ' => 'A',
			'Ẳ' => 'A', 'Ẵ' => 'A', 'Ẹ' => 'E', 'Ẻ' => 'E', 'Ẽ' => 'E', 'Ề' => 'E', 'Ế' => 'E', 'Ệ' => 'E',
			'Ể' => 'E', 'Ễ' => 'E', 'Ị' => 'I', 'Ỉ' => 'I', 'Ọ' => 'O', 'Ỏ' => 'O', 'Ồ' => 'O', 'Ố' => 'O',
			'Ộ' => 'O', 'Ổ' => 'O', 'Ỗ' => 'O', 'Ờ' => 'O', 'Ớ' => 'O', 'Ợ' => 'O', 'Ở' => 'O', 'Ỡ' => 'O',
			'Ụ' => 'U', 'Ủ' => 'U', 'Ừ' => 'U', 'Ứ' => 'U', 'Ự' => 'U', 'Ử' => 'U', 'Ữ' => 'U', 'Ỳ' => 'Y',
			'Ỵ' => 'Y', 'Ỷ' => 'Y', 'Ỹ' => 'Y',
		];
		$specialCharsSearch = array_keys($characterMap);
		$specialCharsReplace = array_values($characterMap);
		$string = str_replace($specialCharsSearch, $specialCharsReplace, $string);
		if($toLowerCase) {
			$string = strtolower($string);
		}
		$pattern = '/([^A-Za-z0-9]){1,}/';
		$string = preg_replace($pattern, '_', $string);
		return trim($string, '_');
	}
}
