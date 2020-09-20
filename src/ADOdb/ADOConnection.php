<?php
/**
* The core driver package for ADOdb
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

/**
 * Connection object. For connecting to databases, and executing queries.
 */
abstract class ADOConnection {
	
	
	/**
	 * Fetch mode
	 *
	 * Set global variable $ADODB_FETCH_MODE to one of these constants or use
	 * the SetFetchMode() method to control how recordset fields are returned
	 * when fetching data.
	 *
	 *   - NUM:     array()
	 *   - ASSOC:   array('id' => 456, 'name' => 'john')
	 *   - BOTH:    array(0 => 456, 'id' => 456, 1 => 'john', 'name' => 'john')
	 *   - DEFAULT: driver-dependent
	 */
	const ADODB_FETCH_DEFAULT = 0;
	const ADODB_FETCH_NUM     = 1;
	const ADODB_FETCH_ASSOC   = 2;
	const ADODB_FETCH_BOTH  = 3;
	
	/**
	 * Associative array case constants
	 *
	 * By defining the ADODB_ASSOC_CASE constant to one of these values, it is
	 * possible to control the case of field names (associative array's keys)
	 * when operating in ADODB_FETCH_ASSOC fetch mode.
	 *   - LOWER:  $rs->fields['orderid']
	 *   - UPPER:  $rs->fields['ORDERID']
	 *   - NATIVE: $rs->fields['OrderID'] (or whatever the RDBMS will return)
	 *
	 * The default is to use native case-names.
	 *
	 * NOTE: This functionality is not implemented everywhere, it currently
	 * works only with: mssql, odbc, oci8 and ibase derived drivers
	 */
	const ADODB_ASSOC_CASE_LOWER = 0;
	const ADODB_ASSOC_CASE_UPPER = 1;
	const ADODB_ASSOC_CASE_NATIVE = 2;

	/**
	 * Constants for returned values from the charMax and textMax methods.
	 * If not specifically defined in the driver, methods return the NOTSET value.
	 */
	const ADODB_STRINGMAX_NOTSET  = -1;
	const ADODB_STRINGMAX_NOLIMIT = -2;
	
	
	/******************************************************
	* Defines a basic set of constants to match the 
	* available ADOdb metatypes
	*******************************************************/
	
	/*
	* Character fields < 250
	*/
	const ADODB_METATYPE_CHAR = 	'C';
	const ADODB_METATYPE_C = 		'C';
	/*
	* Multibyte Character fields
	*/
	const ADODB_METATYPE_MCHAR = 	'C2';
	const ADODB_METATYPE_C2 = 	'C2';
	
	/*
	* Character fields > 250, CLOB
	*/
	const ADODB_METATYPE_TEXT = 	'X';
	const ADODB_METATYPE_X = 		'X';
	
	/*
	* Multibyte Character fields
	*/
	const ADODB_METATYPE_MTEXT = 	'X2';
	const ADODB_METATYPE_X2 = 	'X2';
	
	/*
	* Character Large Object
	*/
	const ADODB_METATYPE_CLOB = 	'XL';
	const ADODB_METATYPE_XL = 	'XL';
	
	/*
	* Binary fields, BLOB
	*/
	const ADODB_METATYPE_BIN = 	'B';
	const ADODB_METATYPE_B = 		'B';
	
	/*
	* Numeric, floating point
	*/
	const ADODB_METATYPE_NUMBER = 'N';
	const ADODB_METATYPE_N = 		'N';
	
	/*
	* Floating point if different from number
	*/
	const ADODB_METATYPE_FLOAT = 	'F';
	const ADODB_METATYPE_F = 		'F';
	
	/*
	* Date
	*/
	const ADODB_METATYPE_DATE = 	'D';
	const ADODB_METATYPE_D =  	'D';
	
	/*
	* timestamp, datetime
	*/
	const ADODB_METATYPE_TIME = 	    'T';
	const ADODB_METATYPE_T = 	    	'T';
	
	/*
	* Logical, sometimes tinyint
	*/
	const ADODB_METATYPE_LOG = 	'L';
	const ADODB_METATYPE_L = 		'L';

	/*
	* Integer types
	*/
	const ADODB_METATYPE_INT = 	'I';
	const ADODB_METATYPE_I = 		'I';
	const ADODB_METATYPE_INT1 = 	'I1';
	const ADODB_METATYPE_I1 = 	'I1';
	const ADODB_METATYPE_INT2 = 	'I2';
	const ADODB_METATYPE_I2 = 	'I2';
	const ADODB_METATYPE_INT4 = 	'I4';
	const ADODB_METATYPE_I4 = 	'I4';
	const ADODB_METATYPE_INT8 = 	'I8';
	const ADODB_METATYPE_I8 = 	'I8';
	
	/*
	* Real, sometimes autoincrement counter/integer
	*/ 
	const ADODB_METATYPE_REAL = 	'R';
	const ADODB_METATYPE_R = 		'R';
	
	/*
     * Some Geometry Types
    */
    const ADODB_METATYPE_GEOMETRY =   'G';
    const ADODB_METATYPE_G = 		  'G';
    const ADODB_METATYPE_POINT =  	  'PO';
    const ADODB_METATYPE_PO = 	      'PO';
    const ADODB_METATYPE_POLYGON =    'PY';
    const ADODB_METATYPE_PY = 	      'PY';
    const ADODB_METATYPE_LINESTRING = 'LS';
    const ADODB_METATYPE_LS = 	      'LS';

    const ADODB_METATYPE_MULTIPOINT = 'MP';
    const ADODB_METATYPE_MP = 	      'MP';
    const ADODB_METATYPE_MULTIPOLYGON='MY';
    const ADODB_METATYPE_MY = 	      'MY';
    const ADODB_METATYPE_MULTILINESTRING = 'MS';
    const ADODB_METATYPE_MS = 	           'MS';
    const ADODB_METATYPE_GEOMETRYCOLLECTION = 'GC';
    const ADODB_METATYPE_GC = 	              'GC';
	
	/*
	* JSON Fields
	*/
	const ADODB_METATYPE_JSON = 'J';
	const ADODB_METATYPE_J    = 'J';


	/*
	* Describes the connection type
	*/
	const CONNECT_NORMAL  = 0;
	const CONNECT_PERSIST = 1;
	const CONNECT_NEW 	  = 2;
	
	/*
	* Cross reference back to actual type, varies by driver
	*/
	protected array $actualTypes = array();
	
	/*
	* Defines the record insertion force mode - ADODB_FORCE_TYPE
	*/
	public int $forceType = 3;
	
	/*
	* Defines the casing of associative arrays - see ADODB_ASSOC_CASE
	*/
	public int $assocCase = 0;
	
	/*
	* Defines the connection parameters. If left false,
	* pass to connect(). Else use an array that signifies
	* connection, eg [database] [user] [password]
	*/
	public object $connection;
	
	/*
	* The name of the database connected
	*/
	public ?string $database = '';
	
	/*
	* Defines how getOne EOF is presented - see ADODB_GETONE_EOF
	*/
	public $getOneEOF = false;
	
	/*
	* Signifies if we want to count records - see COUNTRECS
	*/
	public bool $countRecords = true;
	
	/*
	* Used to revert a temporarily changed countRecords
	*/
	public bool $coreCountRecords = true;
	
	/*
	* This flag temporarily disables countRecords
	* without changing the orginal setting
	*/
	protected bool $disableCountRecords = false;

	/*
	* Used to revert a temporarily changed fetchmode
	*/
	public int $coreFetchMode = 0;
	
	/*
	* The transaction handling object
	*/
	protected object $transactionHandlingObject;
	
	/*
	* The cache server object object
	*/
	protected ?object $cacheObject = null;
	
	/*
	* A Shortcut to the name of the db datetime class
	*/
	protected string $dbDateTimeClass;
	
	var $dataProvider = 'native';
	
	/*
	* Shortcut to connectionDefinitions->debug
	*/
	public bool $debug = false;
	
	/*
	* any varchar/char field this size or greater is treated as 
	* a blobin other words, we use a text area for editing.
	*/
	protected int $blobSize = 100;	
	
	/*
	* maximum size of blobs or large text fields (262144 = 256K)-- some db's die otherwise
	*/
	protected  int $maxblobsize = 262144;	
	
	/********************************************************************************
	* Db sensitive helper functions
	********************************************************************************/
	
	/*
	* default concat operator
	*/
	public string $concat_operator = '+'; 
	/*
	* substring operator
	*/
	public string $substr = 'SUBSTR';	
	
	/*
	* string length operator
	*/
	public string $length = 'LENGTH';
	
	/*
	* random function
	*/
	public string $random = 'RAND()';
	
	/*
	* String that represents uppercase function
	*/
	public string $upperCase = 'UPPER';
	
	/*
	* string to use to replace quotes
	*/
	public string $replaceQuote = "\\'";	
	
	/*
	* string to use to quote identifiers and names
	*/
	public string $nameQuote = '"';
	
	/*
	* strings that represents TRUE/FALSE for a database
	*/
	public string $true  = '1';	
	public string $false = '0';	
	
	
	/**************************************************************************
	* End of helpers
	**************************************************************************/
	
	public string $fmtDate = "'Y-m-d'";	/// used by DBDate() as the default date format used by the database
	public string $fmtTimeStamp = "'Y-m-d, h:i:s A'"; /// used by DBTimeStamp as the default timestamp fmt.
	
	/*
	* Can we scroll backwards
    */
	public bool $hasMoveFirst = false;
	
	
	/*
	* bracketing for t-sql styled column names with quoteFieldName = 'BRACKETS'
	*/
	protected string $leftBracket = '[';	
	protected string $rightBracket = ']';
	
	/*
	* The currently set character set
	*/
	protected ?string $charSet	=	null;
	
	protected ?string 	$metaDatabasesSQL 	= null;
	protected ?string 	$metaTablesSQL 		= null;
	
	var $lastInsID = false;
	//--
	var $hasInsertID = false;		/// supports autoincrement ID?
	var $hasAffectedRows = false;	/// supports affected rows for update/delete?
	
	/*
	* The phrase that gives us access to the top items, e.g. 'top'
	*/
	protected ?string $hasTop = null;	
	
	/*
	* Flags whether the database supports genID
	*/
	public bool $hasGenID = false;
	
	/*
	* If GenID supported, the current number
	*/
	public int $genID = 0;
	
	/*
	* Shortcut to error Function
	*/
	public ?string $raiseErrorFn = null;

	/*
	* The driver directly accepts dates in ISO format
	*/
	protected  bool $isoDates = false;
	
	/*
	* Shortcut to default cache server timeout
	*/
	protected int $cacheSecs = 3600;

	/*
	* name of function that returns the current date
	*/
	protected ?string $sysDate = null;
	
	/*
	* name of function that returns the current timestamp.
	* SessionHandler needs access so public
	*/
	public ?string $sysTimeStamp = null;
	
	/*
	* name of function that returns the current timestamp 
	* accurate to the microsecond or nearest fraction
	*/
	protected $sysUTimeStamp = false; 
	
	/*
	* name of class used to generate array recordsets, which are pre-downloaded
	*/
	protected ?string $arrayClass = null;

	/*
	* Placeholder for the imported OEM class
	* that defines connection attributes
	*/
	public $oemFlags;

	/*
	* Page execution behaviour, imported from connectionDefinitions
	*/
	protected bool $pageExecuteCountRows = true;
	
	/*
	* operator to use for left outer join in WHERE clause
	*/
	public ?string $leftOuter = null; 
	
	/*
	* operator to use for right outer join in WHERE clause
	*/
	public ?string $rightOuter = null; 
	
	
	var $autoRollback = false; // autoRollback on PConnect().
	
	var $fnExecute = false;
	var $fnCacheExecute = false;

	/*
	* Guides commit method in certain drivers
	*/
	protected  bool $autoCommit = true;	
	
	/*
	* These values are backwardly updated by subclasses,
	* but setting them manually doesnt do anything
	* even though they are public
	*/ 
	
	/*
	* temporarily disable transactions
	*/
	public int $transOff = 0;			
	
