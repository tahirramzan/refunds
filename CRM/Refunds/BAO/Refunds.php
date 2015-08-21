<?php

class CRM_Refunds_BAO_Refunds extends CRM_Refunds_DAO_Refunds {

    
  static $_exportableFields = NULL;

  
  static function create(&$params) {
    $refundsDAO = new CRM_Refunds_DAO_Refunds();
    $refundsDAO->copyValues($params);
    return $refundsDAO->save();
  }


  static function recordContribution($values){
    $refundsID = CRM_Utils_Array::value('refunds_id', $values);
    if(!CRM_Utils_Array::value('refunds_id', $values)){
      return;
    }else{
      try{
       $transaction = new CRM_Core_Transaction();
       $params = array(
          'version' => 3,
          'sequential' => 1,
          'contact_id' => CRM_Utils_Array::value('payment_contact', $values),
          'financial_type_id' => CRM_Utils_Array::value('financial_type_id', $values),
          'total_amount' =>  CRM_Utils_Array::value('total_amount', $values),
          'payment_instrument_id' =>  CRM_Utils_Array::value('payment_instrument_id', $values),
          'receive_date' =>  CRM_Utils_Array::value('receive_date', $values),
          'contribution_status_id' =>  CRM_Utils_Array::value('contribution_status_id', $values),
          'source' => CRM_Utils_Array::value('refunds_title', $values),
          'trxn_id' =>  CRM_Utils_Array::value('trxn_id', $values),
        );
        $contribution = civicrm_api('Contribution', 'create', $params);
        $contributionId = CRM_Utils_Array::value('id', $contribution);
        if($contributionId){
          $payment = array('refunds_id' => $refundsID, 'contribution_id' => $contributionId);
          CRM_Refunds_BAO_Payment::create($payment);
        }

        $result = civicrm_api('Slot', 'get', array('version' => 3, 'refunds_id' => $refundsID));
        $slots = CRM_Utils_Array::value('values', $result);
        $lineItem = array('version' => 3, 'financial_type_id' => CRM_Utils_Array::value('financial_type_id', $values));
        foreach ($slots as $slot) {
          $slotID = $slot['id'];
          $configId =  CRM_Utils_Array::value('config_id', $slot);
          $configResult = civicrm_api('ResourceConfigOption', 'get', array('version' => 3, 'id' => $configId));
          $config = CRM_Utils_Array::value('values', $configResult);
          $unitPrice = CRM_Utils_Array::value('price', $config[$configId]);
          $qty = CRM_Utils_Array::value('quantity', $slot);

          $lineItem['label'] = CRM_Utils_Array::value('label', $config[$configId]);
          $lineItem['entity_table'] = "civicrm_refunds_slot";
          $lineItem['entity_id'] = $slotID;
          $lineItem['qty'] = $qty;
          $lineItem['unit_price'] = $unitPrice;
          $lineItem['line_total'] =   self::_calLineTotal($unitPrice, $qty);
          $lineItemResult = civicrm_api('LineItem', 'create', $lineItem);
          $result = civicrm_api('SubSlot', 'get', array('version' => 3 ,'slot_id' => $slotID));
          $subSlots = CRM_Utils_Array::value('values', $result);
          foreach ($subSlots as $subSlot) {
            $subSlotID = $subSlot['id'];
            $configId =  CRM_Utils_Array::value('config_id', $subSlot);
            $configResult = civicrm_api('ResourceConfigOption', 'get', array('version' => 3, 'id' => $configId));
            $config = CRM_Utils_Array::value('values', $configResult);
            $unitPrice = CRM_Utils_Array::value('price', $config[$configId]);
            $qty = CRM_Utils_Array::value('quantity', $subSlot);

            $lineItem['label'] = CRM_Utils_Array::value('label', $config[$configId]);
            $lineItem['entity_table'] = "civicrm_refunds_sub_slot";
            $lineItem['entity_id'] = $subSlotID;
            $lineItem['qty'] = $qty;
            $lineItem['unit_price'] = $unitPrice;
            $lineItem['line_total'] =  self::_calLineTotal($unitPrice, $qty);
            $lineItemResult = civicrm_api('LineItem', 'create', $lineItem);
          }
        }

        $adhocChargesResult = civicrm_api('AdhocCharges', 'get', array('version' => 3, 'refunds_id' => $refundsID));
        $adhocChargesValues = CRM_Utils_Array::value('values', $adhocChargesResult);
        foreach ($adhocChargesValues as $id => $adhocCharges) {

          $itemId =  CRM_Utils_Array::value('item_id', $adhocCharges);
          $itemResult = civicrm_api('AdhocChargesItem', 'get', array('version' => 3, 'id' => $itemId));
          $itemValue = CRM_Utils_Array::value('values', $itemResult);
          $unitPrice = CRM_Utils_Array::value('price', $itemValue[$itemId]);
          $qty = CRM_Utils_Array::value('quantity', $adhocCharges);

          $lineItem['entity_table'] = "civicrm_refunds_adhoc_charges";
          $lineItem['entity_id'] = $id;
          $lineItem['unit_price'] = $unitPrice;
          $lineItem['qty'] = $qty;
          $lineItem['label'] = CRM_Utils_Array::value('label', $itemValue[$itemId]);
          $lineItem['line_total'] = self::_calLineTotal($unitPrice, $qty);
          $lineItemResult = civicrm_api('LineItem', 'create', $lineItem);
        }

        
      }catch (Exception $e) {
          $transaction->rollback();
          CRM_Core_Error::fatal($e->getMessage());
      }
    }

  }

