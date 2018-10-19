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
// the name of the directory where project specific
// modules, blocks, etc are stored
// 

// the php include search path must contain entries so that these directories
// can be found.  Generally, the project directory can be located by having "." in 
// the include path, and placing the project directory in the same directory
// as your main project website, and placing the starting index.php file there
// BTW, the starting index.php file should only need to contain 2 lines of PHP code:
//
//  require 'phpLiteFramework/plf.inc.php';
//  plfGo();
//

// first, check to see we're using php 5
// http://www.gophp5.org !!
//
$versionRequired = '5.0.0';
$versionRunning = phpversion();
if (version_compare(phpversion(), $versionRequired) < 0) {
  echo "The PHP Lite Framework requires at least PHP $versionRequired to run.<br/>";
  echo "You seem to be running version $versionRunning<br/>";
  echo "<br/><br/>If you are running in a mixed / shared hosting environment, you may be able to force PHP 5 by adding the following line to an .htaccess file:<br/><br/>";
  echo "<code>AddHandler application/x-httpd-php5 .php</code><br/><br/>";
  echo "The demo application contains a file named htaccessExample, which you may just rename to .htaccess<br/><br/>";
  echo "If you are exclusively running php 4, you will need to upgrade your php installation.<br/><br/>";
  echo 'For more information: <a href="http://gophp5.org" title="Support GoPHP5.org">
<img src="http://gophp5.org/sites/gophp5.org/buttons/goPHP5-100x33.png"
height="33" width="100" alt="Support GoPHP5.org" />
</a>';
  die();
}
// the framework directory is usually specified specifically in the include path
// for the entire system, so that multiple projects can share the same framework
// code.
//define('PROJECT_DIR', 'project');
//define('FRAMEWORK_DIR', 'phpLiteFramework');


//define('UTIL_DIR', FRAMEWORK_DIR.'/util');
//define('THIRDPARTY_DIR', FRAMEWORK_DIR.'/thirdParty');

// turn on all error reporting (development time only)
//error_reporting(E_ALL);

function getServerName() {
  if (isset($_SERVER['HTTP_HOST'])) {
    $serverName = $_SERVER['HTTP_HOST'];
  }
  elseif (isset($_SERVER['HOSTNAME'])) {
    $serverName = $_SERVER['HOSTNAME'];
  }
  elseif (isset($_SERVER['COMPUTERNAME'])) {
    $serverName = $_SERVER['COMPUTERNAME'];
  }
  // just get first portion if it includes dots
  // so that a user going to testmachine.interaldomain.org
  // and a user going to testmachine
  // will both see testmachine as the SERVERNAME and thus
  // both load the correct config files using that servername
  // in the filename (config.servername.php)
  $dotPosition = strpos($serverName, '.');
  if ($dotPosition) {
    return substr($serverName, 0, $dotPosition);
  }
  else {
    return $serverName;
  }
  
}

// fix some weird timezone issues that are cropping up with
// php5
$localTimezone = ini_get('date.timezone');
if (empty($localTimezone)) {
  ini_set('date.timezone', 'America/Chicago');
}

/**
 * Simple function to replicate PHP 5 behaviour.  Provides
 * fine grained access to the system time, for calculation
 * of execution times
 */
function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}


/**
 * used to define a constant, only defines it if it's not already been defined
 */
function setDefault($constantName, $value) {
  if (!defined($constantName)) {
    define($constantName, $value);
  }
}

function plfIncludeStage1($projectDir) {
  // set any constants that the peoject specific settings didn't define
  require 'util/defaultconfig.inc.php';
  
  // alter the include path so we can find the Zend Framework:
  set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__).'/thirdParty/zendFramework/'.ZEND_FRAMEWORK_VERSION_DIR.'/library/');

  // include the autoloader script for Zend Framework
  require 'Zend/Loader/Autoloader.php';
  
  // PLF methods to support the framework
  require 'util/frameworkFunctions.php';
}

function plfIncludeStage2($projectDir) {
  // definitions of commonly used methods across the project
  $globalFuncsFile = $projectDir.'/project/conf/globalFunctions.php';
  if (file_exists($globalFuncsFile)) {
    include $globalFuncsFile;
  }

  define('ADODB_DIR', dirname(__FILE__).'/thirdParty/dbAbstraction/adodb5'); // adodb instructions require this
  require ADODB_DIR.'/adodb.inc.php';
  
  // PLF designed wrapper functions around the ADOdb database abstraction library
  require 'util/dbFunctions.php';

  // a rounded border creator using CSS and not images
  require 'thirdParty/cssRoundedBorder/phpMyBorder2.class.php';
}


