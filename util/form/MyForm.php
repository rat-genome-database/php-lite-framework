<?php
/*
* ====================================================================
*
* License:      GNU General Public License
*
* Copyright (c) 2007 Centare Group Ltd.  All rights reserved.
*
* This file is part of PHP Lite Framework
*
* PHP Lite Framework is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2.1
* of the License, or (at your option) any later version.
*
* PHP Lite Framework is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* Please refer to the file license.txt in the root directory of this
* distribution for the GNU General Public License or see
* http://www.gnu.org/licenses/lgpl.html
*
* You should have received a copy of the GNU Lesser General Public
* License along with this library; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
* ====================================================================
*
*/
require 'Text.php';
require 'Textarea.php';
require 'Password.php';
require 'Select.php';
require 'ReadOnlySelect.php';
require 'MultipleSelect.php';
require 'ReportingMultipleSelect.php';
require 'CoolMultipleSelect.php';
require 'MultipleCheckbox.php';
require 'Radio.php';
require 'Checkbox.php';
require 'Hidden.php';
require 'ReadOnlyText.php';
require 'ReadOnlyCheckbox.php';
require 'CoolDate.php';
require 'FileUpload.php';
require 'Number.php';
require 'WholeNumber.php';
require 'PaddedNumber.php';
require 'HtmlEditor.php';
require 'ReadOnlyLabel.php';

// returns from the getState method, used for switch/case statement processing
define('SUBMIT_VALID', 1);
define('SUBMIT_INVALID', 2);
define('INITIAL_GET', 3);

/**

 */
class PLF_Form
{
  // for comparing changed stuff, need to store stuff in session
  var $originalElements = array();
  var $newElements = array();


  var $formErrorMessages = array();

  var $renderedElementNames = array();

  var $method;
  var $module;
  var $func;

  var $elements;

  var $tabIndex; // for creating tabindex for the IE bug
  var $coolDateNames; // array of cooldate names,
                      // for  generating
                      //the javascript at the end
                      // of the form
  var $haveReportingMultipleSelect = false; // holds whether we've added any "reporting"
                                            // type multiple selects
  var $haveCoolMultipleSelect = false; // holds whether we've added any "cool"
                                       // type multiple select controls 
  var $haveTextArea = false; // holds whether we've added any textareas to
                             // the form, for generating
                             // javascript text counter at end of form
  var $haveHtmlEditor = false;
  var $haveFileUpload = false; // holds whether we've added a file upload control
                               // to the form, for generating the enctype attribute
                               // on the form tag
  var $submitButtonText;
  var $initialFocusField; // name of field to set initial focus to using
                          // a javascript function
  var $initialSelectField; // name of field to set initial focus to using
                          // a javascript function (and also "select" it, to make it
                          // easy for the user to change the value (like for a search form when
                          // they may want to type a new search term without manually selecting
                          // the prev search term
  var $spellCheckFieldNames; // array of field names to generate spell checking code for
                             // using http://spellerpages.sourceforge.net/

  var $confirmText;
  
  var $useCheckboxes = false; // flag to indicate when the form should show checkboxes
                      // in front of every control... user will have to check 
                      // the box to indicate they want that field included in an 
                      // update statement
                      
  var $forceSecure = false; // flag to indicate if we should build the form action url using https
  /**
  Construct a Form
  $method (POST, or GET) use POST for updates/inserts, use GET for read only transactions
  this allows browsers to utilize the back button to return to search results without getting
  warnings about POST'ing data more than once.
  **/
  public function __construct($submitButtonText, $method, $module, $func, $formName) {
    static $formNumber;
    $formNumber += 1;
    $this->tabIndex = 1000 * $formNumber;
    $this->method = $method;
    $this->module = $module;
    $this->func = $func;
    $this->submitButtonText = $submitButtonText;
    $this->formName = $formName;
  }
  
  public function PLF_Form($submitButtonText, $method, $module, $func, $formName) {
    self::__construct($submitButtonText, $method, $module, $func, $formName);
  }

  function setUseCheckboxes() {
    $this->useCheckboxes = true;
  }
  
  function setSecure() {
    $this->forceSecure = true;
  }
  
  
  /**
   * Changes the submit button text that is set via the constructor
   */
  function setSubmitButtonText ($submitButtonText) {
    $this->submitButtonText = $submitButtonText;
  }

  /**
   * Set the text for a javascript popup message upon form submission, useful
   * to confirm that the user is really ready to submit the form
   */  
  function setConfirmText($confirmText) {
    $this->confirmText = $confirmText;
  }