  static function _calLineTotal($unitPrice, $qty){
    return $unitPrice * $qty;
  }

  
  static function retrieve(&$params, &$defaults) {
    $dao = new CRM_Refunds_DAO_Refunds();
    $dao->copyValues($params);
    if ($dao->find(TRUE)) {
      CRM_Core_DAO::storeValues($dao, $defaults);
      return $dao;
    }
    return NULL;
  }


  static function getRefundsDetails($id){
    $slots = CRM_Refunds_BAO_Slot::getRefundsSlot($id);
    $subSlots = array();
    foreach ($slots as $key => $slot) {
      $label =  CRM_Core_DAO::getFieldValue('CRM_Refunds_DAO_Resource',
        $slot['resource_id'],
        'label',
        'id'
      );

      $slots[$key]['resource_label'] = $label;
      $slots[$key]['config_label'] = CRM_Core_DAO::getFieldValue('CRM_Refunds_DAO_ResourceConfigOption',
        $slot['config_id'],
        'label',
        'id'
      );
      $params = array(
          'version' => 3,
          'entity_id' => $slot['id'],
          'entity_table' => 'civicrm_refunds_slot',
        );
      
        $slots[$key]['total_amount'] = CRM_Refunds_BAO_Slot::calulatePrice($slot['config_id'], $slot['quantity']);
        $slots[$key]['unit_price'] = CRM_Core_DAO::getFieldValue(
            'CRM_Refunds_DAO_ResourceConfigOption',
            $slot['config_id'],
            'price',
            'id'
        );
      
      $childSlots = CRM_Refunds_BAO_SubSlot::getSubSlotSlot($key);
      foreach ($childSlots as $key => $subSlot) {
        $subSlot['resource_label'] = CRM_Core_DAO::getFieldValue('CRM_Refunds_DAO_Resource',
          $subSlot['resource_id'],
          'label',
          'id'
        );
        $subSlot['config_label'] = CRM_Core_DAO::getFieldValue('CRM_Refunds_DAO_ResourceConfigOption',
          $subSlot['config_id'],
          'label',
          'id'
        );
        $params = array(
          'version' => 3,
          'entity_id' => $subSlot['id'],
          'entity_table' => 'civicrm_refunds_sub_slot',
        );
          $subSlot['total_amount'] = CRM_Refunds_BAO_Slot::calulatePrice($subSlot['config_id'], $subSlot['quantity']);
          $subSlot['unit_price'] = CRM_Core_DAO::getFieldValue(
            'CRM_Refunds_DAO_ResourceConfigOption',
            $subSlot['config_id'],
            'price',
            'id'
          );
        

        $subSlot['parent_resource_label'] =  $label;
        $subSlots[$subSlot['id']] = $subSlot;
      }
    }
    //get adhoc charges
    $adhocCharges = array();
    $adhocChargesResult = civicrm_api3('AdhocCharges', 'get', array('refunds_id' => $id , 'is_deleted' => 0));
    $adhocChargesValues = CRM_Utils_Array::value('values', $adhocChargesResult);
    foreach ($adhocChargesValues as $kc => $charges) {
        $charges['item_label'] = CRM_Core_DAO::getFieldValue('CRM_Refunds_DAO_AdhocChargesItem',
          $charges['item_id'],
          'label',
          'id'
        );
        $params = array(
          'entity_id' => $charges['id'],
          'entity_table' => 'civicrm_refunds_adhoc_charges',
        );
        $result = civicrm_api3('LineItem', 'get', $params);	//get LineItem record wheather the refunds has contribution or not.
        if(!empty($result['values'])){
          $chargesLineItem = CRM_Utils_Array::value($result['id'], $result['values']);
          $charges['unit_price'] = CRM_Utils_Array::value('unit_price', $chargesLineItem);
          $charges['total_amount'] = CRM_Utils_Array::value('line_total', $chargesLineItem);
          $charges['quantity'] = CRM_Utils_Array::value('qty', $chargesLineItem);
        }else{ //calulate manually
          $charges['unit_price'] = CRM_Core_DAO::getFieldValue(
            'CRM_Refunds_DAO_AdhocChargesItem',
            $charges['item_id'],
            'price',
            'id'
          );
          $charges['total_amount'] = $charges['unit_price'] * $charges['quantity'];
        }
        $adhocCharges[$kc] = $charges;
    }

    $cancellationCharges = array();
  	$cancellationsResult = civicrm_api3('Cancellation','get',array('refunds_id' => $id));
	  $cancellationsValues = CRM_Utils_Array::value('values',$cancellationsResult);
	  foreach ($cancellationsValues as $key => $cancels) {

		  $params = array(
        'entity_id' => $cancels['id'],
        'entity_table' => 'civicrm_refunds_cancellation',
      );
      $lineItemResult = civicrm_api3('LineItem', 'get', $params);	
      if(!empty($lineItemResult['values'])){
        $cancelsLineItem = CRM_Utils_Array::value($lineItemResult['id'], $lineItemResult['values']);

      }else{ 
        $cancels['total_fee'] = $cancels['cancellation_fee'] + $cancels['additional_fee'];
      }
		  //get refunds price
		  $params = array('refunds_id' => $cancels['refunds_id']);
		  $refundsItem = civicrm_api3('Refunds','get',$params);
		  foreach (CRM_Utils_Array::value('values',$refundsItem) as $k => $v) {
			  $cancels['refunds_price'] = CRM_Utils_Array::value('total_amount',$v);
        $cancels['event_date'] = CRM_Utils_Array::value('start_date',$v);
		  }

      //calculate the total amount of cancellation charge
      $cancels['cancellation_total_fee'] = $cancels['cancellation_fee'];
	  $cancels['cancellation_fee'] = $cancels['cancellation_fee'] - $cancels['additional_fee'];

      //calculate how many days before event date
      $cancellation_date = new DateTime($cancels['cancellation_date']);
      $eventDate = new DateTime($cancels['event_date']);
      $interval = $cancellation_date->diff($eventDate);
      $cancels['prior_days'] = $interval->days;

      $cancellationCharges[$key] = $cancels;
	  }
	  //get contribution
    $contribution = array();
    $refundsPaymentResult = civicrm_api3('RefundsPayment','get',array('refunds_id' => $id));
    $refundsPaymentValues = CRM_Utils_Array::value('values',$refundsPaymentResult); //get contribution id from refunds_payment

    foreach ($refundsPaymentValues as $key => $bpValues) {
        $contributionResult = civicrm_api3('Contribution','get',array('id' => $bpValues['contribution_id']));   //get contribution record
        $contributionValues = CRM_Utils_Array::value('values',$contributionResult);
        foreach ($contributionValues as $k => $conValues) {
            $contribution[$k] = $conValues;
        }
    }

    return array(
      'slots' => $slots,
      'sub_slots' => $subSlots,
      'adhoc_charges' => $adhocCharges,
      'cancellation_charges' =>$cancellationCharges,
      'contribution' => $contribution);
  }

