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
class Dvs_Component_Block_Share_Text extends Phpfox_Component {
	public function process() {
		$this->template()
            ->setHeader(array('share_email.css' => 'style_css'))
			->assign(array(
                'aVideo' => Phpfox::getService('dvs.video')->get($this->request()->get('sRefId')),
                'aDvs' => Phpfox::getService('dvs')->get($this->request()->getInt('iDvsId'), false),
                'bSaveGa' => $this->request()->get('bSaveGa', 1)
		    ));
	}

}

?>