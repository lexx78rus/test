<?php

/**
 * [PHPFOX_HEADER]
 */
defined('PHPFOX') or exit('No direct script access allowed.');

/**
 *
 *
 * @copyright		Konsort.org
 * @author  		Konsort.org
 * @package 		DVS
 */
class Dvs_Component_Block_Player_Preview extends Phpfox_Component {

	public function process()
	{
		$aVals = $this->request()->getArray('val');
		$aValsClean = array();

		foreach ($aVals as $sKey => $sVal)
		{
			$sKey = str_replace('_', '-', $sKey);
			$aValsClean[$sKey] = $sVal;
		}

		$aValsClean['logo-branding-url'] = '';

		$sMakes = '';

		foreach ($aVals['selected_makes'] as $sMake => $bSelected)
		{
			if ($bSelected)
			{
				$sMakes .= $sMake . ',';
			}
		}

		$aValsClean['selected-makes'] = rtrim($sMakes, ',');

		$sUrl = 'dvs.view.preview.' . $aVals['dvs_id'];
		$sIframeUrl = Phpfox::getLib('url')->makeUrl($sUrl, $aValsClean);

		if( isset($aVals['shorturl']) and !empty($aVals['shorturl']) ) {
			if( Phpfox::getParam('dvs.enable_subdomain_mode') ) {
				$sIframeUrl = Phpfox::getLib('url')->makeUrl( 'www' ) . $aVals['shorturl'];
			} else {
				$sIframeUrl = Phpfox::getLib('url')->makeUrl( '' ) . $aVals['shorturl'];
			}
		}
		$this->template()
			->assign(array(
				'aVals' => $aVals,
				'sIframeUrl' => $sIframeUrl,
		));
	}


}

?>