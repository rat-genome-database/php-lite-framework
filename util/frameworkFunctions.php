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
*/

define('SESSION_PREFIX', 'PLF_');
define('FRONT_OF_URL', '?');
define('UNIQUE_PREFIX', 'UNIQ_');

// set a custom error handler function that will be called
// whenever the code calls trigger_error("string error msg" , int error type);
// this method is defined in this file
set_error_handler('userErrorHandler');


function rememberLastScreen() {
  $arr = array();
  $arr['args'] = getCurrentArgsArray();
  $arr['module'] = getModuleName();
  $arr['func'] = getFuncName();
  setSessionVar(SESSION_PREFIX.'lastScreen', $arr);
}

function recallLastScreenUrl() {
  $arr = getSessionVar(SESSION_PREFIX.'lastScreen');
  if (isset($arr)) {
    extract($arr);
    return makeUrl($module, $func, $args);
  }
  else{
    return;
  }
}


/**
 * Place a lock named $lockName, if there is not already a lock present
 * with that name.  If there is no lock present, function will return
 * true, if there is a lock present, function will return false.
 * 
 * Optional parameter $maxSeconds, if set will be used to determine a
 * maximum number of seconds to wait for the lock to release.  If
 * the lock file is older than $maxSeconds, then it will remove the
 * lock file, place a new one, and return true.  It will also log
 * an error, so that if the project is configured to email an administrator
 * upon errors, the admin will be notified about a possible dead
 * process.  
 * 
 * This function is useful for implementing a cron job using php:

// below is an example of a standalone php file placed in the root of the 
// project directory.  This file can be scheduled to be called via cron
// at any frequent interval, and it will never run more than one instance at
// a time, due to the use of getCronLock and releaseCronLock
// Unless, of course, the proces runs longer than 2 minutes (120 sec)
require 'index.php';
echo date("r")." ------ start of cron job\n";

if (getCronLock('crontest', 120)) {
  // do your work that takes a long time here
  sleep(20); // for testing
  releaseCronLock('crontest');
  echo date("r")." ----- successful run of cron job\n";  
}
else {
  echo date("r")."- couldn't get cron lock, skipping the processing this time\n";
}

 */
function getCronLock($lockName, $maxMinutes = null) {
  $thisPID = getmypid();
  $matchingFiles = glob("lock.$lockName.*.lock");
//  if (file_exists("lock.$lockName.lock")) {
  if (count($matchingFiles) > 0) {
//    $contents = file_get_contents("lock.$lockName.lock");
    $contents = file_get_contents($matchingFiles[0]);
    list($date,$pid) = explode("|", $contents);
    list($year, $month, $day, $hour, $minute, $second) = explode(' ', $date);
    $lastLockPlaced = mktime($hour, $minute, $second, $month, $day, $year);
    $now = time();
    $secondsSince = ($now - $lastLockPlaced);
    if (isset($maxMinutes) && ($secondsSince/60.0) > $maxMinutes) {
      logError("A lock named: $lockName with PID: $pid was started on ".date('r', $lastLockPlaced)." but still hadn't been released as of ".date('r', $now).", which is $maxMinutes minutes later.  Your desired max minutes to wait is $maxMinutes so I'm releasing that lock and starting a new one for this process, with PID: $thisPID, thus allowing this process to run.  You may want to investigate since there may be a zombie process with a start date/time of: ".date('r', $lastLockPlaced)." and a PID of $pid  If this process eventually ends cleanly, you should get another message so you will know you need to increase the maxMinutes parameter.");
      // remove the file
      unlink("lock.$lockName.$pid.lock");
      // set contents to null for later logic so that it will create a new file
      $contents = null;
    }
  }
  if (isset($contents)) {
    return false;
  }
  else {
    file_put_contents("lock.$lockName.$thisPID.lock", date('Y m d H i s').'|'.$thisPID);
    return $thisPID;
  }
}

/**
 * Releases the lock named $lockName. Please see documentation for getCronLock for 
 * details and an example.
 */
function releaseCronLock($lockName, $pid) {
  $filename = "lock.$lockName.$pid.lock";
  if (file_exists($filename)) {
    unlink($filename);
  } 
  else {
    logError("A process with PID $pid ended and tried to release its lock named $lockName, but the lock file was gone.  This may be because it ran too long and another process deleted the lock file.  You might consider increasing the maxMinutes parameter in the call to getCronLock() since this process is obviously running longer than you expected.  See documention for the getCronLock() and releaseCronLock() functions for more information.");
  } 
}


/**
 * This was ported from a function I wrote for a java component library back in the day 
 * (March 2003)
 * http://jira.opensymphony.com/browse/CORE-42
 * 8/8/08 WR
 * 
 * It converts initial spaces on a line to &nbsp;, to achieve the same indention that the
 * user who typed the spaces into the textarea intended to have.  
 * It also converts newlines to br's 
 * 
 * Therefore:

this: 

test
 indented one line
  indented 2 lines

becomes:
 
test<br/>&nbsp;indented one line<br/>&nbsp;&nbsp;indented 2 lines

 */
function goodNl2br ($string) {
  $justAfterLineBreak = true;
  $toReturn = '';
  for ($i=0; $i<strlen($string); $i++) {
    $curChar = $string[$i];
    if ($justAfterLineBreak)
    {
      if ($curChar == ' ')
      {
        $toReturn .= "&nbsp;";
      }
      elseif ($curChar=="\n") {
        $toReturn .= '<br/>';
      }
      else
      {
        $toReturn .= $curChar;
        $justAfterLineBreak = false;
      }
    }
    else
    {
      if ($curChar=="\n")
      {
        $toReturn .= '<br/>'; 
        $justAfterLineBreak = true;
      }
      else
      {
        $toReturn .= $curChar;
      }
    }
  }
  return $toReturn;
}



/**
 * Creates forward/back style paging links for large result sets.  If doing database
 * operations to get the "paged" results, you will use fetchRecordsLimit().  You may
 * also use web services if they offer paging features.
 * 
 * @param $count the total number of records in the result set
 * @param $max the number you want to show at a time
 * @param $start the index of the starting point for this chunk of results
 * @param $prevText the text to display for the "previous" link (ex: <-----)
 * @param $nextText the text do display for the "next" link (ex: ------>)
 * @param $form the form object that the user submitted to get this result (used to build the forward and back links)
 * @param $formStartFieldName the name of the field in the form that is used as the start index
 */
function doPagingLinks($count, $max, $start, $prevText, $nextText, $form, $formStartFieldName) {
  $pagingLinks = '';
  if (null == $form) {
    $module = getRequestVarString('module');
    $func = getRequestVarString('func');
    $formValues = getCurrentArgsArray();    
  }
  else {
    $module = $form->module;
    $func = $form->func;
    $formValues = $form->getAllValues();
  }
  if ($count > $max) {
    $backward = $start - $max;
    if ($backward >= 0) {
      $formValues[$formStartFieldName] = $backward;
      $pagingLinks .= makeLink($prevText, $module, $func, $formValues);
    }
    else {
      $pagingLinks .= $prevText;
    } 
    $pagingLinks .= " ";
    $forward = $start + $max;
    if ($forward < $count) {
      $formValues[$formStartFieldName] = $forward;
      $pagingLinks .= makeLink($nextText, $module, $func, $formValues);
    }
    else {
      $pagingLinks .= $nextText;
    } 
  }
  return $pagingLinks;
}


function makeAjaxButton($buttonName, $url, $callbackDivName = '' , $label = null) {
  if (isset($label)) {
    static $counter;
    $counter++;
    $id = "$label-$counter";
    return '<input type="submit" id="'.$id.'" name="'.$buttonName.'" value="'.$buttonName.'" onclick="ajaxCheckbox(\''.jsEscapeString($url).'\', \''.$callbackDivName.'\')">';
  }
  else {
    return '<input type="submit" value="'.$buttonName.'" onclick="ajaxCheckbox(\''.jsEscapeString($url).'\', \''.$callbackDivName.'\')">';
  }  
}

/**
 * Make an ajax enabled link.
 * 
 * @param unknown_type $linkName The name to use for the hyperlink
 * @param $url The url to post to
 * @param unknown_type $callbackDivName is the div tag to update with the results of the ajax call
 * @param unknown_type $label is used for the links "name" and "id" fields
 */

function makeAjaxLink($linkName, $url,  $callbackDivName = '' , $label = null) {
  if (isset($label)) {
    static $counter;
    $counter++;
    $id = "$label-$counter";
    return '<a href="javascript:void(0);" name="'.$id.'" id="'.$id.'" onclick="ajaxCheckbox(\''.jsEscapeString($url).'\', \''.$callbackDivName.'\')">' . $linkName ."</a>";
  }
  else {
    return '<a href="javascript:void(0);" onclick="ajaxCheckbox(\''.jsEscapeString($url).'\', \''.$callbackDivName.'\')">' . $linkName ."</a>";
  }
}

/**
 * Make an ajax enabled checkbox.
 * 
 * $url: the url to post to when checkbox is checked or unchecked
 * $checked: initial state (true = checked, false = unchecked)
 */
function makeAjaxCheckbox($url, $checked, $callbackDivName = '', $label = NULL) {
  if (isset($label)) {
    static $counter;
    $counter++;
    $id = "$label-$counter";
    return '<input type="checkbox" name="'.$id.'" id="'.$id.'" onclick="ajaxCheckbox(\''.jsEscapeString($url).'\', \''.$callbackDivName.'\')" '.($checked?'checked="yes"':'').'><label for="'.$id.'">'.$label.'</label></input>';
  }
  else {
    return '<input type="checkbox" onclick="ajaxCheckbox(\''.jsEscapeString($url).'\', \''.$callbackDivName.'\')" '.($checked?'checked="yes"':'').'/>';
  }
}

/**
 * Make an ajax enabled select control.
 * 
 * $url: the url to post to when item selection changes
 * $values : the array of initial values for the select control
 * $initialValue: the key of the value to be initially selected (defaults to null)
 * $callbackDivName: the div id of the area of the page that you want to receive the echoed text from the post to the url provided as the first parameter (defaults to "")
 * $paramName: the name of the url parameter to use when posting back to the $url.  The value of this param will contain the key of the item selected.
 */

function makeAjaxSelect($url, $values, $initialValue = NULL, $callbackDivName = '', $paramName = '') {
  static $ajaxcounter ;
  $ajaxcounter++;
  $id = "PLF_AjaxSelect-$ajaxcounter";

  $toReturn = ' <select name="'.$id.'" id="'.$id.'" onchange="ajaxSelect(\''.jsEscapeString($url).'\', \''.$callbackDivName.'\', this, \''.$paramName.'\')"  >';

  $toReturn .= '<option value=""></option>';
  foreach ($values as $key=>$value) {
    $toReturn .= '<option value="'.$key.'"';
    if ($initialValue == $key) {
      $toReturn .= ' selected ="selected"';
    }
    $toReturn .= '>'.htmlspecialchars($value);
    $toReturn .= '</option>';
  }
  
  $toReturn .='</select>';
  return $toReturn;
}

/**
 * Make an ajax enabled select control that updates multiple DIV tags with Multiple URLS
 * 
 * $ajaxArray: contains a set of arrays of the format: 
 *      $ajaxArray[0] = array( 'uri' => '/myapp/project/..', 'div' => 'div1' ) 
 *      $ajaxArray[1] = array( 'uri' => '/myapp/project/..', 'div' => 'div2' ) , etc...
 *           where uri contains the url to post to when item selection changes
 *           and  div is the tag to update with text when item selection changes
 * $values : the array of initial values for the select control
 * $initialValue: the key of the value to be initially selected (defaults to null)
 * $paramName: the name of the url parameter to use when posting back to the $url.  The value of this param will contain the key of the item selected.
 */