	/*
	* count of nested transactions
	*/
	public int $transCnt = 0;	

	/*
	* The fetch mode, managed by ADODB_FETCH_MODE
	*/
	public int $fetchMode=0;

	/*
	* in autoexecute/getinsertsql/getupdatesql, this value 
	* will be converted to a null
	*/
	public string $null2null = 'null'; 
	
	/*
	* enable 2D Execute array
	*/
	protected bool $bulkBind = false; 
	
	//
	// PRIVATE VARS
	//
	var $_oldRaiseFn =  false;
	var $_transOK = null;
	
	/*
	* The returned link identifier whenever a successful database connection 
	* is made. This is public so that sublclasses can access it. Maybe
	* a method?
	*/
	//public ?object $_connectionID = null;
	public $_connectionID = false;
	
	
	/*
	* Shortcut into the cache server caching default 
	*/
	protected int $cacheSeconds = 0;
	
	/*
	* A default cache options template
	*/
	protected array $defaultCacheOptions = array(
			'cachesecs'=>0,
			'serverkey'=>null,
			'continue'=>false
			);
	
	var $_errorMsg = false;		/// A variable which was used to keep the returned last error message.  The value will
								/// then returned by the errorMsg() function
	var $_errorCode = false;	/// Last error code, not guaranteed to be used - only by oci8
	var $_queryID = false;		/// This variable keeps the last created result link identifier
	
	/*
	* set to true if ADOConnection.Execute() permits binding of array parameters.
	*/
	protected bool $_bindInputArray = false; 
	
	var $_affected = false;
	var $_logsql = false;
	var $_transmode = ''; // transaction mode
	
	/*
	* An optional array of connection parameters if provided before connection
	*/
	protected array $dbParameters = array(
		'host'=>'',
		'user'=>'',
		'password'=>'',
		'database'=>'',
		'optionSet'=>0
		);

	const DB_OPTIONS_NONE = 0;
	const DB_OPTIONS_SET  = 1;
	const DB_OPTIONS_DSN  = 2;
	
	/*
	* Alternatively, a DSN parameter string
	*/
	protected ?string $dsnParameters = null;
	
	/*
	* Does the driver support a connection
	* using DSN strings
	*/
	protected bool $supportsDsnStrings = false;

	

	/**
	* constructor
	*
	* @param object $connectionDefinitions
	*
	* @return obj
	*/
	public function __construct(object $connectionDefinitions)
	{
		
		/*
		* Import some parameters into the connection
		*/
		$this->debug 			= $connectionDefinitions->debug;
		$this->loggingObject 	= $connectionDefinitions->loggingObject;
		$this->fetchMode 		= $connectionDefinitions->fetchMode;
		$this->coreFetchMode    = $connectionDefinitions->fetchMode;
		$this->driverPath		= $connectionDefinitions->driverPath;
		$this->language			= $connectionDefinitions->language;
		$this->coreCountRecords = $connectionDefinitions->countRecords;
		$this->bulkBind			= $connectionDefinitions->bulkBind;
		$this->pageExecuteCountRows = $connectionDefinitions->pageExecuteCountRows;
		
		$this->arrayClass = $this->driverPath . 'ADORecordSetArray';
		
		$this->connectionDefinitions = $connectionDefinitions;
		
		/*
		* Import the transaction handling class into the environment
		*/
		if ($connectionDefinitions->activateTransactionHandling)
		{
			$tClass = $this->driverPath . 'ADOTransactionManagement';
			$this->transactionHandlingObject = new $tClass($this);
		}
		
		/**
		* If we have provided parameters to activate caching
		* services, connect here
		*/
		if (is_object($connectionDefinitions->cacheDefinitions))
		{
			$service = $connectionDefinitions->cacheDefinitions->service;
			$mClass = sprintf('\ADOdb\cache\plugins\%s\ADOCacheMethods',$service);
			$this->cacheObject = new $mClass($this);
		}
		/*
		* Useful shortcut
		*/
		
		$this->dbDateTimeClass = $this->driverPath . 'ADODbDateTimeFunctions';
		
		
		/*
		* Start with some default oem flags
		*/
		$oemFlagClass = $this->driverPath . 'ADOOemFlags';
		$this->oemFlags = new $oemFlagClass;
		
		if (is_array($connectionDefinitions->oemFlags)) {
			
			/*
			* If we have set some custom configs overlay
			* the defaults we our custom
			*/
			$this->oemFlags = $connectionDefinitions->oemFlags; 
		
		} 
		if (is_object($this->connectionDefinitions->cacheDefinitions)) {
			$this->cacheSeconds = $this->connectionDefinitions->cacheDefinitions->cacheSeconds;
			
			$this->defaultCacheOptions['cachesecs'] = $this->cacheSeconds;
		}
		
		if ($this->connectionDefinitions->dsnParameters)
		{
			$this->dbParameters['host'] = $this->connectionDefinitions->dsnParameters;
			$this->dbParameters['optionSet'] = self::DB_OPTIONS_DSN;
		
		} else {
			
			if (is_array($this->connectionDefinitions->dbParameters))
			{
				$this->dbParameters = array_merge(
							$this->dbParameters,
							$this->connectionDefinitions->dbParameters
							);
				$this->dbParameters['optionSet'] = self::DB_OPTIONS_SET;
			}			
		}
		
	}

	static function Version() {
		global $ADODB_vers;

		// Semantic Version number matching regex
		$regex = '^[vV]?(\d+\.\d+\.\d+'         // Version number (X.Y.Z) with optional 'V'
			. '(?:-(?:'                         // Optional preprod version: a '-'
			. 'dev|'                            // followed by 'dev'
			. '(?:(?:alpha|beta|rc)(?:\.\d+))'  // or a preprod suffix and version number
			. '))?)(?:\s|$)';                   // Whitespace or end of string

		if (!preg_match("/$regex/", $ADODB_vers, $matches)) {
			// This should normally not happen... Return whatever is between the start
			// of the string and the first whitespace (or the end of the string).
			$debugger = new \ADOdb\database\debug\debugger($this);
			
			$debugger->logMessage("Invalid version number: '$ADODB_vers'", Logger::DEBUG);
			$regex = '^[vV]?(.*?)(?:\s|$)';
			preg_match("/$regex/", $ADODB_vers, $matches);
		}
		return $matches[1];
	}

	/**
	* Returns the server info version
	*
	* @return array
	*/
	abstract public function serverInfo() : array;

	/**
	* Is the database connected
	*
	* @return bool
	*/
	final public function isConnected() : bool {
		
		return !empty($this->_connectionID);
	
	}

	/**
	* Extracts the server version from the description
	*
	* @param str $str
	*
	* @return str
	*/
	final protected function _findvers(string $str) : string {
		
		if (preg_match('/([0-9]+\.([0-9\.])+)/',$str, $arr)) {
			return $arr[1];
		} else {
			return '';
		}
	}

	/**
	* Unpacks and validates the caching parameters
	* passed to the standard functions
	*
	* @param	?array		$passed parameters
	*
	* @return ?array
	*/
	final protected function unpackCacheParameters(?array $passedParameters) : ?array {
			
		if (!is_object($this->cacheObject))
			/*
			* Caching not enabled
			*/
			return null;
		
		if (!is_array($passedParameters) || !isset($passedParameters['cache']))
			return null;
		
		$cacheCache = $passedParameters['cache'];
		
		if (!is_array($cacheCache))
		{	
			if ($cacheCache == false)
				return null;
		
			$cacheCache = array();
		}
		
		$currentCacheOption = array_merge($this->defaultCacheOptions,$cacheCache);
		
		if ($this->cacheObject->_connected == false && $currentCacheOption['continue'] == true)
		{
			if ($this->debug)
			{
				$message = 'cache service offline, forcing live connection';
				$this->loggingObject->log(Logger::DEBUG,$message);
			}
			return null;
		}
		
		return $currentCacheOption;
		
	}
	
	/**
	* Returns the current time as seen by the database
	*
	* @return string
	*/
	public function time() : ?string {
		
		$rs = $this->_execute("SELECT $this->sysTimeStamp");
		
		if ($rs && !$rs->EOF) {
			return $this->unixTimeStamp(reset($rs->fields));
		}

		return null;
	}

	/**
	 * Parses the hostname to extract the port.
	 * Overwrites $this->host and $this->port, only if a port is specified.
	 * The Hostname can be fully or partially qualified,
	 * ie: "db.mydomain.com:5432" or "ldaps://ldap.mydomain.com:636"
	 * Any specified scheme such as ldap:// or ldaps:// is maintained.
	 */
	protected function parseHostNameAndPort() :void {
		
		$parsed_url = parse_url($this->host);
		
		if (is_array($parsed_url) && isset($parsed_url['host']) && isset($parsed_url['port'])) {
			if ( isset($parsed_url['scheme']) ) {
				// If scheme is specified (ie: ldap:// or ldaps://, make sure we retain that.
				$this->host = $parsed_url['scheme'] . "://" . $parsed_url['host'];
			} else {
				$this->host = $parsed_url['host'];
			}
			$this->port = $parsed_url['port'];
		}
	}

	/**
	 * Connect to database, this is no longer publicly exposed
	 *
	 * @param string	[argHostname]		Host to connect to
	 * @param string	[argUsername]		Userid to login
	 * @param string	[argPassword]		Associated password
	 * @param string	[argDatabaseName]	database
	 * @param int 		[$optionFlag]	
	 *
	 * @return true or false
	 */
	final public function connect(
		?string $argHostname = "",
		?string $argUsername = "", 
		string $argPassword = "", 
		string $argDatabaseName = "", 
		int $optionFlag = self::CONNECT_NORMAL) : bool {
		
		if (func_num_args() == 0 && $this->dbParameters['optionSet'] == self::DB_OPTIONS_NONE)
		{
			
			/*
			* Neither definitions or runtime used
			*/
			$message = 'Parameters must be passed in connect if not set in definitions';
			$this->loggingObject->log(Logger::CRITICAL,$message);
			return false;
			
		} else if (func_num_args() == 0 && $this->dbParameters['optionSet'] == self::DB_OPTIONS_SET) {
			
			/*
			* Use preset definitions
			*/
			if ($argHostname != "") {
				$this->dbParameters['host'] = $argHostname;
			}
			
			if ($argUsername != "") {
				$this->dbParameters['user'] = $argUsername;
			}
			
			if ($argPassword != "") {
				$this->dbParameters['password'] = 'not stored'; // not stored for security reasons
			} else {
				$argPassword = $this->dbParameters['password'];
			}
			
			if ($argDatabaseName != "") {
				$this->dbParameters['database'] = $argDatabaseName;
			}
		} else if (func_num_args() == 0 && $this->dbParameters['optionSet'] == self::DB_OPTIONS_DSN) {
			
			if ($this->supportsDsnStrings == false)
			{
				$message = 'Driver does not accept DSN format connection strings';
				$this->loggingObject->log(Logger::CRITICAL,$message);
				return false;
			}

		} else if ($argHostname === null 
				&& $argUsername === null
				&& $argPassword
				&& $this->dbParameters['optionSet'] == self::DB_OPTIONS_SET){
			/*
			* Use passed password + defined options
			*/
		} else if ($argHostname === null 
				&& $argUsername === null
				&& $argPassword
				&& $this->dbParameters['optionSet'] == self::DB_OPTIONS_DSN){
			/*
			* Use passed password + defined options
			*/
			if ($this->supportsDsnStrings == false)
			{
				$message = 'Driver does not accept DSN format connection strings';
				$this->loggingObject->log(Logger::CRITICAL,$message);
				return false;
			}

		
		} else if ($this->dbParameters['optionSet'] == self::DB_OPTIONS_SET) {
			/*
			* Use the stored password
			*/
			$argPassword = $this->dbParameters['password'];
		} else {
			/*
			* Use passed arguments
			*/
			$this->dbParameters['host'] = $argHostname;
			$this->dbParameters['user'] = $argUsername;
			$this->dbParameters['database'] = $argDatabaseName;
		}

		if ($optionFlag == self::CONNECT_NEW) {
			
			if ($rez=$this->_nconnect(
						$this->dbParameters['host'], 
						$this->dbParameters['user'], 
						$argPassword, 
						$this->dbParameters['database'])) 
				return true;
				
		} else if ($optionFlag == self::CONNECT_PERSIST) {
			
			if ($rez=$this->_pconnect(
						$this->dbParameters['host'], 
						$this->dbParameters['user'], 
						$argPassword, 
						$this->dbParameters['database'])) 
				return true;
			
		
		} else {
			
			if ($rez=$this->_connect(
						$this->dbParameters['host'], 
						$this->dbParameters['user'], 
						$argPassword, 
						$this->dbParameters['database'])) 
				return true;
		}
		
		if (isset($rez)) {
			$err = $this->errorMsg();
			$errno = $this->errorNo();
			if (empty($err)) {
				$err = sprintf('Connection error to server %s with user %s',
							$this->dbParameters['host'],
							$this->dbParameters['user']
							);
			}
			
		} else {
			$err = "Missing extension for ".$this->dataProvider;
			$errno = 0;
		}
		if ($fn = $this->raiseErrorFn) {
			$fn($this->databaseType, 'CONNECT', $errno, $err, $this->host, $this->database, $this);
		}

		$this->_connectionID = false;
		if ($this->debug) {
			$debugger = new \ADOdb\database\debug\debugger($this);
			$debugger->logMessage( $this->dbParameters['host'].': '.$err,Logger::DEBUG);
		}
		return false;
	}

