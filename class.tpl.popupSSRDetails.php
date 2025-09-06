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
	var $_AnestServiceSSRValue ;
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
		$this->_AnestServiceSSRValue= array();
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
		$_ArequestGroupDetails=$this->_Ocommon->_getRequestGroupDetails($_AtransactionDetails['airlinesRequestId'],$_IgroupId['seriesGroupId']);

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
        if(!empty($this->_AnestServiceSSRValue))
		$selectedSSRCount  = $this->_preSelectedSSRCount($this->_IrequestMasterId,$this->_AnestServiceSSRValue);
		$this->_Osmarty->assign("selectedSSRCount",$selectedSSRCount);
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
		$_ArequestGroupDetails=$this->_Ocommon->_getRequestGroupDetails($_AtransactionDetails['airlinesRequestId'],$_IgroupId['seriesGroupId']);
		$this->_IgroupStatus = end($_ArequestGroupDetails)['group_status'];
		if(in_array($this->_IgroupStatus,explode(",",$CFG['site']['baggageDisplayStatus']['status'])) && !$_BdisplayAncillary)
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
		if($this->_IcurrentStatus==11 || (count($_ApnrPaymentDetails)>0 && $_ApnrPaymentDetails[0]['paymentExpiryStatus']=="Y"  && !in_array($this->_IcurrentStatus,array('9','12','13','14'))))
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
		$this->_IremaingAmount = $this->_Ocommon->_getRoundOffFare($this->_IremaingAmount,'','displayFare');
		$this->_ItotalPnrAmount = $this->_Ocommon->_getRoundOffFare($this->_ItotalPnrAmount,"","displayFare");
		$this->_IpnrPaidAmount = $this->_Ocommon->_getRoundOffFare($this->_IpnrPaidAmount,"","displayFare");
		
		$this->_ApnrFareDetails = $this->_Ocommon->_getPnrFareDetails($this->_IrequestMasterId,$this->_Spnr);
		
		$this->_AtotalPaxCount = $this->_Ocommon->_getPnrPaxDetails($this->_IrequestMasterId,$this->_Spnr);
		$this->_SdisplayPaxCount = $this->_Ocommon->_getPaxDetails($this->_AtotalPaxCount['numberOfAdult'],$this->_AtotalPaxCount['numberOfChild'],$this->_AtotalPaxCount['numberOfInfant'],$this->_AtotalPaxCount['numberOfFoc']);
		
		$_IancillariesAmount = $this->_Ocommon->_getSSRTotalAmount($this->_IrequestMasterId,$this->_Spnr,'','Y');
		
		$_IancillariesAmount = ($_IancillariesAmount!="N" ? $_IancillariesAmount : "0");
		$this->_IancillariesAmount = $this->_Ocommon->_getRoundOffFare($_IancillariesAmount,'','displayFare');
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
					$errorSSR = ((count($getSsrStatus) != $countSSRArray['ERROR'])?'N':'Y');
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
							$tempSSRFareArray[$ssrMasterArray['ssr_master_id']][$_SssrCategoryName] = $this->_SuserCurrency." ".$this->_Ocommon->_getRoundOffFare($tempSSRFareArray[$ssrMasterArray['ssr_master_id']][$_SssrCategoryName],'','displayFare');
						else
							$tempSSRFareArray[$ssrMasterArray['ssr_master_id']][$_SssrCategoryName] = "-";
					}
					$ssrMasterArray['ssr_amount'] = $this->_Ocommon->_getRoundOffFare($ssrMasterArray['ssr_amount'],'','displayFare');
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
				if(isset($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]["status"] == 'Y')
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

						
						if(count($_AfinalStringExecute) > 0)
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
		if(count($policyMasterValueArray) > 0)
		{
			$finalInput=array("inputArray"=>$policyMasterValueArray,"firstFieldName"=>"priority","firstFieldOrder"=>"ASC","secondFieldName"=>"create_date","secondFieldOrder"=>"ASC");
			$finalArray=$this->_Ocommon->_multipleSortFunction($finalInput);
			$fetchRequestFieldPolicyArray[]=$finalArray[0];
			if(count($fetchRequestFieldPolicyArray) > 0)
			{
				$returnFieldArray=$this->_getSSRMatrix($fetchRequestFieldPolicyArray);
				if(count($returnFieldArray) > 0)
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
		if(count($givenRequestPolicyArray)>0 && !empty($givenRequestPolicyArray))
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
								if($CFG["ssr"]["skipSSRPolicy"]['applyUniqueSsrCode']=='Y')
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
		if(isset($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['ONDLevelSSR']=='Y')
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
						if(isset($CFG["ssr"]["skipSSRPolicy"]) && ($CFG["ssr"]["skipSSRPolicy"]['status']=='Y' || $CFG["ssr"]["skipSSRPolicy"]['applyUniqueSsrCode']=='Y'))
							$_SssrCode = $_IserviceSSRValue['SSRCode'].'_'.str_replace(' ','_', strtoupper($_IserviceSSRValue['SSRName']));
						if(in_array($_SssrCode,$_AavailableSSR))
						{
							foreach($this->_AssrListPolicyValues[$_IreferenceKey] AS $_IssrListPolicyKey => &$_IssrListPolicyValue)
							{
								if($_IssrListPolicyKey==$_SssrCode)
								{
									$_IssrListPolicyValue['ssrTax'] = $this->_Ocommon->_getRoundOffFare(($_IserviceSSRValue['totalPrice']-$_IserviceSSRValue['basePrice']));
									$_IssrListPolicyValue['ssrAmount'] = $this->_Ocommon->_getRoundOffFare($_IserviceSSRValue['totalPrice']);
									$_IssrListPolicyValue['ssrBaseFare'] = $this->_Ocommon->_getRoundOffFare($_IserviceSSRValue['basePrice']);

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
									#Get SSR availbity Count 
									if(isset($_IserviceSSRValue['Nest']) && !empty($_IserviceSSRValue['Nest']) )
									{
										$_IssrListPolicyValue['Available']=$_IserviceSSRValue['Available'];
										$_IssrListPolicyValue['Nest'][$_IserviceSSRValue['Nest']]=$_IserviceSSRValue['Available'];
										$this->_AnestServiceSSRValue['Nest'][$_IserviceSSRValue['Nest']]['ssrCode'][]=$_IserviceSSRValue['SSRCode'];

									}
									
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
		fileWrite(print_r($this->_AfinalSSRList,1),'SSR___final','w+');
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
		//Pax count in the PNR
		$_IpnrPaxCount = count($this->_AfinalPassengerSSRList);
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
		if(isset($_pnrBlockingInsertedZero) && $_pnrBlockingInsertedZero =='Y')
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
		if(isset($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['ONDLevelSSR']=='Y')
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
				$_IpaxSSRCount = count($_ApassengerSSR);
				
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
													$_AselectedSSRDetails['ssrAmount'] =  $this->_Ocommon->_getRoundOffFare($availableSSRValue['totalPrice'],'','displayFare');
													$_AselectedSSRDetails['ssrBaseFare'] = $availableSSRValue['basePrice'];
													$_AselectedSSRDetails['ssrTax'] = $this->_Ocommon->_getRoundOffFare(($availableSSRValue['totalPrice']-$availableSSRValue['basePrice']),'','displayFare');
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
													$_AselectedSSRDetails['ssrTax'] = $this->_Ocommon->_getRoundOffFare(($availableSSRValue['totalPrice']-$availableSSRValue['basePrice']),'','displayFare');
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
												if(!in_array(strtolower($_AselectedSSRDetails['ssr_category_name']),$this->_AfinalPassengerSSRList[$paxSSRIndex]['ssrIcons']))
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
		$this->_IcurrentStatus = $this->_Ocommon->_getStatusFromRequestId($this->_IinputData['requestMasterId']);
		
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
					$this->_OobjResponse->script("commonObj.closeGrmPopup(true);commonObj.showSuccessMessage(globalLanguageVar['VALIDATION_POPUPSSRDETAILS_ADD_ANCILLARIES_SUCCESS_MSG']);wrapperScript('viewPaymentRequest','');");  
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
				$_IcountOfKeyInfant = count($_AvalueInfant);
				
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
						if(isset($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['mergingFlights']=='Y')
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
			$viaFlightCount = count($viaFltDetails);
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
		$this->_OdisplaySectorDetailsTpl->_getDisplaySectorDetails();
	}
	
	/*
	 * Function Name 	: _addSeatNoToPnr
	 * Description		: To add the seat details with the pnr
	 * Author			: Subalakshmi S
	 * Created Date		: 08-06-2018
	 */
	 
	function _addSeatNoToPnr()
	{
		global $CFG;
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$_AformValues = array();
		//Preparing the service form values
		$_AflightSegment = $this->_prepareServiceFormValues();
		
		//Checking last ssr master with status new starts
		$_IssrMasterId = 0;
		$_SssrMaster = "SELECT 
							ssr_master_id,
							pnr
						FROM 
							".$CFG['db']['tbl']['ssr_master']." 
						WHERE 
							status = 'NEW' AND 
							request_master_id = ".$this->_IrequestMasterId." 
						ORDER BY 
							ssr_master_id desc LIMIT 1";
		if(DB::isError($result = $this->_Oconnection->query($_SssrMaster)))
		{
			fileWrite($_SssrMaster,"SqlError","a+");
			return false;
		}
		if ($result->numRows() > 0)
		{
			$row=$result->fetchRow(DB_FETCHMODE_ASSOC);
			$_IssrMasterId = $row['ssr_master_id'];
			$this->_IssrMasterId = $row['ssr_master_id'];
			$this->_Spnr = $row['pnr'];
		}
		$_AuserCurrency = $this->_Ocommon->_getUserCurrency($this->_IrequestMasterId);
		$_ScurrencyCode = $_AuserCurrency['user_currency'];
		
		// Set the payment details only for the paid seat not for the free seat
		if($this->_IinputData['ssrTotalAmount'] != 0 || !empty($this->_IinputData['CCPayableAmount']))
		{
			$_ApnrDetails = array();
			$_ApnrDetails = json_decode($this->_IinputData['selectedPNRDetails'],1);				
			$_ApaymentDetails['pnrPaymentId'] = $_ApnrDetails[0]['pnrPaymentId'];			
			$_ApaymentDetails['paymentMasterId'] = $_ApnrDetails[0]['paymentMasterId'];			
			// Getting the user details and currency code 
			$_IuserId = $this->_Ocommon->_getRequestedUserId($this->_IrequestMasterId);		
			$_AuserDetails = $this->_Ocommon->_getUserDetails($_IuserId);
						
			//Getting the agent number
			$sqlSelectAgencyCodeId="SELECT 
										agency_code 
									FROM	
										".$CFG['db']['tbl']['agency_code_details']."
									WHERE  
										agency_code_id='".$this->_IinputData['DEAgentId']."'
									AND	   
										status='Y'";
											
			if(DB::isError($resultAgencyCodeId=$this->_Oconnection->query($sqlSelectAgencyCodeId)))
			{
				fileWrite($sqlSelectAgencyCodeId,"SqlError","a+");
				return false;
			}
			if($resultAgencyCodeId->numRows()>0)
			{
				while($rowAgencyCode=$resultAgencyCodeId->fetchRow(DB_FETCHMODE_ASSOC))
				{
					$_SagentId=$rowAgencyCode['agency_code'];
				}
			}
			
			//Preparing the array of payment details to set in form values
			$_ApaymentDetails['firstName'] = $_AuserDetails['first_name'];
			$_ApaymentDetails['lastName'] = $_AuserDetails['last_name'];
			$_ApaymentDetails['paymentText'] = 'Payment for Testing paymnet';
			$_ApaymentDetails['carrierCode'] = $CFG['default']['airlineCode'];
			$_ApaymentDetails['pnr'] = $this->_Spnr;
			$_ApaymentDetails['currencyCode'] = $_AuserCurrency['user_currency'];
			$_ApaymentDetails['paymentAmount'] = $this->_IinputData['CCPayableAmount'];
			$_ApaymentDetails['paymentMode'] = $this->_IinputData['paymentType'];
			$_ApaymentDetails['agentName'] = $_AuserDetails['corporate_name'];
			$_ApaymentDetails['receivedFrom'] = $this->_OairlineService->_setReceivedFrom();
			$_ApaymentDetails['airlinePayment'] = $this->_IinputData['airlinePayment'];
		    $_ApaymentDetails['selectedPNRDetails'] = $this->_IinputData['selectedPNRDetails'];
			#Assign receipt number in for CA payment
			if($this->_IinputData['paymentType']=="CA")
				$_AmakePaymentDetails['accountNumber']=$this->_IinputData['receiptNumberField'];
			else
				$_AmakePaymentDetails['accountNumber']=$_SagentId;
			$_AmakePaymentDetails['mode'] = $this->_IinputData['paymentType'];
			$_AmakePaymentDetails['payAmount'] = $this->_IinputData['CCPayableAmount'];						
		}		
		$_AassignDetails['securityToken'] = '';
		$_AassignDetails['addedSeatAmount'] = 0;
		//Checking last ssr master with status new ends
		foreach($_AflightSegment as $_IondKey => $_AondValues)
		{
			foreach($_AondValues['viaFlightDetails'] as $_IviaKey => $_AviaValues)
			{
				$_AformValues = array();
				$_AformValues['receivedFrom'] = 'GROUPRM';
				$_AformValues['PNR'] = $this->_Spnr;
				$_AformValues['option'] = 'MANUAL';
				$_AformValues['currency'] = $_ScurrencyCode;
				$_AformValues['flightSegmentDetails'][] = $_AviaValues;
				$_StableName = $CFG['db']['tbl']['ssr_pax_details'].' spd 
								INNER JOIN '.
								$CFG['db']['tbl']['passenger_details'].' pd 
								ON 
								spd.passenger_id = pd.passenger_id';
				$_AselectField = array(
					'spd.ssr_pax_id',
					'spd.pax_reference_id',
					encrypt::_decrypt('pd.first_name') .'AS firstName',
					encrypt::_decrypt('pd.last_name') .'AS lastName'
				);
				$_AconditionValue = array(
					'spd.pnr_blocking_id' => $_AviaValues['pnrBlockingId'],
					'spd.via_flight_id' => $_AviaValues['viaFlightId']
				);
				$_AssrPaxDetails = $this->_Oconnection->_performJoinQuery($_StableName,$_AselectField,$_AconditionValue);
				$_AformValues['passengerDetails'] = array();
				$_AformValues['SeatsInformation'][0]['seats'] = array();
				$_AformValues['SeatsInformation'][0]['preference'] = 'AN';
				foreach($_AssrPaxDetails as $_IpaxKey => $_ApaxValues)
				{
					$_StableName = 'ssr_details';
					$_AselectField = array(
						'ssr_code',
						'ssr_master_id',
						'ssr_total_fare'
					);
					$_AconditionValue = array(
						'ssr_pax_id' => $_ApaxValues['ssr_pax_id'],
						'ssr_category_id' => 4,
						'ssr_status' => 'NEW'
					);
					if($_IssrMasterId > 0)
						$_AconditionValue['ssr_master_id'] = $_IssrMasterId;

					$_AssrDetails = $this->_Oconnection->_performQuery($_StableName,$_AselectField,'DB_AUTOQUERY_SELECT',$_AconditionValue);
					foreach($_AssrDetails as $_IssrDetailsKey => $_AssrDetailsValue)
					{
						$_AtempPassenger = array();
						$_AtempPassenger['paxNum'] = $_ApaxValues['pax_reference_id'];
						$_AtempPassenger['firstName'] = $_ApaxValues['firstName'];
						$_AtempPassenger['lastName'] = $_ApaxValues['lastName'];
						array_push($_AformValues['passengerDetails'],$_AtempPassenger);
						$_AtempSeatDetails = array();
						$_AtempSeatDetails['paxId'] = $_ApaxValues['pax_reference_id'];
						$_AtempSeatDetails['seat'] = explode('-',$_AssrDetailsValue['ssr_code'])[0];
						$_AtempSeatDetails['seatAmount'] = $_AssrDetailsValue['ssr_total_fare'];					
						array_push($_AformValues['SeatsInformation'][0]['seats'],$_AtempSeatDetails);
					}
				}
				if(empty($_AformValues['passengerDetails']))
					continue;
				$this->_processingTakeControlSSR($this->_IinputData['requestMasterId'],'insert',$_IssrMasterId);
				$_AformValues['securityToken'] = $_AassignDetails['securityToken'];				
				#seatPaymentFlag is Y then use the assignSeatsResponse from wallet get order response
				if ($this->_IseatPaymentFlag == 'Y') {
					$_AupdateSSRResponse = $this->_IinputData['assignSeatsResponse'];
				}
				else
				{
					$this->_OairlineService->__construct();
					$this->_OairlineService->_Oconnection = $this->_Oconnection;
					$this->_OairlineService->_IrequestMasterId=$this->_IrequestMasterId;
					$this->_OairlineService->_SserviceName='AssignSeats';
					$this->_OairlineService->_AformValues=$_AformValues;
					$_AupdateSSRResponse = $this->_OairlineService->_updateSSR();
								
				}			
				if(isset($_AupdateSSRResponse['responseCode']) && $_AupdateSSRResponse['responseCode']==0)
				{	
					$_SsuccessFlag = 'Y';
					$_AformFlightSegment[] = $_AformValues['flightSegmentDetails'][0];
					$_AformFlightInfo[] = $_AformValues['flightSegmentDetails'][0];
					$_AformSeatInfo[] = $_AformValues['SeatsInformation'][0];
					$_AassignDetails['securityToken'] = $_AupdateSSRResponse['response']['securityToken'];
					$_AassignDetails['addedSeatAmount'] = $_AassignDetails['addedSeatAmount']+$_AupdateSSRResponse['response']['BookingUpdateResponseData']['Success']['PNRAmount']['addedSeatAmount'];
				}
				else
				{
					//Incase of failure response, return the response to user 
					$this->_OssrMaster->_Oconnection = $this->_Oconnection;
					$this->_OssrMaster->__construct();
					$this->_OssrMaster->_IssrMasterId = $this->_IssrMasterId;
					$this->_OssrMaster->_Sstatus = 'ERROR';
					$this->_OssrMaster->_updateSsrMaster();
					
					$this->_OssrDetails->_Oconnection = $this->_Oconnection;
					$this->_OssrDetails->__construct();
					$this->_OssrDetails->_IssrMasterId = $this->_IssrMasterId;
					$this->_OssrDetails->_SssrStatus = 'ERROR';
					$this->_OssrDetails->_updateSsrDetails();
					if(in_array($_SESSION['groupRM']['groupId'],$CFG['default']['airlinesGroupId']))
					{
						if(isset($_AupdateSSRResponse['response']) && $_AupdateSSRResponse['response']!=""){
							$this->_OobjResponse->call("commonObj.showErrorMessage",$_AupdateSSRResponse['response']);
							$this->_OobjResponse->script("hideLoading();Ext.getCmp('airlinePaymentWindow').close();wrapperScript('ticketRequestQueryBox','');");
						}
						else
							$this->_OobjResponse->script("hideLoading();Ext.getCmp('airlinePaymentWindow').close();errorMessages('','".$this->_Osmarty->getConfigVars('COMMON_SERVICE_PROBLEM_TRY_AGAIN_LATER')."');wrapperScript('ticketRequestQueryBox','');");
						return false;
					}
					else
					{
						if(isset($_AupdateSSRResponse['response']) && $_AupdateSSRResponse['response']!=""){
							$this->_OobjResponse->call("commonObj.showErrorMessage",$_AupdateSSRResponse['response']);
							$this->_OobjResponse->script("hideLoadingPopup();wrapperScript('viewRequestSSR','');");
						}
						else
							$this->_OobjResponse->script("hideLoadingPopup();errorMessages('','".$this->_Osmarty->getConfigVars('COMMON_SERVICE_PROBLEM_TRY_AGAIN_LATER')."');wrapperScript('viewRequestSSR','');");
						return false;
					}
					
				}
			}
		}

		if($_SsuccessFlag == 'Y')
		{
			#Call assign seats service and Return to submitTigerPaymentRequestV1 file to use Wallet Input 
			if ($this->_IseatWalletPayment =='Y')
				return $_AupdateSSRResponse;
					
			//Proceed to check the seat for each passenger and update the seat and payment details for the PNR
			$_AformFlightSegment = array_unique($_AformFlightSegment, SORT_REGULAR);
			$this->_updateSeatDetailsStatus($_AformFlightSegment);
			//fileWrite(print_r($this->_IinputData,true),'inputinstantcheck','a+');
			if(!empty($this->_IinputData) && $this->_IinputData['CCPayableAmount'] > 0)
			{
				$_AinputData = array();
				if($this->_SpaymentSyncCron != 'Y') {
					$this->_IinputData['requestMasterId'] = encrypt::staticDataEncode($this->_IinputData['requestMasterId']);
					$this->_IinputData['airlinesRequestId'] = encrypt::staticDataEncode($this->_IinputData['airlinesRequestId']);
					$this->_IinputData['requestSourceId'] = encrypt::staticDataEncode($this->_IinputData['requestSourceId']);
					$this->_IinputData['CCPayableAmount'] = encrypt::staticDataEncode($this->_IinputData['CCPayableAmount']);
					$this->_IinputData['transactionId'] = encrypt::staticDataEncode($this->_IinputData['transactionId']);
					$this->_IinputData['requestTotalCost'] = encrypt::staticDataEncode($this->_IinputData['requestTotalCost']);
					$this->_IinputData['taxReCheckPayment'] = encrypt::staticDataEncode($this->_IinputData['taxReCheckPayment']);
				}
				$_AinputData = $this->_IinputData;
				if($this->_IinputData['paymentType'] == 'AG') {
					fileRequire("classesTpl/class.tpl.submitTigerPaymentRequestTplV1.php");
					$_OsubmitTigerPaymentrequest = new submitTigerPaymentRequestTplV1();
					$_OsubmitTigerPaymentrequest->_Oconnection = $this->_Oconnection;
					$_OsubmitTigerPaymentrequest->_OuserDetails->_IuserId = $this->_Ocommon->_getRequestedUserId($this->_IinputData['requestMasterId']);
					$_OsubmitTigerPaymentrequest->_SagentId = $this->_IinputData['DEAgentId'];
					$_AinputData['receiptNumberField'] = $_OsubmitTigerPaymentrequest->_getAgencyCodeDetails();
					fileWrite($_AinputData['receiptNumberField'],'receiptNumberField','a+');
				}			
				$_AinputData['typeOfSSR'] = 'Seat';		
				$_AinputData['pnr'] = $this->_Spnr;		
				$_AinputData['currencyCode'] = $_ScurrencyCode;	
				$_ApaymentDetails['securityToken'] = $_AupdateSSRResponse['response']['securityToken'];
				$_ApaymentDetails['addedSeatAmount'] = $_AassignDetails['addedSeatAmount'];
				$_ApaymentDetails['flightSegmentDetails'] = $_AformFlightInfo;				
				$_ApaymentDetails['SeatsInformation'] = $_AformSeatInfo;	
				$_AinputData['formValues'] = $_ApaymentDetails;				
				$_AinputData['makePaymentDetails'][0] = $_AmakePaymentDetails;				
				$_AinputData['ssrMasterId'] = $this->_IssrMasterId;				
				#setting flag to mention wallet call 
				if ($this->_IseatPaymentFlag == 'Y')
					$_AinputData['walletSeatPaymentFlag'] = $this->_IseatPaymentFlag;
				
				if($this->_SpaymentSyncCron == 'Y') {
					fileRequire("classesModule/class.module.submitInstantPaymentRequest.php");
					$this->_OsubmitInstantPaymentRequest=new submitInstantPaymentRequest();
					$this->_OsubmitInstantPaymentRequest->_Oconnection=$this->_Oconnection;
					$this->_OsubmitInstantPaymentRequest->_Osmarty=$this->_Osmarty;
					$this->_OsubmitInstantPaymentRequest->_OobjResponse=$this->_OobjResponse;
					$this->_OsubmitInstantPaymentRequest->_ScronStatus=$this->_SpaymentSyncCron;	
					$this->_OsubmitInstantPaymentRequest->_IinputData=$_AinputData;
					$this->_OsubmitInstantPaymentRequest->_StemplateType='tpl';
					$this->_OsubmitInstantPaymentRequest->_classTplName=array(0=>'class.tpl.submitInstantPaymentRequestTpl');
					$this->_OsubmitInstantPaymentRequest->_setModuleData();
					return true;
				}
				$_AinputData = json_encode($_AinputData);	

				$this->_OobjResponse->script("wrapperScript('submitInstantPaymentRequest',".$_AinputData.");");	
			}
		}
	}
	
	/*
	 * Function Name 	: _addSeatNoToDB
	 * Description		: This function is to add the seat details in the DB 
	 * 						and after it redirect to the payment page 
	 * Author			: Subalakshmi S
	 * Created Date		: 08-06-2018
	 */
	function _addSeatNoToDB()
	{
		global $CFG;
		
		$this->_Aindex = array();
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$this->_Ocommon->_Osmarty = $this->_Osmarty;
		$this->_Ocommon->_OobjResponse = $this->_OobjResponse;

		//List of selected passenger 
		$this->_ApassengerSSRList = $this->_IinputData['passengerSSRList'];
		$this->_OssrMaster->_Oconnection = $this->_Oconnection;
		$this->_OssrMaster->_IrequestMasterId = $this->_IrequestMasterId;
		$this->_OssrMaster->_Spnr = $this->_Spnr;
		$this->_OssrMaster->_IssrAmount = $this->_IinputData['ssrTotalAmount'];
		$this->_OssrMaster->_IupdatedBy = $_SESSION['groupRM']['groupUserId'];
		$this->_OssrMaster->_SssrUpdatedDate = $this->_Ocommon->_getUTCDateValue();
		$this->_OssrMaster->_SlastTransaction = 'N';
		$this->_OssrMaster->_Sstatus = 'NEW';
		$this->_OssrMaster->_IssrCategoryId = 4;
		$this->_OssrMaster->_insertSsrMaster();
		
		$this->_IssrMasterId = $this->_OssrMaster->_IssrMasterId;
		
		$this->_OssrDetails->_Oconnection = $this->_Oconnection;
		
		//Looping all the passenger in the pnr for inserting the ssr details if any new ssr has been selected
		$_IssrPaxIds = '';
		foreach($this->_ApassengerSSRList AS $paxIndex => $_ApassengerSSRArray)
		{
			foreach($_ApassengerSSRArray AS $referenceKey => $_AssrArray)
			{
				if(is_array($_ApassengerSSRArray[$referenceKey]) && !empty($_ApassengerSSRArray[$referenceKey]))
				{					
					foreach($_AssrArray AS $ssrCategory => $selectSSRArray)
					{
						$_IssrPaxIds = $selectSSRArray['ssrPaxId'];
						if(is_array($selectSSRArray['newSeat']) && !empty($selectSSRArray['newSeat']))
						{
							$this->_OssrDetails->__construct();
							$this->_OssrDetails->_IssrMasterId = $this->_IssrMasterId;
							$this->_OssrDetails->_IssrPaxId = $_IssrPaxIds;
							$this->_OssrDetails->_IssrCategoryId = 4;
							$this->_OssrDetails->_SssrCode = $selectSSRArray['newSeat']['seatCode'];
							$this->_OssrDetails->_IssrBaseFare = $selectSSRArray['newSeat']['seatFee'];
							$this->_OssrDetails->_IssrTax = 0;
							$this->_OssrDetails->_IssrTotalFare = $selectSSRArray['newSeat']['seatFee'];
							$this->_OssrDetails->_SemdNumber = '';
							$this->_OssrDetails->_SssrStatus = 'NEW';
							$this->_OssrDetails->_insertSsrDetails();
						}
					}
				}
			}
		}
		
		/*To check whether the selected seats are paid or free.
		 If it is paid, it get redirected to payment or if it is free, 
		 the seats are directly assign to the PNR */
		 
		if($this->_IinputData['ssrTotalAmount'] != 0)
		{
			$this->_OairlinesRequestMapping->__construct();
			$this->_OairlinesRequestMapping->_Oconnection = $this->_Oconnection;
			$this->_OairlinesRequestMapping->_IrequestMasterId = $this->_IrequestMasterId ;
			$_AairlinesRequestMapping = $this->_OairlinesRequestMapping->_selectAirlinesRequestMapping();

			if(in_array($_SESSION['groupRM']['groupId'],$CFG['default']['airlinesGroupId']))
			{
				$_AairlinePayment['currentStatusId'] = $_AairlinesRequestMapping[0]['current_status'];
				$_AairlinePayment['requestMasterId'] = $this->_IrequestMasterId;
				$_AairlinePayment['airlinesRequestId'] = $_AairlinesRequestMapping[0]['airlines_request_id'];
				$_AairlinePayment['lastUpdated'] = $_AairlinesRequestMapping[0]['last_updated'];
				$_AairlinePayment['seatSelection'] = 'Y';
				$_AairlinePayment['INSTANT'] = 'Y';
				$_AairlinePayment['seatDetails'] = $this->_IinputData['ssrTotalAmount'];
				$_AairlinePayment['ssrMasterId'] = $this->_IssrMasterId;
				$_AairlinePayment['pnr'] = $this->_Spnr;
				$_AairlinePayment = json_encode($_AairlinePayment);
				return $this->_OobjResponse->script("commonObj.closeGrmPopup();commonObj.closeGrmPopup(true);wrapperScript('airlinePayment',".$_AairlinePayment.");");
			}
			else
			{
				$this->_OpaymentMaster = new paymentMaster();
				$this->_OpaymentMaster->__construct();
				$this->_OpaymentMaster->_Oconnection=$this->_Oconnection;
				$this->_OpaymentMaster->_IairlinesRequestId = $_AairlinesRequestMapping[0]['airlines_request_id'];
				$_ApaymentMaster=$this->_OpaymentMaster->_selectPaymentMaster();
				if ($this->_SapiCall == "Y")
					return  "Seats inserted successfull";
				if (isset($CFG['payment']['walletPaymentIntegration']['status']) && $CFG['payment']['walletPaymentIntegration']['status']=='Y')
					return $this->_OobjResponse->script("commonObj.closeGrmPopup();commonObj.closeGrmPopup(true);wrapperScript('submitPaymentProcess','".encrypt::staticDataEncode($this->_IrequestMasterId).",".encrypt::staticDataEncode($_AairlinesRequestMapping[0]['airlines_request_id']).",".encrypt::staticDataEncode($_ApaymentMaster[0]['payment_master_id']).",".encrypt::staticDataEncode(payment).",".encrypt::staticDataEncode(seatSelection).",".$this->_IinputData['ssrTotalAmount'].",".$this->_Spnr.",".$this->_IssrMasterId."');");
				else
					return $this->_OobjResponse->script("commonObj.closeGrmPopup();commonObj.closeGrmPopup(true);wrapperScript('submitPaymentProcess','".encrypt::staticDataEncode($this->_IrequestMasterId).",".encrypt::staticDataEncode($_AairlinesRequestMapping[0]['airlines_request_id']).",".encrypt::staticDataEncode($_ApaymentMaster[0]['payment_master_id']).",".encrypt::staticDataEncode(payment).",".encrypt::staticDataEncode(seatSelection).",".$this->_IinputData['ssrTotalAmount'].",".$this->_Spnr."');");
			}
		}
		else
		{
			return $this->_addSeatNoToPnr();
		}

	}
	
	/*
	 * Function Name 	: _updateSeatDetailsStatus
	 * Description		: This function is to update the seat details in the 
	 * 						DB as completed if it is added successfully 
	 * 						or as error if it is not added and to sync 
	 * 						the payment details with the pnr
	 * Author			: Subalakshmi S
	 * Created Date		: 08-06-2018
	 */
	function _updateSeatDetailsStatus($_AformValues = array())
	{
		global $CFG;
		
		$this->_OssrPaxDetails->_Oconnection = $this->_Oconnection;
		$this->_OssrDetails->_Oconnection = $this->_Oconnection;
		
		//Getting the seat details present in the PNR for each passenger
		
		$this->_OairlineService->__construct();
		$this->_OairlineService->_Oconnection = $this->_Oconnection;
		$this->_OairlineService->_Spnr = $this->_Spnr;
		$this->_OairlineService->_IrequestMasterId = $this->_IrequestMasterId;
		$this->_OairlineService->_StypeOfSsr = 'SEAT';
		$_AssrDetailsForPNR = $this->_OairlineService->_getSSRDetailsForPNR();
		$_IssrTotalAmount = 0;

		if($_AssrDetailsForPNR['responseCode']==0)
		{
			if(isset($_AssrDetailsForPNR['response']['paxSSR']) && !empty($_AssrDetailsForPNR['response']['paxSSR']))
			{
				$_ApassengerSSRDetails = $_AssrDetailsForPNR['response']['paxSSR'];
				foreach($_ApassengerSSRDetails AS $_SpaxIndex => $_SselectedSeat)
				{
					if(is_array($_SselectedSeat['AncillaryServices']) && !empty($_SselectedSeat['AncillaryServices']))
					{
						foreach($_AformValues AS $mainSectorKey => $mainSectorValue)
						{
							$_SreferenceKey = $this->_generateFlightReferenceKey($mainSectorValue);
							foreach($_SselectedSeat['AncillaryServices'] AS $flightKey => $flightValue)
							{
								$_SssrReferenceKey = str_replace(" ","",$flightValue['FlightReference']);
								if($_SreferenceKey == $_SssrReferenceKey)
								{
									$this->_OssrPaxDetails->__construct();
									$this->_OssrPaxDetails->_IpnrBlockingId = $mainSectorValue['pnrBlockingId'];
									$this->_OssrPaxDetails->_IpaxReferenceId = $_SselectedSeat['nameId'];
									$this->_OssrPaxDetails->_IviaFlightId = $mainSectorValue['viaFlightId'];
									$this->_OssrPaxDetails->_selectSsrPaxDetails();
									$_IssrPaxId = $this->_OssrPaxDetails->_IssrPaxId;
									
									$this->_OssrDetails->__construct();
									$this->_OssrDetails->_IssrMasterId = $this->_IssrMasterId;
									$this->_OssrDetails->_IssrPaxId = $_IssrPaxId;
									$this->_OssrDetails->_IssrCategoryId = 4;
									$this->_OssrDetails->_selectSsrDetails();
									$_IssrDetailsId = $this->_OssrDetails->_IssrDetailsId;
									$_SssrCode = $flightValue['SeatNumber'];
									//ssrcode have seat number and seat group which is splitted
									$_AseatGroupCode = explode('-',$this->_OssrDetails->_SssrCode);
									if($_SssrCode == $_AseatGroupCode[0]){
										$_SssrStatus = "COMPLETED";
										$_IssrTotalAmount += $this->_OssrDetails->_IssrTotalFare;
										//Insert ssr pax group
										$this->_OssrPaxGroup->__construct();
										$this->_OssrPaxGroup->_Oconnection = $this->_Oconnection;
										$this->_OssrPaxGroup->_IssrDetailsId = $_IssrDetailsId;
										$this->_OssrPaxGroup->_IssrId = $flightValue['id'];
										$this->_OssrPaxGroup->_SssrWeight = $flightValue['pieceOrWeight'];
										$this->_OssrPaxGroup->_insertSsrPaxGrouping();
									}
									else
										$_SssrStatus = "ERROR";
									$_SssrCode = $this->_OssrDetails->_SssrCode;
									$this->_OssrDetails->__construct();
									$this->_OssrDetails->_IssrMasterId = $this->_IssrMasterId;
									$this->_OssrDetails->_IssrPaxId = $_IssrPaxId;
									$this->_OssrDetails->_SssrCode = $_SssrCode;
									$this->_OssrDetails->_SssrStatus = $_SssrStatus;
									$this->_OssrDetails->_updateSsrDetails();
								}
							}
						}
					}
				}
			}

			if ($this->_IinputData['seatPayment'] != 'Y') {
				//Update the old ssr master rows make it as inactive transaction
				$this->_OssrMaster->__construct();
				$this->_OssrMaster->_Oconnection = $this->_Oconnection;
				$this->_OssrMaster->_IssrMasterIdNotEqual = $this->_IssrMasterId;
				$this->_OssrMaster->_IrequestMasterId = $this->_IrequestMasterId;
				$this->_OssrMaster->_IssrCategoryId = 4;
				$this->_OssrMaster->_SlastTransaction = 'N';
				$this->_OssrMaster->_updateSsrMaster();
			}
			
			//Update the status and make it as active transaction
			$this->_OssrMaster->__construct();
			$this->_OssrMaster->_Oconnection = $this->_Oconnection;
			$this->_OssrMaster->_IssrMasterId = $this->_IssrMasterId;
			$this->_OssrMaster->_IrequestMasterId = $this->_IrequestMasterId;
			$this->_OssrMaster->_IssrCategoryId = 4;
			$this->_OssrMaster->_IssrAmount = $_IssrTotalAmount;
			$this->_OssrMaster->_Sstatus = 'COMPLETED';
			$this->_OssrMaster->_SlastTransaction = 'Y';
			$this->_OssrMaster->_updateSsrMaster();			
			
			if($_IssrTotalAmount > 0)
			{
				//Updating the payment details
				$this->_OpnrBlockingDetails->_Oconnection = $this->_Oconnection;
				$this->_OpnrBlockingDetails->__construct();
				$this->_OpnrBlockingDetails->_Spnr=$this->_Spnr;
				$this->_ApnrBlockingDetails=$this->_OpnrBlockingDetails->_selectPnrBlockingDetails();
				
				foreach($this->_ApnrBlockingDetails AS $pnrBlockingKey => $pnrBlockingValue)
				{
					$this->_OpnrBlockingDetails->_Oconnection = $this->_Oconnection;
					$this->_OpnrBlockingDetails->__construct();
					$this->_OpnrBlockingDetails->_Spnr=$this->_Spnr;
					$this->_OpnrBlockingDetails->_IpnrBlockingId =$pnrBlockingValue['pnr_blocking_id'];
					$this->_OpnrBlockingDetails->_IpnrAmount =$pnrBlockingValue['pnr_amount']+$_IssrTotalAmount;
					$this->_OpnrBlockingDetails->_updatePnrBlockingDetails();
				} 
			}

			if(isset($CFG["site"]["navitaireBasedAirline"]) && $CFG["site"]["navitaireBasedAirline"] == "Y" && $this->_IseatPaymentFlag != 'Y') {
				fileRequire("classesTpl/class.tpl.syncPnrDetailsTpl.php");
				$_OsyncPnrDetailsTpl = new syncPnrDetailsTpl();
				$_OsyncPnrDetailsTpl->__construct();
				$_OsyncPnrDetailsTpl->_Oconnection = $this->_Oconnection;
				$_OsyncPnrDetailsTpl->_Osmarty = $this->_Osmarty;
				$_OsyncPnrDetailsTpl->_OobjResponse = $this->_OobjResponse;
				$_OsyncPnrDetailsTpl->_IrequestMasterId = $this->_IrequestMasterId;
				$_OsyncPnrDetailsTpl->_Spnr = $this->_Spnr;
				$_OsyncPnrDetailsTpl->_SseatSelection = 'Y';
				$_OsyncPnrDetailsTpl->_syncPassengerAndPaymentInfos();
			}
			//set SSramount value in input data
			$this->_IinputData['ssrTotalAmount'] = $_IssrTotalAmount;
			//check the ssr amount and change the success message alert
			fileWrite($this->_IinputData['ssrTotalAmount'],'validationmsg','a+');		
			if($this->_IinputData['ssrTotalAmount'] > 0) {
				$_SvalidationMsg = 'VALIDATION_POPUPSSRDETAILS_ADD_SEAT_SUCCESS_MSG';
			} else {
				$_SvalidationMsg = 'VALIDATION_POPUPSSRDETAILS_ADD_SEATONLY_SUCCESS_MSG';
				#To update the takeControlDetails for the status update
		        fileRequire("dataModels/class.takeControlDetails.php");
		        $_OtakeControlDetails=new takeControlDetails();
				$_IreferenceId=$this->_IssrMasterId;
		        if(!empty($_IreferenceId))
		        {
			        $_OtakeControlDetails->__construct();
			        $_OtakeControlDetails->_Oconnection = $this->_Oconnection;
			        $_OtakeControlDetails->_ScontrolStatus = 'Completed';
			        $_OtakeControlDetails->_IreferenceId = $_IreferenceId;
		        	$_OtakeControlDetails->_updateTakeControlDetails();
		        }
			}
			if($this->_SpaymentSyncCron == 'Y')
				return true;
			#view history seat added data send to noSql.
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
			if(in_array($_SESSION['groupRM']['groupId'],$CFG['default']['airlinesGroupId']))
				$this->_OobjResponse->script("hideLoadingPopup();if(Ext.getCmp('airlinePaymentWindow')){Ext.getCmp('airlinePaymentWindow').close();commonObj.showSuccessMessage(globalLanguageVar['".$_SvalidationMsg."']);}else{commonObj.closeGrmPopup();commonObj.closeGrmPopup(true);commonObj.showSuccessMessage(globalLanguageVar['".$_SvalidationMsg."']);wrapperScript('ticketRequestQueryBox','');}");
			else
				$this->_OobjResponse->script("hideLoadingPopup();commonObj.showSuccessMessage(globalLanguageVar['".$_SvalidationMsg."']);commonObj.closeGrmPopup();commonObj.closeGrmPopup(true);wrapperScript('viewRequestSSR','');");
		}
	}
	
	function _getFareClass($_IapprovedFlightId,$_IviaFlightId='')
	{
		global $CFG;
		
		$_sqlFlightCabinDetails = "SELECT 
								flight_cabin_mapping_id,
								request_approved_flight_id,
								via_flight_id,
								fare_basis_code,
								rule_number,
								class_of_service
							FROM 
								".$CFG['db']['tbl']['flight_cabin_mapping_details']."
							WHERE 
								request_approved_flight_id = '".$_IapprovedFlightId."' AND
								via_flight_id = '".$_IviaFlightId."' ";
		#adding dynamic fare class config for set  fare basis code
		if($CFG['pnrBlockingClass']['dynamicFareClass'] == 'Y')
			$_sqlFlightCabinDetails.=" ORDER BY adult_base_fare DESC";
		elseif($CFG['pnrBlockingClass']['dynamicFareClass'] == 'LAFC')
			$_sqlFlightCabinDetails.=" ORDER BY adult_base_fare ASC";
		else
			$_sqlFlightCabinDetails.=" ORDER BY flight_cabin_mapping_id DESC";

		$_AflightCabinDetails= $this->_Ocommon->_executeQuery($_sqlFlightCabinDetails);
		return $_AflightCabinDetails;
        
	}
	/*
	 * Funtion name : _addInstantSSRToDB
	 * Description  : This funtion will invoke when the process of instant payment
	 */
	function _addInstantSSRToDB()
	{
		global $CFG;
		$this->_Aindex = array();
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$this->_Ocommon->_Osmarty = $this->_Osmarty;
		$this->_Ocommon->_OobjResponse = $this->_OobjResponse;
		
		$this->_OssrMaster->__construct();
		$this->_OssrMaster->_Oconnection = $this->_Oconnection;
		$this->_OssrMaster->_IrequestMasterId = $this->_IrequestMasterId;
		$this->_OssrMaster->_Sstatus = 'NEW';
		$_AselectSSRDetails=$this->_OssrMaster->_selectSsrMaster();
		foreach ($_AselectSSRDetails as $ssrKey => $ssrVal)
		{
			if($ssrVal['ssr_master_id']!=$this->_IssrMasterId)
			{
				$this->_OssrMaster->__construct();
				$this->_OssrMaster->_Oconnection = $this->_Oconnection;
				$this->_OssrMaster->_IssrMasterId = $ssrVal['ssr_master_id'];
				$this->_OssrMaster->_IrequestMasterId = $ssrVal['request_master_id'];
				$this->_OssrMaster->_Sstatus = 'INCOMPLETE';
				$this->_OssrMaster->_updateSsrMaster();
			}
		}

		if($this->_IinputData['ssrTotalAmount'] != 0 && $this->_IinputData['instantPayment'] != 'Y')
		{
			$this->_OairlinesRequestMapping->__construct();
			$this->_OairlinesRequestMapping->_Oconnection = $this->_Oconnection;
			$this->_OairlinesRequestMapping->_IrequestMasterId = $this->_IrequestMasterId ;
			$_AairlinesRequestMapping = $this->_OairlinesRequestMapping->_selectAirlinesRequestMapping();

			if(in_array($_SESSION['groupRM']['groupId'],$CFG['default']['airlinesGroupId']))
			{
				$_AairlinePayment['currentStatusId'] = $_AairlinesRequestMapping[0]['current_status'];
				$_AairlinePayment['requestMasterId'] = $this->_IrequestMasterId;
				$_AairlinePayment['airlinesRequestId'] = $_AairlinesRequestMapping[0]['airlines_request_id'];
				$_AairlinePayment['lastUpdated'] = $_AairlinesRequestMapping[0]['last_updated'];
				$_AairlinePayment['INSTANT'] = 'Y';
				$_AairlinePayment['PNR'] = $this->_Spnr;
				$_AairlinePayment['ssrTotalAmount'] = $this->_IinputData['ssrTotalAmount'];
				$_AairlinePayment['ssrMasterId'] = $this->_IssrMasterId;
				$_AairlinePayment = json_encode($_AairlinePayment);
				return $this->_OobjResponse->script("commonObj.closeGrmPopup();commonObj.closeGrmPopup(true);wrapperScript('airlinePayment',".$_AairlinePayment.");");
			}
			else
			{
				$this->_OpaymentMaster = new paymentMaster();
				$this->_OpaymentMaster->__construct();
				$this->_OpaymentMaster->_Oconnection=$this->_Oconnection;
				$this->_OpaymentMaster->_IairlinesRequestId = $_AairlinesRequestMapping[0]['airlines_request_id'];
				$_ApaymentMaster=$this->_OpaymentMaster->_selectPaymentMaster();
				$_ApaymentMaster = end($_ApaymentMaster);
				// return $this->_OobjResponse->script("commonObj.closeGrmPopup();commonObj.closeGrmPopup(true);wrapperScript('submitPaymentProcess','".$this->_IrequestMasterId.",".$_AairlinesRequestMapping[0]['airlines_request_id'].",".$_ApaymentMaster[0]['payment_master_id'].",payment,seatSelection,".$this->_IinputData['ssrTotalAmount']."');");
				#Assign input to travelagent payment
				$_AairlinePayment['currentStatusId'] = $_AairlinesRequestMapping[0]['current_status'];
				$_AairlinePayment['requestMasterId'] = $this->_IrequestMasterId;
				$_AairlinePayment['airlinesRequestId'] = $_AairlinesRequestMapping[0]['airlines_request_id'];
				$_AairlinePayment['lastUpdated'] = $_AairlinesRequestMapping[0]['last_updated'];
				$_AairlinePayment['INSTANT'] = 'Y';
				$_AairlinePayment['PNR'] = $this->_Spnr;
				$_AairlinePayment['ssrTotalAmount'] = $this->_IinputData['ssrTotalAmount'];
				$_AairlinePayment['ssrMasterId'] = $this->_IssrMasterId;
				if ($this->_SapiCall == "Y")
					return ["data"=>"SSR Added successfully"];
				return $this->_OobjResponse->script("commonObj.closeGrmPopup();commonObj.closeGrmPopup(true);wrapperScript('submitPaymentProcess','".$_AairlinePayment['currentStatusId'].","
					.encrypt::staticDataEncode($_AairlinePayment['requestMasterId']).","
					.encrypt::staticDataEncode($_AairlinePayment['airlinesRequestId']).","
					.$_AairlinePayment['lastUpdated'].","
					.$_AairlinePayment['INSTANT'].","
					.$_AairlinePayment['PNR'].","
					.$_AairlinePayment['ssrTotalAmount'].","
					.$_AairlinePayment['ssrMasterId'].","
					.encrypt::staticDataEncode($_ApaymentMaster['payment_master_id']).","
					.encrypt::staticDataEncode('payment').",seatSelection');");
			}
		}
		else
		{
			if(isset($CFG["queueSync"]["offlineSync"]["ancillarySync"]) && $CFG["queueSync"]["offlineSync"]["ancillarySync"]["status"]=="Y")
				$this->_updateCancelSSR();
			//Selected SSR list for passenger
			$this->_ApassengerSSRList = json_decode($this->_IinputData['passengerSSRList'],true);
			
			//Available SSR list for the PNR
			$this->_AavailableSSR = json_decode($this->_IinputData['SSRList'],true);
			
			//Preparing the service form values
			$this->_AformValues['flightSegmentDetails'] = $this->_prepareServiceFormValues();
			$this->_AformValues['PNR'] = $this->_Spnr;
			//Set the via flight status and merged via flight details
			$this->_setViaFlightStatus($this->_AformValues['flightSegmentDetails']);
			
			$this->_callUpdateSsrService();
			
		}
	}

	/*
	 * Funtion name : _callUpdateSsrService
	 * Description  : This function made up of previous code to reuse code to update ssr in service.
	 * return 		: Obj response
	 */
	
	function _callUpdateSsrService()
	{
		global $CFG;
		//Preparing the passenger ssr details
		$_ApassengerSSRDetails = array();
		$_ApassengerAddSSRDetails = array();
		$_AondSSR=array();
		/*
		 * Based on this value, to set service formvalue array as EMPTY if there is no values
		 * We can set this value in pnrWise, Segmentwise and viaFlightwise
		 */
		$existingSSRInPnrWise = $newSSRInPnrWise = "N";
		$_SenableSSR='N';
		//Main flight segement details for the PNR
		foreach($this->_AformValues['flightSegmentDetails'] AS $mainSectorKey => $mainSectorValue)
		{
			$existingSSRInSegmentWise = $newSSRInSegmentWise = "N";
			
			$_ApassengerSSRDetails[$mainSectorKey] = array();
			$_AondPassengerSSRDetails=array();
			$_AondPassengerRemoveSSRDetails=array();
			foreach($mainSectorValue['viaFlightDetails'] AS $viaFlightKey => $viaFlightValue)
			{
				$existingSSRInLegWise = $newSSRInLegWise = "N";
				
				//Preparing the reference key for the segment
				$_SreferenceKey = $this->_generateFlightReferenceKey($viaFlightValue);
				
				$paxAddSSRIndex = 0;
				$paxExistingSSRIndex = 0;
				//Looping the passenger ssr list to prepare form value for add/update ssr

				foreach($this->_ApassengerSSRList AS $passengerIndex => $passengerDetails)
				{
					$selectedSSRDetails = $passengerDetails[$_SreferenceKey];
					foreach($selectedSSRDetails AS $ssrCategory => $ssrDetails)
					{
						$_AondPassengerSSR=array();
						$_AondPassengerRemoveSSR=array();
						if(isset($CFG["ssr"]["instantPayment"]) && $CFG["ssr"]["instantPayment"]["status"] !="Y")
						{
						if(array_key_exists('existing',$ssrDetails))
						{
							$_AexistingSSRCode = array_keys($ssrDetails['existing']);
							$_AnewSsrCode = (isset($ssrDetails['newSSR']) ? array_keys($ssrDetails['newSSR']) : array());
							//Checking if any ssr need to cancel
							$_AdiffSSRCode = array_diff($_AexistingSSRCode,$_AnewSsrCode);
							//Preparing array for cancelling the SSR
							if(!empty($_AdiffSSRCode)) 
							{
								foreach($_AdiffSSRCode AS $ssrIndex => $ssrCode) {
									if(!in_array($ssrCode, $CFG["ssr"]["skipSSRPolicy"]['defaultSSR']))
									{
										$_ApassengerSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxExistingSSRIndex]['SSRCode'] = $ssrCode;
										$_ApassengerSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxExistingSSRIndex]['id'] = $ssrDetails['existing'][$ssrCode]['ssrId'];

										$_ApassengerSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxExistingSSRIndex]['paxNum'] = $passengerDetails['paxNum'];
										$selectCanceledSSRdetailsId="SELECT sd.ssr_details_id,
																			sd.ssr_pax_id,
																			sd.ssr_code,
																			sd.ssr_master_id
																		FROM ssr_details sd
																		INNER JOIN ssr_pax_details spd ON sd.ssr_pax_id = spd.ssr_pax_id
																		INNER JOIN ssr_pax_grouping spg 
																		ON sd.ssr_details_id=spg.ssr_details_id
																		WHERE  spd.pax_reference_id='".$passengerDetails['paxNum']."'
																		AND spg.ssr_id='".$ssrDetails['existing'][$ssrCode]['ssrId']."'
																		AND spd.pnr_blocking_id=".$viaFlightValue['pnrBlockingId'];
										if(!empty($this->_Ocommon->_executeQuery($selectCanceledSSRdetailsId)[0]))
											$_AssrDetails[] = $this->_Ocommon->_executeQuery($selectCanceledSSRdetailsId)[0];
										if(isset($ssrDetails['existing'][$ssrCode]['flightNumber']) && !empty($ssrDetails['existing'][$ssrCode]['flightNumber']))
										{
											foreach ($ssrDetails['existing'][$ssrCode]['flightNumber'] as $removeFlightkey => $removeFlightVal)
											{
												if(in_array($viaFlightValue['flightNumber'], $removeFlightVal))
												{
													if(!isset($_AondSSR[$removeFlightkey]['flights']))
														$_AondSSR[$removeFlightkey]['flights']=$ssrDetails['existing'][$ssrCode]['flightSegments'];
													$_AondPassengerRemoveSSR[]=$_ApassengerSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxExistingSSRIndex];
													if(!empty($_AondPassengerRemoveSSR) && end($_AexistingSSRCode)==$ssrCode)
														$_AondPassengerRemoveSSRDetails=array_merge($_AondPassengerRemoveSSRDetails,$_AondPassengerRemoveSSR);
													$_AondSSR[$removeFlightkey]['passengerSSRDetails']=$_AondPassengerRemoveSSRDetails;
													unset($_ApassengerSSRDetails[$mainSectorKey]['via'][$viaFlightKey]);
												}
											}
										}
										$paxExistingSSRIndex++;
									}
								}
								$existingSSRInPnrWise = $existingSSRInSegmentWise = $existingSSRInLegWise  = "Y";
							}
						}
						}
						
						if(array_key_exists('newSSR',$ssrDetails))
						{
							$_AssrCode = array_keys($ssrDetails['newSSR']);
							$_AexistingSSRCode = array();
							if(isset($ssrDetails['existing']))
								$_AexistingSSRCode = array_keys($ssrDetails['existing']);
							//Checking if any new ssr added to update
							$_AdiffNewSSRCode = array_diff($_AssrCode,$_AexistingSSRCode);
							//Preparing array for adding the SSR in pnr
							if(!empty($_AdiffNewSSRCode)) {
								foreach($_AdiffNewSSRCode AS $ssrIndex => $ssrCode) {
									if($ssrCode!="") {
										$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex]['SSRCode'] = $ssrCode;
										$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex]['fare'] = $ssrDetails['newSSR'][$ssrCode]['ssrAmount'];
										$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex]['paxNum'] = $passengerDetails['paxNum'];

										$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex]['GroupDescription'] =  $ssrDetails['newSSR'][$ssrCode]['GroupDescription'];
										$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex]['SSRType'] =  $ssrDetails['newSSR'][$ssrCode]['SSRType'];
										$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex]['SSRVendor'] =  $ssrDetails['newSSR'][$ssrCode]['SSRVendor'];
										$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex]['SSRName'] =  $ssrDetails['newSSR'][$ssrCode]['SSRName'];

										$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex]['Available'] =  $ssrDetails['newSSR'][$ssrCode]['ssrAvailable'];
										// if(isset($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['status']=='Y')
										{
											if(isset($CFG["ssr"]["skipSSRPolicy"]) && ($CFG["ssr"]["skipSSRPolicy"]['status']=='Y' || $CFG["ssr"]["skipSSRPolicy"]['applyUniqueSsrCode']=='Y'))
											{
											$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex]['SSRCode'] = substr($ssrCode,0,strpos($ssrCode,'_'));
											}
											//Set additional info in formValues
											$_AadditionalInfo=array();
											$_AadditionalInfo['EMDType'] = $ssrDetails['newSSR'][$ssrCode]['additional_info']['EMDType'];
											$_AadditionalInfo['RficCode'] = $ssrDetails['newSSR'][$ssrCode]['additional_info']['RficCode'];
											$_AadditionalInfo['RficSubcode'] = $ssrDetails['newSSR'][$ssrCode]['additional_info']['RficSubcode'];
											$_AadditionalInfo['vendor'] = $ssrDetails['newSSR'][$ssrCode]['additional_info']['SSRVendor'];
											$_AadditionalInfo['SSRName'] = $ssrDetails['newSSR'][$ssrCode]['additional_info']['SSRName'];//$ssrDetails['newSSR'][$ssrCode]['ssr_description'];
											$_AadditionalInfo['SSRType'] = $ssrDetails['newSSR'][$ssrCode]['SSRType'];
											if(isset($ssrDetails['newSSR'][$ssrCode]['additional_info']['SSRType']))
												$_AadditionalInfo['SSRType'] = $ssrDetails['newSSR'][$ssrCode]['additional_info']['SSRType'];
											$_AadditionalInfo['origin'] = $ssrDetails['newSSR'][$ssrCode]['additional_info']['origin'];
											$_AadditionalInfo['destination'] = $ssrDetails['newSSR'][$ssrCode]['additional_info']['destination'];

											$_AadditionalInfo['RefundIndicator'] = $ssrDetails['newSSR'][$ssrCode]['additional_info']['RefundIndicator'];
											$_AadditionalInfo['FeeApplicationIndicator'] = $ssrDetails['newSSR'][$ssrCode]['additional_info']['FeeApplicationIndicator'];
											$_AadditionalInfo['SegmentIndicator'] = $ssrDetails['newSSR'][$ssrCode]['additional_info']['SegmentIndicator'];
											$_AadditionalInfo['SpecialServiceCode'] = $ssrDetails['newSSR'][$ssrCode]['SpecialServiceCode'];
											$_AadditionalInfo['BaggageWeight'] = $ssrDetails['newSSR'][$ssrCode]['BaggageWeight'];
											$_AadditionalInfo['BaggageMetric'] = $ssrDetails['newSSR'][$ssrCode]['BaggageMetric'];
											$_AadditionalInfo['BaggageSize'] = $ssrDetails['newSSR'][$ssrCode]['BaggageSize'];

											$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex]['addtionalInfo'] = $_AadditionalInfo;
											if(isset($ssrDetails['newSSR'][$ssrCode]['flightSegments']))
												$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex]['flightSegments'] = $ssrDetails['newSSR'][$ssrCode]['flightSegments'];
											#preapre update SSR details ond level
											if(isset($ssrDetails['newSSR'][$ssrCode]['flightNumber']) && !empty($ssrDetails['newSSR'][$ssrCode]['flightNumber']))
											{
												foreach ($ssrDetails['newSSR'][$ssrCode]['flightNumber'] as $mergeFlightkey => $mergeFlightVal)
												{
													if(in_array($viaFlightValue['flightNumber'], $mergeFlightVal))
													{
														if(!isset($_AondSSR[$mergeFlightkey]['flights']))
															$_AondSSR[$mergeFlightkey]['flights']=$ssrDetails['newSSR'][$ssrCode]['flightSegments'];
														$_AondPassengerSSR[]=$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$paxAddSSRIndex];
														if(!empty($_AondPassengerSSR) && end($_AdiffNewSSRCode)==$ssrCode)
															$_AondPassengerSSRDetails=array_merge($_AondPassengerSSRDetails,$_AondPassengerSSR);
														$_AondSSR[$mergeFlightkey]['passengerUpdateSSRDetails']=$_AondPassengerSSRDetails;
														unset($_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey]);
													}
												}
											}
										}
										$paxAddSSRIndex++;
										if(isset($ssrDetails['newSSR'][$ssrCode]['FeeApplicationType']) && $ssrDetails['newSSR'][$ssrCode]['FeeApplicationType']=='Journey')
											$_SenableSSR='Y';
									}
								}
								$newSSRInPnrWise = $newSSRInLegWise  = $newSSRInSegmentWise = 'Y';
							}
						}
					}
					if(empty($selectedSSRDetails) && $_SenableSSR=='Y' && isset($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['mergingFlights']=='Y')
					{
						$newSSRInPnrWise = $newSSRInLegWise  = $newSSRInSegmentWise = 'Y';
						$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey] = $_ApassengerAddSSRDetails[$mainSectorKey]['via'][0];
					}
				}
				if(!empty($_AondSSR[$mainSectorKey]['flights']))
					$_AondSSR[$mainSectorKey]['flights'][$viaFlightKey]['departureDateAndTime']=$viaFlightValue['departureDateAndTime'];
				//Set the array as EMPTY if no SSR details available to process in the via flight details 
				if($existingSSRInLegWise == "N" && empty($_AondPassengerRemoveSSR))
					$_ApassengerSSRDetails[$mainSectorKey]['via'][$viaFlightKey] = "EMPTY";
				if($newSSRInLegWise == "N" && empty($_AondPassengerSSR))
					$_ApassengerAddSSRDetails[$mainSectorKey]['via'][$viaFlightKey] = "EMPTY";
				
			}
			//Set the array as EMPTY if no SSR details available to process in the flight segment details
			if($existingSSRInSegmentWise =="N")
				$_ApassengerSSRDetails[$mainSectorKey] = "EMPTY";
			if($newSSRInSegmentWise =="N")
				$_ApassengerAddSSRDetails[$mainSectorKey] = "EMPTY";
		}
		//Reset the cancel SSR array if there is no ssr present in the cancel flow
		if($existingSSRInPnrWise=="N")
			$_ApassengerSSRDetails = array();
		if($newSSRInPnrWise=="N")
			$_ApassengerAddSSRDetails = array();
		if(!empty($_AondSSR))
		{
			foreach ($_ApassengerAddSSRDetails as $addSSRkey => $addSSRval)
			{
				if(empty($_ApassengerAddSSRDetails[$addSSRkey]['via']) || $_ApassengerAddSSRDetails[$addSSRkey]['via']=="EMPTY")
					unset($_ApassengerAddSSRDetails[$addSSRkey]);
			}
			foreach ($_ApassengerSSRDetails as $removeSSRkey => $removeSSRval)
			{
				if(empty($_ApassengerSSRDetails[$removeSSRkey]['via']) || $_ApassengerSSRDetails[$removeSSRkey]['via']=="EMPTY")
					unset($_ApassengerSSRDetails[$removeSSRkey]);
			}
		}
		$this->_OairlineService->__construct();
		$this->_OairlineService->_Oconnection = $this->_Oconnection;
		$this->_OairlineService->_IrequestMasterId=$this->_IrequestMasterId;
		$this->_OairlineService->_SserviceName='UpdateSSRService';
		$_AuserCurrency = $this->_Ocommon->_getUserCurrency($this->_IrequestMasterId);
		
		if(!empty($_ApassengerAddSSRDetails) || !empty($_ApassengerSSRDetails) || !empty($_AondSSR))
		{
			/*******Insert the takecontrol details for SSR*****/
			$this->_processingTakeControlSSR($this->_IinputData['requestMasterId'],'insert',$this->_IssrMasterId);
			if(!empty($_ApassengerSSRDetails))
				$this->_AformValues['passengerSSRDetails']= $_ApassengerSSRDetails;
			$this->_AformValues['passengerUpdateSSRDetails']= $_ApassengerAddSSRDetails;
			if(!empty($_AondSSR))
				$this->_AformValues['ONDupdateSSR']= $_AondSSR;
			$this->_AformValues['receivedFrom']= $this->_OairlineService->_setReceivedFrom();
			$this->_AformValues['currency'] = $_AuserCurrency['user_currency'];
			$this->_OairlineService->_AformValues=$this->_AformValues;
			$_AupdateSSRResponse = $this->_OairlineService->_updateSSR();

			if(isset($_AupdateSSRResponse['responseCode']) && $_AupdateSSRResponse['responseCode']==0)
			{
				//Proceed to check the ssr for each passenger and update the ssr and payment details for the PNR
				if(!empty($_AssrDetails))
				{
					fileRequire("classes/class.ssrManipulation.php");
					$_OssrManipulation = new ssrManipulation();
					foreach ($_AssrDetails as $cancelSSRkey => $cancelSSRval)
					{
						$_AcancelSsrPaxId=array();
						$_AcancelSsrCode=array();
						$_OssrManipulation->_Oconnection = $this->_Oconnection;
						$_OssrManipulation->_IrequestMasterId = $this->_IrequestMasterId;
						$_OssrManipulation->_Spnr = $this->_Spnr;
						$_AcancelSsrPaxId[]=$cancelSSRval['ssr_pax_id'];
						$_AcancelSsrCode[]=$cancelSSRval['ssr_code'];
						$_OssrManipulation->_cancelSsr($_AcancelSsrPaxId,$cancelSSRval['ssr_master_id'],$_AcancelSsrCode);
					}
					//Reecalcualting the ssr amount after cancelled ssr
					$_OssrManipulation->_updateSsrAmount($cancelSSRval['ssr_master_id']);
				}
				$this->_updateSSRDetailsStatus();
				/**Update the status as complete for takecontrol details table if the response get success form service**/
				if(!isset($this->_IinputData['instantPayment']) || ($this->_IinputData['instantPayment'] && $this->_IinputData['instantPayment'] == 'Y'))
					$this->_processingTakeControlSSR($this->_IrequestMasterId,'update','','Completed');
			}
			else
			{
				//Incase of failure response, return the response to user 
				$this->_OssrMaster->__construct();
				$this->_OssrMaster->_Oconnection = $this->_Oconnection;
				$this->_OssrMaster->_IssrMasterId = $this->_IssrMasterId;
				$this->_OssrMaster->_Sstatus = 'ERROR';
				$this->_OssrMaster->_updateSsrMaster();
				
				$this->_OssrDetails->__construct();
				$this->_OssrDetails->_Oconnection = $this->_Oconnection;
				$this->_OssrDetails->_IssrMasterId = $this->_IssrMasterId;
				$this->_OssrDetails->_SssrStatus = 'ERROR';
				$this->_OssrDetails->_updateSsrDetails();
				
				if(isset($this->_IinputData['instantPayment']) && $this->_IinputData['instantPayment'] == 'Y')
				{
					fileRequire("classesTpl/class.tpl.submitInstantPaymentRequestTpl.php");
					$this->_OsubmitInstantPaymentRequestTpl=new submitInstantPaymentRequestTpl();
					$this->_OsubmitInstantPaymentRequestTpl->_Osmarty = $this->_Osmarty;
					$this->_OsubmitInstantPaymentRequestTpl->_OobjResponse = $this->_OobjResponse;
					$this->_OsubmitInstantPaymentRequestTpl->_Oconnection = $this->_Oconnection;
					$this->_OsubmitInstantPaymentRequestTpl->_IpaymentMasterId = $this->_IpaymentMasterId;
					$this->_OsubmitInstantPaymentRequestTpl->_IpnrPaymentId = $this->_IpnrPaymentId;
					$this->_OsubmitInstantPaymentRequestTpl->_ICCPayableAmount =0;
					$this->_OsubmitInstantPaymentRequestTpl->_updateInstantPaymentDetails();
				}

				/**Update the status as complete for takecontrol details table if the response get success form service**/
				if(!isset($this->_IinputData['instantPayment']) || ($this->_IinputData['instantPayment'] && $this->_IinputData['instantPayment'] == 'Y'))
					$this->_processingTakeControlSSR($this->_IrequestMasterId,'update','','Error');
				
				if(isset($_AupdateSSRResponse['response']) && $_AupdateSSRResponse['response']!="")
					$this->_OobjResponse->call("commonObj.showErrorMessage",$_AupdateSSRResponse['response']);
				else
					$this->_OobjResponse->script("errorMessages('','".$this->_Osmarty->getConfigVars('COMMON_SERVICE_PROBLEM_TRY_AGAIN_LATER')."');");
				return false;
			}
		}
	}
	
	/*
	 * Funtion name : _getInstantPaymentFormValues
	 * Param        : pnr,request master id
	 * Description  : This function returns the required formvalues to send to instant payment modules
	 * return 		: Array of formvalues
	 */
	
	function _getInstantPaymentFormValues($_Spnr = '',$_IssrMasterId = 0,$_AssrPaxId = array())
	{
		global $CFG;
		$_AformValues = array();
		$_IssrAmount = 0;
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$_AuserCurrency = $this->_Ocommon->_getUserCurrency($this->_IrequestMasterId);
		$_AformValues['request']['PNR'] = $_Spnr;
		$_AformValues['request']['receivedFrom'] =  $this->_OairlineService->_setReceivedFrom();
		$_AformValues['request']['currencyCode'] = $_AuserCurrency['user_currency'];
		$_AformValues['request']['paymentMasterId'] = $this->_IpaymentMasterId;
		$_AformValues['request']['pnrPaymentId'] = $this->_IpnrPaymentId;
		if($_IssrMasterId==0)
			return $_AformValues;
		$_SssrmasterId = "SELECT 
								ssr_master_id,
								status
							FROM 
								".$CFG['db']['tbl']['ssr_master']." 
							WHERE 
								ssr_master_id = ".$_IssrMasterId;
		
		if(DB::isError($_Rssr=$this->_Oconnection->query($_SssrmasterId)))
		{
			fileWrite($_SssrmasterId,"SqlError","a+");
			return false;
		}
		if($_Rssr->numRows() > 0)
		{
			$rowSsr=$_Rssr->fetchRow(DB_FETCHMODE_ASSOC);
			$_IssrMasterId = $rowSsr['ssr_master_id'];
			if(strtoupper($rowSsr['status'])!='COMPLETED')
				return $_AformValues;
		}
		if($_IssrMasterId)
		{
			$sql = "SELECT 
						spd.pax_reference_id,
						spd.ssr_pax_id,
						spg.ssr_id,
						spg.ssr_weight,
						sd.ssr_code,
						sd.ssr_total_fare,
						sd.ssr_details_id,
						sm.ssr_amount
					FROM 
						".$CFG['db']['tbl']['ssr_master']." sm,
						".$CFG['db']['tbl']['ssr_details']." sd,
						".$CFG['db']['tbl']['ssr_pax_details']." spd,
						".$CFG['db']['tbl']['ssr_pax_grouping']." spg
					WHERE
						sm.ssr_master_id = sd.ssr_master_id AND
						sd.ssr_pax_id = spd.ssr_pax_id AND
						sd.ssr_details_id = spg.ssr_details_id AND
						sd.remarks = '' AND
						sm.ssr_master_id = ".$_IssrMasterId;
					if(DB::isError($result=$this->_Oconnection->query($sql)))
					{
						fileWrite($sql,"SqlError","a+");
						return false;
					}
					if($result->numRows() > 0)
					{
						while($row=$result->fetchRow(DB_FETCHMODE_ASSOC))
						{
							$_AtempSSRDetails=array();
							$_AtempSSRDetails['paxNumber']=$row['pax_reference_id'];
							$_AtempSSRDetails['SSRCode']=$row['ssr_code'];
							if(strtoupper($this->_IinputData['typeOfSSR']) == 'SEAT')
							{
								$_AtempSSRDetails['SSRCode'] = $CFG['SSR']['hideLink']['seat']['ssrCode'];
								$_AtempSSRDetails['seatNo'] = $row['ssr_code'];
							}
							if(!empty($_AssrPaxId))
								$_AtempSSRDetails['paxNumber'] = json_decode($this->_IinputData['SSRPaxRef'],1)[$_AtempSSRDetails['paxNumber']];
							if(isset($CFG["ssr"]["skipSSRPolicy"]) && ($CFG["ssr"]["skipSSRPolicy"]["status"]=='Y' || $CFG["ssr"]["skipSSRPolicy"]["applyUniqueSsrCode"] =='Y'))
								$_AtempSSRDetails['SSRCode']=substr($row['ssr_code'],0,strpos($row['ssr_code'],'_'));
							$_AtempSSRDetails['id']=$row['ssr_id'];
							$_AtempSSRDetails['additionalInfo']['pieceOrWeight']=$row['ssr_weight'];
							$_AtempSSRDetails['Amount']=$this->_Ocommon->_getRoundOffFare($row['ssr_total_fare'],'',$_AuserCurrency['user_currency']);
							$_AtempSSRDetails['requestedAmount']=$this->_Ocommon->_getRoundOffFare($row['ssr_total_fare'],'',$_AuserCurrency['user_currency']);
							$_AtempSSRDetails['ssrPaxId']=$row['ssr_pax_id'];
							$_AtempSSRDetails['ssrDetailsId']=$row['ssr_details_id'];
							$_AtempSSRDetails['ssrMasterId']=$_IssrMasterId;
							$_AformValues['request']['SSR'][]=$_AtempSSRDetails;
							$_IssrAmount = $row['ssr_amount'];
						}
					}
			$_AformValues['request']['paymentAmount'] = $_IssrAmount;
		}
		return $_AformValues;
	}

	/*
	 * Funtion name : _cancelSsr
	 * Param        : ssr_id (Need to assign the pnr and requestmasterid in this class object)
	 * Description  : This function will call update ssr service for cancelling the ssr
	 * return 		: Boolean
	 */
	function _cancelSsr($_AssrId = array(),$_SemdCancel='N')
	{
		global $CFG;
		
		if(empty($_AssrId))
			return false;
		$this->_AformValues = array();
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$this->_Ocommon->_IinputData = $this->_IinputData;
		$_AuserCurrency = $this->_Ocommon->_getUserCurrency($this->_IrequestMasterId);
		$this->_AformValues['flightSegmentDetails'] = $this->_prepareServiceFormValues();
		$this->_AformValues['PNR'] = $this->_Spnr;
		$this->_AformValues['receivedFrom']= $this->_OairlineService->_setReceivedFrom();
		$this->_AformValues['currency'] = $_AuserCurrency['user_currency'];
		$_AssrDetails = $this->_getCancelSsrDetails($_AssrId);
		$_ApassengerSSRDetails = array();
		$_AflightNumberKeys=array();
		$_SondSSRstatus='N';
		if(isset($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['ONDLevelSSR']=='Y')
			$_SondSSRstatus='Y';
		foreach($this->_AformValues['flightSegmentDetails'] AS $mainSectorKey => $mainSectorValue)
		{
			$_ApassengerSSRDetails[$mainSectorKey] = array();
			$_Iindex = 0;
			$_SreferencyKey='';
			foreach($mainSectorValue['viaFlightDetails'] AS $viaFlightKey => $viaFlightValue)
			{
				if(isset($_AssrDetails['SSR'][$viaFlightValue['pnrBlockingId']][$viaFlightValue['viaFlightId']]))
				{
					foreach($_AssrDetails['SSR'][$viaFlightValue['pnrBlockingId']][$viaFlightValue['viaFlightId']] AS $key => $value)
					{
						$_ApassengerSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$_Iindex]['SSRCode'] = $value['SSRCode'];
						if(isset($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['status']=='Y')
						{
							$_ApassengerSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$_Iindex]['SSRCode']=substr($value['SSRCode'],0,strpos($value['SSRCode'],'_'));
						}
						$_ApassengerSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$_Iindex]['paxNum'] = $value['paxNum'];
						$_ApassengerSSRDetails[$mainSectorKey]['via'][$viaFlightKey][$_Iindex]['id'] = $value['id'];
						$_Iindex++;
					}
				}
				if($_SreferencyKey!='')
					$_SreferencyKey.="/";
				$_SreferencyKey.=str_replace("-","",$viaFlightValue['departureDate'])." ".$viaFlightValue['carrierCode']." ".$viaFlightValue['flightNumber']." ".$viaFlightValue['origin'].$viaFlightValue['destination'];
				if($_SondSSRstatus=='Y')
				{
					$_AflightNumberKeys[$mainSectorKey]['flightReferenceKey']=$_SreferencyKey;
					$_AflightNumberKeys[$mainSectorKey]['flights'][$viaFlightKey]['origin']=$viaFlightValue['origin'];
					$_AflightNumberKeys[$mainSectorKey]['flights'][$viaFlightKey]['destination']=$viaFlightValue['destination'];
					$_AflightNumberKeys[$mainSectorKey]['flights'][$viaFlightKey]['departureDateAndTime']=$viaFlightValue['departureDateAndTime'];
					$_AflightNumberKeys[$mainSectorKey]['flights'][$viaFlightKey]['arrivalDateAndTime']=$viaFlightValue['arrivalDateAndTime'];
					$_AflightNumberKeys[$mainSectorKey]['flights'][$viaFlightKey]['flightNumber']=$viaFlightValue['flightNumber'];
					$_AflightNumberKeys[$mainSectorKey]['flights'][$viaFlightKey]['class']=$viaFlightValue['class'];
					$_AflightNumberKeys[$mainSectorKey]['flights'][$viaFlightKey]['marketingCarrierCode']=$viaFlightValue['marketingCarrierCode'];
					$_AflightNumberKeys[$mainSectorKey]['flights'][$viaFlightKey]['operatingCarrierCode']=$viaFlightValue['operatingCarrierCode'];
				}
			}
		}
		$_AtempOndDetails=array();
		if($_SondSSRstatus=='Y')
		{
			$this->_OairlineService->__construct();
			$this->_OairlineService->_Oconnection = $this->_Oconnection;
			$this->_OairlineService->_Spnr = $this->_Spnr;
			$this->_OairlineService->_StypeOfSsr = 'SSR';
			$this->_OairlineService->_IrequestMasterId = $this->_IrequestMasterId;
			$_AgetSSRDetailsForPNR = $this->_OairlineService->_getSSRDetailsForPNR();
			$_AgetSSRDetailsForPNR=$_AgetSSRDetailsForPNR['response'];
			foreach ($_ApassengerSSRDetails as $_IremoveKey => $_AremoveVal)
			{
				foreach ($_AremoveVal['via'] as $viaKey => $viaVal)
				{
					foreach ($viaVal as $_IssrKey => $_AssrVal)
					{
						$paxKey = array_search($_AssrVal['paxNum'], array_column($_AgetSSRDetailsForPNR['paxSSR'], 'nameId'));
						$_AremovedSSRKey = array();
						foreach ($_AgetSSRDetailsForPNR['paxSSR'][$paxKey]['AncillaryServices'] as $_IserviceKey => $serviceVal)
						{
							if($serviceVal['FlightReference']==$_AflightNumberKeys[$_IremoveKey]['flightReferenceKey'] && !in_array($_AssrVal['id'],$_AremovedSSRKey))
							{
								$_AtempOndDetails[$_IremoveKey]['flights']=$_AflightNumberKeys[$_IremoveKey]['flights'];
								$_AtempOndDetails[$_IremoveKey]['passengerSSRDetails'][]['id']=$_AssrVal['id'];
								$_AremovedSSRKey[] = $_AssrVal['id'];
							}
						}
					}
				}
				if(!empty($_AtempOndDetails) && isset($_AtempOndDetails[$_IremoveKey]) && !empty($_AtempOndDetails[$_IremoveKey]))
					$_ApassengerSSRDetails[$_IremoveKey]['via']=array();
			}
			foreach ($_ApassengerSSRDetails as $removeSSRkey => $removeSSRval)
			{
				if(empty($removeSSRval['via']))
					unset($_ApassengerSSRDetails[$removeSSRkey]);
			}
		}
		$this->_AformValues['passengerSSRDetails'] = $_ApassengerSSRDetails;
		if(!empty($_AtempOndDetails) && $_SondSSRstatus=='Y')
			$this->_AformValues['ONDupdateSSR'] = $_AtempOndDetails;
		$this->_OairlineService->__construct();
		$this->_OairlineService->_Oconnection = $this->_Oconnection;
		$this->_OairlineService->_IrequestMasterId=$this->_IrequestMasterId;
		$this->_OairlineService->_SserviceName='UpdateSSRService';
		$this->_AformValues['receivedFrom']= $this->_OairlineService->_setReceivedFrom();
		$this->_OairlineService->_AformValues=$this->_AformValues;
		$_AupdateSSRResponse = $this->_OairlineService->_updateSSR();
		if(isset($_AupdateSSRResponse['responseCode']) && $_AupdateSSRResponse['responseCode']==0)
		{
			if($_SemdCancel=='Y')
				return true;
			//Updating the ssr status for cancelled ssr
			fileRequire("classes/class.ssrManipulation.php");
			$_OssrManipulation = new ssrManipulation();
			$_OssrManipulation->_Oconnection = $this->_Oconnection;
			$_OssrManipulation->_IrequestMasterId = $this->_IrequestMasterId;
			$_OssrManipulation->_Spnr = $this->_Spnr;
			$_IssrMasterId = $_OssrManipulation->_cancelSsr($_AssrDetails['ssrPaxId'],$_AssrDetails['ssrMasterId'],$_AssrDetails['ssrCode']);
			//Reecalcualting the ssr amount after caancelled ssr
			$_OssrManipulation->_updateSsrAmount($_IssrMasterId);
			return true;
		}
		else
		{
			return $_AupdateSSRResponse['responseCode']; 
		}
	}

	/*
	 * Funtion name : _getCancelSsrDetails
	 * Param        : ssr_details_id
	 * Description  : This function will return the ssr details related to given ssr_id
	 * return 		: Array of ssr details
	 */
	
	function _getCancelSsrDetails($_AssrId = array())
	{
		global $CFG;
		
		$_SssrId = implode(',',$_AssrId);
		$_AssrDetails = array();
		$_AssrPaxId = array();
		$_IssrMasterId = 0;
		$sql = "SELECT 
					sd.ssr_pax_id,
					sd.ssr_master_id,
					sd.ssr_code,
					spd.pax_reference_id,
					spg.ssr_id,
					spd.via_flight_id,
					spd.pnr_blocking_id
				FROM 
					".$CFG['db']['tbl']['ssr_details']." sd,
					".$CFG['db']['tbl']['ssr_pax_details']." spd,
					".$CFG['db']['tbl']['ssr_pax_grouping']." spg
				WHERE
					sd.ssr_pax_id = spd.ssr_pax_id AND
					sd.ssr_details_id = spg.ssr_details_id AND
					spg.ssr_details_id in (".$_SssrId.")";
		if(DB::isError($result=$this->_Oconnection->query($sql)))
		{
			fileWrite($sql,"SqlError","a+");
			return false;
		}
		if($result->numRows() > 0)
		{
			while($row=$result->fetchRow(DB_FETCHMODE_ASSOC))
			{
				$_AssrDetails['SSR'][$row['pnr_blocking_id']][$row['via_flight_id']][$row['ssr_id']]['SSRCode'] = $row['ssr_code'];
				$_AssrDetails['SSR'][$row['pnr_blocking_id']][$row['via_flight_id']][$row['ssr_id']]['paxNum'] = $row['pax_reference_id'];
				$_AssrDetails['SSR'][$row['pnr_blocking_id']][$row['via_flight_id']][$row['ssr_id']]['id'] = $row['ssr_id'];
				$_AssrDetails['SSR'][$row['pnr_blocking_id']][$row['via_flight_id']][$row['ssr_id']]['viaFlightId'] = $row['via_flight_id'];
				$_AssrDetails['SSR'][$row['pnr_blocking_id']][$row['via_flight_id']][$row['ssr_id']]['pnrBlockingId'] = $row['pnr_blocking_id'];
				$_AssrPaxId[] = $row['ssr_pax_id'];
				$_AssrCode[] = $row['ssr_code'];
				if(!$_IssrMasterId)
					$_IssrMasterId = $row['ssr_master_id'];
			}
		}
		$_AssrDetails['ssrPaxId'] = $_AssrPaxId;
		$_AssrDetails['ssrCode'] = $_AssrCode;
		$_AssrDetails['ssrMasterId'] = $_IssrMasterId;
		return $_AssrDetails;
	}
	
	function _getDirectcodeFromService($_ARequestDetails = array(),$_AmatrixValue = array())
	{
		global $CFG;
		$_AflightRequestDetails = array();
		if(!isset($_ARequestDetails[0]))
			$_AflightRequestDetails[0]=$_ARequestDetails;
		$_SondSSRstatus='N';
		if(isset($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['ONDLevelSSR']=='Y')
			$_SondSSRstatus='Y';
		/* Based on config shows zero fare ssr */
		$_SzeroFareAncilary='N';
		if(isset($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]['zeroFareAncilary']=='Y')
			$_SzeroFareAncilary='Y';
		$_AdefaultSSRCategory = $CFG["ssr"]["skipSSRPolicy"]["category"];
		$this->_Acategory = array("meals","baggage","others");
		if($_AflightRequestDetails[0]['stops']>0 && empty($_AmatrixValue))
		{
			$this->_OviaFlightDetails->__construct();
			$this->_OviaFlightDetails->_IrequestApprovedFlightId = $_AflightRequestDetails[0]['request_approved_flight_id'];
			$_AflightRequestDetails = $this->_OviaFlightDetails->_selectViaFlightDetails();
			foreach ($_AflightRequestDetails as $_Ikey => &$_Avalue) {
				$_Avalue['departureDate'] = $_Avalue['departure_date'];
				$_Avalue['airlineCode'] = $_Avalue['airline_code'];
				$_Avalue['flightNumber'] = $_Avalue['flight_number'];
				$_Avalue['source'] = $_Avalue['origin'];
			}
		}
		$_AssrCodeDetails = array();
		foreach($this->_AserviceSSRDetails as $key => $value)
		{
			foreach ($_AflightRequestDetails as $tripIndex => $tripValue) {
				#set reference key
				$_Sindex = str_replace("-","",$tripValue['departureDate']).$tripValue['airlineCode'].$tripValue['flightNumber'].$tripValue['source'].$tripValue['destination'];
				if(empty($this->_AssrListPolicyValues[$_IreferenceKey]))
				{
					$_AssrCategoryDetails = $this->_Ocommon->_getSSRListDetails($tripValue['departureDate'],'Y');
				}
				if(!isset($this->_AssrListPolicyValues[$_Sindex]))
				{
					foreach($value['viaFlightDetails'] as $viaKey => $viaValue)
					{
						if(($tripValue['departureDate'] == $viaValue['departureDate']) && ($tripValue['source'] == $viaValue['origin']) && ($tripValue['destination'] == $viaValue['destination']) && ($tripValue['flightNumber'] == $viaValue['flightNumber']))
						{
							if(!isset($_AssrCodeDetails[$_Sindex]))
								$_AssrCodeDetails[$_Sindex] = array_unique(array_column($viaValue['SSRDetails'],'SSRCode'));
							/* check ond SSR available and take OND SSR for the flight*/
							$_AssrDetails=$viaValue['SSRDetails'];
							if(!empty($this->_AondSsrDetails) && $_SondSSRstatus=='Y')
							{
								foreach ($this->_AondSsrDetails as $ondKey => $ondVal)
								{
									$_AcombinedSSR=array_column($ondVal['flights'], 'flightNumber');
									if(in_array($viaValue['flightNumber'],$_AcombinedSSR))
									{
										$_AssrDetails = $ondVal['SSRDetails'];
									}
								}
							}
							foreach($_AssrDetails as $ssrKey => $ssrValue)
							{
								$_SinfantFlag = 'N';
								if(isset($ssrValue['totalPrice']) && $ssrValue['totalPrice']<=0 && $_SzeroFareAncilary=='N')
									continue;
								$_SssrCode = $ssrValue['SSRCode'];
								if($_SssrCode=="INFT" && in_array('INFB',$_AssrCodeDetails[$_Sindex]) && !in_array($_SssrCode,array_keys($_AmatrixValue)))
									$_SinfantFlag = 'Y';
								if(isset($CFG["ssr"]["skipSSRPolicy"]) && $CFG["ssr"]["skipSSRPolicy"]["status"] == 'N' && (isset($_AssrCategoryDetails[$ssrValue['SSRCode']]) || $_SinfantFlag=='Y'))
								{
									if(!empty($_AmatrixValue) && $_SinfantFlag=='N')
									{
										$_AssrFromMatrix = array_keys($_AmatrixValue);
										if(!in_array($_SssrCode,$_AssrFromMatrix))
										{
											continue;
										}
										if($ssrValue['SSRType']=='')
												$ssrValue['SSRType'] = $_AdefaultSSRCategory[$_AmatrixValue[$_SssrCode]['ssr_category_name']];
										$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_description'] =  $_AmatrixValue[$_SssrCode]['ssr_description'];
									}
									else
									{
										if(isset($CFG["ssr"]["skipSSRPolicy"]["showSSRList"]) && $CFG["ssr"]["skipSSRPolicy"]["showSSRList"]=="N")
											continue;
										if($ssrValue['SSRType']=='')
											$ssrValue['SSRType'] = $_AdefaultSSRCategory[$_AssrCategoryDetails[$_SssrCode]['ssr_category_name']];
										$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_description'] =  $_AssrCategoryDetails[$_SssrCode]['ssr_description'];
									}
									$_SssrType = $ssrValue['SSRType'];
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_code'] = $_SssrCode;
									//$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_description'] = $ssrValue['SSRName'];
									$ssrCategoryId = 3;
									$ssrSubCategoryId = 4;
									$ssrCategoryName = 'Others';
									if(in_array($_SssrType,$_AdefaultSSRCategory))
									{
										$ssrCategoryName = array_keys($_AdefaultSSRCategory,$_SssrType)[0];
									}
									if($_SssrType == 'ML')
									{
										$ssrCategoryId = 1;
										$ssrSubCategoryId = 1;
										
									}
									else if($_SssrType == 'BG')
									{
										$ssrCategoryId = 2;
									}
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_type'] = $_SssrType;
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_subcategory_id'] = $ssrSubCategoryId;
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_category_name'] = $ssrCategoryName;
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_name'] = $ssrCategoryName;
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['additional_info'] = $ssrValue['AddtionalInfo'];
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['additional_info']['SSRVendor'] = $ssrValue['SSRVendor'];
									/* Set the INFT SSR in ssrlist if that SSR is not mapped in policy*/
									if($_SssrCode=="INFT")
									{
										if($_SinfantFlag=='Y')
											$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['infantStatus'] = 'N';
										else
											$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['infantStatus'] = 'Y';
									}
								}
								if(isset($CFG["ssr"]["skipSSRPolicy"]) && ($CFG["ssr"]["skipSSRPolicy"]["status"] == 'Y' || $CFG["ssr"]["skipSSRPolicy"]["applyUniqueSsrCode"] =='Y'))
								{
									$_SssrCode = $ssrValue['SSRCode'].'_'.str_replace(' ', '_', strtoupper($ssrValue['SSRName']));

									$_SssrType = $ssrValue['SSRType'];
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_code'] = $_SssrCode;
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_description'] = $ssrValue['SSRName'];
									$ssrCategoryId = 3;
									$ssrSubCategoryId = 4;
									$ssrCategoryName = 'Others';
									if(in_array($_SssrType,$_AdefaultSSRCategory))
									{
										$ssrCategoryName = array_keys($_AdefaultSSRCategory,$_SssrType)[0];
									}
									if($_SssrType == 'ML')
									{
										$ssrCategoryId = 1;
										$ssrSubCategoryId = 1;
										
									}
									else if($_SssrType == 'BG')
									{
										$ssrCategoryId = 2;
									}
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_type'] = $_SssrType;
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_subcategory_id'] = $ssrSubCategoryId;
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_category_name'] = $ssrCategoryName;
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['ssr_name'] = $ssrCategoryName;
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['additional_info'] = $ssrValue['AddtionalInfo'];
									$this->_AssrListPolicyValues[$_Sindex][$_SssrCode]['additional_info']['SSRVendor'] = $ssrValue['SSRVendor'];
								}
							}
						}
					}
				}
			}
		}
	}
	/*
     * Desc : Function used to checking the validity for ssr expiry
     * Param : request approved flight id , ssr type
     * Return : Disable status | String
     * Author : Dilli Raj P
     * Created on : 24-Mar-2020
    **/
	public function _getExpiryTime($_IrequestApprovedFlightId,$_SssrType,$_ScancelSSR='N',$_SgetExpiryDate='N')
	{
		global $CFG;
		if(!isset($CFG['site']['contractManager']) || (isset($CFG['site']['contractManager']) && $CFG['site']['contractManager']['status'] == 'N'))
			return 'N';
		$_StableName = $CFG['db']['tbl']['request_approved_flight_details'].' rafd 
						INNER JOIN 
						'.$CFG['db']['tbl']['series_request_details'].' srd 
						ON 
						rafd.series_request_id = srd.series_request_id';
		$_AselectField = array(
							'srd.series_request_id',
							'srd.series_group_id',
							'rafd.departure_date',
							'rafd.dep_time'
		);
		$_AconditionValue = array(
							'rafd.request_approved_flight_id' => $_IrequestApprovedFlightId
		);
		$_ArequestDetails = $this->_Oconnection->_performJoinQuery($_StableName,$_AselectField,$_AconditionValue);
		$_IrequestMasterId = $this->_IrequestMasterId;
		fileRequire('classes/class.contractManager.php');
		$_OcontractManager = new contractManager();
		$_OcontractManager->_Oconnection = $this->_Oconnection;
		$_AcontractDetails = $_OcontractManager->_getSpecifiedValues($_IrequestMasterId,'ssr_validation_details',$_ArequestDetails[0]['series_group_id']);
		$_AfareValidity = $_OcontractManager->_getFareValidity();
		$_Avalidity = array_column($_AfareValidity,'fare_validity_values','fare_validity_type_id');
		$_IseriesGroupId = $_ArequestDetails[0]['series_group_id'];
		$_DdepartureDate = $_ArequestDetails[0]['departure_date'].' '.$_ArequestDetails[0]['dep_time'].':00';
		if($_ScancelSSR=='Y')
		{
			$_SselectedValue = $_AcontractDetails[$_IseriesGroupId]['cancelSsrCategoryVal_'.ucwords($_SssrType)];
			$_SselectedValue = $_Avalidity[$_SselectedValue];
			$_SselectedInputValue = $_AcontractDetails[$_IseriesGroupId]['cancelSsrCategoryInput_'.ucwords($_SssrType)];
		}
		else
		{
			$_SselectedValue = $_AcontractDetails[$_IseriesGroupId]['ssrCategoryVal_'.ucwords($_SssrType)];

			$_SselectedValue = $_Avalidity[$_SselectedValue];
			$_SselectedInputValue = $_AcontractDetails[$_IseriesGroupId]['ssrCategoryInput_'.ucwords($_SssrType)];
		}
		$_DcalculteDepart = date('Y-m-d H:i:s', strtotime($_DdepartureDate.'-'.$_SselectedInputValue.' '.$_SselectedValue));
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$_SfirstAirportCode = $this->_Ocommon->_getFirstOrigin($_IrequestMasterId);
		$_DoriginCurrentDate = $this->_Ocommon->_getAirportCodeCurrentTime($_SfirstAirportCode);
		if($_SgetExpiryDate=='Y')
			return $_DcalculteDepart;
		$_SdisableStatus = 'N';
		if(strtotime($_DoriginCurrentDate) > strtotime($_DcalculteDepart))
			$_SdisableStatus = 'Y';
		
		return $_SdisableStatus;
	}
	/*
	 * Desc 	: Function used to fetch the DB details
	 * Param*/
	public function _getSSRDetailsFromDB($_Spnr,$_IrequestMasterId=0,$_SseatStatus='N')
	{
		global $CFG;

		$_AreturnResponse = array();
		if($_Spnr == '' || $_IrequestMasterId == 0)
			return $_AreturnResponse;
		//Get the last ssr master id
		$_IlastSsrMasterId = $this->_getLastSsrMasterId($_IrequestMasterId);
		
		//if($_IlastSsrMasterId > 0)
		{
			$_StableName = $CFG['db']['tbl']['ssr_master'].' sm 
							INNER JOIN 
							'.$CFG['db']['tbl']['ssr_details'].' sd 
							ON 
							sd.ssr_master_id = sm.ssr_master_id 
							INNER JOIN
							'.$CFG['db']['tbl']['ssr_pax_details'].' spd
							ON 
							spd.ssr_pax_id = sd.ssr_pax_id';
			$_AselectField = array("sd.ssr_code","sd.ssr_details_id","sm.ssr_master_id","sd.emd_id","sd.ssr_total_fare","spd.pax_reference_id,spd.pnr_blocking_id,spd.ssr_pax_id");
			$_AconditionValue = array(
				'sm.pnr' => $_Spnr,
				'sm.request_master_id' => $_IrequestMasterId,
				'sm.last_transaction' => 'Y',
				'sd.ssr_status' => 'COMPLETED'
			);
			$_AconditionValue['sm.ssr_category_id'] = array('condition' => "NOT IN",
			'value' => array("4"));
			if($_SseatStatus=='Y')
			{
				$_AconditionValue['sm.ssr_category_id'] = array('condition' => "IN",
				'value' => array("4"));
			}
			$_AssrDetailsPaxLevel = $this->_Oconnection->_performJoinQuery($_StableName,$_AselectField,$_AconditionValue);
			if(!empty($_AssrDetailsPaxLevel))
			{
				foreach ($_AssrDetailsPaxLevel as $key => $value) {
					$_AreturnResponse[$value['pnr_blocking_id']][$value['pax_reference_id']][] = $value;
				}
			}
		}
		return $_AreturnResponse;
	}
	/*
	 * Desc 	: Function to get the last ssr master id
	 * Param 	: $_IrequestMasterId
	 */
	public function _getLastSsrMasterId($_IrequestMasterId = 0, $_Spnr = '')
	{
		global $CFG;
		$_IlastSsrMasterId = 0;
		if($_IrequestMasterId > 0)
		{
			$_AlastSsrMasterId = $this->_Oconnection->_performJoinQuery($CFG['db']['tbl']['ssr_master'],array("ssr_master_id"),array("request_master_id"=>$_IrequestMasterId , "pnr"=>$_Spnr),'','','ssr_master_id DESC LIMIT 1');
			
			if(is_array($_AlastSsrMasterId) && !empty($_AlastSsrMasterId))
				$_IlastSsrMasterId = $_AlastSsrMasterId[0]['ssr_master_id'];
		}
		return $_IlastSsrMasterId;
	}


	/*
	*@Author       :A.kaviyarasan
	*@Function name:_getSSRInfoToAddWithPax
	*@Arguments    :$_IrequestMasterId(Integer),$_Spnr(String)
	*@Description  :This function is used to  get the ssr informations for updateNames service
	*@Created date :19-06-2020
	*@Return       :Array
	*/

	function _getSSRInfoForUpdateNames($_IrequestMasterId,$_Spnr)
	{
		global $CFG;
		//assigning the needed properties
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$this->_Ocommon->_Osmarty = $this->_Osmarty;
		$this->_Ocommon->_OobjResponse = $this->_OobjResponse;
		$this->_Spnr=$_Spnr;
		$this->_IrequestMasterId=$_IrequestMasterId;
		//getting informations to call availablity 
		$this->_AuserCurrency = $this->_Ocommon->_getUserCurrency($this->_IrequestMasterId);
		$this->_SuserCurrency = $this->_AuserCurrency['user_currency'];
		//Get the SSR availablity list for all the flight in the PNR
		if(!$this->_getSSRAvailabilityList())
			return false;
		$_ASSRForUpdateNames=$this->_prepareSSRListForUpdateNames($this->_AserviceSSRDetails);
		//ssr details from the stored variable 
		return $_ASSRForUpdateNames;
	}

	/*
	*@Author       :A.kaviyarasan
	*@Function name:_prepareSSRListToUpdateNames
	*@Arguments    :$_AserviceSSRDetails-ssr availablity list(Array)
	*@Description  :(This function will check each leg destination country code with country code present in the config $CFG["nameUpdate"]["addSSRInNameUpdate"].if the country code matches means,it will check the triptype(International only) and add the ssr informations present in the same config).
	*@Created date :19-06-2020
	*@Return       :Array
	*/
	function _prepareSSRListForUpdateNames($_AserviceSSRDetails)
	{
		global $CFG;
		$_IperPaxSSRFare=0;
		$_AdestCountries=array();
		$_ASSRForUpdateNames=array();
		foreach($_AserviceSSRDetails as $_IondIndex => $_AondValue)
		{
			foreach ($_AondValue["viaFlightDetails"] as $_IlegIndex => $_AlegValue) 
			{
				$_SdestCountry=$this->_Ocommon->_getAirportDetails($_AlegValue["destination"]);
				//checking the destination to add ssr
				if(in_array($_SdestCountry["country_code"],$CFG["nameUpdate"]["addSSRInUpdateNames"]["ssrInfo"]["countryCode"]))
				{
					//checking orgin and destination is domestic or international
					$_Asector=array($_AlegValue["origin"],$_AlegValue["destination"]);
					//travel type (International-0 or domestic-1)
					$_ItravelType=$this->_Ocommon->_checkSectorSameCountry($_Asector);
					//apply only for international
					if($_ItravelType===0)
					{
						$_AssrDetails=array();
						//looping the ssr list to add
						foreach ($CFG["nameUpdate"]["addSSRInUpdateNames"]["ssrInfo"]["ssrCode"] as $_SssrCode) 
						{
							//searching the ssr code is available for the current flight segment 
							$_IssrIndex=array_search($_SssrCode,array_column($_AlegValue["SSRDetails"],"SSRCode"));
							if($_IssrIndex!==false)
							{
								$_AssrDetails[]=$_AlegValue["SSRDetails"][$_IssrIndex];
								//counting the amount to show
								$_IperPaxSSRFare=$_IperPaxSSRFare+$_AlegValue["SSRDetails"][$_IssrIndex]["totalPrice"];
								$_AdestCountries[]=$_SdestCountry["citizenship_name"];
							}
						}
						//store the filtered SSR details 
						$_AserviceSSRDetails[$_IondIndex]["viaFlightDetails"][$_IlegIndex]["SSRDetails"]=$_AssrDetails;
					}
					else
						$_AserviceSSRDetails[$_IondIndex]["viaFlightDetails"][$_IlegIndex]["SSRDetails"]=array();
				}
				else
					$_AserviceSSRDetails[$_IondIndex]["viaFlightDetails"][$_IlegIndex]["SSRDetails"]=array();
			}	
 		}
		//preparing the final list
		$_ASSRForUpdateNames["flightSegmentDetails"]=$_AserviceSSRDetails;
		$_ASSRForUpdateNames['perPaxSSRFare']=$_IperPaxSSRFare;
		//unique the destinations to remove duplicates
		$_ASSRForUpdateNames["applicableCountries"]=array_unique($_AdestCountries);
		//returing the filtered output
		return $_ASSRForUpdateNames;
	}
	/*
	*@Author       :A.kaviyarasan
	*@Function name:_addSSRToPax
	*@Arguments    :$_AallPassengersInfo-saved passengger info(Array),$_IrequestMasterId(Integer),$_Spnr(String)
	*@Description  :This function will prepare the form values for passenger information and ssr information to call update pax service with ssr info.
	*@Created date :19-06-2020
	*@Return       :Array or Null
	*/
	public function _addSSRToPax($_AallPassengersInfo,$_IrequestMasterId,$_Spnr)
	{
		global $CFG;
		//preparing the ssr details against the passengers 
		$_ApassengerAddSSRDetails = array();
		$_ApassengerSSRDetails = array();
		$_AssrFormValues=array();

		//getting the DNI tax details from Db
		$_AssrDniDetails = $this->_getDNIDetailsFromDb($_Spnr);
		fileWrite("result".print_r($_AssrDniDetails,1),"dni","a+");
		
		//getting the ssr info to add with updateNames service
		$_ASSRForUpdateNames=$this->_getSSRInfoForUpdateNames($_IrequestMasterId,$_Spnr);
		//getting the each passenger ssr list
		$_AgetSSRDetailsForPNR=$this->_getPNRSSRDetails($_IrequestMasterId,$_Spnr,$this->_StypeOfSsr);
		if($_AgetSSRDetailsForPNR['responseCode']==0)
		{
			if(isset($_AgetSSRDetailsForPNR['response']['paxSSR']) && !empty($_AgetSSRDetailsForPNR['response']['paxSSR']))
			{
				//getting the passengers with their ssr list
				$_ApassengerSSR = $_AgetSSRDetailsForPNR['response']['paxSSR'];
				//Main flight segement details for the PNR
				foreach($_ASSRForUpdateNames['flightSegmentDetails'] AS $_ImainSectorKey => $_AmainSectorValue)
				{				
					foreach($_AmainSectorValue['viaFlightDetails'] AS $_IviaFlightKey => $_AviaFlightValue)
					{
						if(count($_AviaFlightValue["SSRDetails"])>0)
						{
							foreach($_AviaFlightValue["SSRDetails"] AS $_SssrIndex => $_AssrValue)
							{
								//looping the passenger details to add ssr
								foreach($_AallPassengersInfo AS $_SpaxIndex => $_ApassengerDetails)
								{
									//checking the country of the passenger.since the country must be present
									if(isset($_ApassengerDetails["documentNationality"]))
									{
										//skippping the same county and adding the ssr code for other countries
										if(!in_array($_ApassengerDetails["documentNationality"],$CFG["nameUpdate"]["addSSRInUpdateNames"]["ssrInfo"]["countryCode"]))
										{
											//Checking the passenger with ssr info from service
											if(isset($_ApassengerSSR[$_SpaxIndex]))
											{
												$_ApassengerSSRDetails=$_ApassengerSSR[$_SpaxIndex];
												$_AaddInfo=array();
												$_AaddInfo["SSRCode"]=$_AssrValue["SSRCode"];
												$_AaddInfo["paxNum"]=$_ApassengerSSRDetails['nameId']?$_ApassengerSSRDetails['nameId']:$_SpaxIndex;
												if(!in_array($_AaddInfo["paxNum"],$_AssrDniDetails))
												{
													//Adding the  ssr code
													$_ApassengerAddSSRDetails[$_ImainSectorKey]['via'][$_IviaFlightKey][] =$_AaddInfo;
												}
											}
										}
									}
								}
							}
						}
						else
						{
							/*Adding the Empty ssr code for fxing live issue 
							#Mantis id:51555
							#Modified by: Ajith Kumar P
							#Modified on: 07-09-2022 */
							$_ApassengerAddSSRDetails[$_ImainSectorKey]['via'][$_IviaFlightKey][] =array();
						}
					}
				}
				//Preparing the service form values
				$_AssrFormValues['flightSegmentDetails'] = $this->_prepareServiceFormValues();
				$_AssrFormValues['PNR'] =$_Spnr;
				$_AssrFormValues['passengerUpdateSSRDetails'] = $_ApassengerAddSSRDetails;
				$_AssrFormValues['passengerSSRDetails'] = $_ApassengerSSRDetails;
				return $_AssrFormValues;
			}
		}
	}

	/*
	*@Author       :A.kaviyarasan
	*@Function name:_getPNRSSRDetails()
	*@Arguments    :$_IrequestMasterId(Integer),$_Spnr(String),$_SssrType(String)
	*@Description  :This function will fetch the passengers info with their ssr info from service
	*@Created date :19-06-2020
	*@Return       :Array or Null
	*/
	function _getPNRSSRDetails($_IrequestMasterId,$_Spnr,$_SssrType)
	{
		//Get the ssr list for the passenger in the pnr
		$this->_OairlineService->__construct();
		$this->_OairlineService->_Spnr = $_Spnr;
		$this->_OairlineService->_StypeOfSsr = $_SssrType;
		$this->_OairlineService->_IrequestMasterId = $_IrequestMasterId;
		return $this->_OairlineService->_getSSRDetailsForPNR();
	}

	/**
     * Desc : Function used to get the SSR Categories from database
     * Return : Category Names | Array
     * Author : Kiruthika S
     * Created on : 19-Aug-2020
    **/

	function _getSSRCategoriesFromDataBase()
	{

		$_StableName = "ssr_category_details scd";
		$_AselectField = array(
							"scd.ssr_category_name",
							"scd.ssr_category_id"
		);
		$_AconditionValue['scd.display_status'] = 'Y'; 
		$_ASSRCategoryList = $this->_Oconnection->_performJoinQuery($_StableName,$_AselectField,$_AconditionValue);
		foreach ($_ASSRCategoryList as $SSRkey => $SSRvalue) {
			$this->_AcategoryName[$SSRvalue['ssr_category_name']] = $this->_Osmarty->getConfigVars('COMMON_'.strtoupper($SSRvalue['ssr_category_name']));
		}
		
		return $this->_AcategoryName;

	}
	/*
	*@Author       :Sathiswaran.N
	*@Function name:_processingTakeControlSSR()
	*@Arguments    :$_AinputArray(Array),$_Sprocess(String)
	*@Description  :This function will fetch,insert the take_control_details for SSR
	*@Created date :01-08-2022
	*@Return       :fetch - boolean,insert - id
	*/
	function _processingTakeControlSSR($_IrequestMasterId,$_Sprocess='select',$_IssrMasterId = '',$_SserviceStatus =''){
		global $CFG;
		$_StableName = $CFG['db']['tbl']['take_control_details'];
		$_AinputArray = array(
			'select'=>array('fieldValue'=>array('opened_by'),
			'conditionValue'=>array(
				'request_master_id' => $_IrequestMasterId,
				'process_type' => 'SSR',
				'control_status' => 'Requested'
			)),
			'insert'=>array('fieldValue'=>array(
				'request_master_id' => $_IrequestMasterId,
				'reference_id' => $_IssrMasterId,
				'process_type' => 'SSR',
				'opened_by' => $_SESSION['groupRM']['groupUserId'],
				'opened_time' => $this->_Ocommon->_getUTCDateValue(),
				'control_status' => 'Requested'
			)),
			'update'=>array('fieldValue'=>array('control_status' => $_SserviceStatus),'conditionValue'=>array('request_master_id' => $_IrequestMasterId,
			'process_type' => 'SSR',
			'opened_by' => $_SESSION['groupRM']['groupUserId'],
			'control_status' => 'Requested'))
		);

		if (!isset($_SESSION['groupRM']['groupUserId']) && $_SESSION['groupRM']['groupUserId'] == '')
			unset($_AinputArray['update']['conditionValue']['opened_by']);

		if (isset($_IssrMasterId) && !empty($_IssrMasterId) && $this->_SssrPaymentCron == 'Y')
			$_AinputArray['update']['conditionValue']['reference_id']=$_IssrMasterId;

		$_AalreadyOpened  = $this->_Oconnection->_performQuery($_StableName,$_AinputArray[$_Sprocess]['fieldValue'],'DB_AUTOQUERY_'.strtoupper($_Sprocess),$_AinputArray[$_Sprocess]['conditionValue']);
		if($_Sprocess == 'select'){
			if(isset($CFG['ssr']['instantPayment']) && $CFG['ssr']['instantPayment']['status'] == 'Y')
				return (!empty($_AalreadyOpened)) ? false : true;
			return (!empty($_AalreadyOpened) && end($_AalreadyOpened)['opened_by'] != $_SESSION['groupRM']['groupUserId']) ? false : true;
		}
	}

	/*
	 * Author      : Ajeesh.T
	 * Created on  : 29-12-2022
	 * Description : To get DNI tax details from Tables ('ssr_master','ssr_details','ssr_pax_details')
	 */
	function _getDNIDetailsFromDb($_Spnr)
	{
		global $CFG;

		$_sqlDNIDetails = "SELECT 
								ssp.pax_reference_id
							FROM 
								".$CFG['db']['tbl']['ssr_master']." ssm, 
								".$CFG['db']['tbl']['ssr_details']." ssd, 
								".$CFG['db']['tbl']['ssr_pax_details']." ssp 
							WHERE 
								ssm.pnr = '".$_Spnr."' AND 
								ssm.ssr_master_id = ssd.ssr_master_id AND 
								ssd.ssr_pax_id = ssp.ssr_pax_id AND 
								ssd.ssr_code ='DNI'";

		if(DB::isError($result=$this->_Oconnection->query($_sqlDNIDetails)))
		{
			fileWrite($_sqlDNIDetails,"SqlError","a+");
			return false;
		}
		while($row = $result->fetchRow(DB_FETCHMODE_ASSOC))
		{
			$_AssrDniDetails[] =  $row['pax_reference_id'];
		}

		return $_AssrDniDetails;

	}
	/* update the cancel SSR and payment details*/
	function _updateCancelSSR($_Salert='N')
	{
		global $CFG;
		$this->_Ocommon->_Oconnection = $this->_Oconnection;
		$this->_Ocommon->_Osmarty = $this->_Osmarty;
		$this->_Ocommon->_OobjResponse = $this->_OobjResponse;
		fileRequire("classes/class.syncAncillary.php");
		$_OsyncAncilary = new syncAncillary();
		$this->_ApassengerSSRList = json_decode($this->_IinputData['passengerSSRList'],true);
		$this->_AformValues['flightSegmentDetails'] = $this->_prepareServiceFormValues();
		$this->_AformValues['PNR'] = $this->_Spnr;
		$this->_setViaFlightStatus($this->_AformValues['flightSegmentDetails']);
		$_AssrDetails = array();
		foreach($this->_AformValues['flightSegmentDetails'] AS $mainSectorKey => $mainSectorValue)
		{
			foreach($mainSectorValue['viaFlightDetails'] AS $viaFlightKey => $viaFlightValue)
			{
				$_SreferenceKey = $this->_generateFlightReferenceKey($viaFlightValue);
				foreach($this->_ApassengerSSRList AS $passengerIndex => $passengerDetails)
				{
					$selectedSSRDetails = $passengerDetails[$_SreferenceKey];
					foreach($selectedSSRDetails AS $ssrCategory => $ssrDetails)
					{
						if(array_key_exists('existing',$ssrDetails))
						{
							$_AexistingSSRCode = array_keys($ssrDetails['existing']);
							$_AnewSsrCode = (isset($ssrDetails['newSSR']) ? array_keys($ssrDetails['newSSR']) : array());
							//Checking if any ssr need to cancel
							$_AdiffSSRCode = array_diff($_AexistingSSRCode,$_AnewSsrCode);
							if(!empty($_AdiffSSRCode)) 
							{
								foreach($_AdiffSSRCode AS $ssrIndex => $ssrCode)
								{
									$selectCanceledSSRdetailsId="SELECT sd.ssr_details_id,
																					sd.ssr_pax_id,
																					sd.ssr_code,
																					sd.ssr_master_id
																FROM ssr_details sd
																INNER JOIN ssr_pax_details spd ON sd.ssr_pax_id = spd.ssr_pax_id
																INNER JOIN ssr_pax_grouping spg 
																ON sd.ssr_details_id=spg.ssr_details_id
																WHERE  spd.pax_reference_id='".$passengerDetails['paxNum']."'
																AND spg.ssr_id='".$ssrDetails['existing'][$ssrCode]['ssrId']."'
																AND spd.pnr_blocking_id=".$viaFlightValue['pnrBlockingId'];
									if(!empty($this->_Ocommon->_executeQuery($selectCanceledSSRdetailsId)[0]))
										$_AssrDetails[] = $this->_Ocommon->_executeQuery($selectCanceledSSRdetailsId)[0]['ssr_details_id'];
								}
							}
						}
					}
				}
			}
		}
		if(!empty($_AssrDetails))
		{
			/*call updateSSR for cancel*/
			$this->_cancelSsr($_AssrDetails,'Y');
			$this->_AformValues = array();
			$_AssrInfoService = $this->_getPNRSSRDetails($this->_IrequestMasterId,$this->_Spnr,'SSR'); 
			$_AssrInfo['SSR'] = $_AssrInfoService['response']['paxSSR'];
			$_OsyncAncilary->_Oconnection = $this->_Oconnection;
			if($_Salert=='Y')
			{
				$_OsyncAncilary->_callAncillarySync($this->_Spnr,$this->_IrequestMasterId,$_AssrInfo,'','N','Y');
				return $this->_OobjResponse->script("commonObj.closeGrmPopup(true);commonObj.showSuccessMessage('".$this->_Osmarty->getConfigVars('VALIDATION_POPUPSSRDETAILS_CANCEL_ANCILLARIES_SUCCESS_MSG')."');");
			}
			else
				$_OsyncAncilary->_callAncillarySync($this->_Spnr,$this->_IrequestMasterId,$_AssrInfo,'','N','N');
		}
		return true;
	}
	/* Get expiry date*/
	function _getSSRExpiryDate($_IrequestMasterId,$_IrequestApprovedFlightId)
	{
		global $CFG;
		$this->_AssrValidityDetails = array();
		fileRequire('classesTpl/class.tpl.contractManagerInterfaceTpl.php');
		$_OcontractManagerTpl = new contractManagerInterfaceTpl();
		$_OcontractManagerTpl->_Oconnection =$this->_Oconnection;
		$this->_IrequestMasterId =$_IrequestMasterId;
		$_Acategories = $_OcontractManagerTpl->_getCategoryDetails();
		foreach ($_Acategories as $key => $value)
		{
			$_SexpiryDate = $this->_getExpiryTime($_IrequestApprovedFlightId,$value['ssr_category_name'],'','Y');
			$this->_AssrValidityDetails[strtolower($value['ssr_category_name'])] = $_SexpiryDate;
		}
		return $this->_AssrValidityDetails;
	}
	function _preSelectedSSRCount($_IrequestMasterId,$_AselectedSSRlist)
	{   
		global $CFG;
		$totalCount =0;
		foreach ($_AselectedSSRlist['Nest'] as $key => $value) 
		{	
			if(isset($value['ssrCode'])) 
			{
				foreach($value['ssrCode'] as $index => $ssrCode) 
				{
					$sqlSSRselectedCount="SELECT
					                          count(*) as totalCount
											   FROM
													".$CFG['db']['tbl']['ssr_details']."
											   WHERE
													ssr_master_id=(SELECT ssr_master_id FROM ".$CFG['db']['tbl']['ssr_master']." sm WHERE sm.request_master_id = ".$this->_IrequestMasterId.") 
													AND ssr_status = 'COMPLETED' AND ssr_code = "."'".$ssrCode."'";
					if(DB::isError($result=$this->_Oconnection->query($sqlSSRselectedCount)))
					{
						fileWrite($sqlSSRselectedCount,'SqlError','a+');
						return false;
					}
					if($result->numRows()>0)
					{
						while($row= $result->fetchRow(DB_FETCHMODE_ASSOC))
							$totalCount += $row['totalCount'];
					}
			}
			}
		}
		return $totalCount; 
	 
	}
	
}
    
?>