function makeMultipleAjaxSelect($ajaxArray, $values, $initialValue = NULL,  $paramName = '') {

 
  static $counter;
  $counter++;
  $id = "PLF_AjaxSelect-$counter";

  $toReturn = ' <select name="'.$id.'" id="'.$id.'" onchange="';
  foreach ( $ajaxArray as $num => $uriDivArray) { 
    $toReturn .= 'ajaxSelect(\''.jsEscapeString($uriDivArray['uri']).'\', \''.$uriDivArray['div'] .'\', this , \''.$paramName.'\'); ';
  }
  $toReturn .= '"  >';

  foreach ($values as $key=>$value) {
    $toReturn .= '<option value="'.$key.'"';
    if ($initialValue == $key) {
      $toReturn .= ' selected ="selected"';
    }
    $toReturn .= '>'.htmlspecialchars($value);
    $toReturn .= '</option>';
  }
  
  $toReturn .='</select>';
  return $toReturn;
}


/**
 * Compatability function for array_combine which was
 * introduced in PHP 5
 */
function arrayCombine($keys, $values) {
  if (function_exists('array_combine')) {
    return array_combine($keys, $values);
  }
  else {
    // taken from:
    // http://us3.php.net/manual/en/function.array-combine.php#58352
    $toReturn  = array();
    foreach($keys as $indexnum => $key) {
      $toReturn[$key] = $values[$indexnum];
    }
    return $toReturn;
  }
}      

/**
 * Load the file specified with $filename, with fields delimited
 * by $delimiter.  First of the file must be the names of the columns.
 * 
 * If a $callbackFunctionName is provided, this function will be called
 * once for each row of data in the file, and it will be passed an 
 * associative array representing that given row of data.  The keys
 * in the array will be the field names, and the data values of the array
 * will be the data values for that row.  Any data returned by the callback
 * function will be accumulated and returned from this function.  If there
 * is a chance of problems processing the data file, then call this function
 * two times, the first time you should pass it a callback function that 
 * just checks to see that everything is ok, returning messages from the
 *  callback function for errors you
 * want to report. Then, once you can do this without any messages returned,
 * then you can call loadFile again, this time giving it a different
 * callbackFunctionName that will do the real processing.
 * 
 * If a $callbackFunctionName is not provided, then all the rows will
 * be returned in a multidimensional array, with each element of the 
 * array being that associative array described above.  Only use this
 * option if you are sure the size of the file is not an issue, (ie.
 * the file contents will all fit into the memory space allocated
 * to running php scripts)
 */
function loadFileWorkingOnNewVersion($filename, $delimiter, $callbackFunctionName = NULL, $callbackFunctionExtraData = NULL, $columnInfo = NULL) {
  $fileArray = array();
  $returnMessage = '';
  $fh = fopen($filename, 'r');
  $lineNumber = 0;
  if (!feof($fh)) {
    $columnNamesArray = fgetcsv($fh, 0, $delimiter);
    $lineNumber++;
  }
  // here trying to figure out how to change the names of the columns AND also throw out the ones
  // we don't want... come back to this later
  // idea was to have the $columInfo array look like this:
  // [0] = 'newcol1';
  // [1] = 'newcol2';
  // [3] = 'newcolumnX';
  // effectively communicating new col names, and that we want field 3 skipped (ie. index 2)
  if (isset($columnInfo)) {
    foreach ($columnNamesArray as $index=>$columnNameFromDataFile) {
      if (isset($columnInfo[$index])) {
        
      }
    }
  }
  while (!feof($fh)) {
    $lineNumber++;
    $dataArray = fgetcsv($fh, 0, $delimiter);
    // ignore blank lines:
    if (count($dataArray) != 1) {
      $columNamesCount = count($columnNamesArray);
      $dataElementsCount = count($dataArray);
      if ($columNamesCount != $dataElementsCount) {
        $returnMessage .= "Error: on line $lineNumber: There are $columNamesCount columns in the data file, but only $dataElementsCount data elements on this line of the file  ";
        break;
      }
      $assocArray = arrayCombine($columnNamesArray, $dataArray);
      if (isset($callbackFunctionName)) {
        // if provided, call the callback function if it exists
        if (function_exists($callbackFunctionName)) {
          $returnMessage .= $callbackFunctionName($assocArray, $callbackFunctionExtraData);
        }
        else {
          $returnMessage .= "Error: $callbackFunctionName function not found";
          break;
        }
      }
      else {
        // otherwise build up the data and eventually return it
        // for the caller to play with directly
        $fileArray[] = $assocArray;
      }
    }
  }
  fclose($fh);
  if (isset($callbackFunctionName)) {
    return $returnMessage;
  }
  else {
    return $fileArray;
  }
}


function loadFile($filename, $delimiter, $callbackFunctionName = NULL, $callbackFunctionExtraData = NULL) {
  $fileArray = array();
  $returnMessage = '';
  $fh = fopen($filename, 'r');
  $lineNumber = 0;
  if (!feof($fh)) {
    $columnNamesArray = fgetcsv($fh, 0, $delimiter);
    $lineNumber++;
  }
  while (!feof($fh)) {
    $lineNumber++;
    $dataArray = fgetcsv($fh, 0, $delimiter);
    // ignore blank lines:
    if (count($dataArray) != 1) {
      $columNamesCount = count($columnNamesArray);
      $dataElementsCount = count($dataArray);
      if ($columNamesCount != $dataElementsCount) {
        $returnMessage .= "Error: on line $lineNumber: There are $columNamesCount columns in the data file, but only $dataElementsCount data elements on this line of the file  ";
        break;
      }
      $assocArray = arrayCombine($columnNamesArray, $dataArray);
      if (isset($callbackFunctionName)) {
        // if provided, call the callback function if it exists
        if (function_exists($callbackFunctionName)) {
          $returnMessage .= $callbackFunctionName($assocArray, $callbackFunctionExtraData);
        }
        else {
          $returnMessage .= "Error: $callbackFunctionName function not found";
          break;
        }
      }
      else {
        // otherwise build up the data and eventually return it
        // for the caller to play with directly
        $fileArray[] = $assocArray;
      }
    }
  }
  fclose($fh);
  if (isset($callbackFunctionName)) {
    return $returnMessage;
  }
  else {
    return $fileArray;
  }
}

/**
 * Not sure how useful this will be, but here it is:
 * 
 * stores the passed $data into the session, and returns
 * a unique id for that data
 * 
 * the id returned can be used passed into
 * getUniqueSessionData() to retrieve the $data
 * 
 * Careful: excessive use of this function could cause
 * a large amount of session data to accumulate
 */
function storeUniqueSessionData($data) {
  $uniqueKey = md5(uniqid(rand(), true));
  setSessionVar(UNIQUE_PREFIX.$uniqueKey, $data);
  return $uniqueKey;
  
}
/**
 * Gets the unique session data referenced to by $key
 * 
 * Used in conjunction with storeUniqueSessionData() 
 */
function getUniqueSessionData($key) {
  return getSessionVar(UNIQUE_PREFIX.$key);
}

/**
 * Checks for a strong password.  Returns the rule violated if
 * it is not a strong password.  Returns nothing if the password
 * provided is strong enough.
 * 
 * If you don't like these rules, write your own version!
 * 
 * usage: 
 * $violation = checkStrongPassword($pass);
 * if ($violation) {
 *   $theForm->addFormErrorMessage($violation);
 * }
 */
function checkStrongPassword($password) {
  if (strlen($password) <= 7) {
    return 'Password must be more than 7 characters long';
  } 
  elseif (!preg_match('/[a-z]/', $password)) {
    return 'Password must contain at least a lower case letter';
  }
  elseif (!preg_match('/[A-Z]/', $password)) {
    return 'Password must contain at least an upper case letter';
  }
  elseif (!preg_match('/[0-9]/', $password)) {
    return 'Password must contain at least one number';
  }
  else {
    return;
  }
}

function findInArray($array, $key, $value) {
  $toReturn = array();
  foreach ($array as $row) {
    if (isset($row[$key]) && $row[$key] == $value) {
      $toReturn[] = $row;
    }
  }
  return $toReturn;
}


/**
 * The APC Cache is the recommended cache to use with the 
 * PHP Lite Framework, if a cache is desired.
 * 
 * Caching php opcodes introduces the potential for 
 * inadvertently executing the wrong code.  For example,
 * a new version of a php file may be transferred to a
 * server, and the file transfer program may timestamp
 * the file with a later date than the date on the 
 * existing file.  This would cause the cache to continue
 * to use the in memory version of the php script, instead
 * of reading the new one that was transferred to disk.
 * 
 * Another possible problem is with database connections.
 * This method will be called in the event of any 
 * database connectivity issue, in an attempt to clear out
 * stale code and/or stale database connections. (ie. when
 * a database server is restarted without restarting 
 * the web/php server)
 */
function clearPhpCache() {
  if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
  }
}

function extractField($array, $field) {
  $toReturn = array();
  foreach ($array as $element) {
    $toReturn[] = $element[$field];
  }
  return $toReturn;
}


/**
 * Saves the current request arguments.  Except for the module
 * and function arguments.
 * 
 * This is called automatically in plf.inc.php (the controller)
 * 
 * See getCurrentArgsArray();
 */
function setCurrentArgsArray($args) {
  unset($args['func']);
  unset($args['module']);
  setGlobalVar('args', $args);
}

/**
 * Returns the array of the current arguments.
 * 
 * Useful for making a link on a page based on the current page.
 * 
 * makeUrl('myModule', 'myFunction', getCurrentArgsArray());
 * 
 */
function getCurrentArgsArray() {
  return getGlobalVar('args');
}

function getCurrentArgsString() {
  $args = getCurrentArgsArray();
  return processArgsArray($args);
}

function getCurrentDate() {
  return date(PHPDATEFORMAT);
}

function formatDate($format, $date) {
  // standard PLF date format (ie, coming from the form post)
  // is mm/dd/yyyy (or mm-dd-yyyy), so this function parses that format
  // and gives you a date in the format you request
//  $dateArr = split('[-,/]', $date);
  $dateArr = preg_split('/\-|\//', $date);
  
  $m=(int)$dateArr[0];
  $d=(int)$dateArr[1];
  $y=(int)$dateArr[2];
  return date($format, mktime(0,0,0,$m,$d,$y));  
}

/** 
 * Uses the internal dateDiff function to see if 
 * $date1 is less than $date2
 */
function dateLessThan($date1, $date2) {
  $diff = dateDiff('d', $date1, $date2);
  return ($diff > 0);
}

/**
 * Calculate the number of days between the 2 dates
 * 
 * Dates can be in any order (ie. return value will
 * always be positive.)
 */
function daysBetween($date1, $date2) {
  return abs(dateDiff('d', $date1, $date2));
}

/**
 * calculate the difference between 2 dates, using unit of 
 * measurement passed in $interval
 * s = seconds
 * n = minutes
 * ... etc.. look at the code
 * 
 * also note that it uses strtotime to convert the dates passed in
 * so be sure your dates work with this function.
 * 
 * This function grabbed from:
 * http://us3.php.net/manual/en/ref.datetime.php#57251
 */
 function dateDiff($interval, $dateTimeBegin, $dateTimeEnd = NULL) {
   if (isReallySet($dateTimeBegin)) {
     $dateTimeBegin=strtotime($dateTimeBegin);
     if($dateTimeBegin === -1) {
       return;
     }
   }
   else {
     return;
   }

  if (isReallySet($dateTimeEnd)) {
    $dateTimeEnd=strtotime($dateTimeEnd);
    if($dateTimeEnd === -1) {
      return;
    }
  }
  else {
    $dateTimeEnd=time();
  }
  $dif = $dateTimeEnd - $dateTimeBegin;

  switch($interval) {
    case "s": // seconds
      return($dif);
    case "n": // minutes
      return(round($dif/60)); //60s=1m
    case "h": // hours
      return(round($dif/3600)); //3600s=1h
    case "w": // weeks
      return(round($dif/604800)); //604800s=1week=1semana
    case "m": // months
      $monthBegin=(date("Y",$dateTimeBegin)*12)+ date("n",$dateTimeBegin);
      $monthEnd=(date("Y",$dateTimeEnd)*12)+ date("n",$dateTimeEnd);
      $monthDiff=$monthEnd-$monthBegin;
      return($monthDiff);
    case "y": // years
      return(date("Y",$dateTimeEnd) - date("Y",$dateTimeBegin));
    case "d": // days
    default:
      return(round($dif/86400)); //86400s=1d
  }
}