	/**
	* Fallback function if database does not support forcing new connections
	*
	*
	* @param string	[argHostname]		Host to connect to
	* @param string	[argUsername]		Userid to login
	* @param string	[argPassword]		Associated password
	* @param string	[argDatabaseName]	database
	*
	* @return bool successful connection
	*/
	protected function _nconnect(
		string $argHostname, 
		string $argUsername, 
		string $argPassword, 
		string $argDatabaseName) : bool {
			
		return $this->_connect($argHostname, $argUsername, $argPassword, $argDatabaseName);
	}


	
	/**
	* Format date column in sql string given an input format that understands Y M D
	*
	* @param	string	$fmt
	* @param	bool	$col
	*
	* @return string
	*/
	abstract function sqlDate(string $fmt, bool $col=false) : string;
	
	/**
	 * Should prepare the sql statement and return the stmt resource.
	 * For databases that do not support this, we return the $sql. To ensure
	 * compatibility with databases that do not support prepare:
	 *
	 *   $stmt = $db->Prepare("insert into table (id, name) values (?,?)");
	 *   $db->Execute($stmt,array(1,'Jill')) or die('insert failed');
	 *   $db->Execute($stmt,array(2,'Joe')) or die('insert failed');
	 *
	 * @param sql	SQL to send to database
	 *
	 * @return return FALSE, or the prepared statement, or the original sql if
	 *         if the database does not support prepare.
	 *
	 */
	public function prepare(string $sql) : string {
		return $sql;
	}

	/**
	 * Some databases, eg. mssql require a different function for preparing
	 * stored procedures. So we cannot use Prepare().
	 *
	 * Should prepare the stored procedure  and return the stmt resource.
	 * For databases that do not support this, we return the $sql. To ensure
	 * compatibility with databases that do not support prepare:
	 *
	 * @param sql	SQL to send to database
	 *
	 * @return return FALSE, or the prepared statement, or the original sql if
	 *         if the database does not support prepare.
	 *
	 */
	public function prepareSP(string $sql,$param=true) {
		return $this->prepare($sql,$param);
	}

	/**
	* PEAR DB Compat
	*
	* @param str $s The string to quote
	*
	* @return string
	*/
	public function quote(string $s) : string {
		return $this->qstr($s,false);
	}

	/**
	 * Lock a row, will escalate and lock the table if row locking not supported
	 * will normally free the lock at the end of the transaction
	 *
	 * @param $table	name of table to lock
	 * @param $where	where clause to use, eg: "WHERE row=12". If left empty, will escalate to table lock
	 */
	public function rowLock(
		string $table,
		string $where,
		string $col='1 as adodbignore') : bool {
		return false;
	}

	/**
	* Sets the fetch mode after instantiation
	*
	* @param int $mode	The fetchmode ADODB_FETCH_ASSOC or ADODB_FETCH_NUM
	*
	* @returns	int	The previous fetch mode
	*/
	final public function setFetchMode(int $mode) : int
	{
		$old = $this->fetchMode;
		$this->fetchMode = $mode;

		return $old;
	}

	/**
	 * Returns a placeholder for query parameters
	 
	 * @param string $name parameter's name, null to force a reset of the
	 *                     number to 1 (for databases that require positioned
	 *                     params such as PostgreSQL; note that ADOdb will
	 *                     automatically reset this when executing a query )
	 *
	 * @return string query parameter placeholder
	 */
	public function param(
			?string $name) : string	{
		
		if ($this->debug && $name == null)
		{
			$message = 'Parameter rewinding not supported in database';
			$this->loggingObject->log(Logger::DEBUG,$message);
		}
		return '?';
	}

	/*
	* InParameter and OutParameter are self-documenting versions of Parameter().
	*
	* @param obj $stmt		A reference to a procedure
	* @param str $var		The value of the input parameter
	* @param str $name		The name of the input parameter
	* @param str $maxLen	The maximum length of the parameter
	* @param str $type		The type of field, if not set then auto-determined
	*
	* @return void
	*/
	final public function inParameter(
			object &$stmt,
			string &$var,
			string $name,
			int $maxLen=4000,
			?string $type=null) : void {
				
		$this->parameter($stmt,$var,$name,false,$maxLen,$type);
	}

/*
	* InParameter and OutParameter are self-documenting versions of Parameter().
	*
	* @param obj $stmt		A reference to a procedure
	* @param str $var		The value of the input parameter
	* @param str $name		The name of the input parameter
	* @param str $maxLen	The maximum length of the parameter
	* @param str $type		The type of field, if not set then auto-determined
	*
	* @return mixed
	*/
	final public function outParameter(
		object &$stmt,
		string &$var,
		string $name,
		int $maxLen=4000,
		?string $type=null) : void {
			
		$this->parameter($stmt,$var,$name,true,$maxLen,$type);

	}


	/**
	*
	* @param $stmt Statement returned by Prepare() or PrepareSP().
	* @param $var PHP variable to bind to
	* @param $name Name of stored procedure variable name to bind to.
	* @param [$isOutput] Indicates direction of parameter 0/false=IN  1=OUT  2= IN/OUT. This is ignored in oci8.
	* @param [$maxLen] Holds an maximum length of the variable.
	* @param [$type] The data type of $var. Legal values depend on driver.
    *
	* return void
	*/
	public function parameter(
			object &$stmt,
			string &$var,
			string $name,
			bool $isOutput=false,
			int $maxLen=4000,
			bool $type=false) : void {
				
	}


	/**
	* Function not described
	*
	* @param bool $saveErrs
	*
	* @return
	*/
	public function ignoreErrors(bool $saveErrs=false) {
		if (!$saveErrs) {
			$saveErrs = array($this->raiseErrorFn,$this->_transOK);
			$this->raiseErrorFn = false;
			return $saveErrs;
		} else {
			$this->raiseErrorFn = $saveErrs[0];
			$this->_transOK = $saveErrs[1];
		}
	}
	
	/**
	* Sets the transaction level count
	*
	* @param  int $count The level
	*
	* @return bool transactions are active
	*/
	final public function setTransCnt(int $count)
	{
		return $this->transactionHandlingObject->setTransCnt($count);
	}
	
	/**
	* Gets the transaction level count
	*
	* @return int
	*/
	final public function getTransCnt() : int
	{
		return $this->transactionHandlingObject->getTransCnt();
	}
	
	/**
	* Sets the transaction switch off
	*
	* @param  bool $bool the switch
	*
	* @return void
	*/
	final public function setTransOff($bool)
	{
		return $this->transactionHandlingObject->setTransOff($bool);
	}
	
	/**
	* Gets the transaction switch off
	*
	* @return bool
	*/
	final public function getTransOff() : bool
	{
		return $this->transactionHandlingObject->getTransOff();
	}

	/**
	 * Improved method of initiating a transaction. Used together with CompleteTrans().
	 * Advantages include:
     *
	 * a. StartTrans/CompleteTrans is nestable, unlike BeginTrans/CommitTrans/RollbackTrans.
	 *    Only the outermost block is treated as a transaction.<br>
	 * b. CompleteTrans auto-detects SQL errors, and will rollback on errors, commit otherwise.<br>
	 * c. All BeginTrans/CommitTrans/RollbackTrans inside a StartTrans/CompleteTrans block
	 *    are disabled, making it backward compatible.
	 */
	final public function startTrans(
			string $errfn = 'ADODB_TransMonitor') {
		
		return $this->transactionHandlingObject->startTrans($errfn);
		
	}

	/**
	* Used together with StartTrans() to end a transaction. Monitors connection
	* for sql errors, and will commit or rollback as appropriate.

	* @autoComplete if true, monitor sql errors and commit and rollback as appropriate,
	* and if set to false force rollback even if no SQL error detected.
	* @returns true on commit, false on rollback.
	*/
	final public function completeTrans(bool $autoComplete = true) : bool 
	{
		return $this->transactionHandlingObject->completeTrans($autoComplete);
	}

	/**
	* During a StartTrans/CompleteTrans block, trigger a rollback.
	*
	* @return void
	*/
	final public function failTrans() 
	{
		return $this->transactionHandlingObject->failTrans();
	}

	/**
	* Check if transaction has failed, only for Smart Transactions
	*
	* @return void
	*/
	final public function hasFailedTrans() : bool
	{
		if ($this->transOff > 0) {
			return $this->_transOK == false;
		}
		return false;
	}

	/**
	 * Execute SQL
	 *
	 * @param string	sql		SQL statement to execute, or possibly an array holding prepared statement ($sql[0] will hold sql text)
	 * @param array		[inputarr]	holds the input data to bind to. Null elements will be set to null.
	 * @param array		$cacheOptions
	 *
	 * @return RecordSet or null
	 */
	final public function execute(
		string $sql,
		?array $inputarr=null,
		?array $cacheOptions=null) : ?object  {
		
		/*
		* Compatibility, originally passed false by
		* default, now uses null for proper typing
		*/
		if ($inputarr == false)
			$inputarr = null;
		
		$currentCacheOption = $this->unpackCacheParameters($cacheOptions);
		
		if (is_array($currentCacheOption)) { 
						
			return $this->cacheObject->execute(
						$sql,
						$inputarr,
						$currentCacheOption
						);

		}
		
		
		if ($this->fnExecute) {
			$fn = $this->fnExecute;
			$ret = $fn($this,$sql,$inputarr);
			if (isset($ret)) {
				return $ret;
			}
		}
		if ($inputarr != null) {
			if (!is_array($inputarr)) {
				$inputarr = array($inputarr);
			}

			$element0 = reset($inputarr);
			# is_object check because oci8 descriptors can be passed in
			$array_2d = $this->bulkBind && is_array($element0) && !is_object(reset($element0));

			//remove extra memory copy of input -mikefedyk
			unset($element0);

			if (!is_array($sql) && !$this->_bindInputArray) {
				// @TODO this would consider a '?' within a string as a parameter...
				$sqlarr = explode('?',$sql);
				$nparams = sizeof($sqlarr)-1;

				if (!$array_2d) {
					// When not Bind Bulk - convert to array of arguments list
					$inputarr = array($inputarr);
				} else {
					// Bulk bind - Make sure all list of params have the same number of elements
					$countElements = array_map('count', $inputarr);
					if (1 != count(array_unique($countElements))) {
						$message = 	"[bulk execute] Input array has different number of params  [" . print_r($fcountElements, true) . ']';
						$this->loggingObject->log(Logger::CRITICAL,$message);	
						return false;
					}
					unset($countElements);
				}
				// Make sure the number of parameters provided in the input
				// array matches what the query expects
				$element0 = reset($inputarr);
				if ($nparams != count($element0)) {
					$message = "Input array has " . count($element0) .
					" params, does not match query: '" . htmlspecialchars($sql) . "'";
					$this->loggingObject->log(Logger::CRITICAL,$message);	

					return false;
				}

				// clean memory
				unset($element0);

				foreach($inputarr as $arr) {
					$sql = ''; $i = 0;
					foreach ($arr as $v) {
						$sql .= $sqlarr[$i];
						// from Ron Baldwin <ron.baldwin#sourceprose.com>
						// Only quote string types
						$typ = gettype($v);
						if ($typ == 'string') {
							//New memory copy of input created here -mikefedyk
							$sql .= $this->qstr($v);
						} else if ($typ == 'double') {
							$sql .= str_replace(',','.',$v); // locales fix so 1.1 does not get converted to 1,1
						} else if ($typ == 'boolean') {
							$sql .= $v ? $this->true : $this->false;
						} else if ($typ == 'object') {
							if (method_exists($v, '__toString')) {
								$sql .= $this->qstr($v->__toString());
							} else {
								$sql .= $this->qstr((string) $v);
							}
						} else if ($v === null) {
							$sql .= 'NULL';
						} else {
							$sql .= $v;
						}
						$i += 1;

						if ($i == $nparams) {
							break;
						}
					} // while
					if (isset($sqlarr[$i])) {
						$sql .= $sqlarr[$i];
						if ($i+1 != sizeof($sqlarr)) {
							$message = "Input Array does not match ?: ".htmlspecialchars($sql);
							$this->loggingObject->log(Logger::CRITICAL,$message);	

						}
					} else if ($i != sizeof($sqlarr)) {
						$message = "Input array does not match ?: ".htmlspecialchars($sql);
						$this->loggingObject->log(Logger::CRITICAL,$message);	

					}

					$ret = $this->_Execute($sql);
					if (!$ret) {
						return $ret;
					}
				}
			} else {
				if ($array_2d) {
					if (is_string($sql)) {
						$stmt = $this->prepare($sql);
					} else {
						$stmt = $sql;
					}

					foreach($inputarr as $arr) {
						$ret = $this->_Execute($stmt,$arr);
						if (!$ret) {
							return $ret;
						}
					}
				} else {
					$ret = $this->_execute($sql,$inputarr);
				}
			}
		} else {
			$ret = $this->_execute($sql,null);
		}

		return $ret;
	}

