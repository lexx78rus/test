<?php

class Dvs_Service_Inventory_Inventory extends Phpfox_Service {
    private $_sHost = 'sftp.dmotorworks.com';
    private $_sPort = '22';
    private $_sUsername = 'WTVMain';
    private $_sPassword = '$new123';

    function __construct() {
        $this->_sTable = Phpfox::getT('tbd_dvs_inventory');
        Phpfox::getLib('setting')->setParam('dvs.csv_folder', PHPFOX_DIR . 'file' . PHPFOX_DS . 'inventory' . PHPFOX_DS);
    }

    public function importFile() {
        $this->database()->update($this->_sTable, array('is_updated' => 0, 'total' => 0, 'is_video_updated' => 0), '1');
        $this->database()->update($this->_sTable, array('ed_style_id' => 0), 'ed_style_id < 10');

        $sFileName = Phpfox::getParam('dvs.csv_folder') . 'inventory.csv';
        $sDelimiter = ',';

        if (file_exists($sFileName) && is_readable($sFileName)) {
            $aHeader = null;
            if (($oOpenFileHandle = fopen($sFileName, 'r')) !== false) {
                while (($aRow = fgetcsv($oOpenFileHandle, 15000, $sDelimiter)) !== FALSE) {
                    if(!$aHeader) {
                        $aHeader = $aRow;
                    } else {
                        $aData = array_combine($aHeader, $aRow);
                        if (is_array($aRow)) {
                            $this->importRow($aData);
                        }
                    }
                }
                fclose($oOpenFileHandle);
            }
        }

        $this->database()->delete($this->_sTable, 'is_updated = 0');
        return true;
    }

    public function importRow($aData) {
        $aVals = array(
            'dvs_id' => $aData['DEALER_ID'],
            'vin_id' => $aData['VIN'],
            'squish_vin_id' => $this->getSquishVinCode($aData['VIN']),
            'make' => $aData['MAKE'],
            'model' => $aData['MODEL'],
            'year' => $aData['MODEL_YEAR'],
            'ed_style_id' => 0,
            'is_updated' => 1,
            'total' => 1
        );

        if(!$aData['DEALER_ID']) {
            return false;
        }

        $aRow = $this->database()
            ->select('inventory_id, total')
            ->from($this->_sTable)
            ->where('vin_id = \'' . $aVals['vin_id'] . '\' AND dvs_id = \'' . $aVals['dvs_id'] . '\'')
            ->execute('getRow');

        if($aRow) {
            $this->database()->update($this->_sTable, array(
                    'is_updated' => 1,
                    'total' => (int)$aRow['total'] + 1
                ), 'inventory_id = ' . (int)$aRow['inventory_id']);
            return $aRow['inventory_id'];
        }

        $iId = $this->database()->insert($this->_sTable, $aVals);
        return $iId;
    }

    public function updateEdStyleId($iLimit = 20) {
        $aRows = $this->database()
            ->select('i.inventory_id, i.squish_vin_id, i.ed_style_id, vin.ed_style_id AS ed_style_id_parsed')
            ->from($this->_sTable, 'i')
            ->leftJoin(Phpfox::getT('ko_dvs_vin_parsed'), 'vin', 'i.squish_vin_id = vin.squish_vin_id')
            ->where('i.ed_style_id < 5')
            ->group('i.squish_vin_id')
            ->limit($iLimit)
            ->execute('getRows');

        foreach($aRows as $aRow) {
            if($aRow['ed_style_id_parsed'] > 0) {
                $this->database()->update($this->_sTable, array('ed_style_id' => $aRow['ed_style_id_parsed']), 'squish_vin_id = \'' . $aRow['squish_vin_id'] . '\'');
            } else {
                list($aStyles, $aParams) = $this->getStyleByVin($aRow['squish_vin_id']);
                if(isset($aStyles[0]['id'])) {
                    $this->database()->insert(Phpfox::getT('ko_dvs_vin_parsed'), array(
                        'squish_vin_id' => $aRow['squish_vin_id'],
                        'ed_style_id' => $aStyles[0]['id']
                    ));
                    $this->database()->update($this->_sTable, array('ed_style_id' => $aStyles[0]['id']), 'squish_vin_id = \'' . $aRow['squish_vin_id'] . '\'');
                } else {
                    // MARK THIS INVENTORY
                    $this->database()->update($this->_sTable, array('ed_style_id' => (int)$aRow['ed_style_id'] + 1), 'squish_vin_id = \'' . $aRow['squish_vin_id'] . '\'');
                }
            }
        }

        return $aRows;
    }