  function addFormErrorMessage($errorMessage) {
    $this->formErrorMessages[] = $errorMessage;
  }

  function setSpellCheckFieldNames($fieldNames) {
  // treat argument as an array, ie, caller can pass as many field names as desired
  // ex:  $theForm->setSpellCheckFieldNames('description', 'summary', 'note');
    $this->spellCheckFieldNames = func_get_args($fieldNames);
  }

  function addElement(&$element){
    if (isset($this->elements[$element->name])) {
      logError('There is already an element in the form with name: '.$element->name);
    }
    else {
      $this->elements[$element->name] = $element;
    }
  }

  function addText($name, $label, $size, $maxLength, $required) {
    $this->addElement(new PLF_Text($name, $label, $size, $maxLength, $required));
  }
  
  function addNumber($name, $label, $minValue, $maxValue, $required) {
    $this->addElement(new PLF_Number($name, $label, $minValue, $maxValue, $required));
  }

  function addWholeNumber($name, $label, $minValue, $maxValue, $required) {
    $this->addElement(new PLF_WholeNumber($name, $label, $minValue, $maxValue, $required));
  }

  function addPaddedNumber($name, $label, $numDigits, $required) {
    $this->addElement(new PLF_PaddedNumber($name, $label, $numDigits, $required));
  }

  function addFileUpload($name, $label, $maxFileSizeBytes, $required) {
    $this->addElement(new PLF_FileUpload($name, $label, $maxFileSizeBytes, $required));
    $this->haveFileUpload = true;
  }

  function addTextarea($name, $label, $rows, $cols, $maxchars, $required) {
    $this->addElement(new PLF_Textarea($name, $label, $rows, $cols, $maxchars, $required));
    $this->haveTextArea = true;
  }

  function addHtmlEditor($name, $label, $rows, $cols, $maxChars, $required) {
    $this->addElement(new PLF_HtmlEditor($name, $label, $rows, $cols, $maxChars, $required));
    $this->haveHtmlEditor = true;
  }

  function addHidden($name, $value = NULL) {
    $this->addElement(new PLF_Hidden($name));
    if (isset($value)) {
      $this->setDefault($name, $value);
    }
  }

  function addReadOnlyText($name, $label, $size) {
    $this->addElement(new PLF_ReadOnlyText($name, $label, $size));
  }

  function addReadOnlyCheckbox($name, $label, $size) {
    $this->addElement(new PLF_ReadOnlyCheckbox($name, $label, $size));
  }

  function addPassword($name, $label, $maxlength, $size, $required) {
    $this->addElement(new PLF_Password($name, $label, $maxlength, $size, $required));
  }

  function addCoolDate($name, $label, $required) {
    $this->addElement(new PLF_CoolDate($name, $label, $required));
    $this->coolDateNames[] = $name;
  }

  function addSelect($name, $label, $values, $required, $onChange = NULL) {
    $this->addElement(new PLF_Select($name, $label, $values, $required, $onChange));
  }
  
  function addReadOnlySelect($name, $label, $values, $size) {
    $this->addElement(new PLF_ReadOnlySelect($name, $label, $values, $size));
  }

  /**
   * Add a standard multiple select form control.
   * 
   * See addReportingMultipleSelect and addCoolMultipleSelect
   */
  function addMultipleSelect($name, $label, $values, $size, $required) {
    $this->addElement(new PLF_MultipleSelect($name, $label, $values, $size, $required));
  }
  
  /**
   * Add a "reporting" multiple select form control.  This type of control
   * is a normal multiple select, but additionally, it uses javascript
   * to dynamically update the label of the control with the values
   * that have been selected in the list.  This allows the user to 
   * easily see which values have been selected, in the event that not
   * all the values can be seen at once in the select control.
   * 
   * See addMultipleSelect and addCoolMultipleSelect
   */
  function addReportingMultipleSelect($name, $label, $values, $size, $required) {
    $this->addElement(new PLF_ReportingMultipleSelect($name, $label, $values, $size, $required));
    $this->haveReportingMultipleSelect = true;
  }
  
