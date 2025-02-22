<?php

use App\Registries\ContainerRegistry;
use App\Services\CommonService;
use App\Utilities\DateUtility;


/** @var MysqliDb $db */
$db = ContainerRegistry::get('db');

/** @var CommonService $general */
$general = ContainerRegistry::get(CommonService::class);

$tableName1 = "batch_details";
$tableName2 = "form_eid";
try {
    $exist = $general->existBatchCode($_POST['batchCode']);
    if ($exist) {
        $_SESSION['alertMsg'] = "Something went wrong. Please try again later.";
        header("Location:eid-batches.php");
    } else {

        if (isset($_POST['batchCode']) && trim($_POST['batchCode']) != "") {
            $data = array(
                'machine' => $_POST['platform'],
                'batch_code' => $_POST['batchCode'],
                'batch_code_key' => $_POST['batchCodeKey'],
                'position_type' => $_POST['positions'],
                'test_type' => 'eid',
                'created_by' => $_SESSION['userId'],
                'request_created_datetime' => DateUtility::getCurrentDateTime()
            );

            $db->insert($tableName1, $data);
            $lastId = $db->getInsertId();

            if ($lastId > 0 && trim($_POST['selectedSample']) != '') {
                $selectedSample = explode(",", $_POST['selectedSample']);
                $uniqueSampleId = array_unique($selectedSample);
                for ($j = 0; $j <= count($selectedSample); $j++) {
                    if (isset($uniqueSampleId[$j])) {
                        $vlSampleId = $uniqueSampleId[$j];
                        $value = array('sample_batch_id' => $lastId);
                        $db = $db->where('eid_id', $vlSampleId);
                        $db->update($tableName2, $value);
                    }
                }
                header("Location:eid-add-batch-position.php?id=" . base64_encode($lastId) . "&position=" . $_POST['positions']);
            }
        } else {
            header("Location:eid-batches.php");
        }
    }
} catch (Exception $exc) {
    echo ($exc->getMessage());
    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
}
