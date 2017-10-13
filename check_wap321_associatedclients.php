<?php
// check_wap321_associatedclients.php
// Check Associated Clients for Cisco WAP321
// Manufacturer recommended levels are 20 for warning and 35 for critical.
// 
// USAGE: php check_wap321_associatedclients.php HOST COMMUNITY WARNING CRITICAL
// HOST: IP or FQDN of the target Cisco WAP321 device.
// COMMUNITY: SNMP Community Name
// WARNING: Amount of Associated Client connections to trigger WARNING
// CRITICAL: Amount of Associated Client connections to trigger CRITICAL
// EXAMPLE: php check_wap321_associatedclients.php 192.168.1.1 public 20 35
//
// Include in commands.cfg
// define command{
// command_name check_wap321_associatedclients
// command_line php /path/to/check_wap321_associatedclients.php $HOSTADDRESS $ARG1$ $ARG2$ $ARG3$
// }
//
// This plugin requires php5-snmp to be installed
// -Kyle M


// INITIALIIIIIIZE!!!
$message ='';
$status = 0;

// Check if all arguments are supplied
if(count($argv) < 4)
  DisplayMessage(3, "Incomplete statement.\r\nUSAGE: check_terastation_storage.php HOST COMMUNITY WARNING CRITICAL\r\n");

//Assign supplied arguments
list(,$host, $community, $warning, $critical,) = $argv;
$warning=(float)$warning;
$critical=(float)$critical;

//If warning less than critial, give usage example and exit.
if($warning > $critical)
  DisplayMessage(3, "The WARNING value cannot be higher than the CRITICAL value.\r\nUSAGE: check_wap321_associatedclients.php HOST COMMUNITY WARNING CRITICAL\r\n");
elseif( empty($host) || empty($community) )
  DisplayMessage(3, "Error, host and/or community is empty.\r\nUSAGE: check_wap321_associatedclients.php HOST COMMUNITY WARNING CRITICAL\r\n");

// Test connection, SNMP availability, and valid Community.
GetSnmpObjValue($host, $community, 'iso.3.6.1.2.1.1.1.0');

// Take a walk on the OID and get the list of wlans in use.
$ret = snmpwalk("$host", "$community", "1.3.6.1.4.1.9.6.1.104.1.7.1.1.2");
if( $ret === false )		//If walk unsuccessful or empty, fail.
 DisplayMessage(3, "SNMPWALK unsuccessful. Please verify OID for this device.");
else $totalAssociatedClients=count($ret);//Array size gives us num of clients

// All the bacon, this is where we check if we should be alarmed
// Check if totalAssociatedClients is above set points
if( $totalAssociatedClients >= $critical ) {
 $status=2;
 $message.="CRITICAL - Total Associated Clients ($totalAssociatedClients) ";
}elseif($totalAssociatedClients >= $warning ) {
 $status=1;
 $message.="WARNING - Total Associated Clients ($totalAssociatedClients) ";
}else{
 $status=0;
 $message.="OK - Total Associated Clients ($totalAssociatedClients) ";
}

// Go through results and strip everything but the wlan name from the string
foreach( $ret as $key => $value) {
 $sploded = explode('"', $ret[$key]);
 $ret[$key]=$sploded[1];
}

// Break results into an array containing wlan names and num clients for each
$wlans = array_count_values($ret);

// Append all wlans found and amount of clients associated with each to message
foreach( $wlans as $wlanName => $wlanClientsAmt ) {
 $message.= "- $wlanName ($wlanClientsAmt) ";
}

DisplayMessage($status, $message);



//Functions
//
// Display message and exit with proper integer to trigger Nagios OK, Critical, Warning.
function DisplayMessage($exitInt, $exitMsg) {
  echo $exitMsg;
  exit($exitInt);
} // DisplayMessage()


// Connect and return object value.
// If the host doesn't respond to simple SNMP query, exit.
function GetSnmpObjValue($host, $community, $oid) {
  $ret = @snmpget($host, $community, $oid);
  if( $ret === false )
    DisplayMessage(3, 'Cannot reach host: '.$host.', community: '.$community.', OID: '.$oid.'. Possibly offline, SNMP is not enabled, COMMUNITY string is invalid, or wrong OID for this device.');
  return $ret;
} // GetSnmpObjValue()


// Check if returned SNMP object value is an integer, strip 'INTEGER: ' from it and return value.
function GetSnmpObjValueInteger($SnmpObjValue) {
  $ret = strstr($SnmpObjValue, 'INTEGER: ');
  if( $ret === false )
    DisplayMessage(3, 'Unexpected value: '.$ret.' :: Possibly wrong OID for this device.');
  list(,$ret) = explode(' ',$ret);
  return $ret;
} // GetSnmpObjValueInteger()


?>