  /**
   * Add a "cool" multiple select form control.  This will decorate
   * the normal listbox with checkboxes beside each item in the list.
   * Some users will prefer this look as they can easily check and
   * uncheck multiple items, without having to hold down the ctrl
   * or shift key.
   * 
   * The javascript requires this notice:
   * --------------------------------------------------
   * coded by Kae - kae@verens.com
   * I'd appreciate any feedback.
   * You have the right to include this in your sites.
   * Please retain this notice.
   * --------------------------------------------------
   * 
   * Note: if you add one of these to the form, all the multiple
   * selects in the form will have this style, due to the way the javascript
   * is implemented.  This is an area where a javascript expert
   * or maybe kae@verens.com
   * could assist and change it to take the names of the controls
   * to decorate, instead of just doing all the selects on the page.
   * 
   * Please post to the forum on sourceforge if interested in
   * working on this.
   * 
   * See addReportingMultipleSelect() and addMultipleSelect()
   */
  function addCoolMultipleSelect($name, $label, $values, $size, $required) {
    $this->addElement(new PLF_CoolMultipleSelect($name, $label, $values, $size, $required));
    $this->haveCoolMultipleSelect = true;
  }

  function addMultipleCheckbox($name, $label, $values, $required, $delimiter = '<br/>') {
    $this->addElement(new PLF_MultipleCheckbox($name, $label, $values, $required, $delimiter));
  }

  function addRadio($name, $label, $values, $required, $delimiter = '<br/>') {
    $this->addElement(new PLF_Radio($name, $label, $values, $required, $delimiter));
  }

  function addCheckbox($name, $label) {
    $this->addElement(new PLF_Checkbox($name, $label));
  }
  
  function addReadOnlyLabel($name, $label) {
    $this->addElement(new PLF_ReadOnlyLabel($name, $label));
  }

  /**
   * Add extra attributes to the specified field, for example:
   * if you want onchange="someJavascriptMethod()"
   * just do
   * $theForm->setFieldAttribute('FIRSTNAME', 'onchange="someJavascriptMethod"');
   */
  function setFieldAttribute($name, $attribute) {
    $element = &$this->elements[$name];
    if (isset($element)) {
      $element->attribute = $attribute;
    }
    
  }
  
  
 // remove a field so that it's not used when building fields for a db insert or update,
// useful for removing a password confirmation field.
// TODO
// better solution:
// have a passwordConfirm element that will render 2 controls, and its validate
// method will compare the text and render an error message if they don't match.
// do this when have more time
  function removeField($fieldName) {
    unset($this->elements[$fieldName]);
  }
  
  /**
   * Gets the label of the named field
   */
  function getLabel($fieldName) {
    if (isset($this->elements[$fieldName])) {
      $element = $this->elements[$fieldName];
      return $element->getLabel();
    }
  }
  
  
  /**
   * Can be used to get the values of the form as an associative array
   * 
   * Useful if you want to "extract()" them into local variables named after the field names
   * 
   * So instead of:
   * echo 'first name is: '.$theForm->getValue('FIRSTNAME');
   * echo 'last name is: '.$theForm->getValue('LASTNAME');
   * 
   * You can do:
   * extract($theForm->getValues());
   * echo 'first name is: '.$FIRSTNAME;
   * echo 'last name is: '.$LASTNAME;
   * 
   */
  function getValues() {
    $values = array();
    foreach ($this->elements as $element) {
      $values[$element->name] = $element->getValue();
    }
    return $values;
  }

  function getAllValues() {
    $values = array();
    foreach ($this->elements as $element) {
      $values[$element->name] = $element->getValue();
    }
    $values['hiddenXYZ123'] = null;
    return $values;
  }

  function getValuesAsUrl($names) {
    $namesArray = func_get_args();
    $toReturn = '';
    $pairs = array();
    foreach ($namesArray as $name) {
      $pairs[] = $name.'='.$this->getValue($name);
    }
    return implode('&', $pairs);
  }


  /**
   * See getValues()
   * 
   * Only difference is it calls the getValueForDb method on each Element,
   * which will give us the desired db representation of the element, instead
   * of the native datatype that it may be holding (ie. in the case of multiple
   * selection list boxes, which store an array internally, but return a
   * a delimited string from the getValueForDb() method)
   */
  function getValuesForDb() {
    $values = array();
    foreach ($this->elements as $element) {
      $values[$element->name] = $element->getValueForDb();
    }
    return $values;
  }
  
  function getValue($fieldName) {
    if (isset($this->elements[$fieldName])) {
      $element = $this->elements[$fieldName];
      return $element->getValue();
    }
    else {
      logNotice("You're attempting to access a form field named $fieldName, however, a field with this name does not exist in the form");
    }
    
  }

  function getValueForDb($fieldName) {
    if (isset($this->elements[$fieldName])) {
      $element = $this->elements[$fieldName];
      return $element->getValueForDb();
    }
    else {
      logNotice("You're attempting to access a form field named $fieldName, however, a field with this name does not exist in the form");
    }
        
  }

