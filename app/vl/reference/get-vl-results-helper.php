<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


$tableName = "r_vl_results";
$primaryKey = "result_id";
//system config
$systemConfigQuery = "SELECT * from system_config";
$systemConfigResult = $db->query($systemConfigQuery);
$sarr = [];
// now we create an associative array so that we can easily create view variables
for ($i = 0; $i < sizeof($systemConfigResult); $i++) {
    $sarr[$systemConfigResult[$i]['name']] = $systemConfigResult[$i]['value'];
}
/* Array of database columns which should be read and sent back to DataTables. Use a space where
         * you want to insert a non-database field (for example a counter or static image)
        */

$aColumns = array('result', 'status');

/* Indexed column (used for fast and accurate table cardinality) */
$sIndexColumn = $primaryKey;

$sTable = $tableName;
/*
         * Paging
         */
$sLimit = "";
if (isset($_POST['iDisplayStart']) && $_POST['iDisplayLength'] != '-1') {
    $sOffset = $_POST['iDisplayStart'];
    $sLimit = $_POST['iDisplayLength'];
}

/*
         * Ordering
        */

$sOrder = "";
if (isset($_POST['iSortCol_0'])) {
    $sOrder = "";
    for ($i = 0; $i < intval($_POST['iSortingCols']); $i++) {
        if ($_POST['bSortable_' . intval($_POST['iSortCol_' . $i])] == "true") {
            $sOrder .= $aColumns[intval($_POST['iSortCol_' . $i])] . "
				 	" . ($_POST['sSortDir_' . $i]) . ", ";
        }
    }
    $sOrder = substr_replace($sOrder, "", -2);
}

/*
         * Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
        */

$sWhere = "";
if (isset($_POST['sSearch']) && $_POST['sSearch'] != "") {
    $searchArray = explode(" ", $_POST['sSearch']);
    $sWhereSub = "";
    foreach ($searchArray as $search) {
        if ($sWhereSub == "") {
            $sWhereSub .= "(";
        } else {
            $sWhereSub .= " AND (";
        }
        $colSize = count($aColumns);

        for ($i = 0; $i < $colSize; $i++) {
            if ($i < $colSize - 1) {
                $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' OR ";
            } else {
                $sWhereSub .= $aColumns[$i] . " LIKE '%" . ($search) . "%' ";
            }
        }
        $sWhereSub .= ")";
    }
    $sWhere .= $sWhereSub;
}

/* Individual column filtering */
for ($i = 0; $i < count($aColumns); $i++) {
    if (isset($_POST['bSearchable_' . $i]) && $_POST['bSearchable_' . $i] == "true" && $_POST['sSearch_' . $i] != '') {
        if ($sWhere == "") {
            $sWhere .= $aColumns[$i] . " LIKE '%" . ($_POST['sSearch_' . $i]) . "%' ";
        } else {
            $sWhere .= " AND " . $aColumns[$i] . " LIKE '%" . ($_POST['sSearch_' . $i]) . "%' ";
        }
    }
}

/*
         * SQL queries
         * Get data to display
        */

$sQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM r_vl_results";

if (isset($sWhere) && !empty($sWhere)) {
    $sWhere = ' where ' . $sWhere;
    $sQuery = $sQuery . ' ' . $sWhere;
}

if (isset($sOrder) && !empty($sOrder)) {
    $sOrder = preg_replace('/(\v|\s)+/', ' ', $sOrder);
    $sQuery = $sQuery . ' order by ' . $sOrder;
}

if (isset($sLimit) && isset($sOffset)) {
    $sQuery = $sQuery . ' LIMIT ' . $sOffset . ',' . $sLimit;
}

$rResult = $db->rawQuery($sQuery);

/* Data set length after filtering */
$aResultFilterTotal = $db->rawQueryOne("SELECT FOUND_ROWS() as `totalCount`");
$iTotal = $iFilteredTotal = $aResultFilterTotal['totalCount'];

/*
         * Output
        */
$output = array(
    "sEcho" => intval($_POST['sEcho']),
    "iTotalRecords" => $iTotal,
    "iTotalDisplayRecords" => $iFilteredTotal,
    "aaData" => array()
);
foreach ($rResult as $aRow) {
    $instruments = json_decode($aRow['available_for_instruments']);
    $idValues = implode(',', $instruments);
    $sqlInstrument = "SELECT group_concat(machine_name) as machine_name from instruments where config_id in ($idValues)";
    $instrumentRes = $db->rawQuery($sqlInstrument);
    $status = '<select class="form-control" name="status[]" id="' . $aRow['result_id'] . '" title="' . _("Please select status") . '" onchange="updateStatus(this,\'' . $aRow['status'] . '\')">
               <option value="active" ' . ($aRow['status'] == "active" ? "selected=selected" : "") . '>' . _("Active") . '</option>
               <option value="inactive" ' . ($aRow['status'] == "inactive"  ? "selected=selected" : "") . '>' . _("Inactive") . '</option>
               </select><br><br>';
    $row = [];
    $row[] = ($aRow['result']);
    $row[] = $instrumentRes[0]['machine_name'];

    if (isset($_SESSION['privileges']) && in_array("vl-results.php", $_SESSION['privileges'])) {
        $row[] = $status;
        $row[] = '<a href="edit-vl-results.php?id=' . base64_encode($aRow['result_id']) . '" class="btn btn-primary btn-xs" style="margin-right: 2px;" title="' . _("Edit") . '"><em class="fa-solid fa-pen-to-square"></em> ' . _("Edit") . '</em></a>';
    } else {
        $row[] = ($aRow['status']);
    }

    $output['aaData'][] = $row;
}

echo json_encode($output);
