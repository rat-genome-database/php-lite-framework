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
/**
 * Handy functions to wrap the ADOdb database abstration library
 * http://adodb.sourceforge.net/
 * 
 * TODO:
 * wrap ADODB's support for transactions:
 * http://phplens.com/adodb/tutorial.smart.transactions.html
 * 
 */


//putenv('ORACLE_HOME='.ORACLE_HOME); // this allows it to look up the oracle error msg
//define('ADODB_DIR', '../thirdParty/dbAbstraction/adodb'); // adodb instructions require this
//require ADODB_DIR.'/adodb.inc.php';
// uncomment if using the adodb provided "pager" for long db results
//require ADODB_DIR.'/adodb-pager.inc.php';

// this is for a mysql timestamp column
// 20050324100917
function parseTimestamp($timedate) {
  $year=substr($timedate,0,4);
  $month=substr($timedate,4,2);
  $day=substr($timedate,6,2);
  $hour=substr($timedate,8,2) + pnSessionGetVar('timeoffset');
  $min=substr($timedate,10,2);
  $sec=substr($timedate,12,2);
  return date('m/j/y g:i a', mktime($hour,$min,$sec,$month,$day,$year));
}

// this is for a mysql datetime column
// 2005-03-03 15:05:06
function parseDatetime($timedate) {
  $year=substr($timedate,0,4);
  $month=substr($timedate,5,2);
  $day=substr($timedate,8,2);
  $hour=substr($timedate,11,2) + pnSessionGetVar('timeoffset');
  $min=substr($timedate,14,2);
  $sec=substr($timedate,17,2);
  return date('m/j/y g:i a', mktime($hour,$min,$sec,$month,$day,$year));
}


/**
 * prepares the sql statement, precompiling it if the db supports it
 * (ie. for oracle, use :field1, :field2, etc as placeholders)
 *
 * follow this with executePrepared()
 *
 */
function prepare($sql, $dbname = NULL) {
  $dbconn = getNamedConnection($dbname);
  $result = $dbconn->Prepare($sql);
  checkError($dbconn);
  return $result;
}


function getSeq($seqName, $dbname = NULL) {
  return fetchField("select $seqName.nextval from dual", $dbname);
}

/**
 * Override the default fetch mode and switch to
 * NUMERIC (ie. ADODB_FETCH_NUM)
 * 
 *          [0] => 49
            [2] => 294
            [3] => 12/04/1996
            
            instead of:
            
            [SESS_ID] => 49
            [SUBJECT_ID] => 294
            [DATE] => 12/04/1996
 */
function setNumFetchMode($dbname = NULL) {
  $dbconn = getNamedConnection($dbname);
  $dbconn->setFetchMode(ADODB_FETCH_NUM);
}

/**
 * Override the current fetch mode and switch to ASSOCIATIVE
 * (ie. ADODB_FETCH_ASSOC)
            
            [SESS_ID] => 49
            [SUBJECT_ID] => 294
            [DATE] => 12/04/1996

            instead of:
 *          
 *          [0] => 49
            [2] => 294
            [3] => 12/04/1996
            
 */
function setAssocFetchMode($dbname = NULL) {
  $dbconn = getNamedConnection($dbname);
  $dbconn->setFetchMode(ADODB_FETCH_ASSOC);
}

function plfConstant($constantName) {
  if (defined($constantName)) {
    $theValue = constant($constantName);
  }
  else {
    $theValue = getArrayValueAtIndex($_ENV, $constantName);
  }
  return $theValue;
}