  function getValueAndRemove($fieldName) {
    $valueToReturn = $this->getValue($fieldName);
    $this->removeField($fieldName);
    return $valueToReturn;
  }

  function getValueForDbAndRemove($fieldName) {
    $valueToReturn = $this->getValueForDb($fieldName);
    $this->removeField($fieldName);
    return $valueToReturn;
  }

  /**
  Intended to be called for MultipleSelect and MultipleCheckbox
  note: maybe check for this
  function getValues($fieldName) {
    if (isset($this->elements[$fieldName])) {
      $element = $this->elements[$fieldName];
      return implode(',', $element->getValue());
    }
  }
  **/

  function setInitialFocusField($fieldName) {
    $this->initialFocusField = $fieldName;
  }

  function setInitialSelectField($fieldName) {
    $this->initialSelectField = $fieldName;
  }
  
  /**
   * For development time, used to get a comma separated list of all the
   * tags in the form, useful for methods that need comma separated lists
   * of tag names, like renderLabeledFieldsInColumns(3, 'NAME', 'ADDRESS', 'CITY');
   * 
   * usage: 
   * 
   * Just call it, and then copy the output that displays in the browser and use
   * segments of the field names in your php file.  When finished, remove the 
   * call to this method from your code.
   */
  function dumpTags() {
    foreach ($this->elements as $element) {
      echo "'".$element->getName()."', ";
    }
    
  }
  
  // use formStart, renderField, renderLabel, renderLabeledField, etc, formEnd
  // to control your own layout
  function formStart() {
    $toReturn = '';
    $onSubmit = '';
    
    if ($this->useCheckboxes) {
      $toReturn .= '<script LANGUAGE="JavaScript">
<!-- Begin
function toggleTheCheckbox(name) {
  var thecontrol = document.getElementById(name);     
  thecontrol.checked = true;
} 
//  End -->
</script>
';
    }
    
    
    if (isset($this->confirmText)) {
      $toReturn .= '<script LANGUAGE="JavaScript">
<!-- Begin
function verify() {
  if (confirm("'.$this->confirmText.'")) {
    return true;
  }else{
    return false;
  }
} 
//  End -->
</script>
';
      $onSubmit = 'onSubmit = "return verify();" ';   
    }
    
    $toReturn .=  '<form '.$onSubmit.' name="'.$this->formName.'"  id="'.$this->formName.'" method="'.$this->method.'" action="'.makeUrl($this->module, $this->func, null, $this->forceSecure).'"';
    if ($this->haveFileUpload) {
      $toReturn .= 'enctype="multipart/form-data"';
    }
    $toReturn .= ' >';
    foreach ($this->formErrorMessages as $error) {
      $toReturn .= '<div style="color:red;font-size:107%;text-align:center">'.$error.'</div>';
    }
    foreach ($this->elements as $element) {
      if (isset($element->requiredText)) {
        $toReturn .= '<div style="color:red;font-size:107%;text-align:center">Please correct the errors noted below</div>';
        break;
      }
    }

    if ($this->haveReportingMultipleSelect) {
      $toReturn .= '<script type="text/javascript">
        function selectMultipleChanged(fieldname) {
          var messageDiv = document.getElementById(fieldname+"-div");
          var theControl = document.getElementById(fieldname);
        
          var strTemp = "";
          for(var i = 0;i < theControl.length; i++){
            if(theControl.options[i].selected == true){
              strTemp += theControl.options[i].innerHTML + ", ";
            }
          }
          var theString = strTemp.substring(0,strTemp.length-2);
          if (theString == "") {
            messageDiv.innerHTML = "&nbsp;";
          }
          else {
            messageDiv.innerHTML = ": "+theString;
          }
        
        }
        </script>
        ';
    }
    $frameworkUrl = getFrameworkUrl();
    if ($this->haveCoolMultipleSelect) {
      setHeadContent('<script type="text/javascript" src="'.$frameworkUrl.'/thirdParty/multipleSelectMod/multiselect.js"></script>');
    }
    if ($this->haveHtmlEditor) {
//      $toReturn .= '<script type="text/javascript" src="'.$frameworkUrl.'/thirdParty/htmlEditor/fckeditor/fckeditor.js"></script>';
      $toReturn .= '<script type="text/javascript" src="'.$frameworkUrl.'/thirdParty/htmlEditor/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>';
    }
    return $toReturn;
  }

  function startGroup($label) {
    return "<fieldset><legend>$label</legend>";
  }

  function endGroup() {
    return '</fieldset>';
  }

