<?php
ini_set("display_errors",0);
fileRequire("dataModels/class.requestDetails.php");
fileRequire("classes/class.common.php");
fileRequire("classesTpl/class.tpl.showPnrDetailsTpl.php");
fileRequire("classes/class.airlineService.php");
fileRequire("dataModels/class.transactionMaster.php");
fileRequire("dataModels/class.ssrMaster.php");
fileRequire("dataModels/class.ssrDetails.php");
fileRequire("dataModels/class.ssrPaxDetails.php");
fileRequire("dataModels/class.paymentMaster.php");
fileRequire("dataModels/class.requestApprovedFlightDetails.php");
fileRequire("dataModels/class.viaFlightDetails.php");
fileRequire("dataModels/class.requestMaster.php");
fileRequire("dataModels/class.ssrTemp.php");
fileRequire("dataModels/class.ssrTempMaster.php");
fileRequire("dataModels/class.seriesRequestDetails.php");
fileRequire("dataModels/class.passengerDetails.php");
fileRequire("dataModels/class.ssrCategoryDetails.php");
fileRequire("dataModels/class.pnrBlockingDetails.php");
fileRequire("dataModels/class.ssrPaxGrouping.php");
fileRequire("classesTpl/class.tpl.commonPolicyInterfaceTpl.php");
fileRequire("classes/class.fetchPolicyDetails.php");

class ssrResponseTpl
{
	var $_Osmarty;
	var $_Oconnection;
	var $_IinputData;
	var $_OwebServicesPaymentProcess;
	var $_Ocommon;
	var $_OairlineService;
	var $_OobjResponse;
  	var $_OssrMaster;
  	var $_OssrDetails;
  	var $_OrequestDetails;
	var $_OtransactionMaster;
  	var $_OpaymentMaster;
  	var $_OairlinesRequestMapping;
  	var $_OrequestApprovedFlightDetails;
  	var $_OviaFlightDetails;
  	var $_OrequestMaster;
  	var $_OssrTemp;
  	var $_OdisplaySectorDetailsTpl;
  	var $_OseriesRequestDetails;
  	var $_SupdateSSRCheck;
  	var $_IssrTempMasterId;
	var $_OssrTempMaster;
	var $_InoOfAdultPNRBased;
	var $_InoOfChildPNRBased;
	var $_InoOfInfantPNRBased;
	var $_InoOfPassengerPNRBased;
	
	var $_IrequestMasterId;
	var $_Spnr;
	var $_OpassengerDetails;
	var $_AformValues;
	var $_OssrPaxDetails;
	var $_OssrCategoryDetails;
	var $_OpnrBlockingDetails;
	var $_SpnrBlockingIdInString;
	var $_OssrPaxGroup;
	var $_SapiCall; 
	var $_AnestServiceSSRValue;

	function __construct()
	{
		$this->_Osmarty = '';
		$this->_Oconnection = '';
		$this->_IinputData = array();
		$this->_OshowPnrDetailsTpl = new showPnrDetailsTpl();
		$this->_Ocommon = new common();
		$this->_OairlineService = new airlineService();
		$this->_OobjResponse = '';
		$this->_OtransactionMaster = new transactionMaster();
		$this->_OrequestApprovedFlightDetails = new requestApprovedFlightDetails();
		$this->_OviaFlightDetails = new viaFlightDetails();
		$this->_OrequestMaster = new requestMaster();
		$this->_OairlinesRequestMapping = new airlinesRequestMapping();
		$this->_OssrTemp = new ssrTemp();
		$this->_OseriesRequestDetails = new seriesRequestDetails();
		$this->_SupdateSSRCheck="N";
		$this->_IssrTempMasterId=0;
		$this->_OssrTempMaster = new ssrTempMaster();
		$this->_OcommonPolicyInterface=new commonPolicyInterfaceTpl();
		$this->_InoOfAdultPNRBased=0;
		$this->_InoOfChildPNRBased=0;
		$this->_InoOfInfantPNRBased=0;
		$this->_InoOfPassengerPNRBased=0;
		
		$this->_IrequestMasterId = 0;
		$this->_Spnr = '';
		$this->_AsystemMealsDetails = array();
		$this->_AsystemBaggageDetails = array();
		$this->_AsystemOthersDetails = array();
		$this->_AsystemSSRDetails = array();
		$this->_AflightDetails = array();
		$this->_OpassengerDetails = new passengerDetails();
		$this->_AformValues = array();
		$this->_Saction = "SUBMIT";
		$this->_OssrPaxDetails = new ssrPaxDetails();
		$this->_OssrMaster = new ssrMaster();
		$this->_OssrDetails = new ssrDetails();
		$this->_OssrCategoryDetails = new ssrCategoryDetails();
		$this->_OpnrBlockingDetails = new pnrBlockingDetails();
		$this->_SpnrBlockingIdInString = '';
		$this->_StypeOfSsr = 'SSR';
		$this->_IssrCategoryId = '';
		$this->_OssrPaxGroup = new ssrPaxGrouping();
		$this->_AondSsrDetails = array();
		$this->_AmergingFlights = array();
		$this->_SapiCall = "N"; // flag for request raised via API
		$this->_AdisableCancelOption = array();
		$this->_AssrValidityDetails = array();
		$this->_OfetchPolicyDetails = new fetchPolicyDetails();
		$this->_pnrPassengerIDZero = 'N';
		$this->_AnestServiceSSRValue = array();
	}
	
	function _getSSRResponse()
	{
		global $CFG;
		
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$this->_Ocommon->_Osmarty = $this->_Osmarty;
		$this->_Ocommon->_OobjResponse = $this->_OobjResponse;
		
		$this->_OairlineService->_Oconnection = $this->_Oconnection;
		
		$this->_IcurrentStatus = $this->_Ocommon->_getStatusFromRequestId($this->_IrequestMasterId);
		$this->_SdisplayCurrentStatus = $this->_Osmarty->getConfigVars('COMMON_STATUS_DETAILS_'.$this->_IcurrentStatus);
		
		$this->_setPnrBlockingIdInString();
		
		/*
		 * Set request details and payment details for the PNR to display
		 */
		$this->_setRequestAndPaymentDetails();
		
		$this->_getSSRTransactionHistory();
		
		
		/*
		 * Get the SSR availablity list for all the flight in the PNR
		 */
		if(!$this->_getSSRAvailabilityList())
			return false;
		
		/*
		 * Prepare the SSR list with service SSR details based on ssr policy and matrix
		 */
		$this->_setSSRListBasedOnPolicy();

		/***
		* To get the available SSR Categories from Database
		***/
		$this->_getSSRCategoriesFromDataBase();
		
		/*
		 * Prepare the final ssr list which is filtered from service SSR along with policy (if applied)
		 * Or else prepare system SSR list based on departure date from avaiService SSR
		 */
		$this->_prepareFinalSSRList();
		
		/*
		 * Prepare an array for selected SSR for each passenger
		 */
		$this->_getSSRListForPassenger();
		
		/*
		 * Set the passenger details based on the selected pnr for first time
		 */
		 $this->_setPaxDetailsForSSR();
		 
		/*
		 * Modified By	: Subalakshmi S 06.09.2018 
		 * Description	: Redirecting to client side for adding ancillaries
		 */
		//$this->_SredirectLink = 'N';
		if(isset($CFG['ssr']['ssrRedirectLink']['status']) && ($CFG['ssr']['ssrRedirectLink']['status'] == 'Y'))
		{
			$this->_Ocommon->_Oconnection = $this->_Oconnection;
			$this->_ScurrentStatus = $this->_Ocommon->_getCurrentStatusCode($this->_IcurrentStatus);
			$_AstatusArray = explode(',',$CFG['ssr']['ssrRedirectLink']['requestStatus']);
		
			if(in_array($this->_ScurrentStatus,$_AstatusArray))
			{
				$this->_SredirectLink = 'Y';
				$this->_OobjResponse->script("ssrProcessObj.redirectLink='".$CFG['ssr']['ssrRedirectLink']['redirectLink']."';");
			}
		}
		#Get group level current status
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$_AtransactionDetails = $this->_Ocommon->_getTransactionMasterId($this->_IrequestMasterId);
		$_IgroupId=$this->_Ocommon->_getSeriesGroupIdForPnr($this->_IinputData['pnr'],$this->_IinputData['requestMasterId']);
		$_ArequestGroupDetails=$this->_Ocommon->_getRequestGroupDetails($_AtransactionDetails['airlinesRequestId'],$_IgroupId['seriesGroupId'],0,0);

		$this->_IgroupStatus = end($_ArequestGroupDetails)['group_status'];
		# For baggageDisplayStatus 
		if(isset($CFG['site']['baggageDisplayStatus']['status']))
			$this->_AbaggageDisplayStatus = $CFG['site']['baggageDisplayStatus']['status'];		
		
		#$this->_OobjResponse->script("var ssrProcessObj = new ssrProcess();");
		if ($this->_SapiCall == "N"){
			$this->_OobjResponse->script("ssrProcessObj.SSRList=".json_encode($this->_AfinalSSRList).";");
			$this->_OobjResponse->script("ssrProcessObj.passengerSSRList=".json_encode($this->_AfinalPassengerSSRList).";");
		}
		
		$_AorderedSSRArray=array();
		foreach ($this->_AfinalSSRList as $finalKey => $finalValue)
		{
			foreach ($finalValue as $ssrKey => $ssrVal)
			{
				$inputArray=array(
				"inputArray" => $ssrVal,
				"fieldName" => "ssrAmount",
				"orderType" => "ASC"
				);
				$_AorderedSSRArray[$finalKey][$ssrKey] =  $this->_Ocommon->_dynamicSortFunction($inputArray);
			}
		}
		$this->_AfinalSSRList=$_AorderedSSRArray;
		$_AmealSSR=$this->_AfinalSSRList;
		//skip connecting flights row,and join all flights in single row.
		$newFlightDetails=array();
		$bookingIdArray = $output =$mealArray = array();
		foreach($this->_AfinalSSRList as $referenceKey => $referenceValue)
		{
			if(array_key_exists("baggage",$referenceValue) || array_key_exists("meals",$referenceValue) || array_key_exists("others",$referenceValue))
			{
			    $this->_AfinalSSRList[$referenceKey]['displaySSR']='Y';
			}
			$newFlightDetails[$referenceValue['flightDetails']['pnrBlockingId']][] = $referenceValue['flightDetails'];
			$this->_AfinalSSRList[$referenceKey]['commonFlightDetails'] = $referenceValue['flightDetails'];
			$this->_AfinalSSRList[$referenceKey]['mealFlight']=$referenceValue['flightDetails'];
			$mealArray[]=$referenceValue;
			$mealArray['mealFlight']=$referenceValue['flightDetails'];
		}
		foreach($this->_AfinalSSRList as $referenceKey => $referenceValue)
		{
			$this->_AfinalSSRList[$referenceKey]['flightDetails']=$newFlightDetails[$referenceValue['flightDetails']['pnrBlockingId']];
		}
		foreach ($this->_AfinalSSRList as $key => $value) {
			if(!in_array($value['flightDetails'][0]['pnrBlockingId'],$bookingIdArray)){
				$output[$key] = $value;
				$bookingIdArray[] = $value['flightDetails'][0]['pnrBlockingId'];
			}
		}
		$this->_AfinalSSRList=$output;
		$this->_SreviewNotes = $this->_Osmarty->getConfigVars('POPUPSSRDETAILS_ADD_SERVICES_LIKE_BAGGAGE_OR_MEAL').$CFG['settings']['ssrJourneyCondition'].$this->_Osmarty->getConfigVars('POPUPSSRDETAILS_ADD_SERVICES_NOTES');
		/*
		 * Manikumar - 20-12-2018 - Assing the configuration of cancel or downgrade ssrs into script
		 **/
		if(isset($CFG['ssr']['restrictCancelDowngrade']) && !empty($CFG['ssr']['restrictCancelDowngrade']))
			$this->_OobjResponse->script("ssrProcessObj.restrictCancelDowngrade=".json_encode($CFG['ssr']['restrictCancelDowngrade']).";");

		/* Based on config enable the ssr multi-select option - TR change*/
		$_ScabinVal=$_IgroupId['cabin'];
		$_AmultiSelectSSR=$CFG['ssr']['multiSelect']['default'];
		if(isset($CFG['ssr']['multiSelect']['cabin']) && !empty($CFG['ssr']['multiSelect']['cabin'])&& isset($CFG['ssr']['multiSelect']['cabin'][$_ScabinVal]) && !empty($CFG['ssr']['multiSelect']['cabin'][$_ScabinVal]))
			$_AmultiSelectSSR=$CFG['ssr']['multiSelect']['cabin'][$_ScabinVal];
		/*restrict to add free ssr more than once*/
		if(isset($CFG['ssr']['multiSelect']['restrictFreeSSR']) && !empty($CFG['ssr']['multiSelect']['restrictFreeSSR']))
			$this->_OobjResponse->script("ssrProcessObj.restrictFreeSSR=".json_encode($CFG['ssr']['multiSelect']['restrictFreeSSR']).";");
		/* restrict cancel ssr for TA */
		$hideCancelSSR =(in_array($_SESSION['groupRM']['groupId'],$CFG['limit']['hideCancelSSR']['groupId']))?'Y':'N';
		if ($this->_SapiCall == "Y"){
			// Response for api hit
			$ssr_response_data = [
				"data"=>[
					// "allowCancelSSR" => $cancelSSR,
					"SSRList" => $this->_AfinalSSRList,
					"hideCancelSSR" => $hideCancelSSR,
					"passengerSSRList" => $this->_AfinalPassengerSSRList,
					"multiSelectConfig" => $_AmultiSelectSSR,
					"instantPayment"=> $CFG['ssr']['instantPayment']['status'],
					"offLineSsr" => $CFG['ssr']['offLineSsr']
				]
			];
			
			return $ssr_response_data;
		}		
		$cancelSSR = 'N';
		$_AcancelSSR = array();
		$_AcancelSSR['allowCancelSSR'] = 'N';
		$_AcancelSSR['disableCancelOption'] = $this->_AdisableCancelOption;
		$_AcancelSSR['ssrValidity'] = $this->_AssrValidityDetails;
		if(isset($CFG["queueSync"]["offlineSync"]["ancillarySync"]) && $CFG["queueSync"]["offlineSync"]["ancillarySync"]["status"]=="Y")
		{
			$cancelSSR = 'Y';
			$_AcancelSSR['allowCancelSSR'] = 'Y';
			$_AcancelSSR['disableCancelOption'] = $this->_AdisableCancelOption;
		}
		$this->_Osmarty->assign("mealSSR",$_AmealSSR);
		$this->_Osmarty->assign("allowCancelSSR",$cancelSSR);
		$this->_Osmarty->assign("SSRList",$this->_AfinalSSRList);
		$this->_Osmarty->assign("hideCancelSSR",$hideCancelSSR);
		$this->_Osmarty->assign("passengerSSRList",$this->_AfinalPassengerSSRList);
		$this->_Osmarty->assign("ssrObj",$this);
		$this->_Osmarty->assign("multiSelectConfig",$_AmultiSelectSSR);
		$this->_Osmarty->assign("instantPayment",$CFG['ssr']['instantPayment']['status']);
		$this->_Osmarty->assign("offLineSsr",$CFG['ssr']['offLineSsr']);
		$this->_Osmarty->assign("CFG",$CFG);
		//if passengerId inserted to 0 then we restrict the ssr template page. 
		if($this->_pnrPassengerIDZero =='N')
		{
			$template=$this->_Osmarty->fetch("popupSSRDetails.tpl");
			$this->_OobjResponse->call("commonObj.openGrmPopUp",$template,$this->_Osmarty->getConfigVars('COMMON_SSR'),true,'big');
			$this->_OobjResponse->script("ssrProcessObj.prepareReviewListSSR();");
		}
		$this->_OobjResponse->script("ssrProcessObj.cancelSSRArray = [];");
		$this->_OobjResponse->script("ssrProcessObj.cancelSSR = ".json_encode($_AcancelSSR).";");

		// SSR Count Display Feature: Assign SSR count to Smarty
		if (!empty($this->_AnestServiceSSRValue)) {
			$selectedSSRCount = $this->_preSelectedSSRCount($this->_IrequestMasterId, $this->_AnestServiceSSRValue);
			$this->_Osmarty->assign("selectedSSRCount", $selectedSSRCount);
			fileWrite(print_r($this->_AfinalSSRList,1),'SSR___final','w+');
		}
	}
	