/**
 * used internally by plf.inc.php to push up to 5 urls on a stack
 * (managed via a cookie to prevent unnecessary session creation)
 * See getPreviousRequest() for how to use this information
 */
function pushRequestUrl() {
  if (isset($_REQUEST['hiddenXYZ123'])) {
    return; // we don't care about tracking form submits in the history
  }
  $lastUrls = getLastUrlArray();
  array_unshift($lastUrls, FRONT_OF_URL.$_SERVER['QUERY_STRING']);
  if (count($lastUrls) > 5) {
    array_pop($lastUrls); // pull off oldest one
  }
  setCookieVar('lastUrlArray', serialize($lastUrls));
}

function getLastUrlArray() {
  $lastUrls = array();
  $lastUrlsCookie = getCookieVar('lastUrlArray'); 
  if (isReallySet($lastUrlsCookie)) {
// needed to also stripslashes, per this post:
// http://us3.php.net/manual/en/function.setcookie.php#50617
    $lastUrls = unserialize(stripslashes($lastUrlsCookie));
    // keep the arrayUnshift call in pushRequestUrl happy:
    if (empty($lastUrls)) {
      $lastUrls = array(); 
    }
  }
  return $lastUrls;
  
}

function getPreviousUrl($number = 0) {
  $lastUrls = getLastUrlArray();
  return getArrayValueAtIndex($lastUrls, $number);  
}



/**
 * Gets the name of the module in the current request, handy
 * if you want to do something specific (maybe in a block)
 * @TODO: someday 'module' will be a constant instead
 */
function getModuleName() {
  if (isset($_REQUEST['module'])) {
    return $_REQUEST['module'];
  }
}

/**
 * Gets the name of the function in the current request, handy
 * if you want to do something specific (maybe in a block)
 * @TODO: someday 'func' will be a constant instead
 */
function getFuncName() {
  if (isset($_REQUEST['func'])) {
    return $_REQUEST['func'];
  }
}

/**
 * shows the configured value for a checkbox field.  ie. 
 * if the database stores a value of 1 for a checkbox field
 * and you want the user to see "Y", you would set the following
 * in your config.inc.php:
 * 
 * define('CHECKBOX_CHECKED', '1');
 * define('CHECKBOX_UNCHECKED', '0');
 * define('CHECKBOX_CHECKED_DISPLAY', 'Y');
 * define('CHECKBOX_UNCHECKED_DISPLAY', 'N');
 */
function displayCheckbox($value) {
  if ($value == CHECKBOX_CHECKED) {
    return CHECKBOX_CHECKED_DISPLAY;
  }
  else {
    return CHECKBOX_UNCHECKED_DISPLAY;
  }
}

/**
 * returns a "human readable" representation of the filesize
 * 
 * $size filesize in bytes
 * 
 * thanks New York PHP!
 * http://www.nyphp.org/content/presentations/3templates/task3-plain.php
 */
function displayFilesize($size) {

    // Setup some common file size measurements.
    $kb = 1024;         // Kilobyte
    $mb = 1024 * $kb;   // Megabyte
    $gb = 1024 * $mb;   // Gigabyte
    $tb = 1024 * $gb;   // Terabyte

    if($size < $kb) return $size." bytes";
    else if($size < $mb) return round($size/$kb,1)." KB";
    else if($size < $gb) return round($size/$mb,1)." MB";
    else if($size < $tb) return round($size/$gb,1)." GB";
    else return round($size/$tb,2)." TB";
}

/**
 * Really destroy the session, this taken directly from documentation at:
 * http://us2.php.net/session_destroy
 */
function destroySession() {
  startSession();

  // Unset all of the session variables.
  $_SESSION = array();

  // If it's desired to kill the session, also delete the session cookie.
  // Note: This will destroy the session, and not just the session data!
  if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
  }

  // Finally, destroy the session.
  session_destroy();
}


/**
 * 
 * Place an item into a "bucket", a named holding place
 * in the user's session.  If you just want to store some
 * simple values like 567, 568, etc, you can omit the
 * optional third parameter.  If you want to store a
 * more structured item like an array, pass the array
 * as the third parameter, and then use the second parameter
 * as an identifying key. Subsequent calls with
 * an $itemKey that has already been added will cause the 
 * original $item to be replaced with the current one.
 * 
 * If you are not using the third parameter, subsequent
 * calls with the same $itemKey will have no effect.  ie. 
 * the data in the bucket will stay the same since it's 
 * just storing the keys.
 * 
 */

function addItemToBucket($bucketName, $itemKey, $item = NULL) {
  $bucketItems = getSessionVarOkEmpty($bucketName);
  if ($item == NULL) {
    $bucketItems[$itemKey] = $itemKey;
  }
  else {
    $bucketItems[$itemKey] = $item;
  }
  setSessionVar($bucketName, $bucketItems);
}

function isItemInBucket($bucketName, $itemKey) {
  $bucketItems = getSessionVarOkEmpty($bucketName);
  return isset($bucketItems[$itemKey]);
}

function removeItemFromBucket($bucketName, $itemKey) {
  $bucketItems = getSessionVarOkEmpty($bucketName);
  unset($bucketItems[$itemKey]);
  saveCurrentBucketItems($bucketName);
  setSessionVar($bucketName, $bucketItems);
}

/**
 * Retrieve the items in the named bucket, as an array
 */
function getBucketItems($bucketName) {
  $bucket = getSessionVarOkEmpty($bucketName);
  if (isset($bucket)) {
    return $bucket;
  }
  else {
    return array();
  } 
}

function getFirstBucketItemKey($bucketName) {
  $keys = array_keys(getBucketItems($bucketName)); 
  return getArrayValueAtIndex($keys, 0);
}

/**
 * Empty out the named bucket
 */
function emptyBucketItems($bucketName) {
  saveCurrentBucketItems($bucketName);
  setSessionVar($bucketName, NULL);
}

function saveCurrentBucketItems($bucketName) {
  // save the last emptied bucket for possible recovery via recoverLastBucketItems()
  setSessionVar("RECENTLY_REMOVED_BUCKET-$bucketName", getBucketItems($bucketName));
}


function deleteBucket($bucketName) {
  delSessionVar($bucketName);
}

function recoverLastBucketItems($bucketName) {
  setSessionVar($bucketName, getBucketItems("RECENTLY_REMOVED_BUCKET-$bucketName"));
}




/**
 * calculate years of age based on DOB
 * input string: mm/dd/yyyy)
 * 
 * NOTE, used adodb time library so that dob can be before 1970!
 * adodb time library is part of adodb database library, already
 * included in PHP Lite Framework 
 */
function calculateAge($dob, $asOfDate = NULL){
  $dobParts=explode('/', $dob);
  if (count($dobParts) != 3) {
    return;
  }
  $dobSecs = adodb_mktime(0, 0, 0, $dobParts[0], $dobParts[1], $dobParts[2]);
  if ($asOfDate != null) {
    $asOfDateParts = explode('/', $asOfDate);
    $asOfDateSecs = adodb_mktime(0, 0, 0, $asOfDateParts[0], $asOfDateParts[1], $asOfDateParts[2]);
  }
  else {
    $asOfDateSecs = time();
  }
  // cast to int to truncate decimal age
  return (int)(($asOfDateSecs - $dobSecs)/31556926);
}

/**
 * Returns the DOB of a person with the given age as of today
 */
function calculateDOB($age) {
  $todayInSecs = time();
  $secsToSubtract = $age * 31556926;
  $newDate = $todayInSecs - $secsToSubtract;
  return date('m/d/Y', $newDate);
}

function addDays($date, $days) {
  $parts=explode('/', $date);
  $newDate = adodb_mktime(0, 0, 0, $parts[0], $parts[1] + $days, $parts[2]);
  $toReturn = adodb_date('m/d/Y', $newDate);  
  return $toReturn;
  
}

/**
 * Perform the xpath search, returning the single
 * node result if there's only one node found
 * 
 * Convenience method because xpath proper always
 * returns an array and it's annoying when only 
 * one node is found, and we know that only one
 * node will always be found.
 */
function xpath($node, $expression) {
  $result = $node->xpath($expression);
  // always returns an array, even if there's one element
  if (1 == count($result)){
    return $result[0];
  }
  else {
    return $result;
  }
}

function xmlFind($node, $expression) {
  $result = $node->$expression;
  return $result;
}
/**
 * NOTE: grabbed this from the HTML_Javascript pear package at
 * 
 * http://pear.php.net/package/HTML_Javascript/download
 * filename: Convert.php
 * method: escapeString
 * 
 * Used to terminate escape characters in strings,
 * as javascript doesn't allow them.  Used internally 
 * by makeLinkConfirm, so that the caller doesn't have
 * to worry about including single or double quotes
 * in the confirm text.  It will automatically be 
 * escaped properly for javascript land.
 *
 * @param   string  the string to be processed
 * @return  mixed   the processed string
 */
function jsEscapeString($str)
{
    $js_escape = array(
        "\r"    => '\r',
      "\n"    => '\n',
      "\t"    => '\t',
      "'"     => "\\'",
      '"'     => '\"',
      '\\' => '\\\\'
    );

    return strtr($str,$js_escape);
}


/**
 * only returns true if there is something there
 * Returns whatever php's isset function returns, with
 * the following 2 exceptions:
 * numeric 0 returns true 
 * empty string ('') returns false 
 * 
 * This *to me* seems a better intrepretation in the 
 * context of a user's form input.  IE: if they choose 0
 * for a numeric field (instead of leaving it blank), that
 * should be considered "set", while at the same time,
 * choosing the "blank" option from a drop down should 
 * be considered "not set".
 * 
 * 
 */
function isReallySet($var) {
  if (isset($var)) {
    return  ($var !== '');
  }
  else {
    return ($var === 0);
  }  
  
}
  

/**
 * perform a simple url fetch and return the results
 * 
 * great for REST based web services
 */
function restQuery($url)
{
  $string = '';
  $handle = fopen($url, "r");
  if ($handle)
  {
    // php 5
    //$string = stream_get_contents($handle);
    
    // php4
    while (!feof($handle)) {
      $string .= fread($handle, 8192);
    }
    fclose($handle);
  }
  return $string;
}

/**
 * Truncate given string to given length.  Appends ...
 * if it has to truncate the string, to indicate 
 * that it was truncated
 */
function truncateString($string, $length) {
  if (isset($string) && strlen($string) > 0) {
    if (strlen($string) > $length) {
      return substr($string, 0, $length).'...';
    }
    else {
      return $string;
    }
  }
}

/**
 * Construct and return a table object.  Using this method allows the 
 * framework to only include the table class definition when 
 * it is needed.
 */
function newTable($heading = null) {
  include_once 'table/MyTable.php';
  if (!is_array($heading)) {
    $heading = func_get_args();
  }
  return new PLF_Table($heading);
}




function encrypt1($text) {
  return str_rot13(base64_encode(str_rot13($text)));
}

function decrypt1($text) {
  return str_rot13(base64_decode(str_rot13($text)));
}
/**
 * Construct and return a form object.  Using this method allows the 
 * framework to only include the form class definition when 
 * it is needed.
 */
function newForm($submitButtonText, $method, $module, $func, $formName = 'form') {
  include_once 'form/MyForm.php';
  return new PLF_Form($submitButtonText, $method, $module, $func, $formName);
}