  /**
   * Renders the label for the specified field, if it is present in the form
   */
  function renderLabel($fieldName) {
    if (isset($this->elements[$fieldName])) {
      $element = &$this->elements[$fieldName];
      return $element->renderLabel();
    }
  }

  /**
   * Renders the specified field, if it is present in the form
   */
  function renderField($fieldName) {
    if (isset($this->elements[$fieldName])) {
      $element = &$this->elements[$fieldName];
      $toReturn = '';
      // special handling if setUseCheckboxes was called on the form
      if ($this->useCheckboxes && (strcasecmp(get_class($element), 'plf_hidden') != 0)) {
      //if ($this->useCheckboxes) {
        $toReturn .= '<input type="checkbox" tabindex="'.$this->tabIndex.'" name="PLF_CB_'.$fieldName.'" id="PLF_CB_'.$fieldName.'"';
        $submittedValue = getRequestVarString('PLF_CB_'.$fieldName); 
        if ('on' == $submittedValue || 't' == $submittedValue) {
          $toReturn .= ' checked ="checked"';
        } 
        $toReturn .= ' />';
        $element->setAttribute(" onkeyup=toggleTheCheckbox(\"PLF_CB_".$fieldName."\") onchange=toggleTheCheckbox(\"PLF_CB_".$fieldName."\")");
      } 
      $this->tabIndex += 1;
      $toReturn .= $element->render($this->tabIndex);
      $this->tabIndex += 1;
      $this->renderedElementNames[] = $fieldName;
      return $toReturn;
    }
  }
 
  /**
   * Renders the specified fields in a div with 1 pixel of padding, since Firefox
   * seems to squeeze the controls a bit too close together.
   */
  function renderLabeledFields($fieldNames) {
    if (is_array($fieldNames)) {
      $fieldArray = $fieldNames;
    }
    else {
      $fieldArray = func_get_args();
    }
    $fields = '';
    foreach ($fieldArray as $field) {
      $fields .= $this->renderLabeledField($field);
    }
    if (strlen($fields) > 0) {
      return '<div style="padding:1px;">'.$fields.'</div>';
    }    
  }

  /**
   * Generates a table of the provided fields, in the number of columns
   * specified in the first parameter,  ex:
   * 
   * $theform->renderLabeledFieldsInColumns(1, 'FIRST', 'LAST', 'ADDRESS', 'CITY');
   * would generate:
   * -----------------------------------------------
   * | Enter your first name  |                    |
   * -----------------------------------------------
   * | Enter your last name   |                    |
   * -----------------------------------------------
   * | Enter your address     |                    |
   * -----------------------------------------------
   * | Enter your city        |                    |
   * -----------------------------------------------
   *
   * 
   * while
   *   
   * $theform->renderLabeledFieldsInColumns(2, 'FIRST', 'LAST', 'ADDRESS', 'CITY');
   * would generate:
   * --------------------------------------------------------------------------------
   * | Enter your first name |            | Enter your last name      |             |
   * --------------------------------------------------------------------------------
   * | Enter your address    |            | Enter your city           |             |
   * --------------------------------------------------------------------------------
   * 
   * If you forget to provide a numeric field for the first element, a runtime error
   * will be raised
   * 
   */

  function renderLabeledFieldsInColumns($colsAndFieldNames) {
    if (is_array($colsAndFieldNames)) {
      $fieldArray = $colsAndFieldNames;
    }
    else {
      $fieldArray = func_get_args();
    }
    $fields = '';
    if (is_numeric($fieldArray[0])) {
      $across = $fieldArray[0];
      unset($fieldArray[0]);
    }
    else {
      logError('first parameter to renderLabeledFieldsInColumns must be numeric (ie. the number of desired columns)');
      return;
    }
    $buildUp = '';
    $count = 1;
    // if no fields passed, use all of them:
    if (empty($fieldArray)) {
      $fieldArray = $this->getVisibleElementNames();
    }
    foreach ($fieldArray as $field) {
      $cur = $this->renderLabeledFieldInTd($field);
      if ($count % $across == 0) {
        // time to do a row of the table
        $fields .= '<tr>'.$buildUp.$cur.'</tr>';
        $buildUp = '';
      }
      else {
        // keep building up, not done with the row yet...
        $buildUp .= $cur;
      }
      $count++;
    }

    // first, take care of any partially built rows:    
    if (strlen($buildUp) > 0) {
      $fields .=  '<tr>'.$buildUp.'</tr>';
    }
    // then, output the table html if there are any fields
    // to show:
    if (strlen($fields) > 0) {
      return '<table width="100%" border="0">'.$fields.'</table>';
    }
  }


