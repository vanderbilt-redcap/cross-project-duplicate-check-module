<?php

$fieldList = $payload['fields'];
$projects = $payload['projects'];
$fieldValues = $payload['fieldValues'];
$project_id = $payload['currentproject'];
$currentProject = new \Project($project_id);
$currentMeta = $currentProject->metadata;
$dateValidations = array('date_mdy','date_dmy','date_ymd','datetime_mdy','datetime_dmy','datetime_ymd','datetime_seconds_mdy','datetime_seconds_dmy','datetime_seconds_ymd');

$duplicate = "0";
$alertMessage = $this->getProjectSetting('alert-message');
$fieldData = array();

foreach ($fieldList as $index => $fieldName) {
    $fieldData[$fieldName] = $fieldValues[$index][0];
}

$alertMessage = $this->parseRecordSetting($alertMessage,$fieldData);

$fieldNameValues = array();
foreach ($fieldList as $index => $field) {
    if ($this->validateDate($fieldValues[$index][0],$this->getDateFormat($currentMeta[$field]['element_validation_type'], $field, 'php'))) {
        if (in_array($currentMeta[$field]['element_validation_type'], $dateValidations)) {
            $dateFormatting = $this->getDateFormat($currentMeta[$field]['element_validation_type'], $field, 'php');
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

return $duplicate;