/**
 * 
 * Deprecated... please see newPdfTc() below
 * 
 * Construct and return a pdf object for creating PDF documents with the 
 * FreePdf library http://www.fpdf.org/
 * 
 * The PDF object is provided by the framework, but is basically a subclass
 * of the FPDF object provided by the FreePdf library... so see the documentation
 * at fpdf.org for how to manipulate the creation of the PDF DOC.
 * 
 * The purpose of this subclass is to provide the WriteHTML method, (as suggested
 * by a tip at fpdf.org).  This allows the user to provide html to be turned into
 * a pdf.  This makes it easier to write multiple paragraphs to a PDF.  
 * 
 * Instead of using WriteHTML, you can also format the document using the basic
 * methods provided by the FPDF class.
 * 
 * Simple usage (refer to the plfDemo application to see this in action)
 
  function demo_createPdf() {
   setDirectOutput();  
   $pdf = newPdf();
   $pdf->AddPage();
   $pdf->SetFont('Arial','B',16);
   $pdf->Cell(40,10,'Hello World');
   $pdf->Output();  
}

 */
function newPdf($orientation = 'P', $unit = 'mm', $format = 'letter') {
  include_once 'pdf/pdf.php';
  return new PDF($orientation, $unit, $format);
}

/**
 * New function for creating a pdf object for outputting.
 * 
 * See demo application for usage.
 * 
 * We recommend using this more modern pdf creation function, instead of the original one 
 * above.
 */
function newPdfTc($orientation='P', $unit='mm', $format='A4', $unicode=true, $encoding="UTF-8") {
  require(FRAMEWORKDIR.'/thirdParty/pdfGeneration/tcpdf/config/lang/eng.php');
  require(FRAMEWORKDIR.'/thirdParty/pdfGeneration/tcpdf/tcpdf.php');
  
  return new TCPDF($orientation, $unit, $format, $unicode, $encoding); 
}

/**
 * fetch an rss feed and return the magpierss structure, documented at
 * http://magpierss.sourceforge.net/
 * 
 * simple example:

  $url = 'http://rss.slashdot.org/Slashdot/slashdot';
  $rss = fetchRss($url);
  echo "Site: ", $rss->channel['title'], "<br>";
  foreach ($rss->items as $item ) {
    $title = $item['title'];
    $url   = $item['link'];
    echo "<a href=$url>$title</a><br>";
  }
  
  Note: see documentation at url above on the use of the cache directory.
  magpie will attempt to create a cache directory under the main directory
  of your project, (ie. wherever index.php is installed) It may not be able
  to do this, based on permissions, so you may want to do it yourself and 
  make it writable by the web server process.
 */
function fetchRss($url) {
  include_once THIRDPARTY_DIR.'/magpieRss/magpie/rss_fetch.inc';
  return fetch_rss($url);
}

/**
 * Same as fetchUrlIntoString , except passes in a HeaderArray to put in the 
 * HTTP Request. 
 * 
 * Exmaple of array: 
 * 		$headerArray  = array(
 *		"Accept-Language: en-us,en;q=0.5",
 *		"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
 *		"Keep-Alive: 300",
 *		"Connection: keep-alive" );
 */
function fetchUrlIntoStringWithHeader($url, $headerArray, $httpContext = null) {
  $singleSession = false;
  
  if (!isset($httpContext)) {
    $httpContext = getHttpContext();
    $singleSession = true;
  }
  curl_setopt($httpContext, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($httpContext, CURLOPT_URL, $url);
  curl_setopt($httpContext, CURLOPT_HTTPHEADER, $headerArray);
    
  $returnPage = curl_exec($httpContext);
  if ($singleSession) {
    closeHttpContext($httpContext);
  }
  return $returnPage;  
}

/**
 * This function uses the curl library that can optionally be enabled
 * in one's php compile.
 */

function fetchUrlIntoString($url, $httpContext = null) {
  $singleSession = false;
  if (!isset($httpContext)) {
    $httpContext = getHttpContext();
    $singleSession = true;
  }
  curl_setopt($httpContext, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($httpContext, CURLOPT_URL, $url);
  $returnPage = curl_exec($httpContext);
  if ($singleSession) {
    closeHttpContext($httpContext);
  }
  return $returnPage;  
}

/**
 * This function uses the curl library that can optionally be enabled
 * in one's php compile.
 */

function fetchUrlIntoFile($url, $fileName, $httpContext = null) {
  $singleSession = false;
  if (!isset($httpContext)) {
    $httpContext = getHttpContext();
    $singleSession = true;
  }
  curl_setopt($httpContext, CURLOPT_URL, $url);
  $fp = fopen($fileName, "w");
  curl_setopt($httpContext, CURLOPT_FILE, $fp);
  curl_exec($httpContext);
  if ($singleSession) {
    closeHttpContext($httpContext);
  }
}



function getHttpContext() {
  $ch = curl_init();
  // see documentation on these options at:
  // http://us3.php.net/manual/en/function.curl-setopt.php
  curl_setopt($ch, CURLOPT_COOKIEFILE, "cookiefile.txt");
  // give us a 30 second time limit.. if the fetch doesn't return in 30 seconds
  // then it's not worth the wait
  curl_setopt($ch, CURLOPT_TIMEOUT, 30);
  // also give a 15 sec connect timeout
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
  
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    
  return $ch;
}

function closeHttpContext($ch) {
  curl_close($ch);
}

/**
 * This function does the same as fetchUrlIntoXml($url), with 
 * the added benefit of passing along the associated Header Array in the HTTP request. 
 * 
 *  Exmaple of array: 
 * 		$headerArray  = array(
 *		"Accept-Language: en-us,en;q=0.5",
 *		"Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7",
 *		"Keep-Alive: 300",
 *		"Connection: keep-alive" );
 */
function fetchUrlIntoXmlWithHeader($url, $headerArray, $httpContext = null) {
  return simplexml_load_string(fetchUrlIntoStringWithHeader($url, $headerArray, $httpContext = null));
}

/**
 * This function does the same as fetchUrlIntoString($url), with 
 * the added benefit of also converting the resulting data into
 * an xml structure via simplexml_load_string (available in PHP 5 and up)
 */
function fetchUrlIntoXml($url, $httpContext = null) {
  return simplexml_load_string(fetchUrlIntoString($url, $httpContext = null));
}

/**
 * Given an array of values (typically from a fetchRecord() call or
 * a single record from a fetchRecords() call), this function
 * will call the PHP htmlspecialchars function on each one, altering
 * the original array of values for the caller to use
 *
 * Useful when using the results from fetchRecords() as output
 * on a web page, and you don't want any user entered html that is
 * in the db record to be interpreted as html when it reaches the
 * browser.
 *
 * Note: using this functionality, you don't have to worry about stripping
 * out the html that the user might enter into your db, just let it
 * get stored, and call this function when displaying the html chars
 */
function htmlEscapeValues(&$values) {
  foreach ($values as $key=>$value) {
    $values[$key] = htmlspecialchars($value);
  }
}


/**
 * resizes the image, keeping original aspect ratio
 *
 * @param $newHeight the desired height in pixels (width calculated from aspect ratio)
 * @param $filename the filename on disk to resize
 * @return the raw bytes of the resized image, suitable for db insert to blob
 */
function resizeImage($newSize, $filename) {
  $sizes = getimagesize($filename);

  $image_type=$sizes[2];

  if ($image_type==1) {
    $srcimg = imagecreatefromgif($filename);
  }
  elseif($image_type==2) {
    $srcimg = imagecreatefromjpeg($filename);
  }
  elseif($image_type==3) {
    $srcimg = imagecreatefrompng($filename);
  }

  $aspect_ratio = $sizes[1]/$sizes[0];
  if ($sizes[1] <= $newSize)
  {
    $new_width = $sizes[0];
    $new_height = $sizes[1];
  }
  else {
    $new_height = $newSize;
    $new_width = abs($new_height/$aspect_ratio);
  }

  $destimg=ImageCreateTrueColor($new_width,$new_height) or die('Problem Creating image');
  // resampled is better than ImageCopyResized
  ImageCopyResampled($destimg,$srcimg,0,0,0,0,$new_width,$new_height,ImageSX($srcimg),ImageSY($srcimg)) or die('Problem resizing');

  ob_start();
  if ($image_type==1) {
    imageGIF($destimg, '', 100);  // this method only outputs to browser, to wrap in the ob_ methods to trap
  }
  elseif ($image_type==2) {
    imageJPEG($destimg, '', 100);  // this method only outputs to browser, to wrap in the ob_ methods to trap
  }
  elseif ($image_type==3) {
    imagePNG($destimg, '', 100);  // this method only outputs to browser, to wrap in the ob_ methods to trap
  }

  $binaryThumbnail = ob_get_contents();
  ob_end_clean();
  return $binaryThumbnail;
}

/**
Validate an email address.
Provide email address (raw input)
Returns true if the email address has the email 
address format and the domain exists.

Thanks to:
http://www.linuxjournal.com/article/9585

*/
function validEmail($email)
{
   $isValid = true;
   $atIndex = strrpos($email, "@");
   if (is_bool($atIndex) && !$atIndex)
   {
      $isValid = false;
   }
   else
   {
      $domain = substr($email, $atIndex+1);
      $local = substr($email, 0, $atIndex);
      $localLen = strlen($local);
      $domainLen = strlen($domain);
      if ($localLen < 1 || $localLen > 64)
      {
         // local part length exceeded
         $isValid = false;
      }
      else if ($domainLen < 1 || $domainLen > 255)
      {
         // domain part length exceeded
         $isValid = false;
      }
      else if ($local[0] == '.' || $local[$localLen-1] == '.')
      {
         // local part starts or ends with '.'
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $local))
      {
         // local part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
      {
         // character not valid in domain part
         $isValid = false;
      }
      else if (preg_match('/\\.\\./', $domain))
      {
         // domain part has two consecutive dots
         $isValid = false;
      }
      else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                 str_replace("\\\\","",$local)))
      {
         // character not valid in local part unless 
         // local part is quoted
         if (!preg_match('/^"(\\\\"|[^"])+"$/',
             str_replace("\\\\","",$local)))
         {
            $isValid = false;
         }
      }
      if ($isValid && !(checkdnsrr($domain,"MX") || checkdnsrr($domain,"A")))
      {
         // domain not found in DNS
         $isValid = false;
      }
   }
   return $isValid;
}


function sendMail($replyToBounceAddress, $fromAddress, $fromName, $toAddress, $toName, $subject, $text, $html = NULL) {

  // the swift mailer, from http://www.swiftmailer.org/
  require_once FRAMEWORKDIR.'/thirdParty/mail/Swift-3.3.1-php5/lib/Swift.php';
  require_once FRAMEWORKDIR.'/thirdParty/mail/Swift-3.3.1-php5/lib/Swift/Connection/SMTP.php';
  require_once FRAMEWORKDIR.'/thirdParty/mail/Swift-3.3.1-php5/lib/Swift/Authenticator/LOGIN.php';
  try {
    if (MAIL_SMTPSECURE) {
      $swiftConn = new Swift_Connection_SMTP(MAIL_SMTPSERVER, MAIL_SMTPPORT, Swift_Connection_SMTP::ENC_TLS);
    }
    else {
      $swiftConn = new Swift_Connection_SMTP(MAIL_SMTPSERVER, MAIL_SMTPPORT);
    }
  
    $username = MAIL_SMTPUSERNAME;
    $password = MAIL_SMTPPASSWORD;
    if (!empty($username) && !empty($password)) {
      $swiftConn->setUsername($username);
      $swiftConn->setPassword($password);
    }
  
    $swift = new Swift($swiftConn);
     
    //Create a message
    if ($html == NULL) {
      // if they only provided a text portion for the email, just
      // send a text email
      $message = new Swift_Message($subject, $text);
    }
    else {
      // if they provided both text and html, send multipart email
      $message = new Swift_Message($subject);
      $message->attach(new Swift_Message_Part($text));
      $message->attach(new Swift_Message_Part($html, "text/html"));
    }
  
    $message->setReturnPath($replyToBounceAddress);
    if (REALLYSENDMAIL) {
      $address = $toAddress;
    }
    else {
      logNotice("REALLYSENDMAIL is set to false, so we are sending an email to the configured TEMP_EMAIL_ADDRESS of ".TEMP_EMAIL_ADDRESS);
      $address = TEMP_EMAIL_ADDRESS;
    }
  
    $swift->send($message,  new Swift_Address($address, $toName),  new Swift_Address($fromAddress, $fromName));
  
  } catch (Swift_ConnectionException $e) {
    return "There was a problem communicating with SMTP server (".MAIL_SMTPSERVER."): " . $e->getMessage();
  } catch (Swift_Message_MimeException $e) {
    return "There was an unexpected problem building the email:" . $e->getMessage();
  } catch (Exception $e) {
    return "There was an unexpected problem :" . $e->getMessage();
  }
}

