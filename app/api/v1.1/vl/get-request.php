<?php
// Allow from any origin

use App\Exceptions\SystemException;
use App\Services\ApiService;
use App\Services\FacilitiesService;
use App\Registries\ContainerRegistry;
use App\Services\CommonService;
use App\Services\UsersService;

session_unset(); // no need of session in json response

ini_set('memory_limit', -1);


/** @var Slim\Psr7\Request $request */
$request = $GLOBALS['request'];

$origJson = (string) $request->getBody();
$input = $request->getParsedBody();

/** @var MysqliDb $db */
$db = ContainerRegistry::get('db');

/** @var CommonService $general */
$general = ContainerRegistry::get(CommonService::class);

/** @var UsersService $usersService */
$usersService = ContainerRegistry::get(UsersService::class);

/** @var FacilitiesService $facilitiesService */
$facilitiesService = ContainerRegistry::get(FacilitiesService::class);

/** @var ApiService $app */
$app = ContainerRegistry::get(ApiService::class);

$transactionId = $general->generateUUID();
$arr = $general->getGlobalConfig();
$user = null;

/* For API Tracking params */
$requestUrl = $_SERVER['HTTP_HOST'];
$requestUrl .= $_SERVER['REQUEST_URI'];
$authToken = $general->getAuthorizationBearerToken();
$user = $usersService->getUserByToken($authToken);
try {
    $sQuery = "SELECT
            vl.app_sample_code                                   as appSampleCode,
            vl.unique_id                                         as uniqueId,
            vl.vl_sample_id                                      as vlSampleId,
            vl.sample_code                                       as sampleCode,
            vl.remote_sample_code                                as remoteSampleCode,
            vl.vlsm_instance_id                                  as instanceId,
            vl.vlsm_country_id                                   as formId,
            vl.facility_id                                       as facilityId,
            vl.lab_id                                            as labId,
            vl.implementing_partner                              as implementingPartner,
            vl.funding_source                                    as fundingSource,
            vl.sample_collection_date                            as sampleCollectionDate,
            vl.patient_first_name                                as patientFirstName,
            vl.patient_last_name                                 as patientLastName,
            vl.patient_gender                                    as gender,
            vl.patient_gender                                    as patientGender,
            vl.patient_dob                                       as dob,
            vl.patient_dob                                       as patientDob,
            vl.patient_age_in_years                              as ageInYears,
            vl.patient_age_in_months                             as ageInMonths,
            vl.is_patient_pregnant                               as patientPregnant,
            vl.is_patient_breastfeeding                          as breastfeeding,
            vl.patient_art_no                                    as artNo,
            vl.patient_art_no                                    as patientArtNo,
            vl.treatment_initiated_date                          as dateOfArtInitiation,
            vl.regimen_change_date                               as dateOfArvRegimenChange,
            vl.reason_for_regimen_change                         as reasonForArvRegimenChange,
            vl.current_regimen                                   as artRegimen,
            vl.date_of_initiation_of_current_regimen             as regimenInitiatedOn,
            vl.patient_mobile_number                             as patientPhoneNumber,
            vl.consent_to_receive_sms                            as receiveSms,
            vl.sample_type                                       as specimenType,
            vl.arv_adherance_percentage                          as arvAdherence,
            vl.reason_for_vl_testing                             as reasonForVLTesting,
            vl.community_sample                                  as communitySample,
            vl.last_vl_date_routine                              as rmTestingLastVLDate,
            vl.last_vl_result_routine                            as rmTestingVlValue,
            vl.last_vl_date_failure_ac                           as repeatTestingLastVLDate,
            vl.last_vl_result_failure_ac                         as repeatTestingVlValue,
            vl.last_vl_date_failure                              as suspendTreatmentLastVLDate,
            vl.last_vl_result_failure                            as suspendTreatmentVlValue,
            vl.request_clinician_name                            as reqClinician,
            vl.request_clinician_phone_number                    as reqClinicianPhoneNumber,
            vl.test_requested_on                                 as requestDate,
            vl.vl_focal_person                                   as vlFocalPerson,
            vl.vl_focal_person_phone_number                      as vlFocalPersonPhoneNumber,
            vl.lab_id                                            as labId,
            vl.vl_test_platform                                  as testingPlatform,
            vl.sample_received_at_hub_datetime                   as sampleReceivedAtHubOn,
            vl.sample_received_at_vl_lab_datetime                as sampleReceivedDate,
            vl.sample_tested_datetime                            as sampleTestingDateAtLab,
            vl.sample_dispatched_datetime                        as sampleDispatchedOn,
            vl.result_dispatched_datetime                        as resultDispatchedOn,
            vl.result_value_hiv_detection                        as hivDetection,
            vl.reason_for_failure                                as reasonForFailure,
            vl.reason_for_sample_rejection                       as rejectionReasonId,
            vl.rejection_on                                      as rejectionDate,
            vl.result_value_absolute                             as vlResult,
            vl.result_value_absolute_decimal                     as vlResultDecimal,
            vl.result                                            as result,
            vl.result_value_log                                  as vlLog,
            vl.tested_by                                         as testedBy,
            vl.result_approved_by                                as approvedBy,
            vl.lab_tech_comments                                 as labComments,
            vl.result_status                                     as resultStatus,
            vl.funding_source                                    as fundingSource,
            vl.implementing_partner                              as implementingPartner,
            vl.request_created_datetime                          as requestCreatedDatetime,
            vl.last_modified_datetime                            as lastModifiedDatetime,
            vl.manual_result_entry                               as manualResultEntry,
            vl.vl_result_category                                as vlResultCategory,
            l_f.facility_name                                    as labName,
            f.facility_district                                  as district,
            u_d.user_name                                        as reviewedBy,
            lt_u_d.user_name                                     as labTechnicianName,
            t_b.user_name                                        as testedByName,
            rs.rejection_reason_name                             as rejectionReason,
            g.geo_id                                             as provinceId,
            g.geo_name                                           as provinceName,
            r_f_s.funding_source_name                            as fundingSourceName,
            r_i_p.i_partner_name                                 as implementingPartnerName,
            ts.status_name                                       as resultStatusName,
            f.facility_district_id                               as districtId,
            f.facility_name                                      as facilityName,
            vl.is_sample_rejected                                as isSampleRejected,
            vl.result_approved_datetime                          as approvedOn,
            vl.revised_by                                        as revisedBy,
            r_r_b.user_name                                      as revisedByName,
            vl.revised_on                                        as revisedOn,
            vl.external_sample_code                              as serialNo,
            vl.is_patient_new                                    as isPatientNew,
            vl.has_patient_changed_regimen                       as hasChangedRegimen,
            vl.sample_dispatched_datetime                as dateDispatchedFromClinicToLab,
            vl.vl_test_number                                    as viralLoadNo,
            vl.last_viral_load_result                            as lastViralLoadResult,
            vl.last_viral_load_date                              as lastViralLoadTestDate,
            vl.facility_support_partner                          as facilitySupportPartner,
            vl.date_test_ordered_by_physician                    as dateOfDemand,
            vl.result_reviewed_by                                as reviewedBy,
            vl.result_reviewed_datetime                          as reviewedOn,
            vl.reason_for_vl_result_changes                      as reasonForVlResultChanges

            FROM form_vl as vl
            LEFT JOIN geographical_divisions as g ON vl.province_id=g.geo_id
            LEFT JOIN facility_details as f ON vl.facility_id=f.facility_id
            LEFT JOIN facility_details as l_f ON vl.lab_id=l_f.facility_id
            LEFT JOIN r_sample_status as ts ON ts.status_id=vl.result_status
            LEFT JOIN batch_details as b ON b.batch_id=vl.sample_batch_id
            LEFT JOIN user_details as u_d ON u_d.user_id=vl.result_reviewed_by
            LEFT JOIN user_details as a_u_d ON a_u_d.user_id=vl.result_approved_by
            LEFT JOIN user_details as r_r_b ON r_r_b.user_id=vl.revised_by
            LEFT JOIN user_details as lt_u_d ON lt_u_d.user_id=vl.lab_technician
            LEFT JOIN user_details as t_b ON t_b.user_id=vl.tested_by
            LEFT JOIN r_vl_test_reasons as rtr ON rtr.test_reason_id=vl.reason_for_vl_testing
            LEFT JOIN r_vl_sample_type as rst ON rst.sample_id=vl.sample_type
            LEFT JOIN r_vl_sample_rejection_reasons as rs ON rs.rejection_reason_id=vl.reason_for_sample_rejection
            LEFT JOIN r_funding_sources as r_f_s ON r_f_s.funding_source_id=vl.funding_source
            LEFT JOIN r_implementation_partners as r_i_p ON r_i_p.i_partner_id=vl.implementing_partner";


    $where = [];
    if (!empty($user)) {
        $facilityMap = $facilitiesService->getUserFacilityMap($user['user_id'], 1);
        if (!empty($facilityMap)) {
            $where[] = " vl.facility_id IN (" . $facilityMap . ")";
        } else {
            $where[] = " (request_created_by = '" . $user['user_id'] . "')";
        }
    }
    /* To check the sample code filter */

    if (!empty($input['sampleCode'])) {
        $sampleCode = $input['sampleCode'];
        $sampleCode = implode("','", $sampleCode);
        $where[] = " (sample_code IN ('$sampleCode') OR remote_sample_code IN ('$sampleCode') )";
    }
    /* To check the facility and date range filter */
    if (!empty($input['sampleCollectionDate'])) {
        $from = $input['sampleCollectionDate'][0];
        $to = $input['sampleCollectionDate'][1];
        if (!empty($from) && !empty($to)) {
            $where[] = " DATE(sample_collection_date) between '$from' AND '$to' ";
        }
    }

    if (!empty($input['facility'])) {
        $facilityId = implode("','", $input['facility']);
        $where[] = " vl.facility_id IN ('$facilityId') ";
    }

    if (!empty($input['patientArtNo'])) {
        $patientArtNo = implode("','", $input['patientArtNo']);
        $where[] = " vl.patient_art_no IN ('" . $patientArtNo . "') ";
    }

    if (!empty($input['patientName'])) {
        $where[] = " CONCAT(COALESCE(vl.patient_first_name,''), COALESCE(vl.patient_last_name,'')) like '%" . $input['patientName'] . "%'";
    }

    $sampleStatus = $input['sampleStatus'] ?? [];
    if (!empty($sampleStatus)) {
        $sampleStatus = implode("','", $sampleStatus);
        $where[] = " result_status IN ('$sampleStatus') ";
    }
    $where[] = " vl.app_sample_code is not null";
    $where = " WHERE " . implode(" AND ", $where);
    $sQuery .= $where . " ORDER BY last_modified_datetime DESC limit 100;";
    // // die($sQuery);
    $rowData = $db->rawQuery($sQuery);

    http_response_code(200);
    $payload = [
        'status' => 'success',
        'timestamp' => time(),
        'data' => $rowData ?? []
    ];
} catch (SystemException $exc) {
    // http_response_code(500);
    $payload = [
        'status' => 'failed',
        'timestamp' => time(),
        'error' => $exc->getMessage(),
        'data' => []
    ];
    error_log($exc->getMessage());
    error_log($exc->getTraceAsString());
}
$payload = json_encode($payload);
$general->addApiTracking($transactionId, $user['user_id'], count($rowData), 'get-request', 'vl', $_SERVER['REQUEST_URI'], $origJson, $payload, 'json');
echo $payload;