    public function updateReferenceId($iLimit = 20) {
        $aRows = $this->database()
            ->select('i.inventory_id, i.squish_vin_id, i.ed_style_id, i.referenceId')
            ->from($this->_sTable, 'i')
            ->where('i.ed_style_id > 5 AND i.referenceId IS NULL AND i.is_video_updated = 0')
            ->group('i.ed_style_id')
            ->limit($iLimit)
            ->execute('getRows');

        $aEdStyleIds = array();
        foreach($aRows as $aRow) {
            $aEdStyleIds[] = $aRow['ed_style_id'];
        }

        if(!count($aEdStyleIds)) {
            return false;
        }

        list($aData, $aReferenceIds) = $this->getAllVideoIdByEdStyles($aEdStyleIds);
        foreach($aEdStyleIds as $iEdStyleId) {
            if(isset($aData[$iEdStyleId])) {
                $this->database()->update($this->_sTable, array('referenceId' => $aData[$iEdStyleId]['videoId']), 'ed_style_id = ' . (int)$iEdStyleId);
            } else {
                $this->database()->update($this->_sTable, array('is_video_updated' => 1), 'ed_style_id = ' . (int)$iEdStyleId);
            }
        }

        return true;
    }

    public function getPending($sType = 'style') {
        if($sType == 'style') {
            $sWhere = 'ed_style_id < 5';
        } else {
            $sWhere = 'ed_style_id > 5 AND referenceId IS NULL AND is_video_updated = 0';
        }

        $iTotal = $this->database()
            ->select('COUNT(*)')
            ->from($this->_sTable)
            ->where($sWhere)
            ->execute('getField');
        return $iTotal;
    }

    public function getSquishVinCode($sVin) {
        if(strlen($sVin) > 10) {
            $sQuishVin = substr($sVin, 0, 8) . substr($sVin, 9, 2);
            return $sQuishVin;
        }
        return false;
    }

