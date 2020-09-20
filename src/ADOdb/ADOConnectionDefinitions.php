<?php
/**
* The main connection definitions for ADOdb sessions system
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb;

final class ADOConnectionDefinitions
{
	/*
	* Value must be one of the supported V6 options
	*/
	public string $driver;
	
	/*
	* Defines the debugging level
	*/
	public int $debug = 0;
	
	/*
	* Defines the fetch mode - see ADODB_FETCH_MODE
	*/
	public int $fetchMode = 0;
	
	/*
	* Defines the record insertion force mode - ADODB_FORCE_TYPE
	*/
	public int $forceType = 3;
	
	/*
	* Defines the casing of associative arrays - see ADODB_ASSOC_CASE
	*/
	public int $assocCase = 0;
	
	/*
	* Defines the metatype for unknown types
	*/
	public string $defaultMetaType = 'N';
	
	/*
	* Defines the ActualType for unknown types
	*/
	public string $defaultActualType = 'VARCHAR';
	
	/*
	* Defines the connection parameters. If left false,
	* pass to connect(). Else use an array that signifies
	* connection, eg [database] [user] [password]
	*/
	public ?object $connection = null;
	
	/*
	* Set this to skip various driver loaded and valid 
	*/
	public bool $assumeValidDriver = false;
	
	
	/*
	* Defines how getOne EOF is presented - see ADODB_GETONE_EOF
	*/
	public $getOneEOF = false;
	
	/*
	* Signifies if we want to count records - see COUNTRECS
	*/
	public bool $countRecords = true;
	
	/*
	* The language set to use for errors. defaults to US English
	*/
	public string $language = 'enUS';
	
	/*
	* Quotes field names on update statements - see ADODB_QUOTE_FIELDNAMES
	* options - false, UPPER,LOWER,NATIVE
	*/
	public bool $quoteFieldNames = false;
	
	/*
	* Tells the driver to auto-activate the transaction handling feature
	*/
	public bool $activateTransactionHandling = true;
	
	/*
	* Parameters that are executed prior to the oem connect
	*/
	public array $connectionParameters = array();
	
	/*
	* Attaches the monolog stream handler
	*/
	public ?array $streamHandler = null;
	
	/*
	* Parameters that are executed with the oem connect,
	* if supported. Must be defined as ADOOemFlags Class
	*/
	public ?object $oemFlags = null;
	
	/*
	* 2d binding must be deliberately activated here
	* if you want to use it
	*/
	public bool $bulkBind = false;
	
	/*
	* If using Paging, set false to make the page count limitless
	*/
	public bool $pageExecuteCountRows = true;
	
	/*
	* Suppresses the extended attributes returned from
	* metaIndexes so that only the orginal legacy
	* version is returned
	*/
	public bool $suppressExtendedMetaIndexes = false;
	
	/*
	* DSN String if provided
	*/
	public ?string  $dsnParameters = null;
	
	/*
	* Associative array of database parameters if used. can replace connect
	* parameters
	*/
	public ?array	$dbParameters = null;
	
	/*
	* Placeholders
	*/
	public ?object $loggingDefinitions  = null;
	public ?object $dateTimeDefinitions = null;
	public ?object $cacheDefinitions    = null;
	
	/**
	* Constructor loads in the logging and date/time
	*
	* @param object $streamHandlers
	* @param object $dateTimeDefinitions
	* @param object $cacheDefinitions
	*
	*/
	public function __construct(
				?array $streamHandlers = null,
				?object $dateTimeDefinitions = null,
				?object $cacheDefinitions = null) {
		/*
		* Load some sane defaults into the system,
		* or load your own
		*/
		$this->loggingDefinitions = new \ADOdb\common\ADOLoggingDefinitions;
		
		if ($streamHandlers)
			$this->loggingDefinitions->streamHandlers = $streamHandlers;
		
		/*
		* append the date time definitions into connection
		*/
		if ($dateTimeDefinitions)
			$this->dateTimeDefinitions = $dateTimeDefinitions;
		else
			$this->dateTimeDefinitions = new \ADOdb\time\ADODateTimeDefinitions;
		
		if ($cacheDefinitions)
			$this->cacheDefinitions = $cacheDefinitions;
		
		
		
	}
	
}