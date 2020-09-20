<?php
/**
* The main connection point for ADOdb system
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use \ADOdb\database\driver;
use \ADOdb\database\dictionary;
use \ADOdb\database\error;
use \ADOdb\database\debug;
use \ADOdb\session;

	
final class connector
{
	
	const CONNECT_NORMAL  = 0;
	const CONNECT_PERSIST = 1;
	const CONNECT_NEW 	  = 2;
	
	
	protected $driver;
	
	protected $driverPath;
	
	protected $connectionObject;
	
	public $loggingObject;
 
	public $debug;
	
	protected $dataDictionary;
	
	/*
	* If we uses session management, the session object is here
	*/
	public ?object $sessionObject = null;
	
	protected $performanceMonitor;
 
	/**
	* Activate an ADOdb connection
	*
	* @param object|string $connectionDefinitions
	*
	* @return bool
	*/
	public function __construct($connectionDefinitions)
	{
		
		if (!is_object($connectionDefinitions))
			$connectionDefinitions = json_decode($connectionDefinitions);
		
		$this->connectionDefinitions = $connectionDefinitions;
		
		//if ($connectionDefinitions->debug)  
		/*
		* Always open the logging down to allow traffic
		* down the critical channel
		*/
		$this->openLogger();
		
		/*
		* Must provide a valid extension
		*/
		if (!$connectionDefinitions->driver)
		{
			if (!$this->debug)
				$this->openLogger();
			
			$this->logMessage('Must specify a valid ADOdb driver',Logger::CRITICAL);
			return false;
		}
		
		$driver = str_replace('/','\\',$connectionDefinitions->driver);
		
		/*
		* Lets see if we have a valid driver
		*/
		if (!$this->connectionDefinitions->assumeValidDriver)
			list($status,$cleanDriver) = $this->checkDriverStatus($driver);
		
		if ($status)
			return false;
		
		$this->driverPath = "ADOdb\\database\\drivers\\$driver\\";
		$connectorClass   = $this->driverPath . 'ADOConnection';
		
		$connectionDefinitions->loggingObject 	= $this->loggingObject;
		$connectionDefinitions->driverPath 	    = $this->driverPath;
		
		$this->connectionObject = new $connectorClass($connectionDefinitions);
		
		return true;
		
	}
	
	/**
	* Opens a connection to the monolog connection using
	* the defined parameters
	*
	* @return void
	*/
	final private function openLogger() : void
	{
		$loggingTag 	= $this->connectionDefinitions->loggingDefinitions->loggingTag;
		$logLevel 	    = $this->connectionDefinitions->loggingDefinitions->logLevel;
		$streamHandlers 	= $this->connectionDefinitions->loggingDefinitions->streamHandlers;
		if (!$streamHandlers)
		{
			/*
			* Use the default file logging parameters for both the debug
			* and critical streams
			*/
			$textFile = $this->connectionDefinitions->loggingDefinitions->textFile;
			$streamHandlers   = array();
			$streamHandlers[] = new StreamHandler($textFile,Logger::DEBUG);
			$streamHandlers[] = new StreamHandler($textFile,Logger::CRITICAL);
		}
		
		$this->loggingObject = new \Monolog\Logger($loggingTag);
		foreach($streamHandlers as $s)
			$this->loggingObject->pushHandler($s);
	}
	
	/**
	* Writes a logging request on the selected channel
    *
	* @param string $message
	* @param int    $level
	*
	* @return void
	*/
	final public function logMessage(string $message,int $level=-1) : void
	{
		switch ($level)
		{
			case Logger::DEBUG:
			$this->loggingObject->log(Logger::DEBUG,$message);
			break;
			case Logger::CRITICAL:
			default:
			$this->loggingObject->log(Logger::CRITICAL,$message);
			break;
		}
	}
	
	/**
	 * Connect to database
	 *
	 * @param [argHostname]		Host to connect to
	 * @param [argUsername]		Userid to login
	 * @param [argPassword]		Associated password
	 * @param [argDatabaseName]	database
	 *
	 * @return the connection object to issue commands against
	 */
	final public function connect(
		?string $argHostname = "",
		?string $argUsername = "", 
		string $argPassword = "",   
		string $argDatabaseName = "") : ?object {
			
		$this->connectionObject->connect($argHostname, $argUsername, $argPassword, $argDatabaseName);
		//if ($this->connectionObject->isConnected())
			return $this->connectionObject;
		//return null;
	}
	
	/**
	 * Persistent Connect to database
	 *
	 * @param [argHostname]		Host to connect to
	 * @param [argUsername]		Userid to login
	 * @param [argPassword]		Associated password
	 * @param [argDatabaseName]	database
	 *
	 * @return the connection object to issue commands against or null
	 */
	final public function pConnect(
		?string $argHostname = "",
		?string $argUsername = "", 
		string $argPassword = "", 
		string $argDatabaseName = "") : ?object {
			
		$this->connectionObject->connect($argHostname, $argUsername, $argPassword, $argDatabaseName, self::CONNECT_PERSIST);
		if ($this->connectionObject->isConnected())
			return $this->connectionObject;
		
		return null;
	}
	
	/**
	 * Force New Connect to database (not universally supported)
	 *
	 * @param [argHostname]		Host to connect to
	 * @param [argUsername]		Userid to login
	 * @param [argPassword]		Associated password
	 * @param [argDatabaseName]	database
	 *
	 * @return the connection object to issue commands against or null
	 */
	final public function nConnect(
		?string $argHostname = "",
		?string $argUsername = "", 
		string $argPassword = "", 
		string $argDatabaseName = "") : ?object {
			
		$this->connectionObject->connect($argHostname, $argUsername, $argPassword, $argDatabaseName, self::CONNECT_NEW);
		if ($this->connectionObject->isConnected())
			return $this->connectionObject;
		
		return null;
	}
	
		
	/**
	* Ensures that the driver exists both physically as 
	* part of ADOdb, and as a PHP extension
	*
	* @param array $driverArray
	*
	* @return bool
	*/
	final private function checkDriverStatus(string $driver) : array
	{
		/*
		* Test that the relevant extension is loaded
		*/
		if(!extension_loaded($driver)) {
			
			$message = sprintf(
				'PHP %s extension not loaded',
				$driver);
			
			$this->loggingObject->log(Logger::CRITICAL,$message);

			return array(1,'');
		}
		
		$driverArray = explode('\\',$driver);

		
		$physicalDriver = __DIR__ . '/database/drivers/';
		
		$driverText = '[' . implode('\\',$driverArray) . ']';
		
		$cleanDriver = '';
		if (count($driverArray) > 2)
		{
			$message = $driverText . ' is not a valid ADOdb driver name';
			$this->loggingObject->log(Logger::CRITICAL,$message);
			return array(1,'');
		}
		
		if (count($driverArray) > 1)
		{
			
			$pdo = strtoupper($driverArray[0]);
			if (strcmp($pdo,'PDO') <> 0)
			{
				$message = $driverText . ' is not a valid ADOdb driver name';
				$this->loggingObject->log(Logger::CRITICAL,$message);
				return array(1,'');
			}
			$physicalDriver .= $pdo . '/';
			
			$cleanDriver = 'PDO\\';
		}
		
		$endpoint = strtolower(array_pop($driverArray));
		
		$physicalDriver .= $endpoint;
		$cleanDriver    .= $endpoint;
		
		if (!is_dir($physicalDriver))
		{
			$message = $driverText . ' is not a valid ADOdb driver name';$this->loggingObject->log(Logger::CRITICAL,$message);
			return array(1,'');
		}
		
		
		return array(0,$cleanDriver);
	}

	/**
	* The entry point for the session management system
	*
	* @param objest a sessionDefinitions object
	*
	* @return object
	*/
	final public function startSession(?object $sessionDefinitions=null) : ?object
	{
		
		/*
		* If no session definition is passed, build a default set
		*/
		if (!$sessionDefinitions)
			$sessionDefinitions = new \ADOdb\session\ADOSessionDefinitions;
		
		/*
		* Load the session, against an existing connection. If the 
		*/
		if ($sessionDefinitions->sessionManagementClass === null)
		{
		
			
			$driver = str_replace('/','\\',$this->connectionDefinitions->driver);
			
			$sessionClass = '\\ADOdb\\session\\' . $driver . '\\ADOSession';
			
		} else
			
			$sessionClass = $sessionDefinitions->sessionManagementClass;
			
		$this->sessionObject = new $sessionClass($this->connectionObject,$sessionDefinitions);
		
		return $this->sessionObject;
		
	}
		
	public function loadPerformanceMonitor()
	{
		$perfmon = $this->driverPath . '/perfmon/performanceMonitor.php';
		if (!file_exists($perfmon))
		{
			if ($this->debug)
				$this->logMessage('Performance Monitor not available for this driver',Logger::DEBUG);
			return false;
		}
		
		$this->performanceMonitor = new $perfmon;
		
		return $this->performanceMonitor;
	}
	
	/**
	* A Shortcut function to the datadictionary
	*
	*
	* @return obj
	*/
	public function loadDataDictionary()
	{
		$datadict = $this->driverPath . 'ADODataDictionary';
		
		$dataDictionary = new $datadict($this->connectionObject);
		
		return $dataDictionary;
	}		
	
}
//==============================================================================================
	// GLOBAL VARIABLES
	//==============================================================================================

	/*
	GLOBAL
		$ADODB_vers,		// database version
		$ADODB_CACHE_DIR,	// directory to cache recordsets
		$ADODB_CACHE,
		$ADODB_CACHE_CLASS,
*/
