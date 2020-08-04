<?php

session_start();
$sess_id_1 = session_id();
$sess_id_2 = "cross-duplicate-module";
session_write_close();
session_id($sess_id_2);
session_start();

define("NOAUTH",true);

$fieldList = $_POST['fields'];
$projects = $_POST['projects'];
$fieldValues = $_POST['fieldValues'];

$duplicate = "0";
$alertMessage = $module->getProjectSetting('alert-message');
$fieldData = array();

foreach ($fieldList as $index => $fieldName) {
    $fieldData[$fieldName] = $fieldValues[$index][0];
}

$alertMessage = parseRecordSetting($alertMessage,$fieldData);

if (!empty($_POST['token'])) {
    if (hash_equals($_SESSION['survey_piping_token'], $_POST['token'])) {
        $fieldNameValues = array();
        foreach ($fieldList as $index => $field) {
            $fieldNameValues[$index] = "[".$field."] = '".$fieldValues[$index][0]."'";
        }
        foreach ($projects as $projectID) {
            $recordData = json_decode(\REDCap::getData($projectID,'json',array(),$fieldList,array(), array(), false, false, false, implode(" AND ",$fieldNameValues)),true);
            if (!empty($recordData)) {
                $currentProject = new \Project($projectID);
                $projectName = $currentProject->project['app_title'];
                if ($duplicate == "0") {
                    $duplicate = $alertMessage." has duplicates in the following project(s):\n";
                }
                $duplicate .= $projectName."\n";
            }
        }
    }
}

session_write_close();
session_id($sess_id_1);
session_start();

echo $duplicate;

function parseRecordSetting($recordsetting,$recorddata) {
    $returnString = $recordsetting;
    preg_match_all("/\[(.*?)\]/",$recordsetting,$matchRegEx);
    $stringsToReplace = $matchRegEx[0];
    $fieldNamesReplace = $matchRegEx[1];
    foreach ($fieldNamesReplace as $index => $fieldName) {
        $returnString = db_real_escape_string(str_replace($stringsToReplace[$index],$recorddata[$fieldName],$returnString));
    }
    return $returnString;
}