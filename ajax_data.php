<?php

session_name("cross-duplicate-module");
session_start();

define("NOAUTH",true);

$fieldList = $_POST['fields'];
$projects = $_POST['projects'];
$fieldValues = $_POST['fieldValues'];
$project_id = $_POST['currentproject'];
$currentProject = new \Project($project_id);
$currentMeta = $currentProject->metadata;
$dateValidations = array('date_mdy','date_dmy','date_ymd','datetime_mdy','datetime_dmy','datetime_ymd','datetime_seconds_mdy','datetime_seconds_dmy','datetime_seconds_ymd');

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
            if (validateDate($fieldValues[$index][0],$module->getDateFormat($currentMeta[$field]['element_validation_type'], $field, 'php'))) {
                if (in_array($currentMeta[$field]['element_validation_type'], $dateValidations)) {
                    $dateFormatting = $module->getDateFormat($currentMeta[$field]['element_validation_type'], $field, 'php');
                    $date = DateTime::createFromFormat($dateFormatting, $fieldValues[$index][0]);
                    $fieldValues[$index][0] = $date->format("Y-m-d");
                }
            }
            $fieldNameValues[$index] = "[".$field."] = '".$fieldValues[$index][0]."'";
        }

        foreach ($projects as $projectID) {
            $recordData = json_decode(\REDCap::getData($projectID,'json',array(),$fieldList,array(), array(), false, false, false, implode(" AND ",$fieldNameValues)),true);

            if (!empty($recordData)) {
                $currentProject = new \Project($projectID);
                $projectName = $currentProject->project['app_title'];
                if ($duplicate == "0") {
                    $duplicate = $alertMessage."\n";
                }
                $duplicate .= $projectName."\n";
            }
        }
    }
}

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

function validateDate($date,$format='Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
    return $d && $d->format($format) === $date;
}