  /**
   * Renders the specified field with its label to the left
   */
  function renderLabeledField($fieldName) {
    $label = $this->renderLabel($fieldName);
    $field =  $this->renderField($fieldName);
    if (strlen($label.$field) > 0) {
      return $label.' '.$field.' ';
    }
  }

  /**
   */
  function renderLabeledFieldInTd($fieldName) {
    $label = $this->renderLabel($fieldName);
    $field =  $this->renderField($fieldName);
    if (isReallySet($label)) {
      $label = '<td>'.$label.'</td>';
    }
    if (isReallySet($field)) {
      $field = '<td>'.$field.'</td>';
    }
   
    return $label.$field;
  }

  function formEnd() {
    $toReturn = '';
    if (count($this->spellCheckFieldNames) > 0) {
      $toReturn .= '<input type="button" value="Check Spelling" onClick="openSpellChecker();"/>&nbsp;';
    }
    $toReturn .= '<input type="submit" tabindex="'.$this->tabIndex.'" name="submitBtn" value="'.$this->submitButtonText.'" />';

    // render a special hidden element that we use to know if the form has been submitted
    $toReturn .= '<input type="hidden" name="hiddenXYZ123"/>';

    // render hidden fields for the module name and func name
    // to get around problem when the method is GET
    // mentioned here: http://p2p.wrox.com/topic.asp?whichpage=1&TOPIC_ID=19391

    $toReturn .= '<input type="hidden" name="module" value="'.$this->module.'"/>';
    $toReturn .= '<input type="hidden" name="func" value ="'.$this->func.'"/>';

    // now render any hidden elements that have not already been rendered
    foreach ($this->elements as $element) {
      if ((strcasecmp(get_class($element), 'plf_hidden') == 0) && !in_array($element->name, $this->renderedElementNames)) {
        $toReturn .= $element->render(0);
      }
    }
    $toReturn .= '</form>';
    // if there have been any cooldate elements added to this form, render appropriate
    // javascript references and methods to control the calendar popup
    // NOTE: these urls attempt to locate the css and javascript from the dhtml
    // calendar widget that is included with the framework.  The widget allows customization
    // in a file named calendar-setup.js, which these urls will attempt to find in the
    // conf directory for your particular project (ie. outside the phpLiteFramework directory)
    if (sizeof($this->coolDateNames) > 0) {
      $frameworkUrl = getFrameworkUrl();
      $toReturn .= '
<style type="text/css">@import url('.$frameworkUrl.'/thirdParty/dhtmlCalendar/jscalendar-1.0/calendar-system.css'.');</style>
<script type="text/javascript" src="'.$frameworkUrl.'/thirdParty/dhtmlCalendar/jscalendar-1.0/calendar.js'.'"></script><script type="text/javascript" src="'.$frameworkUrl.'/thirdParty/dhtmlCalendar/jscalendar-1.0/lang/calendar-en.js'.'"></script><script type="text/javascript" src="'.'project/conf/calendar-setup.js'.'"></script>';

      $toReturn.='<script type="text/javascript">';
      foreach($this->coolDateNames as $coolDateName) {
          if (in_array($coolDateName, $this->renderedElementNames)) {
            $toReturn .= 'Calendar.setup(
            {
              inputField  : "'.$coolDateName.'",
              button      : "'.$coolDateName.'trigger"
            }
          );';
          }
      }
      $toReturn .='</script>';



    }
    
