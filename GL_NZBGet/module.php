<?
    // Klassendefinition
    class NZBGet extends IPSModule {       
        public $xmlrpc_client;
        
        public function __construct($InstanceID) {
            parent::__construct($InstanceID);
        
            include 'xmlrpc.inc';
            //surpress all warnings as class properties are available after Create method first     
            @$this->xmlrpc_client = new xmlrpc_client(@$this->ReadPropertyString("NzbUserName").':'.@$this->ReadPropertyString("NzbPassword").'/xmlrpc', @$this->ReadPropertyString("NzbServerIpAddress"), @$this->ReadPropertyInteger("NzbPort"));
        }
        
        public function Create() {        
            parent::Create();
        
            // Modul-Eigenschaftserstellung
            $this->RegisterPropertyString("NzbServerIpAddress", "192.168.0.250");
            $this->RegisterPropertyInteger("NzbPort", 6789);
            $this->RegisterPropertyString("NzbUserName", "apiUser");
            $this->RegisterPropertyString("NzbPassword", "");
            $this->RegisterPropertyString("NzbIdentPrefix", "NzbGet");
            
            $this->RegisterPropertyBoolean("NzbStatusUpdateEnabled", true);
            $this->RegisterPropertyInteger("NzbStatusUpdateInterval", 10);
            
            $this->RegisterPropertyBoolean("NzbLogImportEnabled", true);  
            $this->RegisterPropertyInteger("NzbLogImportInterval", 60);
            $this->RegisterPropertyInteger("NzbLogImportAmount", 20);
            $this->RegisterPropertyInteger("NzbDownloadImportInterval", 60);
            $this->RegisterPropertyString("NzbDownloadFields", '"NZBName", "Category", "FileSizeMB", "RemainingSizeMB", "Status"');          
        }
        
        public function ApplyChanges() {
            parent::ApplyChanges();
            
            if($this->IsConfigFilled())
            {
                if(is_string(@$this->GetVersion()))
                {
                    $this->SetStatus(102);
                    // Timer for LogImport
                    $this->ManageCyclicEvent($this->ReadPropertyString("NzbIdentPrefix")."LogImportTimer", $this->ReadPropertyInteger("NzbLogImportInterval"), 'GL_ImportLogs($id)', $this->ReadPropertyBoolean("NzbLogImportEnabled"));
                    // Timer for Status Import
                    $this->ManageCyclicEvent($this->ReadPropertyString("NzbIdentPrefix")."StatusImportTimer", $this->ReadPropertyInteger("NzbStatusUpdateInterval"), 'GL_ImportStatus($id)', $this->ReadPropertyBoolean("NzbLogImportEnabled"));
                    // Timer for Status Import
                    $this->ManageCyclicEvent($this->ReadPropertyString("NzbIdentPrefix")."NzbDownloadTimer", $this->ReadPropertyInteger("NzbDownloadImportInterval"), 'GL_ImportDownloads($id)', true);             
                }else{
                    $this->SetStatus(201);
                }
            }
        }
        
        public function IsConfigFilled()
        {
            $propertyArray = array();
            array_push($propertyArray, $this->ReadPropertyString("NzbServerIpAddress"));
            array_push($propertyArray, $this->ReadPropertyInteger("NzbPort"));
            array_push($propertyArray, $this->ReadPropertyString("NzbUserName"));
            array_push($propertyArray, $this->ReadPropertyString("NzbPassword"));
            
            if(in_array("", $propertyArray))
                return false;
            else 
                return true;
        }
        
        public function GetXmlRpcClient()
        {
            return $this->xmlrpc_client;
        }
        
        protected function CreateAndUpdateVariable($variableType, $variableName, $value, $parent, $ident)
        {
            $existingVariableId = @IPS_GetObjectIDByIdent($ident, $parent);         
            if(!$existingVariableId)
            {
                $newVariableId = IPS_CreateVariable($variableType);
                IPS_SetName($newVariableId, $variableName);
                SetValue($newVariableId, $value);
                IPS_SetParent($newVariableId, $parent);
                IPS_SetIdent($newVariableId, $ident);
                return $newVariableId;
            }
            else 
            {
                SetValue($existingVariableId, $value);
                return $existingVariableId;
            }
        }
        
        protected function VariableExists($variableName)
        {
            try{
                $result = @IPS_GetObjectIDByIdent($this->ReadPropertyString("NzbIdentPrefix").$variableName, $this->InstanceID);
                if(!is_int($result))
                    throw new Exception();
                else
                    return TRUE;
            }
            catch(Exception $e){
                return FALSE;
            }
        }
        
        protected function CreateCategoryIfNotAvailable($categoryName, $parent, $ident)
        {
            if(!@IPS_GetObjectIDByIdent($ident, $parent))
            {
                $catId = IPS_CreateCategory();
                IPS_SetName($catId, $categoryName);
                IPS_SetParent($catId, $parent);
                IPS_SetIdent($catId, $ident);
                return $catId;
            }else return IPS_GetObjectIDByIdent($ident, $parent);
        }
        
        private function TraverseArray($array, $area = NULL)
        {
            foreach($array as $key=>$value)
            {
                if($key=="0")
                    $key=$area;
                
                $targetCatId = $this->CreateCategoryIfNotAvailable(ucfirst($area), $this->InstanceID, $this->ReadPropertyString("NzbIdentPrefix").$area);
        
                if (!is_array($value))
                    $this->CreateAndUpdateVariable($this->GetIPSDataTypeCode(gettype($value)), $key, $value, $targetCatId, $this->ReadPropertyString("NzbIdentPrefix").$key);

                // $key acts as instance name and is representing the nested array name
                else $this->TraverseArray($value, $key);
            }
        }
        
        private function ArrayToHtmlTable($array, $indexedArrayColumnScope = NULL)
        {
            //TODO: Refacotring
            if(is_array($array) && count($array)>0)
            {
                if($indexedArrayColumnScope != null)
                {
                    $scope = array_fill_keys($indexedArrayColumnScope, "");
                    //compare specified column scope  with given array
                    $columns = array_intersect_key($scope,$array[0]);
                }
                else $columns = $array[0];

                    $tableHtml = "<table>";
                    $tableHtml .= "<tr><th>";
                    $tableHtml .= implode("</th><th>", array_keys($columns)); //Create columns in same order as provided in $indexedArrayColumnScope
                    $tableHtml .= "</th><tr>";    
                        
                        foreach($array as $key=>$value)
                        {
                                    $tableHtml .= "<tr>";   
                                    foreach ($columns as $columnKey => $columnValue)
                                    {
                                        foreach ($value as $nestedKey => $nestedValue)
                                        {
                                            if($columnKey == $nestedKey)
                                            {
                                                if($nestedKey=="Time")
                                                    $nestedValue = date("d-m-Y H:i:s",$nestedValue);
                                                $tableHtml .= "<td>".$nestedValue."</td>";
                                             }
                                        }
                                    }
                                    $tableHtml .= "</tr>";        
                        }
                        $tableHtml .= "</table>";
                return  $tableHtml;
                }
                else return  "Specified parameter is not an array";  
        }
        
        //Credits to traxanos
        protected function RegisterTimer($ident, $interval, $script) {
            $id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);
        
            if ($id && IPS_GetEvent($id)['EventType'] <> 1) {
                IPS_DeleteEvent($id);
                $id = 0;
            }
        
            if (!$id) {
                $id = IPS_CreateEvent(1);
                IPS_SetParent($id, $this->InstanceID);
                IPS_SetIdent($id, $ident);
            }
        
            IPS_SetName($id, $ident);
            IPS_SetHidden($id, true);
            IPS_SetEventScript($id, "\$id = \$_IPS['TARGET'];\n$script;");
        
            if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type");
        
            if (!($interval > 0)) {
                IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);
                IPS_SetEventActive($id, false);
            } else {
                IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval);
                IPS_SetEventActive($id, true);
            }
        }
                
        // Method for deleting a timer; Activating and deactivating is done in RegisterTimer
        protected function ManageCyclicEvent($ident, $interval, $script, $toggle)
        {
            $targetVariableId = @$this->GetIDForIdent($ident);
            if(!$toggle && $targetVariableId)
            {
                $deleteResult = IPS_DeleteEvent($targetVariableId);
                if($deleteResult)
                echo "Timer has been deleted. Ident/VariableId was: ".$ident."/".$targetVariableId;
                else echo "Could not delete Timer. Ident/VariableId was: ".$ident."/".$targetVariableId;
            }
            elseif ($toggle && !$targetVariableId)
            {
                $this->RegisterTimer($ident, $interval, $script);
            }elseif (!$toggle && !$targetVariableId)
                IPS_LogMessage("NzbGetModule", "Boolean was set to false and Timer Event did not exist yet.");
            else IPS_LogMessage("NzbGetModule", "Timer already exists ID/Ident: ".$targetVariableId." / ".$ident);
        }
             

        private function GetRpcResponseValue($rpcOperation, $rpcParameters = NULL)
        {
            $xmlrpc_message = new xmlrpcmsg($rpcOperation);
            if(is_array($rpcParameters))
            {
                foreach ($rpcParameters as $rpcParameterValue)
                    $xmlrpc_message->addParam(new xmlrpcval($rpcParameterValue, $this->GetDataTypeForXMLRPC($rpcParameterValue)));
            }else
                $xmlrpc_message->addParam(new xmlrpcval($rpcParameters, $this->GetDataTypeForXMLRPC($rpcParameters)));
        
            $xmlrpc_response = $this->xmlrpc_client->send($xmlrpc_message);
            return $xmlrpc_response->value();
        
        }
        
        private function GetDataTypeForXMLRPC($value)
        {
            switch(gettype($value)){
                case 'integer':
                    return 'int';
                    break;
                default:
                    return gettype($value);
            }
        }
        
        protected function GetIPSDataTypeCode($dataType)
        {
            switch($dataType)
            {
                case "string";
                default:
                    return 3;
                    break;
                case "double": //float
                    return 2;
                    break;
                case "integer":
                    return 1;
                    break;
                case "boolean":
                    return 0;
                    break;
            }
        }
        
        //
        // Methods for managing NZBGet, i.e. sending data to NZBGet
        //
        
        public function Shutdown()
        {
            $response = $this->GetRpcResponseValue('shutdown');
            return php_xmlrpc_decode($response);
        }
        
        public function Reload()
        {
            $response = $this->GetRpcResponseValue('reload');
            return php_xmlrpc_decode($response);
        }
        
        public function SetDownloadSpeedLimit($speedLimitInKbs)
        {
            $response = $this->GetRpcResponseValue('rate', $speedLimitInKbs);
            echo php_xmlrpc_decode($response);
            return php_xmlrpc_decode($response);
        }
        
        public function AddNzbToQueue($nzbFileNameDestDir, $nzbContentOrUri, $category, $priority, $dupeKey, $dupeScore, $dupeMode, $postProcessingArray, $addToTop = false, $addPaused = FALSE)
        {
            //possible priorities are: -100 (very low), -50 (low), 0 (normal), 50 (high), 100 (very high), 900 (force).
            // $addToTop = false means nzb will be added to the end of queue
            $parameters = array($nzbFileNameDestDir, $nzbContentOrUri, $category, $priority, $addToTop, $addPaused, $dupeKey, $dupeScore, $dupeMode, $postProcessingArray);
        
            $response = $this->GetRpcResponseValue('append', $parameters);
            return php_xmlrpc_decode($response);
        }
        
        public function PauseDownloadQueue()
        {
            $response = $this->GetRpcResponseValue('pausedownload');
            return php_xmlrpc_decode($response);
        }
        
        public function ResumeDownloadQueue()
        {
            $response = $this->GetRpcResponseValue('resumedownload');
            return php_xmlrpc_decode($response);
        }
        
        public function PausePostProcessing()
        {
            $response = $this->GetRpcResponseValue('pausepost');
            return php_xmlrpc_decode($response);
        }
        
        public function ResumePostProcessing()
        {
            $response = $this->GetRpcResponseValue('resumepost');
            return php_xmlrpc_decode($response);
        }
        
        public function ScanNzbDir()
        {
            $response = $this->GetRpcResponseValue('scan');
            return php_xmlrpc_decode($response);
        }
        
        public function PauseNzbDirScan()
        {
            $response = $this->GetRpcResponseValue('pausescan');
            return php_xmlrpc_decode($response);
        }
        
        public function ResumeNzbDirScan()
        {
            $response = $this->GetRpcResponseValue('resumescan');
            return php_xmlrpc_decode($response);
        }
        
        public function ScheduleResumeOfAllActivities($afterSeconds)
        {
            $response = $this->GetRpcResponseValue('scheduleresume', $afterSeconds);
            return php_xmlrpc_decode($response);
        }
        
        public function WriteNzbGetLog($kind, $logText)
        {
        
            // NZBGet requires uppercase log kind strings
            $availableLogKinds =  array('INFO', 'WARNING', 'ERROR', 'DETAIL', 'DEBUG');
            $kindUpper = strtoupper($kind);
        
            if(in_array($kindUpper, $availableLogKinds))
            {
                $parameters = array($kindUpper, $logText);
                $response = $this->GetRpcResponseValue('writelog', $parameters);
                return php_xmlrpc_decode($response);
            }
            else
                return $kind." is not a valid log kind for NZBGet. Please use ".implode(',', $availableLogKinds);
        }
        
        public function WriteNzbGetLog_INFO($logText)
        {
            return $this->WriteNzbGetLog('INFO', $logText);
        }
        
        public function WriteNzbGetLog_WARNING($logText)
        {
            return $this->WriteNzbGetLog('WARNING', $logText);
        }
        
        public function WriteNzbGetLog_ERROR($logText)
        {
            return $this->WriteNzbGetLog('ERROR', $logText);
        }
        
        public function WriteNzbGetLog_DETAIL($logText)
        {
            return $this->WriteNzbGetLog('DETAIL', $logText);
        }
        
        public function WriteNzbGetLog_DEBUG($logText)
        {
            return $this->WriteNzbGetLog('DEBUG', $logText);
        }
        
        public function ResetNewsServerStatistics($serverId, $counter)
        {
            $parameters = array($serverId, $counter);
            $response = $this->GetRpcResponseValue('resetservervolume', $parameters);
            return php_xmlrpc_decode($response);
        }
        
        public function SaveConfigFile($arrayStruct)
        {
            $response = $this->GetRpcResponseValue('saveconfig');
            return php_xmlrpc_decode($response);
        }
        
        //
        // Methods for getting data from NZBGet and writing to Symcon
        //
        
        public function GetHistory($showHiddenRecords)
        {
            $response = $this->GetRpcResponseValue('history', $showHiddenRecords);
            return php_xmlrpc_decode($response);
        }
        
        public function ImportHistory()
        {
            return 'Import not implemented. Encoded value return type in GetHistory($showHiddenRecords)';
        }
        
        public function GetDownloads()
        {
            $response = $this->GetRpcResponseValue('listgroups');
            return php_xmlrpc_decode($response); 
        }
        
        public function ImportDownloads()
        {
            $downloads      = $this->GetDownloads();
            $tablehtml = $this->ArrayToHtmlTable($downloads, str_getcsv($this->ReadPropertyString("NzbDownloadFields")));
            $downloadsHtmlBoxId = $this->RegisterVariableString($this->ReadPropertyString("NzbIdentPrefix")."ImportedDownloads", "NZBGetDownloads", "~HTMLBox");
            SetValueString($downloadsHtmlBoxId, $tablehtml);
        }
        
        public function GetFiles($nzbid)
        {
            $response = $this->GetRpcResponseValue('listfiles', $nzbid);
            return php_xmlrpc_decode($response);
        }
        
        public function GetLogs($idFrom, $numberOfEntries)
        {
            $parameters = array($idFrom, $numberOfEntries);
            $response = $this->GetRpcResponseValue('log', $parameters);
            return php_xmlrpc_decode($response);
        }
        
        public function ImportLogs()
        {
            //TODO: Handle if Amount of Logs is 0
            $logs      = $this->GetLogs(0, $this->ReadPropertyInteger("NzbLogImportAmount"));
            $tablehtml = $this->ArrayToHtmlTable($logs);
            $logsHtmlBoxId = $this->RegisterVariableString($this->ReadPropertyString("NzbIdentPrefix")."ImportedLogs", "NZBGetLogs", "~HTMLBox");
            SetValueString($logsHtmlBoxId, $tablehtml);
        }
        
        public function GetStatus()
        {
            $response = $this->GetRpcResponseValue('status');
            return php_xmlrpc_decode($response);
        }
        
        public function ImportStatus()
        {
            $this->TraverseArray($this->GetStatus(), 'status');
        }
        
        public function GetVersion()
        {
            $response = $this->GetRpcResponseValue('version');
            return php_xmlrpc_decode($response);
        }
        
        public function ImportVersionInfo() {
            if(!$this->VariableExists("Version"))
                $createdVariableId = $this->RegisterVariableString($this->ReadPropertyString("NzbIdentPrefix")."Version", "Version");
        
            SetValueString(IPS_GetObjectIDByIdent($this->ReadPropertyString("NzbIdentPrefix")."Version", $this->InstanceID), $this->GetVersion());
        }
        
        public function GetLoadedConfig()
        {
            //TODO: Re-consider parsing of struct
            $response = $this->GetRpcResponseValue('config');
            $decodedStatus = php_xmlrpc_decode($response);
        
            $this->TraverseArray($decodedStatus, 'config');
        }
        
        public function GetConfigFile()
        {
            $response = $this->GetRpcResponseValue('loadconfig');
            return php_xmlrpc_decode($response);
        }
        
        public function GetConfigTemplates($fromDisk)
        {
            $response = $this->GetRpcResponseValue('configtemplates', $fromDisk);
            return php_xmlrpc_decode($response);
        }

        //
        // Dedicated Actions (TestMethods)
        // 
        
        public function CheckInstanceProperties()
        {
            IPS_LogMessage("NzbGetModule", $this->VariableExists("Version"));
        }
        
        public function GetModuleStatus()
        {
            //return $this->GetStatus();
        }
        
        public function ToggleLogImportTimer()
        {
            $this->ManageCyclicEvent($this->ReadPropertyString("NzbIdentPrefix")."LogImportTimer", $this->ReadPropertyInteger("NzbLogImportInterval"), 'GL_ImportLogs($id)', $this->ReadPropertyBoolean("NzbLogImportEnabled"));
        }    
    }
?>