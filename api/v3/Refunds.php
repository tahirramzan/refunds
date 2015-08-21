<?php

function _civicrm_api3_refunds_create_spec(&$spec) {
    $spec['primary_contact_id']['api.required'] = 1;
    $spec['title']['api.required'] = 1;
    $spec['status_id']['api.required'] = 1;
}

function civicrm_api3_refunds_create($params) {
    $refundsBAO = CRM_Refunds_BAO_Refunds::create($params);
    _civicrm_api3_object_to_array($refundsBAO, $refundsArray[$refundsBAO->id]);
    return civicrm_api3_create_success($refundsArray, $params, 'refunds', 'create');
}

function civicrm_api3_refunds_get($params) {
    return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

function civicrm_api3_refunds_delete($params) {
    if (CRM_Refunds_BAO_Refunds::del($params['id'])) {
        return civicrm_api3_create_success($params, $params, 'refunds', 'delete');
    } else {
        return civicrm_api3_create_error('Could not delete.');
    }
}
