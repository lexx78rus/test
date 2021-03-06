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
class Dvs_Component_Controller_Settings extends Phpfox_Component {

	public function process()
	{
		Phpfox::isUser(true);

		if (($iDvsId = $this->request()->getInt('id')))
		{
			if (!Phpfox::getService('dvs')->hasAccess($iDvsId, Phpfox::getUserId()))
			{
				$this->url()->send('');
				return false;
			}

			//If there is an ID, we're editing
			$sBreadcrumb = Phpfox::getPhrase('dvs.edit_dealer_video_showroom');
			$bIsEdit = true;

			if (($aDvs = Phpfox::getService('dvs')->get($iDvsId)))
			{
                //var_dump($aDvs);
				if(is_array($aDvs['dealer_id'])) {
                    $aDvs['dealer_id'] = implode(', ', $aDvs['dealer_id']);
                } else {
                    $aDvs['dealer_id'] = '';
                }

                if ($aDvs['user_id'] == Phpfox::getUserId() || Phpfox::isAdmin())
				{
					$bCanAddDvss = true;
				}
				else
				{
					$bCanAddDvss = false;
				}
			}

			$aPhraseVars = array_merge(Phpfox::getService('dvs.override')->aPhraseVars, Phpfox::getService('dvs.override')->getOverrides($iDvsId));

			//Load existing welcome greeting in to editor
			$aDvs['welcome'] = $aDvs['text_parsed'];
		}
		else
		{
			//New dvs being created, make sure user can create one
			$sBreadcrumb = Phpfox ::getPhrase('dvs.add_dvs');
			$bIsEdit = false;
			$aDvs = array();

			$aDvss = Phpfox::getService('dvs')->listDvss(0, 0, Phpfox::getUserId(), false);

			if (count($aDvss) < Phpfox::getUserParam('dvs.dvss'))
			{
				$bCanAddDvss = true;
			}
			else
			{
				$bCanAddDvss = false;
			}

			$aPhraseVars = Phpfox::getService('dvs.override')->aPhraseVars;
		}

		$this->setParam(array(
			'country_child_value' => 'US'
		));

		$connectors = Phpfox::getService('dvs')->getConnectors();

		$importInventoryRes = null;
		if($_POST){
			$importInventoryRes = Phpfox::getService('dvs')->importInventory($iDvsId);
		}
		$sMessage           = Phpfox::getPhrase('dvs.import_finished_successfully');

		// if($this->request()->get('import') == 1){// lock this
		if(isset($_COOKIE['dev']) && ($_COOKIE['dev'] == 1) && 0){// lock this
			Phpfox::getService('dvs')->importInventory($iDvsId);
		}

        $aCountries = Phpfox::getService('dvs')->getCountry();

        $this->template()
			->setHeader(array(
				'country.js' => 'module_core',
				//'add.css' => 'module_dvs',
				'settings.css' => 'module_dvs',))
			->assign(array(
				'aForms'                   => $aDvs,
				'aCountries'                 => $aCountries,
				'connectors'               => $connectors,
				'importInventoryRes'       => $importInventoryRes,
				'sMessage'                 => $sMessage,
				'bIsEdit'                  => $bIsEdit,
				'bCanAddDvss'              => $bCanAddDvss,
				'bSubdomainMode'           => (Phpfox::getParam('dvs.enable_subdomain_mode') ? true : false),
				'aPhraseVars'              => $aPhraseVars,
				'iWelcomeGreetingMaxChars' => Phpfox::getParam('dvs.welcome_greeting_max_chars'),
				's1onOneDefault'           => (Phpfox::getParam('dvs.1onone_video_url_replacement') ? Phpfox::getParam('dvs.1onone_video_url_replacement') : 'overview'),
				'sNew2UDefault'            => (Phpfox::getParam('dvs.new2u_video_url_replacement') ? Phpfox::getParam('dvs.new2u_video_url_replacement') : 'used-car-report'),
				'sTop200Default'           => (Phpfox::getParam('dvs.top200_video_url_replacement') ? Phpfox::getParam('dvs.top200_video_url_replacement') : 'test-drive')
			))
			->setBreadcrumb(Phpfox::getPhrase('dvs.my_dealer_video_showrooms'), Phpfox::getLib('url')->makeUrl('dvs'))
			->setBreadcrumb($sBreadcrumb);
	}


}

?>