function &getNamedConnection($connectionName = NULL) {
  $connectionNameOrig = $connectionName;
  if ($connectionName != NULL) {
    $connectionName .= '_';
  }
  if (plfConstant($connectionName.'DBHOSTNAME') == NULL) {
    if (isset($connectionName)) {
      logError("You are attempting to use a database named $connectionNameOrig, but the database connection settings for this database are not defined.  To use this database, you must define constants named after this connection name, such as {$connectionName}DBHOSTNAME, {$connectionName}DBUSERNAME, etc. These constants must be defined in the index.php file or provided as system environment variables, prior to calling plfGo().  See the plfDemo project for examples");
      die();
    }
    else {
      logError("You are attempting to use a database function but the database connection settings are not defined.  To use the database functions, you must define constants such as DBHOSTNAME, DBUSERNAME, etc. These constants must be defined in the index.php file or provided as system environment variables, prior to calling plfGo().  See the plfDemo project for examples");
      die();      
    }
  }
  
  // this is not heavily commented in order to protect projects using this code
  // from search engines that choose to index source code from open source
  // projects
  // -----
  // if you don't understand what it's doing, don't bother
  //  
  $p = plfConstant($connectionName.'DBPASSWORD');
  if ($p == NULL) {
    $ep = plfConstant($connectionName.'DBPASSWORD_EP');
    $ky = plfConstant($connectionName.'DBPASSWORD_KY');
    $alg = plfConstant($connectionName.'DBPASSWORD_ALG');
    $p = $alg($ep, $ky);
  }
  
  return getConnection(plfConstant($connectionName.'DBUSERNAME'), $p, plfConstant($connectionName.'DBNAME'), plfConstant($connectionName.'DBHOSTNAME'), plfConstant($connectionName.'ADODBDRIVER'));
}

function oracleSysdate() {
  // this is the php equivalent of  
// $theConnection->NLS_DATE_FORMAT = 'MM/DD/YYYY';
// which is set in getConnection
  return date('m/d/Y');
}

/**
 * Convert the given date string to a date string using the framework
 * default of MM/DD/YYYY for the oracle default date format.
 * 
 * The input string can be in any format that PHP's strtotime
 * method likes.
 * see:
 * http://us2.php.net/strtotime
 * and:
 * http://www.gnu.org/software/tar/manual/html_node/tar_109.html
 * 
 */
function convertToOracleDate($date) {
  // this is the php equivalent of  
// $theConnection->NLS_DATE_FORMAT = 'MM/DD/YYYY';
// which is set in getConnection
  if (!empty($date)) {
    return (date("m/d/Y", strtotime($date)));
  }
}


