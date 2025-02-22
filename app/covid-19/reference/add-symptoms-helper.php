<?php

use App\Registries\ContainerRegistry;
use App\Services\CommonService;
use App\Utilities\DateUtility;


if (session_status() == PHP_SESSION_NONE) {
	session_start();
}




/** @var MysqliDb $db */
$db = ContainerRegistry::get('db');

/** @var CommonService $general */
$general = ContainerRegistry::get(CommonService::class);



$tableName = "r_covid19_symptoms";

try {
	if (isset($_POST['symptomsName']) && trim($_POST['symptomsName']) != "") {

		$data = array(
			'symptom_name' => $_POST['symptomsName'],
			'parent_symptom' => $_POST['parentSymptom'],
			'symptom_status' => $_POST['symptomsStatus'],
			'updated_datetime' => DateUtility::getCurrentDateTime(),
		);

		$db->insert($tableName, $data);
		$lastId = $db->getInsertId();

		$_SESSION['alertMsg'] = _("Symptom details added successfully");
		$general->activityLog('add-symptoms', $_SESSION['userName'] . ' added new reference symptom' . $_POST['symptomsName'], 'reference-covid19-symptoms');
	}
	header("Location:covid19-symptoms.php");
} catch (Exception $exc) {
	error_log($exc->getMessage());
	error_log($exc->getTraceAsString());
}