	function _checkValidationForSSR()
	{
		global $CFG;
		
		/*
		 * Restrict to add ancillaries for speicified status 
		 */
		fileRequire("classes/class.ssrManipulation.php");
		$_OssrManipulation = new ssrManipulation();
		$_OssrManipulation->_Oconnection = $this->_Oconnection;
		$_BdisplayAncillary = $_OssrManipulation->_validateDisplayOfLink('ANCILLARY',$this->_IrequestMasterId,$this->_Spnr);
		#Get group level current status
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$_AtransactionDetails = $this->_Ocommon->_getTransactionMasterId($this->_IrequestMasterId);
		$_IgroupId=$this->_Ocommon->_getSeriesGroupIdForPnr($this->_IinputData['pnr'],$this->_IinputData['requestMasterId']);
		$_ArequestGroupDetails=$this->_Ocommon->_getRequestGroupDetails($_AtransactionDetails['airlinesRequestId'],$_IgroupId['seriesGroupId'],0,0);
		$this->_IgroupStatus = end($_ArequestGroupDetails)['group_status'];
		if(in_array($this->_IgroupStatus,explode(",",(string)$CFG['site']['baggageDisplayStatus']['status'])) && !$_BdisplayAncillary)
		{
			$this->_OobjResponse->script("errorMessages('Error','Adding ancillaries is not allowed to this request');");
			return false;
		}
		
		
		/*
		 * Modified By	: Subalakshmi S
		 * Desc			: Allow to add ancillaries for return sector 
		 * 					eventhough onward departure date expires
		 */
		/*$this->_DdepartureDate = $this->_Ocommon->_getPnrMinDepartureDate($this->_Spnr,$this->_IrequestMasterId);
		$_SdepatureOrigin=$this->_Ocommon->_SoriginValue;
		$_DoriginCurrentDate = $this->_Ocommon->_getAirportCurrentTime($_SdepatureOrigin);
		$_DvalidationDate = date("Y-m-d H:i:s", strtotime($_DoriginCurrentDate.'+'.$CFG['settings']['ssrJourneyCondition'].' hours'));
		
		$_DdepartureDate = strtotime($this->_DdepartureDate);
		$_DvalidationDate = strtotime($_DvalidationDate);
		
		if($_DdepartureDate <= $_DvalidationDate) 
		{
			$this->_Osmarty->assign("response",'EMPTY');
			$this->_OobjResponse->script("errorMessages('','".$this->_Osmarty->getConfigVars('COMMON_SSR_JOURNEY_ERROR').' '.$CFG['settings']['ssrJourneyCondition'].' '.$this->_Osmarty->getConfigVars('COMMON_HOURS')."');");
			return false;
		}*/
		
		/*
		 * Check the pnr status if closed or not
		 */
		$_ApnrInformation = $this->_Ocommon->_getPnrInformation($this->_Spnr,$this->_IrequestMasterId);
		
		if($_ApnrInformation['pnrDetails']['status']=="Closed")
		{
			$this->_OobjResponse->script("Ext.Msg.alert('".$this->_Osmarty->getConfigVars('COMMON_REPORT_ERROR')."','".$this->_Osmarty->getConfigVars('COMMON_THIS_PNR_IS_CLOSED')."');");
			return false;
		}
		
		/*
		 * Check the pnr validity is expired or not 
		 */
		$_ApnrPaymentDetails=$this->_Ocommon->_getPnrPaymentDetails($this->_IrequestMasterId,$this->_Spnr,"PENDING");
		/*In order to avoid the alert when the request is pnr submitted*/
		if($this->_IcurrentStatus==11 || (count((array)$_ApnrPaymentDetails)>0 && $_ApnrPaymentDetails[0]['paymentExpiryStatus']=="Y"  && !in_array($this->_IcurrentStatus,array('9','12','13','14'))))
		{
			$this->_OobjResponse->script("Ext.Msg.alert('".$this->_Osmarty->getConfigVars('COMMON_REPORT_ERROR')."','".$this->_Osmarty->getConfigVars('COMMON_THIS_PNR_VALIDITY_DATE_IS_EXPIRED')."');");
			return false;
		}
		
		/*
		 * Modified by: Subalakshmi S 29-08-2018 
		 * To restrict add ancillaries for the request which has not been in the configured status for travel agents
		 **/ 
		if(!in_array($_SESSION['groupRM']['groupId'],$CFG['default']['airlinesGroupId']))
		{
			if(isset($CFG["ssr"]["viewSSRRequestStatus"]) && !empty($CFG["ssr"]["viewSSRRequestStatus"]))
			{
				if(!in_array($this->_IcurrentStatus,$CFG['ssr']['viewSSRRequestStatus']))
				{
					$this->_OobjResponse->script("errorMessages('','".$this->_Osmarty->getConfigVars('COMMON_VALIDATION_NOT_ALLOW_ADD_ANCILLARIES')."');");
					return false;
				}
			}
		}
		return true;
	}
	
	/*
	 * Setting the request and payment details for the pnr
	 */
	function _setRequestAndPaymentDetails()
	{
		global $CFG;
		
		fileRequire("dataModels/class.paymentAdditionalChargeDetails.php");
		$_OpaymentAdditionalChargeDetails = new paymentAdditionalChargeDetails();

		fileRequire("dataModels/class.pnrBlockingDetails.php");
		$_OpnrBlockingDetails = new pnrBlockingDetails();

		$this->_SdisplayRequestId = $this->_Ocommon->_changeGroupRequestFormat($this->_IrequestMasterId);
		$this->_SrequestType = ucfirst($this->_Ocommon->_getRequestType($this->_IrequestMasterId));
		$this->_IrequestTypeId = $this->_Ocommon->_getRequestType($this->_IrequestMasterId,"Y");
		$this->_AuserCurrency = $this->_Ocommon->_getUserCurrency($this->_IrequestMasterId);
		$this->_SuserCurrency = $this->_AuserCurrency['user_currency'];
		
		$this->_SdepartureDate = $this->_Ocommon->_getPnrMinDepartureDate($this->_Spnr,$this->_IrequestMasterId);
		$this->_SdepartureDate = date('d-M-Y H:i',strtotime($this->_SdepartureDate));
		
		$this->_ItotalPnrAmount = $this->_Ocommon->_getPnrAmountValue($this->_Spnr,$this->_IrequestMasterId);
		//Rounding off total amount
		$this->_ItotalPnrAmount = $this->_Ocommon->_getRoundOffFare($this->_ItotalPnrAmount,'',$this->_SuserCurrency);
		$this->_ItotalPnrAmountDisplay = $this->_Ocommon->_getRoundOffFare($this->_ItotalPnrAmount,"","displayFare");
		
		$this->_IpnrPaidAmount = $this->_Ocommon->_getPnrPaidAmount($this->_IrequestMasterId,$this->_Spnr);
		$this->_IpnrPaidAmountDisplay = $this->_Ocommon->_getRoundOffFare($this->_IpnrPaidAmount,"","displayFare");

		$_ApnrPaymentDetails=$this->_Ocommon->_getPnrPaymentDetails($this->_IrequestMasterId,$this->_Spnr,"SSRCANCEL");
		if(!empty($_ApnrPaymentDetails))
			$this->_IpnrPaidAmount = $this->_IpnrPaidAmount + array_sum(array_column($_ApnrPaymentDetails,'paidAmount'));
		$this->_IremaingAmount = $this->_Ocommon->_getRoundOffFare(($this->_ItotalPnrAmount-$this->_IpnrPaidAmount));
		if(isset($CFG['ssr']['SSRPayment']) && $CFG['ssr']['SSRPayment']['status']=='Y')
		{
			$_OpnrBlockingDetails->__construct();
			$_OpnrBlockingDetails->_Oconnection=$this->_Oconnection;
			$_OpnrBlockingDetails->_IrequestMasterId =$this->_IrequestMasterId;
			$_OpnrBlockingDetails->_Spnr =$this->_Spnr;
			$_ApnrDetails=$_OpnrBlockingDetails->_selectPnrBlockingDetails();

			$_OpaymentAdditionalChargeDetails->__construct();
			$_OpaymentAdditionalChargeDetails->_Oconnection=$this->_Oconnection;
			$_OpaymentAdditionalChargeDetails->_IrequestMasterId =$this->_IrequestMasterId;
			$_OpaymentAdditionalChargeDetails->_IpnrBlockingId =$_ApnrDetails[0]['pnr_blocking_id'];
			$_OpaymentAdditionalChargeDetails->_SpaidStatus ='PENDING';
			//$_OpaymentAdditionalChargeDetails->_SssrStatus ='Y';
			$_OpaymentAdditionalChargeDetails->_SssrStatus ='SR';
			if($this->_StypeOfSsr=="SEAT")
				$_OpaymentAdditionalChargeDetails->_SssrStatus ='SE';
			$_AssrPaidDetails=$_OpaymentAdditionalChargeDetails->_selectPaymentAdditionalChargeDetails();
			$_AssrRemainingAmount=array_column($_AssrPaidDetails, 'additional_amount');
			if(!empty($_AssrRemainingAmount))
				$this->_IremaingAmount+=array_sum($_AssrRemainingAmount);
		}
		//disable displayfare
		$_AuserCurrency = $this->_Ocommon->_getUserCurrency($this->_IrequestMasterId);
		$this->_ScurrencyCode = $CFG['site']['disableDisplayFare']=='Y' && $_AuserCurrency['user_currency']!='' ? $_AuserCurrency['user_currency'] : "displayFare";
		$this->_IremaingAmount = $this->_Ocommon->_getRoundOffFare($this->_IremaingAmount,'',$this->_ScurrencyCode);
		$this->_ItotalPnrAmount = $this->_Ocommon->_getRoundOffFare($this->_ItotalPnrAmount,"",$this->_ScurrencyCode);
		$this->_IpnrPaidAmount = $this->_Ocommon->_getRoundOffFare($this->_IpnrPaidAmount,"",$this->_ScurrencyCode);
		
		$this->_ApnrFareDetails = $this->_Ocommon->_getPnrFareDetails($this->_IrequestMasterId,$this->_Spnr);
		
		$this->_AtotalPaxCount = $this->_Ocommon->_getPnrPaxDetails($this->_IrequestMasterId,$this->_Spnr);
		$this->_SdisplayPaxCount = $this->_Ocommon->_getPaxDetails($this->_AtotalPaxCount['numberOfAdult'],$this->_AtotalPaxCount['numberOfChild'],$this->_AtotalPaxCount['numberOfInfant'],$this->_AtotalPaxCount['numberOfFoc']);
		
		$_IancillariesAmount = $this->_Ocommon->_getSSRTotalAmount($this->_IrequestMasterId,$this->_Spnr,'','Y');
		
		$_IancillariesAmount = ($_IancillariesAmount!="N" ? $_IancillariesAmount : "0");
		$this->_IancillariesAmount = $this->_Ocommon->_getRoundOffFare($_IancillariesAmount,'',$currencyCode);
	}
	