/**
 Create an anchor html tag

 @param $url The url the user will go to if the link is clicked
 @param $name The name of the link the user will see in the browser
**/
function href($url, $name) {
  return '<a href="'.$url.'">'.$name.'</a>';
}

/**
 * Create an image tag
**/
function img($url, $altText = NULL) {
  if (empty($altText)) {
    $altText = "image of $url";
  }
  return '<img src="'.$url.'" alt="'.$altText.'"/>';
}

function getFileUploadErrorDescriptions() {
  return array(
       1=>"Maximum filesize exceeded, please try again", //exceeds upload_max_filesize directive in php.ini
       2=>"Maximum filesize exceeded, please try again",  // exceeds the MAX_FILE_SIZE directive specified in the HTML form
       3=>"The uploaded file was only partially uploaded, please try again",
       4=>"No file was uploaded, please try again",
       6=>"Missing a temporary folder, contact website administrator"
  );
}

/**
  this avoids the warning and the encapsulates the extra
  isset check required in the case where there is nothing
  in the array at the specified index
**/
function getArrayValueAtIndex($array, $index) {
  if (isset($array[$index])) {
    return $array[$index];
  }
  else {
    return;
  }
}

/**
 * Avoids a warning if the specified index is not in the array
 */
function appendArrayValueAtIndex(&$array, $index, $value) {
  if (is_array($value)) {
     $array[$index][] = $value;
  }
  else {
    if (isset($array[$index])) {
      $array[$index] .= $value;
    }
    else {
      $array[$index] = $value;
    }
  }
}

function redirectWithMessage($message, $url=null) {
  if (DB_DEBUG_ON) {
    echo "ALERT Database Debug flag is on, so here I *would* be redirecting, but instead I'll pause to allow you to view the db debug messages<br/>";
    echo "click here to proceed ";
    if (isset($url)) {
      setSessionVar('statusMsg',$message);
      echo href($url, $url);
    }
    else {
      setSessionVar('statusMsgSticky',$message);
      echo href(makeUrl('showStatusMsg'), makeUrl('showStatusMsg'));
    }
  }
  else {
    if (isset($url)) {
      setSessionVar('statusMsg',$message);
      redirect($url);
    }
    else {
      setSessionVar('statusMsgSticky',$message);
      redirect(makeUrl('showStatusMsg')); // special module name handled by controller
    }
  }
}

function implode_with_keys($glue, $array) {
       $output = array();
       foreach( $array as $key => $item )
               $output[] = $key . "=" . $item;

       return implode($glue, $output);
}

function getSiteServerName() {
  return $_SERVER['SERVER_NAME'];
}


/**
 * Log a message in the "NOTICE" severity
 *
 * @param String $message
 */
function logNotice($message) {
  trigger_error($message, E_USER_NOTICE);
}

/**
 * Log a message in the "ERROR" severity
 *
 * @param String $message
 */
function logError($message) {
  trigger_error($message, E_USER_ERROR);
}

/**
 * Log a message to the error log without regard for
 * what the userErrorHandler does (used internally for
 * security type message logging that we don't want to 
 * let go to the browser even if the project is configured
 * to write the error message to the browser.)
 *
 * @param String $message
 */
function logErrorSilent($message) {
  error_log($message);
}

/**
 * Log a message in the "WARNING" severity
 *
 * @param String $message
 */
function logWarning($message) {
  trigger_error($message, E_USER_WARNING);
}

 /**
   * Return/print a nicely formatted backtrace
   * Based on code originally by John Lim
   *
   * @param bool $print If true, the backtrace will be printed
   * @return string the formatted backtrace
   */
function getBacktraceFormatted () {
  $s = '';

  $MAXSTRLEN = 300;

  if (! function_exists ('debug_backtrace'))
    return (false);

  $s = "\n";
  $traceArr = debug_backtrace();
  $tabs = sizeof($traceArr)-1;

  foreach ($traceArr as $arr) {
    //for ($i=0; $i < $tabs; $i++) $s .= ' ';
    //$tabs -= 1;
    if (strcasecmp($arr['function'], 'getBacktraceFormatted') == 0 ||
        strcasecmp($arr['function'], 'userErrorHandler') == 0) {
      continue;
    }
    $s .= ' at ';
    $args = array();
//    if (isset($arr['class'])) $s .= $arr['class'].'.';
    if (isset($arr['args'])) {
      foreach($arr['args'] as $v) {
        if (is_null($v)) $args[] = 'null';
        else if (is_array($v)) $args[] = 'Array['.sizeof($v).']';
        else if (is_object($v)) $args[] = 'Object:'.get_class($v);
        else if (is_bool($v)) $args[] = $v ? 'true' : 'false';
        else {
          $v = (string) @$v;
//          $str = htmlspecialchars(substr($v,0,$MAXSTRLEN));
          $str = substr($v,0,$MAXSTRLEN);
          if (strlen($v) > $MAXSTRLEN) $str .= '...';
          $args[] = $str;
        }
      }
    }

    if (!empty($arr['line'])) {
      $s .= $arr['file'].':'.$arr['line'].' ';
    }
    if (isset($arr['class'])) $s .= $arr['class'].'.';
    // don't show arguments passed to the getConnection method!!
    if ($arr['function'] == 'getConnection' || $arr['function'] == 'getconnection') {
      $s .= $arr['function'].'(... hidden ...)';
    }
    else {
      $s .= $arr['function'].'('.implode(', ',$args).')';
    }
    $s .= "\n";
  }

  return $s;
}

/**
 * taken from idea at http://us4.php.net/errorfunc
 * called with filename, linenumber, etc internally by php
 * whenever trigger_error is called
 * since this method is registered as a custom error handler by
 * the framework
 *
 * logNotice(), logError(), and logWarning() provided as a convenience
 * 
 * Note: as of 9/2007, this was changed to just log without specifying
 * a filename, thus going to the webserver's error log.
 */
function userErrorHandler($errno, $errmsg, $filename, $linenum, $vars)
{
  $dt = date("Y-m-d H:i:s O");
  if ($errno == E_STRICT) {
    // ignore E_STRICT for now...
    return;
  }
  else if ($errno != E_USER_WARNING && $errno != E_USER_NOTICE && $errno != E_USER_ERROR) {
    // these are errors *not* generated inside the framework (via logError(), logWarning(), or 
    // logNotice() calls), so just log them )
    $msg = $dt.' '.$filename.':'.$linenum.' '.$errmsg;
    if (SHOWEVERYTHINGELSETOUSER) {
      echo "$msg<hr/>";
    }
    if (LOGEVERYTHINGELSE) {
      error_log($msg."\n");
    }
  }  
  else {
    
    // define an assoc array of error string
    // in reality the only entries we should
    // consider are E_WARNING, E_NOTICE, E_USER_ERROR,
    // E_USER_WARNING and E_USER_NOTICE
    $errortype = array (
             E_ERROR          => "Error",
             E_WARNING        => "Warning",
             E_PARSE          => "Parsing Error",
             E_NOTICE          => "Notice",
             E_CORE_ERROR      => "Core Error",
             E_CORE_WARNING    => "Core Warning",
             E_COMPILE_ERROR  => "Compile Error",
             E_COMPILE_WARNING => "Compile Warning",
             E_USER_ERROR      => "Error",
             E_USER_WARNING    => "Warning",
             E_USER_NOTICE    => "Notice",
             E_STRICT          => "Runtime Notice"
             );
    $refer = 'unknown';
    if (isset($_SERVER['HTTP_REQUEST'])) {
      $refer = $_SERVER['HTTP_REQUEST'];
    }
    $request = 'unknown';
    if (isset($_SERVER['HTTP_HOST']) && isset($_SERVER['REQUEST_URI'])) {
      $request = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    }
    $err = $dt.' [PLF '.$errortype[$errno].'] pid: ('.getmypid().') referer: ('.$refer.') request url: ('.$request.') source: ('.$filename.':'.$linenum.') message: ('.$errmsg.")\n";
    
    if (E_USER_ERROR == $errno) {
      if (SHOWERRORSTOUSER) {
        echo htmlspecialchars($err).'<br/>';
        echo '<pre>'.htmlspecialchars(getBacktraceFormatted()).'</pre><hr/>';
      }
      else {
        echo FRIENDLY_ERROR_MSG_FOR_USER;
      }
      $whatToLog = $err.getBacktraceFormatted();
      if (EMAIL_ADMIN_ON_ERROR) {
        sendMail(ADMIN_EMAIL_ADDR, ADMIN_EMAIL_ADDR, ADMIN_EMAIL_ADDR, ADMIN_EMAIL_ADDR, ADMIN_EMAIL_ADDR, ADMIN_EMAIL_SUBJECT_PREPEND.' Website error for site: '.WEBSITENAME, $whatToLog);
      }
//      error_log($err.getBacktraceFormatted(), 3, ERRORLOGNAME);
      error_log($err.getBacktraceFormatted());
    }
    else if (E_USER_WARNING == $errno) {
      if (SHOWWARNINGSTOUSER) {
        echo htmlspecialchars($err).'<br/>';
        echo '<pre>'.htmlspecialchars(getBacktraceFormatted()).'</pre><hr/>';
      }
//      error_log($err, 3, WARNINGLOGNAME);
      if (LOGWARNINGS) {
        error_log($err.getBacktraceFormatted());
      }
    }
    else {
      // must be E_USER_NOTICE here, everything else picked out above already
      if (SHOWNOTICESTOUSER) {
        echo htmlspecialchars($err).'<br/>';
      }
//      error_log($err, 3, NOTICELOGNAME);
      if (LOGNOTICES) {
        error_log($err);
      }
    }
  }
}

/**
 * Set the title to be used for the page.  The title
 * is accessed in the template via either:
 * {{pageTitle}} or {{pageTitleWithSiteName}}
 * 
 * 
 */
function setPageTitle($pageTitle) {
  $GLOBALS['pageTitle'] = $pageTitle;
}

/** Call this method to append stuff to the "head content"...
 * that is, the {{headContent}} part of your template
 * 
 * This enables you to control what goes into the <head>
 * area of your template (like javascript stuff, meta tags, etc)
 * 
 * This can be called anytime, and it will store the 
 * content for later when the page is actually generated.
 */
function setHeadContent($headContent) {
  if (isset($GLOBALS['headContent'])) {
    $GLOBALS['headContent'] .= $headContent;
  }
  else {
    $GLOBALS['headContent'] = $headContent;
  }
}

/** Call this method to append stuff to the "body attribute"...
 * that is, the {{bodyAttribute}} part of your template
 * 
 * generally the template will be written like this
 * <body {{bodyAttribute}}>
 * etc...
 * </body>
 * 
 * then if you want something like onload="myJsFunction()"
 * 
 * you do this:
 * setBodyAttribute('onload="myJsFunction()"');
 * 
 * This can be called anytime, and it will store the 
 * content for later when the page is actually generated.
 */
function setBodyAttribute($bodyAttribute) {
  if (isset($GLOBALS['bodyAttribute'])) {
    $GLOBALS['bodyAttribute'] .= $bodyAttribute;
  }
  else {
    $GLOBALS['bodyAttribute'] = $bodyAttribute;
  }
}

/**
 * Used internally when building the page, when the 
 * {{headContent}} placeholder is visited.
 */
function getHeadContent() {
  if (isset($GLOBALS['headContent'])) {
    return $GLOBALS['headContent'];
  }
}

/**
 * Used internally when building the page, when the 
 * {{bodyAttribute}} placeholder is visited.
 */
function getBodyAttribute() {
  if (isset($GLOBALS['bodyAttribute'])) {
    return $GLOBALS['bodyAttribute'];
  }
}

