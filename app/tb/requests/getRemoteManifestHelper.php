<?php

use App\Registries\ContainerRegistry;
use App\Services\CommonService;

/** @var MysqliDb $db */
$db = ContainerRegistry::get('db');

/** @var CommonService $general */
$general = ContainerRegistry::get(CommonService::class);

// Sanitized values from $request object
/** @var Laminas\Diactoros\ServerRequest $request */
$request = $GLOBALS['request'];
$_POST = $request->getParsedBody();

$sampleData = [];
$sampleQuery = "SELECT tb_id FROM form_tb
                WHERE sample_code IS NULL
                AND (sample_package_code LIKE ? OR remote_sample_code LIKE ?)";
$sampleResult = $db->rawQuery($sampleQuery, [$_POST['samplePackageCode'], $_POST['samplePackageCode']]);
$sampleData = array_column($sampleResult, 'tb_id');
echo implode(',', $sampleData);