	/*
	 * Get the SSR transaction history details
	 */
	function _getSSRTransactionHistory($_SseatStatus = 'N')
	{
		global $CFG;
		
		$this->_AssrTransactionHistory = array();
		
		$this->_OssrMaster->_Oconnection = $this->_Oconnection;
		$this->_OssrMaster->_IrequestMasterId = $this->_IrequestMasterId;
		$this->_OssrMaster->_Spnr = $this->_Spnr;
		$this->_AssrTransactionHistory = $this->_OssrMaster->_selectSsrMaster();
		$_AssrCategoryName = $this->_getSSRCategoryId('Y');
		
		if(!empty($this->_AssrTransactionHistory)) {
		
			foreach($this->_AssrTransactionHistory AS $ssrMasterIndex => &$ssrMasterArray) {
				//if($ssrMasterArray['ssr_category_id']!=4)
				{
					if($_SseatStatus=='N' && $ssrMasterArray['ssr_category_id']==4)
					{
						unset($this->_AssrTransactionHistory[$ssrMasterIndex]);
						continue;
					}
					else if($_SseatStatus=='Y' && $ssrMasterArray['ssr_category_id']!=4)
					{
						unset($this->_AssrTransactionHistory[$ssrMasterIndex]);
						continue;
					}
					/*$cond = "";
					if($CFG['ssr']['instantPayment']['status'] == 'Y')
						$cond = " OR sd.emd_id != 0";*/
					$selectSSRDetails = "SELECT
											sd.ssr_category_id,
											sd.ssr_total_fare,
											scd.ssr_category_name,
											sd.ssr_status,
											sd.ssr_master_id
										FROM
											".$CFG['db']['tbl']['ssr_details']." sd, 
											".$CFG['db']['tbl']['ssr_category_details']." scd
										WHERE
											sd.ssr_master_id = ".$ssrMasterArray['ssr_master_id']."
											AND sd.ssr_category_id = scd.ssr_category_id
											AND (ssr_status='NEW' OR ssr_status='COMPLETED'OR ssr_status='ERROR' OR ssr_status='CANCELLED')";
					if(DB::isError($resultSSRDetails=$this->_Oconnection->query($selectSSRDetails)))
					{
						fileWrite($selectSSRDetails,"SqlError","a+");
						return false;
					}
					$tempSSRFareArray = array();
					$getData=$this->_Ocommon->_executeQuery($selectSSRDetails);
					$getSsrStatus= array_column($getData, 'ssr_status');
					$countSSRArray = array_count_values($getSsrStatus);
					$errorSSR = ((count((array)$getSsrStatus) != $countSSRArray['ERROR'])?'N':'Y');
					$_ItotalAmount=0;
					if($resultSSRDetails->numRows() > 0)
					{
						while($rowSSRDetails=$resultSSRDetails->fetchRow(DB_FETCHMODE_ASSOC))
						{
							//Setting the ssr total fare based on ssr category
							if(($rowSSRDetails['ssr_status']=='COMPLETED' || $rowSSRDetails['ssr_status']=='CANCELLED') && $errorSSR == 'Y')
							{
								//Setting the ssr total fare based on ssr category
								if($rowSSRDetails['ssr_master_id']!=$ssrMasterArray['ssr_master_id'])
									continue;
								$tempSSRFareArray[$ssrMasterArray['ssr_master_id']][strtolower($rowSSRDetails['ssr_category_name'])] += $rowSSRDetails['ssr_total_fare'];
								$_ItotalAmount += $rowSSRDetails['ssr_total_fare'];
							}
							if($errorSSR != 'Y' || $rowSSRDetails['ssr_status']=='NEW'){
								if($rowSSRDetails['ssr_master_id']!=$ssrMasterArray['ssr_master_id'])
									continue;
								if($rowSSRDetails['ssr_master_id']==$ssrMasterArray['ssr_master_id'] && in_array($rowSSRDetails['ssr_status'],array('CANCELLED','COMPLETED')) && $ssrMasterArray['status']=='ERROR')
									continue;
								$tempSSRFareArray[$ssrMasterArray['ssr_master_id']][strtolower($rowSSRDetails['ssr_category_name'])] += $rowSSRDetails['ssr_total_fare'];
								
								$_ItotalAmount += $rowSSRDetails['ssr_total_fare'];
							}
						}
					}
					
					foreach($_AssrCategoryName AS $_SssrCategoryName) {
						if(isset($tempSSRFareArray[$ssrMasterArray['ssr_master_id']][$_SssrCategoryName]))
						$tempSSRFareArray[$ssrMasterArray['ssr_master_id']][$_SssrCategoryName] = $this->_SuserCurrency." ".$this->_Ocommon->_getRoundOffFare($tempSSRFareArray[$ssrMasterArray['ssr_master_id']][$_SssrCategoryName],'',$this->_ScurrencyCode);
						else
							$tempSSRFareArray[$ssrMasterArray['ssr_master_id']][$_SssrCategoryName] = "-";
					}
					$ssrMasterArray['ssr_amount'] = $this->_Ocommon->_getRoundOffFare($ssrMasterArray['ssr_amount'],'',$this->_ScurrencyCode);
					if($ssrMasterArray['status']=='NEW' )
					{
						$ssrMasterArray['status'] = 'INCOMPLETE';
					}
					if($ssrMasterArray['status']=='ERROR')
					{
						$ssrMasterArray['status'] = 'INCOMPLETE';
					}
					if($ssrMasterArray['ssr_amount']<= $_ItotalAmount && $CFG['ssr']['instantPayment']['status'] == 'Y')
					{
						$ssrMasterArray['ssr_amount'] = $this->_Ocommon->_getRoundOffFare($_ItotalAmount,'','displayFare');
					}
					elseif($_ItotalAmount==0)
					{
						if($ssrMasterArray['last_transaction']=='N')
							$ssrMasterArray['status'] = 'CANCELLED';
						else if($ssrMasterArray['status']!='INCOMPLETE')
							$ssrMasterArray['status'] = 'FAILED';
					}
					elseif($ssrMasterArray['last_transaction']=='N')
						$ssrMasterArray['status'] = 'CANCELLED';
					if($ssrMasterArray['last_transaction']=='Y' && $ssrMasterArray['status']=="ERROR")
						$ssrMasterArray['status'] = 'COMPLETED';
					$ssrMasterArray['ssrUpdatedDate'] = date('d-M-Y H:i',strtotime($this->_Ocommon->_getUserDateFormatValue($ssrMasterArray['ssr_updated_date'])));
					$ssrMasterArray['ssrDetails'] = $tempSSRFareArray[$ssrMasterArray['ssr_master_id']];
					if($ssrMasterArray['ssr_category_id']==4 && $_SseatStatus=='Y')
						$this->_ASSRCancelHistory[$ssrMasterArray['ssr_master_id']]=$this->_getSSRCancelHistory($ssrMasterArray['ssr_master_id'],$ssrMasterArray['last_transaction'],'Y');
					else
						$this->_ASSRCancelHistory[$ssrMasterArray['ssr_master_id']]=$this->_getSSRCancelHistory($ssrMasterArray['ssr_master_id'],$ssrMasterArray['last_transaction']);
				}
			}
		}
		return $this->_AssrTransactionHistory;
	}
	/*
	 * Get the SSR transaction details and emd in pax wise
	 */
	function _getSSRCancelHistory($_IssrMasterId=0,$_SlastTransaction='N',$_SseatStatus='N')
	{
		global $CFG;
		fileRequire('classes/class.getPNRDetails.php');
		$_OgetPnr = new getPNRDetails();
		fileRequire('dataModels/class.passengerDetails.php');
		$_OpassengerDetails = new passengerDetails();
		$_ASSRCancelHistory = array();
		if($_SseatStatus=='Y')
			$_Scond = " AND sm.ssr_category_id = 4 ";
		else
			$_Scond = " AND sm.ssr_category_id != 4 ";
		$selectSSRDetails = "SELECT
										sd.ssr_category_id,
										sd.ssr_total_fare,
										scd.ssr_category_name,
										sd.ssr_status,
										sd.emd_id,
										sm.pnr,
										sm.ssr_updated_date,
										sd.ssr_code,
										spd.passenger_id,
										sd.ssr_base_fare
									FROM
										".$CFG['db']['tbl']['ssr_master']." sm,
										".$CFG['db']['tbl']['ssr_details']." sd, 
										".$CFG['db']['tbl']['ssr_category_details']." scd,
										".$CFG['db']['tbl']['ssr_pax_details']." spd
									WHERE
										sm.pnr = '".$this->_Spnr."'
										AND sm.ssr_master_id = sd.ssr_master_id
										AND sd.ssr_category_id = scd.ssr_category_id
										AND sd.ssr_pax_id = spd.ssr_pax_id
										AND sm.ssr_master_id = ".$_IssrMasterId.
										$_Scond;
		if(DB::isError($resultSSRDetails=$this->_Oconnection->query($selectSSRDetails)))
		{
			fileWrite($selectSSRDetails,"SqlError","a+");
			return false;
		}
		$i=0;
		if($resultSSRDetails->numRows() > 0)
		{
			while($rowSSRDetails=$resultSSRDetails->fetchRow(DB_FETCHMODE_ASSOC))
			{
				$_ASSRCancelHistory[$i][strtolower($rowSSRDetails['ssr_category_name'])] = $this->_Ocommon->_getRoundOffFare($rowSSRDetails['ssr_total_fare'],'','displayFare');
				$_ASSRCancelHistory[$i]['emdId'] = $rowSSRDetails['emd_id'];
				$_ASSRCancelHistory[$i]['ssrStatus'] = $rowSSRDetails['ssr_status'];
				$_ASSRCancelHistory[$i]['ssrCode'] = $rowSSRDetails['ssr_code'];
				$_ASSRCancelHistory[$i]['passengerId'] = $rowSSRDetails['passenger_id'];
				if($_SlastTransaction=='Y' && $rowSSRDetails['emd_id']>0 && $rowSSRDetails['ssr_base_fare']>0)
					$_ASSRCancelHistory[$i]['ssrStatus'] = "COMPLETED";
				$i++;
			}
		}
		if(!empty($_ASSRCancelHistory))
		{
			$_AemdIds = array_column($_ASSRCancelHistory,'emdId');
			$_OgetPnr->_Oconnection = $this->_Oconnection;
			$_AemdInfo = $_OgetPnr->_getEMDInfo('',$_AemdIds);
			$_AemdInfo = array_column($_AemdInfo,'issued_document_number','emd_id');

			$_OpassengerDetails->__construct();
			$_OpassengerDetails->_Oconnection = $this->_Oconnection;
			$_OpassengerDetails->_INcondition = "IN";
			$_OpassengerDetails->_IpassengerId = implode(',',array_column($_ASSRCancelHistory,'passengerId'));
			$_ApassengerDetails = $_OpassengerDetails->_selectPassengerDetails();

			foreach($_ASSRCancelHistory as $ssrKey=>$_AssrVal)
			{
				if($_AssrVal['emdId']>0)
					$_ASSRCancelHistory[$ssrKey]['emdId'] = $_AemdInfo[$_AssrVal['emdId']];
				$_IpassengerIndex = array_search($_AssrVal['passengerId'],array_column($_ApassengerDetails,'passenger_id'));
				$_ASSRCancelHistory[$ssrKey]['passengerName'] = $_ApassengerDetails[$_IpassengerIndex]['first_name']." ".$_ApassengerDetails[$_IpassengerIndex]['last_name'];
			}
		}
		return $_ASSRCancelHistory;
	}
	
	
	/*
	 * Get the ssr list based on ssr policy
	 */
	function _setSSRListBasedOnPolicy()
	{
		global $CFG;
		$this->_AssrListPolicyValues = array();
		$this->_OviaFlightDetails->_Oconnection = $this->_Oconnection;
		//Preparing form values for policy input
		$sqlSelectRequestDetails= "SELECT
									rm.request_master_id as requestMasterId,
									rm.user_id,
									rd.request_id,
									rm.request_type_id as requestType,
									rm.requested_date,
									rm.trip_type as tripType,
									pbd.no_of_adult,
									pbd.no_of_child,
									(pbd.no_of_adult+pbd.no_of_child) as noOfPax,
									pbd.no_of_infant,
									rm.user_currency as currencyType,
									rafd.source,
									rafd.destination,
									rd.cabin as cabinClass,
									rafd.request_approved_flight_id,
									rafd.departure_date as departureDate,
									rafd.airline_code as airlineCode,
									rafd.flight_code as flightNumber,
									rafd.stops,
									rafd.fare_filter_method as fareType,
									ud.corporate_id as travelAgency,
									".encrypt::_decrypt('ud.email_id')." as loginId,
									".encrypt::_decrypt('ud.country_code')." as countryCode,
									cd.corporate_type_id as userType,
									".encrypt::_decrypt('cd.pcc_code')." as skyAgentId
								FROM
									".$CFG['db']['tbl']['request_master']." rm,
									".$CFG['db']['tbl']['request_details']." rd,
									".$CFG['db']['tbl']['request_approved_flight_details']." rafd,
									".$CFG['db']['tbl']['series_request_details']." srd,
									".$CFG['db']['tbl']['user_details']." ud,
									".$CFG['db']['tbl']['corporate_details']." cd,
									".$CFG['db']['tbl']['pnr_blocking_details']." pbd
								WHERE
									rm.request_master_id=rd.request_master_id
									AND rafd.series_request_id=srd.series_request_id
									AND rd.request_id=srd.request_id
									AND rm.user_id= ud.user_id
									AND ud.corporate_id = cd.corporate_id
									AND pbd.request_approved_flight_id = rafd.request_approved_flight_id
									AND pbd.pnr ='".$this->_Spnr."'
								ORDER BY
									rafd.departure_date";
		if(DB::isError($resultSelectRequestDetails=$this->_Oconnection->query($sqlSelectRequestDetails)))
		{
			fileWrite($sqlSelectRequestDetails,"SqlError","a+");
			return false;
		}
		if($resultSelectRequestDetails->numRows() > 0)
		{
			while($rowSelectRequestDetails=$resultSelectRequestDetails->fetchRow(DB_FETCHMODE_ASSOC))
			{
				if($rowSelectRequestDetails['tripType']==1)
					$rowSelectRequestDetails['tripType']="O";
				elseif($rowSelectRequestDetails['tripType']==2)
					$rowSelectRequestDetails['tripType']="R";
				elseif($rowSelectRequestDetails['tripType']==3)
					$rowSelectRequestDetails['tripType']="M";
				$rowSelectRequestDetails['tripCategory'] = $this->_Ocommon->_isDomestic($rowSelectRequestDetails['requestMasterId']);
				//To skip ssr policy
				if(isset($CFG["ssr"]["skipSSRPolicy"]) && is_array($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]["status"] == 'Y')
				{
					$this->_getDirectcodeFromService($rowSelectRequestDetails);
				}
				elseif($rowSelectRequestDetails['stops']>0)
				{
					$this->_OviaFlightDetails->__construct();
					$this->_OviaFlightDetails->_IrequestApprovedFlightId = $rowSelectRequestDetails['request_approved_flight_id'];
					$this->_OviaFlightDetails->_selectViaFlightDetails();
					
					foreach($this->_OviaFlightDetails->_AviaFlightDetails as $viaFlightKey => $viaFlightValue)
					{
						$rowSelectRequestDetails['departureDate'] = $viaFlightValue['departure_date'];
						$rowSelectRequestDetails['flightNumber'] = $viaFlightValue['flight_number'];
						$rowSelectRequestDetails['airlineCode'] = $viaFlightValue['airline_code'];
						$rowSelectRequestDetails['source'] = $viaFlightValue['origin'];
						$rowSelectRequestDetails['destination'] = $viaFlightValue['destination'];
						
						$rowSelectRequestDetails['departureDOW'] =$this->_Ocommon->_getDayOfWeek($rowSelectRequestDetails['departureDate']);
						$rowSelectRequestDetails['policyRequestedDate'] = date("Y-m-d H:i:s",strtotime($rowSelectRequestDetails['requested_date']));
						
						$returnArray = $this->_fetchSSRPolicy($rowSelectRequestDetails);
						$this->_getDirectcodeFromService($rowSelectRequestDetails,$returnArray);
						// $this->_AssrListPolicyValues[str_replace("-","",$rowSelectRequestDetails['departureDate']).$rowSelectRequestDetails['airlineCode'].$rowSelectRequestDetails['flightNumber'].$rowSelectRequestDetails['source'].$rowSelectRequestDetails['destination']]=$returnArray;
					}
				}
				else
				{
					$rowSelectRequestDetails['departureDOW'] =$this->_Ocommon->_getDayOfWeek($rowSelectRequestDetails['departureDate']);
					$rowSelectRequestDetails['policyRequestedDate'] = date("Y-m-d H:i:s",strtotime($rowSelectRequestDetails['requested_date']));
					$rowSelectRequestDetails['daysToDeparture']=round(abs(strtotime(date('Y-m-d'))-strtotime($rowSelectRequestDetails['departureDate']))/60/60/24);
					
					$returnArray = $this->_fetchSSRPolicy($rowSelectRequestDetails);
					$this->_getDirectcodeFromService($rowSelectRequestDetails,$returnArray);
					// $this->_AssrListPolicyValues[str_replace("-","",$rowSelectRequestDetails['departureDate']).$rowSelectRequestDetails['airlineCode'].$rowSelectRequestDetails['flightNumber'].$rowSelectRequestDetails['source'].$rowSelectRequestDetails['destination']]=$returnArray;
				}
			}
		}
	}
	
	/*
	 * Get the ssr policy for the flight details
	 */
	function _fetchSSRPolicy($policyInputArray)
	{
		global $CFG;
		$returnArray=array();
		$policyMasterValueArray=array();
		
		$sqlSelectRequestPolicyMaster="SELECT
						spm.policy_id,
						spm.policy_name,
						spm.matrix_id,
						spm.priority,
						spm.created_date,
						spm.policy_dow,
						spm.policy_string
					FROM
						".$CFG['db']['tbl']['ssr_policy_master']." spm
					WHERE
						spm.active_status='Y'
						AND ('".$policyInputArray['policyRequestedDate']."' BETWEEN spm.start_date AND spm.end_date)";
		if(DB::isError($resultSelectRequestPolicyMaster=$this->_Oconnection->query($sqlSelectRequestPolicyMaster)))
		{
			fileWrite($sqlSelectRequestPolicyMaster,"SqlError","a+");
			return false;
		}
		
		if($resultSelectRequestPolicyMaster->numRows() > 0)
		{
			
			while($rowSelectRequestPolicyMaster=$resultSelectRequestPolicyMaster->fetchRow(DB_FETCHMODE_ASSOC))
			{
				if($this->_Ocommon->_getPolicyDayOfWeekStatus($policyInputArray['policyRequestedDate'],$rowSelectRequestPolicyMaster['policy_dow'])=="Y")
				{
					$_Stable = $CFG['db']['tbl']['ssr_policy_details'].' spd 
                        INNER JOIN '. 
                        $CFG['db']['tbl']['criteria_master'].' cm
                        ON 
						spd.criteria_id=cm.criteria_id
                        INNER JOIN '. 
                        $CFG['db']['tbl']['operator_master'].' om
                        ON 
                        spd.operator_id=om.operator_id';
						
					$_AselectField = array(
						"spd.policy_details_id",	
						"spd.policy_id",
						"spd.criteria_id",			 
						"spd.loop_value",
						"cm.criteria_name",
						"cm.criteria_type",
						"cm.criteria_type", 
						"cm.criteria_logical_id",			
						"spd.operator_id",
						"om.operator_name",
						"om.logical_value",
						"om.operator_type",	
						"spd.policy_value"		
					);
					$_AconditionValue = array(
						'spd.policy_id' => $rowSelectRequestPolicyMaster['policy_id']
					);
					$_ApolicyDetails = $this->_Oconnection->_performJoinQuery($_Stable,$_AselectField,$_AconditionValue);
				
					$rowSelectRequestPolicyMaster['policy_string']= strtr($rowSelectRequestPolicyMaster['policy_string'],$policyInputArray);
					
					//Evaluating the policy string from policy master table
					if($rowSelectRequestPolicyMaster['policy_string']!="")
					{
						$_ApolicyStringDetails = json_decode($rowSelectRequestPolicyMaster['policy_string'],1);
						$this->_OfetchPolicyDetails->_Oconnection = $this->_Oconnection;
						$_AaggregatePolicyDetails = $this->_OfetchPolicyDetails->_getAggregatePolicyVaues($_ApolicyDetails,$policyInputArray);
				        $_AfinalStringExecute = array();
						if(!empty($_AaggregatePolicyDetails['string'])){
							$_AfinalStringExecute[]=$_AaggregatePolicyDetails['string'];

						}
						$_SfinalExecuteString = '';

						$_SstringToExecute = $this->_Ocommon->_getStringToExecuteForAllPolicy($_AaggregatePolicyDetails['allPolicyArray'],$policyInputArray);
						if($_SstringToExecute != '')
							$_AfinalStringExecute[]=$_SstringToExecute;

						
						if(count((array)$_AfinalStringExecute) > 0)
							$_SfinalExecuteString=implode(" && ",$_AfinalStringExecute);
                    	$_IresultValue = 0;
						$_SresultStringToEval="IF( ".$_SfinalExecuteString." ){".'$_IresultValue'."=1;}";
						eval($_SresultStringToEval);
						if($_IresultValue==1)
						{
							$policyMasterValueArray[]=$rowSelectRequestPolicyMaster;
						}
					}
					
				}
			}
		}
		if(count((array)$policyMasterValueArray) > 0)
		{
			$finalInput=array("inputArray"=>$policyMasterValueArray,"firstFieldName"=>"priority","firstFieldOrder"=>"ASC","secondFieldName"=>"create_date","secondFieldOrder"=>"ASC");
			$finalArray=$this->_Ocommon->_multipleSortFunction($finalInput);
			$fetchRequestFieldPolicyArray[]=$finalArray[0];
			if(count((array)$fetchRequestFieldPolicyArray) > 0)
			{
				$returnFieldArray=$this->_getSSRMatrix($fetchRequestFieldPolicyArray);
				if(count((array)$returnFieldArray) > 0)
				{
					$returnArray=array();
					$returnArray=$returnFieldArray;
				}
			}
		}
		return $returnArray;
	}