/**
 * Center the given text, using a DIV and a text-align css directive
 */
function center($text) {
  return '<div style="text-align:center;">'.$text.'</div>';
}

/**
 * put some javascript in...
 */
function javascript($javascript) {
//    $js = '<script type="text/javascript" language="javascript" charset="utf-8">// <![CDATA[';
    $js = '<script type="text/javascript" language="javascript" charset="utf-8"><!--';
    $js .= "\n".$javascript."\n";
//    $js .= '// ]]></script>';
    $js .= '//--></script>';
    return $js;
}

/**
 * Append some more text to the page title.  Useful if you have multiple areas
 * with conditional logic that sets the page title.  Ex. First, you determine
 * if the page should be labeled "Update Address" or "Add Address", then later you look up some 
 * identifying text for the parent record, storing it in a variable called $name.  You
 * can then do: appendPageTitle(' for '.$name) and the page title will then be something
 * like "Update Address for Jim Smith"
 */
function appendPageTitle($extraTitle) {
  if (isset($GLOBALS['pageTitle'])) {
    $GLOBALS['pageTitle'] = $GLOBALS['pageTitle'].$extraTitle;
  }
  else {
    $GLOBALS['pageTitle'] = $extraTitle;
  }
}

/**
 * Set desired template name to use when rendering the page.
 * If never called, template will be 'default' as returned by 
 * getTemplate().  Template html file will be found in root dir
 * of web application.
 */
function setTemplate($template) {
  $GLOBALS['template'] = $template;
}

/**
 *  Gets the template name set by setTemplate()
 */
function getTemplate() {
  if (isset($GLOBALS['template'])) {
    return $GLOBALS['template'];
  }
  else {
    return 'default';
  }
}

/**
 * call this in a function if you will not be returning a string
 * for rendering inside the template.  Useful when the function 
 * will be doing its own streaming of data, like a pdf or an image.
 * If doing this, remember to set headers appropriately for the 
 * stream, if it is not standard text.
 */
function setDirectOutput($directOutput = TRUE) {
  $GLOBALS['directOutput'] = TRUE;
}


/**
 * inquire as to whether the direct output flag was set
 */
function getDirectOutput() {
  if (isset($GLOBALS['directOutput'])) {
    return $GLOBALS['directOutput'];
  }
  
}

function getPageTitle() {
  if (isset($GLOBALS['pageTitle'])) {
    return $GLOBALS['pageTitle'];
  }
}

function loadModuleFile($projectDir, $modname)
{
  $osmodname = pnVarPrepForOS($modname);
  $osfile = $projectDir."/project/modules/$osmodname.php";
  if (file_exists($osfile)) {
    // Load file
    include_once $osfile;
  }
}


function loadBlockFile($projectDir, $modname)
{
    static $loaded = array();
    if (empty($modname)) {
        return false;
    }

    if (!empty($loaded["$modname"])) {
        // Already loaded from somewhere else
        return $modname;
    }
    // Load the module and module language files
    $osmodname = pnVarPrepForOS($modname);
    $osfile = $projectDir."/project/blocks/$osmodname.php";

    if (!file_exists($osfile)) {
        // File does not exist
        return false;
    }

    // Load file
    include $osfile;
    $loaded["$modname"] = 1;

    // Return the module name
    return $modname;
}

function callBlock($projectDir, $block) {
// Build function name and call function
  loadBlockFile($projectDir, $block);
  $modfunc = $block.'_contents';

  if (function_exists($modfunc)) {
      return $modfunc();
  }
  return false;

}

// the "internal" functions are for resources that need to be
// fetched by the browser, yet are contained within the framework
// like javascript resources, calendar images, css, etc
// these are all configured to just echo their stuff out, so 
// we need to just call them and let them echo.
function callInternalFunc($module, $func) {
  // Build function name and call function
  $modfunc = "{$module}_{$func}";
  if (function_exists($modfunc)) {
   $modfunc();
  }
}

/**
 * run a module function, returning whatever the function returns
 * as well as anything it may echo out. (appended together, return 
 * data being first, followed by any echo)  Of course, it is best
 * to either echo or return your data, but not do both.
 * 
 * @param modname - name of module
 * @param func - function to run
 * @returns whatever the function returns, and anything it echos
 */
function callFunc($module, $func)
{
  // Build function name and call function
  $modfunc = "{$module}_{$func}";
  if (function_exists($modfunc)) {
    if (getDirectOutput()) {
      $modfunc();
    }
    else {
      $toReturn = '';
      ob_start();
      $toReturn .= $modfunc();
      $toReturn .= ob_get_contents();
      ob_end_clean();
      return $toReturn ;
    }
  }
}


/**
 *
 * Gets a variable from the inbound request.  Uses $_REQUEST, so 
 * it will get variables via $_GET and $_POST.  The value must
 * be a number, otherwise the default will be provided.  If no
 * default is given, 0 will be returned.
 * 
 * This saves the caller from having to validate the datatype.
 * 
 * See also: getRequestVarString() and getRequestVarArray()   
 * 
 */
function getRequestVarNum($name, $default = 0) {
  if (isset($_REQUEST[$name]) && !is_array($_REQUEST[$name])) {
    return (int)$_REQUEST[$name];
  }
  else {
    return $default;
  }
}

/**
 *
 * Gets a variable from the inbound request.  Uses $_REQUEST, so 
 * it will get variables via $_GET and $_POST.  The value can 
 * be anything except an array, (ie. a number or a string).  If
 * an array is provided, nothing will be returned.
 * 
 * This saves the caller from having to validate the datatype.
 * 
 * See also: getRequestVarNum() and getRequestVarArray()   
 * 
 */
function getRequestVarString($name, $default = '') {
  if (isset($_REQUEST[$name]) && !is_array($_REQUEST[$name])) {
    return $_REQUEST[$name];
  }
  else {
    return $default;
  }
}

/**
 *
 * Gets a variable from the inbound request.  Uses $_REQUEST, so 
 * it will get variables via $_GET and $_POST.  The value must
 * be an array, if it is not an array, nothing will be returned.
 * 
 * This saves the caller from having to validate the datatype.
 * 
 * See also: getRequestVarNum() and getRequestVarString()   
 * 
 */
function getRequestVarArray($name, $default = array()) {
  if (isset($_REQUEST[$name]) && is_array($_REQUEST[$name])) {
    return $_REQUEST[$name];
  }
  else {
    return $default;
  }
}

function setCookieVar($name, $value) {
  setcookie(SESSION_PREFIX.$name, $value);
}

/**
 * Get the value of the named cookie.
 * 
 * Note:  This method returns nothing if the value
 * of the cookie is the text "deleted".
 * 
 * This workaround is necessary to account for users
 * who are accessing the application via the 
 * privoxy proxy:
 * http://www.privoxy.org/
 * 
 * There is a bug reported at:
 * https://sourceforge.net/tracker/index.php?func=detail&aid=932612&group_id=11118&atid=111118
 * that documents this behavior, and I can't think of any
 * other way to get around it.
 * Unfortunately, if you want to set the value of your cookie
 * to "deleted", you're out of luck.  Sorry.
 * 
 * I logged a comment to the bug report on 11/15/2006.
 */
function getCookieVar($name) {
  if (isset($_COOKIE[SESSION_PREFIX.$name]) && 'deleted' != $_COOKIE[SESSION_PREFIX.$name]) {
    return $_COOKIE[SESSION_PREFIX.$name];
  }
}

/**
 * Delete the cookie with the given name
 */
function delCookieVar($name) {
//  setcookie(SESSION_PREFIX.$name, '',  time()-60000);
  setcookie(SESSION_PREFIX.$name, '');
}

function getGlobalVar($name) {
  if (isset($GLOBALS[$name])) {
    return $GLOBALS[$name];
  }
}

/**
 * Gets a variable from the session.  Starts a session if one
 * has not already been started.  Also updates the session
 * access time for the purpose of timing out the session
 * independently from the php/web server environment.
 *
 * This now does the same as getSesssionVarOkEmpty 
 */
function getSessionVar($name)
{
  return getSessionVarOkEmpty($name);
  
/*  $sessionVar = getSessionVarOkEmpty($name);

  if (isset($sessionVar)) {
    updateSessionAccessTime();
    return $sessionVar;
  }
  else {
    loadModuleFile(DEFAULTMODULE);
    $return = callFunc(DEFAULTMODULE, DEFAULT_SESSION_EXPIRED_FUNC);
    logNotice("calling the session expired function and dying.  session variable we were attempting to get: $name");
    echo $return;
    die();
  }*/
}

function updateSessionAccessTime() {
  $_SESSION[SESSION_PREFIX.'lastaccesstime'] = time();
}


/**
 * See getSessionVar().  Same behavior, except if the variable
 * is not present in the session, or the session is expired, it
 * does not redirect the user to the session expired message, it
 * will just return nothing.  This is used when you don't care
 * if the session is expired, you just want to see if the variable
 * is present and handle things yourself.
 */
function getSessionVarOkEmpty($name)
{
  startSession();
  $var = SESSION_PREFIX.$name;
  if (isset($_SESSION[$var])) {
    $lastSessionAccessTime = getArrayValueAtIndex($_SESSION, SESSION_PREFIX.'lastaccesstime');
    $rightNow = time();
    $minutesSinceLastActivity = ($rightNow - $lastSessionAccessTime)/ 60.0;
    if ($minutesSinceLastActivity > SESSION_EXPIRE_MINUTES) {
      session_destroy();
      return;
    }
    else {
      updateSessionAccessTime();
      return $_SESSION[$var];
    }
  }
}

/**
 * Delete a session variable
 * @param name name of the session variable to delete
 */
function delSessionVar($name)
{
  startSession();
  $var = SESSION_PREFIX.$name;
  //session_unregister($var);
  // change to unset because above function is deprecated in 5.3.0
  unset($_SESSION[$var]);
  return true;
}

function startSession() {
  if (session_id() == '') {
    session_set_cookie_params(0, pnGetBaseURI().'/');
    session_start();
  }
}

/**
 * Set a variable in the session
 */
function setSessionVar($name, $value)
{
  startSession();
  $var = SESSION_PREFIX.$name;
  $_SESSION[$var] = $value;
  updateSessionAccessTime();
  return true;
}

/**
 * Set a global variable... this only sticks around during
 * the current script execution, not from one request to the 
 * other.  Use setSessionVar if you need a variable to stay
 * around from one page request to the next.
 */
function setGlobalVar($name, $value) {
  $GLOBALS[$name] = $value;
}


function hashIt($stringToHash) {
  return md5($stringToHash);
}

/**
 * Dumps out the incoming data in tabular format. Heading will be
 * the keys of the first row, so it's nice to use an associative
 * array.
 */
function dumpTable($data, $message = NULL) {
  echo($message.'<br/>');
  if (count($data) > 0) {
    $table = newTable(array_keys($data[0]));
    $table->setAttributes("border=1");
    foreach($data as $row) {
      $table->addRow($row);
    }
    echo $table->toHtml();
  }
  echo "Total Rows: ".count($data).'<br/>';
}

/**
Dumps a readable representation of the provided variable to the browser
NOTE: it can also be used via the PHP command line interface (CLI), since it will
look for the presence of a SERVER variable HTTP_HOST, which won't be present
if using the CLI.  This allows it to know whether or not it needs to format
the output using html tags, or if it can just call the internal print_r method
directly.
optionally, pass a message as a second param to be output before the data
**/
function dump($data, $message = NULL) {
  if (isset($_SERVER['HTTP_HOST'])) {
    echo dumpString($data, $message);
  }
  else {
    echo $message."\n";
    print_r($data);
  }
}

/**
 * See dump()... difference here is that the readable representation 
 * of the variable is returned from this method, instead of directly
 * being echoed to the page.  Useful when you want to control 
 */
