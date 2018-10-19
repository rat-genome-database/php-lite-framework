<?php
/**
 * Default configuration options that may be overriden in the project's 
 * index.php file (be sure to change from setDefault to define
 * if copying from here)
 * 
 * define('CONSTANT_NAME', value);
 * 
 */


// Database settings
// do you want to see the sql statements being executed?
setDefault('DB_DEBUG_ON', false);
// set to true to be able to use dbQuoteString function without having a database set up
setDefault('DEMO_MODE', false);
// specify whether to use PConnect or Connect when connecting to the database
// PConnect will generally attempt to share/reuse database connections for better performance
setDefault('USE_PERSISTENT_DB_CONNECT', true);

// the ones below have no logical defaults, so just copy
// and put them in your index.php
//  define('DBHOSTNAME', 'somehostname');
//  define('DBUSERNAME', 'someusername');
//  define('DBPASSWORD', 'somepassword');
//  define('DBNAME', 'somedbservicename.something.else');  

// General Framework Settings

// the directory holding the Zend Framework (minimal).. ie. just the libraries
// projects can override if they want to stick to an older version when we start
// including a newer version in PLF
setDefault('ZEND_FRAMEWORK_VERSION_DIR', '1.10.1');


// If a file with this name exists in the root of the web application, its contents
// will be displayed instead of the normal web application
setDefault('SITE_DOWN_FILENAME', 'sitedown.txt');


setDefault('SESSION_EXPIRE_MINUTES', 30);

// this specifies the value to store on the db when user checks 
// a form checkbox, should probably use a number for database
// efficiency, if possible, otherwise set it to what your 
// existing database uses if you're inheriting a system
setDefault('CHECKBOX_CHECKED', 'T');
// this specifies the value to store on the db when user leave
// a form chekbox unchecked
setDefault('CHECKBOX_UNCHECKED', 'F');

// this specifies the value to display when calling displayCheckbox
// for a database value that was stored using a form checkbox field
setDefault('CHECKBOX_CHECKED_DISPLAY', 'T');
// see CHECKBOX_CHECKED_DISPLAY above, this is what to show if 
// checkbox wasn't checked
setDefault('CHECKBOX_UNCHECKED_DISPLAY', 'F');

// sets the module and function to call if the user loads the homepage, ie
// goes to the website without a "module= function=" type url
setDefault('DEFAULTMODULE', 'mainPage');
setDefault('DEFAULTFUNC', 'contents');
// the function to load in the DEFAULTMODULE if a page url is malformed
setDefault('DEFAULT404FUNC', '404Message');
// the function to load if the user's session has expired
// and we attempt to grab a session variable
setDefault('DEFAULT_SESSION_EXPIRED_FUNC', 'sessionExpiredMessage');

// the name of the website for the browser title bar
setDefault('WEBSITENAME', 'Website Name Override This Please');

// indicates if errors with stack trace should be echoed to the browser
// in addition to being logged
// NOTE: we don't allow error logging to be suppressed from the log file
// like we allow the other severities to be suppressed.  (see LOGWARNINGS,
// LOGNOTICES, and LOGEVERYTHINGELSE)
setDefault ('SHOWERRORSTOUSER', true );

// the following 2 settings are independent:
// indicates if warnings should be written to the webserver log file
setDefault('LOGWARNINGS', true);
// indicates if warnings should be echoed to the user
setDefault('SHOWWARNINGSTOUSER', true);

// the following 2 settings are independent:
// indicates if notices should be written to the webserver log file
setDefault('LOGNOTICES', true);
// indicates if notices should be echoed to the user
setDefault('SHOWNOTICESTOUSER', true);


// the following 2 settings are independent:
// indicates if any other errors, notices, warnings etc
// not specifically raised by the framework (ie. those raised
// by third party libs, or the PHP language itself) should
// be logged to the webserver log file
setDefault('LOGEVERYTHINGELSE', true);
// indicates if any other errors, notices, warnings etc
// not specifically raised by the framework (ie. those raised
// by third party libs, or the PHP language itself) should
// be echoed to the user
setDefault('SHOWEVERYTHINGELSETOUSER', true);
// log everything else to the log file?
setDefault('LOGEVERYTHINGELSE', true);

setDefault('PHPDATEFORMAT', 'm/d/Y');

// do you want the user forced into https mode?
setDefault('FORCESSL', false);

// the error message to show whenever the code calls logError
// and SHOWERRORSTOUSER is false
// the actual error message will show in the log file, but the user
// won't see it
setDefault('FRIENDLY_ERROR_MSG_FOR_USER', 'We have encountered an internal error, we will resolve it as soon as possible, please check back soon');
// do you want to email the administrator whenever logError is called
// (great for production)
setDefault('EMAIL_ADMIN_ON_ERROR', false);
// address to mail the error email
setDefault('ADMIN_EMAIL_ADDR', 'adminAddress@example.com');
// what to prepend to the error email subject line (great for people with
// lots of email messages)
setDefault('ADMIN_EMAIL_SUBJECT_PREPEND', '');

// ---------------------------------------------------------------------------------
// the following parameters may be changed to test out emailing without
// actually sending email to the intended recipients.
// the subject line of the email will contain the intended recipient
// ---------------------------------------------------------------------------------

// set to false if you want to send mail to the TEMP_EMAIL_ADDRESS
// instead of the actual recipient
setDefault ('REALLYSENDMAIL', true);

// only used if REALLYSENDMAIL above is false
// this is the recipient email address -
// it can be multiple addresses separated by commas
//
// the actual intended recipient's address will be put into the subject
// of the email message so you know who it's intended to go to once
// the REALLYSENDMAIL flag is set to true.
setDefault ('TEMP_EMAIL_ADDRESS', 'address@example.com');

setDefault('MAIL_SMTPSERVER', '');
setDefault('MAIL_SMTPUSERNAME', '');
setDefault('MAIL_SMTPPASSWORD', '');
// use 465 for secure, plus also set MAIL_SMTPSECURE to 1
setDefault('MAIL_SMTPPORT', '25');
setDefault('MAIL_SMTPSECURE', '0');
?>