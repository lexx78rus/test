<?php
defined('PHPFOX') or exit('No direct script access allowed.');

class Dvs_Component_Controller_Download_Instruction extends Phpfox_Component {

	public function process() {
        $iId = $this->request()->getInt('id');

        Phpfox::getBlock('dvs.download-instructions', array(
            'iId' => $iId
        ));
        header("Content-type: text/plain");
        header("Content-Disposition: attachment; filename={$aDvs.title_url} DVS Integration Instructions.txt");
        $this->template()->setTemplate('');
	}

}

?>