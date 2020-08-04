<?php
namespace Vanderbilt\DupcheckExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class DupcheckExternalModule extends AbstractExternalModule
{
    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id = NULL, $repeat_instance = 1) {
        //list($transferData,$currentIndex,$formIndex) = $this->getMatchingRecordData("submit",$project_id,$record,$instrument,$event_id,$group_id,null,null,$repeat_instance);
        /*$currentProject = new \Project($project_id);
        $currentMeta = $currentProject->metadata;
        echo "<pre>";
        print_r($currentMeta);
        echo "</pre>";*/
    }

    function redcap_survey_page_top($project_id,$record,$instrument,$event_id,$group_id,$survey_hash,$response_id,$repeat_instance = 1)
    {
        $project = new \Project($project_id);
        $alertSetting = $this->getProjectSetting('display-alert');
        if ($alertSetting == '1') {
            $projectIDs = $this->getProjectSetting('project-id');
            $fields = $this->getProjectSetting('field');

            $sess_id_1 = session_id();
            $sess_id_2 = "cross-duplicate-module";
            session_write_close();
            session_id($sess_id_2);
            session_start();

            if (empty($_SESSION['survey_piping_token'])) {
                $_SESSION['survey_piping_token'] = bin2hex(random_bytes(32));
            }
            $token = $_SESSION['survey_piping_token'];

            $javaString = "<script>
                $(document).ready(function() {";

            if (count($projectIDs) > 0 && count($fields) > 0) {
                foreach ($fields as $field) {
                    $javaString .= "$('[name=\"".$field."\"]').change(function() {
                        checkDuplicateData();
                    });";
                }

                $javaString .= "function checkDuplicateData() {
                    var fields = ['".implode("','",$fields)."'];
                    var projectIDs = [".implode(",",$projectIDs)."];
                    var fieldValues = getModuleFieldValues(fields);
                    if (fieldValues.length == fields.length) {
                        $.ajax({
                            url: '".$this->getUrl('ajax_data.php')."&NOAUTH',
                            method: 'post',
                            data: {
                                'currentproject': $project_id,
                                'fields': fields,
                                'projects': projectIDs,
                                'fieldValues': fieldValues,
                                'token': '$token'
                            },
                            success: function (data) {
                                //console.log(data);
                                if (data != '0') {
                                    alert(data);
                                }
                            }
                        });
                    }
                }
                function getModuleFieldValues(fields) {
                    var fieldValues = [];
                    for (var i = 0; i < fields.length; i++) {
                        fieldValues[i] = [];
                        $('#'+fields[i]+'-tr :input').each(function() {
                            fieldValues[i].push($(this).val());
                        });
                    }
                    return fieldValues;
                }";
            }

            $javaString .= "});
            </script>";

            session_write_close();
            session_id($sess_id_1);
            session_start();
            echo $javaString;
        }
    }

    /*
	 * Determine the correct date formatting based on a field's element validation.
	 * @param $elementValidationType The element validation for the data field being examined.
	 * @param $type Either 'php' or 'javascript', based on where the data format string is being injected
	 * @return Date format string
	 */
    function getDateFormat($elementValidationType, $fieldName, $type) {
        $returnString = "";
        switch ($elementValidationType) {
            case "date_mdy":
                if ($type == "php") {
                    $returnString = "m-d-Y";
                }
                elseif ($type == "javascript") {
                    $returnString = "addZ($fieldName.getUTCMonth()+1)+'-'+addZ($fieldName.getUTCDate())+'-'+$fieldName.getUTCFullYear()";
                }
                break;
            case "date_dmy":
                if ($type == "php") {
                    $returnString = "d-m-Y";
                }
                elseif ($type == "javascript") {
                    $returnString = "addZ($fieldName.getUTCDate())+'-'+addZ($fieldName.getUTCMonth()+1)+'-'+$fieldName.getUTCFullYear()";
                }
                break;
            case "date_ymd":
                if ($type == "php") {
                    $returnString = "Y-m-d";
                }
                elseif ($type == "javascript") {
                    $returnString = "$fieldName.getUTCFullYear()+'-'+addZ($fieldName.getUTCMonth()+1)+'-'+addZ($fieldName.getUTCDate())";
                }
                break;
            case "datetime_mdy":
                if ($type == "php") {
                    $returnString = "m-d-Y H:i";
                }
                elseif ($type == "javascript") {
                    $returnString = "addZ($fieldName.getUTCMonth()+1)+'-'+addZ($fieldName.getUTCDate())+'-'+$fieldName.getUTCFullYear()+' '+addZ($fieldName.getUTCHours())+':'+addZ($fieldName.getUTCMinutes())";
                }
                break;
            case "datetime_dmy":
                if ($type == "php") {
                    $returnString = "d-m-Y H:i";
                }
                elseif ($type == "javascript") {
                    $returnString = "addZ($fieldName.getUTCDate())+'-'+addZ($fieldName.getUTCMonth()+1)+'-'+$fieldName.getUTCFullYear()+' '+addZ($fieldName.getUTCHours())+':'+addZ($fieldName.getUTCMinutes())";
                }
                break;
            case "datetime_ymd":
                if ($type == "php") {
                    $returnString = "Y-m-d H:i";
                }
                elseif ($type == "javascript") {
                    $returnString = "$fieldName.getUTCFullYear()+'-'+addZ($fieldName.getUTCMonth()+1)+'-'+addZ($fieldName.getUTCDate())+' '+addZ($fieldName.getUTCHours())+':'+addZ($fieldName.getUTCMinutes())";
                }
                break;
            case "datetime_seconds_mdy":
                if ($type == "php") {
                    $returnString = "m-d-Y H:i:s";
                }
                elseif ($type == "javascript") {
                    $returnString = "addZ($fieldName.getUTCMonth()+1)+'-'+addZ($fieldName.getUTCDate())+'-'+$fieldName.getUTCFullYear()+' '+addZ($fieldName.getUTCHours())+':'+addZ($fieldName.getUTCMinutes())+':'+addZ($fieldName.getUTCSeconds())";
                }
                break;
            case "datetime_seconds_dmy":
                if ($type == "php") {
                    $returnString = "d-m-Y H:i:s";
                }
                elseif ($type == "javascript") {
                    $returnString = "addZ($fieldName.getUTCDate())+'-'+addZ($fieldName.getUTCMonth()+1)+'-'+$fieldName.getUTCFullYear()+' '+addZ($fieldName.getUTCHours())+':'+addZ($fieldName.getUTCMinutes())+':'+addZ($fieldName.getUTCSeconds())";
                }
                break;
            case "datetime_seconds_ymd":
                if ($type == "php") {
                    $returnString = "Y-m-d H:i:s";
                }
                elseif ($type == "javascript") {
                    $returnString = "$fieldName.getUTCFullYear()+'-'+addZ($fieldName.getUTCMonth()+1)+'-'+addZ($fieldName.getUTCDate())+' '+addZ($fieldName.getUTCHours())+':'+addZ($fieldName.getUTCMinutes())+':'+addZ($fieldName.getUTCSeconds())";
                }
                break;
            default:
                $returnString = '';
        }
        return $returnString;
    }
}