	/*
	 * Get the matrix details based on applied policy
	 */
	function _getSSRMatrix($givenRequestPolicyArray)
	{
		global $CFG;
		$ssrArray=array();
		if(count((array)$givenRequestPolicyArray)>0 && !empty($givenRequestPolicyArray))
		{
			$this->_ArequestCriteriaDetails=array();
			
			$sqlSelectRequestCriteriaDetails="SELECT
									smm.matrix_id,
									smm.matrix_name,
									smm.status,
									smd.matrix_details_id,
									smd.request_criteria_field_id,
									smd.loop_value,
									om.operator_name,
									smd.criteria_value,
									(SELECT request_criteria_field_logical_name FROM ".$CFG['db']['tbl']['request_criteria_field_details']." WHERE request_criteria_field_id=smd.request_criteria_field_id) as logicalOperatorName
								FROM
									".$CFG['db']['tbl']['ssr_matrix_master']." smm,
									".$CFG['db']['tbl']['ssr_matrix_details']." smd,
									".$CFG['db']['tbl']['operator_master']." om
								WHERE
									smm.matrix_id=smd.matrix_master_id
									AND smd.operator_id=om.operator_id
									AND smm.matrix_id='".$givenRequestPolicyArray[0]['matrix_id']."'
									ORDER BY smd.request_criteria_field_id,smd.loop_value";
	
			if(DB::isError($resultSelectRequestCriteriaDetails=$this->_Oconnection->query($sqlSelectRequestCriteriaDetails)))
			{
				fileWrite($sqlSelectRequestCriteriaDetails,"SqlError","a+");
				return false;
			}

			if($resultSelectRequestCriteriaDetails->numRows() > 0)
			{
				while($rowSelectRequestCriteriaDetails=$resultSelectRequestCriteriaDetails->fetchRow(DB_FETCHMODE_ASSOC))
				{
					$this->_ArequestCriteriaDetails[]=$rowSelectRequestCriteriaDetails;
				}
			}
			
			if(isset($this->_ArequestCriteriaDetails) && !empty($this->_ArequestCriteriaDetails))
			{
				//Fetching the ssr details based on the selected ssr in the ssr matrix
				foreach($this->_ArequestCriteriaDetails as $key=>$value)
				{
					$sql = "SELECT 
								sl.ssr_list_id,
								sl.ssr_code,
								sl.ssr_description,
								scd.ssr_category_name,
								sl.ssr_subcategory_id
							FROM 
								".$CFG['db']['tbl']['ssr_list']." sl,
								".$CFG['db']['tbl']['ssr_category_details']." scd
							WHERE 
								sl.ssr_list_id In (".$value['criteria_value'].")
								AND sl.ssr_category_id = scd.ssr_category_id";

					if(DB::isError($result=$this->_Oconnection->query($sql)))
					{
						fileWrite($sql,"SqlError","a+");
						return false;
					}
					if($result->numRows() > 0)
					{
						$mealIndex = 0;
						$baggageIndex = 0;
						$othersIndex = 0;
						while($row=$result->fetchRow(DB_FETCHMODE_ASSOC))
						{
							if(strtoupper($value['criteria_value']) != 'N')
							{
								if(isset($CFG["ssr"]["skipSSRPolicy"]) && is_array($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['applyUniqueSsrCode']=='Y')
								{
									$ssrCode=$row['ssr_code'].'_'.str_replace(' ','_',strtoupper($row['ssr_description']));
									$ssrArray[$ssrCode]=$row;
								}
								else
									$ssrArray[$row['ssr_code']]=$row;
							}
						}
					}
				}
			}
		}
		
		return $ssrArray;
	}
	
	/*
	 * Get the ssr details added in the PNR for all passenger
	 */
	function _getSSRAvailabilityList()
	{
		global $CFG;
		
		$this->_AformValues['segmentDetails'] = $this->_prepareServiceFormValues();
		$this->_AformValues['PNR'] = $this->_Spnr;
		$this->_AformValues['currency'] = $this->_AuserCurrency['user_currency'];
		
		$this->_setViaFlightStatus($this->_AformValues['segmentDetails']);

		$this->_OairlineService->_Oconnection = $this->_Oconnection;
		$this->_OairlineService->__construct();
		$this->_OairlineService->_IrequestMasterId=$this->_IrequestMasterId;
		$this->_OairlineService->_AformValues=$this->_AformValues;
		
		$_AssrResponse = $this->_OairlineService->_airlinesBaggageDetailsForSSR();
		if(is_array($_AssrResponse) && $_AssrResponse['responseCode']==0)
		{
			$_AssrFlightDetails = $_AssrResponse['response']['segmentDetails'];
			if(isset($_AssrResponse['response']['ONDssr']))
				$this->_AondSsrDetails=$_AssrResponse['response']['ONDssr'];
			if(!isset($_AssrFlightDetails[0]))
				$_AssrFlightDetails = array($_AssrFlightDetails);
			
			$this->_AserviceSSRDetails = $_AssrFlightDetails;
			return true;
		}
		else
		{
			if(isset($_AssrResponse['response']) && $_AssrResponse['response']!="")
				$this->_OobjResponse->script("errorMessages('','".$_AssrResponse['response']."');");
			else
				$this->_OobjResponse->script("errorMessages('','".$this->_Osmarty->getConfigVars('COMMON_SERVICE_PROBLEM_TRY_AGAIN_LATER')."');");
			return false;
		}
	}
	
	/*
	 * Prepare the final ssr list based on flight segment
	 */
	function _prepareFinalSSRList()
	{
		global $CFG;
		$this->_AfinalSSRList = array();
		$_AserviceAvailableSSR = array();
		$_SondSSRstatus='N';
		if(isset($CFG["ssr"]["skipSSRPolicy"]) && is_array($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['ONDLevelSSR']=='Y')
			$_SondSSRstatus='Y';
		#$this->_AssrListPolicyValues = array();
		if(!empty($this->_AserviceSSRDetails) && isset($this->_AserviceSSRDetails[0]))
		{
			//Looping the ssr details array which is get from service for the flight segment
			foreach($this->_AserviceSSRDetails as $_IserviceSSRKey => $_SserviceSSRArray)
			{
				$_ScombinedFlightNumberKey='';
				foreach($_SserviceSSRArray['viaFlightDetails'] AS $ssrKey =>$_SserviceSSRValue)
				{
					$_SviaFlightStatus='N';
					$_AserviceSSRDetails = $_SserviceSSRValue['SSRDetails'];
					$_IreferenceKey = $this->_generateFlightReferenceKey($_SserviceSSRValue);
					if(!empty($this->_AondSsrDetails) && $_SondSSRstatus=='Y')
					{
						/* check ond SSR available and take OND SSR for the flight*/
						$this->_AmergingFlights=array();
						if(isset($this->_AssrListPolicyValues[$_ScombinedFlightNumberKey]) && $_ScombinedFlightNumberKey!='')
							$_SviaFlightStatus='Y';
						foreach ($this->_AondSsrDetails as $ondKey => $ondVal)
						{
							$_AcombinedSSR=array_column($ondVal['flights'], 'flightNumber');
							$this->_AmergingFlights[]=$_AcombinedSSR;
							if(in_array($_SserviceSSRValue['flightNumber'],$_AcombinedSSR))
							{
								$_AserviceSSRDetails = $ondVal['SSRDetails'];
								$_ScombinedFlightNumberKey=$_IreferenceKey;
							}
						}
					}
					
					/*If there is no ssr list prepared based on policy and matrix for the segment, 
					 * pull the ssr list from DB based on departure date of flight
					 */
					if(empty($this->_AssrListPolicyValues[$_IreferenceKey]))
					{
						$this->_AssrListPolicyValues[$_IreferenceKey] = $this->_Ocommon->_getSSRListDetails($_SserviceSSRValue['departureDate'],'Y');
					}
					//Initially set the display status for the ssr
					foreach($this->_AssrListPolicyValues[$_IreferenceKey] AS $ssrCode => &$ssrDetails){
						/*
						 * Unset the baggage details for via flight details except first flight 
						 **/
						if(isset($this->_AviaFlightStatus[$_SserviceSSRValue['viaFlightId']]) && $this->_AviaFlightStatus[$_SserviceSSRValue['viaFlightId']]=="N" || $_SviaFlightStatus=='Y')
						{
							if(in_array(strtoupper($ssrDetails['ssr_category_name']),array("BAGGAGE","OTHERS")))
								unset($this->_AssrListPolicyValues[$_IreferenceKey][$ssrCode]);
						}else
						{
							$ssrDetails['displayStatus'] = 'N';
							$ssrDetails['disabled'] = 'N';
							$ssrDetails['ssrAmount'] = 0;
							$ssrDetails['ssrBaseFare'] = 0;
							$ssrDetails['ssrTax'] = 0;
							$ssrDetails['ssrAvailable'] = 0;
						}
					}
					
					//Set the flight details for the ssr list to display based on segment
					$originName = $this->_Ocommon->_getAirportDetails($_SserviceSSRValue['origin']);
					$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails']['origin'] = $originName['airport_name'];
					$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails']['originCode'] = $_SserviceSSRValue['origin'];
					$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails']['pnrBlockingId'] = $_SserviceSSRValue['pnrBlockingId'];
					$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails']['requestApprovedFlightId'] = $_SserviceSSRValue['requestApprovedFlightId'];
					$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails']['viaFlightId'] = $_SserviceSSRValue['viaFlightId'];
					
					$destinationName = $this->_Ocommon->_getAirportDetails($_SserviceSSRValue['destination']);
					$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails']['destination'] = $destinationName['airport_name'];
					$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails']['destinationCode'] = $_SserviceSSRValue['destination'];
					$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetailsNew']['destinationCode'] = $_SserviceSSRValue['destination'];
					$departureDate = date('d-M-Y H:i',strtotime($_SserviceSSRValue['departureDate']." ".$_SserviceSSRValue['departureTime']));
					
					/*
					 * Modified by: Subalakshmi S 04-09-2018 
					 * To disable ancillaries based on departure date validation
					 */
					$_DdepartureDate = strtotime($departureDate);
					$_DoriginCurrentDate = $this->_Ocommon->_getAirportCurrentTime($_SserviceSSRValue['origin']);
					$_DvalidationDate = date("Y-m-d H:i:s", strtotime($_DoriginCurrentDate.'+'.$CFG['settings']['ssrJourneyCondition'].' hours'));
					$_DvalidationDate = strtotime($_DvalidationDate);
					$_Sdisabled = 'N';
					if(!isset($CFG['site']['contractManager']) || (isset($CFG['site']['contractManager']) && $CFG['site']['contractManager']['status'] == 'N'))
					{
						if($_DdepartureDate <= $_DvalidationDate) 
							$_Sdisabled = 'Y';
					}
					$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails']['departureDate'] = $departureDate;
					$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails']['flightNumber'] = $_SserviceSSRValue['carrierCode']."-".$_SserviceSSRValue['flightNumber'];
					
					$_AavailableSSR = array_keys($this->_AssrListPolicyValues[$_IreferenceKey]);
					foreach($_AserviceSSRDetails AS $_IserviceKey => &$_IserviceSSRValue)
					{
						$_SssrCode = $_IserviceSSRValue['SSRCode'];
						if(isset($CFG["ssr"]["skipSSRPolicy"]) && is_array($CFG["ssr"]["skipSSRPolicy"]) && ($CFG["ssr"]["skipSSRPolicy"]['status']=='Y' || $CFG["ssr"]["skipSSRPolicy"]['applyUniqueSsrCode']=='Y'))
							$_SssrCode = $_IserviceSSRValue['SSRCode'].'_'.str_replace(' ','_', strtoupper($_IserviceSSRValue['SSRName']));
						if(in_array($_SssrCode,$_AavailableSSR))
						{
							foreach($this->_AssrListPolicyValues[$_IreferenceKey] AS $_IssrListPolicyKey => &$_IssrListPolicyValue)
							{
								if($_IssrListPolicyKey==$_SssrCode)
								{
									$_IssrListPolicyValue['ssrTax'] = $this->_Ocommon->_getRoundOffFare(($_IserviceSSRValue['totalPrice']-$_IserviceSSRValue['basePrice']),'',$this->_ScurrencyCode);
									$_IssrListPolicyValue['ssrAmount'] = $this->_Ocommon->_getRoundOffFare($_IserviceSSRValue['totalPrice'],'',$this->_ScurrencyCode);
									$_IssrListPolicyValue['ssrBaseFare'] = $this->_Ocommon->_getRoundOffFare($_IserviceSSRValue['basePrice'],'',$this->_ScurrencyCode);

									// Display amount with comma format
									$_IssrListPolicyValue['ssrTaxDisplay'] = $this->_Ocommon->_getRoundOffFare(($_IserviceSSRValue['totalPrice']-$_IserviceSSRValue['basePrice']),'','displayFare');
									$_IssrListPolicyValue['ssrAmountDisplay'] = $this->_Ocommon->_getRoundOffFare($_IserviceSSRValue['totalPrice'],'','displayFare');
									$_IssrListPolicyValue['ssrBaseFareDisplay'] = $this->_Ocommon->_getRoundOffFare($_IserviceSSRValue['basePrice'],'','displayFare');
									$_IssrListPolicyValue['additional_info'] = $_IserviceSSRValue['AddtionalInfo'];
									#prepare data for SSR list
									$_IssrListPolicyValue['SSRVendor'] = $_IserviceSSRValue['SSRVendor'];
									$_IssrListPolicyValue['SSRName'] = $_IserviceSSRValue['SSRName'];
									$_IssrListPolicyValue['SSRType'] = $_IserviceSSRValue['SSRType'];
									$_IssrListPolicyValue['GroupDescription'] = $_IserviceSSRValue['GroupDescription'];

									$_IssrListPolicyValue['additional_info']['SSRVendor'] = $_IserviceSSRValue['SSRVendor'];
									$_IssrListPolicyValue['additional_info']['SSRName'] = $_IserviceSSRValue['SSRName'];
									$_IssrListPolicyValue['additional_info']['SSRType'] = $_IserviceSSRValue['SSRType'];
									/* sent as array in update ssr service issue fixing*/
									if($_IserviceSSRValue['AddtionalInfo']['SegmentIndicator']=="")
										$_IssrListPolicyValue['additional_info']['SegmentIndicator']="";
									if(isset($_IserviceSSRValue['FeeApplicationType']))
										$_IssrListPolicyValue['FeeApplicationType'] = $_IserviceSSRValue['FeeApplicationType'];
									
									if(isset($_IssrListPolicyValue['infantStatus']) && $_IssrListPolicyValue['infantStatus']=='N')
									{
										$_IssrListPolicyValue['displayStatus'] = 'N';
										$_IssrListPolicyValue['disabled'] = 'Y';
									}
									else if($_Sdisabled == 'Y')
									{
										$_IssrListPolicyValue['displayStatus'] = 'N';
										$_IssrListPolicyValue['disabled'] = 'Y';
									}
									else
									{
										//Enable the status once the ssr available in the flight
										$_IssrListPolicyValue['displayStatus'] = 'Y';
										$_IssrListPolicyValue['disabled'] = 'N';
									}
									//Set the ssr available
									if($_IserviceSSRValue['InventoryControlled']==true)
										$_IssrListPolicyValue['ssrAvailable'] = $_IserviceSSRValue['Available'];
									if(isset($_IserviceSSRValue['flightSegments']) && $_SondSSRstatus=='Y')
										$_IssrListPolicyValue['flightSegments'] = $_IserviceSSRValue['flightSegments'];
									#preapre flight number which is having ond level SSR
									if(!empty($this->_AmergingFlights))
										$_IssrListPolicyValue['flightNumber'] = $this->_AmergingFlights;
									if($_IssrListPolicyValue['displayStatus'] == 'Y')
									{
										$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails'][strtolower($_IssrListPolicyValue['ssr_category_name']).'DisplayStatus']= 'Y';
									}
									$_AserviceAvailableSSR[$_IreferenceKey][]=$_IssrListPolicyKey;
								}
							}
						}
					}
					#unset the SSR for remaining connecting flights
					if($_SondSSRstatus=='Y')
					{
						foreach ($this->_AssrListPolicyValues[$_IreferenceKey] as $policyKey => $policyVal)
						{
							if(!in_array($policyKey,$_AserviceAvailableSSR[$_IreferenceKey]) && $policyKey!='flightDetails')
								unset($this->_AssrListPolicyValues[$_IreferenceKey][$policyKey]);
						}
					}
					$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails']['disableAncillaries'] = $_Sdisabled;
					if($_Sdisabled == 'Y')
						$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails']['disableMsg']=str_replace('%S',$CFG["settings"]["ssrJourneyCondition"],$this->_Osmarty->getConfigVars('COMMON_NOT_ALLOWED_ADD_ANCILLARIES_BASED_JOURNEY_TIME'));

					$_AssrInfo = array('baggage','meals','others');
					foreach($_AssrInfo as $_SssrValue)
					{
						$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails'][$_SssrValue.'_status'] = 'N';
						$this->_AdisableCancelOption[$_IreferenceKey][$_SssrValue] = 'Y';
						if($this->_getExpiryTime($_SserviceSSRValue['requestApprovedFlightId'],$_SssrValue) == 'Y')
						{
							$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails'][$_SssrValue.'DisplayStatus'] = 'N';
							$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails'][$_SssrValue.'_status'] = 'Y';
							$this->_AssrListPolicyValues[$_IreferenceKey]['flightDetails'][$_SssrValue.'_statusMsg'] = 'Timelimit Expired';
							$this->_AdisableCancelOption[$_IreferenceKey][$_SssrValue] = 'N';
						}
						if(isset($CFG['site']['contractManager']) && $CFG['site']['contractManager']['status']=="Y")
							$this->_getSSRExpiryDate($this->_IrequestMasterId,$_SserviceSSRValue['requestApprovedFlightId']);
						if(isset($CFG["queueSync"]["offlineSync"]["ancillarySync"]) && $CFG["queueSync"]["offlineSync"]["ancillarySync"]["status"]=="Y" && $CFG["queueSync"]["offlineSync"]["ancillarySync"]["cancelTimelimit"]=="Y")
						{
							$this->_AdisableCancelOption[$_IreferenceKey][$_SssrValue] = 'Y';
							if($this->_getExpiryTime($_SserviceSSRValue['requestApprovedFlightId'],$_SssrValue,'Y') == 'Y')
							{
								$this->_AdisableCancelOption[$_IreferenceKey][$_SssrValue] = 'N';
							}
						}
					}
				}
			}
		}
		/*
		 * Prepare the final ssr list based on ssr category name
		 * We have flightDetails array in the final array to display the segment details
		 */
		foreach($this->_AssrListPolicyValues AS $_Ikey => $_Svalue)
		{
			foreach($_Svalue as $subKey => $subValue)
			{
				if(strtoupper($subKey)=='FLIGHTDETAILS')
				{
					$this->_AfinalSSRList[$_Ikey]['flightDetails'] = $subValue;
					$_AtempSSRList[$_Ikey]['flightDetails'] = $subValue;
				}
				else
				{
					$this->_AfinalSSRList[$_Ikey][strtolower($subValue['ssr_category_name'])][$subValue['ssr_code']] = $subValue;
					$_AtempSSRList[$_Ikey]['category'] = $this->_AfinalSSRList[$_Ikey];
				}
				// SSR Count Display Feature: Collect SSR codes by Nest
				if (isset($subValue['additional_info']['Nest'])) {
					$nest = $subValue['additional_info']['Nest'];
					$ssrCode = $subValue['ssr_code'];
					$this->_AnestServiceSSRValue[$nest][] = $ssrCode;
				}
			}
			/*Anboli M 08-04-2020 - In order to ordering the SSR to show the ancillaries orderly in review panel*/
			//if($CFG["ssr"]["skipSSRPolicy"]['status']=='Y')
			{
				if(!empty($this->_Acategory))
				{
					unset($this->_AfinalSSRList[$_Ikey]);
					foreach ($this->_Acategory as $key => $value) {
						if($_AtempSSRList[$_Ikey]['category'][$value])
							$this->_AfinalSSRList[$_Ikey][$value] = $_AtempSSRList[$_Ikey]['category'][$value];
					}
					$this->_AfinalSSRList[$_Ikey]['flightDetails']=$_AtempSSRList[$_Ikey]['flightDetails'];
				}
			}
			unset($_AtempSSRList);
		}
	}
	
	/*
	 * When open the SSR page for first time, add the flight ids, pnr for each pax by default 
	 * Information will be inserted for the first time only
	 */
	 function _setPaxDetailsForSSR()
	 {
		global $CFG;
		
		$this->_OssrPaxDetails->_Oconnection = $this->_Oconnection;
		
		//Flight details for the PNR
		$_AflightDetails = $this->_prepareServiceFormValues();
		//finding the pax count
		$this->_OssrPaxDetails->__construct();
		$this->_OssrPaxDetails->_IpnrBlockingId = $this->_SpnrBlockingIdInString;
		$this->_OssrPaxDetails->_SgroupBy = 'pax_reference_id';
		//$this->_OssrPaxDetails->_Sstatus = 'Y';
		$this->_OssrPaxDetails->_selectSsrPaxDetails();
		$_IssrPaxCount = $this->_OssrPaxDetails->_IcountLoop;
		//passenger details table count
		$_IPassengerDetailsTableCount = count((array)$this->_ApassengerDetails);
		//Pax count in the PNR
		$_IpnrPaxCount = count((array)$this->_AfinalPassengerSSRList);
		//Looping all the passenger in the PNR
		for($paxToInsert=0;$paxToInsert<$_IpnrPaxCount;$paxToInsert++){
			//Inserting the pax details in the table if previously not insert
			if($_IssrPaxCount <= $paxToInsert) {
				//Inserting all flight details for all pax for the PNR
				foreach($_AflightDetails as $flightKey => $flightValue){
					
					foreach($flightValue['viaFlightDetails'] AS $flightId =>$flightArray) {
						
						$this->_OssrPaxDetails->__construct();
						$this->_OssrPaxDetails->_IpnrBlockingId = $flightArray['pnrBlockingId'];
						$this->_OssrPaxDetails->_IpaxReferenceId = $this->_AfinalPassengerSSRList[$paxToInsert]['paxNum'];
						
						$this->_OssrPaxDetails->_IviaFlightId = $flightArray['viaFlightId'];
						$this->_OssrPaxDetails->_IpassengerId = (isset($this->_AfinalPassengerSSRList[$paxToInsert]['passengerId']) ? $this->_AfinalPassengerSSRList[$paxToInsert]['passengerId'] : 0);
						if($this->_OssrPaxDetails->_IpassengerId == 0)
						{
							$_pnrBlockingInsertedZero ='Y';
							filewrite(print_r($_AflightDetails,1),'flightDetailsSSR','a+');
							filewrite(print_r($this->_AfinalPassengerSSRList,1),'finalPassengerSsrList','a+');
							
						}
						$this->_OssrPaxDetails->_Istatus = 'Y';
						$this->_OssrPaxDetails->_insertSsrPaxDetails();
						if(is_string($this->_AfinalPassengerSSRList[$paxToInsert]['paxNum']))
							$this->_ApaxReferenceId[$this->_OssrPaxDetails->_IpaxReferenceId] = $this->_AfinalPassengerSSRList[$paxToInsert]['paxNum'];
						else
							$this->_ApaxReferenceId[floor($this->_OssrPaxDetails->_IpaxReferenceId)] = $this->_AfinalPassengerSSRList[$paxToInsert]['paxNum'];
					}
				}
			}
			//Updating the passenger id if name update done for the passenger
			else {
				if(is_string($this->_AfinalPassengerSSRList[$paxToInsert]['paxNum']))
					$this->_ApaxReferenceId[$this->_AfinalPassengerSSRList[$paxToInsert]['paxNum']] = $this->_AfinalPassengerSSRList[$paxToInsert]['paxNum'];
				else
					$this->_ApaxReferenceId[floor($this->_AfinalPassengerSSRList[$paxToInsert]['paxNum'])] = $this->_AfinalPassengerSSRList[$paxToInsert]['paxNum'];
				if(isset($this->_AfinalPassengerSSRList[$paxToInsert]['passengerId'])) {
					$this->_OssrPaxDetails->__construct();
					$this->_OssrPaxDetails->_IpnrBlockingId = $this->_SpnrBlockingIdInString;
					$this->_OssrPaxDetails->_INcondition = "IN";
					$this->_OssrPaxDetails->_IpaxReferenceId = $this->_AfinalPassengerSSRList[$paxToInsert]['paxNum'];
					$this->_OssrPaxDetails->_IpassengerId = $this->_AfinalPassengerSSRList[$paxToInsert]['passengerId'];
					if($this->_OssrPaxDetails->_IpassengerId == 0)
					{
						$_pnrBlockingInsertedZero ='Y';
						filewrite(print_r($this->_AfinalPassengerSSRList,1),'finalPassengerSsrListUpdate','a+');
					}
					$this->_OssrPaxDetails->_updateSsrPaxDetails();
				}
			}
		}
		//while passenger id inserted to 0 in ssr_pax_details ,we will delete the entire row of against pnrBlockingId.
		if(isset($_pnrBlockingInsertedZero) && $_pnrBlockingInsertedZero =='Y' && $_IPassengerDetailsTableCount==$_IpnrPaxCount)
		{
			$sqlSsrPaxDetailsDelete = "DELETE FROM ".$CFG['db']['tbl']['ssr_pax_details']."  WHERE pnr_blocking_id IN(".$this->_SpnrBlockingIdInString.")";
			fileWrite($sqlSsrPaxDetailsDelete,"ssrPaxDetailsDelete","a+");
			if(DB::isError($resultPax=$this->_Oconnection->query($sqlSsrPaxDetailsDelete)))
			{
				fileWrite($sqlSsrPaxDetailsDelete,"SqlError","a+");
				return false;
			}
            $this->_pnrPassengerIDZero ='Y';
			$this->_OobjResponse->script("commonObj.closeGrmPopup(true);");
			$this->_OobjResponse->call("commonObj.showSuccessMessage",$this->_Osmarty->getConfigVars('UNABLE_TO_ASSIGN_SELECTED_SEAT'));
		}

		/*Anboli M - 19-03-2020 To set the paxnum associated to pax reference id*/
		if(!empty($this->_ApaxReferenceId) && $this->_SapiCall == "N")
		{
			$this->_OobjResponse->script("ssrProcessObj.SSRPaxReference=".json_encode($this->_ApaxReferenceId).";");
		}
	 }
	 
	/*
	 * Preparing the pnr blocking id string based on PNR
	 * It can be use to fetch the ssr pax details based on the pnr blocking id
	 */
	function _setPnrBlockingIdInString()
	{
		global $CFG;
		
		$this->_OpnrBlockingDetails->_Oconnection = $this->_Oconnection;
		$this->_OpnrBlockingDetails->__construct();
		$this->_OpnrBlockingDetails->_Spnr=$this->_Spnr;
		$this->_OpnrBlockingDetails->_IrequestMasterId = $this->_IrequestMasterId;
		$this->_ApnrBlockingDetails=$this->_OpnrBlockingDetails->_selectPnrBlockingDetails();
		
		$separator = "";
		foreach($this->_ApnrBlockingDetails AS $pnrBlockingIndex => $_ApnrBlockingArray) {
			$this->_SpnrBlockingIdInString .= $separator.$_ApnrBlockingArray['pnr_blocking_id'];
			$separator = ",";
		}
		return $this->_SpnrBlockingIdInString;
	}
	
	/*
	 * Getting the ssr list for the passenger in the pnr
	 * Preparing the final array for the passenger ssr list
	 */
	function _getSSRListForPassenger()
	{
		global $CFG;		
		//insert dummy row for passenger details table,if already inserted it won't happen.		
		fileRequire("classesModule/class.module.seatSelection.php");
		$this->_AseatSelection=new seatSelection();
		$this->_AseatSelection->_Oconnection=$this->_Oconnection;
		$this->_AseatSelection->_Osmarty = $this->_Osmarty;
		$this->_AseatSelection->_OobjResponse = $this->_OobjResponse;
		if(empty($this->_IinputData['pnr']) || empty($this->_IinputData['requestMasterId']))
		{
			$this->_IinputData['pnr']= $this->_Spnr;
			$this->_IinputData['requestMasterId']= $this->_IrequestMasterId;
		}
		$this->_AseatSelection->_setDummyPassengerList($this->_IinputData);
		
		if(empty($this->_Spnr) || strpos(trim($this->_Spnr),"GROUP") !== false)
			return true;
		$this->_AfinalPassengerSSRList = array();
		
		//Get the ssr list for the passenger in the pnr
		$this->_OairlineService->__construct();
		$this->_OairlineService->_Oconnection = $this->_Oconnection;
		$this->_OairlineService->_Spnr = $this->_Spnr;
		$this->_OairlineService->_StypeOfSsr = $this->_StypeOfSsr;
		$this->_OairlineService->_IrequestMasterId = $this->_IrequestMasterId;
		$_AgetSSRDetailsForPNR = $this->_OairlineService->_getSSRDetailsForPNR();
		$paxIdArray=array();
		$_SondSSRstatus='N';
		if(isset($CFG["ssr"]["skipSSRPolicy"]) && is_array($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['ONDLevelSSR']=='Y')
			$_SondSSRstatus='Y';
		if($_AgetSSRDetailsForPNR['responseCode']==0)
		{
			if(isset($_AgetSSRDetailsForPNR['response']['paxSSR']) && !empty($_AgetSSRDetailsForPNR['response']['paxSSR']))
			{
				$this->_OpassengerDetails->_Oconnection = $this->_Oconnection;
				$this->_OpassengerDetails->_Spnr = $this->_Spnr;
				$this->_OpassengerDetails->_SneedDummyRows = 'Y';
				$this->_ApassengerDetails = $this->_OpassengerDetails->_selectPassengerDetails();
				
				$_ApassengerSSR = $_AgetSSRDetailsForPNR['response']['paxSSR'];
				$_IpaxSSRCount = count((array)$_ApassengerSSR);
				
				/* Preparing the passenger details in the array if name update done for the passenger
				 * Set the first name and last name for the passenger from DB if name update done
				 */
				for($paxSSRIndex=0;$paxSSRIndex<$_IpaxSSRCount;$paxSSRIndex++)
				{
					$_ApassengerSSRDetails = $_ApassengerSSR[$paxSSRIndex];
					//Initially set the ssr existing as 'N' for the passenger
					$this->_AfinalPassengerSSRList[$paxSSRIndex]['ssrExists'] = "N";
					$this->_AfinalPassengerSSRList[$paxSSRIndex]['paxNum'] = $_ApassengerSSRDetails['nameId']?$_ApassengerSSRDetails['nameId']:$paxSSRIndex;

					$_IapprovedFlightId=0;
					foreach($this->_AformValues['segmentDetails'] AS $mainFlightKey =>$mainFlightValue)
					{
						foreach($mainFlightValue['viaFlightDetails'] AS $flightKey => $flightValue)
						{
							$_SreferenceKey= $this->_generateFlightReferenceKey($flightValue);
							$_SviaBaggagereferenceKey = '';
							if(isset($this->_AviaFlightStatus[$flightValue['viaFlightId']]) && $this->_AviaFlightStatus[$flightValue['viaFlightId']]=="Y")
							{
								$_AtempMergedViaFlightDetails = $this->_AmergedViaFlightDetails[$flightValue['viaFlightId']];
								$_SviaBaggagereferenceKey = $this->_generateFlightReferenceKey($_AtempMergedViaFlightDetails);
							}
							//Set the first name and last name to the array from pnr
							$this->_AfinalPassengerSSRList[$paxSSRIndex]['firstName'] = $_ApassengerSSRDetails['FirstName'];
							$this->_AfinalPassengerSSRList[$paxSSRIndex]['lastName'] = $_ApassengerSSRDetails['LastName'];
							$this->_AfinalPassengerSSRList[$paxSSRIndex]['paxType'] = $_ApassengerSSRDetails['paxType'];
							$this->_AfinalPassengerSSRList[$paxSSRIndex]['withInfant'] = $_ApassengerSSRDetails['withInfant'];
							
							//Checking the dummy name present for the passenger in the pnr
							$firstNameCheckArray = array('ADT','PAX','CHD','INT','TEST');
							$firstNameCheck = in_array($_ApassengerSSRDetails['FirstName'],$firstNameCheckArray);
							//finding the passenger id based on first name and last name for the pnr
							if($firstNameCheck===false)
							{
								$paxType = ($_ApassengerSSRDetails['paxType']=="ADT" ? "ADULT":($_ApassengerSSRDetails['paxType']=="CHD" ? "CHILD" : ""));
								if(!empty($CFG['nameUpdate']['pnrWiseIssueTicket']['customerInfoGivenNameTitle']) && ($CFG['nameUpdate']['pnrWiseIssueTicket']['customerInfoGivenNameTitle'] == 'Y'))
									$_SfirstName = "CAST(concat(".encrypt::_decrypt('first_name').",' ',".encrypt::_decrypt('title').") AS CHAR)=
									'".$this->_Oconnection->escapeSimple(strtoupper($_ApassengerSSRDetails['FirstName']))."'";
								else
									$_SfirstName = "CAST(".encrypt::_decrypt('first_name')." AS CHAR)='".$this->_Oconnection->escapeSimple(strtoupper($_ApassengerSSRDetails['FirstName']))."'";
								$sqlNameCheck="SELECT 
										passenger_id
									FROM
										".$CFG['db']['tbl']['passenger_details']."
									WHERE
										UPPER(passenger_type)='".strtoupper($paxType)."'
										AND ".$_SfirstName."
										AND CAST(".encrypt::_decrypt('last_name')." AS CHAR)='".$this->_Oconnection->escapeSimple(strtoupper($_ApassengerSSRDetails['LastName']))."'
										AND pnr='".$this->_Spnr."'";
								if(DB::isError($resultNameCheck = $this->_Oconnection->query($sqlNameCheck)))
								{
									fileWrite($sqlNameCheck,"SqlError","a+");
									return false;
								}
								//Set the passenger id to the array if the name found in the DB for the pnr
								if ($resultNameCheck->numRows() > 0)
								{
									$rowNameCheck=$resultNameCheck->fetchRow(DB_FETCHMODE_ASSOC);
									$paxIdArray[]=$rowNameCheck['passenger_id'];
									$this->_AfinalPassengerSSRList[$paxSSRIndex]['passengerId'] = $rowNameCheck['passenger_id'];
								}
							}
							//Set the name for passenger which is updated in the DB and not in the PNR if there is any passenger details left to assign
							if(!empty($this->_ApassengerDetails[$paxSSRIndex]) && !in_array($this->_ApassengerDetails[$paxSSRIndex]['passenger_id'],$paxIdArray) && !isset($this->_AfinalPassengerSSRList[$paxSSRIndex]['passengerId']))
							{
								$this->_AfinalPassengerSSRList[$paxSSRIndex]['firstName'] = $this->_ApassengerDetails[$paxSSRIndex]['first_name'];
								$this->_AfinalPassengerSSRList[$paxSSRIndex]['lastName'] = $this->_ApassengerDetails[$paxSSRIndex]['last_name'];
								$this->_AfinalPassengerSSRList[$paxSSRIndex]['paxType'] = $this->_ApassengerDetails[$paxSSRIndex]['passenger_type'];
								$this->_AfinalPassengerSSRList[$paxSSRIndex]['passengerId'] = $this->_ApassengerDetails[$paxSSRIndex]['passenger_id'];
								$paxIdArray[]=$this->_ApassengerDetails[$paxSSRIndex]['passenger_id'];
							}
							if($this->_StypeOfSsr == 'SEAT')
							{
								$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey] = array();
								
								if(isset($_ApassengerSSRDetails['AncillaryServices']) && !empty($_ApassengerSSRDetails['AncillaryServices']))
								{
									foreach($_ApassengerSSRDetails['AncillaryServices'] AS $availableSSRKey => $availableSSRValue)
									{
										if(!empty($availableSSRValue['SeatNumber']))
										{
											if(str_replace(" ","",$availableSSRValue['FlightReference'])==$_SreferenceKey)
											{
												$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey]['seat']['existing']['seatDesignator'] = $availableSSRValue['SeatNumber'];
												$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey]['seat']['existing']['seatFee'] = $availableSSRValue['totalPrice'];
												$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey]['seat']['existing']['seatPreference'] = $availableSSRValue['seatPreference'];
												$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey]['seat']['existing']['emdNumber'] = $availableSSRValue['EMDNumber'];
												$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey]['seat']['newSeat']['seatDesignator'] = $availableSSRValue['SeatNumber'];
												$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey]['seat']['newSeat']['seatFee'] = $availableSSRValue['totalPrice'];
												$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey]['seat']['newSeat']['emdNumber'] = $availableSSRValue['EMDNumber'];
											}
										}
									}
								}
							}
							/*
							 * Set the ssr details for the ssr which is added for the passenger
							 */

							elseif(isset($this->_AssrListPolicyValues[$_SreferenceKey]))
							{
								$_AavailableSSR = array_keys($this->_AssrListPolicyValues[$_SreferenceKey]);
								$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey] = array();
								
								if(isset($_ApassengerSSRDetails['AncillaryServices']) && !empty($_ApassengerSSRDetails['AncillaryServices']))
								{
									foreach($_ApassengerSSRDetails['AncillaryServices'] AS $availableSSRKey => $availableSSRValue)
									{
										$_SflightReference = str_replace(" ","",$availableSSRValue['FlightReference']);
										if(($_SflightReference==$_SreferenceKey || ($_SflightReference == $_SviaBaggagereferenceKey && $_SviaBaggagereferenceKey!="")) || strpos($_SflightReference, $_SreferenceKey) !== false)/*&& in_array($availableSSRValue['SSRCode'],$_AavailableSSR)*/
										{
											if(isset($CFG["ssr"]["skipSSRPolicy"]) && ($CFG["ssr"]["skipSSRPolicy"]['status']=='Y' || $CFG["ssr"]["skipSSRPolicy"]['applyUniqueSsrCode']=='Y'))
											{
												$availableSSRValue['SSRCode'] = $availableSSRValue['SSRCode'].'_'.str_replace(" ","_",strtoupper($availableSSRValue['CommercialName']));
											}
											$_AselectedSSRDetails = $this->_AssrListPolicyValues[$_SreferenceKey][$availableSSRValue['SSRCode']];
											/* Keep the ssr price for the selected ssr from passenger ssr list
											 * if the ssr is not available in the current ssr list 
											 * Prabhu - Overrided the PNR SSR Fare, When the SSR fare is exists.
											 */
											if(isset($this->_AssrListPolicyValues[$_SreferenceKey][$availableSSRValue['SSRCode']])/* && $this->_AssrListPolicyValues[$_SreferenceKey][$availableSSRValue['SSRCode']]['displayStatus']!="Y"*/ || $_SondSSRstatus=='Y') {
												if(!empty($_AselectedSSRDetails))
												{
													$_AselectedSSRDetails['displayStatus']="Y";
													$_AselectedSSRDetails['ssrId'] = $availableSSRValue['id'];
													$this->_AfinalSSRList[$_SreferenceKey][strtolower($_AselectedSSRDetails['ssr_category_name'])][$availableSSRValue['SSRCode']] = $_AselectedSSRDetails;
													if($this->_AssrListPolicyValues[$_SreferenceKey][$availableSSRValue['SSRCode']]['displayStatus']!="Y") {
														$this->_AfinalSSRList[$_SreferenceKey][strtolower($_AselectedSSRDetails['ssr_category_name'])][$availableSSRValue['SSRCode']]['disabled'] = 'Y';
														$this->_AfinalSSRList[$_SreferenceKey][strtolower($_AselectedSSRDetails['ssr_category_name'])][$availableSSRValue['SSRCode']]['displayStatus'] = 'Y';
													}
													// different fare from service for meals before pnr blocking.so,we taking ssr amount from availability service.
													$_AselectedSSRDetails['ssrAmount'] =  $this->_Ocommon->_getRoundOffFare($availableSSRValue['totalPrice'],'',$this->_ScurrencyCode);
													$_AselectedSSRDetails['ssrBaseFare'] = $availableSSRValue['basePrice'];
													$_AselectedSSRDetails['ssrTax'] = $this->_Ocommon->_getRoundOffFare(($availableSSRValue['totalPrice']-$availableSSRValue['basePrice']),'',$this->_ScurrencyCode);
													if(isset($CFG["ssr"]["instantPayment"]) && $CFG["ssr"]["instantPayment"]["status"] =="Y")
													{
														unset($this->_AfinalSSRList[$_SreferenceKey][strtolower($_AselectedSSRDetails['ssr_category_name'])][$availableSSRValue['SSRCode']]['ssrId']);
													}
												}
											}
											/*
											 * If selected ssr is not available in default ssr array, and available for passenger, then set the ssr details in the array
											 **/
											else
											{
												$_AtempSSRDetails = $this->_Ocommon->_getSSRListDetails('',"Y",$availableSSRValue['SSRCode']);
												if(!empty($_AtempSSRDetails))
												{
													$_AselectedSSRDetails['ssr_list_id'] = $_AtempSSRDetails[$availableSSRValue['SSRCode']]['ssr_list_id'];
													$_AselectedSSRDetails['ssr_category_name'] = $_AtempSSRDetails[$availableSSRValue['SSRCode']]['ssr_category_name'];
													$_AselectedSSRDetails['ssr_description'] = $_AtempSSRDetails[$availableSSRValue['SSRCode']]['ssr_description'];
													$_AselectedSSRDetails['ssr_code']=$availableSSRValue['SSRCode'];
													$_AselectedSSRDetails['displayStatus']="Y";
													$_AselectedSSRDetails['ssrAmount'] = $availableSSRValue['totalPrice'];
													$_AselectedSSRDetails['ssrBaseFare'] = $availableSSRValue['basePrice'];
													$_AselectedSSRDetails['ssrId'] = $availableSSRValue['id'];
													$_AselectedSSRDetails['ssrTax'] = $this->_Ocommon->_getRoundOffFare(($availableSSRValue['totalPrice']-$availableSSRValue['basePrice']),'',$this->_ScurrencyCode);
													$this->_AfinalSSRList[$_SreferenceKey][strtolower($_AtempSSRDetails[$availableSSRValue['SSRCode']]['ssr_category_name'])][$availableSSRValue['SSRCode']] = $_AselectedSSRDetails;
													$this->_AfinalSSRList[$_SreferenceKey][strtolower($_AtempSSRDetails[$availableSSRValue['SSRCode']]['ssr_category_name'])][$availableSSRValue['SSRCode']]['disabled'] = 'Y';
												}
											}
											if(!isset($this->_AfinalSSRList[$_SreferenceKey]['flightDetails']['othersDisplayStatus']) && isset($this->_AfinalSSRList[$_SreferenceKey]['others']))
												$this->_AfinalSSRList[$_SreferenceKey]['flightDetails']['othersDisplayStatus']='Y';
											if(!empty($_AselectedSSRDetails))
											{
												$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey][strtolower($_AselectedSSRDetails['ssr_category_name'])]['existing'][$_AselectedSSRDetails['ssr_code']]=$_AselectedSSRDetails;
												$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey][strtolower($_AselectedSSRDetails['ssr_category_name'])]['newSSR'][$_AselectedSSRDetails['ssr_code']]=$_AselectedSSRDetails;
												
												/* Set the ssrExists as 'Y' if ssr already added for the passenger
												 * Based on this value, we will display the passenger in the view ancillaries list part
												 */
												if($_AselectedSSRDetails['ssr_category_name'] !='')
													$this->_AfinalPassengerSSRList[$paxSSRIndex]['ssrExists'] = "Y";
												//Based on this, select ssr icon will display in the passenger list part
												if(!in_array(strtolower($_AselectedSSRDetails['ssr_category_name']),(array)$this->_AfinalPassengerSSRList[$paxSSRIndex]['ssrIcons']))
													$this->_AfinalPassengerSSRList[$paxSSRIndex]['ssrIcons'][] = strtolower($_AselectedSSRDetails['ssr_category_name']);
												if($_SondSSRstatus=='Y' && $_IapprovedFlightId!=0 && $_IapprovedFlightId==$flightValue['requestApprovedFlightId'])
												{
													$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey]=array();
													$_AtempFlightDetails=$this->_AfinalSSRList[$_SreferenceKey]['flightDetails'];
													$this->_AfinalSSRList[$_SreferenceKey]=array();
													$this->_AfinalSSRList[$_SreferenceKey]['flightDetails']=$_AtempFlightDetails;
												}
											}
											if(isset($availableSSRValue['EMDNumber']) && $availableSSRValue['EMDNumber']>0)
											{
												$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey][strtolower($_AselectedSSRDetails['ssr_category_name'])]['newSSR'][$_AselectedSSRDetails['ssr_code']]['EMDNumber']=$availableSSRValue['EMDNumber'];
												$this->_AfinalPassengerSSRList[$paxSSRIndex][$_SreferenceKey][strtolower($_AselectedSSRDetails['ssr_category_name'])]['existing'][$_AselectedSSRDetails['ssr_code']]['EMDNumber']=$availableSSRValue['EMDNumber'];
											}
										}
									}
								}
							}
							$_IapprovedFlightId=$flightValue['requestApprovedFlightId'];
						}
					}
				}
			}
		}
		else
		{
			$this->_OobjResponse->script("errorMessages('','".$_AgetSSRDetailsForPNR['response']."');");
			return false;
		}
		
	}
	/*
	 * Generate the flight referencekey based on flight details
	 * Pattern : 201709014U24CGNTXL
	 */
	function _generateFlightReferenceKey($flightValue)
	{
		global $CFG;
		$_DdepartureDate = explode("T",$flightValue['departureDateAndTime']);
		$referenceKey = str_replace("-","",$_DdepartureDate[0]).$flightValue['carrierCode'].$flightValue['flightNumber'].$flightValue['origin'].$flightValue['destination'];
		return $referenceKey;
	}
	
	/*
	 * Submitting the selected SSR to service for selected passenger in the pnr
	 */
	function _addSSRToPnr()
	{
		global $CFG;
		
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$this->_Ocommon->_Osmarty = $this->_Osmarty;
		$this->_Ocommon->_OobjResponse = $this->_OobjResponse;
		$this->_IcurrentStatus = $this->_Ocommon->_getStatusFromRequestId($this->_IrequestMasterId);
		
		/*
		 * Modified by: Subalakshmi S 04-09-2018 
		 * Check the basic validation before proceeding to add ancillaries
		 */
		if(!$this->_checkValidationForSSR())
			return $this->_OobjResponse->script("commonObj.closeGrmPopup(true);");
		
		//Selected SSR list for passenger
		$this->_ApassengerSSRList = json_decode($this->_IinputData['passengerSSRList'],true);
		//Available SSR list for the PNR
		$this->_AavailableSSR = json_decode($this->_IinputData['SSRList'],true);
		
		//Preparing the service form values
		$this->_AformValues['flightSegmentDetails'] = $this->_prepareServiceFormValues();
		$this->_AformValues['PNR'] = $this->_Spnr;
		
		//Set the via flight status and merged via flight details
		$this->_setViaFlightStatus($this->_AformValues['flightSegmentDetails']);
		
		/*foreach($this->_AformValues['flightSegmentDetails'] AS $_Ikey=> &$_Svalue)
		{
			$_Svalue['viaFlightDetails'] = $this->_Ocommon->_mergeViaFlights($_Svalue['viaFlightDetails']);
		}*/

		//Inserting the ssr details in DB
		$this->_addSSRToDB();
		
		if($this->_IinputData['ssrType'] == 'INSTANT')
		{
			if ($this->_SapiCall == "Y") 
				return $this->_addInstantSSRToDB();
			$this->_addInstantSSRToDB();
		}
		else
		{
			if ($this->_SapiCall == "Y") 
				return $this->_callUpdateSsrService();
			$this->_callUpdateSsrService();
		}
	}
	
	/*
	 * Inserting the ssr information with 'New' status in DB before calling the service
	 */
	function _addSSRToDB()
	{
		global $CFG;
		
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$this->_OssrMaster->_Oconnection = $this->_Oconnection;
		$this->_OssrMaster->_IrequestMasterId = $this->_IrequestMasterId;
		$this->_OssrMaster->_Spnr = $this->_Spnr;
		$this->_OssrMaster->_IssrAmount = $this->_IinputData['ssrTotalAmount'];
		$this->_OssrMaster->_IupdatedBy = $_SESSION['groupRM']['groupUserId'];
		$this->_OssrMaster->_SssrUpdatedDate = $this->_Ocommon->_getUTCDateValue();
		$this->_OssrMaster->_SlastTransaction = 'N';
		$this->_OssrMaster->_Sstatus = 'NEW';
		$this->_OssrMaster->_insertSsrMaster();
		$this->_IssrMasterId = $this->_OssrMaster->_IssrMasterId;
		
		//Set the array for fetch ssr category id based on ssr category name
		$this->_AssrCategoryId = $this->_getSSRCategoryId();
		
		$this->_OssrDetails->_Oconnection = $this->_Oconnection;
		$this->_OssrPaxDetails->_Oconnection = $this->_Oconnection;
		
		//Looping all the passenger in the pnr for inserting the ssr details if any new ssr has been selected
		foreach($this->_ApassengerSSRList AS $paxIndex => $_ApassengerSSRArray)
		{
			foreach($this->_AavailableSSR AS $referenceKey => $_AssrArray){
				
				//Checking the segment is exist or not with prepared referencekey
				if(isset($_ApassengerSSRArray[$referenceKey]))
				{
					foreach($_ApassengerSSRArray[$referenceKey] AS $ssrCategory => $selectSSRArray) {
						
						//Insert the ssr details if new ssr selected for the passenger
						if(isset($selectSSRArray['newSSR']) && !empty($selectSSRArray['newSSR'])) {
							
							//Finding the ssrPaxId
							$this->_OssrPaxDetails->__construct();
							$this->_OssrPaxDetails->_IpnrBlockingId = $_AssrArray['flightDetails']['pnrBlockingId'];
							$this->_OssrPaxDetails->_IviaFlightId = $_AssrArray['flightDetails']['viaFlightId'];
							if(is_string($_ApassengerSSRArray['paxNum']) && $_ApassengerSSRArray['paxNum']!='')
								$this->_OssrPaxDetails->_IpaxReferenceId = $_ApassengerSSRArray['paxNum'];
							else
								$this->_OssrPaxDetails->_IpaxReferenceId = ($_ApassengerSSRArray['paxNum'])?floor($_ApassengerSSRArray['paxNum']):$paxIndex+1;
							$this->_OssrPaxDetails->_selectSsrPaxDetails();
							$_IssrPaxId = $this->_OssrPaxDetails->_IssrPaxId;
							//Inserting multiple selected ssr for the passenger
							foreach($selectSSRArray['newSSR'] AS $ssrCode => $ssrDetails) {
								if(!isset($this->_IinputData['ssrType']) || (isset($this->_IinputData['ssrType']) && $this->_IinputData['ssrType']!='INSTANT'))
								{
									$this->_OssrDetails->__construct();
									$this->_OssrDetails->_IssrMasterId = $this->_IssrMasterId;
									$this->_OssrDetails->_IssrPaxId = $_IssrPaxId;
									$this->_OssrDetails->_IssrCategoryId = $this->_AssrCategoryId[$ssrCategory];
									$this->_OssrDetails->_SssrCode = $ssrDetails['ssr_code'];
									$this->_OssrDetails->_IssrBaseFare = $ssrDetails['ssrBaseFare'];
									$this->_OssrDetails->_IssrTax = $ssrDetails['ssrTax'];
									$this->_OssrDetails->_IssrTotalFare = $ssrDetails['ssrAmount'];
									$this->_OssrDetails->_Sremarks = '';
									$this->_OssrDetails->_SssrStatus = 'NEW';
									$this->_OssrDetails->_insertSsrDetails();
								}
								else
								{
									if($ssrDetails['ssrId']=='')
									{
										$this->_OssrDetails->__construct();
										$this->_OssrDetails->_IssrMasterId = $this->_IssrMasterId;
										$this->_OssrDetails->_IssrPaxId = $_IssrPaxId;
										$this->_OssrDetails->_IssrCategoryId = $this->_AssrCategoryId[$ssrCategory];
										$this->_OssrDetails->_SssrCode = $ssrDetails['ssr_code'];
										$this->_OssrDetails->_IssrBaseFare = $ssrDetails['ssrBaseFare'];
										$this->_OssrDetails->_IssrTax = $ssrDetails['ssrTax'];
										$this->_OssrDetails->_IssrTotalFare = $ssrDetails['ssrAmount'];
										$this->_OssrDetails->_Sremarks = '';
										$this->_OssrDetails->_SssrStatus = 'NEW';
										$this->_OssrDetails->_insertSsrDetails();
									}
								}
								
							}
						}
					}
				}
			}
		}
	}
	
	/*
	 * Updating the SSR status in the ssr_details table once the ssr added to the passenger
	 * The status will get update as COMPLETED once ssr added to the passenger while checking
	 * If the ssr is not added, then status will update as ERROR
	 */
	function _updateSSRDetailsStatus()
	{
		global $CFG;
		
		if(empty($this->_Spnr) || strpos(trim($this->_Spnr),"GROUP") !== false)
			return true;
		$this->_OssrPaxDetails->_Oconnection = $this->_Oconnection;
		$this->_OssrDetails->_Oconnection = $this->_Oconnection;
		
		//Getting the SSR details present in the PNR for each passenger
		$this->_OairlineService->__construct();
		$this->_OairlineService->_Spnr = $this->_Spnr;
		$this->_OairlineService->_IrequestMasterId = $this->_IrequestMasterId;
		$_AssrDetailsForPNR = $this->_OairlineService->_getSSRDetailsForPNR();
		$_AreferenceKeyArray = array_keys($this->_AavailableSSR);
		$_IssrTotalAmount = 0;
		if($_AssrDetailsForPNR['responseCode']==0)
		{
			if(isset($_AssrDetailsForPNR['response']['paxSSR']) && !empty($_AssrDetailsForPNR['response']['paxSSR']))
			{
				$_ApassengerSSRDetails = $_AssrDetailsForPNR['response']['paxSSR'];
				foreach($this->_ApassengerSSRList AS $paxIndex => $passengerDetails) {
					//Available sector wise
					foreach($_AreferenceKeyArray AS $_SreferenceKey) {
						
						$_AcurrentFlightDetails = $this->_AavailableSSR[$_SreferenceKey]['flightDetails'];
						
						/* Manikumar S - 27-07-2018
						 * To set the reference key for via flight details
						 **/
						$_SviaBaggagereferenceKey = '';
						if(isset($this->_AviaFlightStatus[$_AcurrentFlightDetails['viaFlightId']]) && $this->_AviaFlightStatus[$_AcurrentFlightDetails['viaFlightId']]=="Y") {
							$_AtempMergedViaFlightDetails = $this->_AmergedViaFlightDetails[$_AcurrentFlightDetails['viaFlightId']];
							$_SviaBaggagereferenceKey = $this->_generateFlightReferenceKey($_AtempMergedViaFlightDetails);
						}
						$_AssrCodeForSector = array();
						$_IssrId = 0;
						$_AssrWeight = array();$_SssrWeight = '';

						//Finding the added ssr code for the passenger in flight wise
						foreach($_ApassengerSSRDetails[$paxIndex]['AncillaryServices'] AS $_IssrIndexForPax => $_AssrDetailsForPax) {
							
							$_SssrReferenceKey = str_replace(" ","",$_AssrDetailsForPax['FlightReference']);
							if($_SreferenceKey == $_SssrReferenceKey || ($_SviaBaggagereferenceKey == $_SssrReferenceKey && $_SviaBaggagereferenceKey!="") || strpos($_SssrReferenceKey, $_SreferenceKey) !== false) {

								if(isset($CFG["ssr"]["skipSSRPolicy"]) && ($CFG["ssr"]["skipSSRPolicy"]['status']=='Y' || $CFG["ssr"]["skipSSRPolicy"]['applyUniqueSsrCode']=='Y'))
											{
												$_AssrDetailsForPax['SSRCode'] = $_AssrDetailsForPax['SSRCode'].'_'.str_replace(" ","_",strtoupper($_AssrDetailsForPax['CommercialName']));
											}
								if($_AssrDetailsForPax['SSRCode']!='' && $_AssrDetailsForPax['GroupCode']!='SEAT')
									$_AssrCodeForSector['SSRCode'][] = $_AssrDetailsForPax['SSRCode'];
								$_AssrCodeForSector[$_AssrDetailsForPax['SSRCode']] = $_AssrDetailsForPax['id'];
								if(isset($_AssrDetailsForPax['pieceOrWeight']) && $_AssrDetailsForPax['pieceOrWeight']!='')
									$_AssrWeight[$_AssrDetailsForPax['SSRCode']] = $_AssrDetailsForPax['pieceOrWeight'];
							}
						}
						//Finding the ssr_pax_id which is mapped in the ssr_details
						$this->_OssrPaxDetails->__construct();
						$this->_OssrPaxDetails->_IpnrBlockingId = $_AcurrentFlightDetails['pnrBlockingId'];
						if(isset($passengerDetails['nameId']))
							$this->_OssrPaxDetails->_IpaxReferenceId = $passengerDetails['nameId'];
						else
						{
							if(is_string($passengerDetails['paxNum']))
								$this->_OssrPaxDetails->_IpaxReferenceId=$passengerDetails['paxNum'];
							else
							{
								$this->_OssrPaxDetails->_IpaxReferenceId = ($_ApassengerSSRArray['paxNum'])?floor($_ApassengerSSRArray['paxNum']):$paxIndex+1;
							}
						}
						$this->_OssrPaxDetails->_IviaFlightId = $_AcurrentFlightDetails['viaFlightId'];
						$this->_OssrPaxDetails->_IpassengerId = (isset($passengerDetails['passengerId']))? $passengerDetails['passengerId']:0;
						$this->_OssrPaxDetails->_selectSsrPaxDetails();
						$_IssrPaxId = $this->_OssrPaxDetails->_IssrPaxId;
						
						//Checking the selected SSR are added in PNR or not for the passsenger based on input array of _ApassengerSSRList
						$newSSRArrayForPaxInFlight = array_column($passengerDetails[$_SreferenceKey],'newSSR');
						foreach($newSSRArrayForPaxInFlight AS $ssrIndex => $newSSRArray) {
							
							foreach($newSSRArray as $newSSRCode => $newSSRDetails) {
								
								/*
								 * Selected SSR code found in the pnr for the passenger,then update the ssr_status as completed
								 * Also update the ssr amount in the ssr master based on the currently added ssr in the pnr
								 */
								if(in_array($newSSRCode,$_AssrCodeForSector['SSRCode'])) {
									$_SssrStatus = "COMPLETED";
									$_IssrId = $_AssrCodeForSector[$newSSRCode];
									if(isset($_AssrWeight[$newSSRCode]))
									{
										$_SssrWeight = $_AssrWeight[$newSSRCode];
									}
									if(!isset($this->_IinputData['instantPayment']) || (isset($this->_IinputData['instantPayment']) && $this->_IinputData['instantPayment']!='Y'))
										$_IssrTotalAmount += (is_array($newSSRDetails['ssrAmount']))?0:$newSSRDetails['ssrAmount'];
										else
										{
											if(!$newSSRDetails['ssrId'])
												$_IssrTotalAmount += (is_array($newSSRDetails['ssrAmount']))?0:$newSSRDetails['ssrAmount'];
										}

								}
								//If not found in the pnr for the passenger,then update the status as error
								else {
									$_SssrStatus = "ERROR";
								}
								//Updating the ssr status for the passenger in flight wise
								$this->_OssrDetails->__construct();
								$this->_OssrDetails->_IssrMasterId = $this->_IssrMasterId;
								$this->_OssrDetails->_IssrPaxId = $_IssrPaxId;
								$this->_OssrDetails->_SssrCode = $newSSRCode;
								$this->_OssrDetails->_SssrStatus = $_SssrStatus;
								$this->_OssrDetails->_updateSsrDetails();
								
								$this->_OssrDetails->__construct();
								$this->_OssrDetails->_IssrMasterId = $this->_IssrMasterId;
								$this->_OssrDetails->_IssrPaxId = $_IssrPaxId;
								$this->_OssrDetails->_SssrCode = $newSSRCode;
								$this->_OssrDetails->_selectSsrDetails();
								//Update insert ssr pax group
								$this->_OssrPaxGroup->__construct();
								$this->_OssrPaxGroup->_Oconnection = $this->_Oconnection;
								$this->_OssrPaxGroup->_IssrDetailsId = $this->_OssrDetails->_IssrDetailsId;
								$this->_OssrPaxGroup->_IssrId = $_IssrId;
								$this->_OssrPaxGroup->_SssrWeight = $_SssrWeight;
								$this->_OssrPaxGroup->_insertSsrPaxGrouping();
							}
						}
					}
				}
			}
			//Update the old ssr master rows make it as inactive transaction
			
			$this->_OssrMaster->__construct();
			$this->_OssrMaster->_Oconnection = $this->_Oconnection;
			if(!isset($this->_IinputData['instantPayment']) || (isset($this->_IinputData['instantPayment']) && $this->_IinputData['instantPayment']!='Y'))
				$this->_OssrMaster->_IssrMasterIdNotEqual = $this->_IssrMasterId;
			else
				$this->_OssrMaster->_IssrMasterId = $this->_IssrMasterId;
			$this->_OssrMaster->_IrequestMasterId = $this->_IrequestMasterId;
			// To update the ssr other than the seat
			$this->_OssrMaster->_SemptyValue = 'Y';
			$this->_OssrMaster->_Spnr = $this->_Spnr;
			$this->_OssrMaster->_SlastTransaction = 'N';
			$this->_OssrMaster->_updateSsrMaster();
			
			//Update the status and make it as active transaction
			$this->_OssrMaster->__construct();
			$this->_OssrMaster->_Oconnection = $this->_Oconnection;
			$this->_OssrMaster->_IssrMasterId = $this->_IssrMasterId;
			$this->_OssrMaster->_IrequestMasterId = $this->_IrequestMasterId;
			// To update the ssr other than the seat
			$this->_OssrMaster->_SemptyValue = 'Y';
			$this->_OssrMaster->_IssrAmount = $_IssrTotalAmount;
			$this->_OssrMaster->_Sstatus = 'COMPLETED';
			$this->_OssrMaster->_SlastTransaction = 'Y';
			$this->_OssrMaster->_updateSsrMaster();
			//Updating the payment details
			if($this->_IinputData['instantPayment'] != 'Y')
			{
				if(isset($CFG['ssr']['SSRPayment']) && $CFG['ssr']['SSRPayment']['status']=='Y')
				{
					fileRequire("classesTpl/class.tpl.submitPenaltyPaymentRequestTpl.php");
					$this->_OsubmitPenaltyPayment=new submitPenaltyPaymentRequestTpl();
					$this->_OsubmitPenaltyPayment->_Oconnection=$this->_Oconnection;
					$this->_OsubmitPenaltyPayment->_OobjResponse=$this->_OobjResponse;
					$_AinputData=array();
					$_AinputData['ssrPayment']='Y';
					$_AinputData['requestMasterId']=$this->_IrequestMasterId;
					$_AinputData['ssrMasterId']=$this->_IssrMasterId;
					$_AinputData['pnr']=$this->_Spnr;
					$_AinputData['ssrAmount']=$_IssrTotalAmount;
					$this->_OsubmitPenaltyPayment->_IinputData=$_AinputData;
					$this->_OsubmitPenaltyPayment->_insertPaymentTablesForPenalty();
				}
				else
					$this->_updatePaymentDetails();
			}
			if($this->_StypeOfSsr == 'SEAT')
				$this->_OobjResponse->script("commonObj.closeGrmPopup(true);commonObj.showSuccessMessage(globalLanguageVar['VALIDATION_POPUPSSRDETAILS_ADD_SEAT_SUCCESS_MSG']);");
			else if(!isset($this->_IinputData['instantPayment']))
			{
				if(in_array($_SESSION['groupRM']['groupId'],$CFG['default']['airlinesGroupId']))
				$this->_OobjResponse->script("
									var displayField = {'getBooking': 'N','paxTimeLine': 'N','passengerUpload': 'N','paidPercentage': 'N'};
									commonObj.closeGrmPopup(true);
									commonObj.showSuccessMessage(globalLanguageVar['VALIDATION_POPUPSSRDETAILS_ADD_ANCILLARIES_SUCCESS_MSG']);
									wrapperScript('paymentRequest', '');wrapperScript('viewPaymentRequest', displayField);");
				else
					$this->_OobjResponse->script("commonObj.closeGrmPopup(true);commonObj.showSuccessMessage(globalLanguageVar['VALIDATION_POPUPSSRDETAILS_ADD_ANCILLARIES_SUCCESS_MSG']);wrapperScript('viewRequestSSR','');");
			}
			#view history Ancillary added data send to noSql.
			if($CFG['ssr']['instantPayment']['status'] != 'Y' && isset($this->_IinputData['pnr']) && !empty($this->_IinputData['pnr']))
			{
				fileRequire("dataModels/class.ssrMaster.php");
				$_OssrMaster = new ssrMaster();
				$_OssrMaster->__construct();
				$_OssrMaster->_Oconnection = $this->_Oconnection;
				$_OssrMaster->_IrequestMasterId = $this->_IinputData['requestMasterId'];
				$_OssrMaster->_Spnr = $this->_IinputData['pnr'];
				$_OssrMaster->_SlastTransaction = 'Y';//Y-succesful transaction ,N - Error
				$_AgetSsrMaster = $_OssrMaster->_selectSsrMaster();
			
				$this->_OpnrBlockingDetails->_Oconnection = $this->_Oconnection;
				$this->_OpnrBlockingDetails->__construct();
				$this->_OpnrBlockingDetails->_Spnr=$this->_IinputData['pnr'];
				$_ApnrBlockingDetails=$this->_OpnrBlockingDetails->_selectPnrBlockingDetails();
				$_ApnrBlockingIds = implode(',', array_column($_ApnrBlockingDetails, 'pnr_blocking_id'));

				fileRequire("classes/class.viewHistoryProcess.php");
				$_AviewHistoryProcess=new viewHistoryProcess();
				$_AviewHistoryProcess->__construct();
				$_AviewHistoryProcess->_Oconnection=$this->_Oconnection;
				$_AviewHistoryProcess->_SssrMasterId=$_AgetSsrMaster[count($_AgetSsrMaster)-1]['ssr_master_id'];
				$_AviewHistoryProcess->_Spnr=$this->_IinputData['pnr'];
				$_AviewHistoryProcess->_SpnrBlockingId=$_ApnrBlockingIds;
				$_AviewHistoryProcess->_fetchHistoryData('AA',$this->_IinputData['requestMasterId']);
			}					
		}
	}
	
	/*
	 * Updating the payment information along with SSR amount
	 */
	function _updatePaymentDetails()
	{		
		global $CFG;
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$_IpnrTotalAmount = $this->_Ocommon->_getPnrAmountValue($this->_Spnr,$this->_IrequestMasterId,'N');
		
		//Updating the new pnr amount
		$this->_OpnrBlockingDetails->__construct();
		$this->_OpnrBlockingDetails->_Oconnection = $this->_Oconnection;
		$this->_OpnrBlockingDetails->_IrequestMasterId = $this->_IrequestMasterId;
		$this->_OpnrBlockingDetails->_Spnr = $this->_Spnr;
		$this->_OpnrBlockingDetails->_IpnrAmount = $_IpnrTotalAmount;
		$this->_OpnrBlockingDetails->_updatePnrBlockingDetails();
		
		//Updating the payment details for the request
		fileRequire("classes/class.updatePaymentDetails.php");
		$this->_OupdatePaymentDetails = new updatePaymentDetails();	
		$this->_OupdatePaymentDetails->__construct();
		$this->_OupdatePaymentDetails->_Oconnection=$this->_Oconnection;
		$this->_OupdatePaymentDetails->_Osmarty=$this->_Osmarty;
		$this->_OupdatePaymentDetails->_IrequestMasterId= $this->_IrequestMasterId;
		$this->_OupdatePaymentDetails->_SPNR = $this->_Spnr;
		$this->_OupdatePaymentDetails->_updateGenericPaymentDetails();
		
		#Auto requesting the remaining amount for all PNR's
		$this->_OupdatePaymentDetails->__construct();
		$this->_OupdatePaymentDetails->_Oconnection=$this->_Oconnection;
		$this->_OupdatePaymentDetails->_Osmarty=$this->_Osmarty;
		$this->_OupdatePaymentDetails->_BsetSSRValidity=true;
		$this->_OupdatePaymentDetails->_IrequestMasterId = $this->_IrequestMasterId;
		$this->_OupdatePaymentDetails->_autoRequestingPaymentDetails($this->_Spnr);
		
		$this->_OupdatePaymentDetails->__construct();
		$this->_OupdatePaymentDetails->_Oconnection=$this->_Oconnection;
		$this->_OupdatePaymentDetails->_Osmarty=$this->_Osmarty;
		$this->_OupdatePaymentDetails->_IrequestMasterId = $this->_IrequestMasterId;
		$status=$this->_OupdatePaymentDetails->_getPNRPendingStatus();

		// Check ssr infant count with infant submitted count only if infant pax not available in regular passenger count
		$_BisNotSubmittedInfantAvailable = false;
		if($CFG['ssr']['infantCount']['ssrInfant'] == 'Y'){
			$_ArequestApporvedFlightId = $this->_Ocommon->_getRequestApprovedFlightDetails($this->_IrequestMasterId);
			$_SrequestApporvedFlightId = implode(',',array_column($_ArequestApporvedFlightId, 'request_approved_flight_id'));
			$_IssrInfantCount=$this->_Ocommon->_getSSRInfantCount($_SrequestApporvedFlightId,$this->_Spnr);
			if(in_array("INFT",$CFG['settings']['hidePaxDetails'])){
				$_AsubmittedPassengerCount = $this->_Ocommon->_getSubmittedCountByPaxType($this->_Spnr);
				if( $_IssrInfantCount > 0 && $_AsubmittedPassengerCount['submittedInfant'] != $_IssrInfantCount)
					$_BisNotSubmittedInfantAvailable = true;
			}
		}
		
		if($status==1 || $_BisNotSubmittedInfantAvailable)
		{
			$this->_IairlinesRequestId=$this->_Ocommon->_getAirlineRequestId($this->_IrequestMasterId);
			$this->_OairlinesRequestMapping->__construct();
			$this->_OairlinesRequestMapping->_Oconnection = $this->_Oconnection;

			// Get and set status based on SSR with fare or without fare
			if($status)
				$_SstatusDetail = 'AR';
			else if($_BisNotSubmittedInfantAvailable)
				$_SstatusDetail = 'PC';

			$_IpnrSubmitted = $this->_Ocommon->_getStatusDetails($_SstatusDetail);
			$this->_OairlinesRequestMapping->_IcurrentStatus = $_IpnrSubmitted['status_id'];
			$this->_OairlinesRequestMapping->_SlastUpdated = $this->_Ocommon->_getUTCDateValue();
			$this->_OairlinesRequestMapping->_IairlinesRequestId = $this->_IairlinesRequestId;
			$this->_OairlinesRequestMapping->_updateAirlinesRequestMapping();

			/*Get SSR infant count for add with requested infant count and 
			  pass this count with the CSV functions to combine child values 
			  with adult array*/
			if($CFG['ssr']['infantCount']['ssrInfant']=='Y')
			{
				// Get submitted passenger details
				$this->_OpassengerDetails->_Oconnection = $this->_Oconnection;
				$this->_OpassengerDetails->_Spnr = $this->_Spnr;
				$this->_ApassengerDetails = $this->_OpassengerDetails->_selectPassengerDetails();
				// Get passenger type values only from submitted passengers
				$_ApassengerType = array_column($this->_ApassengerDetails, 'passenger_type');
				// Get Infant value key
				$_AvalueInfant = array_keys($_ApassengerType,'Infant');
				// Count the infant value key for check with submitted infants to take decision if allow to name update ot not
				$_IcountOfKeyInfant = count((array)$_AvalueInfant);
				
				if($_IssrInfantCount > 0  && $_IcountOfKeyInfant!=$_IssrInfantCount)
				{
					$sqlUpdatePnrDetails = "UPDATE ".$CFG['db']['tbl']['pnr_details']."
										SET pnr_status=".$_IpnrSubmitted['status_id']." 
										WHERE pnr_number='".$this->_Spnr."'";
				
					if (DB::isError($result= $this->_Oconnection->query($sqlUpdatePnrDetails)))
					{
						fileWrite($sqlUpdatePnrDetails,"SqlError","a+");
						return false;
					}
				}
			}
		}
	}
	
	/*
	 * Get the ssr category name by its id and vice versa
	 * $categoryId - 'Y' Get the category id with category name as index
	 * $categoryId - 'N' Get the category name with category id as index
	 */
	
	function _getSSRCategoryId($categoryId="N")
	{
		global $CFG;
		
		$_AssrCategoryArray = array();
		
		$selectSSRCategory = "SELECT
								ssr_category_id,
								ssr_category_name
							FROM
								".$CFG['db']['tbl']['ssr_category_details']."
							ORDER BY ssr_category_id";
		
		if(DB::isError($resultSSRCategory=$this->_Oconnection->query($selectSSRCategory)))
		{
			fileWrite($selectSSRCategory,"SqlError","a+");
			return false;
		}
		
		if($resultSSRCategory->numRows() > 0)
		{
			while($rowSSRCategory=$resultSSRCategory->fetchRow(DB_FETCHMODE_ASSOC))
			{
				if($categoryId=="Y")
					$_AssrCategoryArray[$rowSSRCategory['ssr_category_id']] = strtolower($rowSSRCategory['ssr_category_name']);
				else
					$_AssrCategoryArray[strtolower($rowSSRCategory['ssr_category_name'])] = $rowSSRCategory['ssr_category_id'];
			}
		}
		return $_AssrCategoryArray;
	}
	
	/*
	 * Prepare the serive form values based on the flight details for the pnr
	 */
	function _prepareServiceFormValues()
	{
		global $CFG;
		
		$this->_AflightDetails = $this->_Ocommon->_getFlightDetailsForPNR($this->_Spnr,$this->_IrequestMasterId);
		
		//$viaFlightDetails=array();
		$_AsegmentDetails=array();
		
		$k=0;
		/*
		fileRequire("dataModels/class.cabinDetails.php");
		$_OcabinDetails = new cabinDetails;
		$_OcabinDetails->_Oconnection=$this->_Oconnection;
		$_OcabinDetails->_ScabinValue=strtoupper(trim($this->_Ocommon->_getRequestedCabin($this->_IrequestMasterId)));
		$_OcabinDetails->_selectCabinDetails();
		if($_OcabinDetails->_IcountLoop > 0)
			$classOfService = $_OcabinDetails->_AcabinDetails[0]['pnr_blocking_class'];
		*/
		foreach($this->_AflightDetails AS $flightKey => $flightValue)
		{
			$i=0;
			$viaFlightDetails=array();
			if(is_array($flightValue['viaFlight']) && !empty($flightValue['viaFlight']) && $flightValue['stops']>0)
			{
				foreach($flightValue['viaFlight'] AS $viaFlightKey => $viaFlightValue)
				{
					$viaFlightDetails[$i]['requestApprovedFlightId'] = $viaFlightValue['request_approved_flight_id'];
					$viaFlightDetails[$i]['viaFlightId'] = $viaFlightValue['via_flight_id'];
					$viaFlightDetails[$i]['pnrBlockingId'] = $flightValue['pnrBlockingId'];
					$viaFlightDetails[$i]['origin'] = $viaFlightValue['origin'];
					$viaFlightDetails[$i]['destination'] = $viaFlightValue['destination'];
					$viaFlightDetails[$i]['departureDateAndTime'] = $viaFlightValue['departure_date']."T".$viaFlightValue['departure_time'];
					$viaFlightDetails[$i]['departureDate'] = $viaFlightValue['departure_date'];
					$viaFlightDetails[$i]['departureTime'] = $viaFlightValue['departure_time'];
					$viaFlightDetails[$i]['arrivalDateAndTime'] = $viaFlightValue['arrival_date']."T".$viaFlightValue['arrival_time'];
					$viaFlightDetails[$i]['arrivalDate'] = $viaFlightValue['arrival_date'];
					$viaFlightDetails[$i]['arrivalTime'] = $viaFlightValue['arrival_time'];
					$viaFlightDetails[$i]['flightNumber'] = $viaFlightValue['flight_number'];
					#getting farebasis code and fare class from flight cabin detail based on dynamic fare class
					$_AflightCabinMappingDetailsData=$this->_getFareClass($viaFlightValue['request_approved_flight_id'],$viaFlightValue['via_flight_id']);
					$viaFlightDetails[$i]['fareBasisCode'] = $_AflightCabinMappingDetailsData[0]['fare_basis_code'];
					if(isset($CFG['ssr']['ssrDynamicClass']) && $CFG['ssr']['ssrDynamicClass']!='')
						$viaFlightDetails[$i]['class'] = $CFG['ssr']['ssrDynamicClass'];
					else
						$viaFlightDetails[$i]['class'] = $_AflightCabinMappingDetailsData[0]['class_of_service'];
					
					$viaFlightDetails[$i]['carrierCode'] = $viaFlightValue['airline_code'];
					$viaFlightDetails[$i]['marketingCarrierCode'] = $viaFlightValue['airline_code'];
					$viaFlightDetails[$i]['operatingCarrierCode'] = $viaFlightValue['airline_code'];
					$i++;
				}
			}
			else
			{
				$viaFlightDetails[$i]['requestApprovedFlightId'] = $flightValue['request_approved_flight_id'];
				$viaFlightDetails[$i]['viaFlightId'] = 0;
				$viaFlightDetails[$i]['pnrBlockingId'] = $flightValue['pnrBlockingId'];
				$viaFlightDetails[$i]['origin'] = $flightValue['source'];
				$viaFlightDetails[$i]['destination'] = $flightValue['destination'];
				$viaFlightDetails[$i]['departureDateAndTime'] = $flightValue['departure_date']."T".$flightValue['dep_time'].":00";
				$viaFlightDetails[$i]['departureDate'] = $flightValue['departure_date'];
				$viaFlightDetails[$i]['departureTime'] = $flightValue['dep_time'].":00";
				$viaFlightDetails[$i]['arrivalDateAndTime'] = $flightValue['arrival_date']."T".$flightValue['arr_time'].":00";
				$viaFlightDetails[$i]['arrivalDate'] = $flightValue['arrival_date'];
				$viaFlightDetails[$i]['arrivalTime'] = $flightValue['arr_time'].":00";
				$viaFlightDetails[$i]['flightNumber'] = $flightValue['flight_code'];
				#getting farebasis code and fare class from flight cabin detail based on dynamic fare class
				$_AflightCabinMappingDetailsData=$this->_getFareClass($flightValue['request_approved_flight_id']);
				$viaFlightDetails[$i]['fareBasisCode'] = $_AflightCabinMappingDetailsData[0]['fare_basis_code'];
				if(isset($CFG['ssr']['ssrDynamicClass']) && $CFG['ssr']['ssrDynamicClass']!='')
						$viaFlightDetails[$i]['class'] = $CFG['ssr']['ssrDynamicClass'];
				else
					$viaFlightDetails[$i]['class'] = $_AflightCabinMappingDetailsData[0]['class_of_service'];			
				$viaFlightDetails[$i]['carrierCode'] = $flightValue['airline_code'];
				$viaFlightDetails[$i]['marketingCarrierCode'] = $flightValue['airline_code'];
				$viaFlightDetails[$i]['operatingCarrierCode'] = $flightValue['airline_code'];
			}
			//To merge via flight to select seat for single flight
			if($this->_StypeOfSsr == 'SEAT')
				$_AsegmentDetails[$k]['viaFlightDetails'] = $this->_Ocommon->_mergeViaFlights($viaFlightDetails);
			else
				$_AsegmentDetails[$k]['viaFlightDetails']=$viaFlightDetails;
			$k++;
		}
		return $_AsegmentDetails;
	}
	
	/*
	 * Manikumar S - 17-07-2018
	 * Flagged as Y for via flights
	 **/
	function _setViaFlightStatus($_AflightDetails)
	{
		global $CFG;
		
		if(empty($_AflightDetails))
			return false;
		$this->_AviaFlightStatus = array();
		$_AtempFlightDetails = array();
		$_IcheckFlightId = '';
		$_AapprovedFlightId = '';
		foreach($_AflightDetails AS $_IflightKey => $_AviaFlightDetails) {
			
			foreach($_AviaFlightDetails['viaFlightDetails'] AS $_IviaFlightKey => $_AflightVal) {
				if($_AflightVal['viaFlightId']>0) {
					$flightNumber = isset($_AflightVal['flight_number'])?$_AflightVal['flight_number']:$_AflightVal['flightNumber'];
					
					if(isset($_AflightVal['requestApprovedFlightId']) && $_AflightVal['requestApprovedFlightId']!='')
						$_IcheckFlightId='_'.$_AflightVal['requestApprovedFlightId'];
					if(!isset($_AtempFlightDetails[$flightNumber.$_IcheckFlightId])) {
						$_AtempFlightDetails[$flightNumber.$_IcheckFlightId] = array();
						$this->_AviaFlightStatus[$_AflightVal['viaFlightId']] = 'Y';
						if(isset($CFG["ssr"]["skipSSRPolicy"]) && is_array($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['mergingFlights']=='Y')
						{
							if($_AapprovedFlightId==$_AflightVal['requestApprovedFlightId'])
								$this->_AviaFlightStatus[$_AflightVal['viaFlightId']] = 'N';
						}
					}else
					{
						$this->_AviaFlightStatus[$_AflightVal['viaFlightId']] = 'N';
					}
					$_AtempFlightDetails[$flightNumber.$_IcheckFlightId][] = $_AflightVal;
					$_AapprovedFlightId=$_AflightVal['requestApprovedFlightId'];
				}
			}
		}
		$this->_AmergedViaFlightDetails = array();
		foreach($_AtempFlightDetails as $viaFltNo=>$viaFltDetails)
		{
			$viaFlightCount = count((array)$viaFltDetails);
			if($viaFlightCount > 1)
			{
				$viaFlightCount = $viaFlightCount-1;
				$viaFltDetails[0]['destination'] = $viaFltDetails[$viaFlightCount]['destination'];
				$viaFltDetails[0]['arrivalDate'] = $viaFltDetails[$viaFlightCount]['arrivalDate'];
				
			}
			if($this->_AviaFlightStatus[$viaFltDetails[0]['viaFlightId']]=="Y")
				$this->_AmergedViaFlightDetails[$viaFltDetails[0]['viaFlightId']] = $viaFltDetails[0];
		}
	}
	function _getSeriesSSRResponse()
	{
		global $CFG;
		fileRequire("classesTpl/class.tpl.displaySectorDetailsTpl.php");  
		$this->_OdisplaySectorDetailsTpl = new displaySectorDetailsTpl();
		$this->_OdisplaySectorDetailsTpl->_Oconnection = $this->_Oconnection;
		$this->_OdisplaySectorDetailsTpl->_Osmarty = $this->_Osmarty;
		$this->_OdisplaySectorDetailsTpl->_OobjResponse = $this->_OobjResponse;
		$this->_OdisplaySectorDetailsTpl->_IrequestMasterId = $this->_IinputData['requestMasterId'];
		$this->_OdisplaySectorDetailsTpl->_SpnrView = 'N';
		$this->_OdisplaySectorDetailsTpl->_SssrTag = 'Y';
		if(!empty($this->_Osmarty->getConfigVars('ADD_ANCILLARY_DISCLIMER')))
			$this->_OdisplaySectorDetailsTpl->_SshowDisclaimerContent = 'Y';
		$this->_OdisplaySectorDetailsTpl->_getDisplaySectorDetails();
	}

	function _preSelectedSSRCount($requestMasterId, $selectedSSRlist)
	{
		global $CFG;
		$count = 0;
		if (empty($selectedSSRlist)) return $count;

		foreach ($selectedSSRlist as $nest => $ssrCodes) {
			foreach ($ssrCodes as $ssrCode) {
				$sql = "SELECT COUNT(*) as cnt FROM ".$CFG['db']['tbl']['ssr_details']." sd
						INNER JOIN ".$CFG['db']['tbl']['ssr_master']." sm ON sd.ssr_master_id = sm.ssr_master_id
						WHERE sm.request_master_id = ".intval($requestMasterId)."
						AND sd.ssr_code = '".$this->_Oconnection->escapeSimple($ssrCode)."'
						AND sd.ssr_status = 'COMPLETED'";
				$result = $this->_Oconnection->query($sql);
				if (!DB::isError($result) && $result->numRows() > 0) {
					$row = $result->fetchRow(DB_FETCHMODE_ASSOC);
					$count += intval($row['cnt']);
				}
			}
		}
		return $count;
	}
}
?>
