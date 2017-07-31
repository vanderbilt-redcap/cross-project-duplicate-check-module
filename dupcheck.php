<?php
namespace ExternalModules;
require_once ExternalModules::getProjectHeaderPath();

# requires a GET['pid'], a configuration for the current project, and a GET['location'] (this [project] or all)
# for repeating forms/instruments/events, the first instance on the earliest event is used

$title = \REDCap::getProjectTitle();
$firstField = \REDCap::getRecordIdField();
$settings = ExternalModules::getProjectSettingsAsArray("vanderbilt_dupcheck", $_GET['pid']);

$project_ids = $settings['project-id']['value'];
if (!is_array($project_ids)) {
	$project_ids = array($project_ids);
}

$fields = $settings['field']['value'];
if (!is_array($fields)) {
	$fields = array($fields);
}

$fieldsToAcquire = $fields;
$fieldsToAcquire[] = $firstField;

$projectTitles = array();
if (isset($_GET['location'])) {
	if ($_GET['location'] == "all") { 
		if (!in_array($_GET['pid'], $project_ids)) {
			$project_ids[] = $_GET['pid'];
		}
	} else if ($_GET['location'] == "this") {
		$project_ids = array($_GET['pid']);
	}
	else {
		# invalid
		return;
	}
	\REDCap::allowProjects($project_ids);

	$sql = "SELECT project_id, app_title
			FROM redcap_projects
			WHERE project_id IN (".implode(", ", $project_ids).");";
	$result = db_query($sql);
	while ($row = db_fetch_assoc($result)) {
		$projectTitles[$row['project_id']] = $row['app_title'];
	}

	echo "<html>";
	echo "<head>";
	echo "<title>$title - Check for Duplicates</title>";
	echo "</head>";

	echo "<body>";
	echo "<h1>$title</h1>";
	if (count($projectTitles) > 1) {
		echo "<p><b>Associated Projects</b><br>";
		$titleList = array();
		foreach ($projectTitles as $pid => $projectTitle) {
			$titleList[] = $projectTitle;
		}
		echo implode("<br>", $titleList);
		echo "</p>";
	}
	$allData = array();
	$values = array();
	foreach ($project_ids as $pid) {
		$allData[$pid] = array();
		$allData[$pid] = \REDCap::getData($pid, 'array', null, $fields);
		$infinitelyRepeating = false;
		foreach ($allData[$pid] as $record => $recData) {
			foreach ($recData as $evID => $evData) {
				foreach ($evData as $field => $value) {
					if (is_array($field)) {
						$infinitelyRepeating = true;
					}
				}
			}
		}
	}
	$sortedAry = array();
	$sep = "|||";
	$firstField = "";
	foreach ($values as $field => $pids) {
		$firstField = $field;
	}

	# non-infinitely repeating
	foreach ($allData as $pid => $pidData) {
		foreach ($pidData as $record => $recData) {
			foreach ($recData as $evID => $evData) {
				$str = "";
				# ordered list
				foreach ($fields as $field) {
					$str .= $sep.$evData[$field];
				}
				if (isset($sortedAry[$str])) {
					$sortedAry[$str][] = $pid.$sep.$record;
				} else {
					$sortedAry[$str] = array($pid.$sep.$record);
				}
			}
		}
	}
	echo "<h2>Duplicates</h2>";
	echo "<p>The following projects have all the primary-key fields (".implode(", ", $fields).") matching.</p>";
	$i = 0;
	foreach ($sortedAry as $str => $ary) {
		if (count($ary) > 1) {
			$i++;
			echo "<p><b>Match $i</b>";

			$vals = explode($sep, $str);
			$j = 1;
			foreach ($fields as $field) {
				echo "<br>$field: ".$vals[$j];
				$j++;
			}
			foreach ($ary as $projectRecord) {
				$vals = explode($sep, $projectRecord);
				$project = $projectTitles[$vals[0]];
				$record = $vals[1];
				echo "<br><i>$project: Record $record</i>";
			}
			echo "</p>";
		}
	}
	if ($i === 0) {
		echo "<p>None found.</p>";
	}
	echo "</body>";
	echo "</html>";
	
}

require_once ExternalModules::getProjectFooterPath();
