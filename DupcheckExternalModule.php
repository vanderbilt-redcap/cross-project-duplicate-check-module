<?php
namespace Vanderbilt\DupcheckExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;

class DupcheckExternalModule extends AbstractExternalModule
{
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
}