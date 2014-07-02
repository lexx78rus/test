<?php

/**
 * [PHPFOX_HEADER]
 */
defined('PHPFOX') or exit('No direct script access allowed.');

/**
 *
 *
 * @copyright		Konsort.org
 * @author  		James
 * @package 		DVS
 */
class Dvs_Service_Dvs extends Phpfox_Service {

	public function __construct()
	{
		$this->_sTable = Phpfox::getT('ko_dvs');
	}

	public function getModelInventory($ko_id = 0)
	{
		if(empty($ko_id)){
			return array();
		}

		$videoCar = $this->getVideoCar($ko_id);
		// $videoCar = $this->getVideoCar(3511);// delete this

		$where_arr    = array();
		$primaryTitle = $videoCar['year'].' '.$videoCar['make'].' '.$videoCar['model'];
		$where_arr[]  = "(title LIKE '".Phpfox::getLib('database')->escape($primaryTitle)."%')";

		if(strstr($videoCar['model'], ' and ')){
			// chrysler town and country model
			$where_arr[]  = "(title LIKE '".Phpfox::getLib('database')->escape($videoCar['year'].' '.$videoCar['make'].' '.str_replace(' and ', ' & ', $videoCar['model']))."%')";
		}

		if(strstr($videoCar['model'], '1 Series')){
			// 1 Series
			$where_arr[]  = "(title REGEXP '".Phpfox::getLib('database')->escape('.*1[0-9]{2}(i|xi)')."')";
		}

		if(strstr($videoCar['model'], '2 Series')){
			// 2 Series
			$where_arr[]  = "(title REGEXP '".Phpfox::getLib('database')->escape('.*2[0-9]{2}(i|xi)')."')";
		}

		if(strstr($videoCar['model'], '3 Series')){
			// 3 Series
			$where_arr[]  = "(title REGEXP '".Phpfox::getLib('database')->escape('.*3[0-9]{2}(i|xi)')."')";
		}

		if(strstr($videoCar['model'], '5 Series')){
			// 5 Series
			$where_arr[]  = "(title REGEXP '".Phpfox::getLib('database')->escape('.*5[0-9]{2}(i|xi)')."')";
		}

		if(strstr($videoCar['model'], '6 Series')){
			// 6 Series
			$where_arr[]  = "(title REGEXP '".Phpfox::getLib('database')->escape('.*6[0-9]{2}(i|xi)')."')";
		}

		if(strstr($videoCar['model'], '7 Series')){
			// 7 Series
			$where_arr[]  = "(title REGEXP '".Phpfox::getLib('database')->escape('.*7[0-9]{2}(i|xi)')."')";
		}

		if(strstr($videoCar['model'], 'Hybrid-Energi')){
			// Ford Fusion Hybrid-Energi
			$where_arr[]  = "(title LIKE '".Phpfox::getLib('database')->escape($videoCar['year'].' '.$videoCar['make'].' '.str_replace('Hybrid-Energi', 'Hybrid', $videoCar['model']))."%')";
			$where_arr[]  = "(title LIKE '".Phpfox::getLib('database')->escape($videoCar['year'].' '.$videoCar['make'].' '.str_replace('Hybrid-Energi', 'Energi', $videoCar['model']))."%')";
		}

		if(strstr($videoCar['model'], '2500-3500')){
			// Chevrolet Silverado 2500-3500
			$where_arr[]  = "(title LIKE '".Phpfox::getLib('database')->escape($videoCar['year'].' '.$videoCar['make'].' '.str_replace('2500-3500', '2500', $videoCar['model']))."%')";
			$where_arr[]  = "(title LIKE '".Phpfox::getLib('database')->escape($videoCar['year'].' '.$videoCar['make'].' '.str_replace('2500-3500', '3500', $videoCar['model']))."%')";
		}

		$inventories = Phpfox::getLib('database')->select('*')
			->from(Phpfox::getT('ko_dvs_inventory'))
			->where(join(' OR ', $where_arr))
			// ->limit(0)
			->execute('getRows');

		return $inventories;
	}
	/*phpmasterminds Edited for sort in gallery and footer starts*/
public function aasort (&$array, $key) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    arsort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
	return $array;
}
/*phpmasterminds Edited for sort in gallery and footer starts*/

	public function cleanImages()
	{
		return true;

		$inventories = Phpfox::getLib('database')->select('*')
			->from(Phpfox::getT('ko_dvs_inventory'))
			->limit(500)
			->execute('getRows');

			if($inventories){
				$image_dest_dir  = Phpfox::getParam('core.dir_file');
				foreach ($inventories as $inv) {
		    	if(file_exists($image_dest_dir.$inv['image']) && 0){
		    		$new_path = str_replace('inventory/2014/', 'inventory/2014a/', $inv['image']);
						Phpfox::getLib('file')->copy(Phpfox::getParam('core.path').'file/'.$inv['image'], $image_dest_dir.$new_path);
			    }
	    		$new_path = str_replace('inventory/2014/', 'inventory/2014a/', $inv['image']);
		    	if(!file_exists($image_dest_dir.$new_path && 0)){
		    		var_dump($new_path);
			    }
				}
			}
			return true;
	}

	public function importInventory($dvs_id)
	{
		//global admin cp setting for userGuid and apiKey
    $userGuid  = $this->getSettingValue('dvs_inventory_guid');
    $apiKey    = $this->getSettingValue('dvs_inventory_api_key');
    $connector = $this->getInventoryConnector($dvs_id);
    $dvs       = $this->get($dvs_id);

		$connector['inventory_url'] = str_replace('http://', '', $dvs['inv_domain']);

    if(empty($connector) || empty($connector['guid']) || empty($connector['inventory_url'])){
      return array('error' => 'Connector Error', 'status' => false);
    }

		//Load player data
    $aPlayer            = Phpfox::getService('dvs.player')->get($dvs_id);
    $detailedImportType = false;

    $time_start    = microtime(true);
    $refusedMakes  = array();
    $mcnt          = 0;
    $firstItemLink = '';

    if(!$detailedImportType){
      $start = 0;
      do {
        $result = $this->importInventoryQuery($apiKey, $connector['guid'], array(
          "start"   => $start,
        ));
        $carsArr = array_shift($result['results']);
        if(empty($carsArr[1]['name']['href']) || $firstItemLink == $carsArr[1]['name']['href']){
          break;
        }
        if($start >= 640){ // @todo delete this
          break;
        }
        if(empty($firstItemLink) && !empty($carsArr[1]['name']['href'])){
          $firstItemLink = $carsArr[1]['name']['href'];
        }
        if(!empty($result)){
          $mcnt += $result['count'];
          $addedCount = $this->addDvsInventories($carsArr, $dvs_id);
          $start += $addedCount;
        }
      }while (!empty($carsArr));

    }else{
  		$aValidVSYears = Phpfox::getService('dvs.video')->getValidVSYears($aPlayer['makes']);

  		if(empty($aValidVSYears)){
  			return array('error' => 'Valid Years Error Setting', 'status' => false);
  		}

  		foreach ($aValidVSYears as $yearValue) {
  			$aValidVSMakes = Phpfox::getService('dvs.video')->getValidVSMakes($yearValue, $aPlayer['makes']);
  			if(!empty($aValidVSMakes)){
  				foreach ($aValidVSMakes as $makeValue) {

  					// check if this make unavailable by this catalogue, checked previously
  					if(in_array($makeValue['make'], $refusedMakes)){
  						continue;
  					}

  					// check if this make unavailable by this catalogue
  					$result = $this->importInventoryQuery($apiKey, $connector['guid'], array(
  						"make"   => $makeValue['make'],
  					));

  					$totalResults = $result->totalResults;
  					
  					$pages = ceil($totalResults / 16);

  					if(empty($result->results)){
  						$refusedMakes[] = $makeValue['make'];
  						continue;
  					}else{
  						// check if this make unavailable by this catalogue for selected year
  						$result = $this->importInventoryQuery($connector['guid'], array(
  							"year"   => $yearValue,
  							"make"   => $makeValue['make'],
  							"domain" => $connector['inventory_url'],
  						), $userGuid, $apiKey, false);
  						if(empty($result->results)){
  							continue;
  						}else{ // full make import, then filter items on render by regular expressoin
  							// echo 'full import success.'."\n";
  							$mcnt += count($result->results);
  							$this->addDvsInventories($result->results, $dvs_id);
  						}
  					}

  					$thinImportMode = null;
  					if($thinImportMode){ // thin import mode - import only certain model car inventory // currently temporary disabled
  						$aVideoSelect = Phpfox::getService('dvs.video')->getVideoSelect($yearValue, $makeValue['make'], '', true);
  						if(!empty($aVideoSelect)){
  							foreach ($aVideoSelect as $videoValue) {
  								$result = $this->importInventoryQuery($connector['guid'], array(
  									"year"   => $videoValue['year'],
  									"make"   => $videoValue['make'],
  									"model"  => $videoValue['model'],
  									"domain" => $connector['inventory_url'],
  								), $userGuid, $apiKey, false);
  								if(!empty($result->results)){
  									$this->addDvsInventories($result->results, $dvs_id, $yearValue);
  								}
  							}
  							$mcnt += count($aValidVSMakes);
  							if($mcnt > 2){
  								break;
  							}
  						}
  					}
  				}
  			}
  		}
    }
		$time_end = microtime(true);
		$execution_time = ($time_end - $time_start);
		$trackingDevMode = false;
		if($trackingDevMode){
			echo "\n";
			echo 'Total Execution Time: '.$execution_time.' sec'."\n";
			echo 'finaly - '.$mcnt;
      die();
		}

		if(isset($result->error)){
			return array('error' => $result->error, 'status' => false);
		}

    $res = Phpfox::getLib('database')->update(Phpfox::getT('ko_dvs'), array(
			'inv_last_cronjob' => time(),
    ), "dvs_id = '".$dvs_id."'");

		return array('mcnt' => $mcnt, 'status' => true);
	}

  /**
   * Api request to import.io
   */
	function importInventoryQuery($apiKey, $userGuid, $params) {

    $request = "http://www.kimonolabs.com/api/{$userGuid}?apikey={$apiKey}";

    if(!empty($params['year'])){
      $request .= "&year=".$params['year'];
    }
    if(!empty($params['make'])){
      $request .= "&make=".$params['make'];
    }
    if(!empty($params['model'])){
      $request .= "&model=".$params['model'];
    }
    if(!empty($params['start'])){
      $request .= "&start=".$params['start'];
    }

    $response = file_get_contents($request);
    return json_decode($response, TRUE);

	}

  /**
   * Add multiple dvs invenotry.
   */
  public function addDvsInventories($inventories= array(), $dvs_id = 0, $yearValue = null)
  {
		if(empty($inventories) || empty($dvs_id)){
			return false;
		}

    $res = 0;

		foreach ($inventories as $item) {
      if(empty($item['name'])) continue;

			if($this->addDvsInventory($item, $dvs_id, $yearValue)){
        $res++;
      }
		}

    return $res;
  }

  /**
   * Add dvs invenotry. if extists - update
   */
  public function addDvsInventory($inventory = array(), $dvs_id = 0, $yearValue = null)
  {
		if(empty($inventory) || empty($inventory['name'])){
			return false;
		}

    if(empty($yearValue)){
    	$invTitleArr = explode(' ', $inventory['name']['text']);
    	$invTitleArrCpy = array_values($invTitleArr);
    	$firstInvItem = array_shift($invTitleArrCpy);
      $yearValue = intval($firstInvItem);
      if(empty($yearValue)){
        $yearValue = date('Y');
      }
    }

		$prev_inventory  = $this->getInventoryItem($inventory, $dvs_id);
		
		$image_dest_dir  = Phpfox::getParam('core.dir_file').'static/inventory/';
		// build server dir structure
		if (!is_dir($image_dest_dir))
		{
			@mkdir($image_dest_dir, 0777);
			@chmod($image_dest_dir, 0777);
		}

		$image_dest_dir .= ($yearValue == null?date('Y'):$yearValue).'/';
		if (!is_dir($image_dest_dir))
		{
			@mkdir($image_dest_dir, 0777);
			@chmod($image_dest_dir, 0777);
		}

    if(!empty($prev_inventory)){ // delete old image
    	if(file_exists(Phpfox::getParam('core.dir_file').$prev_inventory['image'])){
	    	Phpfox::getLib('file')->unlink(Phpfox::getParam('core.dir_file').$prev_inventory['image']);
    	}
    }

		$matches = array();
		preg_match_all('/([a-zA-Z0-9]*)\.(jpg|gif|jpeg|png)$/im', strtolower($inventory['image']['src']), $matches);
		$image_full_name = $matches[0][0];
		$image_name      = $matches[1][0];
		$image_ext       = $matches[2][0];

		$dest_image_name = $image_name.'_'.uniqid().'.'.$image_ext;
		$dest_image      = $image_dest_dir.$dest_image_name;
		
		$uploaded_image_res  = Phpfox::getLib('file')->copy($inventory['image']['src'], $dest_image);
		if(is_array($inventory['price'])){
			$invPriceValues  = array_values($inventory['price']);
			$inventory_price = array_shift($invPriceValues);
		}else{
			$inventory_price = $inventory['price'];
		}

    if(!empty($inventory['name']) && is_string($inventory['name'])){
      $inventory_name = trim(preg_replace('/\s+/', ' ', $inventory['name']));
    }elseif(!empty($inventory['name']['text']) && is_string($inventory['name']['text'])){
      $inventory_name = trim(preg_replace('/\s+/', ' ', $inventory['name']['text']));
    }else{
      $inventory_name = '';
    }
    $inventory_name = str_replace('\n', ' ', $inventory_name);

    if(!empty($inventory['name']['href']) && is_string($inventory['name']['href'])){
      $inventory_href = $inventory['image']['href'];
    }elseif(!empty($inventory['name']['href']) && is_string($inventory['name']['href'])){
      $inventory_href = $inventory['name']['href'];
    }else{
      $inventory_href = '';
    }

    if(!empty($prev_inventory)){
      $res = Phpfox::getLib('database')->update(Phpfox::getT('ko_dvs_inventory'), array(
				'title'         => Phpfox::getLib('database')->escape($inventory_name),
				'image'         => 'static/inventory/'.($yearValue == null?date('Y'):$yearValue).'/'.$dest_image_name,
				'price'         => Phpfox::getLib('database')->escape($inventory_price),
				'color'         => Phpfox::getLib('database')->escape($inventory['color']),
				'link'          => Phpfox::getLib('database')->escape($inventory_href),
				'modified_date' => time(),
      ), "inventory_id = '".$prev_inventory['inventory_id']."'");
    }else{
      $res = Phpfox::getLib('database')->insert(Phpfox::getT('ko_dvs_inventory'), array(
				'dvs_id'        => $dvs_id,
				'title'         => Phpfox::getLib('database')->escape($inventory_name),
				'image'         => 'static/inventory/'.($yearValue == null?date('Y'):$yearValue).'/'.$dest_image_name,
				'price'         => Phpfox::getLib('database')->escape($inventory_price),
				'color'         => Phpfox::getLib('database')->escape($inventory['color']),
				'link'          => Phpfox::getLib('database')->escape($inventory_href),
				'creation_date' => time(),
				'modified_date' => time(),
        )
      );
    }

    return $res;
  }

  /**
   * Get single inventory item
   */
  public function getScheduledInventory()
  {

    $dvsRows = Phpfox::getLib('database')->select('*')
      ->from(Phpfox::getT('ko_dvs'))
      ->where("(inv_display_status = '1') AND (FROM_UNIXTIME(inv_last_cronjob) < (NOW() - INTERVAL inv_schedule_hours HOUR))")
      ->limit(50)
      ->execute('getRows');

    return $dvsRows;

      // 13-05-2014 - 1399939200
      // 16-05-2014 - 1400255613
  }

  /**
   * Get single inventory item
   */
  public function getInventoryItem($inventory = null, $dvs_id = 0)
  {
  	if(empty($inventory) || empty($dvs_id)){
  		return false;
  	}

    if(is_array($inventory['price'])){
      $invPriceValues  = array_values($inventory['price']);
      $inventory_price = array_shift($invPriceValues);
    }else{
      $inventory_price = $inventory['price'];
    }

    if(!empty($inventory['name']) && is_string($inventory['name'])){
      $inventory_name = trim(preg_replace('/\s+/', ' ', $inventory['name']));
    }elseif(!empty($inventory['name']['text']) && is_string($inventory['name']['text'])){
      $inventory_name = trim(preg_replace('/\s+/', ' ', $inventory['name']['text']));
    }else{
      $inventory_name = '';
    }

    if(!empty($inventory['name']['href']) && is_string($inventory['name']['href'])){
      $inventory_href = $inventory['image']['href'];
    }elseif(!empty($inventory['name']['href']) && is_string($inventory['name']['href'])){
      $inventory_href = $inventory['name']['href'];
    }else{
      $inventory_href = '';
    }

    $dvsRow = Phpfox::getLib('database')->select('*')
      ->from(Phpfox::getT('ko_dvs'))
      ->where("(dvs_id = '".Phpfox::getLib('database')->escape($dvs_id)."')")
      ->limit(1)
      ->execute('getRow');

  	$where_arr[] = "(dvs.inv_feed_type = '".Phpfox::getLib('database')->escape($dvsRow['inv_feed_type'])."')";
    $where_arr[] = "(inv.link = '".Phpfox::getLib('database')->escape($inventory_href)."')";
    $extended_search_list = null;
    if($extended_search_list){
    	$where_arr[] = "(inv.title = '".Phpfox::getLib('database')->escape($inventory_name)."')";
    	$where_arr[] = "(inv.color = '".Phpfox::getLib('database')->escape($inventory['color'])."')";
	  	$where_arr[] = "(inv.price = '".Phpfox::getLib('database')->escape($inventory_price)."')";
  	}

    $value = Phpfox::getLib('database')->select('inv.*')
      ->from(Phpfox::getT('ko_dvs_inventory'), 'inv')
			->leftjoin(Phpfox::getT('ko_dvs'), 'dvs', 'inv.dvs_id = dvs.dvs_id')
			->leftjoin(Phpfox::getT('ko_dvs_inventory_connectors'), 'ic', 'ic.connector_id = dvs.inv_feed_type')
      ->where(join(' AND ', $where_arr))
      ->execute('getRow');

    return (!empty($value)?$value:null);
  }

  /**
   * Get video car item
   */
  public function getVideoCar($ko_id = 0)
  {
    $value = Phpfox::getLib('database')->select('*')
      ->from(Phpfox::getT('ko_brightcove'))
      ->where("ko_id = '".Phpfox::getLib('database')->escape($ko_id)."'")
      ->execute('getRow');

    return $value;
  }

  /**
   * Get inventory settings
   */
  public function getSettingValue($name = '')
  {
    $value = Phpfox::getLib('database')->select('value')
      ->from(Phpfox::getT('ko_dvs_inventory_settings'))
      ->where("name = '".Phpfox::getLib('database')->escape($name)."'")
      ->execute('getField');

    return $value;
  }

  /**
   * Get dvs inventory connector
   */
  public function getInventoryConnector($dvs_id = 0)
  {
		$connector = $this->database()
			->select('d.inventory_url, ic.*')
			->from($this->_sTable, 'd')
			->leftjoin(Phpfox::getT('ko_dvs_inventory_connectors'), 'ic', 'ic.connector_id = d.inv_feed_type')
			->where("(d.inv_display_status = '1') AND (d.inv_display_status = '1') AND (d.dvs_id = '".$dvs_id."')")
			->limit(1)
			->execute('getRow');

    return $connector;
  }

	public function getConnectors()
	{
    $values = Phpfox::getLib('database')->select('*')
      ->from(Phpfox::getT('ko_dvs_inventory_connectors'))
      ->order('connector_id DESC')
      ->limit(1000)
      ->execute('getRows');

    return $values;
	}

	public function listDvss($iPage, $iPageSize, $iUserId, $bPaginate = true)
	{
		$iPage = (int) $iPage;
		$iPageSize = (int) $iPageSize;
		$iUserId = (int) $iUserId;

		if ($bPaginate)
		{
			if ($iUserId)
			{
				$this->database()->where('user_id =' . $iUserId);
			}
			$iCnt = $this->database()->select('COUNT(*)')
				->from($this->_sTable)
				->execute('getField');

			$this->database()->limit($iPage, $iPageSize, $iCnt);
		}

		if ($iUserId)
		{
			$this->database()->where('d.user_id =' . $iUserId);
		}

		$aDvss = $this->database()
			->select('d.*, t.text, s.branding_file_id, s.background_file_id, s.menu_background, s.menu_link, s.page_background, s.page_text, s.button_background, s.button_text, s.button_top_gradient, s.button_bottom_gradient, s.button_border, s.text_link, s.footer_link, cc.name as state_string, ' . Phpfox::getUserField())
			->from($this->_sTable, 'd')
			->leftjoin(Phpfox::getT('country_child'), 'cc', 'cc.child_id = d.country_child_id')
			->leftjoin(Phpfox::getT('ko_dvs_text'), 't', 't.dvs_id = d.dvs_id')
			->leftjoin(Phpfox::getT('ko_dvs_style'), 's', 's.dvs_id = d.dvs_id')
			->leftjoin(Phpfox::getT('ko_dvs_branding_files'), 'b', 'b.branding_id = s.branding_file_id')
			->leftjoin(Phpfox::getT('ko_dvs_background_files'), 'bg', 'bg.background_id = s.background_file_id')
			->leftjoin(Phpfox::getT('ko_dvs_players'), 'p', 'p.dvs_id = d.dvs_id')
			//->leftjoin(Phpfox::getT('ko_dvs_logo_files'), 'l', 'l.logo_id = p.logo_file_id')
			->leftjoin(Phpfox::getT('ko_dvs_preroll_files'), 'pr', 'pr.preroll_id = p.preroll_file_id')
			->join(Phpfox::getT('user'), 'u', 'u.user_id = d.user_id')
			->execute('getRows');

		if ($bPaginate)
		{
			return array($aDvss, $iCnt);
		}
		else
		{
			return $aDvss;
		}
	}


	public function getTitleUrl($sDvsName, $iDvsId = 0)
	{
		$sDvsName = preg_replace("/[^A-Za-z0-9 ]/", "", $sDvsName);

		return $this->preParse()->prepareTitle('dvs', $sDvsName, 'title_url', null, $this->_sTable, ($iDvsId ? 'title_url LIKE "%' . $this->preParse()->clean($sDvsName) . '%" AND dvs_id !=' . (int) $iDvsId : null), false, false);
	}


	public function get($mDvs, $bUseTitle = false)
	{
		if (!$mDvs)
		{
			return array();
		}

		if ($bUseTitle)
		{
			$this->database()->where('d.title_url = "' . $this->preParse()->clean($mDvs) . '"');
		}
		else
		{
			$this->database()->where('d.dvs_id = ' . (int) $mDvs);
		}

		$aDvs = $this->database()
			->select('cc.name as state_string, t.*, s.*, b.*, bg.*, p.*, pr.*, ' . Phpfox::getUserField('u', 'dealer_user_') . ', d.*')
			->from($this->_sTable, 'd')
			->leftjoin(Phpfox::getT('country_child'), 'cc', 'cc.child_id = d.country_child_id')
			->leftjoin(Phpfox::getT('ko_dvs_text'), 't', 't.dvs_id = d.dvs_id')
			->leftjoin(Phpfox::getT('ko_dvs_style'), 's', 's.dvs_id = d.dvs_id')
			->leftjoin(Phpfox::getT('ko_dvs_branding_files'), 'b', 'b.branding_id = s.branding_file_id')
			->leftjoin(Phpfox::getT('ko_dvs_background_files'), 'bg', 'bg.background_id = s.background_file_id')
			->leftjoin(Phpfox::getT('ko_dvs_players'), 'p', 'p.dvs_id = d.dvs_id')
			//->leftjoin(Phpfox::getT('ko_dvs_logo_files'), 'l', 'l.logo_id = p.logo_file_id')
			->leftjoin(Phpfox::getT('ko_dvs_preroll_files'), 'pr', 'pr.preroll_id = p.preroll_file_id')
			->join(Phpfox::getT('user'), 'u', 'u.user_id = d.user_id')
			->execute('getRow');

        if(isset($aDvs['font_family_id'])) {
            $aDvs['font_family'] = Phpfox::getService('dvs.style')->getFontFamily($aDvs['font_family_id']);
        }

		return $aDvs;
	}


	public function geoCode($sAddress, $bRecursion = true)
	{
		$sAddress = utf8_encode($sAddress);

		// output
		$aOutput = array();

		$sRequestUrl = "http://maps.googleapis.com/maps/api/geocode/xml?sensor=false" . "&address=" . urlencode($sAddress);

		$oXml = simplexml_load_file($sRequestUrl);

		$sStatusCode = (string) $oXml->status;

		if (strcmp($sStatusCode, "OK") == 0)
		{
			$aOutput['latitude'] = (string) $oXml->result->geometry->location->lat;
			$aOutput['longitude'] = (string) $oXml->result->geometry->location->lng;
		}
		else if (strcmp($sStatusCode, "620") == 0)
		{
			if ($bRecursion === true)
			{
				sleep(1);
				$aOutput = $this->geoCode($sAddress, false);
			}
		}
		else
		{
			// failure to geocode
		}

		if (!empty($aOutput['latitude']) && !empty($aOutput['longitude']))
		{
			$aOutput['success'] = true;
		}
		else
		{
			$aOutput['success'] = false;
		}

		return $aOutput;
	}


	public function makeAddress($iCountryChildId, $sCityLocation, $sZipCode, $sStreetAddress)
	{
		$oCountry = Phpfox::getService('core.country');
		$oParseOutput = Phpfox::getLib('parse.output');
		$aAddress = array();

		if (!empty($sStreetAddress))
		{
			$aAddress['address'] = $oParseOutput->clean($sStreetAddress);
		}

		if (!empty($sCityLocation))
		{
			$aAddress['city'] = $oParseOutput->clean($sCityLocation);
		}

		if (!empty($iCountryChildId) && $iCountryChildId > 0)
		{
			$aAddress['country_child'] = $oCountry->getChild($iCountryChildId);
		}

		if (!empty($sZipCode))
		{
			$aAddress['postal_code'] = $sZipCode;
		}

		return implode(', ', $aAddress);
	}


	public function getCss($aDvs, $bSubdomainMode)
	{
		$sCss = $this->buildCss('body', array(
			'background' => 'none repeat scroll 0 0 #' . $aDvs['page_background'] . ' !important',
			'color' => '#' . $aDvs['page_text']
		));

		$sCss .= $this->buildCss('h1', array(
			'color' => '#' . $aDvs['page_text']
		));
		
		//added 3/21 by Collin
		$sCss .= $this->buildCss('h2', array(
			'color' => '#' . $aDvs['page_text']
		));
		
		$sCss .= $this->buildCss('h3', array(
			'color' => '#' . $aDvs['page_text']
		));
		
		$sCss .= $this->buildCss('.dvs-info', array(
			'color' => '#' . $aDvs['page_text']
		));

		$sCss .= $this->buildCss('#dvs_branding_container h1', array(
			'background' => 'none repeat scroll 0 0 #' . $aDvs['page_background'],
			'color' => '#' . $aDvs['page_text']
			), true);

		if (!$aDvs['background_opacity'])
		{
			$aDvs['background_opacity'] = 1;
		}


		// The following addition can come out when the dvs_background div is removed from the desktop template
		$sCss .= $this->buildCss('#dvs_background', array(
			'background' => '#' . $aDvs['page_background'] . ($aDvs['background_file_name'] ? ' url(' . Phpfox::getLib('url')->makeUrl(($bSubdomainMode ? 'www.' : '') . 'file.dvs.background') . $aDvs['background_file_name'] . ')' : ''),
			'opacity' => $aDvs['background_opacity'],
			'filter' => 'alpha(opacity=' . ($aDvs['background_opacity'] * 100) . ')'
		));

		$sCss .= $this->buildCss('.dvs_background', array(
			'background' => '#' . $aDvs['page_background'] . ' url(' . Phpfox::getLib('url')->makeUrl(($bSubdomainMode ? 'www.' : '') . 'file.dvs.background') . $aDvs['background_file_name'] . ')'
		));

		if ($aDvs['background_file_name'])
		{
			$sCss .= $this->buildCss('.dvs_background_image', array(
				'background' => 'url(' . Phpfox::getLib('url')->makeUrl(($bSubdomainMode ? 'www.' : '') . 'file.dvs.background') . $aDvs['background_file_name'] . ')',
				'opacity' => $aDvs['background_opacity'],
				'filter' => 'alpha(opacity=' . ($aDvs['background_opacity'] * 100) . ')'
			));
		}

		$sCss .= $this->buildCss('#dvs_menu_container', array(
			'background' => 'none repeat scroll 0 0 #' . $aDvs['menu_background']
		));

		$sCss .= $this->buildCss('#dvs_menu_container a', array(
			'color' => '#' . $aDvs['menu_link']
			), true);

		$sCss .= $this->buildCss('#dvs_dealer_info a', array(
			'color' => '#' . $aDvs['text_link']
			), true);

		$sCss .= $this->buildCss('.text_expander_links', array(
			'color' => '#' . $aDvs['text_link']
		));

		$sCss .= $this->buildCss('.text_expander_links:hover', array(
			'color' => '#' . $aDvs['text_link']
		));

		$sCss .= $this->buildCss('#dvs_vehicle_select_container', array(
			'color' => '#' . $aDvs['page_text']
		));

		$sCss .= $this->buildCss('#dvs_video_information a', array(
			'color' => '#' . $aDvs['page_text']
		));

		$sCss .= $this->buildCss('.dvs_c2a_button', array(
			'background-image' => '-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #' . $aDvs['button_top_gradient'] . '), color-stop(1, #' . $aDvs['button_bottom_gradient'] . ') )',
			'background-image' => '-webkit-linear-gradient(top, #' . $aDvs['button_top_gradient'] . ', #' . $aDvs['button_bottom_gradient'] . ')',
			'background' => '-moz-linear-gradient( center top, #' . $aDvs['button_top_gradient'] . ' 5%, #' . $aDvs['button_bottom_gradient'] . ' 100% )',
			'filter' => 'progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#' . $aDvs['button_top_gradient'] . '\', endColorstr=\'#' . $aDvs['button_bottom_gradient'] . '\')',
			'background-color' => '#' . $aDvs['button_top_gradient'] . '',
			'border' => '1px solid #' . $aDvs['button_border'],
			'color' => '#' . $aDvs['button_text']
		));

		$sCss .= $this->buildCss('.dvs_c2a_button:hover', array(
			'background-image' => '-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #' . $aDvs['button_bottom_gradient'] . '), color-stop(1, #' . $aDvs['button_top_gradient'] . ') )',
			'background-image' => '-webkit-linear-gradient(top, #' . $aDvs['button_bottom_gradient'] . ', #' . $aDvs['button_top_gradient'] . ')',
			'background' => '-moz-linear-gradient( center top, #' . $aDvs['button_bottom_gradient'] . ' 5%, #' . $aDvs['button_top_gradient'] . ' 100% )',
			'filter' => 'progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#' . $aDvs['button_bottom_gradient'] . '\', endColorstr=\'#' . $aDvs['button_top_gradient'] . '\')',
			'background-color' => '#' . $aDvs['button_bottom_gradient'] . '',
			'border' => '1px solid #' . $aDvs['button_border'],
			'color' => '#' . $aDvs['button_text']
		));

		$sCss .= $this->buildCss('#dvs_footer_container', array(
			'color' => '#' . $aDvs['footer_link']
		));

		$sCss .= $this->buildCss('.dvs_footer_link', array(
			'color' => '#' . $aDvs['footer_link']
			), false, true);

		$sCss .= $this->buildCss('.dvs_footer_link:hover', array(
			'color' => '#' . $aDvs['footer_link']
		));

		$sCss .= $this->buildCss('.dvs_footer_info a', array(
			'color' => '#' . $aDvs['footer_link']
			), false, true);

		$sCss .= $this->buildCss('.dvs_footer_info a:hover', array(
			'color' => '#' . $aDvs['footer_link']
		));

		//contact-form buttons
		$sCss .= $this->buildCss('.dvs_form_button', array(
			'background-image' => '-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #' . $aDvs['button_top_gradient'] . '), color-stop(1, #' . $aDvs['button_bottom_gradient'] . ') )',
			'background-image' => '-webkit-linear-gradient(top, #' . $aDvs['button_top_gradient'] . ', #' . $aDvs['button_bottom_gradient'] . ')',
			'background' => '-moz-linear-gradient( center top, #' . $aDvs['button_top_gradient'] . ' 5%, #' . $aDvs['button_bottom_gradient'] . ' 100% )',
			'filter' => 'progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#' . $aDvs['button_top_gradient'] . '\', endColorstr=\'#' . $aDvs['button_bottom_gradient'] . '\')',
			'background-color' => '#' . $aDvs['button_top_gradient'] . '',
			'border' => '1px solid #' . $aDvs['button_border'],
			'color' => '#' . $aDvs['button_text']
		));

		$sCss .= $this->buildCss('.dvs_form_button:hover', array(
			'background-image' => '-webkit-gradient( linear, left top, left bottom, color-stop(0.05, #' . $aDvs['button_bottom_gradient'] . '), color-stop(1, #' . $aDvs['button_top_gradient'] . ') )',
			'background-image' => '-webkit-linear-gradient(top, #' . $aDvs['button_bottom_gradient'] . ', #' . $aDvs['button_top_gradient'] . ')',
			'background' => '-moz-linear-gradient( center top, #' . $aDvs['button_bottom_gradient'] . ' 5%, #' . $aDvs['button_top_gradient'] . ' 100% )',
			'filter' => 'progid:DXImageTransform.Microsoft.gradient(startColorstr=\'#' . $aDvs['button_bottom_gradient'] . '\', endColorstr=\'#' . $aDvs['button_top_gradient'] . '\')',
			'background-color' => '#' . $aDvs['button_bottom_gradient'] . '',
			'border' => '1px solid #' . $aDvs['button_border'],
			'color' => '#' . $aDvs['button_text']
		));

		return $sCss;
	}


	public function buildCss($sSelector, $aDeclarations, $bContextual = false, $bEnd = false)
	{
		$sCss = $sSelector . ($bContextual ? '' : ' ') . '{' . "\n";

		foreach ($aDeclarations as $sProperty => $sValue)
		{
			$sCss .= "\t" . $sProperty . ': ' . $sValue . ';' . "\n";
		}

		$sCss .= '}' . ($bEnd ? '' : "\n\n");

		return $sCss;
	}


	public function getCname()
	{
		$aUrl = explode('.', $_SERVER['SERVER_NAME']);

		if ($aUrl[0] == 'www' || $aUrl[0] == 'dvs' || $aUrl[0] == 'idrive')
		{
			return false;
		}

		$aDvs = $this->get($aUrl[0], true);

		return (isset($aDvs['title_url']) ? $aDvs['title_url'] : false);
	}


	public function hasAccess($iId, $iUserId, $sIdSource = '')
	{
		if (Phpfox::isAdmin())
		{
			return true;
		}

		if (!$iId || !$iUserId)
		{
			return false;
		}

		if ($sIdSource == '')
		{
			$aDvs = $this->get($iId, false);

			if ($aDvs['user_id'] == $iUserId)
			{
				return true;
			}
		}

		if ($sIdSource == 'branding')
		{
			$iOwnerId = $this->database()
				->select('user_id')
				->from(Phpfox::getT('ko_dvs_branding_files'))
				->where('branding_id = ' . (int) $iId)
				->execute('getField');

			if ($iOwnerId == $iUserId)
			{
				return true;
			}
		}

		if ($sIdSource == 'background')
		{
			$iOwnerId = $this->database()
				->select('user_id')
				->from(Phpfox::getT('ko_dvs_background_files'))
				->where('background_id = ' . (int) $iId)
				->execute('getField');

			if ($iOwnerId == $iUserId)
			{
				return true;
			}
		}

		if ($sIdSource == 'logo')
		{
			$iOwnerId = $this->database()
				->select('user_id')
				->from(Phpfox::getT('ko_dvs_logo_files'))
				->where('logo_id = ' . (int) $iId)
				->execute('getField');

			if ($iOwnerId == $iUserId)
			{
				return true;
			}
		}

		if ($sIdSource == 'preroll')
		{
			$iOwnerId = $this->database()
				->select('user_id')
				->from(Phpfox::getT('ko_dvs_preroll_files'))
				->where('preroll_id = ' . (int) $iId)
				->execute('getField');

			if ($iOwnerId == $iUserId)
			{
				return true;
			}
		}
		return false;
	}


	public function getBrowser()
	{
		static $sAgent;
		$this->_bIsMobile = false;

		$sAgent = Phpfox::getLib('request')->getServer('HTTP_USER_AGENT');

		if (preg_match("/Firefox\/(.*)/i", $sAgent, $aMatches) && isset($aMatches[1]))
		{
			$sAgent = 'Firefox ' . $aMatches[1];
		}
		elseif (preg_match("/MSIE (.*);/i", $sAgent, $aMatches))
		{
			$aParts = explode(';', $aMatches[1]);
			$sAgent = 'IE ' . $aParts[0];
		}
		elseif (preg_match("/Opera\/(.*)/i", $sAgent, $aMatches))
		{
			$aParts = explode(' ', trim($aMatches[1]));
			$sAgent = 'Opera ' . $aParts[0];
		}
		elseif (preg_match('/\s+?chrome\/([0-9.]{1,10})/i', $sAgent, $aMatches))
		{
			$aParts = explode(' ', trim($aMatches[1]));
			$sAgent = 'Chrome ' . $aParts[0];
		}
		elseif (preg_match('/android/i', $sAgent))
		{
			$this->_bIsMobile = true;
			$sAgent = 'Android';
		}
		elseif (preg_match('/opera mini/i', $sAgent))
		{
			$this->_bIsMobile = true;
			$sAgent = 'Opera Mini';
		}
		elseif (preg_match('/(pre\/|palm os|palm|hiptop|avantgo|fennec|plucker|xiino|blazer|elaine)/i', $sAgent))
		{
			$this->_bIsMobile = true;
			$sAgent = 'Palm';
		}
		elseif (preg_match('/blackberry/i', $sAgent))
		{
			$this->_bIsMobile = true;
			$sAgent = 'Blackberry';
		}
		elseif (preg_match('/(iris|3g_t|windows ce|opera mobi|windows ce; smartphone;|windows ce; iemobile|windows phone)/i', $sAgent))
		{
			$this->_bIsMobile = true;
			$sAgent = 'Windows Smartphone';
		}
		elseif (preg_match("/Version\/(.*) Safari\/(.*)/i", $sAgent, $aMatches) && isset($aMatches[1]))
		{
			if (preg_match("/iPhone/i", $sAgent) || preg_match("/ipod/i", $sAgent))
			{
				$aParts = explode(' ', trim($aMatches[1]));
				$sAgent = 'Safari iPhone ' . $aParts[0];
				$this->_bIsMobile = true;
			}
			else if (preg_match("/ipad/i", $sAgent))
			{
				$aParts = explode(' ', trim($aMatches[1]));
				$sAgent = 'ipad';
				$this->_bIsMobile = true;
			}
			else
			{
				$sAgent = 'Safari ' . $aMatches[1];
			}
		}
		//custom ipad detection
		elseif (preg_match('/crios/i', $sAgent)) //detects Chrome browser for iOS
		{
			$this->_bIsMobile = false;
			$sAgent = 'ipad';
		}
		elseif (preg_match('/(mini 9.5|vx1000|lge |m800|e860|u940|ux840|compal|wireless| mobi|ahong|lg380|lgku|lgu900|lg210|lg47|lg920|lg840|lg370|sam-r|mg50|s55|g83|t66|vx400|mk99|d615|d763|el370|sl900|mp500|samu3|samu4|vx10|xda_|samu5|samu6|samu7|samu9|a615|b832|m881|s920|n210|s700|c-810|_h797|mob-x|sk16d|848b|mowser|s580|r800|471x|v120|rim8|c500foma:|160x|x160|480x|x640|t503|w839|i250|sprint|w398samr810|m5252|c7100|mt126|x225|s5330|s820|htil-g1|fly v71|s302|-x113|novarra|k610i|-three|8325rc|8352rc|sanyo|vx54|c888|nx250|n120|mtk |c5588|s710|t880|c5005|i;458x|p404i|s210|c5100|teleca|s940|c500|s590|foma|samsu|vx8|vx9|a1000|_mms|myx|a700|gu1100|bc831|e300|ems100|me701|me702m-three|sd588|s800|8325rc|ac831|mw200|brew |d88|htc\/|htc_touch|355x|m50|km100|d736|p-9521|telco|sl74|ktouch|m4u\/|me702|8325rc|kddi|phone|lg |sonyericsson|samsung|240x|x320vx10|nokia|sony cmd|motorola|up.browser|up.link|mmp|symbian|smartphone|midp|wap|vodafone|o2|pocket|kindle|mobile|psp|treo)/i', $sAgent))
		{
			$this->_bIsMobile = true;
		}

		if ($sAgent == 'ipad')
		{
			return 'ipad';
		}
		else
		{
			return ($this->_bIsMobile ? 'mobile' : 'desktop');
		}
	}

}

?>