    // if a textarea has been added to this form, render the appropriate javascript
    // to update the remaining characters countdown thingy
    /* NOT necessary for the profile manager, plus ran into problems
    // with the javascript not working with some of the linux browsers, (firefox, mozilla)
    // did work with konqueror and opera though...
    if ($this->haveTextArea) {
      $toReturn.='<SCRIPT LANGUAGE="JavaScript">
        <!-- Original:  Ronnie T. Moore -->
        <!-- Web Site:  The JavaScript Source -->

        <!-- Dynamic fix by: Nannette Thacker -->
        <!-- Web Site: http://www.shiningstar.net -->

        <!-- This script and many more are available free online at -->
        <!-- The JavaScript Source!! http://javascript.internet.com -->

        <!-- Begin
        function textCounter(field, countfield, maxlimit) {
          if (field.value.length > maxlimit) // if too long...trim it!
            field.value = field.value.substring(0, maxlimit);
          // otherwise, update characters left counter
          else
            countfield.value = maxlimit - field.value.length;
        }
        // End -->
        </script>';

    }*/
/*    if (!isset($this->initialFocusField)) {
      $editableElements = $this->getEditableElements();
      if (0 < count($editableElements)) {
        $firstElement = current($this->getEditableElements());
        $this->setInitialFocusField($firstElement->name);
      }
    }
*/
    if (isset($this->initialFocusField)) {
      $toReturn .= '
        <script type="text/javascript" language="JavaScript">
          <!--
          var focusControl = document.forms["'.$this->formName.'"].elements["'.$this->initialFocusField.'"];

          if (focusControl.type != "hidden") {
            focusControl.focus();
          }
          // -->
        </script>';

    }
    if (isset($this->initialSelectField)) {
      $toReturn .= '
        <script type="text/javascript" language="JavaScript">
          <!--
          var focusControl = document.forms["'.$this->formName.'"].elements["'.$this->initialSelectField.'"];

          if (focusControl.type != "hidden") {
            focusControl.focus();focusControl.select();
          }
          // -->
        </script>';

    }
    if (count($this->spellCheckFieldNames) > 0) {
      $toReturn .=
'      <script src="internal/speller/spellChecker.js"></script>
        <script>
        function openSpellChecker() {
';

          foreach ($this->spellCheckFieldNames as $field) {
            $toReturn .= 'var '.$field.' = document.forms["'.$this->formName.'"].elements["'.$field.'"]
            ';
          }

          $toReturn .= '
          var speller = new spellChecker('.implode(',', $this->spellCheckFieldNames).');

          speller.openChecker();
        }
        </script>
';
    }
    return $toReturn;

  }


  // use quickRender to get the fields rendered in order
  // useful for prototyping, then lay it out nicer later
  // using formStart, renderField, renderLabel, renderLabeledField, formEnd
  function quickRender() {
    $toReturn = $this->formStart();
    $toReturn .= '<table width="100%" id="form">';
    foreach ($this->elements as $element) {
      $elementName = $element->name;
      if ((strcasecmp(get_class($element), 'readonlylabel') == 0)) {
        $toReturn .= '<tr><td colspan="2">'.$this->renderLabel($elementName).'</td></tr>';
      }
      else if ((strcasecmp(get_class($element), 'plf_hidden') != 0)) {
        $toReturn .= '<tr><td class="plf_formlabel">'.$this->renderLabel($elementName).'</td><td class="plf_formfield">'.$this->renderField($elementName).'</td></tr>';
      }
      else {
        $toReturn .= $this->renderField($elementName);
      }
    }
    $toReturn .= '</table>';
    $toReturn .= $this->formEnd();
    return $toReturn;
  }

  // takes an array of default values (usually either an associative array
  // read from the db for the current record to be updated, or from previous
  // values on the form if redrawing with error messages)
  function setDefaults($defaults) {
    if (isset($defaults)) {
      foreach ($defaults as $key=>$value) {
        if (isset($this->elements[$key])) {
          // use & here since want to work on reference to the element
          // not a copy!  LOVE PHP4!
          $currentElement = &$this->elements[$key];
          $currentElement->setValue($value);
        }
      }
    }
  }

  function setDefaultsFromTable($tableNamePrefix, $defaults) {
    if (isset($defaults)) {
      foreach ($defaults as $key=>$value) {
        $keyToUse = $tableNamePrefix.'-'.$key;
        // use & here since want to work on reference to the element
        // not a copy!  LOVE PHP4!
        if (isset($this->elements[$keyToUse])) {
          $currentElement = &$this->elements[$keyToUse];
          $currentElement->setValue($value);
        }
      }
    }
  }

  /*
   * Sets the label of the specified field.  Useful if you want to change
   * the previously set label for a specific field.
   */
  function setLabel($fieldName, $label) {
    if (isset($this->elements[$fieldName])) {
      $currentElement = &$this->elements[$fieldName];
      $currentElement->setLabel($label);
    }
  }
  
  function appendLabel($fieldName, $additionalLabelText) {
    $this->setLabel($fieldName, $this->getLabel($fieldName).$additionalLabelText);
  }


  function setDefault($fieldName, $value) {
    if (isset($this->elements[$fieldName])) {
      $currentElement = &$this->elements[$fieldName];
      $currentElement->setValue($value);
    }
  }

  function getState() {
    // $_REQUEST contains get, post, and cookies
    // is this really good to do??
    // vs specifically doing GET or POST and not cookies
    $cleanFields = cleanArray($_REQUEST);
    $this->setDefaults($cleanFields);
    if (isset($cleanFields['hiddenXYZ123'])) {
      if ($this->isValid()) {
        return SUBMIT_VALID;
      }
      else {
        return SUBMIT_INVALID;
      }
    }
    else {
      return INITIAL_GET;
    }
  }

