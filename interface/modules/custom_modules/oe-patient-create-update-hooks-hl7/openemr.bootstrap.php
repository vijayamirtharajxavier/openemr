<?php

/**
 * Bootstrap custom Patient Update/Create Listener module
 *
 * This is the main file for the example module that demonstrates the ability
 * to listen for patient-update and patient-create events and perform additional
 * actions.
 *
 * @package   OpenEMR
 * @link      http://www.open-emr.org
 * @author    Ken Chapple <ken@mi-squared.com>
 * @copyright Copyright (c) 2021 Ken Chapple <ken@mi-squared.com>
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

use OpenEMR\Events\Patient\PatientCreatedEvent;
use OpenEMR\Events\Patient\PatientUpdatedEvent;
use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Get new or updated patient data and do something with it
 *
 * @param $patientData
 */
function send_patient_data_to_remote_system($patientData,$adt_type)
{
    $uuid = uniqid(mt_rand(), true);

    // Remove non-numeric characters
    $uuid = preg_replace('/[^0-9]/', '', $uuid);
    $uuid = substr($uuid, 0, 20);
// Generate HL7 message for the sample patient data
$hl7_message = generate_hl7_message($patientData,$uuid,$adt_type);
// Output the generated HL7 message

//Sending HL7 Message to MLLP server
$mllp_result = send_hl7_mllp($hl7_message);
var_dump($mllp_result);
//Write HL7 to a File
// $flstatus= write_to_file($hl7_message,$uuid);
//var_dump($hl7_message);
// var_dump($hl7_message . $flstatus . $uuid);
  // This is just a stub for example only
    // For example, you could write data to a file and send to a remote SFTP server
    // or build a remote API call.
    return;
}




//Send HL7 message to MLLP Server
function send_hl7_mllp($hl7_message)
{
    <?php
    // HL7 message to send
//    $hl7_message = "MSH|^~\&|Sender|SenderFac|Receiver|ReceiverFac|20240321120000||ADT^A04|123456|P|2.5";
    
    // MLLP framing
    $hl7_message = "\x0B" . $hl7_message . "\x1C\x0D";
    
    // MLLP server configuration
    $server_host = '172.18.0.8';
    $server_port = 6661;
    
    // Establish connection
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket === false) {
        echo "Error: Unable to create socket\n";
        exit;
    }
    
    $result = socket_connect($socket, $server_host, $server_port);
    if ($result === false) {
        echo "Error: Unable to connect to server\n";
        exit;
    }
    
    // Send HL7 message
    socket_write($socket, $hl7_message, strlen($hl7_message));
    
    // Close connection
    socket_close($socket);
    
    echo "HL7 message sent successfully.\n";
    ?>
    
}



// Function to generate HL7 message for patient data
function generate_hl7_message($patient_data,$uuid,$adt_type) {
//HL7 sample for Register new Patieht
/*
MSH|^~\&|SendingApp|SendingFac|ReceivingApp|ReceivingFac|20240321120000||ADT^A04|123456|P|2.5
EVN|A04|20240321120000|||
PID|1||1234567890^^^MR^MRN1||Doe^John^^^Mr.||19800101|M||||||||||123456789|
PV1|1|O|^^^|||||||||||||||||N

//HL7 sample for Update a Patient

MSH|^~\&|SendingApp|SendingFac|ReceivingApp|ReceivingFac|20240321120000||ADT^A08|123457|P|2.5
EVN|A08|20240321120000|||
PID|1||1234567890^^^MR^MRN1||Doe^John^^^Mr.||19800101|M||||||||||123457890|
PV1|1|O|^^^|||||||||||||||||N


*/



    // Construct MSH segment
    $adt_explode=explode("^",$adt_type);
    $adt_code = $adt_explode[1];
    $msh_segment = 'MSH|^~\&|SendingApp|SendingFac|ReceivingApp|ReceivingFac|' . date('YmdHis') . '||'. $adt_type .'|' . $uuid . '|P|2.5|||AL|NE' . "\r";
    $evn_segment = 'EVN|'. $adt_code .'|' . date('YmdHis') . '|||';
    $pv1_segment='PV1|1|O|^^^|||||||||||||||||N';

    $dob = $newDate = date("Ymd", strtotime($patient_data['DOB']));
    // Construct PID segment
    //PID|1|PatientID|PatientIdentifierList|AlternatePatientID|PatientName Last^First^Middle^Suffix|MotherMaidenName|DateOfBirth|AdministrativeSex|PatientAlias|Race|PatientAddress|CountyCode|PhoneNumberHome|PhoneNumberBusiness|PrimaryLanguage|MaritalStatus|Religion|PatientAccountNumber|SSNNumber|DriverLicenseNumber|MothersIdentifier|EthnicGroup|BirthPlace|MultipleBirthIndicator|BirthOrder|Citizenship|VeteransMilitaryStatus|Nationality|PatientDeathDateAndTime|PatientDeathIndicator

    $pid_segment = 'PID|1|' . $patient_data['pid'] . '|' . $patient_data['pid'] . '||' . $patient_data['lname'] . '^' . $patient_data['fname'] . '^' . $patient_data['suffix'] . '|'.$patient_data['mothersname'] .'|' . $dob . '|||||||||||' . "\r";

    // Combine segments into HL7 message
    $hl7_message = $msh_segment . $evn_segment . $pid_segment . $pv1_segment;
//var_dump($hl7_message);
    return $hl7_message;
}



// Trigger the file writing operation
function write_to_file($data,$filename) {
     
    // File path
    $file = '/var/www/localhost/htdocs/openemr/openemr-data/' . $filename .  '.txt';
//var_dump($file . "----" . $data);
$myfile = fopen($file, "x") or die("Unable to open file!");
fwrite($myfile, $data);
fclose($myfile);

}

/**
* This function is called when a patient is created, so we can do
 * any additional processing that a 3rd party may require. For example,
 * sending data to another system like Quickbooks
 *
 * @param PatientCreatedEvent $patientCreatedEvent
 * @return mixed
 */
function oe_module_custom_patient_created_action(PatientCreatedEvent $patientCreatedEvent)
{
    $patientData = $patientCreatedEvent->getPatientData();
    $adt_type="ADT^A04";
    send_patient_data_to_remote_system($patientData,$adt_type);
    return $patientCreatedEvent;
}

/**
 * This function is called when a patient is updated, so we can do
 * any additional processing that a 3rd party may require. For example,
 * sending data to another system like Quickbooks
 *
 * @param PatientUpdatedEvent $patientUpdatedEvent
 * @return PatientUpdatedEvent
 */
function oe_module_custom_patient_update_action(PatientUpdatedEvent $patientUpdatedEvent)
{
    $patientData = $patientUpdatedEvent->getNewPatientData();
    $adt_type="ADT^A08";
    send_patient_data_to_remote_system($patientData,$adt_type);
    return $patientUpdatedEvent;
}

// Listen for the patient update and create events
$eventDispatcher = $GLOBALS['kernel']->getEventDispatcher();
$eventDispatcher->addListener(PatientCreatedEvent::EVENT_HANDLE, 'oe_module_custom_patient_created_action');
$eventDispatcher->addListener(PatientUpdatedEvent::EVENT_HANDLE, 'oe_module_custom_patient_update_action');