	/**
	* Executes an sql statement
	*
	* @param string|array $sql
	* @param sarray|bool $inputarr
	*
	* @return mixed obj|bool
	*/
	protected function _execute(
		string $sql, 
		?array $inputarr=null) : ?object {
		/*
		* ExecuteCursor() may send non-string queries (such as arrays),
		* so we need to ignore those.
		*/
		if( is_string($sql) ) {
			/*
			* Strips keyword used to help generate SELECT COUNT(*) queries
			* from SQL if it exists.
			*/
			$sql = str_replace( '_ADODB_COUNT', '', $sql );
		}

		if ($this->debug) {
			
			$this->debugger = new \ADOdb\database\debug\debugger($this);
			$this->_queryID = $this->debugger->_query($sql,$inputarr);
		
		} else {
			$this->_queryID = @$this->_query($sql,$inputarr);
		}

		// ************************
		// OK, query executed
		// ************************

		/*
		* error handling if query fails
		*/
		if ($this->_queryID == false) {
			if ($this->debug == 99) {
				//$debugger = new \ADOdb\database\debug\debugger($this);
				//$debugger->doBacktrace(true,5);
			}
			$fn = $this->raiseErrorFn;
			if ($fn) {
				
				$this->transactionHandlingObject->$fn($this->database,'EXECUTE',$this->ErrorNo(),$this->ErrorMsg(),$sql,$inputarr,$this);
			}
			return null;
		}

		/*
		* return simplified recordset for inserts/updates/deletes 
		* with lower overhead
		*/
		if ($this->_queryID === true)
		{
			$rsClass = $this->driverPath . 'ADORecordSetEmpty';
			
			$rs = new $rsClass($this->_queryID,$this);
			return $rs;
			
		}

		/*
		* return real recordset from select statement
		*/
		$rsclass = $this->driverPath . 'ADORecordSet';
		
		$rs = new $rsclass($this->_queryID,$this);
		$rs->init();
		
		if (is_array($sql)) {
			$rs->sql = $sql[0];
		} else {
			$rs->sql = $sql;
		}
		if ($rs->recordCount() <= 0) {
			if ($this->countRecords && !$this->disableCountRecords) {
				if (!$rs->EOF) {
					$rs = $this->_rs2rs($rs,-1,-1,!is_array($sql));
				} else {
					$rs->setRecordCount(0);
				}
			}
		}
		return $rs;
	}

	/**
	* Create a sequence (real or emulated)
	*
	* @param str $sequence name
	* @param int $startId
	*
	* @return mixed
	*/
	public function createSequence(
			string $seqname='adodbseq',
			int $startID=1) {
		
		if (empty($this->_genSeqSQL)) {
			return false;
		}
		
		return $this->execute(sprintf($this->_genSeqSQL,$seqname,$startID));
	}

	/**
	* Drops a sequence
	*
	* @param str $seqname 
	*
	* @return mixed
	*/
	public function dropSequence(string $seqname='adodbseq') {
		
		if (empty($this->_dropSeqSQL)) {
			return false;
		}
		return $this->execute(sprintf($this->_dropSeqSQL,$seqname));
	}

	/**
	 * Generates a sequence id and stores it in $this->genID;
	 * GenID is only available if $this->hasGenID = true;
	 *
	 * @param seqname		name of sequence to use
	 * @param startID		if sequence does not exist, start at this ID
	 * @return		0 if not supported, otherwise a sequence id
	 */
	public function genID(
		string $seqname='adodbseq',
		int $startID=1) : int {
		if (!$this->hasGenID) {
			return 0; // formerly returns false pre 1.60
		}

		$getnext = sprintf($this->_genIDSQL,$seqname);

		$holdtransOK = $this->_transOK;

		$save_handler = $this->raiseErrorFn;
		$this->raiseErrorFn = '';
		@($rs = $this->Execute($getnext));
		$this->raiseErrorFn = $save_handler;

		if (!$rs) {
			$this->_transOK = $holdtransOK; //if the status was ok before reset
			$createseq = $this->execute(sprintf($this->_genSeqSQL,$seqname,$startID));
			$rs = $this->execute($getnext);
		}
		
		if ($rs && !$rs->EOF) {
			$this->genID = reset($rs->fields);
		} else {
			$this->genID = 0; // false
		}

		if ($rs) {
			$rs->Close();
		}

		return $this->genID;
	}

	/**
	* Returns the global connection insert id
	*
	 * @param $table string name of the table, not needed by all databases (eg. mysql), default ''
	 * @param $column string name of the column, not needed by all databases (eg. mysql), default ''
	 * @return  the last inserted ID. Not all databases support this.
	 */
	final public function insert_ID(
			string $table='',
			string $column='') : ?int {
		
		if ($this->_logsql && $this->lastInsID) {
			return $this->lastInsID;
		}
		
		if ($this->hasInsertID) {
			return $this->_insertid($table,$column);
		}
		if ($this->debug) {
			$debugger = new \ADOdb\database\debug\debugger($this);
			$debugger->logMessage( 'Insert_ID error');
			$debugger->doBacktrace(true,5);
		}
		return null;
	}


	
	/**
	* @return # rows affected by UPDATE/DELETE
	*
	* @return int
	*/
	public function Affected_Rows() : ?int {
		if ($this->hasAffectedRows) {
			if ($this->fnExecute === 'adodb_log_sql') {
				if ($this->_logsql && $this->_affected !== false) {
					return $this->_affected;
				}
			}
			$val = $this->_affectedrows();
			return ($val < 0) ? null : $val;
		}

		if ($this->debug) {
			$this->loggingObject->log(Logger::DEBUG, 'Affected_Rows error');
		}
		return null;
	}


	/**
	* @return  the last error message
	* 
	* @return string
	*/
	public function errorMsg() : string {
		if ($this->_errorMsg) {
			return '!! '.strtoupper($this->dataProvider.' '.$this->databaseType).': '.$this->_errorMsg;
		} else {
			return '';
		}
	}