function &getConnection($dbusername = DBUSERNAME, $dbpassword = DBPASSWORD, $dbname = DBNAME, $dbhostname = DBHOSTNAME, $adodbdriver = ADODBDRIVER) {
  $globalsIndexName = $dbusername.'|'.$dbname.'|'.$dbhostname.'|'.$adodbdriver;

  $numTries = 2;
  $secondsSleepBetweenTries = 2;
  if (DEMO_MODE) {
    logWarning('Demo mode is on.  Only use DEMO_MODE when you are not doing database operations');
  }

  // keep as many connection objects in the $globals array as we are called upon to declare
  // ie, getConnection('username1', 'password1');
  // and
  // getConnection('username2', 'password2');
  // would create 2 connections in the globals array
  if (isset($GLOBALS[$globalsIndexName])) {
    $theConnection = &$GLOBALS[$globalsIndexName];
  }

  if (isset($theConnection)) {
    return $theConnection;
  }
  else {
    $theConnection = &NewADOConnection($adodbdriver);
    // force indexing by the field names, override this using setNumFetchMode()
    $theConnection->SetFetchMode(ADODB_FETCH_ASSOC);

    // below is oracle specific stuff for dealing with dates:
    // Oracle provides the NLS_DATE_FORMAT session setting that
    // will set the default date format for all inbound and outbound
    // date formats
    // This is the same format we use for the date popup, so all is well
    $theConnection->NLS_DATE_FORMAT = 'MM/DD/YYYY';
    // this call may trigger a warning
    if (USE_PERSISTENT_DB_CONNECT) {
      $return = $theConnection->PConnect($dbhostname, $dbusername, $dbpassword, $dbname);
    }
    else {
      $return = $theConnection->Connect($dbhostname, $dbusername, $dbpassword, $dbname);
    }
    $tries = 0;
    while ((!$return || $theConnection->ErrorNo() != 0) && $tries < $numTries) {
      $tries += 1;
      // don't log msg or sleep first time through, since most times we're able to reconnect
      // first time after a failure, and don't want to have to wait the full
      // second or display any msg
      if ($tries > 1) {
        logWarning('unable to connect to db --  Sleeping '.$secondsSleepBetweenTries. ' seconds, then will try again, remaining tries: '.($numTries - $tries). ' -- ErrorNo: '.$theConnection->ErrorNo(). ' ErrorMsg: '.$theConnection->ErrorMsg());
        sleep($secondsSleepBetweenTries);
      }
      // attempt to clear the apc cache, if we're using APC:
      clearPhpCache();
      // sleep 1 second, just to give it a sec to breathe...
      sleep(1);
      $theConnection = NewADOConnection($adodbdriver);
      $theConnection->NLS_DATE_FORMAT = 'MM/DD/YYYY';
      if (USE_PERSISTENT_DB_CONNECT) {
        $return = $theConnection->PConnect($dbhostname, $dbusername, $dbpassword, $dbname);
      }
      else {
        $return = $theConnection->Connect($dbhostname, $dbusername, $dbpassword, $dbname);
      }
    }
    if (!$return)  {
      $message = 'Unable to connect to db, tried '.$tries.' times -- ErrorNo: '.$theConnection->ErrorNo(). ' ErrorMsg: '.$theConnection->ErrorMsg();
      logError($message);
      die();
    }
    else if ($theConnection->ErrorNo() != 0) {
      $message = 'pconnect continued to set non zero error num, even after '.$tries.' tries -- ErrorNo: '.$theConnection->ErrorNo(). ' ErrorMsg: '.$theConnection->ErrorMsg();
      logError($message);
      die();
    }
    else if ($tries > 1) {
      logWarning('successfully connected after failing '.$tries.' time(s)');
    }
    else {
//      trigger_error('dont log this , success on first connect', E_USER_WARNING);
    }
    $theConnection->debug = DB_DEBUG_ON;
    $GLOBALS[$globalsIndexName] = &$theConnection;
    return $theConnection;
  }
}

/**
 * Utilizes the adodb library to properly quote and escape the $value
 * so that anything can be inserted into the db, including special characters
 * that happen to be used as special delimiters on the insert by the db.
 *
 * For example, the single quote is typically use to enclose strings, therefore
 * if it is part of the data you want to insert, it must be escaped.
 *
 * The methods below that form insert/update strings from the Form object
 * (getFieldsForInsert() , getFieldsForUpdate(), etc...)
 * will all call this method when building the strings, so the only time
 * you need to call this directly is if you are forming statements directly
 * without the help of the Form object
 *
 */
function dbQuoteString($value, $dbname = NULL) {
  if (DEMO_MODE) {
    return "'$value'";
  }
  else {
    $dbconn = getNamedConnection($dbname);
    return $dbconn->qstr($value);
  }
}
/**
 * Given an array of fields, performs a dbQuoteString operation
 * on each field, altering the array to contain the db quoted
 * version of each value
 */
function dbQuoteStrings(&$values, $dbname = NULL) {
  foreach ($values as $key=>$value) {
    $values[$key] = dbQuoteString($value, $dbname);
  }
  return ($values);
}

function dbQuoteStringsUsingNull(&$values, $dbname = NULL) {
  dump($values);
  foreach ($values as $key=>$value) {
    $values[$key] = dbQuoteString($value, $dbname);
  }
  return ($values);
}

/**
 * Perform a dbQuoteString operation on every field of the provided
 * array of values.  Additionally this function will trim the values.
 *
 * @param Array $values the array of values to work with (it is modified)
 * @param String $dbname optional name of database connection
 * @return Array the modified array (the array passed in is also modified)
 */
function dbQuoteStringsAndTrim(&$values, $dbname = NULL) {
  foreach ($values as $key=>$value) {
    $values[$key] = dbQuoteString(trim($value), $dbname);
  }
  return ($values);
}

/**
 * Checks for a non zero error number and outputs the error message
 * and the error number, then dies :)
 *
 */
