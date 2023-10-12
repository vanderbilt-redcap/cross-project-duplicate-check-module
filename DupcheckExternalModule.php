<?php
namespace Vanderbilt\DupcheckExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class DupcheckExternalModule extends AbstractExternalModule
{
    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id = NULL, $repeat_instance = 1) {
        echo $this->buildJavaString($project_id,$record,$instrument,$event_id,$repeat_instance);
    }

    function redcap_survey_page_top($project_id,$record,$instrument,$event_id,$group_id,$survey_hash,$response_id,$repeat_instance = 1)
    {
        echo $this->buildJavaString($project_id,$record,$instrument,$event_id,$repeat_instance);
    }

    function buildJavaString($project_id,$record,$instrument,$event_id,$repeat_instance = 1) {
        $project = new \Project($project_id);
        $alertSetting = $this->getProjectSetting('display-alert');
        if ($alertSetting == '1') {
            $projectIDs = $this->getProjectSetting('project-id');
            $fields = $this->getProjectSetting('field');

            if (count($projectIDs) > 0 && count($fields) > 0) {
                ?>
                <?=$this->initializeJavascriptModuleObject()?>
                <script>
                    $(() => {
                        const module = <?=$this->getJavascriptModuleObjectName()?>;

                        const checkDuplicateData = () => {
                            var fields = <?=json_encode($fields)?>;
                            var projectIDs = <?=json_encode($projectIDs)?>;
                            var fieldValues = getModuleFieldValues(fields);
                            if (fieldValues.length == fields.length) {
                                module.ajax('check for duplicates',  {
                                    'currentproject': <?=json_encode($project_id)?>,
                                    'fields': fields,
                                    'projects': projectIDs,
                                    'fieldValues': fieldValues,
                                }).then((response) => {
                                    if (response != '0') {
                                        alert(response);
                                    }
                                }).catch(function(err) {
                                    alert('Error checking for duplicates!')
                                })
                            }
                        }

                        const getModuleFieldValues = (fields) => {
                            var fieldValues = [];
                            for (var i = 0; i < fields.length; i++) {
                                fieldValues[i] = [];
                                $('#'+fields[i]+'-tr :input').each(function() {
                                    fieldValues[i].push($(this).val());
                                });
                            }
                            return fieldValues;
                        }
                        
                        <?php foreach ($fields as $field) { ?>
                            $(<?=json_encode("[name=\"$field\"]")?>).change(function() {
                                checkDuplicateData()
                            })
                        <?php } ?>
                    })
                </script>
                <?php
            }
        }
    }

    function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance, $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id) {
        if($action === 'check for duplicates'){
            return require_once __DIR__ . '/ajax_data.php';
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
        $d = \DateTime::createFromFormat($format, $date);
        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        return $d && $d->format($format) === $date;
    }
}