function dumpString($data, $message = NULL){
  $output = '<hr/><div align="center"><h3>'.$message.'</h3></div>'; 
  $output .= 'Using print_r():<br/><br/>';
  ob_start();
  print_r($data);
  $output .= htmlspecialchars(ob_get_contents());
  ob_end_clean();
  $output .= '<br/><br/><br/><div align="center"><h3>'.$message.'</h3></div> Using var_dump():<br/><br/>';
  ob_start();
  var_dump($data);
  $output .= htmlspecialchars(ob_get_contents());
  ob_end_clean();
  $output .= '<hr/>';
  return '<pre>'.$output.'</pre>';
}

function makeLinkConfirm($confirmText, $linkName, $modname="", $func="", $args=array()) {
  $onclick = ' onclick="return confirm(\''.jsEscapeString($confirmText).'\')" ';
  return '<a href="'.makeUrl($modname, $func, $args).'"'.$onclick.'>'.$linkName.'</a>';
}

function makeExternalLink($linkName, $url, $args=array()) {
  return '<a href="'.$url.processArgsArray($args).'">'.$linkName.'</a>';
}

function makeExternalLinkPop($popupTitle, $linkName, $url, $args=array()) {
  return '<a title="'.$popupTitle.'" href="'.$url.processArgsArray($args).'">'.$linkName.'</a>';
}

function makeExternalLinkPopNewWin($popupTitle, $linkName, $url, $args=array()) {
  return '<a target="_blank" title="'.$popupTitle.'" href="'.$url.processArgsArray($args).'">'.$linkName.'</a>';
}


/**
 * Makes a button which, when clicked, will fetch the specified
 * module/function, with optional arguments (requires javascript
 * in the browser)
 *
 * @param unknown_type $linkName The text to display on the button
 * @param unknown_type $modname The desired module to call
 * @param unknown_type $func the desired function to call
 * @param unknown_type $args the optional arguments to pass (no ? necessary)
 * @return unknown The hyperlink, ie, <a href="... etc">linkname</a>
 */
function makeButtonLink($linkName, $modname="", $func="", $args=array()) {
  return '<input type="submit" name="'.$linkName.'" value="'.$linkName.'" onClick="window.location=\''.jsEscapeString(makeUrl($modname, $func, $args)).'\';">';
}

/**
 * Makes a button which, when clicked, will fetch the specified
 * module/function, with optional arguments (requires javascript
 * in the browser)
 *
 * @param unknown_type $linkName The text to display on the button
 * @param unknown_type $modname The desired module to call
 * @param unknown_type $func the desired function to call
 * @param unknown_type $args the optional arguments to pass (no ? necessary)
 * @return unknown The hyperlink, ie, <a href="... etc">linkname</a>
 */
function makeButtonLinkPop($popupTitle, $linkName, $modname="", $func="", $args=array()) {
  return '<input title="'.$popupTitle.'" type="submit" name="'.$linkName.'" value="'.$linkName.'" onClick="window.location=\''.jsEscapeString(makeUrl($modname, $func, $args)).'\';">';
}

/**
 * Makes a button which, when clicked, will first prompt the user a question
 * (the $confirmText) using a javascript popup.  If user clicks OK on the
 * popup, it will then fetch the specified
 * module/function, with optional arguments (requires javascript
 * in the browser) 
 *
 * @param $confirmText The text to show in the confirm popup
 * @param unknown_type $linkName The text to display on the button
 * @param unknown_type $modname The desired module to call
 * @param unknown_type $func the desired function to call
 * @param unknown_type $args the optional arguments to pass (no ? necessary)
 * @return unknown The hyperlink, ie, <a href="... etc">linkname</a>
 */
function makeButtonLinkConfirm($confirmText, $linkName, $modname="", $func="", $args=array()) {
  return '<input type="submit" name="'.$linkName.'" value="'.$linkName.'" onClick="if (confirm(\''.jsEscapeString($confirmText).'\')) {window.location=\''.makeUrl($modname, $func, $args).'\'};">';
}

/**
 * Makes a hyperlink to a specific module/function, with optional arguments
 *
 * @param unknown_type $linkName The name to use for the hyperlink
 * @param unknown_type $modname The desired module to call
 * @param unknown_type $func the desired function to call
 * @param unknown_type $args the optional arguments to pass (no ? necessary)
 * @return unknown The hyperlink, ie, <a href="... etc">linkname</a>
 */
function makeLink($linkName, $modname="", $func="", $args=array()) {
  return '<a href="'.makeUrl($modname, $func, $args).'">'.$linkName.'</a>';
}

/**
 * Makes a hyperlink to a specific module/function, with optional arguments
 *
 * @param $popupTitle The "title" of the hyperlink (most browsers show as a tooltip)
 * @param unknown_type $linkName The name to use for the hyperlink
 * @param unknown_type $modname The desired module to call
 * @param unknown_type $func the desired function to call
 * @param unknown_type $args the optional arguments to pass (no ? necessary)
 * @return unknown The hyperlink, ie, <a href="... etc">linkname</a>
 */
function makeLinkPop($popupTitle, $linkName, $modname="", $func="", $args=array()) {
  return '<a title="'.$popupTitle.'" href="'.makeUrl($modname, $func, $args).'">'.$linkName.'</a>';
}

function makePop($popupTitle, $text) {
  return '<acronym title="'.$popupTitle.'">'.$text.'</acronym>';
}

function makeLinkNewWin($linkName, $modname="", $func="", $args=array()) {
  return '<a target="_blank" href="'.makeUrl($modname, $func, $args).'">'.$linkName.'</a>';
}

function makeLinkPopNewWin($popupTitle, $linkName, $modname="", $func="", $args=array()) {
  return '<a target="_blank" title="'.$popupTitle.'" href="'.makeUrl($modname, $func, $args).'">'.$linkName.'</a>';
}

function makeExternalLinkNewWin($linkName, $url, $args=array()) {
  return '<a target="_blank" href="'.$url.processArgsArray($args).'">'.$linkName.'</a>';
}
/**
 * Uses the oberLIB library from:
 * http://www.bosrup.com/web/overlib/
 * to create nice popup windows over hyperlinks.
 * 
 * Pass in desired overlibArgs per the documentation at the
 * above website. Use the helper function overLibArgs() which
 * will properly quote and excape the arguments for
 * javascriptland
 *   Ex:
 * makeLinkOverlib(overLibArgs("This is a sticky
   with a caption. And it is centered under the mouse! and it
   contains a single quote ' which will be excaped properly", 'STICKY', 'CAPTION', 'Sticky!', 'CENTER'), 'nameoflink');
   
   or, to create a real clickable link, pass optional module, function
   , and arguments as such:
 * makeLinkOverlib(overLibArgs("This is a sticky
   with a caption. And it is centered under the mouse! and it
   contains a single quote ' which will be excaped properly", 'STICKY', 'CAPTION', 'Sticky!', 'CENTER'), 'nameoflink', 'someModule', 'someFunc', 'ID=5');
 */
function makeLinkOverlib($overlibArgs, $linkName, $modname="", $func="", $args=array()) {
  setGlobalVar('usingOverlib', 1);
  if (isReallySet($modname) && isReallySet($func)) {
    $url = makeUrl($modname, $func, $args);
  }
  else {
    $url = 'javascript:void(0);';
  }
  $overlibCall = 'return overlib('.$overlibArgs.')';
  return '<a href="'.$url.'" onmouseover="'.$overlibCall.'" onmouseout="return nd();">'.$linkName.'</a>'; 
}

/**
 * Helper used when calling makeLinkOverlib or hrefOverlib.  Properly
 * quotes and escapes the arguments for javascriptland
 */
function overlibArgs($args) {
  $overlibArgs = func_get_args();
  foreach ($overlibArgs as $key=>$value) {
    $overlibArgs[$key] = "'".jsEscapeString($value)."'";
  }
  return implode(',', $overlibArgs);
}

/**
 * Create an anchor html tag with the nice overlib popup window 
 * Uses the oberLIB library from:
 * http://www.bosrup.com/web/overlib/
 * 
 * @param $overlibArgs the arguments per the overlib documentation (preprocessed
 * via the overlibArgs() function above 
 * @param $name The name of the link the user will see in the browser
 * @param $url The url the user will go to if the link is clicked otherwise javascript:void(0) if not set
**/
function hrefOverlib($overlibArgs, $linkName, $url = NULL) {
  setGlobalVar('usingOverlib', 1);
  $overlibCall = 'return overlib('.$overlibArgs.')';
  if ( ! isReallySet ( $url ) ) { 
      $url = 'javascript:void(0);';
  }
  return '<a href="'.$url.'" onmouseover="'.$overlibCall.'" onmouseout="return nd();">'.$linkName.'</a>'; 
}

/**
 * Generate a url using the module and function format of the framework
 * @param modname - registered name of module
 * @param func - module function
 * @param args - array of arguments to put on the URL
 * @param forceSecure - set to true to force a https type url to be generated
 *                     if false, or unset, we will generate a url in whatever mode we 
 *                     are currently in. 
 * @returns string
 * @return absolute URL for call
 * call with no arguments to generate generic home page url
 * 
 * credit: pnModUrl from post nuke
 */
function makeUrl($modname="", $func="", $args=array(), $forceSecure = false)
{
    if (empty($modname)) {
//     return "index.php";
       return FRONT_OF_URL;
    }
    // The arguments
    $urlargs[] = "module=$modname";
    if (!empty($func)) {
        $urlargs[] = "func=$func";
    }
    $urlargs = join('&', $urlargs);
//    $url = "index.php?$urlargs";
    if ($forceSecure) {
      $url = pnGetSecureBaseURL();
    }
    else {
      $url = pnGetBaseURL();
    }
    $url .= FRONT_OF_URL.$urlargs;

    $url .= processArgsArray($args);

    // The URL
    return $url;
}

function getSiteUrl($forceSecure = false) {
  if ($forceSecure) {
    $url = pnGetSecureBaseURL();
  }
  else {
    $url = pnGetBaseURL();
  }
  return $url;
}

function processArgsArray($args) {
  $url = '';
  // <rabbitt> added array check on args
  // April 11, 2003
  if (!is_array($args)) {
  // wes changed from postnuke to take string of args
  // instead of requiring them to be in an array
      $url .= '&'.$args;
  } else {
      foreach ($args as $k=>$v) {
          $k = urlencode($k);
          if (is_array($v)) {
              foreach($v as $l=>$w) {
                  $w = urlencode($w);
                  $url .= "&$k" . "[$l]=$w";
              }
          } else {
              $v = urlencode($v);
              $url .= "&$k=$v";
          }
      }
  }
  return $url;
}
/**
Clean variables in an array to try to ensure that hack attacks don't work
All user input (form posts) should be sent through here first before processing.
Taken from postNuke pnVarCleanFromInput, but modified
to take array and return new cleaned array (associative)
*/
function cleanArray($arrayToClean) {
  $search = array('|</?\s*SCRIPT.*?>|si',
                  '|</?\s*FRAME.*?>|si',
                  '|</?\s*OBJECT.*?>|si',
                  '|</?\s*META.*?>|si',
                  '|</?\s*APPLET.*?>|si',
                  '|</?\s*LINK.*?>|si',
                  '|</?\s*IFRAME.*?>|si',
                  '|STYLE\s*=\s*"[^"]*"|si');

  $replace = array('');
  $resarray = array();

  reset($arrayToClean);
  foreach ( $arrayToClean as $key=>$value ) {
    //if (get_magic_quotes_gpc()) {
    //  pnStripslashes($value);
    //}
    $resarray[$key] = preg_replace($search, $replace, $value);
  }
  return $resarray;
}
/**
 * ready user output
 * <br>
 * Gets a variable, cleaning it up such that the text is
 * shown exactly as expected
 * @param var variable to prepare
 * @param ...
 * @returns string/array
 * @return prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
function pnVarPrepForDisplay()
{
    // This search and replace finds the text 'x@y' and replaces
    // it with HTML entities, this provides protection against
    // email harvesters
    static $search = array('/(.)@(.)/se');

    static $replace = array('"&#" .
                            sprintf("%03d", ord("\\1")) .
                            ";&#064;&#" .
                            sprintf("%03d", ord("\\2")) . ";";');

    $resarray = array();
    foreach (func_get_args() as $ourvar) {

        // Prepare var
        $ourvar = htmlspecialchars($ourvar);

        $ourvar = preg_replace($search, $replace, $ourvar);

        // Add to array
        array_push($resarray, $ourvar);
    }

    // Return vars
    if (func_num_args() == 1) {
        return $resarray[0];
    } else {
        return $resarray;
    }
}
/**
 * strip slashes
 *
 * stripslashes on multidimensional arrays.
 * Used in conjunction with pnVarCleanFromInput
 * @access private
 * @param any variables or arrays to be stripslashed
 */