function checkError($dbconn) {
  if ($dbconn->ErrorNo() != 0) {
      trigger_error('dberrorMsg: '.$dbconn->errorMsg().' dberrorNo: '.$dbconn->errorNo(), E_USER_ERROR);
      die();
  }
}

/**
 * Use this to run db statements and ignore any error that comes back.
 * Use sparingly, perhaps for a situation where you don't want to bother
 * checking for a dupliate row and instead want to rely on the database's
 * constraint to prevent dups.  In this case you'd fire off the insert
 * without first checking if it's there. The db would allow the initial one, 
 * and give a constraint violation on subsequent ones, but it would be ignored
 * with this function.
 * 
 * Of course, this also prevents you from knowing that the db is down, but 
 * the idea is that there are enough other db calls going on in the application
 * so you would know soon enough that there is a db problem.
 * 
 * Future enhancement would be to discern which db errors were constraint type
 * violations and which were more serious database connection errors, and only
 * ignore the less serious ones...
 * 
 * NOTE: the adodb framework will log all db errors, so set SHOWEVERYTHINGELSETOUSER
 *  to false to prevent a message from being echoed.
 */
function executeIgnoreError($statement, $dbname = NULL) {
  $dbconn = getNamedConnection($dbname);
  $rs = &$dbconn->execute($statement);
  return $rs;
}



function selectLimit($statement, $limit, $offset, $dbname = NULL){
  $dbconn = getNamedConnection($dbname);
  $rs = &$dbconn->selectLimit($statement, $limit, $offset);
  checkError($dbconn);
  return $rs;
}

/**
 * Execute statement and return result set
 * (connection optional)
 *
 * also used in place of fetchRecords when we have a huge
 * result set and need to iterate over it via the resultset -
 * fetchRecords, since it fills up an array with the result,
 * cannot be used with huge result sets that are too big
 * to be stored in local memory.
 * 
 * Usage:
  $rs = executeQuery("select * from $tableName");
  while (!$rs->EOF) {
    extract($rs->fields);
    //do something with the fields
    $rs->moveNext();
  }

 *
 */
function executeQuery($statement, $dbname = NULL){
  $dbconn = getNamedConnection($dbname);
  $rs = &$dbconn->execute($statement);
  checkError($dbconn);
  return $rs;
}

/** 
 * Execute statement and return count of rows
 * updated (connection optional)
 * 
 */
function executeUpdate($statement, $dbname = NULL) {
  $dbconn = getNamedConnection($dbname);
  $dbconn->execute($statement);
  $rows = $dbconn->affected_rows();
  checkError($dbconn);
  return $rows;
}

/**
 * Execute statement (connection optional)
 * 
 * This function returns nothing.  If you want
 * a result set returned, use executeQuery().
 * If you want the count of the rows updated,
 * use executeUpdate().  If you could care less,
 * go ahead and use this one.
 */
function execute($statement, $dbname = NULL) {
  $dbconn = getNamedConnection($dbname);
  $dbconn->execute($statement);
  checkError($dbconn);
}

/**
 * Execute statement and return result set
 * (connection optional)
 */
function executePrepared($statement, $fields, $dbname = NULL){
  $dbconn = getNamedConnection($dbname);
  $rs = &$dbconn->execute($statement, $fields);
  checkError($dbconn);
  return $rs;
}

/**
 * Fetches multiple records from the database, moving them all into memory
 * and returing them as an array (associative) Uses $limit and $offset
 * to ease paging through large results
 */
function fetchRecordsLimit($sql, $limit, $offset, $dbname = NULL)
{
  $dbconn = getNamedConnection($dbname);
  $rs = &$dbconn->SelectLimit($sql, $limit, $offset);
  checkError($dbconn);
  $items = &$rs->GetRows();
  checkError($dbconn);
  return $items;
}


/**
 * Fetches multiple records from the database, moving them all into memory
 * and returing them as an array (associative)
 */
function fetchRecords($sql, $dbname = NULL)
{
  $dbconn = getNamedConnection($dbname);
  $rs = &$dbconn->Execute($sql);
  checkError($dbconn);
  $items = &$rs->GetRows();
  checkError($dbconn);
  return $items;
}

