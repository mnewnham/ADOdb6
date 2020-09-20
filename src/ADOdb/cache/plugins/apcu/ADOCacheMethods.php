<?php
/**
* Methods associated with caching recordsets using the apcu server
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\cache\plugins\apcu;
use Monolog\Logger;

final class ADOCacheMethods extends \ADOdb\cache\ADOCacheMethods
{
	
	/*
	* An integer index into the libraries
	*/
	const ACPU  = 1;
	
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
	final protected function connect() : bool 
	{
		/*
		*	do we have memcache or memcached?
		*/
		$apcu = function_exists('apcu_enabled') && apcu_enabled();
		if ($apcu) {
			$this->library		= 'ACPU';
			$this->libraryFlag 	= 1;
			
			
			
			if ($this->debug)
			{
				$message = 'ACPU: Loaded the class libary';
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}

		} else {
			$message = 'APCU: The APCU library was not found';
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
			return false;
		}

		if (!is_array($this->hosts)) 
			$this->hosts = array($this->hosts);

		/*
		* Get the server list, create a connection group
		*/
		
		$failcnt = 0;
		$failTarget = 0;
		/*
		* If we are using memcached, and the memCacheControllers item
		* is defined, we are going to use the addservers command,
		* otherwise we will iterate over the array
		*/
	
		/*
		* Global flag
		*/
		$this->_connected = true;
		
		/*
		* Memcache options are only supported with memcached
		* we ignore them for memcache
		*/
		if (is_array($this->memCacheOptions) && $this->libraryFlag == self::MCLIBD)
		{
			foreach($this->memCacheOptions as $k=>$v)
			{
				if (!@$apcu->setOption($k,$v)) {
					$message = sprintf('APCU: Failed Setting memcache option %s to %s',$k,$v);
					$this->connection->loggingObject->log(Logger::CRITICAL,$message);
				} else if ($this->debug) {
					$message = sprintf('APCU: Successfully set memcache option %s to %s',	$k,$v);
					$this->connection->loggingObject->log(Logger::DEBUG,$message);
				}
			}
		} else if (is_array($this->memCacheOptions) && $this->libraryFlag == self::MCLIB
		    && $this->debug) {
			$message = 'APCU: Defined memcache options are ignored for the Memcache library';
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}
		
		
		/*
		* The memcache connection object
		*/
		$this->cacheLibrary = new \stdClass; 
		
		return true;
	}
	
	/**
	* Flushes all entries from memcache
	*
	* @return
	*/
	final protected	function flushall() : void
	{
		if (!$this->_connected) {
			if (!$this->connect())
				return;
		}
		
		if (!$this->cacheLibrary) 
			return;

		apcu_clear_cache();
		
		if ($this->debug) {
			$message = 'APCU: flushall in APCU service succeeded';
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}
	}
	
	/**
	* Flush an individual query from memcache
	*
	* @param string $filename The md5 of the query
	*
	* @return void
	*/
	final protected function flushcache(
					string $filename,
					?string $serverKey=null) : void {
					
		if (!$this->_connected) {
			$this->connect();
		}
		
		if (!$this->cacheLibrary) 
			return;

		apcu_delete($filename);

		if ($this->debug)
		{
			$message = "APCU: $filename entry flushed from memcache server";
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}
		return;
	}
	
	/**
	* Tries to return a recordset from the cache
	*
	* @param string $filename the md5 code of the request
	* @param int $secs2cache
	*
	* @return recordset
	*/
	final protected	function readcache(
				string $filename, 
				int $secs2cache,
				?string $serverKey) :?object	{
			
		if (!$this->_connected) 
			$this->connect($err);
		
		if (!$this->cacheLibrary) 
			return null;

		$success = false;
		
		//print_r(apcu_cache_info ());
		$rs = apcu_fetch($filename,$success);
		
		//print "\nSUCCESS $filename $rs"; exit;
		
		if (!$rs) {
			$message = sprintf('APCU: Item with key %s doesn\'t exist in the APCU cache', $filename);
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
			return null;
		} else if ($this->debug) {
			$message = sprintf('APCU: Item with key %s retrieved from the APCU cache', $filename);
	
			if ($serverKey)	{
				$message .= sprintf(' [server key %s was ignored]',$serverKey);
			}
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}
		
		// hack, should actually use _csv2rs
		$rs = explode("\n", $rs);
		unset($rs[0]);
		$rs = join("\n", $rs);
		$rs = unserialize($rs);
		
		if (! is_object($rs)) {
			$message = 'APCU: Unable to unserialize $rs';
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
			return null;
		}
		if ($rs->timeCreated == 0)
		{	
			if ($this->debug)
			{
				$message = 'APCU: Time on recordset is zero';
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}
			return $rs; // apparently have been reports that timeCreated was set to 0 somewhere
		}
		
		$tdiff = intval($rs->timeCreated + $secs2cache - time());
		if ($tdiff <= 2) {
			switch($tdiff) {
				case 2:
				if ((rand() & 15) == 0) {
					$message = "APCU: Timeout 2";
					$this->connection->loggingObject->log(Logger::CRITICAL,$message);
					return null;
				}
				break;
				case 1:
				if ((rand() & 3) == 0) {
					$message = "APCU: Timeout 1";
					$this->connection->loggingObject->log(Logger::CRITICAL,$message);
				}
				break;
				default:
				$message = "APCU: Timeout 0";
				$this->connection->loggingObject->log(Logger::CRITICAL,$message);
				return null;
			}
		}
		return $rs;
	}
	
	/**
	* Builds a cached data set
	*
	* @param string $filename
	* @param string $contents
	* @param int $secs2cache
	*
	* @return bool
	*/
	final protected	function writecache(
			string $filename, 
			string $contents, 
			int $secs2cache,
			?string $serverKey) : bool {
				
		if (!$this->_connected)
			$this->connect();
		
		if (!$this->cacheLibrary) 
			/*
			* No object available
			*/
			return false;

		$success = apcu_add ( $filename , $contents ,$secs2cache );
		
		if (!$success) {
			
			$message = 'APCU: Failed to save data in the APCU cache';
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
			return false;
		
		} else if ($this->debug) {
			
			$message = sprintf('APCU: Successfully wrote query contents on key %s to APCU cache',$filename);
			
			if ($serverKey)
				$message .= sprintf(' [server key %s was ignored]',$serverKey);
			
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}	

		return true;
	}
}