  /**
   * Function to delete Refunds
   *
   * @param  int  $id     Id of the Resource to be deleted.
   *
   * @return boolean
   *
   * @access public
   * @static
   */
  static function del($id) {
    $transaction = new CRM_Core_Transaction();
    try{
      $slots = CRM_Refunds_BAO_Slot::getRefundsSlot($id);
        foreach ($slots as $slotId => $slots) {
          $subSlots = CRM_Refunds_BAO_SubSlot::getSubSlotSlot($slotId);
          foreach ($subSlots as $subSlotId => $subSlot) {
          CRM_Refunds_BAO_SubSlot::del($subSlotId);
        }
        CRM_Refunds_BAO_Slot::del($slotId);
      }
      $dao = new CRM_Refunds_DAO_Refunds();
      $dao->id = $id;
      $dao->is_deleted = 1;
      return $dao->save();

    }catch (Exception $e) {
          $transaction->rollback();
          CRM_Core_Error::fatal($e->getMessage());
    }
  }

  
  static function getValues(&$params, &$values, &$ids) {
    if (empty($params)) {
      return NULL;
    }
    $refunds = new CRM_Refunds_DAO_Refunds();
    $refunds->copyValues($params);
    $refunds->find();
    $refundss = array();
    while ($refunds->fetch()) {
      $ids['refunds'] = $refunds->id;
      CRM_Core_DAO::storeValues($refunds, $values[$refunds->id]);
      $refundss[$refunds->id] = $refunds;
    }
    return $refundss;
  }

