<?php

use App\Registries\ContainerRegistry;
use App\Services\CommonService;



/** @var MysqliDb $db */
$db = ContainerRegistry::get('db');

/** @var CommonService $general */
$general = ContainerRegistry::get(CommonService::class);

$tableName1 = "batch_details";
$tableName2 = "form_generic";
try {
    if (isset($_POST['batchCode']) && trim($_POST['batchCode']) != "") {
        $id = intval($_POST['batchId']);
        $data = array(
            'batch_code' => $_POST['batchCode'],
            'position_type' => $_POST['positions'],
            'machine' => $_POST['machine']
        );
        $db = $db->where('batch_id', $id);
        $db->update($tableName1, $data);
        if ($id > 0) {
            $value = array('sample_batch_id' => null);
            $db = $db->where('sample_batch_id', $id);
            $db->update($tableName2, $value);
            $xplodResultSample = [];
            // echo '<pre>'; print_r($_POST['selectedSample']); die;
            if (isset($_POST['selectedSample']) && trim($_POST['selectedSample']) != "") {
                $xplodResultSample = explode(",", $_POST['selectedSample']);
            }
            $sample = [];
            //Mergeing disabled samples into existing samples
            if (isset($_POST['sampleCode']) && !empty($_POST['sampleCode'])) {
                if (count($xplodResultSample) > 0) {
                    $sample = array_unique(array_merge($_POST['sampleCode'], $xplodResultSample));
                } else {
                    $sample = $_POST['sampleCode'];
                }
            } elseif (count($xplodResultSample) > 0) {
                $sample = $xplodResultSample;
            }

            for ($j = 0; $j < count($sample); $j++) {
                $value = array('sample_batch_id' => $id);
                $db = $db->where('sample_id', $sample[$j]);
                $db->update($tableName2, $value);
            }
            //Update batch controls position, If samples has changed
            $displaySampleOrderArray = [];
            $batchQuery = "SELECT * from batch_details as b_d INNER JOIN instruments as i_c ON i_c.config_id=b_d.machine where batch_id=$id";
            $batchInfo = $db->query($batchQuery);
            if (isset($batchInfo) && !empty($batchInfo)) {

                if (isset($batchInfo[0]['position_type']) && $batchInfo[0]['position_type'] == 'alpha-numeric') {
                    foreach ($general->excelColumnRange('A', 'H') as $value) {
                        foreach (range(1, 12) as $no) {
                            $alphaNumeric[] = $value . $no;
                        }
                    }
                }
                if (isset($batchInfo[0]['label_order']) && trim($batchInfo[0]['label_order']) != '') {
                    //Get display sample only
                    $samplesQuery = "SELECT sample_id,sample_code from form_generic where sample_batch_id=$id ORDER BY sample_code ASC";
                    $samplesInfo = $db->query($samplesQuery);
                    foreach ($samplesInfo as $sample) {
                        $displaySampleOrderArray[] = $sample['sample_id'];
                    }
                    //Set label order
                    $jsonToArray = json_decode($batchInfo[0]['label_order'], true);
                    $displaySampleArray = [];
                    if (isset($batchInfo[0]['position_type']) && $batchInfo[0]['position_type'] == 'alpha-numeric') {
                        $displayOrder = [];
                        for ($j = 0; $j < count($jsonToArray); $j++) {
                            $xplodJsonToArray = explode("_", $jsonToArray[$alphaNumeric[$j]]);
                            if (count($xplodJsonToArray) > 1 && $xplodJsonToArray[0] == "s") {
                                if (in_array($xplodJsonToArray[1], $displaySampleOrderArray)) {
                                    $displayOrder[] = $jsonToArray[$alphaNumeric[$j]];
                                    $displaySampleArray[] = $xplodJsonToArray[1];
                                }
                            } else {
                                $displayOrder[] = $jsonToArray[$alphaNumeric[$j]];
                            }
                        }
                    } else {
                        $displayOrder = [];
                        for ($j = 0; $j < count($jsonToArray); $j++) {
                            $xplodJsonToArray = explode("_", $jsonToArray[$j]);
                            if (count($xplodJsonToArray) > 1 && $xplodJsonToArray[0] == "s") {
                                if (in_array($xplodJsonToArray[1], $displaySampleOrderArray)) {
                                    $displayOrder[] = $jsonToArray[$j];
                                    $displaySampleArray[] = $xplodJsonToArray[1];
                                }
                            } else {
                                $displayOrder[] = $jsonToArray[$j];
                            }
                        }
                    }
                    $remainSampleNewArray = array_values(array_diff($displaySampleOrderArray, $displaySampleArray));
                    //For new samples
                    for ($ns = 0; $ns < count($remainSampleNewArray); $ns++) {
                        $displayOrder[] = 's_' . $remainSampleNewArray[$ns];
                    }
                    $orderArray = [];
                    if (isset($batchInfo[0]['position_type']) && $batchInfo[0]['position_type'] == 'alpha-numeric') {
                        for ($o = 0; $o < count($displayOrder); $o++) {
                            $orderArray[$alphaNumeric[$o]] = $displayOrder[$o];
                        }
                    } else {
                        for ($o = 0; $o < count($displayOrder); $o++) {
                            $orderArray[$o] = $displayOrder[$o];
                        }
                    }
                    $labelOrder = json_encode($orderArray, JSON_FORCE_OBJECT);
                    //Update label order
                    $data = array('label_order' => $labelOrder);
                    $db = $db->where('batch_id', $id);
                    $db->update($tableName1, $data);
                }
            }
            $_SESSION['alertMsg'] = "Batch code updated successfully";
        }
    }
    header("Location:batch-code.php");
} catch (Exception $exc) {
    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
}