/**
 * Fetches a single record from the database, moving it into memory
 * and returing it as an array (associative)
 */
function fetchRecord($sql, $dbname = NULL)
{
  $dbconn = getNamedConnection($dbname);
  $item = &$dbconn->GetRow($sql);
  checkError($dbconn);
  return $item;
}

/**
 * Fetch a single field from a query (connection optional)
 * ex:
 * $maxYear = fetchField('select max(year) from tableA');
 */
function fetchField($sql, $dbname = NULL)
{
  $dbconn = getNamedConnection($dbname);
  $field = $dbconn->GetOne($sql);
  checkError($dbconn);
  return $field;
}


/**
 * Gets the last insert id, (mysql has this)
 */
function getLastInsertId($dbname = NULL)
{
  $dbconn = getNamedConnection($dbname);
  $item = &$dbconn->GetOne('select last_insert_id()');
  checkError($dbconn);
  return $item;
}


/**
 * Build an array that can be directly used in a select field
 * NOTE: the query *MUST* select 2 columns of data
 *
 * EX:
 * $accountTypes = fetchArrayForSelectField('select account_id, description from accountcodes');
 * $theForm->addSelect('ACCTTYPE', 'Account Type', $accountTypes, true);
 *
 */
function fetchArrayForSelectField($sql, $dbname = NULL)
{
  $dbconn = getNamedConnection($dbname);
  $array = &$dbconn->GetAssoc($sql);
  checkError($dbconn);
  return $array;
}

/**
 * Produce a string containing all the editable field names
 * in the form, separated by commas.  Used in forming
 * insert statements.
 *
 * EX:
 *
 * $theForm->addElement('NAME', 'Enter your name', 20, 20, true);
 * $theForm->addElement('ADDRESS', 'Enter your address', 40, 50, true);
 *
 * echo getFieldNames();
 *
 * produces:
 * (NAME, ADDRESS)
 *
 */
function getFieldNames(&$form) {
  $toReturn = ' (';
  $elementsToUse = $form->getEditableElements();
  foreach ($elementsToUse as $element) {
    $toReturn .= $element->name.', ';
  }
  return substr($toReturn, 0, -2).') ';
}

/**
 * Produce a string containg the field names, followed by their
 * values, useful for an insert statement.
 *
 * EX:
 *
 * $theForm->addElement('NAME', 'Enter your name', 20, 20, true);
 * $theForm->addElement('ADDRESS', 'Enter your address', 40, 50, true);
 *
 * after the user has submitted data for this form...
 *
 * echo getFieldsForInsert($theForm);
 *
 * produces:
 * (NAME, ADDRESS) values ('Tim Smith', '123 Main Street')
 *
 */
function getFieldsForInsert(&$form) {
  $toReturn = getFieldNames($form).' values (';
  $elementsToUse = $form->getEditableElements();
  foreach ($elementsToUse as $element) {
    $toReturn .= dbQuoteString($element->getValueForDb()).', ';
  }
  return substr($toReturn, 0, -2).') ';
}

function getFieldsForInsertWithNull(&$form) {
  $toReturn = getFieldNames($form).' values (';
  $elementsToUse = $form->getEditableElements();
  foreach ($elementsToUse as $element) {
//    $toReturn .= dbQuoteString($element->getValueForDb()).', ';
    $theValue = $element->getValueForDb();
    if ('' == $theValue) {
      $toReturn .= ' null, ';
    }
    else {
      $toReturn .= dbQuoteString($theValue).', ';
    }
    
  }
  return substr($toReturn, 0, -2).') ';
}


function getFieldsForInsertFromArray(&$array) {
  $toReturn = ' ('.implode(', ', array_keys($array)).') values ';
  $arrayValues = array_values($array);
  dbQuoteStrings($arrayValues);
  $toReturn .= '('.implode(', ', $arrayValues).') ';
  return $toReturn;
}

