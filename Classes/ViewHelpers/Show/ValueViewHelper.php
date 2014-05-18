<?php
namespace Vanilla\Messenger\ViewHelpers\Show;
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Fabien Udriot <fabien.udriot@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Vidi\Tca\TcaService;

/**
 * View helper which renders a value given by the context.
 */
class ValueViewHelper extends AbstractViewHelper {

	/**
	 * Return a value given by the context.
	 *
	 * @return string
	 */
	public function render() {
		$value = $this->templateVariableContainer->get('value');
		$fieldName = $this->templateVariableContainer->get('key');
		if ($this->templateVariableContainer->exists('dataType')) {
			$dataType = $this->templateVariableContainer->get('dataType');
			$fieldType = TcaService::table($dataType)->field($fieldName)->getType();

			if ($fieldType === TcaService::RADIO || $fieldType === TcaService::SELECT) {
				$value = TcaService::table($dataType)->field($fieldName)->getLabelForItem($value);
			}

		}
		return $value;
	}
}

?>