	/**
	* @return the last error number. Normally 0 means no error.
	*/
	public function errorNo() : int
	{
		return ($this->_errorMsg) ? -1 : 0;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function metaError(bool $err=false) : int
	{
		$aemClass = $this->driverPath . '\ADOErrorMap';
		$aem      = new $aemClass($this);
		
		if ($err === false) {
			$err = $this->ErrorNo();
		}
		return $aem->adodb_error($err);
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function metaErrorMsg(int $errno) : string 
	{
		$aemClass = $this->driverPath . 'ADOErrorMap';
		$aem      = new $aemClass($this);
		
		if ($errno === false) {
			$errno = $this->ErrorNo();
		}
		return $aem->adodb_errormsg($errno);
	}

	/**
	* @returns an array with the primary key columns in it
	*
	* @param str $table
	* @param str $owner
	*
	* @return array
	*/
	final public function metaPrimaryKeys(
		string $table, 
		string $owner=null): array {
		
		$metaClass = $this->driverPath . 'ADOMetaFunctions';
		$meta = new $metaClass($this);
		
		return $meta->metaPrimaryKeys($table,$owner);

	}

	/**
	* @returns assoc array where keys are tables, and values are foreign keys
	*
	* @param str $1
	* @param str $2
	*
	* return array
	*/
	final public function metaForeignKeys( 
			string $table,
			string $owner = null,
			bool $upper = false, 
			bool $associative = false) : array
	{
		$metaClass = $this->driverPath . 'ADOMetaFunctions';
		$meta = new $metaClass($this);
		return $meta->metaForeignKeys( $table, $owner ,$upper, $associative);
	}	
	
	/**
	* Choose a database to connect to. Many databases do not support this.
	*
	* @param dbName is the name of the database to select
	* @return true or false
	*/
	public function selectDB(string $dbName) : bool {
		return false;
	}

	/**
	* Will select, getting rows from $offset (1-based), for $nrows.
	* This simulates the MySQL "select * from table limit $offset,$nrows" , and
	* the PostgreSQL "select * from table limit $nrows offset $offset". Note that
	* MySQL and PostgreSQL parameter ordering is the opposite of the other.
	* eg.
	*  SelectLimit('select * from table',3); will return rows 1 to 3 (1-based)
	*  SelectLimit('select * from table',3,2); will return rows 3 to 5 (1-based)
	*
	* Uses SELECT TOP for Microsoft databases (when $this->hasTop is set)
	* BUG: Currently SelectLimit fails with $sql with LIMIT or TOP clause already set
	*
	* @param sql
	* @param [offset]	is the row to start calculations from (1-based)
	* @param [nrows]		is the number of rows to get
	* @param [inputarr]	array of bind variables
	* @param [secs2cache]		is a private parameter only used by jlim
	* @return		the recordset ($rs->databaseType == 'array')
	*/
	public function selectLimit(
			string $sql,
			int $nrows=-1,
			int $offset=-1, 
			array $inputarr=null,
			int $secs2cache=0) 
	{
		$nrows = (int)$nrows;
		$offset = (int)$offset;

		if ($this->hasTop && $nrows > 0) {
			// suggested by Reinhard Balling. Access requires top after distinct
			// Informix requires first before distinct - F Riosa
			$ismssql = (strpos($this->databaseType,'mssql') !== false);
			if ($ismssql) {
				$isaccess = false;
			} else {
				$isaccess = (strpos($this->databaseType,'access') !== false);
			}

			if ($offset <= 0) {
					// access includes ties in result
					if ($isaccess) {
						$sql = preg_replace(
						'/(^\s*select\s+(distinctrow|distinct)?)/i','\\1 '.$this->hasTop.' '.$nrows.' ',$sql);

						if ($secs2cache != 0) {
							$ret = $this->CacheExecute($secs2cache, $sql,$inputarr);
						} else {
							$ret = $this->Execute($sql,$inputarr);
						}
						return $ret; // PHP5 fix
					} else if ($ismssql){
						$sql = preg_replace(
						'/(^\s*select\s+(distinctrow|distinct)?)/i','\\1 '.$this->hasTop.' '.$nrows.' ',$sql);
					} else {
						$sql = preg_replace(
						'/(^\s*select\s)/i','\\1 '.$this->hasTop.' '.$nrows.' ',$sql);
					}
			} else {
				$nn = $nrows + $offset;
				if ($isaccess || $ismssql) {
					$sql = preg_replace(
					'/(^\s*select\s+(distinctrow|distinct)?)/i','\\1 '.$this->hasTop.' '.$nn.' ',$sql);
				} else {
					$sql = preg_replace(
					'/(^\s*select\s)/i','\\1 '.$this->hasTop.' '.$nn.' ',$sql);
				}
			}
		}

		// if $offset>0, we want to skip rows, and $ADODB_COUNTRECS is set, we buffer  rows
		// 0 to offset-1 which will be discarded anyway. So we disable $ADODB_COUNTRECS.
	
		$this->disableCountRecords = true;


		if ($secs2cache != 0) {
			$rs = $this->CacheExecute($secs2cache,$sql,$inputarr);
		} else {
			$rs = $this->Execute($sql,$inputarr);
		}

		$this->disableCountRecords = false;
		if ($rs && !$rs->EOF) {
			$rs = $this->_rs2rs($rs,$nrows,$offset);
		}
		return $rs;
	}

	/**
	* Create serializable recordset. Breaks rs link to connection.
	*
	* @param rs			the recordset to serialize
	*/
	public function serializableRs(object &$rs) {
		$rs2 = $this->_rs2rs($rs);
		$ignore = false;
		$rs2->connection = $ignore;

		return $rs2;
	}

	/**
	* Convert database recordset to an array recordset
	* input recordset's cursor should be at beginning, and
	* old $rs will be closed.
	*
	* @param rs			the recordset to copy
	* @param [nrows]	number of rows to retrieve (optional)
	* @param [offset]	offset by number of rows (optional)
	* @return			the new recordset
	*/
	public function &_rs2rs(
			object &$rs,
			int $nrows=-1,
			int $offset=-1,
			bool $close=true) {
		
		if (! $rs) {
			return false;
		}
		$dbtype = $rs->databaseType;
		if (!$dbtype) {
			$rs = $rs;  // required to prevent crashing in 4.2.1, but does not happen in 4.3.1 -- why ?
			return $rs;
		}
		if (($dbtype == 'array' || $dbtype == 'csv') && $nrows == -1 && $offset == -1) {
			$rs->MoveFirst();
			$rs = $rs; // required to prevent crashing in 4.2.1, but does not happen in 4.3.1-- why ?
			return $rs;
		}
		$flds = $rs->fetchField(-1);
		
		$arr = $rs->getArrayLimit($nrows,$offset);

		if ($close) {
			$rs->Close();
		}

		$arrayClass = $this->arrayClass;
		
		$rs2 = new $arrayClass($rs,$this);
		
		//$rs2->connection = $this;
		
		$rs2->sql = $rs->sql;
		$rs2->dataProvider = $this->dataProvider;
		$rs2->initArrayFields($arr,$flds);
		$rs2->fetchMode = isset($rs->adodbFetchMode) ? $rs->adodbFetchMode : $rs->fetchMode;
		return $rs2;
	}

	/*
	* Return all rows.
	*
	* @param str	$sql
	* @param mixed  $inputarr
	* @param ?array	$cacheInfo
	*
	* @return ?array
	*/
	final public function getAll(
		string $sql, 
		array $inputarr=null,
		?array $cacheInfo=null) : array {
		
		if ($cacheInfo === null)
			return $this->getArray($sql,$inputarr);
		else
		{
			$secs2cache = -1;
			$severKey  = '';
			
			if (isset($cacheInfo['cachesecs']))
				$secs2cache = $cacheInfo['cachesecs'];
			
			if (isset($cacheInfo['serverkey']))
				$serverKey = $cacheInfo['serverkey'];
			
			return $this->cacheObject->getAll($secs2cache,$sql,$inputarr,$serverKey);
		}
	}

	/**
	* Gets an associative array of data
	*
	* @param str $sql
	* @param ?array $inputarr
	* @param bool	$force_arrau
	* @param bool	$first2cols
	* @param ?array $cacheOptions
	*
	* @return
	*/
	final public function getAssoc(
		string $sql, 
		array $inputarr=null,
		bool $force_array = false, 
		bool $first2cols = false,
		?array $cacheOptions=null) {
			
		$currentCacheOption = $this->unpackCacheParameters($cacheOptions);
		
		if (is_array($currentCacheOption)) { 
						
			return $this->cacheObject->getAssoc(
						$sql,
						$inputarr,
						$force_array,
						$first2cols,
						$currentCacheOption
						);
		
		} else { 
			
			$rs = $this->execute($sql, $inputarr);
		}
		
		if (!$rs) {
			return false;
		}
		$arr = $rs->GetAssoc($force_array,$first2cols);
		return $arr;
	}

	
	/**
	* Return first element of first row of sql statement. Recordset is disposed
	* for you.
	*
	* @param sql			SQL statement
	* @param [inputarr]		input bind array
	* @param ?array $cacheOptions
	*
	* @param mixed
	*/
	final public function getOne(
				string $sql,
				?array $inputarr=null,
				?array $cacheOptions=null)  {
		
		
		$currentCacheOption = $this->unpackCacheParameters($cacheOptions);
		
		if (is_array($currentCacheOption)) { 
						
			return $this->cacheObject->getOne($sql,$inputarr,$currentCacheOption);
			
		}
		
		$this->disableCountRecords = true;

		$ret = false;
		$rs = $this->execute($sql,$inputarr);
		if ($rs) {
			if ($rs->EOF) {
				$ret = $this->getOneEOF;
			} else {
				$ret = reset($rs->fields);
			}

			$rs->Close();
		}
		$this->disableCountRecords = false;
		
		return $ret;
	}

	/*
	* Returns the median value of a column in a table. This
	* can be very slow. The $where should include 'WHERE fld=value'
	* if used
	*
	* @param	string $table
	* @param	string $field
	* @param	string $where
	*
	* @return string Median value
	*/
	final public function getMedian(
		string $table, 
		string $field,
		string $where = '')  : ?string {
			
		$total = $this->GetOne("select count(*) from $table $where");
		if (!$total) {
			return null;
		}

		$midrow = (integer) ($total/2);
		
		$sql = "select $field from $table $where order by 1";
		
		$rs = $this->SelectLimit($sql,1,$midrow);
		
		if ($rs && !$rs->EOF) {
			return reset($rs->fields);
		}
		return null;
	}

	/**
	* Returns the first column of a recordset
	*
	* @param string 	$sql
	* @param mixed		$inputarr
	* @return bool		$trim
	*
	* @return string[]
    */
	final public function getCol(
				string $sql, 
				?array $inputarr=null,
				bool $trim=false,
				?array $cacheOptions=null) {
		
		$currentCacheOption = $this->unpackCacheParameters($cacheOptions);
		
		if ($currentCacheOption) {
			
			return $this->cacheObject->getCol($sql,$inputarr,$trim,$currentCacheOption);
			
		}
		
		$rs = $this->Execute($sql, $inputarr);
		if ($rs) {
			$rv = array();
			if ($trim) {
				while (!$rs->EOF) {
					$rv[] = trim(reset($rs->fields));
					$rs->moveNext();
				}
			} else {
				while (!$rs->EOF) {
					$rv[] = reset($rs->fields);
					$rs->moveNext();
				}
			}
			$rs->Close();
		} else {
			$rv = false;
		}
		return $rv;
	}

	/**
	*Calculate the offset of a date for a particular database and generate
	* appropriate SQL. Useful for calculating future/past dates and storing
	*		in a database.
    *
	* @example If dayFraction=1.5 means 1.5 days from now, 1.0/24 for 1 hour.
	*/
	public function offsetDate($dayFraction,$date=false) {
		
		$dtClass = $this->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dtClass($this);
		return $dt->offsetDate($dayFraction,$date);
		
	}

	/**
	*
	* @param sql			SQL statement
	* @param [inputarr]		input bind array
	*/
	final public function getArray(
				string $sql,
				?array $inputarr=null,
				?array $cacheOptions=null) : ?array {
		
		$currentCacheOption = $this->unpackCacheParameters($cacheOptions);
		
		if ($currentCacheOption) {
			
			return $this->cacheObject->getArray($sql,$inputarr,$currentCacheOption);
			
		}
		
		$this->disableCountRecords = true;
		
		$rs = $this->execute($sql,$inputarr);
		
		$this->disableCountRecords = false;
		
		if (!$rs)
			return false;
		
		$arr = $rs->getArray();
		$rs->Close();
		return $arr;
	}

	/**
	* Return one random row of a Recordset.
	*
	* @param sql			SQL statement
	* @param [inputarr]		input bind array
	*/
	final public function getRandRow(
		string $sql,
		?array $arr=null) : ?array	{
		$rezarr = $this->getAll($sql, $arr);
		$sz = sizeof($rezarr);
		return $rezarr[abs(rand()) % $sz];
	}

	/**
	* Return one row of sql statement. Recordset is disposed for you.
	* Note that SelectLimit should not be called.
	*
	* @param sql			SQL statement
	* @param [inputarr]		input bind array
	* @param [cacheOptions]
	*/
	final public function getRow(
			string $sql,
			$inputarr=null,
			$cacheOptions=null) : ?array {
		
		
		$currentCacheOption = $this->unpackCacheParameters($cacheOptions);
		
		if ($currentCacheOption) {
			
			return $this->cacheObject->getRow($sql,$inputarr,$currentCacheOption);
			
		}
		
		
		$this->disableCountRecords = true;
		
		$rs = $this->execute($sql,$inputarr);
		
		$this->disableCountRecords = false;

		if ($rs) {
			if (!$rs->EOF) {
				$arr = $rs->fetchFields();
			} else {
				$arr = array();
			}
			$rs->Close();
			return $arr;
		}

		return false;
	}

	

	/**
	* Insert or replace a single record. Note: this is not the same as MySQL's replace.
	* ADOdb's Replace() uses update-insert semantics, not insert-delete-duplicates of MySQL.
	* Also note that no table locking is done currently, so it is possible that the
	* record be inserted twice by two programs...
	*
	* $this->Replace('products', array('prodname' =>"'Nails'","price" => 3.99), 'prodname');
	*
	* $table		table name
	* $fieldArray	associative array of data (you must quote strings yourself).
	* $keyCol		the primary key field name or if compound key, array of field names
	* autoQuote		set to true to use a hueristic to quote strings. Works with nulls and numbers
	*					but does not work with dates nor SQL functions.
	* has_autoinc	the primary key is an auto-inc field, so skip in insert.
	*
	* Currently blob replace not supported
	*
	* returns 0 = fail, 1 = update, 2 = insert
	*/

	final public function replace(
			string $table, 
			array $fieldArray, 
			string $keyCol, 
			bool $autoQuote=false,
			bool $has_autoinc=false) {
		
		$handlerClass = $this->driverPath . 'ADORecordHandler';
		$recordHandler = new $handlerClass($this);


		return $recordHandler->_adodb_replace($table, $fieldArray, $keyCol, $autoQuote, $has_autoinc);
	}


	/*
		Similar to PEAR DB's autoExecute(), except that
		$mode can be 'INSERT' or 'UPDATE' or DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE
		If $mode == 'UPDATE', then $where is compulsory as a safety measure.

		$forceUpdate means that even if the data has not changed, perform update.
	 */
	final public function autoExecute(
		string $table, 
		string $fields_values, 
		string $mode = 'INSERT', 
		string $where = null, 
		bool $forceUpdate = true, 
		bool $magicq = false) 
	{
		
		$handlerClass = $this->driverPath . 'ADORecordHandler';
		$recordHandler = new $handlerClass($this);

		return $recordHandler->autoExecute($table, $fields_values, $mode,$where,$forceUpdate, $magicq); 
		
	}

	/**
	 * Generates an Update Query based on an existing recordset.
	 * $arrFields is an associative array of fields with the value
	 * that should be assigned.
	 *
	 * Note: This function should only be used on a recordset
	 *	   that is run against a single table and sql should only
	 *		 be a simple select stmt with no groupby/orderby/limit
	 *
	 * "Jonathan Younger" <jyounger@unilab.com>
	 */
	final public function getUpdateSQL(
		object &$rs, 
		array $arrFields,
		bool $forceUpdate=false,
		bool $magicq=false,
		bool $force=null) 
	{
		
		$handlerClass = $this->driverPath . 'ADORecordHandler';
		$recordHandler = new $handlerClass($this);
		
		// ********************************************************
		// This is here to maintain compatibility
		// with older adodb versions. Sets force type to force nulls if $forcenulls is set.
		if (!isset($force)) {
			global $ADODB_FORCE_TYPE;
			$force = $ADODB_FORCE_TYPE;
		}
		
		return $recordHandler->getUpdateSQL($rs,$arrFields,$forceUpdate,$magicq,$force);
	}

	/**
	 * Generates an Insert Query based on an existing recordset.
	 * $arrFields is an associative array of fields with the value
	 * that should be assigned.
	 *
	 * Note: This function should only be used on a recordset
	 *       that is run against a single table.
	 */
	final public function getInsertSQL(&$rs, $arrFields,$magicq=false,$force=0) {
		
		if (!isset($force)) {
			global $ADODB_FORCE_TYPE;
			$force = $ADODB_FORCE_TYPE;
		}
		
		$handlerClass = $this->driverPath . 'ADORecordHandler';
		
		$recordHandler = new $handlerClass($this);
		
		return $recordHandler->getInsertSQL($rs,$arrFields,$magicq,$force);
	}


	/**
	* Update a blob column, given a where clause. There are more sophisticated
	* blob handling functions that we could have implemented, but all require
	* a very complex API. Instead we have chosen something that is extremely
	* simple to understand and use.
	*
	* Note: $blobtype supports 'BLOB' and 'CLOB', default is BLOB of course.
	*
	* Usage to update a $blobvalue which has a primary key blob_id=1 into a
	* field blobtable.blobcolumn:
	*
	*	UpdateBlob('blobtable', 'blobcolumn', $blobvalue, 'blob_id=1');
	*
	* Insert example:
	*
	*	$conn->Execute('INSERT INTO blobtable (id, blobcol) VALUES (1, null)');
	*	$conn->UpdateBlob('blobtable','blobcol',$blob,'id=1');
	*/
	public function UpdateBlob($table,$column,$val,$where,$blobtype='BLOB') {
		return $this->Execute("UPDATE $table SET $column=? WHERE $where",array($val)) != false;
	}

	/**
	* Usage:
	*	UpdateBlob('TABLE', 'COLUMN', '/path/to/file', 'ID=1');
	*
	*	$blobtype supports 'BLOB' and 'CLOB'
	*
	*	$conn->Execute('INSERT INTO blobtable (id, blobcol) VALUES (1, null)');
	*	$conn->UpdateBlob('blobtable','blobcol',$blobpath,'id=1');
	*/
	public function UpdateBlobFile($table,$column,$path,$where,$blobtype='BLOB') {
		$fd = fopen($path,'rb');
		if ($fd === false) {
			return false;
		}
		$val = fread($fd,filesize($path));
		fclose($fd);
		return $this->UpdateBlob($table,$column,$val,$where,$blobtype);
	}

	function blobDecode($blob) {
		return $blob;
	}

	function blobEncode($blob) {
		return $blob;
	}

	/**
	* Get the name of the character set the client connection is using now.
	*
	* @return ?string The name of the character set, or null if it can't be determined.
	*/
	public function getCharSet() : ?string {
		return $this->charSet;
	}
	
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	public function setCharSet(string $charset) : bool {
		$this->charSet = $charset;
		return true;
	}
	
	/**
	* Creates a db specific ifnull statement
	*
	* @param str $field
	* @param str $ifNull descriptor for ifnull
	*
	* @return string
	*/
	public function ifNull( 
				string $field, 
				string $ifNull ) : string {
					
		return " CASE WHEN $field is null THEN $ifNull ELSE $field END ";
	}
	
	
	/**
	* Usage:
	*	UpdateClob('TABLE', 'COLUMN', $var, 'ID=1', 'CLOB');
	*
	*	$conn->Execute('INSERT INTO clobtable (id, clobcol) VALUES (1, null)');
	*	$conn->UpdateClob('clobtable','clobcol',$clob,'id=1');
	*
	8 param string $table
	8 param string $column
	8 param string $val
	8 param string $where
	*
	* @return string
	*/
	public function updateClob(
				string $table,
				string $column,
				string $val,
				string $where) : string {
					
		return $this->updateBlob($table,$column,$val,$where,'CLOB');
	}
	
	/**
	* Return the ADOdb metatype for the db type
	*
	* @param string|object $t
	* @param int $len
	* @param object|bool $fieldobj
	*
	* @return string
	*/
	final public function metaType(
			$t, 
			int $len = -1, 
			$fieldobj = false): string {

		$metaPath = $this->driverPath . 'ADOMetaFunctions';
		$meta = new $metaPath($this);
		return $meta->metaType($t,$len=-1,$fieldobj=false);
	}

	/**
	*  Change the SQL connection locale to a specified locale.
	*  This is used to get the date formats written depending on the client locale.
	*/
	public function setDateLocale($locale = 'En') {
		$this->locale = $locale;
		switch (strtoupper($locale))
		{
			case 'EN':
				$this->fmtDate="'Y-m-d'";
				$this->fmtTimeStamp = "'Y-m-d H:i:s'";
				break;

			case 'US':
				$this->fmtDate = "'m-d-Y'";
				$this->fmtTimeStamp = "'m-d-Y H:i:s'";
				break;

			case 'PT_BR':
			case 'NL':
			case 'FR':
			case 'RO':
			case 'IT':
				$this->fmtDate="'d-m-Y'";
				$this->fmtTimeStamp = "'d-m-Y H:i:s'";
				break;

			case 'GE':
				$this->fmtDate="'d.m.Y'";
				$this->fmtTimeStamp = "'d.m.Y H:i:s'";
				break;

			default:
				$this->fmtDate="'Y-m-d'";
				$this->fmtTimeStamp = "'Y-m-d H:i:s'";
				break;
		}
	}

	/**
	 * GetActiveRecordsClass Performs an 'ALL' query
	 *
	 * @param mixed $class This string represents the class of the current active record
	 * @param mixed $table Table used by the active record object
	 * @param mixed $whereOrderBy Where, order, by clauses
	 * @param mixed $bindarr
	 * @param mixed $primkeyArr
	 * @param array $extra Query extras: limit, offset...
	 * @param mixed $relations Associative array: table's foreign name, "hasMany", "belongsTo"
	 * @access public
	 * @return void
	 */
	function GetActiveRecordsClass(
			$class, $table,$whereOrderBy=false,$bindarr=false, $primkeyArr=false,
			$extra=array(),
			$relations=array())
	{
		global $_ADODB_ACTIVE_DBS;
		## reduce overhead of adodb.inc.php -- moved to adodb-active-record.inc.php
		## if adodb-active-recordx is loaded -- should be no issue as they will probably use Find()
		if (!isset($_ADODB_ACTIVE_DBS)) {
			include_once(ADODB_DIR.'/adodb-active-record.inc.php');
		}
		return adodb_GetActiveRecordsClass($this, $class, $table, $whereOrderBy, $bindarr, $primkeyArr, $extra, $relations);
	}

	function GetActiveRecords($table,$where=false,$bindarr=false,$primkeyArr=false) {
		$arr = $this->GetActiveRecordsClass('ADODB_Active_Record', $table, $where, $bindarr, $primkeyArr);
		return $arr;
	}

	/**
	 * Close Connection
	 */
	function Close() {
		$rez = $this->_close();
		$this->_queryID = false;
		$this->_connectionID = false;
		return $rez;
	}

	/**
	 * Begin a Transaction. Must be followed by CommitTrans() or RollbackTrans().
	 *
	 * @return true if succeeded or false if database does not support transactions
	 */
	public function beginTrans() : bool {
		
		return $this->transactionHandlingObject->beginTrans($this);
	}

	/**
	* Set Transaction Mode
	*
	* @param str $transaction_mode
	*
	* @return void
	*/
	public function setTransactionMode( string $transaction_mode ): void {
		
		$transaction_mode = $this->MetaTransaction($transaction_mode, $this->dataProvider);
		$this->_transmode  = $transaction_mode;
	}

	/**
	* Gets the matching transaction mode for the database
	*
	* @param str $mode
	*
	* @return string
	*/
	final public function metaTransaction(string $mode) : string 
	{
		
		$metaClass = $this->driverPath . 'ADOMetaFunctions';
		$mc = new $metaClass($this);
		
		return $mc->metaTransaction($mode);
		
	}

	/**
	 * If database does not support transactions, always return true as data always commited
	 *
	 * @param $ok  set to false to rollback transaction, true to commit
	 *
	 * @return true/false.
	 */
	public function commitTrans(bool $ok=true) : bool {
		return true;
	}


	/**
	 * If database does not support transactions, rollbacks always fail, so return false
	 *
	 * @return true/false.
	 */
	public function rollbackTrans() : bool {
		return false;
	}
	
	
	/**
	* Directly pushes any message into the DEBUG log
	*
	* @param string	$tag
	* @param string $message
	*
	* @return void
	*/
	final public function debugLogMarker(string $tag,$message): void {
		
		if ($this->debug)
			$this->loggingObject->log(Logger::DEBUG,"$tag: $message");
	}


	/**
	 * return the databases that the driver can connect to.
	 * Some databases will return an empty array.
	 *
	 * @return an array of database names.
	 */
	final public function metaDatabases()
	{
		$metaClass = $this->connectionDefinitions->driverPath . 'ADOMetaFunctions';
		$meta = new $metaClass($this);
		
		return $meta->metaDatabases();

	}

	/**
	 * List procedures or functions in an array.
	 * @param procedureNamePattern  a procedure name pattern; must match the procedure name as it is stored in the database
	 * @param catalog a catalog name; must match the catalog name as it is stored in the database;
	 * @param schemaPattern a schema name pattern;
	 *
	 * @return array of procedures on current database.
	 *
	 * Array(
	 *   [name_of_procedure] => Array(
	 *     [type] => PROCEDURE or FUNCTION
	 *     [catalog] => Catalog_name
	 *     [schema] => Schema_name
	 *     [remarks] => explanatory comment on the procedure
	 *   )
	 * )
	 */

	final public function metaProcedures(
			bool $namePattern=false, 
			bool $catalog=null,
			string $schemaPattern=null)
	{
		$metaPath = $this->driverPath . 'ADOMetaFunctions';
		$meta = new $metaPath($this);
		return $meta->MetaProcedures($namePattern, $catalog, $schemaPattern);
	}

	/**
	 * @param ttype can either be 'VIEW' or 'TABLE' or false.
	 *		If false, both views and tables are returned.
	 *		"VIEW" returns only views
	 *		"TABLE" returns only tables
	 * @param showSchema returns the schema/user with the table name, eg. USER.TABLE
	 * @param mask  is the input mask - only supported by oci8 and postgresql
	 *
	 * @return  array of tables for current database.
	 */
	final public function metaTables(
		bool $ttype=false,
		bool $showSchema=false,
		bool $mask=false)
	{
		
		$metaPath = $this->driverPath . 'ADOMetaFunctions';
		$meta = new $metaPath($this);
		return $meta->metaTables($ttype,$showSchema,$mask);
	}

	/**
	* Function not described
	*
	* @param str $table
	* @param str $schema
	*
	* @return void
	*/
	final public function _findschema(
		string &$table,
		string &$schema) : void {
		if (!$schema && ($at = strpos($table,'.')) !== false) {
			$schema = substr($table,0,$at);
			$table = substr($table,$at+1);
		}
	}

	/**
	 * List columns in a database as an array of ADOFieldObjects.
	 * See top of file for definition of object.
	 *
	 * @param $table	table name to query
	 * @param $normalize	makes table name case-insensitive (required by some databases)
	 * @schema is optional database schema to use - not supported by all databases.
	 *
	 * @return  array of ADOFieldObjects for current table.
	 */
	final public function metaColumns(
			string $table, 
			bool $normalize=true)
	{
		
		$metaClass = $this->driverPath . 'ADOMetaFunctions';
		$meta = new $metaClass($this);
		
		return $meta->metaColumns($table, $normalize=true);

	}
	/**
	 * List indexes on a table as an array.
	 * Array(
	 *   [name_of_index] => Array(
	 *     [unique] => true or false
	 *     [columns] => Array(
	 *       [0] => firstname
	 *       [1] => lastname
	 *     )
	 *   )
	 * )
	 * @param string table  table name to query
	 * @param bool primary true to only show primary keys. 
	 * @param string owner
	 *
	 * @return array of indexes on current table. Each element represents an index, and is
	 * itself an associative array.
	 *
	 */
	final public function metaIndexes (
		string $table, 
		bool $primary = false, 
		?string $owner = null) : ?array{
		
		$metaClass = $this->driverPath . 'ADOMetaFunctions';
		$meta = new $metaClass($this);
		
		return $meta->metaIndexes($table, $primary, $owner);
		
	}
	/**
	 * List columns names in a table as an array
	 *
	 * @param string table	table name to query
	 * @param bool numericIndexes 
	 * @param bool postgres index optionb
	 *
	 * @return  array of column names for current table.
	 */
	final public function metaColumnNames(
		string $table, 
		bool $numIndexes=false, 
		bool $useattnum=false) : array
	{

		$metaClass = $this->driverPath . 'ADOMetaFunctions';
		$meta = new $metaClass($this);
		
		return $meta->metaColumnNames($table, $numIndexes,$useattnum);

	}

	/**
	 * Different SQL databases used different methods to combine strings together.
	 * This function provides a wrapper.
	 *
	 * param s	variable number of string parameters
	 *
	 * Usage: $db->Concat($str1,$str2);
	 *
	 * @return concatenated string
	 */
	public function concat() : string {
		/*
		* Gets as many arguments as passed
		*/
		$arr = func_get_args();
		return implode($this->concat_operator, $arr);
	}


	/**
	 * Converts a date "d" to a string that the database can understand.
	 *
	 * @param d	a date in Unix date format.
	 *
	 * @return  date string in database date format
	 */
	final public function dbDate(string $d, bool $isfld=false) {
		
		$dtClass = $this->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dtClass($this);
		
		return $dt->dbDate($d,$isfld);
	}

	/**
	*
	*
	* @param string	$d
	*
	* @return string
	*/
	final public function bindDate(string $d)
	{
		$dClass = $this->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dClass($this);
		
		return $dt->bindDate($d);
	}
	
	/**
	*
	*
	* @param string	$d
	*
	* @return string
	*/
	final public function bindTimeStamp(string $d)
	{
		$dClass = $this->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dClass($this);
		
		return $dt->bindTimeStamp($d);
	}
	/**
	 * Converts a timestamp "ts" to a string that the database can understand.
	 *
	 * @param ts	a timestamp in Unix date time format.
	 *
	 * @return  timestamp string in database timestamp format
	 */
	final public function dbTimeStamp(int $ts,bool $isfld=false)
	{
		$dClass = $this->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dClass($this);
		
		return $dt->dbTimeStamp($ts,$isfld);
	}

	/**
	* Also in ADORecordSet.
	* @param $v is a date string in YYYY-MM-DD format
	*
	* @return date in unix timestamp format, or 0 if before TIMESTAMP_FIRST_YEAR, or false if invalid * date format
	 */
	final public function unixDate(string $v)
	{
		$dClass = $this->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dClass($this);
		
		return $dt->unixDate($v);
	}

	/**
	 * Also in ADORecordSet.
	 * @param $v is a timestamp string in YYYY-MM-DD HH-NN-SS format
	 *
	 * @return date in unix timestamp format, or 0 if before TIMESTAMP_FIRST_YEAR, or false if invalid date format
	 */
	final public function unixTimeStamp(string $v)
	{
		$dClass = $this->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dClass($this);
		
		return $dt->unixTimeStamp($v);
	}
	

	/**
	 * Also in ADORecordSet.
	 *
	 * Format database date based on user defined format.
	 *
	 * @param v		is the character date in YYYY-MM-DD format, returned by database
	 * @param fmt	is the format to apply to it, using date()
	 *
	 * @return a date formated as user desires
	 */
	final public function userDate(
		
			string $v,
			string $fmt='Y-m-d',
			bool $gmt=false) {

		$dClass = $this->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dClass($this);
		
		return $dt->UserDate($v,$fmt,$gmt);
	}
	
	/**
	 *
	 * @param v		is the character timestamp in YYYY-MM-DD hh:mm:ss format
	 * @param fmt	is the format to apply to it, using date()
	 *
	 * @return a timestamp formated as user desires
	 */
	final public function userTimeStamp(
			string $v,
			string $fmt='Y-m-d H:i:s',
			bool $gmt=false) {

		$dClass = $this->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dClass($this);
		return $dt->userTimeStamp($v,$fmt,$gmt);
	}

	/**
	* Quotes a string, without prefixing nor appending quotes. 
	* Used mostly when binding
	*
	* @param string $s
	*
	* @return string
	*/
	public function addQ(string $s) : string
	{
		if ($this->replaceQuote[0] == '\\') {
			$s = str_replace(array('\\',"\0"),array('\\\\',"\\\0"),$s);
		}
		return  str_replace("'",$this->replaceQuote,$s);
	}

	/**
	* Correctly quotes a string so that all strings are escaped. We prefix and append
	* to the string single-quotes.
	* @example $db->qstr("Don't bother");
	*
	* @param string $s			the string to quote
	*
	* @return  quoted string to be sent back to database
	*/
	public function qStr(?string $s=null) : string {
		
		if ($this->replaceQuote[0] == '\\'){
			$s = str_replace(array('\\',"\0"),array('\\\\',"\\\0"),$s);
		}
		
		return  "'".str_replace("'",$this->replaceQuote,$s)."'";
	}


	/**
	* Will select the supplied $page number from a recordset, given 
	* that it is paginated in pages of $nrows rows per page.
	*
	* @param string sql
	* @param int	nrows		 is the number of rows per page to get
	* @param int 	page		 is the page number to get (1-based)
	* @param array 	[inputarr]	 array of bind variables
	* @param int 	[secs2cache] is a private parameter only used by jlim
	*
	* @return		the recordset ($rs->databaseType == 'array')
	*/
	final public function pageExecute(
				string $sql, 
				int $nrows, 
				int $page, 
				?array $inputarr=null,
				int $secs2cache=0) : object  {
		
		$pageExecuteClass = '\ADOdb\addons\ADOPagingFunctions';
		$peClass = new $pageExecuteClass($this);
		
		if ($this->pageExecuteCountRows) {
			$rs = $peClass->_adodb_pageexecute_all_rows($sql, $nrows, $page, $inputarr, $secs2cache);
		} else {
			$rs = $peClass->_adodb_pageexecute_no_last_page($sql, $nrows, $page, $inputarr, $secs2cache);
		}
		return $rs;
	}
	
	/**
	 * Get the last error recorded by PHP and clear the message.
	 *
	 * By clearing the message, it becomes possible to detect whether a new error
	 * has occurred, even when it is the same error as before being repeated.
	 *
	 * @return array|null Array if an error has previously occurred. Null otherwise.
	 */
	protected function resetLastError() {
		$error = error_get_last();

		if (is_array($error)) {
			$error['message'] = '';
		}

		return $error;
	}

	/**
	 * Compare a previously stored error message with the last error recorded by PHP
	 * to determine whether a new error has occured.
	 *
	 * @param array|null $old Optional. Previously stored return value of error_get_last().
	 *
	 * @return string The error message if a new error has occured
	 *                or an empty string if no (new) errors have occured..
	 */
	protected function getChangedErrorMsg($old = null) {
		$new = error_get_last();

		if (is_null($new)) {
			// No error has occured yet at all.
			return '';
		}

		if (is_null($old)) {
			// First error recorded.
			return $new['message'];
		}

		$changed = false;
		foreach($new as $key => $value) {
			if ($new[$key] !== $old[$key]) {
				$changed = true;
				break;
			}
		}

		if ($changed === true) {
			return $new['message'];
		}

		return '';
	}
	
	/**
	* Returns the maximum size of a MetaType C field. If the method
	* is not defined in the driver returns ADODB_STRINGMAX_NOTSET
	*
	* @return int
	*/
	public function charMax() : int {
		return self::ADODB_STRINGMAX_NOTSET;
	}

	/**
	* Returns the maximum size of a MetaType X field. If the method
	* is not defined in the driver returns ADODB_STRINGMAX_NOTSET
	*
	* @return int
	*/
	public function textMax(): int {
		return ADODB_STRINGMAX_NOTSET;
	}
	
	/**
	* Returns the minimum boundary for text boxes
	*
	* @return int
	*/
	public function getMinBlobSize() : int {
		return $this->blobSize;
	}
	
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function quoteField(
				string $field,
				string $method) : string {
	
		if ($method == 'BRACKETS')
			$quoteChar = '';
		else
			$quoteChar = $this->nameQuote;
		
		switch ($method) {
		
		case 'BRACKETS':
			$fnameq = $this->leftBracket. strtoupper($field) .$this->rightBracket;
			break;
		case 'LOWER':
			$fnameq = strtolower($field);
			break;
		case 'NATIVE':
			$fnameq = $field;
			break;
		case 'UPPER':
		default:
			$fnameq = strtoupper($field);
			break;
		}
		
		return sprintf('%s%s%s',$fnameq);
	}
				

	/**
	* Returns a substring of a varchar type field
	*
	* Some databases have variations of the parameters, which is why
	* we have an ADOdb function for it
	*
	* @param	string	$fld	The field to sub-string
	* @param	int		$start	The start point
	* @param	int		$length	An optional length
	*
	* @return string	The SQL text
	*/
	public function substr(
		string $fld,
		int $start,
		int $length=0) : string {
		$text = "{$this->substr}($fld,$start";
		if ($length > 0)
			$text .= ",$length";
		$text .= ')';
		return $text;
	}

	/**
	* Formats the date into Month only format MM with leading zeroes
	*
	* @param	string		$fld	The name of the date to format
	*
	* @return	string				The SQL text
	*/
	public function month(string $fld) : string {
		return $this->sqlDate('m',$fld);
	}

	/**
	* Formats the date into Day only format DD with leading zeroes
	*
	* @param	string		$fld	The name of the date to format
	* @return	string		The SQL text
	*/
	public function day(string $fld) : string {
		return $this->sqlDate('d',$fld);
	}

	/**
	* Formats the date into year only format YYYY
	*
	* @param	string		$fld The name of the date to format
	*
	* @return	string		The SQL text
	*/
	public function year(string $fld) : string {
		return $this->sqlDate('Y',$fld);
	}
	
	/**
	* Returns the isodates flag
	*
	* @return bool
	*/
	final public function handlesIsoDates()
	{
		return $this->isoDates;
	}
	
	/**
	* Sets/Resets the override function name
	*
	* @param	string $fName
	*
	* @return void
	*/
	final public function setErrorFunction(?string $fName) : void {
		if ($fName == null)
		{
			$this->raiseErrorRn = $this->_oldErrFn;
			$this->_oldErrFn = '';
		}
		else
		{
			$this->_oldErrorFn = $this->raiseErrorFn;
			$this->raiseErrorFn = $fName;
		}
	}
	
	/************************************************************************
	* Data Caching methods
	*************************************************************************/
	/**
	* Will select the supplied $page number from a recordset, given that 
	* it is paginated in pages of $nrows rows per page. It also saves two 
	* boolean values saying if the given page is the first and/or last one of
	* the recordset. 
	*
	* A non-typed compatibility method
	*
	* @param int	$secs2cache	seconds to cache data, set to 0 to force query
	* @param string	$sql
	* @param int	$nrows		is the number of rows per page to get
	* @param int	$page		is the page number to get (1-based)
	* @param array	[$inputarr]	array of bind variables
	* @param string $serverKey
	*
	* @return		the recordset ($rs->databaseType == 'array')
	*/
	final public function cachePageExecute(
				$secs2cache, 
				$sql=false, 
				$nrows=false, 
				$page=false,
				$inputarr=false,
				$serverKey=false) {
		
		
		/*
		* Uses the standard paging feature, caching is 
		* called from there
		*/
		return $this->pageExecute($sql,$nrows,$page,$inputarr, $secs2cache,$serverKey);
		
		$passedOptions  = func_get_args();
		$argCount 		= count($passedOptions);
		
		if ($argCount < 6)
		{
			array_unshift($passedOptions,$this->cacheSeconds);
		}	
		/*
		* The real typed arguments format for the method
		*/
		$defaultOptions = array(0,'',0,0,null,null);
		
		/*
		* Merge the 2 arrays
		*/
		$currentOptions = array_replace($defaultOptions,$passedOptions);
		
		$currentOptions[4] = $currentOptions[4] ? $currentOptions[2] : null;
		$currentOptions[5] = $currentOptions[5] ? $currentOptions[5] : null;
		
		/*
		* Beware the re-ordering of the options
		*/
		$return = $this->pageExecute(
					$currentOptions[1],$currentOptions[2],
					$currentOptions[3],$currentOptions[0],
					$currentOptions[4]);
					
		if ($return == null)
			/*
			* Backwards compatible
			*/
			$return = false;
		
		return $return;
		
	}
	
	/**
	* Get an associative array from the cache
	*
	* A non-typed compatibility function
	*
	* @param int $secs2cache, 
	* @param string $sql, 
	* @param ?array $inputarr=null,
	* @param bool $force_array = false, 
	* @param bool $first2cols = false
	* @param string $serverKey
	*
	* @return ?array
	*/
	final public function cacheGetAssoc(
				$secs2cache, 
				$sql=false, 
				$inputarr=false,
				$force_array = false, 
				$first2cols = false,
				$serverKey=false)  {
		
			
		$passedOptions  = func_get_args();
		$argCount 		= count($passedOptions);
		
		if ($argCount < 6)
		{
			array_unshift($passedOptions,$this->cacheSeconds);
		}	
		/*
		* The real typed arguments format for the method
		*/
		$defaultOptions = array(0,'',null,false,false,null);
		
		/*
		* Merge the 2 arrays
		*/
		$currentOptions = array_replace($defaultOptions,$passedOptions);
		
		$currentOptions[2] = $currentOptions[2] ? $currentOptions[2] : null;
		$currentOptions[5] = $currentOptions[5] ? $currentOptions[5] : null;
		
		$return = $this->cacheObject->getAssoc(
					$currentOptions[0],$currentOptions[1],
					$currentOptions[2],$currentOptions[3],
					$currentOptions[4],$currentOptions[5]);
					
		if ($return == null)
			/*
			* Backwards compatible
			*/
			$return = false;
		
		return $return;
	}
	
	/**
	* Returns a single element from the cache
	*
	* A non-typed compatibility function
	* @param int $secs2cache,
	* @param string $sql=false,
	* @param array	$inputarr=false
	* @param string $serverKey
	*
	* @return string
	*/
	final public function cacheGetOne(
			$secs2cache,
			$sql = false,
			$inputarr=false,
			$serverKey=false) {

			
		$passedOptions  = func_get_args();
		$argCount 		= count($passedOptions);
		
		if ($argCount < 4)
		{
			array_unshift($passedOptions,$this->cacheSeconds);
		}	
		/*
		* The real typed arguments format for the method
		*/
		$defaultOptions = array(0,'',null,null);
		
		
		
		/*
		* Merge the 2 arrays
		*/
		$currentOptions = array_replace($defaultOptions,$passedOptions);
		
		$currentOptions[2] = $currentOptions[2] ? $currentOptions[2] : null;
		$currentOptions[3] = $currentOptions[3] ? $currentOptions[3] : null;
		
		$cacheOptions = $this->defaultCacheOptions;
		$cacheOptions['cachesecs'] = $passedOptions[0];
		$cacheOptions['serverkey'] = $passedOptions[3];
			
		$return = $this->cacheObject->getOne(
					$currentOptions[1],$currentOptions[2],$cacheOptions);
					
		if ($return == null)
			/*
			* Backwards compatible
			*/
			$return = false;
		
		return $return;
	
	}
	
	/**
	* Gets a cached column of data from the cache server
	*
	* A non typed compatibility option *
	*
	* @param int $secs2cache, 
	* @param string $sql,
	* @param ?array $inputarr=null.
	* @param bool $trim=false
	* @param string $serverKey
	*
	* @return array
	*/
	final public function cacheGetCol(
				$secs2cache, 
				$sql = false,
				$inputarr=false,
				$trim=false,
				$serverKey=false){
		
		$passedOptions  = func_get_args();
		$argCount 		= count($passedOptions);
		
		if ($argCount < 5)
		{
			array_unshift($passedOptions,$this->cacheSeconds);
		}
		
		/*
		* The real typed arguments format for the method
		*/
		$defaultOptions = array(0,'',null,false,null);
		
		/*
		* Merge the 2 arrays
		*/
		$currentOptions = array_replace($defaultOptions,$passedOptions);
		
		$currentOptions[2] = $currentOptions[2] ? $currentOptions[2] : null;
		$currentOptions[4] = $currentOptions[4] ? $currentOptions[4] : null;
			
		$return = $this->cacheObject->getCol(
					$currentOptions[0],$currentOptions[1],
					$currentOptions[2],$currentOptions[3],
					$currentOptions[4]);
					
		if ($return == null)
			/*
			* Backwards compatible
			*/
			$return = false;
		
		return $return;
	}
	
	/**
	* Gets all results from the cache
	*
	* A non typed compatibility option
	*
	* @param int $secs2cache,
	* @param string $sql,
	* @param ?array $inputarr=null
	* @param string $serverKey
	*
	* @return array
	*/
	final public function cacheGetAll(
				$secs2cache,
				$sql,
				$inputarr=false,
				$serverKey=false)  {
		
		$passedOptions  = func_get_args();
		$argCount 		= count($passedOptions);
		
		if ($argCount < 4)
		{
			array_unshift($passedOptions,$this->cacheSeconds);
		}	
		/*
		* The real typed arguments format for the method
		*/
		$defaultOptions = array(0,'',null,null);
		
		/*
		* Merge the 2 arrays
		*/
		$currentOptions = array_replace($defaultOptions,$passedOptions);
		
		$currentOptions[2] = $currentOptions[2] ? $currentOptions[2] : null;
		$currentOptions[3] = $currentOptions[3] ? $currentOptions[3] : null;
		
		$return = $this->cacheObject->getAll(
					$currentOptions[0],$currentOptions[1],
					$currentOptions[2],$currentOptions[3]);
					
		if ($return == null)
			/*
			* Backwards compatible
			*/
			$return = false;
		
		return $return;
	}

	/**
	* Gets all results from the cache
	*
	* A non typed compatibility option
	*
	* @param int $secs2cache,
	* @param string $sql,
	* @param ?array $inputarr=null
	* @param string $serverKey
	*
	* @return array
	*/
	final public function cacheGetArray(
				$secs2cache,
				$sql=false,
				$inputarr=false,
				$serverKey=false) {

		$passedOptions  = func_get_args();
		$argCount 		= count($passedOptions);
		
		if ($argCount < 4)
		{
			array_unshift($passedOptions,$this->cacheSeconds);
		}
			
		/*
		* The real typed arguments format for the method
		*/
		$defaultOptions = array(0,'',null,null);
		
		/*
		* Merge the 2 arrays
		*/
		$currentOptions = array_replace($defaultOptions,$passedOptions);
		
		$currentOptions[2] = $currentOptions[2] ? $currentOptions[2] : null;
		$currentOptions[3] = $currentOptions[3] ? $currentOptions[3] : null;
			
		$return = $this->cacheObject->getArray(
					$currentOptions[0],$currentOptions[1],
					$currentOptions[2],$currentOptions[3]);
					
		if ($return == null)
			/*
			* Backwards compatible
			*/
			$return = false;
		
		return $return;
	}
	
	/**
	* Returns a single cached row of data
	*
	* A non-typed compatibility option 
	*
	* @param int $secs2cache,
	* @param string $sql,
	* @param ?array $inputarr=null
	* @param string $serverKey
	*
	* @return array
	*/
	final public function cacheGetRow(
				$secs2cache,
				$sql=false,
				$inputarr=false,
				$serverKey=false) : ?array {
					
		$passedOptions  = func_get_args();
		$argCount 		= count($passedOptions);
		
		if ($argCount < 4)
		{
			array_unshift($passedOptions,$this->cacheSeconds);
			
		}
		/*
		* The real typed arguments format for the method
		*/
		$defaultOptions = array(0,'',null,null);
		
		/*
		* Merge the 2 arrays
		*/
		$currentOptions = array_replace($defaultOptions,$passedOptions);
		
		$currentOptions[2] = $currentOptions[2] ? $currentOptions[2] : null;
		$currentOptions[3] = $currentOptions[3] ? $currentOptions[3] : null;
			
		$return = $this->cacheObject->getRow(
					$currentOptions[0],$currentOptions[1],
					$currentOptions[2],$currentOptions[3]);
					
		if ($return == null)
			/*
			* Backwards compatible
			*/
			$return = false;
		
		return $return;
	}
	
	/**
	* Flush cached recordsets that match a particular $sql statement.
	* If $sql == false, then we purge all files in the cache.
	*
	* A non-typed compatibility option
	*
	* @param ?string $sql=null,
	* @param ?array $inputarr=null
	* @param string $serverKey
	* 
	* return void
	*/
	final public function cacheFlush(
			$sql=false,
			$inputarr=false,
			$serverKey=false){
		
		$passedOptions  = func_get_args();
		$argCount 		= count($passedOptions);
		
			
		/*
		* The real typed arguments format for the method
		*/
		$defaultOptions = array(null,null,null);
		
		/*
		* Merge the 2 arrays
		*/
		$currentOptions = array_replace($defaultOptions,$passedOptions);
		
		$currentOptions[1] = $currentOptions[1] ? $currentOptions[1] : null;
		$currentOptions[2] = $currentOptions[2] ? $currentOptions[2] : null;
			
		
		$this->cacheObject->cacheFlush(
					$currentOptions[0],$currentOptions[1],
					$currentOptions[2]);
					
				
	}

	/**
	* Execute SQL, caching recordsets.
	*
	* @param int 	$secs2cache	seconds to cache data, set to 0 to force query.
	* @param string $sql		SQL statement to execute
	* @param ?array [inputarr]	holds the input data  to bind to
	* @param string $serverKey
	*
	* @return		RecordSet or null
	*/
	final public function cacheExecute(
			int $secs2cache,
			string $sql,
			?array $inputarr=null,
			?string $serverKey=null) : ?object {

		return $this->cacheObject->execute($secs2cache,$sql,$inputarr,$serverKey);
		
	}
	
	/**
	* Will select, getting rows from $offset (1-based), for $nrows.
	*
	* @param int 		$secs2cache	seconds to cache data, set to 0 to force query.
	* @param string	$sql
	* @param int 		$offset	is the row to start calculations from (1-based)
	* @param int 		nrows	is the number of rows to get
	* @param inputarr	array of bind variables
	* @param string $serverKey
	*
	* @return		the recordset ($rs->databaseType == 'array')
	*/
	final public function cacheSelectLimit(
			int $secs2cache,
			string $sql,
			int $nrows=-1,
			int $offset=-1,
			?array $inputarr=null,
			?string $serverKey=null): ?object {
		
		return $this->cacheObject->cacheSelectLimit($secs2cache,$sql,$nrows,$offset,$inputarr,$serverKey);
	}

} // end class ADOConnection

