<?php
/**
* Methods associated with caching recordsets
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\cache;
use Monolog\Logger;

abstract class ADOCacheMethods
{

	public $connection;
	public $cacheDefinitions;
	
	/*
	* Different debugging to the connection
	*/
	public bool $debug = false;
	
	/*
	* An indicator of which library we are using
	*/
	protected int $libraryFlag = 0;

	/*
	* $library will be populated with the proper library on connect
	* and is used later when there are differences in specific calls
	* between memcache and memcached
	*/
	protected ?string $library = null;

	/*
	* array of hosts, if not overrided by controllers
	*/
	protected ?array $hosts = null;	
	
	/*
	* port, if not overrided by controllers
	*/
	protected ?int $port = 11211;
	
	/*
	* memcache compression with zlib
	*/
	protected bool $compress = false;
	
	/*
	* Has a connection been established
	*/
	public bool $_connected = false;
	
	/*
	* Holds the instance of the library we will use
	*/
	protected ?object $cacheLibrary = null;
	
	protected string $databaseType;
	protected string $database;
	protected string $user = 'adodb';

	protected int $numCacheHits = 0;
	protected int $numCacheMisses = 0;
	
	/*
	* array of connection parameters, if used
	*/
	protected ?array $memCacheControllers = null;
	
	/*
	* Array of memcached options, if used
	*/
	protected ?array $memCacheOptions = null;
	
	/*
	* Default cache timeout
	*/
	public int $cacheSeconds = 3600;
	
	/*
	* A default cache options template
	*/
	protected array $defaultCacheOptions = array(
			'cachesecs'=>0,
			'serverkey'=>null,
			'offline'=>false
			);
	
	function __construct($connection)
	{
		$this->connection    = $connection;
		
		$cacheDefinitions = $connection->connectionDefinitions->cacheDefinitions;
	
		$this->cacheSeconds = $cacheDefinitions->cacheSeconds;
	
		$this->hosts 	= $cacheDefinitions->memCacheHost;
		$this->port 	= $cacheDefinitions->memCachePort;
		$this->compress = $cacheDefinitions->memCacheCompress;
		
		$this->memCacheControllers = $cacheDefinitions->memCacheControllers;
		$this->memCacheOptions     = $cacheDefinitions->memCacheOptions;
		
		$this->databaseType = $connection->connectionDefinitions->driver;
		$this->database     = $connection->database;
		
		$this->debug = $cacheDefinitions->debug;
		
		$this->cacheSeconds = $cacheDefinitions->cacheSeconds;
		$this->defaultCacheOptions['cachesecs'] = $this->cacheSeconds;

		
		/*
		* Startup the client connection
		*/
		$this->connect();
		
		/*
		* We do this just to bring the ADORecordSetArray class array into the
		* class space. We're not going to use it. We get an incomplete class
		* error reading a cached set if we don't. This is a special usage of
		* the class without a query id
		*/
		$rsClass = $this->connection->driverPath . 'ADORecordSetArray';
		$classTemplate = new $rsClass(null,$this->connection);

	}

	/**
	* Connect to one of the available 
	* 
	* @return bool
	*/
	abstract protected function connect(): bool;
	
	
	/**
	* Builds a cached data set
	*
	* @param string $filename
	* @param string $contents
	* @param int $secs2cache
	*
	* @return bool
	*/
	abstract protected	function writecache(
			string $filename, 
			string $contents, 
			int $secs2cache,
			?string $serverKey) : bool;
				
		
	/**
	* Tries to return a recordset from the cache
	*
	* @param string $filename the md5 code of the request
	* @param int $secs2cache
	*
	* @return recordset
	*/
	abstract protected	function readcache(
				string $filename, 
				int $secs2cache,
				?string $serverKey) :?object;
			
	

	/**
	* Flushes all entries from library
	*
	* @return void
	*/
	abstract protected	function flushall() : void;
	

	/**
	* Flush an individual query from memcache
	*
	* @param string $filename The md5 of the query
	*
	* @return void
	*/
	abstract protected function flushcache(
					string $filename,
					?string $serverKey=null) : void;
					
		

	/**************************************************************************
	* Public methods
	**************************************************************************/
		
		
		
	/**
	* Will select the supplied $page number from a recordset, given that it is paginated in pages of
	* $nrows rows per page. It also saves two boolean values saying if the given page is the first
	* and/or last one of the recordset. Added by Iván Oliva to provide recordset pagination.
	*
	* @param secs2cache	seconds to cache data, set to 0 to force query
	* @param sql
	* @param nrows		is the number of rows per page to get
	* @param page		is the page number to get (1-based)
	* @param [inputarr]	array of bind variables
	* @return		the recordset ($rs->databaseType == 'array')
	*/
	final public function pageExecute(
				int $secs2cache, 
				string $sql, 
				int $nrows, 
				int $page,
				?array $inputarr=null,
				?string $serverKey=null) : ?object {
		
		/*switch($this->dataProvider) {
		case 'postgres':
		case 'mysql':
			break;
		default: $secs2cache = 0; break;
		}*/
		$rs = $this->connection->pageExecute($sql,$nrows,$page,$inputarr,$secs2cache,$serverKey);
		return $rs;
	}
	
	/**
	* Get an associative array from the cache
	*
	* @param string $sql, 
	* @param ?array $inputarr=null,
	* @param bool $force_array = false, 
	* @param bool $first2cols = false
	* @param ?array $currentCacheOptions
	* 
	* @return ?array
	*/
	final public function getAssoc(
				?string $sql, 
				?array $inputarr=null,
				bool $force_array=false, 
				bool $first2cols=false,
				?array $currentCacheOptions=null) : ?array {
					
		$rs = $this->execute($sql, $inputarr,$currentCacheOptions);
		
		if (!$rs) {
			return null;
		}
		$arr = $rs->getAssoc($force_array,$first2cols);
		return $arr;
	}
	
	/**
	* Returns a single element from the cache
	*
	* @param string $sql
	* @param array	$inputarr=false
	* @param ?array $currentCacheOptions
	*
	* @return string
	*/
	final public function getOne(
				?string $sql,
				?array $inputarr=null,
				?array $currentCacheOptions=null) : ?string {

		$ret = null;
		
		//$this->connection->disableCountRecords = true;

		$rs = $this->execute($sql,$inputarr,$currentCacheOptions);
		if ($rs) {
			if ($rs->EOF) {
				$ret = $this->getOneEOF;
			} else {
				$ret = reset($rs->fields);
			}
			$rs->close();
		}
		
		//$this->connection->disableCountRecords = false;

		return $ret;
	}
	
	/**
	* Gets a cached column of data from memcache
	*
	* @param string $sql
	* @param ?array $inputarr=null.
	* @param bool $trim=false
	* @param ?array $currentCacheOptions
	*
	* @return array
	*/
	final public function getCol(
				?string $sql=null,
				?array $inputarr=null,
				bool $trim=false,
				?array $currentCacheOptions=null) : ?array {
		
		$rs = $this->execute($sql, $inputarr,$currentCacheOptions);
		
		if ($rs) {
			$rv = array();
			if ($trim) {
				while (!$rs->EOF) {
					$rv[] = trim(reset($rs->fields));
					$rs->MoveNext();
				}
			} else {
				while (!$rs->EOF) {
					$rv[] = reset($rs->fields);
					$rs->MoveNext();
				}
			}
			$rs->Close();
		} else
			$rv = null;

		return $rv;
	}
	
	/**
	* Gets all results from the cache
	*
	* @param int $secs2cache,
	* @param string $sql,
	* @param ?array $inputarr=null
	*
	* @return array
	*/
	final public function getAll(
				int $secs2cache,
				?string $sql=null,
				?array $inputarr=null,
				?string $serverKey=null) : array {
					
		return $this->getArray($secs2cache,$sql,$inputarr,$serverKey);
		
	}

	/**
	* Gets all results from the cache
	*
	* @param int $secs2cache,
	* @param string $sql,
	* @param ?array $inputarr=null
	*
	* @return array
	*/
	final public function getArray(
				string $sql,
				?array $inputarr=null,
				?array $cacheOptions=null) : ?array {
		
		$this->connection->countRecords = false;

		$rs = $this->execute($sql,$inputarr,$cacheOptions);

		$this->connection->countRecords = $this->connection->coreCountRecords;

		if (!$rs)
			return null;
		
		$arr = $rs->getArray();
		$rs->Close();
		return $arr;
	}
	
	/**
	* Returns a single cached row of data
	*
	* @param string $sql,
	* @param ?array $inputarr=null
	* @param ?array $cacheOptions=null
	*
	* @return array
	*/
	final public function getRow(
			?string $sql=null,
			?array $inputarr=null,
			?array $cacheOptions=null) : ?array {
				
		$rs = $this->execute($sql,$inputarr,$cacheOptions);
		
		if ($rs) {
			if (!$rs->EOF) {
				$arr = $rs->fields;
			} else {
				$arr = array();
			}

			$rs->close();
			return $arr;
		}
		
		return null;
	}
	
	/**
	* Flush cached recordsets that match a particular $sql statement.
	* If $sql == false, then we purge all files in the cache.
	*
	* @param ?string $sql=null,
	* @param ?array $inputarr=null
	* 
	*/
	final public function cacheFlush(
				?string $sql=null,
				?array $inputarr=null,
				?string $serverKey=null) : void {

		if (!$sql) {
			$this->flushall();
			return;
		}

		$f = $this->generateCacheName($sql.serialize($inputarr),false);
		
		$this->flushcache($f,$serverKey);
	}

	/**
	* Execute SQL, caching recordsets.
	*
	* @param string $sql					SQL statement to execute
	* @param ?array [$inputarr]				holds the input data  to bind to
	* @param ?array	[$currentCacheOptions]	standard cacheoptions
	
	* @return		RecordSet or null
	*/
	final public function execute(
					string $sql,
					?array $inputarr=null,
					?array $currentCacheOptions=null) : ?object {

		
		if ($currentCacheOptions == null)
			$currentCacheOptions = $this->defaultCacheOptions;
		
		$secs2cache = $currentCacheOptions['cachesecs'];
		$serverKey  = $currentCacheOptions['serverkey'];

		$md5file = $this->generateCacheName($sql.serialize($inputarr),true);
		$err = '';

		if ($secs2cache > 0){
			$rs = $this->readcache($md5file,$secs2cache,$serverKey);
			$this->numCacheHits += 1;
		} else {
			$err='Timeout 1';
			$rs = false;
			$this->numCacheMisses += 1;
		}

		if (!$rs) {
			/*
			* no cached rs found, set up a normal query to get a new
			* one to cache
			*/
			if ($this->debug) {
				$message = "CACHE: $md5file cache failure: $err (this is a notice and not an error";
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}

			$result = $this->connection->execute($sql,$inputarr);
			
			if ($result) {
			
				$eof = $result->EOF;
				$rs = $this->connection->_rs2rs($result); // read entire recordset into memory immediately
				$rs->timeCreated = time();
				$txt = $this->_rs2serialize($rs,false,$sql); // serialize

				$ok = $this->writecache($md5file,$txt, $secs2cache,$serverKey);

				if (!$ok) {

					if ($ok === false) {

						$em = 'Cache write error';
						$en = -32000;
						$message = 'CACHE: ' . $em;
						$this->connection->loggingObject->log(Logger::CRITICAL,$message);

					} else {
						
						$message = 'CACHE: Cache file locked warning';
						$en = -32001;

					}

					if ($this->debug) {
						
						$this->connection->loggingObject->log(Logger::DEBUG,$message);
					}
				}
				if ($rs->EOF && !$eof) {
					$rs->MoveFirst();
				}
			}
		} else {
			$this->_errorMsg = '';
			$this->_errorCode = 0;

			if ($this->connection->fnCacheExecute) {
				$fn = $this->connection->fnCacheExecute;
				$fn($this, $secs2cache, $sql, $inputarr);
			}
			// ok, set cached object found
			//$rs->connection = $this; // Pablo suggestion
			if ($this->debug){
				if ($this->connection->debug == 99) {
					$debugger = new \ADOdb\database\debug\debugger($this->connection);
					$debugger->doBacktrace();

				}
				$ttl = $rs->timeCreated + $secs2cache - time();
				$s = is_array($sql) ? $sql[0] : $sql;
				
				$message = "CACHE: $md5file reloaded, ttl=$ttl [ $s ]";
				$this->connection->loggingObject->log(Logger::DEBUG,$message);

			}
		}
		return $rs;
	}
	
	/**
	 * Will select, getting rows from $offset (1-based), for $nrows.
	 * This simulates the MySQL "select * from table limit $offset,$nrows" , and
	 * the PostgreSQL "select * from table limit $nrows offset $offset". Note that
	 * MySQL and PostgreSQL parameter ordering is the opposite of the other.
	 * eg.
	 *  CacheSelectLimit(15,'select * from table',3); will return rows 1 to 3 (1-based)
	 *  CacheSelectLimit(15,'select * from table',3,2); will return rows 3 to 5 (1-based)
	 *
	 * BUG: Currently CacheSelectLimit fails with $sql with LIMIT or TOP clause already set
	 *
	 * @param [secs2cache]	seconds to cache data, set to 0 to force query. This is optional
	 * @param sql
	 * @param [offset]	is the row to start calculations from (1-based)
	 * @param [nrows]	is the number of rows to get
	 * @param [inputarr]	array of bind variables
	 * @return		the recordset ($rs->databaseType == 'array')
	 */
	final public function selectLimit(
					
					int $secs2cache,
					string $sql,
					int $nrows=-1,
					int $offset=-1,
					?array $inputarr=null,
					?string $serverKey=null) : object {
						
		$rs = $this->connection->selectLimit($sql,$nrows,$offset,$inputarr,$secs2cache,$serverKey);
		return $rs;
	}

	
	/**
	 * generates md5 key for caching.
	 * Filename is generated based on:
	 *
	 *  - sql statement
	 *  - database type (oci8, ibase, ifx, etc)
	 *  - database name
	 *  - userid
	 *  - setFetchMode
	 *
	 * @param string $sql the sql statement
	 *
	 * @return string
	 */
	final protected function generateCacheName(string $sql) : string {

		$mode = $this->connection->connectionDefinitions->fetchMode;
		
		return md5($sql.$this->databaseType.$this->database.$this->user.$mode);
		
	}
	
	
	/**
 	 * convert a recordset into special format
	 *
	 * @param rs	the recordset
	 *
	 * @return	the CSV formatted data
	 */
	final protected function _rs2serialize(
				object &$rs,
				$conn=false,
				$sql='') : string 	{
					
		$max = ($rs) ? $rs->FieldCount() : 0;

		if ($sql) $sql = urlencode($sql);
		// metadata setup

		if ($max <= 0 || $rs->dataProvider == 'empty') { // is insert/update/delete
			if (is_object($conn)) {
				$sql .= ','.$conn->Affected_Rows();
				$sql .= ','.$conn->Insert_ID();
			} else
				$sql .= ',,';

			$text = "====-1,0,$sql\n";
			return $text;
		}
		$tt = $rs->timeCreated;
		$tt = $tt ? $tt : time();

		## changed format from ====0 to ====1
		$line = "====1,$tt,$sql\n";

		if ($rs->databaseType == 'array') {
			$rows = $rs->_array;
		} else {
			$rows = array();
			while (!$rs->EOF) {
				$rows[] = $rs->fields;
				//print_r($rs->fields);
				$rs->MoveNext();
			}
		}
		for($i=0; $i < $max; $i++) {
			$o = $rs->FetchField($i);
			$flds[] = $o;
		}

		$savefetch = isset($rs->adodbFetchMode) ? $rs->adodbFetchMode : $rs->fetchMode;
		$class = '\\' . $this->connection->driverPath . 'ADORecordSetArray';
		//$class = $this->connection->arrayClass;
		$rs2 = new $class($rs,$this->connection);
		$rs2->timeCreated = $rs->timeCreated; # memcache fix
		
		$rs2->sql = $rs->sql;
		$rs2->oldProvider = $rs->dataProvider;
		$rs2->initArrayFields($rows,$flds);
		$rs2->fetchMode = $savefetch;
		return $line.serialize($rs2);
	}
}