function getFieldsForUpdateFromArray(&$array) {
  $toReturn = ' ';
  foreach ($array as $fieldname=>$value) {
    $toReturn .= $fieldname.' = '.dbQuoteString($value).', ';
  }
  return substr($toReturn, 0, -2).' ';  
}

/**
 * Produce a string containing the field names and their values,
 * joined with the equal sign, suitable for an update statement
 *
 * EX:
 *
 * $theForm->addElement('NAME', 'Enter your name', 20, 20, true);
 * $theForm->addElement('ADDRESS', 'Enter your address', 40, 50, true);
 *
 * after the user has submitted data for this form...
 *
 * echo getFieldsForUpdate($theForm);
 *
 * produces:
 * NAME = 'Tim Smith', ADDRESS = '123 Main Street'
 *
 */
function getFieldsForUpdate(&$form) {
  $toReturn = ' ';
  $elementsToUse = $form->getEditableElements();
  foreach ($elementsToUse as $element) {
    $toReturn .= $element->name.' = '.dbQuoteString($element->getValueForDb()).', ';
  }
  return substr($toReturn, 0, -2).' ';
}

function getFieldsForUpdateWithNull(&$form) {
  $toReturn = ' ';
  $elementsToUse = $form->getEditableElements();
  foreach ($elementsToUse as $element) {
    $theValue = $element->getValueForDb();
    if ('' == $theValue) {
      $toReturn .= $element->name.' = null, ';
    }
    else {
      $toReturn .= $element->name.' = '.dbQuoteString($theValue).', ';
    }
  }
  return substr($toReturn, 0, -2).' ';
}

function getNonEmptyFieldsForUpdate(&$form) {
  $toReturn = ' ';
  $elementsToUse = $form->getEditableElements();
  foreach ($elementsToUse as $element) {
    $value = $element->getValueForDb();
    if (isReallySet($value)) {
      $toReturn .= $element->name.' = '.dbQuoteString($value).', ';
    }
  }
  return substr($toReturn, 0, -2).' ';
}



/**
 * Gets the editable elements in the provided form 
 * that are all prefixed by the specified tablename
 * 
 * NOTE: the names of the elements returned will be
 * changed to _not_ include the prefix.
 * 
 * ex: if you have a field of TABLE1-FIRST_NAME
 * and you call this method looking for elements
 * by table TABLE1, the resulting element returned
 * will have a field named FIRST_NAME
 */
function getEditableElementsByTable(&$form, $tableName) {
  $elementsToUse = $form->getEditableElements();
  $editableElements = array();
  foreach ($elementsToUse as $element) {
    $toSearchFor = $tableName.'-';
    if (strpos($element->name, $toSearchFor) === 0) {
      $editableElements[] = $element;
    }
  }
  return $editableElements;
}

/**
 * Similar to getFieldsForInsert, however this version
 * only returns data for fields whose names are prefixed
 * by the specified $tableName (using a hyphen)
 */
function getFieldsForInsertByTable(&$form, $tableName) {
  $toReturn = ' (';
  $elementsToUse = getEditableElementsByTable($form, $tableName);
  $names = '';
  $values = '';
  foreach ($elementsToUse as $element) {
    $names .= str_replace($tableName.'-', '', $element->name).', ';
    $values .= dbQuoteString($element->getValueForDb()).', ';
  }
  return '('.substr($names, 0, -2).') values ('.substr($values, 0, -2).') ';
}

/**
 * Similar to getFieldsForUpdate, however this version
 * only returns data for fields whose names are prefixed
 * by the specified $tableName (using a hyphen)
 */

function getFieldsForUpdateByTable(&$form, $tableName) {
  $toReturn = ' ';
  $elementsToUse = getEditableElementsByTable($form, $tableName);
  foreach ($elementsToUse as $element) {
    $toReturn .= str_replace($tableName.'-', '', $element->name).' = '.dbQuoteString($element->getValueForDb()).', ';
  }
  return substr($toReturn, 0, -2).' ';
}

function dbDate($format, $fieldName, $dbname = NULL) {
  $dbconn = getNamedConnection($dbname);
  return $dbconn->SQLDate($format, $fieldName).' as '.$fieldName;
}


?>