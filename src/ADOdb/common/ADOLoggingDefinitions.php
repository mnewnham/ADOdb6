<?php
/**
* Driver logger options used when building the Monolog connector.
* Passed to the connector definitions
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace ADOdb\common;

/**
* Defines the attributes passed to the monolog interface
*/
final class ADOLoggingDefinitions
{
	/*
	* Sane Default Options
	*/
	
	/*
	* The default tag that appears in the log file
	*/
	public $loggingTag = 'ADOdb';
	
	/*
	* The ldefault log level that appears in the log file
	*/
	public $logLevel   = 'critical';
	
	/*
	* A sane default file location for the log file
	*/
	public $textFile = __DIR__ . '/adodb.log';
	
	/*
	* An imported Monolog stream handler. If this is an array,
	* then the keys are the levels, and the values are the
	* streams. There are 2 levels,CRITICAL and DEBUG
	*/
	public $streamHandler = false;
}