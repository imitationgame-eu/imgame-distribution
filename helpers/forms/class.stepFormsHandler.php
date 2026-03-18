<?php
/**
 * Step forms controller
 * top-level controller for step forms
 * @author MartinHall
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL);
if (!isset($root_path)) { $root_path = $_SERVER['DOCUMENT_ROOT']; }
include_once $root_path.'/helpers/models/class.experimentModel.php';
include_once $root_path.'/domainSpecific/mySqlObject.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

class stepFormsHandler {
  private $controlItems = array();
  private $judgeTypes = [];
  
  private $igrtSqli;
  private $tabIndex = 1;
  private $formName;
  public $formDef;  // public during debug
  
  private $userId;
  private $formType;
  private $exptId;
  private $jType;
  private $userAccordionStates;
  
// <editor-fold defaultstate="collapsed" desc=" get, save & create definition functions">

  function getEligibilityQ($useEligibilityQ) {
    $qSql = sprintf("SELECT * FROM fdStepFormsEligibilityQuestions WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $qResult = $this->igrtSqli->query($qSql);
    $qRow = $qResult->fetch_object();
    $qDef = [];
    if (isset($qRow)) {
      $optionsDef = array(
        'options' => $this->getEligibilityQOptions(),
        'eligibilityOptionsAccordionClosed' => $this->userAccordionStates->topLevelStates->eligibilityOptionsAccordionClosed == 1,
        //'qOptionsAreExclusive' => $qRow->qOptionsAreExclusive,
      );
	    $qDef=array(
		    'qType' => $qRow->qType,
		    'qLabel' => $qRow->qLabel,
		    'eligibilitySectionClosed' => $this->userAccordionStates->topLevelStates->eligibilitySectionClosed == 1,
		    //'qValidationMsg' => $qRow->qValidationMsg,
		    'qContinuousSliderMax' => $qRow->qContinuousSliderMax,
		    'qNonEligibleMsg' => $qRow->qNonEligibleMsg,
		    'qUseJTypeSelector' => $qRow->qUseJTypeSelector == 1,
        'useEligibilityQ' => $useEligibilityQ == 1,
		    'optionsDef' => $optionsDef
	    );
    }
    else {
    	$this->createDefaultEligibilityQuestion();
    	$qDef = $this->getEligibilityQ($useEligibilityQ);
    }
    return $qDef;
  }

  function getEligibilityQOptions() {
	  // get associated options (always at least 2 options for eligibility Q )
	  $eqOptions=[];
	  $eqoSql=sprintf("SELECT * FROM fdStepFormsEligibilityQuestionsOptions WHERE exptId='%s' AND formType='%s' ORDER BY displayOrder ASC", $this->exptId, $this->formType);
	  $eqoResult = $this->igrtSqli->query($eqoSql);
	  while ($eqoRow = $eqoResult->fetch_object()) {
		  $eqoDef=array(
			  'id'=>$eqoRow->displayOrder,
			  'label'=>$eqoRow->label,
			  'isEligibleResponse'=>$eqoRow->isEligibleResponse == 1, // this field is used if not JTypeSelector, otherwise jType is used
			  'jType' => $eqoRow->jType,
        'jTypeLabel' => $this->judgeTypes[$eqoRow->jType]['label']
		  );
		  array_push($eqOptions, $eqoDef);
	  }
	  if (count($eqOptions) == 0) {
	  	$this->createDefaultEligibilityQuestionOptions();
	  	$eqOptions = $this->getEligibilityQOptions();
	  }
		return $eqOptions;
  }
  function getQuestionOptions($pNo, $qNo) {
    // get associated options (always at least one option even for no-option qTypes )
    $cbPairs = array();
    $sqlCb = sprintf("SELECT * FROM fdStepFormsQuestionsOptions WHERE exptId='%s' AND formType='%s' AND qNo='%s' "
      . "AND pNo='%s' ORDER BY displayOrder ASC", $this->exptId, $this->formType, $qNo, $pNo);
    $cbResult = $this->igrtSqli->query($sqlCb);
    if ($cbResult->num_rows > 20)
    {
      for ($i=0; $i<20; $i++) {
        $cbRow = $cbResult->fetch_object();
        $cbPairDef = array('id' => $cbRow->displayOrder, 'label' => $cbRow->label);
        array_push($cbPairs, $cbPairDef);
      }
    }
    else {
      while ($cbRow = $cbResult->fetch_object()) {
        $cbPairDef = array('id' => $cbRow->displayOrder, 'label' => $cbRow->label);
        array_push($cbPairs, $cbPairDef);
      }
    }
    return $cbPairs;
  }

  function getFormPageQuestions($pNo) {
    $qList = [];
    $qSql = sprintf("SELECT * FROM fdStepFormsQuestions WHERE exptId='%s' AND formType='%s' "
                  . "AND pNo='%s' ORDER BY qNo ASC", $this->exptId, $this->formType, $pNo);
    $qResult = $this->igrtSqli->query($qSql);
    if($qResult->num_rows == 0) {
      $this->createDefaultPageQuestion($pNo);
      $qList = $this->getFormPageQuestions($pNo);
    }
    else {
      // check for spurious saves
      if ($qResult->num_rows > 30) {
        $this->createDefaultPageQuestion($pNo);
        $qList = $this->getFormPageQuestions($pNo);
      }
      else {
        for ($i=0; $i<$qResult->num_rows; $i++) {
          
          $qRow = $qResult->fetch_object();
          $qDef = array(
            'pNo' => $pNo,
            'qNo' => $qRow->qNo,
            'qType' => $qRow->qType,
            'qLabel' => $qRow->qLabel,
            'accordionClosed' => isset($this->userAccordionStates->pageAccordions[$pNo]->qAccordions[$i]->status) ? $this->userAccordionStates->pageAccordions[$pNo]->qAccordions[$i]->status == 1 : true,
            'qFilterValue' => $qRow->qFilterValue,
            'qIsFilter' => $qRow->qIsFilter == 1,
            'qValidationMsg' => $qRow->qValidationMsg,
            'qContinuousSliderMax' => $qRow->qContinuousSliderMax,
            'qMandatory' => $qRow->qMandatory == 1,
            'options' => $this->getQuestionOptions($pNo, $qRow->qNo)
          );
           array_push($qList, $qDef);
        }
      }
    }
    
    
    return $qList;
  }
  
  function getFormPageDefinitions() {
    $pageDefList = array();
    $pageSql = sprintf("SELECT * FROM fdStepFormsPages WHERE exptId='%s' AND formType='%s' ORDER BY pNo ASC", $this->exptId, $this->formType);
    $pageResult = $this->igrtSqli->query($pageSql);
    if ($pageResult->num_rows == 0) {
      $this->createDefaultPage();
      $pageDefList = $this->getFormPageDefinitions();
    }
    else {
      // check for bad save where multiple spurious pages created.
      if ($pageResult->num_rows > 50) {
        $this->createDefaultPage();
        $pageDefList = $this->getFormPageDefinitions();
      }
      else {
        for ($i=0;$i<$pageResult->num_rows; $i++) {
          $pageRow = $pageResult->fetch_object();
          $pNo = $pageRow->pNo;
          $pageDef = array(
            'pNo' => $pNo,
            'pageTitle' => $pageRow->pageTitle,
            'pageInst' => $pageRow->pageInst,
            'pageButtonLabel' => $pageRow->pageButtonLabel,
            'contingentPage' => $pageRow->contingentPage == 1,
            'pageAccordionClosed' => $this->userAccordionStates->pageAccordions[$i]->state->status == 1,
            'contingentValue' => $pageRow->contingentValue,
            'contingentText' => $pageRow->contingentText,
            'ignorePage' => $pageRow->ignorePage == 1,
            'jType' => $pageRow->jType, // jType is mainly used if form does not have eligibilityQ (post forms especially, as jType is selected from pre-form which does have )
            'questions' => $this->getFormPageQuestions($pNo)
          );
          array_push($pageDefList, $pageDef);
        }
      }
      
    }
    return $pageDefList;
  }
  
  function getActivePageCount($jType, $pages) {
  	$cnt = 0;
  	foreach ($pages as $page) {
  		if ($page['contingentPage'] == 0) {
  			++$cnt;
		  }
  		else {
  			if ($page['contingentValue'] == $jType) {
  				++$cnt;
  			}
		  }
	  }
  	return $cnt;
  }
  
  function getForm($retry) {
    $form = array();
    $form['retry'] = $retry;
    $form['igControlTypes'] = $this->controlItems;
    // check a definition is in  for this expt and type, create if not
    $sql=sprintf("SELECT * FROM fdStepForms WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $result=$this->igrtSqli->query($sql);
    if ($result->num_rows == 1) {
      $row=$result->fetch_object();
      $form['judgeTypeOptions'] = $this->judgeTypes;
      $form['exptId'] = $this->exptId;
      $form['formType'] = $this->formType;
      $form['formName'] = $this->formName;
      $form['currentFocusControlId'] = $row->currentFocusControlId;
      $form['formTitle'] = $row->formTitle;
      $form['formInst'] = $row->formInst;
      
      
      $recruitmentSection = array();
      $recruitmentSection['recruitmentSectionClosed'] = $this->userAccordionStates->topLevelStates->recruitmentSectionClosed == 1;
      $recruitmentSection['useRecruitmentCode'] = $row->useRecruitmentCode == 1;
      //$recruitmentSection['allowNullRecruitmentCode'] = $row->allowNullRecruitmentCode;
      $recruitmentSection['recruitmentCodeLabel'] = $row->recruitmentCodeLabel;
      $recruitmentSection['recruitmentCodeMessage'] = $row->recruitmentCodeMessage;
      $recruitmentSection['recruitmentCodeYesLabel'] = $row->recruitmentCodeOptionLabel;
      $recruitmentSection['recruitmentCodeNoLabel'] = $row->nullRecruitmentCodeOptionLabel;
      
      $introPage = array();
      $introPage['recruitmentSection'] = $recruitmentSection;
      $introPage['useIntroPage'] = $row->useIntroPage == 1;
      $introPage['introPageTitle'] = $row->introPageTitle;
      $introPage['introPageMessage'] = $row->introPageMessage;
      $introPage['introPageButtonLabel'] = $row->introPageButtonLabel;
      $introPage['introPageAccordionClosed'] = $this->userAccordionStates->topLevelStates->introPageAccordionClosed == 1;
      $introPage['eligibilityQ'] = $this->getEligibilityQ($row->useEligibilityQ);
      $form['introPage'] = $introPage;
      
      $finalPage = array();
      $finalPage['finalMsg'] = $row->finalMsg;
      $finalPage['finalButtonLabel'] = $row->finalButtonLabel;
      $finalPage['finalPageAccordionClosed'] = $this->userAccordionStates->topLevelStates->finalPageAccordionClosed == 1;
      $finalPage['useFinalPage'] = $row->useFinalPage == 1;
      $form['finalPage'] = $finalPage;
      
      
      $form['pagesAccordionClosed'] = $this->userAccordionStates->topLevelStates->pagesAccordionClosed == 1;
      $form['definitionComplete'] = $row->definitionComplete;
      $this->formDef = $form; // need this instantiated for getFormPageDefinitions - clumsy
      $form['pages'] = $this->getFormPageDefinitions();
      $form['cntActivePages'] = [];
      array_push($form['cntActivePages'], $this->getActivePageCount("0", $form['pages']));
      array_push($form['cntActivePages'], $this->getActivePageCount("1", $form['pages']));
      return $form;
    }
    else {
      return $this->makeDefaultForm();  // builds default values in db and then reloads
    }
  }
       
  function saveForm() {
    // wipe out existing form 
    $cleanForm = "DELETE FROM fdStepForms WHERE exptId=$this->exptId AND formType=$this->formType";      
    $this->igrtSqli->query($cleanForm);
    $cleanPages = "DELETE FROM fdStepFormsPages WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanPages);
    $cleanQuestions = "DELETE FROM fdStepFormsEligibilityQuestions WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanQuestions);
    $cleanOptions = "DELETE FROM fdStepFormsEligibilityQuestionsOptions WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanOptions);
    $cleanQuestions = "DELETE FROM fdStepFormsQuestions WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanQuestions);
    $cleanOptions = "DELETE FROM fdStepFormsQuestionsOptions WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanOptions);
    $cleanOptions = "DELETE FROM fdStepFormsGridValues WHERE exptId=$this->exptId AND formType=$this->formType";
    $this->igrtSqli->query($cleanOptions);
    
    // insert
    $makeForm = sprintf("INSERT INTO fdStepForms (exptId, formType, formTitle, formInst, finalMsg, finalButtonLabel, introAccordionClosed, "
      . "finalAccordionClosed, useIntroPage, introPageTitle, introPageMessage, introPageButtonLabel, useEligibilityQ, currentFocusControlId, "
        . "useRecruitmentCode, allowNullRecruitmentCode, recruitmentCodeLabel, recruitmentCodeMessage, recruitmentCodeOptionLabel, nullRecruitmentCodeOptionLabel ) "
      . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')", 
      $this->exptId, $this->formType, $this->formDef['formTitle'], $this->formDef['formInst'], $this->formDef['finalMsg'], 
      $this->formDef['finalButtonLabel'], $this->formDef['introAccordionClosed'], $this->formDef['finalAccordionClosed'],
      $this->formDef['useIntroPage'], $this->formDef['introPageTitle'], $this->formDef['introPageMessage'], 
      $this->formDef['introPageButtonLabel'], $this->formDef['useEligibilityQ'], $this->formDef['currentFocusControlId'],   
      $this->formDef['useRecruitmentCode'], $this->formDef['allowNullRecruitmentCode'], $this->formDef['recruitmentCodeLabel'],   
      $this->formDef['recruitmentCodeMessage'], $this->formDef['recruitmentCodeOptionLabel'], $this->formDef['nullRecruitmentCodeOptionLabel']);   
    $this->igrtSqli->query($makeForm);
    // store eligibility question 
    $makeEligibilityQuestion = sprintf("INSERT INTO fdStepFormsEligibilityQuestions (exptId, formType, qType, qLabel, qAccordionClosed, "
        . "qValidationMsg, qContinuousSliderMax, qOptionsAreExclusive, qNonEligibleMsg, qUseJTypeSelector) "
      . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
      $this->exptId, $this->formType, $this->formDef['eligibilityQ']['qType'], $this->formDef['eligibilityQ']['qLabel'], 
      $this->formDef['eligibilityQ']['qAccordionClosed'], $this->formDef['eligibilityQ']['qValidationMsg'], $this->formDef['eligibilityQ']['qContinuousSliderMax'],
      $this->formDef['eligibilityQ']['qOptionsAreExclusive'], $this->formDef['eligibilityQ']['qNonEligibleMsg'], $this->formDef['eligibilityQ']['qUseJTypeSelector']);
    $this->igrtSqli->query($makeEligibilityQuestion);
    // store options for eligibility
    foreach ($this->formDef['eligibilityQ']['options'] as $qOption) {
      $makeEligibilityOption = sprintf("INSERT INTO fdStepFormsEligibilityQuestionsOptions (exptId, formType, label, displayOrder, isEligibleResponse, jType)"
        . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",
        $this->exptId, $this->formType, $qOption['label'], $qOption['id'], $qOption['isEligibleResponse'], $qOption['jType']);
      $this->igrtSqli->query($makeEligibilityOption);
    }
    // now do each page
    for ($pageNo=0; $pageNo<count($this->formDef['pages']); $pageNo++) {
      $pageDef = $this->formDef['pages'][$pageNo];
      $makePage = sprintf("INSERT INTO fdStepFormsPages (exptId, formType, pNo, pageTitle, pageInst, pageButtonLabel, "
        . "contingentPage, pageAccordionClosed, contingentValue, contingentText, ignorePage, q0isFilter, jType) "
        . "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ,'%s', '%s', '%s', '%s', '%s')",
        $this->exptId, $this->formType, $pageNo, $pageDef['pageTitle'], $pageDef['pageInst'], $pageDef['pageButtonLabel'], 
        $pageDef['contingentPage'], $pageDef['pageAccordionClosed'], $pageDef['contingentValue'],
        $pageDef['contingentText'], $pageDef['ignorePage'], $pageDef['q0isFilter'], $pageDef['jType']);
      $this->igrtSqli->query($makePage);     
      // do each question
      for ($qNo=0; $qNo<count($pageDef['questions']); $qNo++) {
        $qDef = $pageDef['questions'][$qNo];
        $makeQuestion = sprintf("INSERT INTO fdStepFormsQuestions (exptId, formType, pNo, qNo, qType, qLabel, "
            . "qContingentValue, qAccordionClosed, qValidationMsg, qContinuousSliderMax, qMandatory, qGridTarget, qGridInstruction) "
          . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
          $this->exptId, $this->formType, $pageNo, $qNo, $qDef['qType'], $qDef['qLabel'], 
          $qDef['qContingentValue'], $qDef['qAccordionClosed'], $qDef['qValidationMsg'], $qDef['qContinuousSliderMax'],
          $qDef['qMandatory'], $qDef['qGridTarget'], $qDef['qGridInstruction']);
        $this->igrtSqli->query($makeQuestion);
        //do each option for this question
        for ($optionNo=0; $optionNo<count($qDef['options']); $optionNo++) {
          $qOption = $qDef['options'][$optionNo];
          $makeOption = sprintf("INSERT INTO fdStepFormsQuestionsOptions (exptId, formType, pNo, qNo, label, displayOrder)"
            . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",
            $this->exptId, $this->formType, $pageNo, $qNo, $qOption['label'], $qOption['id']);
          $this->igrtSqli->query($makeOption);          
        }
        for ($i = 0; $i<count($qDef['gridColumns']); $i++) {
          $gridSet = $qDef['gridColumns'][$i];
          $makeGridSet = sprintf("INSERT INTO fdStepFormsGridValues (exptId, formType, pNo, qNo, label, colValue, isRowLabel)"
            . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '0')",
            $this->exptId, $this->formType, $pageNo, $qNo, $gridSet['label'], $gridSet['colValue']);
          $this->igrtSqli->query($makeGridSet);          
        }
        for ($i = 0; $i<count($qDef['gridRows']); $i++) {
          $gridSet = $qDef['gridRows'][$i];
          $makeGridSet = sprintf("INSERT INTO fdStepFormsGridValues (exptId, formType, pNo, qNo, label, rowNo, isRowLabel)"
            . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '1')",
            $this->exptId, $this->formType, $pageNo, $qNo, $gridSet['label'], $i);
          $this->igrtSqli->query($makeGridSet);          
        }
      }  
    }
  }

  function makeDefaultForm() {
    // make basic form with minimum pages, questions and options
    $sqlMake = sprintf("INSERT INTO fdStepForms (exptId, formType, formTitle, formInst, finalMsg, finalButtonLabel, "
      . "useIntroPage, introPageTitle, introPageMessage, introPageButtonLabel, useEligibilityQ) "
      . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
      $this->exptId, $this->formType, "insert title here", "insert instructions here", "insert final message here", "done",
      "0", "insert intro page title here", "insert intro page message here", "next", "0");
    $this->igrtSqli->query($sqlMake);
	  $this->createDefaultPage();
    $this->createDefaultEligibilityQuestion();
    return $this->getForm(true);
  }
  
  function createDefaultPageQuestionOption($pNo) {
    $cleanOptions = sprintf("DELETE FROM fdStepFormsQuestionsOptions where exptId='%s' and formType='%s' and pNo='%s'",$this->exptId, $this->formType, $pNo );
    $this->igrtSqli->query($cleanOptions);
    $insertOption = sprintf("INSERT INTO fdStepFormsQuestionsOptions (exptId, formType, pNo, qNo, label, displayOrder) VALUES ('%s','%s','%s','%s','%s','%s')",
      $this->exptId, $this->formType, $pNo, 0, 'first option', 0);
    $this->igrtSqli->query($insertOption);
  }

  function createDefaultPageQuestion($pNo) {
    $cleanQuestions = sprintf("DELETE FROM fdStepFormsQuestions WHERE exptId=$this->exptId AND formType=$this->formType AND pNo=$pNo");
    $this->igrtSqli->query($cleanQuestions);
	  $insertQuestion = sprintf("INSERT INTO fdStepFormsQuestions (exptId, formType, pNo, qNo, qType, qLabel, qValidationMsg,  qContinuousSliderMax, qMandatory, qGridTarget, qGridInstruction)
			VALUES ('%s', '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
		  $this->exptId, $this->formType, $pNo, 0, 6, 'question label', 'this question must be answered', 100, 0, 0, 'grid instruction');
	  $this->igrtSqli->query($insertQuestion);
    $this->createDefaultPageQuestionOption($pNo);
  }

  function createDefaultPage() {
    $cleanPages = sprintf("DELETE FROM fdStepFormsPages WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $this->igrtSqli->query($cleanPages);
    $insertPage = sprintf("INSERT INTO fdStepFormsPages (exptId, formType, pNo, pageTitle, pageInst, pageButtonLabel, contingentPage, useFilter, contingentValue, contingentText, ignorePage, jType)
			VALUES ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
			$this->exptId, $this->formType, 0, 'page title', 'page instruction', 'button label', 0, 1, 0, '', 0, 0);
		$this->igrtSqli->query($insertPage);
    $this->createDefaultPageQuestion();
  }

  function createDefaultEligibilityQuestion() {
    $cleanEligibilityQuestions = sprintf("DELETE FROM fdStepFormsEligibilityQuestions WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $this->igrtSqli->query($cleanEligibilityQuestions);
    $insertSql = sprintf("INSERT INTO fdStepFormsEligibilityQuestions (exptId, formType, qType, qLabel, qValidationMsg, qOptionsAreExclusive, qNonEligibleMsg) "
      . "VALUES ('%s', '%s', '5', 'eligibility question instruction', 'eligibility validation message', '0', 'non-eligible message')",
      $this->exptId, $this->formType);
    $this->igrtSqli->query($insertSql);
    $this->createDefaultEligibilityQuestionOptions();
  }

  function createDefaultEligibilityQuestionOptions() {
    $cleanEligibilityOptions = sprintf("DELETE FROM fdStepFormsEligibilityQuestionsOptions WHERE exptId='%s' AND formType='%s'", $this->exptId, $this->formType);
    $this->igrtSqli->query($cleanEligibilityOptions);
    // eligibility questions require 2 options if being used as a jType selector.
    $makeOption = sprintf("INSERT INTO fdStepFormsEligibilityQuestionsOptions (exptId, formType, label, displayOrder, isEligibleResponse, jType)"
      . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",
      $this->exptId, $this->formType, 'first eligibility option', 0, 0, 0);
    $this->igrtSqli->query($makeOption);
    $makeOption = sprintf("INSERT INTO fdStepFormsEligibilityQuestionsOptions (exptId, formType, label, displayOrder, isEligibleResponse, jType)"
      . "VALUES ('%s', '%s', '%s', '%s', '%s', '%s')",
      $this->exptId, $this->formType, 'second eligibility option', 1, 1, 1);
    $this->igrtSqli->query($makeOption);
  }
 
  

// </editor-fold>
  
// <editor-fold defaultstate="collapsed" desc=" helpers and debug">

  function getFormType() {
    $getTypeSql = sprintf("SELECT * FROM fdStepFormsNames WHERE formName='%s'", $this->formName);
    $getTypeResult = $this->igrtSqli->query($getTypeSql);
    if ($this->igrtSqli->affected_rows > 0) {
      $getTypeRow = $getTypeResult->fetch_object();
      return $getTypeRow->formType;      
    }
    else {
      return -1;
    }      
  }
  function getFormName() {
    $getTypeSql = sprintf("SELECT * FROM fdStepFormsNames WHERE formType='%s'", $this->formType);
//    return $getTypeSql;
    $getTypeResult = $this->igrtSqli->query($getTypeSql);
    if ($this->igrtSqli->affected_rows > 0) {
      $getTypeRow = $getTypeResult->fetch_object();
      return $getTypeRow->formName;      
    }
    else {
      return 'unset';
    }          
  }
  function getControlItems() {
    // get all control values  
    $controlQry = "SELECT * FROM igControlTypes";
    $controlResults = $this->igrtSqli->query($controlQry);
    if ($this->igrtSqli->affected_rows > 0) {
      while ($row = $controlResults->fetch_object()) {
        $controlDetail = array(
          'id' => $row->cValue,
          'label' => $row->cLabel
        );
        array_push($this->controlItems, $controlDetail);
      }
    }
  }
  function getJudgeTypes() {
		$eModel = new experimentModel($this->exptId);
	  array_push($this->judgeTypes, ['id' => 0, 'label' => $eModel->evenS1Label]);
	  array_push($this->judgeTypes, ['id' => 1, 'label' => $eModel->oddS1Label]);
    array_push($this->judgeTypes, ['id' => 255, 'label' => 'no contingency required']);
    
  }
  public function setJType($jType) {
    $this->jType = $jType;
  }
  private function getPageCnt() {
    $result = $this->igrtSqli->query(sprintf("select * from fdStepFormsPages where exptId='%s' and formType='%s'", $this->exptId, $this->formType));
    return $result->num_rows;
  }
  private function getPageQuestionCnt($pageNo) {
    $result = $this->igrtSqli->query(sprintf("select * from fdStepFormsQuestions where exptId='%s' and formType='%s' and pNo='%s'", $this->exptId, $this->formType, $pageNo));
    return $result->num_rows;
  }
  private function getPageQuestionAccordionStates($pageNo) {
    $returnValue = [];
    $questionCnt = $this->getPageQuestionCnt($pageNo);
    for ($i=0; $i<$questionCnt; $i++) {
      $stateQry = sprintf("select * from ui_formDefPageQuestionControlStatus where userId='%s' and exptId='%s' and formType='%s' and pNo='%s' and qNo='%s'", $this->userId, $this->exptId, $this->formType, $pageNo, $i);
      $stateResult = $this->igrtSqli->query($stateQry);
      if ($stateResult->num_rows == 0) {
        $createQry = sprintf("insert into ui_formDefPageQuestionControlStatus (userId,exptId,formType,pNo,qNo,status) VALUES ('%s','%s','%s','%s','%s','1')", $this->userId, $this->exptId, $this->formType, $pageNo, $i);
        $this->igrtSqli->query($createQry);
        $stateResult = $this->igrtSqli->query($stateQry);
      }
      $state = $stateResult->fetch_object();
      $returnValue[] = $state;
    }
    return $returnValue;
  }
  private function getPageAccordionStates() {
    $returnValue = [];
    $pageCnt = $this->getPageCnt();
    for ($i=0; $i<$pageCnt; $i++) {
      $stateQry = sprintf("select * from ui_formDefPageControlUserStatus where userId='%s' and exptId='%s' and formType='%s' and pNo='%s'", $this->userId, $this->exptId, $this->formType, $i);
      $stateResult = $this->igrtSqli->query($stateQry);
      if ($stateResult->num_rows == 0) {
        $createQry = sprintf("insert into ui_formDefPageControlUserStatus (userId,exptId,formType,pNo,status) VALUES ('%s','%s','%s','%s','1')", $this->userId, $this->exptId, $this->formType, $i);
        $this->igrtSqli->query($createQry);
        $stateResult = $this->igrtSqli->query($stateQry);
      }
      $pageStates = new stdClass();
      $pageStates->state = $stateResult->fetch_object();;
      $pageStates->qAccordions = $this->getPageQuestionAccordionStates($i);
      $returnValue[] = $pageStates;
    }
    return $returnValue;
  }
  private function getUserAccordionStates() {
    $returnValue = new stdClass();
    $statesQry = sprintf("select * from ui_formDefControlUserStatus where userId='%s' and exptId='%s' and formType='%s'", $this->userId, $this->exptId, $this->formType);
    $statesResult = $this->igrtSqli->query($statesQry);
    if ($statesResult->num_rows == 0) {
      $createQry = sprintf("insert into ui_formDefControlUserStatus (userId, exptId, formType) VALUES ('%s','%s','%s')", $this->userId, $this->exptId, $this->formType);
      $this->igrtSqli->query($createQry);
    }
    $statesResult = $this->igrtSqli->query($statesQry);
    $returnValue->topLevelStates= $statesResult->fetch_object();
    $returnValue->pageAccordions = $this->getPageAccordionStates();
    return $returnValue;
  }


// </editor-fold>

// <editor-fold defaultstate="collapsed" desc=" constructor">
  
  
  function __construct($_userId = null, $_exptId = null, $_formType = null) {
    global $igrtSqli;
    $this->igrtSqli = $igrtSqli;
    $this->userId = $_userId;
    $this->exptId = $_exptId;
    $this->formType = $_formType;
    $this->userAccordionStates = $this->getUserAccordionStates();
	  $this->formName = $this->getFormName();
    $this->tabIndex = 1;   //
    $this->getControlItems();
    $this->getJudgeTypes();
  }


// </editor-fold>

}