    public function getAllVideoIdByEdStyles($aEdStyleIds) {
        $sEdStyleIds = implode('/', $aEdStyleIds) . '/';
        $sTargetUrl = 'http://api.wheelstv.co/v1/edstyleid/' . $sEdStyleIds;
        $ch = curl_init($sTargetUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $oResponse = curl_exec($ch);
        $oOutput= @json_decode($oResponse);

        $aData = array();
        $aReferenceIds = array();
        foreach($oOutput->items as $aItem) {
            $aData[$aItem->edStyleId] = array(
                'videoId' => $aItem->videoId,
                'wtvId' => $aItem->wtvId
            );
            $aReferenceIds[] = $aItem->videoId;
        }
        return array($aData, $aReferenceIds);
    }

    public function getStyleByVin($sVin) {
        $aParams = array();
        $aStyles = array();
        $sApiKey = 'wztmmwrvnegb6b547asz8u2a';

        $sTargetUrl = "https://api.edmunds.com/api/vehicle/v2/squishvins/" . trim($sVin) . "/?fmt=json&api_key=" . $sApiKey;
        $ch = curl_init($sTargetUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        $oResponse = curl_exec($ch);

        $oOutput= @json_decode($oResponse);

        if ($oOutput === null || !isset($oOutput->make)) {
            return array($aStyles, $aParams);
        }

        if(isset($oOutput->make)) {
            $aParams['make'] = $oOutput->make->name;
        }

        if(isset($oOutput->make)) {
            $aParams['model'] = $oOutput->model->name;
        }

        $aParams['year'] = array();
        if(isset($oOutput->years)) {
            foreach($oOutput->years as $oYear) {
                $aParams['year'][] = $oYear->year;
            }
        }

        if(isset($oOutput->categories->vehicleStyle)) {
            $aParams['bodyStyle'] = $oOutput->categories->vehicleStyle;
        }

        $aYears = $this->objectToArray($oOutput->years);

        if (isset($aYears[0]['styles'])) {
            $aStyles = $aYears[0]['styles'];
        } else {
            return array($aStyles, $aParams);
        }

        return array($aStyles, $aParams);
    }

    function objectToArray( $object ) {
        if( !is_object( $object ) && !is_array( $object ) ) {
            return $object;
        }
        if( is_object( $object ) ) {
            $object = (array) $object;
        }

        foreach($object as $iKey => $aObject) {
            $object[$iKey] = $this->objectToArray($aObject);
        }

        return $object;
    }

    public function downloadZipFile() {
        if (!function_exists("ssh2_connect")) {
            return false;
            die('Function ssh2_connect not found, you cannot use ssh2 here');
        }

        if (!$oConnection = ssh2_connect($this->_sHost, $this->_sPort)) {
            return false;
            die('Unable to connect');
        }


        if (!ssh2_auth_password($oConnection, $this->_sUsername, $this->_sPassword)) {
            return false;
            die('Unable to authenticate.');
        }


        if (!$oStream = ssh2_sftp($oConnection)) {
            return false;
            die('Unable to create a stream.');
        }


        if (!$oDir = opendir("ssh2.sftp://{$oStream}/./")) {
            return false;
            die('Could not open the directory');
        }


        $sFile = '';
        while (false !== ($sTempFile = readdir($oDir))) {
            if ($sTempFile == "." || $sTempFile == "..")
                continue;
            if(strpos($sTempFile, 'VINVENTORY') === 0) {
                $sFile = $sTempFile;
                break;
            }
        }

        if(!$sFile) {
            return false;
        }

        if (!$fRemote = @fopen("ssh2.sftp://{$oStream}/{$sFile}", 'r')) {
            return false;
        }

        if (!$fLocal = @fopen(Phpfox::getParam('dvs.csv_folder') . $sFile, 'w')) {
            return false;
        }

        $iRead = 0;
        $iFilesize = filesize("ssh2.sftp://{$oStream}/{$sFile}");
        while ($iRead < $iFilesize && ($iBuffer = fread($fRemote, $iFilesize - $iRead))) {
            $iRead += strlen($iBuffer);
            if (fwrite($fLocal, $iBuffer) === FALSE) {
                echo "Unable to write to local file: $sFile\n";
                return false;
            }
        }
        fclose($fLocal);
        fclose($fRemote);

        return $sFile;
    }

    public function extracFile($sFile) {
        $oZip = new ZipArchive;
        $oRes = $oZip->open(Phpfox::getParam('dvs.csv_folder') . $sFile);
        if ($oRes === TRUE) {
            $oZip->extractTo(Phpfox::getParam('dvs.csv_folder'));
            $oZip->close();

            if (!rename(Phpfox::getParam('dvs.csv_folder') . str_replace('.zip', '.txt', $sFile), Phpfox::getParam('dvs.csv_folder') . 'inventory.csv')) {
                return false;
            }
            @unlink(Phpfox::getParam('dvs.csv_folder') . $sFile);
            return true;
        } else {
            return false;
        }
    }

    public function runCronjob() {
        //if($sFile = $this->downloadZipFile()) {
            //if ($this->extracFile($sFile)) {
                $this->importFile();
            //}
            return true;
        //}
        return false;
    }
}

?>