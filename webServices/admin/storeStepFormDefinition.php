<?php
  // ---- functions
    function getTypeFromText($text) {
      switch ($text) {
        case 'checkbox' : { return 0; break; }
        case 'single-line edit' : { return 1; break; }
        case 'multi-line edit' : { return 2; break; }
        case 'email' : { return 3; break; }
        case 'datetime' : { return 4; break; }
        case 'radiobutton' : { return 5; break; }
        case 'selector' : { return 6; break; }
        case 'slider' : { return 7; break; }
        case 'continuous slider' : { return 8; break; }
        case 'radiobuttonGrid' : { return 9; break; }
      }
    }

    function getContingentValueFromText($question0, $text) {
      for ($i = 0; $i<count($question0['options']); $i++) {
        if ($question0['options'][$i]['label'] === $text) { return $i; }
      }
      return -1; // possible houston, but not if no filter question on this page
    }
    
    function getContingentTextFromValue($igTypeOptions, $value) {
      for ($i=0; $i<count($igTypeOptions); $i++) {
        if ($igTypeOptions[$i]['jTypeValue'] === $value)
          return $igTypeOptions[$i]['jTypeLabel'];
      }
    }
    
    function getSelectedValueFromLabel($options, $textValue) {
      for ($i=0; $i<count($options); $i++) {
        if ($options[$i] == $textValue)
          return $i;
      }
      return -1;
    }
    
    function getFormDef($jSonArray) {
      global $igrtSqli;
      $formDef = new StdClass;
      
      
      $formDef->exptId = $jSonArray['exptId'];
      $formDef->formType = $jSonArray['formType'];
      $formDef->formName = $jSonArray['formName'];
      $formDef->definitionComplete = $jSonArray['dcVM']['booleanValue'];
      $formDef->formTitle = $igrtSqli->real_escape_string($jSonArray['formTitleVM']['textValue']);
      $formDef->formInst = $igrtSqli->real_escape_string($jSonArray['formInstructionVM']['textValue']);
      // intro page
      $formDef->useIntroPage = $jSonArray['introPageVM']['fieldVMs'][0]['booleanValue'];
      $formDef->introPageTitle = $igrtSqli->real_escape_string($jSonArray['introPageVM']['fieldVMs'][1]['textValue']);
      $formDef->introPageMessage = $igrtSqli->real_escape_string($jSonArray['introPageVM']['fieldVMs'][2]['textValue']);
      $formDef->introPageButtonLabel = $igrtSqli->real_escape_string($jSonArray['introPageVM']['fieldVMs'][3]['textValue']);
      // intro page - recruitment
      $formDef->useRecruitmentCode = $jSonArray['introPageVM']['recruitmentSectionVM']['fieldVMs'][0]['booleanValue'];
      $formDef->recruitmentCodeMessage = $igrtSqli->real_escape_string($jSonArray['introPageVM']['recruitmentSectionVM']['fieldVMs'][1]['textValue']);
      $formDef->nullRecruitmentCodeOptionLabel = $igrtSqli->real_escape_string($jSonArray['introPageVM']['recruitmentSectionVM']['fieldVMs'][2]['textValue']);
      $formDef->recruitmentCodeOptionLabel = $igrtSqli->real_escape_string($jSonArray['introPageVM']['recruitmentSectionVM']['fieldVMs'][3]['textValue']);
      $formDef->recruitmentCodeLabel = $igrtSqli->real_escape_string($jSonArray['introPageVM']['recruitmentSectionVM']['fieldVMs'][4]['textValue']);
      // intro page -eligibility question
      $formDef->useEligibilityQ = $jSonArray['introPageVM']['eligibilityQVM']['fieldVMs'][0]['booleanValue'];
      $formDef->eligibilityQLabel = $jSonArray['introPageVM']['eligibilityQVM']['fieldVMs'][1]['textValue'];
      $formDef->qNonEligibleMsg = $jSonArray['introPageVM']['eligibilityQVM']['fieldVMs'][2]['textValue'];
      $formDef->eligibilityQType = $jSonArray['introPageVM']['eligibilityQVM']['fieldVMs'][3]['selectedValue'];
      $formDef->eqUseJTypeSelector = $jSonArray['introPageVM']['eligibilityQVM']['fieldVMs'][4]['booleanValue'];
      
      $formDef->eligibilityOptions = [];
      
      $formDef->judgeTypeOptionsVM = $jSonArray['igJTypeOptionsVM'];
      
      foreach ($jSonArray['introPageVM']['eligibilityQVM']['eqOptionsVM']['optionVMs'] as $eqOption) {
        $eOption = new StdClass;
        $eOption->label = $eqOption['optionLabel'];
        $eOption->isEligibleResponse = $eqOption['isEligibleResponse'];
        $eOption->jType = $eqOption['jType'];
        $formDef->eligibilityOptions[] = $eOption;
      }
      // pages
      $pages = [];
      foreach ($jSonArray['pageVMs'] as $pageVM) {
        $page = new StdClass;
        $page->ignorePage = $pageVM['fieldVMs'][0]['booleanValue'];
        $page->pageTitle = $pageVM['fieldVMs'][1]['textValue'];
        $page->pageInstruction = $pageVM['fieldVMs'][2]['textValue'];
        $page->pageButtonLabel = $pageVM['fieldVMs'][3]['textValue'];
        
        $contingentDef = new StdClass;
        $contingentDef->contingentPage = $pageVM['contingentVM']['contingentPage'];
        $contingentDef->contingentValue = $pageVM['contingentVM']['contingentValue'];
        $contingentDef->contingentText = getContingentTextFromValue($formDef->judgeTypeOptionsVM, $pageVM['contingentVM']['contingentValue']);
        $page->contingentDef = $contingentDef;
        
        $page->jType = $pageVM['jType'];
        
        $mandatoryFilterDef = new StdClass;
        $mandatoryFilterDef->qMandatory= $pageVM['questionFilterMandatoryVM']['qMandatory'];
        $mandatoryFilterDef->qValidationMsg= $pageVM['questionFilterMandatoryVM']['qValidationMsg'];
        $mandatoryFilterDef->qType= $pageVM['questionFilterMandatoryVM']['qType'];
        $mandatoryFilterDef->qTypeFilter= $pageVM['questionFilterMandatoryVM']['qTypeFilter'];
        $mandatoryFilterDef->qIsFilter= $pageVM['questionFilterMandatoryVM']['qIsFilter'] ? 1 : 0;
        $mandatoryFilterDef->qLabel= $pageVM['questionFilterMandatoryVM']['qLabel'];
        
        $options = [];
        $option = new StdClass;
        $option->id = $pageVM['questionFilterMandatoryVM']['defaultOptionVM']['id'];
        $option->label = $pageVM['questionFilterMandatoryVM']['defaultOptionVM']['label'];
        $options[] = $option;
        // now get subsequent options
        foreach ($pageVM['questionFilterMandatoryVM']['optionVMs'] as $optionVM) {
          $option = new StdClass;
          $option->id = $optionVM['id'];
          $option->label = $optionVM['label'];
          $options[] = $option;
        }
        $mandatoryFilterDef->options = $options;
        $page->mandatoryFilterDef = $mandatoryFilterDef;
        
        $questions = [];
        foreach ($pageVM['questionVMs'] as $questionVM) {
          $question = new StdClass;
          $question->qNo = $questionVM['qNo'];
          $question->qMandatory = $questionVM['qMandatory'];
          $question->qType = $questionVM['qType'];
          $question->qLabel = $questionVM['qLabel'];
          $question->qValidationMsg = $questionVM['qValidationMsg'];
          $question->qFilterValue = $questionVM['qFilterValue'];
          $question->qIsFilter = 0; // only the mandatoryFilterQ can be set as filter
          
          $options = [];
          $option = new StdClass;
          $option->id = $questionVM['defaultOptionVM']['id'];
          $option->label = $questionVM['defaultOptionVM']['label'];
          $options[] = $option;
          
          foreach ($questionVM['optionVMs'] as $optionVM) {
            $option = new StdClass;
            $option->id = $optionVM['id'];
            $option->label = $optionVM['label'];
            $options[] = $option;
          }
          
          $question->options = $options;
          $questions[] = $question;
        }
        $page->questions = $questions;
        
        $pages[] = $page;
      }
      $formDef->pages=$pages;
      // final page
      $formDef->useFinalPage = $jSonArray['finalPageVM']['fieldVMs'][0]['booleanValue'];
      $formDef->finalMsg = $jSonArray['finalPageVM']['fieldVMs'][1]['textValue'];
      $formDef->finalButtonLabel = $jSonArray['finalPageVM']['fieldVMs'][2]['textValue'];
      
      return $formDef;
    }

    function cleanForm($exptId, $formType) {
      global $igrtSqli;
      // delete all current info about the form
      $cleanForm = "DELETE FROM fdStepForms WHERE exptId=$exptId AND formType=$formType";
      $igrtSqli->query($cleanForm);
      $cleanPages = "DELETE FROM fdStepFormsPages WHERE exptId=$exptId AND formType=$formType";
      $igrtSqli->query($cleanPages);
      $cleanQuestions = "DELETE FROM fdStepFormsQuestions WHERE exptId=$exptId AND formType=$formType";
      $igrtSqli->query($cleanQuestions);
      $cleanOptions = "DELETE FROM fdStepFormsQuestionsOptions WHERE exptId=$exptId AND formType=$formType";
      $igrtSqli->query($cleanOptions);
      $cleanQuestions = "DELETE FROM fdStepFormsEligibilityQuestions WHERE exptId=$exptId AND formType=$formType";
      $igrtSqli->query($cleanQuestions);
      $cleanOptions = "DELETE FROM fdStepFormsEligibilityQuestionsOptions WHERE exptId=$exptId AND formType=$formType";
      $igrtSqli->query($cleanOptions);
//      $cleanOptions = "DELETE FROM fdStepFormsGridValues WHERE exptId=$exptId AND formType=$formType";
//      $igrtSqli->query($cleanOptions);
      
    }
    
    function storeForm($formDef) {
      global $igrtSqli;
      // insert new definition
      $uipBool = $formDef->useIntroPage === true ? 1 : 0;
      $ueqBool = $formDef->useEligibilityQ === true ? 1 : 0;
      $dcBool = $formDef->definitionComplete === true ? 1 : 0;
      $useRCBool = $formDef->useRecruitmentCode === true ? 1 : 0;
      $makeForm=sprintf("INSERT INTO fdStepForms (exptId, formType, formTitle, formInst, finalMsg, finalButtonLabel, useIntroPage, "
        . "introPageTitle, introPageMessage, introPageButtonLabel, useEligibilityQ, definitionComplete, "
        . "useRecruitmentCode, recruitmentCodeLabel, recruitmentCodeMessage, recruitmentCodeOptionLabel, nullRecruitmentCodeOptionLabel) "
        . "VALUES('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
        $formDef->exptId, $formDef->formType, $formDef->formTitle, $formDef->formInst, $formDef->finalMsg, $formDef->finalButtonLabel,
        $uipBool, $formDef->introPageTitle, $formDef->introPageMessage, $formDef->introPageButtonLabel, $ueqBool, $dcBool,
        $useRCBool,  $formDef->recruitmentCodeLabel, $formDef->recruitmentCodeMessage, $formDef->recruitmentCodeOptionLabel, $formDef->nullRecruitmentCodeOptionLabel);
      $igrtSqli->query($makeForm);
      // echo $makeForm;
      
      // store eligibility questions
      $eqUseJTypeSelectorBool = $formDef->eqUseJTypeSelector === true ? 1 : 0;
      $makeEligibility = sprintf("INSERT INTO fdStepFormsEligibilityQuestions "
        . "(exptId, formType,  qType, qLabel,  qNonEligibleMsg, qUseJTypeSelector) "
        . "VALUES('%s', '%s', '%s', '%s', '%s', '%s')",
        $formDef->exptId, $formDef->formType, $formDef->eligibilityQType,
        $formDef->eligibilityQLabel, $formDef->qNonEligibleMsg, $eqUseJTypeSelectorBool);
      $igrtSqli->query($makeEligibility);
      //echo $makeEligibility;
      for ($oPtr=0; $oPtr<count($formDef->eligibilityOptions); $oPtr++) {
        $ierBool = $formDef->eligibilityOptions[$oPtr]->isEligibleResponse === true ? 1 : 0;
        $makeEligibilityOptions = sprintf("INSERT INTO fdStepFormsEligibilityQuestionsOptions (exptId, formType, label, displayOrder, isEligibleResponse, jType) "
          . "VALUES('%s', '%s', '%s', '%s', '%s', '%s')",
          $formDef->exptId, $formDef->formType, $igrtSqli->real_escape_string($formDef->eligibilityOptions[$oPtr]->label), $oPtr, $ierBool, $formDef->eligibilityOptions[$oPtr]->jType);
        $igrtSqli->query($makeEligibilityOptions);
        //echo $makeEligibilityOptions;
      }
      
      // build up queries with multiple VALUES statements as more efficient - avoid locking problems
      $makePage = "INSERT INTO fdStepFormsPages (exptId, formType, pNo, pageTitle, pageInst, pageButtonLabel, "
        . "contingentPage, contingentValue, contingentText, ignorePage, jType) VALUES";
      $makeQuestion = "INSERT INTO fdStepFormsQuestions "
        . "(exptId, formType, pNo, qNo, qType, qLabel, qIsFilter, qFilterValue, qValidationMsg, qMandatory) VALUES ";
      $makeOption = "INSERT INTO fdStepFormsQuestionsOptions (exptId, formType, pNo, qNo, label, displayOrder) VALUES ";
      //$makeGridColumns = "INSERT INTO fdStepFormsGridValues (exptId, formType, pNo, qNo, label, isRowLabel, colValue) VALUES ";
      //$makeGridRows = "INSERT INTO fdStepFormsGridValues (exptId, formType, pNo, qNo, label, isRowLabel, rowNo) VALUES ";
      for ($i=0; $i<count($formDef->pages); $i++) {
        $pageNo = $i;
        if ($i > 0) { $makePage.= ','; }
        $makePage.= sprintf("('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
          $formDef->exptId, $formDef->formType, $pageNo,
          $igrtSqli->real_escape_string($formDef->pages[$i]->pageTitle),
          $igrtSqli->real_escape_string($formDef->pages[$i]->pageInstruction),
          $igrtSqli->real_escape_string($formDef->pages[$i]->pageButtonLabel),
          $formDef->pages[$i]->contingentDef->contingentPage === true ? 1 : 0,
          $formDef->pages[$i]->contingentDef->contingentValue,
          $formDef->pages[$i]->contingentDef->contingentText,
          $formDef->pages[$i]->ignorePage === true ? 1 : 0,
          $formDef->pages[$i]->jType);
        //echo $makePage;
        // store the mandatoryfilterVM question
        if ($i>0)   // after first page
          $makeQuestion.= ',';
        $makeQuestion.= sprintf("('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
          $formDef->exptId, $formDef->formType, $pageNo, 0,
          $formDef->pages[$i]->mandatoryFilterDef->qType,
          $igrtSqli->real_escape_string($formDef->pages[$i]->mandatoryFilterDef->qLabel),
          $formDef->pages[$i]->mandatoryFilterDef->qIsFilter,
          -1,
          $igrtSqli->real_escape_string($formDef->pages[$i]->mandatoryFilterDef->qValidationMsg),
          $formDef->pages[$i]->mandatoryFilterDef->qMandatory === true ? 1 : 0,
        );
        // store the related options
        for ($k=0; $k<count($formDef->pages[$i]->mandatoryFilterDef->options); $k++) {
          if ($i>0 || $k>0) { $makeOption.= ','; }
          $makeOption.= sprintf("('%s', '%s', '%s', '%s', '%s', '%s')",
            $formDef->exptId, $formDef->formType, $pageNo, 0,
            $igrtSqli->real_escape_string($formDef->pages[$i]->mandatoryFilterDef->options[$k]->label),
            $formDef->pages[$i]->mandatoryFilterDef->options[$k]->id);
        }
        
        
        // store questions per page
        for ($j=0; $j<count($formDef->pages[$i]->questions); $j++) {
          
          //if (($i>0) || ($j>0)) { $makeQuestion.= ','; }
          $makeQuestion.= ',';
          $makeQuestion.= sprintf("('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            $formDef->exptId, $formDef->formType, $pageNo, $formDef->pages[$i]->questions[$j]->qNo,
            $formDef->pages[$i]->questions[$j]->qType,
            $igrtSqli->real_escape_string($formDef->pages[$i]->questions[$j]->qLabel),
            0,
            $formDef->pages[$i]->questions[$j]->qFilterValue,
            $igrtSqli->real_escape_string($formDef->pages[$i]->questions[$j]->qValidationMsg),
            $formDef->pages[$i]->questions[$j]->qMandatory === true ? 1 : 0,
          );
          // store options per question
          for ($k=0; $k<count($formDef->pages[$i]->questions[$j]->options); $k++) {
            //if (($i>0) || ($j>0) || ($k>0)) { $makeOption.= ','; }
            $makeOption.= ',';
            $makeOption.= sprintf("('%s', '%s', '%s', '%s', '%s', '%s')",
              $formDef->exptId, $formDef->formType, $pageNo, $formDef->pages[$i]->questions[$j]->qNo,
              $igrtSqli->real_escape_string($formDef->pages[$i]->questions[$j]->options[$k]->label),
              $formDef->pages[$i]->questions[$j]->options[$k]->id);
          }
        }
      }
      $igrtSqli->query($makePage);
      $igrtSqli->query($makeQuestion);
      $igrtSqli->query($makeOption);
//      $igrtSqli->query($makeGridColumns);
//      $igrtSqli->query($makeGridRows);
      
    }


// ----

ini_set('display_errors', 'On');
error_reporting(E_ALL);

$full_ws_path=realpath(dirname(__FILE__));
$root_path=substr($full_ws_path, 0, strlen($full_ws_path)-18);
include_once $root_path.'/domainSpecific/mySqlObject.php';     
include_once $root_path.'/helpers/parseJSON.php';

global $igrtSqli;

$rawBody = file_get_contents('php://input');
$jSonArray = json_decode($rawBody, true);

$formDef = getFormDef($jSonArray);


//$currentFocusControlId = $jSonArray['currentFocusControlId'];   // would need to implement this per user so probably implement later

cleanForm($formDef->exptId, $formDef->formType);
storeForm($formDef);

