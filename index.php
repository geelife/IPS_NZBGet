<?php


class IPS_NZBGet{
    
### Do not change
### XMLRPC lib required for easy remote procedure calls

### To be changed
### Environment specific variables
public $nzbGetIp           = '192.168.0.250';
public $nzbGetPort         = 6789;
public $nzbGetUserName     = 'michael';
public $nzbGetUserPassword = 'ElaMi';
public $rpcDebug           = 0;
public $xmlrpc_client;

//TODO: instantiate the rpc client in the constructor only once the IPS_NZBGet is instantiated.
function __construct() {
    include 'lib/xmlrpc.inc';    
    $this->xmlrpc_client = new xmlrpc_client($this->nzbGetUserName.':'.$this->nzbGetUserPassword.'/xmlrpc', $this->nzbGetIp, $this->nzbGetPort);
    $this->xmlrpc_client->setDebug($this->rpcDebug);
}


### Available API calls from NZBGet
### https://github.com/nzbget/nzbget/wiki/API
## Program control
#version                             - implemented
#shutdown                            - implemented
#reload                              - implemented (e.g. after changing settings)

## Queue and history
#listgroups                          - tbd (test while downloading)
#listfiles                           - tbd (test while downloading)
#history                             - implemented
#append                              - implemented (Post Processing Parameters to be checked)
#editqueue                           - tbd
#scan                                - implemented

## Status, logging and statistics
#status                             - implemented
#log                                - implemented
#writelog                           - implemented (e.g. after IPS server executed post scripts such as file move or if added NZBs to scan folder)
#loadlog                            - implemented (not tested)
#servervolumes                      - tbd (not documented enough in API documentation yet)
#resetservervolume                  - implemented (not tested)

## Pause and speed limit
#rate                               - implemented (set speed limit in kb/s (int): e.g. if you leave your home set it to 0(unlimited) and limit it if you are at home)
#pausedownload                      - implemented
#resumedownload                     - implemented
#pausepost                          - implemented
#resumepost                         - implemented
#pausescan                          - implemented (pause scan of NzbDir)
#resumescan                         - implemented (resume scan of NzbDir)
#scheduleresume                     - implemented (schedule to resume all activities after specified seconds (int)

## Configuration
#config                             - implemented (TODO: Parsing)
#loadconfig                         - implemented (TODO: Parsing)
#saveconfig                         - implemented (TODO: Parsing)
#configtemplates                    - implemented (TODO: Parsing)

private function TraverseArray($array, $area = NULL)
{
    foreach($array as $key=>$value)
    {
        //nested arrays are called "0" in NZBGet
        //those arrays should get the key value from cascading array
        //TODO: call function that creates instances in IPS based on instance provided by a parameter
        if($key=="0")
           $key=$area;
        
            print $area.$key."<br>";

        if (!is_array($value))
            print $area.$value."<br>";
        //TODO: Wire up function to create IPS instances
        // $key acts as instance name and is representing the nested array name
        else $this->TraverseArray($value, $key);
    }
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

public function GetVersion()
{
    $response = $this->GetRpcResponseValue('version');  
    return php_xmlrpc_decode($response);
}

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

public function GetGroups()
{
    $response = $this->GetRpcResponseValue('listgroups');
    var_dump($response);
    $decodedStatus = php_xmlrpc_decode($response);
    $this->TraverseArray($decodedStatus);
}

public function GetFiles($nzbid)
{
    $response = $this->GetRpcResponseValue('listfiles', $nzbid);
    var_dump($response);
    $decodedStatus = php_xmlrpc_decode($response);
    $this->TraverseArray($decodedStatus);
}

public function GetHistory($showHiddenRecords)
{
    $response = $this->GetRpcResponseValue('history', $showHiddenRecords);
    var_dump($response);
    $decodedStatus = php_xmlrpc_decode($response);
    $this->TraverseArray($decodedStatus);
}

public function AddNzbToQueue($nzbFileNameDestDir, $nzbContentOrUri, $category, $priority, $dupeKey, $dupeScore, $dupeMode, $postProcessingArray, $addToTop = false, $addPaused = FALSE)
{
    //possible priorities are: -100 (very low), -50 (low), 0 (normal), 50 (high), 100 (very high), 900 (force).
    // $addToTop = false means nzb will be added to the end of queue
    $parameters = array($nzbFileNameDestDir, $nzbContentOrUri, $category, $priority, $addToTop, $addPaused, $dupeKey, $dupeScore, $dupeMode, $postProcessingArray);
    
    
    $response = $this->GetRpcResponseValue('append', $parameters);
    var_dump($response);
    $decodedStatus = php_xmlrpc_decode($response);
    $this->TraverseArray($decodedStatus);
}

public function EditQueue($command, $offSet, $editText, $ids)
{
    //possible priorities are: -100 (very low), -50 (low), 0 (normal), 50 (high), 100 (very high), 900 (force).
    // $addToTop = false means nzb will be added to the end of queue
    $parameters = array($command, $offSet, $editText, $ids);


    $response = $this->GetRpcResponseValue('editqueue', $parameters);
    var_dump($response);
    $decodedStatus = php_xmlrpc_decode($response);
    $this->TraverseArray($decodedStatus);
}

public function ScanNzbDir()
{
    $response = $this->GetRpcResponseValue('scan');
    return php_xmlrpc_decode($response);
}

public function GetStatus()
{
    $response = $this->GetRpcResponseValue('status');
    $decodedStatus = php_xmlrpc_decode($response);
    
    $this->TraverseArray($decodedStatus, 'status');
}

public function GetLogs($idFrom, $numberOfEntries)
{
    $parameters = array($idFrom, $numberOfEntries);
    $response = $this->GetRpcResponseValue('log', $parameters);
    $decodedStatus = php_xmlrpc_decode($response);
    $this->TraverseArray($decodedStatus);
    
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

public function GetLogsFromDisk($nzbId, $idFrom, $numberOfEntries)
{
    $parameters = array($nzbId, $idFrom, $numberOfEntries);
    $response = $this->GetRpcResponseValue('log', $parameters);
    $decodedStatus = php_xmlrpc_decode($response);
    $this->TraverseArray($decodedStatus);

}

public function GetNewsServerStatistics()
{
    $response = $this->GetRpcResponseValue('servervolumes');
    $decodedStatus = php_xmlrpc_decode($response);
    $this->TraverseArray($decodedStatus);
}

public function ResetNewsServerStatistics($serverId, $counter)
{
    $parameters = array($serverId, $counter);
    $response = $this->GetRpcResponseValue('resetservervolume', $parameters);
    $decodedStatus = php_xmlrpc_decode($response);
}


public function SetDownloadSpeedLimit($speedLimitInKbs)
{
    $response = $this->GetRpcResponseValue('rate', $speedLimitInKbs);
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

public function GetLoadedConfig()
{
    //TODO: Re-consider parsing of struct
    $response = $this->GetRpcResponseValue('config');
    $decodedStatus = php_xmlrpc_decode($response);

    $this->TraverseArray($decodedStatus, 'config');
}

public function GetConfigFile()
{
    //TODO: Re-consider parsing of struct
    $response = $this->GetRpcResponseValue('loadconfig');
    $decodedStatus = php_xmlrpc_decode($response);

    $this->TraverseArray($decodedStatus, 'loadconfig');
}

public function SaveConfigFile($arrayStruct)
{
    //TODO: pass parameters
    $response = $this->GetRpcResponseValue('saveconfig');
    $decodedStatus = php_xmlrpc_decode($response);
}

public function GetConfigTemplates($fromDisk)
{
    //TODO: Re-consider parsing of struct
    $response = $this->GetRpcResponseValue('configtemplates', $fromDisk);
    $decodedStatus = php_xmlrpc_decode($response);

    $this->TraverseArray($decodedStatus, 'configtemplates');
}

public function TestCall()
{
    $xmlrpc_client = $this->InstantiateNewRpc();

    
    
    $logXml = new xmlrpcmsg('log', $params);
    $versionXml = new xmlrpcmsg('version');
    $historyXml = new xmlrpcmsg('history'); 
    
    
    $xmlrpc_response = $xmlrpc_client->send($versionXml);
    $response = $xmlrpc_response->value();
    
    $decoded = php_xmlrpc_decode($response);
    
    
    
    if(is_array($decoded))
    $this->TraverseArray($decoded);
    else print $decoded;
    
    
    
    if ($xmlrpc_response == False) // responseonsek for successful transaction
    
    die('error message');
    
    if (!$xmlrpc_response->faultCode()) {
        // …
        }
}
}

$testCall = new IPS_NZBGet();
//$testCall->TestCall();

//print_r($testCall->GetVersion());
//print_r($testCall->GetStatus());
//print_r($testCall->WriteNzbGetLog('info', 'blsssaas265a'));
//print_r($testCall->WriteNzbGetLog_ERROR('blub'));
// print_r($testCall->GetLogsFromDisk(0, 0, 10));
print_r($testCall->GetNewsServerStatistics());

//print_r($testCall->Reload());
// print_r($testCall->GetGroups());
// print_r($testCall->GetFiles());
//print_r($testCall->GetHistory(false));
//print_r($testCall->AddNzbToQueue($nzbFileNameDestDir, $nzbContentOrUri, $category, $priority, $dupeKey, $dupeScore, $dupeMode, $postProcessingArray));

//print_r($testCall->Shutdown());
//print_r($testCall->ScanNzbDir());
//print_r($testCall->SetDownloadSpeedLimit(1200));
//print_r($testCall->PauseDownloadQueue());
//print_r($testCall->ResumeDownloadQueue());
//print_r($testCall->PausePostProcessing());
//print_r($testCall->ResumePostProcessing());
//print_r($testCall->PauseNzbDirScan());
//print_r($testCall->ResumeNzbDirScan());
//print_r($testCall->ScheduleResumeOfAllActivities(30));
// print_r($testCall->GetLoadedConfig());
//print_r($testCall->GetConfigFile());
//print_r($testCall->SaveConfigFile($tesstt));
// print_r($testCall->GetConfigTemplates(true));
// print_r($testCall->GetLogs(0, 100));

?>