function pnStripslashes (&$value) {
    if(!is_array($value)) {
        $value = stripslashes($value);
    } else {
        array_walk($value,'pnStripslashes');
    }
}


/**
 * generate an authorisation key
 * <br>
 * The authorisation key is used to confirm that actions requested by a
 * particular user have followed the correct path.  Any stage that an
 * action could be made (e.g. a form or a 'delete' button) this function
 * must be called and the resultant string passed to the client as either
 * a GET or POST variable.  When the action then takes place it first calls
 * <code>pnSecConfirmAuthKey()</code> to ensure that the operation has
 * indeed been manually requested by the user and that the key is valid
 *
 * @public
 * @param modname the module this authorisation key is for (optional)
 * @returns string
 * @return an encrypted key for use in authorisation of operations
 */
function pnSecGenAuthKey($modname='')
{
    $key = pnSessionGetVar('rand');

    // Encrypt key
    $authid = md5($key);

    // Return encrypted key
    return $authid;
}

/**
 * confirm an authorisation key is valid
 * <br>
 * See description of <code>pnSecGenAuthKey</code> for information on
 * this function
 * @public
 * @returns bool
 * @return true if the key is valid, false if it is not
 */
function pnSecConfirmAuthKey()
{
  $postVars = cleanArray($_POST);

  $partkey = pnSessionGetVar('rand');

  if ((md5($partkey)) == $postVars['authid']) {
    // Match - generate new random number for next key and leave happy
    srand((double)microtime()*1000000);
    pnSessionSetVar('rand', rand());
    return true;
  }

  // Not found, assume invalid
  return false;
}

/**
 * get base URI for PostNuke
 * @returns string
 * @return base URI for PostNuke
 */
function pnGetBaseURI()
{
    // Get the name of this URI
    // Start of with REQUEST_URI
    if (isset($_SERVER['REQUEST_URI'])) {
        $path = $_SERVER['REQUEST_URI'];
    } else {
        $path = getenv('REQUEST_URI');
    }
    if ((empty($path)) ||
        (substr($path, -1, 1) == '/')) {
        // REQUEST_URI was empty or pointed to a path
        // Try looking at PATH_INFO
        $path = getenv('PATH_INFO');
        if (empty($path)) {
            // No luck there either
            // Try SCRIPT_NAME
            if (isset($_SERVER['SCRIPT_NAME'])) {
                $path = $_SERVER['SCRIPT_NAME'];
            } else {
                $path = getenv('SCRIPT_NAME');
            }
        }
    }
    $path = preg_replace('/[#\?].*/', '', $path);

// WR hack for when we're using urls without index.php
// ie: http://servername.com/somepath/?module=modname&func=showDetails
//
// this didn't work with the below code because when dirname was applied
// the "somepath" was lost since there was no index.php after "somepath"
    if (stristr($path, '.php')) {
      $path = dirname($path);
    }
    else {
      $path = substr($path, 0, strlen($path) -1);
    }
// WR end hack

    if (preg_match('!^[/\\\]*$!', $path)) {
        $path = '';
    }
    return $path;
}

function getProtocol() {
  // IIS sets HTTPS=off
  if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
      $proto = 'https://';
  } else {
      $proto = 'http://';
  }
  return $proto;
}

function getFrameworkUrl() {
  $proto = getProtocol();
  if ("http://" == $proto) {
    return str_replace("https://", "http://", FRAMEWORKURL);
  }
  else {
    return str_replace("http://", "https://", FRAMEWORKURL);
  }
}



/**
 * get base URL for PostNuke
 * @returns string
 * @return base URL for PostNuke
 */
function pnGetBaseURL()
{
    if (empty($_SERVER['HTTP_HOST'])) {
        $server = getenv('HTTP_HOST');
    } else {
        $server = $_SERVER['HTTP_HOST'];
    }
    if (empty($server)) {
      // if we can't figure out the server name, we may be running
      // in command line mode, and in this case, just return 
      // without the http://servername/pathname/ which will probably
      // work as it will allow the caller to build a url without an 
      // initial forward slash
      // This will work for the situation of having a CLI cron job creating
      // a file as a cache
      return;
    }

    $path = pnGetBaseURI();

    return getProtocol()."$server$path/";
}

function pnGetSecureBaseURL()
{
    if (empty($_SERVER['HTTP_HOST'])) {
        $server = getenv('HTTP_HOST');
    } else {
        $server = $_SERVER['HTTP_HOST'];
    }
    if (empty($server)) {
      // if we can't figure out the server name, we may be running
      // in command line mode, and in this case, just return 
      // without the http://servername/pathname/ which will probably
      // work as it will allow the caller to build a url without an 
      // initial forward slash
      // This will work for the situation of having a CLI cron job creating
      // a file as a cache
      return;
    }

    $path = pnGetBaseURI();

    return "https://$server$path/";
}

/**
 * Carry out a redirect
 * @param the URL to redirect to
 * @returns void
 * lineage: postnuke pnRedirect function
 */
function redirect($redirecturl)
{
    // Always close session before redirect
    if (function_exists('session_write_close')) {
        session_write_close();
    }

    if (preg_match('!^http!', $redirecturl)) {
        // Absolute URL - simple redirect
        Header("Location: $redirecturl");
        return;
    } else {
        // Removing leading slashes from redirect url
        $redirecturl = preg_replace('!^/*!', '', $redirecturl);

// NOTE, took this out, this was left over from postnuke function
// not sure why it was used, but it messed up stuff when we were using
// apache redirects

        // Get base URL
        $baseurl = pnGetBaseURL();
        Header("Location: $baseurl$redirecturl");
//        Header("Location: $redirecturl");
    }
    exit();
}

/**
 * Carry out a redirect, but first wait specified number of seconds
 * @param the URL to redirect to
 * @returns void
 */
function redirectAfterSeconds($redirecturl, $secondsToWait)
{
    // Always close session before redirect
    if (function_exists('session_write_close')) {
        session_write_close();
    }

    if (preg_match('!^http!', $redirecturl)) {
        // Absolute URL - simple redirect
        Header('refresh: '.$secondsToWait.'; url='.$redirecturl);
    } else {
        // Removing leading slashes from redirect url
        $redirecturl = preg_replace('!^/*!', '', $redirecturl);
        // Get base URL
        $baseurl = pnGetBaseURL();
        Header('refresh: '.$secondsToWait.'; url='.$baseurl.$redirecturl);
    }
    exit();
}


/**
 * ready operating system output
 * <br>
 * Gets a variable, cleaning it up such that any attempts
 * to access files outside of the scope of the PostNuke
 * system is not allowed
 * @param var variable to prepare
 * @param ...
 * @returns string/array
 * @return prepared variable if only one variable passed
 * in, otherwise an array of prepared variables
 */
function pnVarPrepForOS()
{
    static $search = array('!\.\./!si', // .. (directory traversal)
                           '!^.*://!si', // .*:// (start of URL)
                           '!/!si',     // Forward slash (directory traversal)
                           '!\\\\!si'); // Backslash (directory traversal)

    static $replace = array('',
                            '',
                            '_',
                            '_');

    $resarray = array();
    foreach (func_get_args() as $ourvar) {

        // Parse out bad things
        $ourvar = preg_replace($search, $replace, $ourvar);

        // Prepare var
        //if (!get_magic_quotes_runtime()) {
            $ourvar = addslashes($ourvar);
        //}

        // Add to array
        array_push($resarray, $ourvar);
    }

    // Return vars
    if (func_num_args() == 1) {
        return $resarray[0];
    } else {
        return $resarray;
    }
}

// below find a version of the sha1 function that accepts the extra argument for
// getting the result in binary form... this was added in php5, but we need it
// in php 4

/*
** Date modified: 1st October 2004 20:09 GMT
*
** PHP implementation of the Secure Hash Algorithm ( SHA-1 )
*
** This code is available under the GNU Lesser General Public License:
** http://www.gnu.org/licenses/lgpl.txt
*
** Based on the PHP implementation by Marcus Campbell
** http://www.tecknik.net/sha-1/
*
** This is a slightly modified version by me Jerome Clarke ( sinatosk@gmail.com )
** because I feel more comfortable with this
*/

function sha1_str2blks_SHA1($str)
{
   $strlen_str = strlen($str);

   $nblk = (($strlen_str + 8) >> 6) + 1;

   for ($i=0; $i < $nblk * 16; $i++) $blks[$i] = 0;

   for ($i=0; $i < $strlen_str; $i++)
   {
       $blks[$i >> 2] |= ord(substr($str, $i, 1)) << (24 - ($i % 4) * 8);
   }

   $blks[$i >> 2] |= 0x80 << (24 - ($i % 4) * 8);
   $blks[$nblk * 16 - 1] = $strlen_str * 8;

   return $blks;
}

function sha1_safe_add($x, $y)
{
   $lsw = ($x & 0xFFFF) + ($y & 0xFFFF);
   $msw = ($x >> 16) + ($y >> 16) + ($lsw >> 16);

   return ($msw << 16) | ($lsw & 0xFFFF);
}

function sha1_rol($num, $cnt)
{
   return ($num << $cnt) | sha1_zeroFill($num, 32 - $cnt);
}

function sha1_zeroFill($a, $b)
{
   $bin = decbin($a);

   $strlen_bin = strlen($bin);

   $bin = $strlen_bin < $b ? 0 : substr($bin, 0, $strlen_bin - $b);

   for ($i=0; $i < $b; $i++) $bin = '0'.$bin;

   return bindec($bin);
}

function sha1_ft($t, $b, $c, $d)
{
   if ($t < 20) return ($b & $c) | ((~$b) & $d);
   if ($t < 40) return $b ^ $c ^ $d;
   if ($t < 60) return ($b & $c) | ($b & $d) | ($c & $d);

   return $b ^ $c ^ $d;
}

function sha1_kt($t)
{
   if ($t < 20) return 1518500249;
   if ($t < 40) return 1859775393;
   if ($t < 60) return -1894007588;

   return -899497514;
}

function sha1_compat($str, $raw_output=FALSE)
{
  echo "using compat method of sha";
   if ( $raw_output === TRUE ) return pack('H*', sha1($str, FALSE));

   $x = sha1_str2blks_SHA1($str);
   $a =  1732584193;
   $b = -271733879;
   $c = -1732584194;
   $d =  271733878;
   $e = -1009589776;

   $x_count = count($x);

   for ($i = 0; $i < $x_count; $i += 16)
   {
       $olda = $a;
       $oldb = $b;
       $oldc = $c;
       $oldd = $d;
       $olde = $e;

       for ($j = 0; $j < 80; $j++)
       {
           $w[$j] = ($j < 16) ? $x[$i + $j] : sha1_rol($w[$j - 3] ^ $w[$j - 8] ^ $w[$j - 14] ^ $w[$j - 16], 1);

           $t = sha1_safe_add(sha1_safe_add(sha1_rol($a, 5), sha1_ft($j, $b, $c, $d)), sha1_safe_add(sha1_safe_add($e, $w[$j]), sha1_kt($j)));
           $e = $d;
           $d = $c;
           $c = sha1_rol($b, 30);
           $b = $a;
           $a = $t;
       }

       $a = sha1_safe_add($a, $olda);
       $b = sha1_safe_add($b, $oldb);
       $c = sha1_safe_add($c, $oldc);
       $d = sha1_safe_add($d, $oldd);
       $e = sha1_safe_add($e, $olde);
   }

   return sprintf('%08x%08x%08x%08x%08x', $a, $b, $c, $d, $e);
}

?>
