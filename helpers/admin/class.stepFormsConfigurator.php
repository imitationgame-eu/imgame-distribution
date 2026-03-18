<?php
/**
 * stepForms Configuration
 * top-level controller to create/delete experiments
 * @author MartinHall
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/domainSpecific/mySqlObject.php';
include_once $root_path.'/helpers/models/class.experimentModel.php';
include_once $root_path.'/helpers/forms/class.stepFormsHandler.php';
include_once $root_path.'/helpers/debug/class.debugLogger.php';
include_once $root_path.'/helpers/parseJSON.php';




class stepFormsConfigurator {
  private $formControlSelectOptions = array();
  private $formDef;
  private $formName;
  private $formType = -1;
  private $exptId;
  private $jType;
  
  //private $getOperation = false;   // used in error handling - i.e. if cannot read form from db catch error and load default with success flag false
  
  private $userId;
  
  private $stepFormsHandler;

// <editor-fold defaultstate="collapsed" desc=" form configuration JSON functions">
  
  function getContingentTextFromValue($page, $contingentValue) {
    foreach ($page['questions'][0]['options'] as $option) {
      if (intval($option['id']) === intval($contingentValue)) { return $option['label']; }          
    }
    return 'unset';
  }
  
  function buildControlTypeJSON() {
    $jSonRep = "\"controls\":[";
    for ($i=0; $i<count($this->formControlSelectOptions); $i++) {
      if ($i > 0) { $jSonRep.=","; }
      $jSonRep.= JSONparse($this->formControlSelectOptions[$i]['label']);
    }
    $jSonRep.= "],"; 
    return $jSonRep;
  }
  
  function buildFilterQOptionControlJSON($page) {
    $jSonRep = "\"filterQoptions\":[";
	  foreach ($page['filterQuestion']['options'] as $i=>$option) {
		  if ($i > 0) { $jSonRep.=","; }
		  $jSonRep.= JSONparse($option['label']);
	  }
    $jSonRep.= "],";
    return $jSonRep;
  }
  
  function buildEligibilityChoicesJSON() {
    $jSonRep = "\"eligibilityChoices\":[";
    for ($i=0; $i<count($this->formDef['eligibilityQ']['options']); $i++) {
      if ($i > 0) { $jSonRep.=",";}
      $jSonRep.= JSONparse($this->formDef['eligibilityQ']['options'][$i]['label']);
    }
    $jSonRep.= "],";
    return $jSonRep;    
  }
  
  function buildEligibilityControlTypesJSON() {
    $jSonRep = "\"eligibilityControlTypes\":[";
    $jSonRep.= "\"select\",\"radiobutton\",\"slider\",\"continuous slider\",\"checkbox\"";
    $jSonRep.= "],";
    return $jSonRep;
  }
  
  function getControlLabelFromType($qType) {
    return $qType == - 1 ? 'not selected' : $this->formControlSelectOptions[$qType]['label'];
  }
  
  function getStepFormJSON() {
	  $this->formType = $this->formType > -1 ? $this->formType : $this->stepFormsHandler->getFormType();
	  $this->formName = $this->stepFormsHandler->getFormName();
	  $this->formDef = $this->stepFormsHandler->getForm(false);
	  $this->stepFormsHandler->setJType($this->jType);
    return json_encode($this->formDef);
  }
  

// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" form structure modification functions">

//  function getReloadMessage() {
//    // reload forces a repost on client-side to reload the persistent object for rendering in KO-JS
//    $xml = sprintf("<message><messageType>reloadForm</messageType><content>%s</content></message>", "null");
//    return $xml;
//  }
//
//  function AddOption($optionType, $pageNo, $qNo, $optionNo) {
//    $stepFormsHandler = new stepFormsHandler($this->exptId, $this->formType);
//    $stepFormsHandler->addOption($optionType, $pageNo, $qNo, $optionNo);
//    return $this->getReloadMessage();
//  }
//
//  function DelOption($optionType, $pageNo, $qNo, $optionNo) {
//    $stepFormsHandler = new stepFormsHandler($this->exptId, $this->formType);
//    $stepFormsHandler->delOption($optionType, $pageNo, $qNo, $optionNo);
//    return $this->getReloadMessage();
//  }
//
//  function AddQuestion($pageNo, $newQNo) {
//    $stepFormsHandler = new stepFormsHandler($this->exptId, $this->formType);
//    $stepFormsHandler->addQuestion($pageNo, $newQNo);
//    return $this->getReloadMessage();
//  }
//
//  function CloneQuestion($pageNo, $srcQNo) {
//    $stepFormsHandler = new stepFormsHandler($this->exptId, $this->formType);
//    $stepFormsHandler->cloneQuestion($pageNo, $srcQNo);
//    return $this->getReloadMessage();
//  }
//
//  function DelQuestion($pageNo, $delQNo) {
//    $stepFormsHandler = new stepFormsHandler($this->exptId, $this->formType);
//    $stepFormsHandler->delQuestion($pageNo, $delQNo);
//    return $this->getReloadMessage();
//  }
//
//  function AddPage($pageNo) {
//    $stepFormsHandler = new stepFormsHandler($this->exptId, $this->formType);
//    $stepFormsHandler->addPage($pageNo);
//    return $this->getReloadMessage();
//  }
//
//  function ClonePage($srcPageNo) {
//    $stepFormsHandler = new stepFormsHandler($this->exptId, $this->formType);
//    $stepFormsHandler->clonePage($srcPageNo);
//    return $this->getReloadMessage();
//  }
//
//  function DelPage($pageNo) {
//    $stepFormsHandler = new stepFormsHandler($this->exptId, $this->formType);
//    $stepFormsHandler->delPage($pageNo);
//    return $this->getReloadMessage();
//  }
// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" runtime JSON functions">

  function buildSelectorOptionsJSON($options) {
    $jSonRep = "\"selectOptions\":[";
    for ($i=0; $i<count($options); $i++) {
      if ($i > 0) { $jSonRep.=","; }
      $jSonRep.= JSONparse($options[$i]['label']);
    }
    $jSonRep.= "],";
    return $jSonRep;
  }

  function buildFQSelectorOptionsJSON($options) {
    $jSonRep = "\"fqSelectOptions\":[";
    for ($i=0; $i<count($options); $i++) {
      if ($i > 0) { $jSonRep.=","; }
      $jSonRep.= JSONparse($options[$i]['label']);
    }
    $jSonRep.= "],";
    return $jSonRep;
  }
  
  function getFormUID() {
    global $igrtSqli;
    switch ($this->formType) {
      case 0:
      case 1:
      case 2:
      case 3:
      case 4:
      case 5: {
        $tblName = "wt_Step1FormUIDs";
        break;
      }
      case 6:
      case 7:
      case 8:
      case 9:
      case 10:
      case 11: {
        $tblName = "wt_Step2FormUIDs";
        break;
      }
      case 12:
      case 13:
      case 14:
      case 15:
      case 16:
      case 17: {
        $tblName = "wt_Step4FormUIDs";
        break;
      }
    }
    $qry = sprintf("INSERT INTO %s (exptId, formType, currentPageNo) VALUES ('%s', '%s', '%s')", $tblName, $this->exptId, $this->formType, 0);
    $igrtSqli->query($qry);
    return $igrtSqli->insert_id;
  }
  
  function getPageNo($restartUID) {
      //TODO - real restart code
    return 0;
  }
  
  function getStepFormRuntimeJSON($formType, $restartUID, $respId) {
    $stepFormsHandler = new stepFormsHandler(null, $this->exptId, $this->formType);
    $this->formType = $formType;
    $this->formDef = $stepFormsHandler->getForm($formType);
    return $this->buildStepFormRuntimeJSON($restartUID, $respId);
  }
  
  function buildGridJSON($gridRows, $gridColumns) {
    $jSonRep = "\"optionCnt\":\"" . count($gridRows) . "\",";        
    $jSonRep.= "\"options\": [";
    for ($i=0; $i<count($gridRows); $i++) {      
      if ($i > 0) { $jSonRep.= ","; } // prepend any option after the first
      $jSonRep.= "{";
      $jSonRep.= "\"optionNo\":\"" . $i . "\",";
      $jSonRep.= "\"optionNoLabel\":\"" . intval($i + 1) . "\",";
      $jSonRep.= "\"optionSelected\":false,";
      $jSonRep.= "\"id\":\"" .$i. "\",";
      $jSonRep.= "\"label\":" . JSONparse($gridRows[$i]['label']). ",";
      $jSonRep.= "\"rowColumns\":[";
      for ($j=0; $j<count($gridColumns); $j++) {
        if ($j>0) { $jSonRep.= ","; }
        $jSonRep.= "{";
        $jSonRep.= "\"rbSelected\":false,";
        $jSonRep.= "\"label\":". JSONparse($gridColumns[$j]['label']). ",";
        $jSonRep.= "\"colValue\":\"". $gridColumns[$j]['colValue'] . "\"";
        $jSonRep.= "}";
      }
      $jSonRep.= "]";      
      $jSonRep.= "}";
    }
    $jSonRep.= "]";
    return $jSonRep;
  }
  
  function buildOptionsJSON($options) {
    $jSonRep = "\"optionCnt\":\"" . count($options) . "\",";        
    $jSonRep.= "\"options\": [";
    for ($oo=0; $oo<count($options); $oo++) {      
      if ($oo > 0) { $jSonRep.= ","; } // prepend any option after the first
      $jSonRep.= "{";
      $jSonRep.= "\"optionNo\":\"" . $oo . "\",";
      $jSonRep.= "\"optionNoLabel\":\"" . intval($oo + 1) . "\",";
      $jSonRep.= "\"optionSelected\":false,";
      $jSonRep.= "\"id\":\"" .$options[$oo]['id']. "\",";
      $jSonRep.= "\"label\":" . JSONparse($options[$oo]['label']). ",";
      $jSonRep.= "\"rowColumns\":[]";
      $jSonRep.= "}";
    }
    $jSonRep.= "]";
    return $jSonRep;    
  }
  
  function buildIndividualQuestionJSON($qNo, $question, $qLabel, $logicalQNo, $isSlider, $logicalSliderPtr) {
    $jSonRep = "{";
    $jSonRep.= "\"qNo\":\"". $qNo . "\",";
    $jSonRep.= $question['qMandatory'] === '0' ? "\"qMandatory\":false," : "\"qMandatory\":true,"; 
    if ($isSlider) {
      $jSonRep.= "\"isSlider\":true,";
      $jSonRep.= "\"logicalSliderNo\":\"". $logicalSliderPtr . "\",";
    }
    else {
      $jSonRep.= "\"isSlider\":false,";  
      $jSonRep.= "\"logicalSliderNo\":\"". -1 . "\",";
    }
    $jSonRep.= "\"QAnswerValue\":0,";
    $jSonRep.= "\"QAnswerText\":\"\",";
    $jSonRep.= "\"qNoLabel\":\"". $qLabel ."\",";
    $jSonRep.= "\"logicalQNo\":\"". $logicalQNo ."\",";
    $jSonRep.= "\"qType\":" . JSONparse($this->getControlLabelFromType($question['qType'])) . ",";
    $jSonRep.= "\"qLabel\":["; 
      $paraArray = makeParaArray($question['qLabel']);
      for ($line=0; $line<count($paraArray); $line++) {
        if ($line > 0) { $jSonRep.=","; }
        $jSonRep.= "{\"para\":" . JSONparse($paraArray[$line]) . "}"; 
      }
    $jSonRep.= "],";    
    $jSonRep.= "\"qValidationMsg\":" . JSONparse($question['qValidationMsg']) . ",";
    $jSonRep.= "\"qContingentText\":" . JSONparse($question['qContingentText']) . ",";
    $jSonRep.= "\"qContinuousSliderMax\":\"" . $question['qContinuousSliderMax'] . "\",";
    $jSonRep.= "\"gridInstruction\":[";
      $gridInstArray = makeParaArray($question['qGridInstruction']);
      for ($line=0; $line<count($gridInstArray); $line++) {
        if ($line > 0) { $jSonRep.=","; }
        $jSonRep.= "{\"para\":" . JSONparse($gridInstArray[$line]) . "}";         
      }
    $jSonRep.= "],";
    $jSonRep.= "\"qGridTarget\":\"" . $question['qGridTarget'] . "\",";
    $jSonRep.= $this->buildSelectorOptionsJSON($question['options']);
    
    if ($question['qType'] === '9') {
      // radiobuttonGrid is a special case of options where each option is a row, with a sub array of columns
      $jSonRep.= $this->buildGridJSON($question['gridRows'], $question['gridColumns']);
    }
    else {
      $jSonRep.= $this->buildOptionsJSON($question['options']);
    }
    $jSonRep.= "}";
    return $jSonRep;
  }
    
  function buildQuestionsJSON($isFilterPage, $optionLabel, $page, &$qcount, &$qAnsweredArray, &$isSliderArray, &$slidersAnswered) {
    $jSonRep = "";
    $sequentialFilteredCount = 0;
    $logicalSliderCount = 0;
    $logicalSliderPtr = -1;
    $qAnsweredArray = array();
    $isSliderArray = array();
    $slidersAnswered = array();
    if ($isFilterPage) {
      for ($q=1; $q<count($page['questions']); $q++) {
        if ($page['questions'][$q]['qContingentText'] == $optionLabel) {
          array_push($qAnsweredArray, $page['questions'][$q]['qMandatory'] == 1 ? false : true);
          if ($page['questions'][$q]['qType'] == 7) {
            array_push($isSliderArray, 1);
            array_push($slidersAnswered, 0);
            $isSlider = true;
            $logicalSliderPtr = $logicalSliderCount++ ;
          }
          else {
            array_push($isSliderArray, 0);
            array_push($slidersAnswered, 0);
            $isSlider = false;
          }
          if ($sequentialFilteredCount > 0) { $jSonRep.= ","; }
          $logicalQNo = $sequentialFilteredCount;
          ++$sequentialFilteredCount;
          $jSonRep.= $this->buildIndividualQuestionJSON($q, $page['questions'][$q], $sequentialFilteredCount, $logicalQNo, $isSlider, $logicalSliderPtr);
        }
      }
      $qcount = $sequentialFilteredCount;
    }
    else {
      for ($q=0; $q<count($page['questions']); $q++) {
        array_push($qAnsweredArray, $page['questions'][$q]['qMandatory'] == 1 ? false : true);
        if ($page['questions'][$q]['qType'] == 7) {
            array_push($isSliderArray, 1);
            array_push($slidersAnswered, 0);
          $isSlider = true;
          $logicalSliderPtr = $logicalSliderCount++ ;
        }
        else {
            array_push($isSliderArray, 0);
            array_push($slidersAnswered, 0);
          $isSlider = false;
        }
        if ($q > 0) { $jSonRep.= ","; }
        $jSonRep.= $this->buildIndividualQuestionJSON($q, $page['questions'][$q], $q + 1, $q, $isSlider, $logicalSliderPtr);     
      }
      $qcount = count($page['questions']);
    }
    return $jSonRep;    
  }
  
  function buildEmptyFilterQuestionJSON() {
    $jSonRep = "";
    $jSonRep.= "\"fqNo\":\"0\",";
    $jSonRep.= "\"fqAnswerText\":\"\",";
    $jSonRep.= "\"fqAnswerValue\":255,";
    $jSonRep.= "\"fqNoLabel\":\"1\",";
    $jSonRep.= "\"fqType\":\"none\",";
    $jSonRep.= "\"fqLabel\":[],"; 
    $jSonRep.= "\"fqValidationMsg\":\"\",";
    $jSonRep.= "\"fqContinuousSliderMax\":\"" . 0 . "\",";
    $jSonRep.= $this->buildFQSelectorOptionsJSON(array());
    $jSonRep.= "\"fqOptionCnt\":\"" . 0 . "\",";        
    $jSonRep.= "\"fqOptions\": [],";
    return $jSonRep;    
  }
  
  function buildFilterQuestionJSON($question) {
    $jSonRep = "";
    $jSonRep.= "\"fqNo\":\"0\",";
    $jSonRep.= "\"fqAnswerText\":\"\",";
    $jSonRep.= "\"fqAnswerValue\":255,";
    $jSonRep.= "\"fqNoLabel\":\"1\",";
    $jSonRep.= "\"fqType\":" . JSONparse($this->getControlLabelFromType($question['qType'])) . ",";
    $jSonRep.= "\"fqLabel\":["; 
      $paraArray = makeParaArray($question['qLabel']);
      for ($line=0; $line<count($paraArray); $line++) {
        if ($line > 0) { $jSonRep.=","; }
        $jSonRep.= "{\"para\":" . JSONparse($paraArray[$line]) . "}"; 
      }
    $jSonRep.= "],";    
    $jSonRep.= "\"fqValidationMsg\":" . JSONparse($question['qValidationMsg']) . ",";
    $jSonRep.= "\"fqContinuousSliderMax\":\"" . $question['qContinuousSliderMax'] . "\",";
    $jSonRep.= $this->buildFQSelectorOptionsJSON($question['options']);
    $jSonRep.= "\"fqOptionCnt\":\"" . count($question['options']) . "\",";        
    $jSonRep.= "\"fqOptions\": [";
    for ($oo=0; $oo<count($question['options']); $oo++) {      
      if ($oo > 0) { $jSonRep.= ","; } // prepend any option after the first
      $jSonRep.= "{";
      $jSonRep.= "\"optionNo\":\"" . $oo . "\",";
      $jSonRep.= "\"optionNoLabel\":\"" . intval($oo + 1) . "\",";
      $jSonRep.= "\"optionSelected\":false,";
      $jSonRep.= "\"id\":\"" .$question['options'][$oo]['id']. "\",";
      $jSonRep.= "\"label\":" . JSONparse($question['options'][$oo]['label']);
      $jSonRep.= "}";
    }
    $jSonRep.= "],";
    return $jSonRep;
  }  
  
  function buildStepFormRuntimeJSON($restartUID, $respId) {
    //return print_r($this->formDef, true);
    $jSonRep = "{";
    $jSonRep.= "\"exptId\":\"" . $this->exptId . "\","; 
    $jSonRep.= "\"formType\":\"" . $this->formType . "\",";
    $jSonRep.= "\"jType\":\"" . $this->jType . "\",";
    $jSonRep.= "\"restartUID\":\"" . $restartUID . "\","; 
    $jSonRep.= "\"respId\":\"" .$respId."\","; // maps back to step2pptStatus id and should be linked to restartUID
    $jSonRep.= "\"formTitle\":" . JSONparse($this->formDef['formTitle']) . ",";
    $jSonRep.= "\"formInst\":["; 
      $paraArray = makeParaArray($this->formDef['formInst']);
      for ($i=0; $i<count($paraArray); $i++) {
        if ($i > 0) { $jSonRep.=","; }
        $jSonRep.= "{\"para\":" . JSONparse($paraArray[$i]) . "}"; 
      }
    $jSonRep.= "],";    
    $jSonRep.= "\"finalMsg\":["; 
      $paraArray = makeParaArray($this->formDef['finalMsg']);
      for ($i=0; $i<count($paraArray); $i++) {
        if ($i > 0) { $jSonRep.=","; }
        $jSonRep.= "{\"para\":" . JSONparse($paraArray[$i]) . "}"; 
      }
    $jSonRep.= "],";    
    $jSonRep.= "\"finalButtonLabel\":" . JSONparse($this->formDef['finalButtonLabel']) . ",";
    $uipElement = $this->formDef['useIntroPage'] === '0' ? "\"useIntroPage\":false," : "\"useIntroPage\":true," ;
    $jSonRep.= $uipElement;
    $jSonRep.= "\"introPageMessage\":["; 
      $paraArray = makeParaArray($this->formDef['introPageMessage']);
      for ($i=0; $i<count($paraArray); $i++) {
        if ($i > 0) { $jSonRep.=","; }
        $jSonRep.= "{\"para\":" . JSONparse($paraArray[$i]) . "}"; 
      }
    $jSonRep.= "],";
    $jSonRep.= "\"introPageTitle\":" . JSONparse($this->formDef['introPageTitle']) . ","; 
    $jSonRep.= "\"introPageButtonLabel\":" . JSONparse($this->formDef['introPageButtonLabel']) . ","; 
    $jSonRep.= $this->buildSelectorOptionsJSON($this->formDef['eligibilityQ']['options']);
    $jSonRep.= "\"eligibilityQAnswerText\":\"\",";
    $jSonRep.= "\"eligibilityQAnswerValue\":255,";
    $ueqElement = $this->formDef['useEligibilityQ'] === '0' ? "\"useEligibilityQ\":false," : "\"useEligibilityQ\":true," ;
    $jSonRep.= $ueqElement;
    $jSonRep.= "\"eligibilityQType\":" . JSONparse($this->getControlLabelFromType($this->formDef['eligibilityQ']['qType'])) . ",";
    $jSonRep.= "\"eligibilityQTypeValue\":\"" . $this->formDef['eligibilityQ']['qType'] . "\",";
    $jSonRep.= "\"eligibilityQLabel\":["; 
      $paraArray = makeParaArray($this->formDef['eligibilityQ']['qLabel']);
      for ($i=0; $i<count($paraArray); $i++) {
        if ($i > 0) { $jSonRep.=","; }
        $jSonRep.= "{\"para\":" . JSONparse($paraArray[$i]) . "}"; 
      }
    $jSonRep.= "],";
    $jSonRep.= "\"eligibilityQValidationMsg\":" . JSONparse($this->formDef['eligibilityQ']['qValidationMsg']) . ",";
    $jSonRep.= "\"nonEligibleMsg\":" . JSONparse($this->formDef['eligibilityQ']['qNonEligibleMsg']) . ",";
    $jSonRep.= "\"eligibilityQContinuousSliderMax\":\"" . $this->formDef['eligibilityQ']['qContinuousSliderMax'] . "\",";
    $jSonRep.= "\"eligibilityQisJTypeSelector\":\"" . $this->formDef['eligibilityQ']['qUseJTypeSelector'] . "\",";
    $jSonRep.= "\"eqOptionsCount\":\"" . count($this->formDef['eligibilityQ']['options']) . "\","; 

    $urcElement = $this->formDef['useRecruitmentCode'] === '0' ? "\"useRecruitmentCode\":false," : "\"useRecruitmentCode\":true," ;
    $jSonRep.= $urcElement; 
    $arcElement = $this->formDef['allowNullRecruitmentCode'] === '0' ? "\"allowNullRecruitmentCode\":false," : "\"allowNullRecruitmentCode\":true," ;
    $jSonRep.= $arcElement; 
    $jSonRep.= "\"recruitmentCodeLabel\":" . JSONparse($this->formDef['recruitmentCodeLabel']) . ","; 
    $jSonRep.= "\"recruitmentCodeMessage\":["; 
      $paraArray = makeParaArray($this->formDef['recruitmentCodeMessage']);
      for ($i=0; $i<count($paraArray); $i++) {
        if ($i > 0) { $jSonRep.=","; }
        $jSonRep.= "{\"para\":" . JSONparse($paraArray[$i]) . "}"; 
      }
    $jSonRep.= "],";        
    $jSonRep.= "\"recruitmentCodeText\":\"\",";
    $jSonRep.= "\"hasCodeSelected\":false,";
    $jSonRep.= "\"hasNoCodeSelected\":false,";
    $jSonRep.= "\"recruitmentCodeOptionLabel\":" . JSONparse($this->formDef['recruitmentCodeOptionLabel']) . ","; 
    $jSonRep.= "\"nullRecruitmentCodeOptionLabel\":" . JSONparse($this->formDef['nullRecruitmentCodeOptionLabel']) . ","; 

        
    $jSonRep.= $this->formDef['pages'][0]['ignorePage'] == 1 ? "\"bypassPages\":true," : "\"bypassPages\":false,";
    $jSonRep.= "\"eqOptionSelected\":\"\",";
    $jSonRep.= "\"eqOptions\":[";
    for ($eo=0; $eo<count($this->formDef['eligibilityQ']['options']); $eo++) {
      if ($eo > 0) { $jSonRep.=","; }  // prepend any option after the first
      $jSonRep.= "{";
        $jSonRep.= "\"optionNo\":\"" . $eo . "\",";
        $jSonRep.= "\"optionNoLabel\":\"" . intval($eo + 1) . "\",";
        $jSonRep.= "\"optionSelected\":false,";
        $ierElement = $this->formDef['eligibilityQ']['options'][$eo]['isEligibleResponse'] === '0' ? "\"isEligibleResponse\":false," : "\"isEligibleResponse\":true,";
        $jSonRep.= "\"jType\":\"" . $this->formDef['eligibilityQ']['options'][$eo]['jType'] . "\",";
        $jSonRep.= $ierElement;
        $jSonRep.= "\"id\":\"" . $this->formDef['eligibilityQ']['options'][$eo]['id'] . "\",";
        $jSonRep.= "\"label\":" . JSONparse($this->formDef['eligibilityQ']['options'][$eo]['label']) . ",";
        $countRelevantSections = 0;
        $jSonRep.= "\"eligibleSections\":[";
          if ($this->formDef['pages'][0]['ignorePage'] == 0) {
            for ($p=0; $p<count($this->formDef['pages']); $p++) {
              $includePage = false;
              if ($this->formDef['useEligibilityQ'] === '1') {
                if ( ($this->formDef['pages'][$p]['contingentPage'] == 1)
                    && ($this->formDef['pages'][$p]['contingentText'] == $this->formDef['eligibilityQ']['options'][$eo]['label']) ) {
                  $includePage = true;
                }
                else {
                  if ($this->formDef['pages'][$p]['contingentPage'] == 0) {
                    $includePage = true;
                  }
                }
              }
              else {
                if ($this->formDef['pages'][$p]['jType'] == $this->jType) {
                  $includePage = true;
                }
              }
              if ($includePage) {
                if ($countRelevantSections > 0) { $jSonRep.=','; }
                $jSonRep.= "{";
                  $jSonRep.= "\"pageNo\":\"" . $countRelevantSections . "\",";
                  $jSonRep.= "\"pageNoLabel\":\"" . intval($countRelevantSections + 1) . "\",";
                  $jSonRep.= "\"pageTitle\":" . JSONparse($this->formDef['pages'][$p]['pageTitle']) . ",";
                  $jSonRep.= "\"pageInst\":["; 
                    $paraArray = makeParaArray($this->formDef['pages'][$p]['pageInst']);
                    for ($line=0; $line<count($paraArray); $line++) {
                      if ($line > 0) { $jSonRep.=","; }
                      $jSonRep.= "{\"para\":" . JSONparse($paraArray[$line]) . "}"; 
                    }
                  $jSonRep.= "],";
                  $jSonRep.= $this->formDef['pages'][$p]['ignorePage'] == 1 ? "\"ignorePage\":true," : "\"ignorePage\":false,";
                  $jSonRep.= "\"pageButtonLabel\":" . JSONparse($this->formDef['pages'][$p]['pageButtonLabel']) . ",";
                  if ($this->formDef['pages'][$p]['q0isFilter'] == 1) {
                    $jSonRep.= "\"filterPage\":true,";
                    $jSonRep.= $this->buildFilterQuestionJSON($this->formDef['pages'][$p]['questions'][0]);
                  }
                  else {
                    $jSonRep.= "\"filterPage\":false,";
                    $jSonRep.= $this->buildEmptyFilterQuestionJSON();
                  }
                  $jSonRep.= "\"filterOptions\":[";
                    if ($this->formDef['pages'][$p]['q0isFilter'] == 1) {
                      for ($fon=0; $fon<count($this->formDef['pages'][$p]['questions'][0]['options']); $fon++) {
                        $optionLabel = $this->formDef['pages'][$p]['questions'][0]['options'][$fon]['label'];
                        if ($fon>0) { $jSonRep.=","; }
                        $jSonRep.= "{";
                          $jSonRep.= "\"optionLabel\":". JSONparse($optionLabel) . ",";
                          $jSonRep.= "\"optionId\":\"" . $this->formDef['pages'][$p]['questions'][0]['options'][$fon]['id'] . "\",";
                          $subQCnt = -1;
                          $jSonRep.= "\"questions\":[";
                            $jSonRep.= $this->buildQuestionsJSON(true, $optionLabel, $this->formDef['pages'][$p], $subQCnt, $subQAnsweredArray, $isSliderArray, $slidersAnswered);
                          $jSonRep.= "],";
                          $jSonRep.= "\"questionCount\":\"". $subQCnt . "\",";
                          $jSonRep.= "\"questionAnswered\":[";
                          for ($sqc=0; $sqc<$subQCnt; $sqc++) {
                            if ($sqc>0) {$jSonRep.=",";}                            
                            $jSonRep.= $subQAnsweredArray[$sqc] == true ? "\"1\"" : "\"0\"" ;
                          }
                          $jSonRep.= "],";
                          $jSonRep.= "\"slidersAnswered\":[";
                          for ($sa=0; $sa<count($slidersAnswered); $sa++) {
                            if ($sa>0) {$jSonRep.=",";}                            
                            $jSonRep.= $slidersAnswered[$sa];
                          }
                          $jSonRep.= "],";
                          $jSonRep.= "\"isSlider\":[";
                          for ($sa=0; $sa<count($isSliderArray); $sa++) {
                            if ($sa>0) {$jSonRep.=",";}                            
                            $jSonRep.= $isSliderArray[$sa];
                          }
                          $jSonRep.= "]";
                        $jSonRep.= "}";
                      }
                    }
                    else {
                      $jSonRep.= "{";
                        $jSonRep.= "\"optionLabel\":\"-1\",";
                        $jSonRep.= "\"optionId\":\"-1\",";
                        $subQCnt = -1;
                        $jSonRep.= "\"questions\":[";
                          $jSonRep.= $this->buildQuestionsJSON(false, "", $this->formDef['pages'][$p], $subQCnt, $subQAnsweredArray, $slidersUsed, $logicalSliderNos);
                        $jSonRep.= "],";
                        $jSonRep.= "\"questionCount\":\"". $subQCnt . "\",";
                        $jSonRep.= "\"questionAnswered\":[";
                        for ($sqc=0; $sqc<$subQCnt; $sqc++) {
                          if ($sqc>0) {$jSonRep.=",";}
                          $jSonRep.= $subQAnsweredArray[$sqc] == true ? "\"1\"" : "\"0\"" ;
                        }
                        $jSonRep.= "],";
                        $jSonRep.= "\"slidersAnswered\":[";
                        for ($sa=0; $sa<count($slidersUsed); $sa++) {
                          if ($sa>0) {$jSonRep.=",";}                            
                          $jSonRep.= $slidersUsed[$sa];
                        }
                        $jSonRep.= "],";
                        $jSonRep.= "\"logicalSliderNos\":[";
                        for ($sa=0; $sa<count($logicalSliderNos); $sa++) {
                          if ($sa>0) {$jSonRep.=",";}                            
                          $jSonRep.= $logicalSliderNos[$sa];
                        }
                        $jSonRep.= "]";
                      $jSonRep.= "}";
                    }
                  $jSonRep.="]";
                $jSonRep.= "}";
                ++$countRelevantSections;
              }
            }            
          }
        $jSonRep.= "],";
        $jSonRep.= "\"eligibleSectionsCount\":\"". $countRelevantSections . "\",";
        $jSonRep.= "\"eligibleSectionsFinished\":[";
        for ($esc=0; $esc<$countRelevantSections; $esc++) {
          if ($esc>0) { $jSonRep.= ","; }
          $jSonRep.="false";
        }
        $jSonRep.= "]";
      $jSonRep.= "}";      
    }
    $jSonRep.= "]}";
    return $jSonRep;    
  }

// </editor-fold>
                           
// <editor-fold defaultstate="collapsed" desc=" helpers">
  
  function getControlItems() {
    // get all control values 
    global $igrtSqli;
    $controlQry = "SELECT * FROM igControlTypes";
    $controlResults = $igrtSqli->query($controlQry);
    if ($controlResults) {
      while ($row = $controlResults->fetch_object()) {
        $controlDetail = array(
          'id' => $row->cValue,
          'label' => $row->cLabel
        );
        array_push($this->formControlSelectOptions, $controlDetail);
      }
    }
  }
// </editor-fold>
                       
// <editor-fold defaultstate="collapsed" desc=" constructor">
  function __construct($userId, $exptId, $formType) {
    $this->exptId = $exptId;
    if (isset($GLOBALS['formName'])) { $this->formName = $GLOBALS['formName']; }
    $this->stepFormsHandler = new stepFormsHandler($userId, $exptId, $formType);
    $this->getControlItems();
  }
// </editor-fold>

}