/*  function hasBeenSubmitted() {
    return isset($cleanFields['hiddenXYZ123']);
  }*/

  // only call if hasBeenSubmitted has returned true
  function isValidOld() {
    $isValid = true;
    foreach (array_keys($this->elements) as $key) {
      // use & here since want to work on reference to the element
      // not a copy!  LOVE PHP4!
      $currentElement = &$this->elements[$key];
      // using ternary operator here to avoid php warning when
      // indexing into an array ($cleanFields) looking for an index
      // which may not exist (in the case of checkboxes and the multiple
      // selection control) which don't put their value into the array
      // if the checkbox is not set or if nothing is selected
      $returnVal = $currentElement->validate();
      // "if at least one returns not valid, whole form is not valid"
      // so AND the results to get final result
      $isValid = $isValid && $returnVal;
    }
    // finally, check to see if any general form error msgs have been
    // added to the form
    if (count($this->formErrorMessages) > 0) {
      $isValid = false;
    }
    return $isValid;
  }

  function isValid() {
    $isValid = true;
    $elements = $this->getEditableElements();
    foreach (array_keys($elements) as $key) {
      // use & here since want to work on reference to the element
      // not a copy!  LOVE PHP4!
      $currentElement = &$this->elements[$key];
      // using ternary operator here to avoid php warning when
      // indexing into an array ($cleanFields) looking for an index
      // which may not exist (in the case of checkboxes and the multiple
      // selection control) which don't put their value into the array
      // if the checkbox is not set or if nothing is selected
      $returnVal = $currentElement->validate();
      // "if at least one returns not valid, whole form is not valid"
      // so AND the results to get final result
      $isValid = $isValid && $returnVal;
    }
    // finally, check to see if any general form error msgs have been
    // added to the form
    if (count($this->formErrorMessages) > 0) {
      $isValid = false;
    }
    return $isValid;
  }

  function getEditableElements() {
    $editableElements = array();
    foreach ($this->elements as $element) {
      // WHEN converted fully to PHP5, can use stripos here:
      // also seeing if we're set to use checkboxes... if so, be
      // sure the checkbox
      // has been checked for the particular element in the loop
      if (false === strpos(strtoupper(get_class($element)), 'READONLY') && (!$this->useCheckboxes || 'on' == getRequestVarString('PLF_CB_'.$element->getName()) || 't' == getRequestVarString('PLF_CB_'.$element->getName()) )) {
        $editableElements[$element->getName()] = $element;
      }      
    }
    return $editableElements;
  }

  function getVisibleElementNames() {
    $visibleElements = array();
    foreach ($this->elements as $element) {
      if (strcasecmp(get_class($element), 'plf_hidden') != 0) {
        $visibleElements[] = $element->getName();
      }
    }
    return $visibleElements;
  }

  function snapshotOriginalElements() {
    setSessionVar($this->formName.'originalElements', $this->getEditableElements());
  }

  function snapshotNewElements() {
    setSessionVar($this->formName.'newElements', $this->getEditableElements());
  }

  function getChangedElementsArray() {
    $toReturn = array();
    $this->snapshotNewElements();
    $originalElements = getSessionVar($this->formName.'originalElements');
    $newElements = getSessionVar($this->formName.'newElements');
    foreach ($originalElements as $originalElement) {
      $origValue = $originalElement->getValue();
      if (isset($newElements[$originalElement->name])) {
        $newElement = $newElements[$originalElement->name];
        $newValue = $newElement->getValue();
        if (strcmp($origValue, $newValue) != 0) {
          $toReturn[$originalElement->name] = $newValue;
        }
      }
    }
    $this->snapshotOriginalElements();
    return $toReturn;
  }

  function getChangedElementsReport() {
    $toReturn = '';
    $this->snapshotNewElements();
    $originalElements = getSessionVar($this->formName.'originalElements');
    $newElements = getSessionVar($this->formName.'newElements');
    foreach ($originalElements as $originalElement) {
      $origValue = $originalElement->getValueForDb();
      if (isset($newElements[$originalElement->name])) {
        $newElement = $newElements[$originalElement->name];
        $newValue = $newElement->getValueForDb();
        if (strcmp($origValue, $newValue) != 0) {
          $toReturn .= 'Field labeled <'.$newElement->getLabel().'> changed from <'.$origValue.'> to <'.$newValue.'>'."\n";
        }
      }
    }
    $this->snapshotOriginalElements();
    return $toReturn;
  }

}
?>