  static function getRefundsContactCount($contactId){
    $params = array(1 => array( $contactId, 'Integer'));
    $query = "SELECT COUNT(DISTINCT(id)) AS count
              FROM civicrm_refunds
              WHERE 1
              AND (primary_contact_id = %1 OR secondary_contact_id = %1)
              AND is_deleted = 0 ";
    return CRM_Core_DAO::singleValueQuery($query, $params);
  }

  static function getContactAssociatedRefunds($contactId){

    $params = array(1 => array( $contactId, 'Integer'));

    $query = "SELECT civicrm_refunds.id as id,
                     civicrm_refunds.primary_contact_id,
                     civicrm_contact.display_name as parimary_contact_name,
                     civicrm_refunds.title as title,
                     civicrm_refunds.created_date as created_date,
                     civicrm_refunds.refunds_date as refunds_date,
                     civicrm_refunds.start_date as start_date,
                     civicrm_refunds.end_date as end_date,
                     civicrm_refunds.total_amount as total_amount,
                     payment_status_value.label as payment_status,
                     refunds_status_value.label as refunds_status
              FROM civicrm_refunds
              INNER JOIN civicrm_contact ON civicrm_contact.id = civicrm_refunds.primary_contact_id
              INNER JOIN civicrm_option_group refunds_status_group ON refunds_status_group.name = 'refunds_status'
              INNER JOIN civicrm_option_value refunds_status_value ON refunds_status_value.value = civicrm_refunds.status_id
                                             AND refunds_status_group.id = refunds_status_value.option_group_id
              LEFT JOIN civicrm_refunds_payment ON civicrm_refunds_payment.refunds_id = civicrm_refunds.id
              LEFT JOIN civicrm_contribution ON civicrm_contribution.id = civicrm_refunds_payment.contribution_id
              LEFT JOIN civicrm_option_group payment_status_group ON payment_status_group.name = 'contribution_status'
              LEFT JOIN civicrm_option_value payment_status_value ON payment_status_value.value = civicrm_contribution.contribution_status_id
                                             AND payment_status_group.id = payment_status_value.option_group_id
              WHERE civicrm_refunds.secondary_contact_id = %1
              AND civicrm_refunds.is_deleted = 0";

    $refundss = array();
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $refundss[$dao->id] = array(
        'id' => $dao->id,
        'primary_contact_id' => $dao->primary_contact_id,
        'primary_contact_name' => $dao->parimary_contact_name,
        'title' => $dao->title,
        'created_date' => $dao->created_date,
        'refunds_date' => $dao->refunds_date,
        'start_date' => $dao->start_date,
        'end_date' => $dao->end_date,
        'total_amount' => $dao->total_amount,
        'refunds_payment_status' => $dao->payment_status,
        'refunds_status' => $dao->refunds_status
      );
    }
    return $refundss;
  }

  static function getPaymentStatus($id){
    $params = array(1 => array( $id, 'Integer'));
    $query = "SELECT civicrm_option_value.label as status
              FROM civicrm_refunds
              LEFT JOIN civicrm_refunds_payment ON civicrm_refunds_payment.refunds_id = civicrm_refunds.id
              LEFT JOIN civicrm_contribution ON civicrm_contribution.id = civicrm_refunds_payment.contribution_id
              LEFT JOIN civicrm_option_group ON civicrm_option_group.name = 'contribution_status'
              LEFT JOIN civicrm_option_value ON civicrm_option_value.value = civicrm_contribution.contribution_status_id
                                             AND civicrm_option_group.id = civicrm_option_value.option_group_id
              WHERE civicrm_refunds.id = %1";
    return CRM_Core_DAO::singleValueQuery($query, $params);

  }


   /**
   * Get the values for pseudoconstants for name->value and reverse.
   *
   * @param array   $defaults (reference) the default values, some of which need to be resolved.
   * @param boolean $reverse  true if we want to resolve the values in the reverse direction (value -> name)
   *
   * @return void
   * @access public
   * @static
   */
  static function resolveDefaults(&$defaults, $reverse = FALSE) {
    $statusIds =  CRM_Refunds_BAO_Refunds::buildOptions('status_id', 'create');
    self::lookupValue($defaults, 'status', $statusIds, $reverse);
  }

  /**
   * This function is used to convert associative array names to values
   * and vice-versa.
   *
   * This function is used by both the web form layer and the api. Note that
   * the api needs the name => value conversion, also the view layer typically
   * requires value => name conversion
   */
  static function lookupValue(&$defaults, $property, &$lookup, $reverse) {
    $id = $property . '_id';

    $src = $reverse ? $property : $id;
    $dst = $reverse ? $id : $property;

    if (!array_key_exists($src, $defaults)) {
      return FALSE;
    }

    $look = $reverse ? array_flip($lookup) : $lookup;

    if (is_array($look)) {
      if (!array_key_exists($defaults[$src], $look)) {
        return FALSE;
      }
    }
    $defaults[$dst] = $look[$defaults[$src]];
    return TRUE;
  }


  /**
   * Get the exportable fields for Refunds
   *
   *
   * @return array array of exportable Fields
   * @access public
   * @static
   */
  static function &exportableFields() {
    if (!isset(self::$_exportableFields["refunds"])) {
      self::$_exportableFields["refunds"] = array();

      $exportableFields = CRM_Refunds_DAO_Refunds::export();

      $refundsFields = array(
        'refunds_title' => array('title' => ts('Title'), 'type' => CRM_Utils_Type::T_STRING),
        'refunds_po_no' => array('title' => ts('PO Number'), 'type' => CRM_Utils_Type::T_STRING),
        'refunds_status' => array('title' => ts('Refunds Status'), 'type' => CRM_Utils_Type::T_STRING),
        'refunds_payment_status' => array('title' => ts('Refunds Status'), 'type' => CRM_Utils_Type::T_STRING),
      );

      $fields = array_merge($refundsFields, $exportableFields);

      self::$_exportableFields["refunds"] = $fields;
    }
    return self::$_exportableFields["refunds"];
  }

  /**
   * Get all amount of refunds
   *
   * Remark: The total_amount has been deducted from discount amount.
   */
  static function getRefundsAmount($id){
    if(!$id){
      return;
    }
    $refundsAmount = array(
      'resource_fees' => 0,
      'sub_resource_fees' => 0,
      'adhoc_charges_fees' => 0,
      'discount_amount' => 0,
      'total_amount' => 0,
    );
    $params = array('id' => $id);
    self::retrieve($params, $refunds);

    $refundsAmount['discount_amount'] = CRM_Utils_Array::value('discount_amount', $refunds);
    $refundsAmount['total_amount'] = CRM_Utils_Array::value('total_amount', $refunds);
    $slots = CRM_Refunds_BAO_Slot::getRefundsSlot($id);
    $subSlots = array();
    foreach ($slots as $key => $slot) {
      $subSlotResult = CRM_Refunds_BAO_SubSlot::getSubSlotSlot($slot['id']);
      foreach ($subSlotResult as $key => $subSlot) {
        $subSlots[$key] = $subSlot;
      }
    }
    $adhocCharges = CRM_Refunds_BAO_AdhocCharges::getRefundsAdhocCharges($id);
    $params = array('refunds_id' => $id);
    CRM_Refunds_BAO_Payment::retrieve($params, $payment);
    if(!empty($payment) && isset($payment['contribution_id'])){ // contribution exit so get all price from line item
      /*
      $params = array(
        'version' => 3,
        'id' => $payment['contribution_id'],
        );
      $result = civicrm_api('Contribution', 'get', $params);
      $contribution = CRM_Utils_Array::value($payment['contribution_id'], $result['values'] );
      $refundsAmount['total_amount']  = CRM_Utils_Array::value('total_amount', $contribution);
      */
      foreach ($slots as $slotId => $slot) {
        $params = array(
          'version' => 3,
          'entity_id' => $slotId,
          'entity_table' => 'civicrm_refunds_slot',
        );
        $result = civicrm_api('LineItem', 'get', $params);
        $lineItem = CRM_Utils_Array::value($result['id'], $result['values']);
        $refundsAmount['resource_fees']  += CRM_Utils_Array::value('line_total', $lineItem);
      }
      foreach ($subSlots as $subSlotId => $subSlots) {
        $params = array(
          'version' => 3,
          'entity_id' => $subSlotId,
          'entity_table' => 'civicrm_refunds_sub_slot',
        );
        $result = civicrm_api('LineItem', 'get', $params);
        $lineItem = CRM_Utils_Array::value($result['id'], $result['values']);
        $refundsAmount['sub_resource_fees']  += CRM_Utils_Array::value('line_total', $lineItem);
      }
      foreach ($adhocCharges as $charges) {
        $params = array(
          'version' => 3,
          'entity_id' => CRM_Utils_Array::value('id', $charges),
          'entity_table' => 'civicrm_refunds_adhoc_charges',
        );
        $result = civicrm_api('LineItem', 'get', $params);
        $lineItem = CRM_Utils_Array::value($result['id'], $result['values']);
        $refundsAmount['adhoc_charges_fees']  += CRM_Utils_Array::value('line_total', $lineItem);
      }
    }else{
      foreach ($slots as $id => $slot) {
        $refundsAmount['resource_fees'] += CRM_Refunds_BAO_Slot::calulatePrice(CRM_Utils_Array::value('config_id', $slot) ,CRM_Utils_Array::value('quantity', $slot));
      }
      foreach ($subSlots as $id => $subSlot) {
        $refundsAmount['sub_resource_fees'] += CRM_Refunds_BAO_Slot::calulatePrice(CRM_Utils_Array::value('config_id', $subSlot) ,CRM_Utils_Array::value('quantity', $subSlot));
      }
      foreach ($adhocCharges as $charges) {
        $price = CRM_Core_DAO::getFieldValue('CRM_Refunds_DAO_AdhocChargesItem',
          CRM_Utils_Array::value('item_id', $charges) ,
          'price',
          'id'
        );
        $refundsAmount['adhoc_charges_fees'] += ($price * CRM_Utils_Array::value('quantity', $charges));
      }
    }
    return $refundsAmount;
  }

  static function createActivity($params){
    $session =& CRM_Core_Session::singleton( );
    $userId = $session->get( 'userID' ); // which is contact id of the user
    $optionValue = civicrm_api3('OptionValue', 'get',
      array(
       'option_group_name' => 'activity_type',
       'name' => CRM_Refunds_Utils_Constants::ACTIVITY_TYPE
      )
    );
    $activityTypeId = $optionValue['values'][$optionValue['id']]['value'];
    $params = array(
      'source_contact_id' => $userId,
      'activity_type_id' => $activityTypeId,
      'subject' =>  CRM_Utils_Array::value('subject', $params),
      'activity_date_time' => date('YmdHis'),
      'target_contact_id' => CRM_Utils_Array::value('target_contact_id', $params),
      'status_id' => 2,
      'priority_id' => 2,
    );
    $result = civicrm_api3('Activity', 'create', $params);
  }


  /**
   * Process that send e-mails
   *
   * @return void
   * @access public
   */
  static function sendMail($contactID, &$values, $isTest = FALSE, $returnMessageText = FALSE) {
    //TODO:: check if from email address is entered
    $config = CRM_Refunds_BAO_RefundsConfig::getConfig();

    $template = CRM_Core_Smarty::singleton( );

    list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);

    //send email only when email is present
    if ($email) {
      $refundsId = $values['refunds_id'];

      //get latest refunds status
      $params = array(
            'id' => $refundsId,
        );
      $refundsLatest = civicrm_api3('Refunds', 'get', $params);
      $refundsStatusValueItems =  CRM_Refunds_BAO_Refunds::buildOptions('status_id', 'create'); //get refunds status option values
      $refundsLatestStatus = $refundsStatusValueItems[$refundsLatest['values'][$refundsId]['status_id']];

    	//get refunds detail
    	$refundsDetail = CRM_Refunds_BAO_Refunds::getRefundsDetails($values['refunds_id']);
    	$slots = CRM_Utils_Array::value('slots', $refundsDetail);
    	$subSlots = CRM_Utils_Array::value('sub_slots', $refundsDetail);
    	$adhocCharges = CRM_Utils_Array::value('adhoc_charges', $refundsDetail);
    	$cancellationCharges = CRM_Utils_Array::value('cancellation_charges' , $refundsDetail);

      //get contacts associating with refunds
      $contactIds = array();
      $contactIds['primary_contact'] = CRM_Utils_Array::value('primary_contact_id',$values);
      $contactIds['secondary_contact'] = CRM_Utils_Array::value('secondary_contact_id',$values);
      $contactsDetail = array();
      foreach (array_filter($contactIds) as $k => $contactIdItem) {
        //get contact detail
        $contactDetail = array();
        $params = array(
            'contact_id' => $contactIdItem,
        );
        $contactDetailResult = civicrm_api3('Contact', 'get', $params);
        $contactValues = CRM_Utils_Array::value($contactDetailResult['id'], $contactDetailResult['values']);
        foreach ($contactValues as $key => $contactItem) {
            $contactDetail[$key] = $contactItem;
        }
        $contactsDetail[$k] = $contactDetail;
      }

      //get Price elements(Subtotal, Discount, Total)
      $refunds_amount = CRM_Refunds_BAO_Refunds::getRefundsAmount($values['refunds_id']);
      //get date refunds made
      $dateRefundsMade = new DateTime($values['refunds_date']);

      $tplParams = array(
          'email' => $email,
          'today_date' => date('d.m.Y'),
          'receipt_header_message' => $values['receipt_header_message'],
          'receipt_footer_message' => $values['receipt_footer_message'],
          'refunds_id' => $refundsId,
          'refunds_title' => $values['refunds_title'],
          'refunds_status' => $refundsLatestStatus,
          'refunds_date_made' => $values['refunds_date'],
          'refunds_start_date' => $values['refunds_start_date'],
          'refunds_end_date' => $values['refunds_end_date'],
          'refunds_event_day' => $dateRefundsMade->format('l'),
          'refunds_subtotal' => number_format($refunds_amount['total_amount'] + $refunds_amount['discount_amount'], 2, '.', ''), //total_amount has been deducted from discount
          'refunds_total' => number_format($refunds_amount['total_amount'], 2, '.', ''),
          'refunds_discount' => number_format($refunds_amount['discount_amount'], 2, '.', ''),
          'participants_estimate' => $values['participants_estimate'],
          'participants_actual' => $values['participants_actual'],
          'contacts' => $contactsDetail,
          'slots' => $slots,
          'sub_slots' => $subSlots,
          'adhoc_charges' => $adhocCharges,
          'cancellation_charges' => $cancellationCharges,
      );

      $sendTemplateParams = array(
        'groupName' => 'msg_tpl_workflow_refunds',
        'valueName' => 'refunds_offline_receipt',
        'contactId' => $contactID,
        'isTest' => $isTest,
        'tplParams' => $tplParams,
        'PDFFilename' => 'refundsReceipt.pdf',
      );

      
      if(CRM_Utils_Array::value('contribution',$refundsDetail)){
 
          
        $contribution = array();
        $contributionResult = CRM_Utils_Array::value('contribution',$refundsDetail);
        foreach ($contributionResult as $kx => $ctbItem) {
            $contribution = $ctbItem;
        }
        $sendTemplateParams['tplParams']['contribution'] = $contribution;


        
        $sendTemplateParams['tplParams']['amount_outstanding'] = number_format($refunds_amount['total_amount']-$contribution['total_amount'], 2, '.', '');
      }

      
      if ($lineItem = CRM_Utils_Array::value('lineItem', $values)) {
        $sendTemplateParams['tplParams']['lineItem'] = $lineItem;
      }

      $sendTemplateParams['from'] =  $values['from_email_address'];
      $sendTemplateParams['toName'] = $displayName;
      $sendTemplateParams['toEmail'] = $email;
      //$sendTemplateParams['autoSubmitted'] = TRUE;
      $cc = CRM_Utils_Array::value('cc_email_address', $config);
      if($cc){
        $sendTemplateParams['cc'] = $cc;
      }
      $bcc = CRM_Utils_Array::value('bcc_email_address', $config);
      if($bcc){
        $sendTemplateParams['bcc'] = $bcc;
      }

      list($sent, $subject, $message, $html)  = CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      if($sent && CRM_Utils_Array::value('log_confirmation_email', $config)){  //check log_email_confirmaiton
          $session =& CRM_Core_Session::singleton( );
          $userId = $session->get( 'userID' ); // which is contact id of the user
          //create activity for sending email
          $params = array(
            'option_group_name' => 'activity_type',
            'name' => CRM_Refunds_Utils_Constants::ACTIVITY_TYPE_SEND_EMAIL,
          );
          $optionValue = civicrm_api3('OptionValue', 'get', $params);
          $activityTypeId = $optionValue['values'][$optionValue['id']]['value'];
          $params = array(
            'source_contact_id' => $userId,
            'activity_type_id' => $activityTypeId,
            'subject' => ts('Send Refunds Confirmation Email'),
            'activity_date_time' => date('YmdHis'),
            'target_contact_id' => $contactID,
            'details' => $message,
            'status_id' => 2,
            'priority_id' => 2,
          );
          $result = civicrm_api3('Activity', 'create', $params);
       }
      if ($returnMessageText) {
        return array(
          'subject' => $subject,
          'body' => $message,
          'to' => $displayName,
          'html' => $html,
        );
      }
    }
  }
}
