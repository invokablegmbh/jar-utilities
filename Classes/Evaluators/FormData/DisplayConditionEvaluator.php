<?php

declare(strict_types=1);

namespace Jar\Utilities\Evaluators\FormData;

use TYPO3\CMS\Backend\Form\FormDataProvider\EvaluateDisplayConditions;

/*
 * This file is part of the JAR/Utilities project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */


/** 
 * @package Jar\Utilities\Evaluators\FormData 
 **/

class DisplayConditionEvaluator extends EvaluateDisplayConditions
{
	/**
	 * @param mixed $condition 
	 * @param array $row 
	 * @return bool 
	 */
	public function match($condition, array $row): bool {
		if(empty($condition)) {
			return true;
		}
		$condition = $this->parseConditionRecursive($condition, $row);
		return $this->evaluateConditionRecursive($condition);
	}
	
}
