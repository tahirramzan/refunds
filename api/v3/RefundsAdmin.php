<?php

function _civicrm_api3_refunds_admin_create_spec(&$spec) {
    $spec['primary_contact_id']['api.required'] = 1;
    $spec['title']['api.required'] = 1;
    $spec['status_id']['api.required'] = 1;
}

function civicrm_api3_refunds_admin_create($params) {
    $refundsAdminBAO = CRM_Refunds_BAO_Refunds_Admin::create($params);
    _civicrm_api3_object_to_array($refundsAdminBAO, $refundsAdminArray[$refundsAdminBAO->id]);
    return civicrm_api3_create_success($refundsAdminArray, $params, 'refundsAdmin', 'create');
}

function civicrm_api3_refunds_admin_get($params) {
    return _civicrm_api3_basic_get(_civicrm_api3_get_BAO(__FUNCTION__), $params);
}

function civicrm_api3_refunds_admin_delete($params) {
    if (CRM_Refunds_BAO_Refunds::del($params['id'])) {
        return civicrm_api3_create_success($params, $params, 'refundsAdmin', 'delete');
    } else {
        return civicrm_api3_create_error('Could not delete.');
    }
}