function plfGo($projectDir) {
  plfIncludeStage1($projectDir);

  // if someone is calling this in command line mode, just get out now... 
  // we've included the important code that they will need in their command line
  // script  
  if (!isset($_SERVER['HTTP_HOST'])) {
    // include the db stuff first, then get out
    plfIncludeStage2($projectDir);
    return;
  }
  // now, double check that someone's not in here via a call to another php script that is not
  // the index.php file (the central controller)
  // this could be a hack attempt, or an attempt to call one of the standalone scripts in the main
  // dir of the project that is intended to be run only interactively by someone on the server
  
  // NOTE: when we allow the user to set the script name (ie. to something different like index.jsp)
  // then we'll have to modify this check to use that variable instead.
  if ( substr( $_SERVER['SCRIPT_NAME'], strlen( $_SERVER['SCRIPT_NAME'] ) - strlen( 'index.php' ) ) !== 'index.php')  {
    $remoteAddr = (isset($_SERVER['REMOTE_ADDR']))?$_SERVER['REMOTE_ADDR']:'Unknown';
    $requestUri = (isset($_SERVER['REQUEST_URI']))?$_SERVER['REQUEST_URI']:'Unknown';
    logErrorSilent("A php script other than the main controller (index.php) is being called from a web environment, which is not allowed for security reasons. The request uri was: $requestUri The request is coming from remote ip address $remoteAddr - if you aren't expecting this, it may be an attempted security breach.  We will now halt execution of this script.");
    die();
  }

  // -----------------------------------------------------------
  // ------------------- OK, Here we go... ---------------------
  // -----------------------------------------------------------

  // bootstrap the Zend Framework so developers don't have to worry about includes
  $loader = Zend_Loader_Autoloader::getInstance();

  // force ssl mode if set in config file
  if (FORCESSL) {
    if ( !isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on' ) {
       header ('Location: https://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
       exit();
    }
  }

  // sitedown.txt is the default filename (see util/defaultconfig.inc.php)
  // if this file is present in the root of the web app, it will be shown instead of running 
  // the web app.
  $siteDownFileExists = file_exists(SITE_DOWN_FILENAME);
  if ($siteDownFileExists) {
    echo file_get_contents(SITE_DOWN_FILENAME);
    exit();
  }


  //clean all input so modules don't have to :
  $_REQUEST = cleanArray($_REQUEST);
  $_GET = cleanArray($_GET);
  $_POST = cleanArray($_POST);
  $_COOKIE = cleanArray($_COOKIE);

  $module = null;
  $func = null;

  if (isset($_GET['module']) && !is_array($_GET['module'])) {
    $module = $_GET['module'];
  }
  if (isset($_GET['func']) && !is_array($_GET['func'])) {
    $func = $_GET['func'];
  }
  // save off the current arguments used... for use by getCurrentArgsArray()  
  setCurrentArgsArray($_GET);

  // run the prefilter if the file exists
  // this is a good way to apply security at the module/function level
  // just return some message from the pre method, and it will
  // be displayed instead of calling the requested function
  $preFilterFilename = $projectDir.'/project/filters/pre.php';
  if (file_exists($preFilterFilename)) {
    include $preFilterFilename;
    $preFilterMsg = pre($module, $func);
  }

  // if the prefilter returned something display it, else
  // run the requested function
  if (!empty($preFilterMsg)) {
    $return = $preFilterMsg;
  }
  else {
    plfIncludeStage2($projectDir);
    pushRequestUrl();

    if (empty($module)) {
      loadModuleFile($projectDir, DEFAULTMODULE);
      $return = callFunc(DEFAULTMODULE, DEFAULTFUNC);
    }
    elseif ('showStatusMsg' == $module) {
      // this "persistent" status message is propagated via cookie
      // to avoid using the session which would result in unncessary
      // session creation on the server in the case where sessions are not
      // explicitly being used.  didn't use url because this makes the url messy
      $return = getSessionVar('statusMsgSticky');
    }
    else {
      loadModuleFile($projectDir, $module);
      $return = callFunc($module, $func);
      if ($return == '' && !getDirectOutput()) {
        loadModuleFile($projectDir, DEFAULTMODULE);
        $return = callFunc(DEFAULTMODULE, DEFAULT404FUNC);
      }
    }
  }

  // if setDirectOutput() has been called in the module function, we just exit
  // here without doing the template stuff.  We assume that the function itself
  // is echoing data directly, or calling some other function to stream data back.
  // this is used typically for something like pdf streaming, where a library
  // echoes the pdf stream directly, and would be thrown off by our standard
  // html response stream that is output below.
  //
  // another use is when streaming image data back to the browser, for example, if
  // image blobs are stored in the db, and we create a module function to
  // to retrieve the specified image
  if (getDirectOutput()) {
    // temporary fix to new stuff with trapping the echoed data
    echo $return;
    exit;
  }

  // if we make it here, we know we're using a template and need to start
  // processing the blocks and putting the body in the correct place...
  
  $contents = file_get_contents(getTemplate().'.html');

  // take care of the blocks
  $tokens = preg_match_all('/\[\[([a-z0-9]*)\]\]/i', $contents, $matches);
  $newStuff = $contents;
  foreach ($matches[1] as $match) {
    $newStuff = str_replace('[['.$match.']]', callBlock($projectDir, $match), $newStuff);
  }

  $pageTitle = getPageTitle();

  // take care of the page title in the template (generally displayed above the body)
  $newStuff = str_replace('{{pageTitle}}', getPageTitle(), $newStuff);

  // take care of the page title with the site name appended (generally used for the <title> attribute
  // of the html pages (here we strip html tags for clarity)
  if (isset($pageTitle)) {
    $pageTitleWithSiteName = WEBSITENAME.' - '.$pageTitle;
  }
  else {
    $pageTitleWithSiteName = WEBSITENAME;
  }

  $newStuff = str_replace('{{pageTitleWithSiteName}}', strip_tags($pageTitleWithSiteName), $newStuff);

  $headContent = '';
  $frameworkUrl = getFrameworkUrl();
  // reference the javascript we need using an internal function:
  $headContent .= '<script src="'.$frameworkUrl.'/util/javascript/plf.js" type="text/javascript"></script><script src="'.$frameworkUrl.'/thirdParty/scriptaculous/scriptaculous-js-1.6.0/lib/prototype.js" type="text/javascript"></script><script src="'.$frameworkUrl.'/thirdParty/scriptaculous/scriptaculous-js-1.6.0/src/builder.js" type="text/javascript"></script><script src="'.$frameworkUrl.'/thirdParty/scriptaculous/scriptaculous-js-1.6.0/src/controls.js" type="text/javascript"></script><script src="'.$frameworkUrl.'/thirdParty/scriptaculous/scriptaculous-js-1.6.0/src/dragdrop.js" type="text/javascript"></script><script src="'.$frameworkUrl.'/thirdParty/scriptaculous/scriptaculous-js-1.6.0/src/effects.js" type="text/javascript"></script><script src="'.$frameworkUrl.'/thirdParty/scriptaculous/scriptaculous-js-1.6.0/src/slider.js" type="text/javascript"></script>';

  // set up a style on the acronym tag since IE doesn't apply the dotted underline
  //to the <acronym> tagged page elements, like FF does...
  $headContent .= '<style type="text/css">acronym{border-bottom: #000 1px dotted}</style>';
  
  if (1 == getGlobalVar('usingOverlib')) {
    $headContent .= '<script type="text/javascript" src="'.$frameworkUrl.'/thirdParty/overlib/overlib/overlib.js"><!-- overLIB (c) Erik Bosrup --></script>';
  }
  $headContent .= getHeadContent();
  $newStuff = str_replace('{{headContent}}', $headContent, $newStuff);

  // do the body attribute

  $newStuff = str_replace('{{bodyAttribute}}', getBodyAttribute(), $newStuff);

  // take care of the transient status message:
  $statusMsg = getSessionVar('statusMsg');
  if (isset($statusMsg)) {
    delSessionVar('statusMsg');
    $newStuff = str_replace('{{statusMessage}}', '<div style="color:red">'.$statusMsg.'</div>', $newStuff);
  }
  else {
    $newStuff = str_replace('{{statusMessage}}', '', $newStuff);
  }

  // take care of the main body
  $newStuff = str_replace('{{body}}', $return, $newStuff);

  $end = microtime_float();

//  $sessId = session_id();

//  if (isset($sessId)) {
//    setNewToken();
//  }

  // finally, display whole mess to the user
  echo $newStuff;
}

?>
