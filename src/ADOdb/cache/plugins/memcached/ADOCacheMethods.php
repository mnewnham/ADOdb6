<?php
/**
* Methods associated with caching recordsets using the memcached server
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\cache\plugins\memcached;
use Monolog\Logger;

final class ADOCacheMethods extends \ADOdb\cache\ADOCacheMethods
{
	
	function __construct($connection)
	{
		$this->connection    = $connection;
		
		$cacheDefinitions = $connection->connectionDefinitions->cacheDefinitions;
	
		$this->cacheSeconds = $cacheDefinitions->cacheSeconds;
	
		$this->hosts 	= $cacheDefinitions->memCacheHost;
		$this->port 	= $cacheDefinitions->memCachePort;
		
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
		* Is memcached loaded?
		*/
		if (class_exists('Memcached')) {
			$this->library		= 'Memcached';
			
			$memcache = new \MemCached;
			if ($this->debug)
			{
				$message = 'MEMCACHED : Loaded the MemCached Libary';
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}
		} else {
			$message = 'MEMCACHED: Memcached extension not found!';
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
		if (is_array($this->memCacheControllers))
		{
			$failTarget = count($this->memCacheControllers);
			if (!@$memcache->addServers($this->memCacheControllers))
			{
				$message = 'MEMCACHED: Could not create weighted controller group';
				$this->connection->loggingObject->log(Logger::CRITICAL,$message);
				return false;
			}
		
		} else {
			
			$failTarget = count($this->hosts);
			
			foreach($this->hosts as $host) {
				if (!@$memcache->addServer($host,$this->port)) {
					$failcnt += 1;
					if ($this->debug) {
						$message = sprintf("MEMCACHED: Attempt to add server %s on port %s to available memcache connections failed",  $host, $this->port);
						$this->connection->loggingObject->log(Logger::DEBUG,$message);
					}
				} else if ($this->debug) {
					sprintf("MEMCACHED: Added server %s on port %s to available memcache connection",  $host, $this->port);
					$this->connection->loggingObject->log(Logger::DEBUG,$message);
				}
			}
		}
		if ($failcnt == $failTarget) {
			$message = 'MEMCACHED: Can\'t connect to any memcache server';
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
			return false;
		}
		/*
		* Global flag
		*/
		$this->_connected = true;
		
		/*
		* Memcache options are only supported with memcached
		* we ignore them for memcache
		*/
		if (is_array($this->memCacheOptions))
		{
			foreach($this->memCacheOptions as $k=>$v)
			{
				if (!@$memcache->setOption($k,$v)) {
					$message = sprintf('MEMCACHED: Failed Setting memcache option %s to %s',$k,$v);
					$this->connection->loggingObject->log(Logger::CRITICAL,$message);
				} else if ($this->debug) {
					$message = sprintf('MEMCACHED: Successfully set memcache option %s to %s',	$k,$v);
					$this->connection->loggingObject->log(Logger::DEBUG,$message);
				}
			}
		} else if (is_array($this->memCacheOptions) && $this->debug) {
			$message = 'MEMCACHED: Defined memcache options are ignored for the Memcache library';
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}
		
		
		/*
		* The memcache connection object
		*/
		$this->cacheLibrary = $memcache;
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

		$del = $this->cacheLibrary->flush();

		if (!$del) {
			$message = 'MEMCACHED: flushall in MemCache service failed';
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
		} else if ($this->debug) {
			$message = 'MEMCACHED: flushall in MemCache service succeeded';
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

		if ($serverKey)
			$del = $this->deleteByKey($serverKey,$filename);
		else
			$del = $this->cacheLibrary->delete($filename);

		if ($this->debug)
		{
			if (!$del) 
				$message = "MEMCACHED: $filename entry doesn't exist on memcache server";
			else 
				$message = "MEMCACHED: $filename entry flushed from memcache server";
			
			if ($serverKey)
			{
				$message .= sprintf(' [using server key %s ]', $serverKey);
			}
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

		if ($serverKey) {
			$rs = $this->cacheLibrary->getByKey($serverKey,$filename);
		} else {
			$rs = $this->cacheLibrary->get($filename);
		}
		
		if (!$rs) {
			$message = sprintf('MEMCACHED: Item with key %s doesn\'t exist on the memcache server.', $filename);
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
			return null;
		} else if ($this->debug) {
			$message = sprintf('MEMCACHED: Item with key %s retrieved from the memcache server.', $filename);
	
			if ($serverKey)	{
				$message .= sprintf(' [using server key %s]',$serverKey);
			}
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}
		
		// hack, should actually use _csv2rs
		$rs = explode("\n", $rs);
		unset($rs[0]);
		$rs = join("\n", $rs);
		$rs = unserialize($rs);
		
		if (! is_object($rs)) {
			$message = 'MEMCACHED: Unable to unserialize $rs';
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
			return null;
		}
		if ($rs->timeCreated == 0)
		{	
			if ($this->debug)
			{
				$message = 'MEMCACHED: Time on recordset is zero';
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}
			return $rs; // apparently have been reports that timeCreated was set to 0 somewhere
		}
		
		$tdiff = intval($rs->timeCreated + $secs2cache - time());
		if ($tdiff <= 2) {
			switch($tdiff) {
				case 2:
				if ((rand() & 15) == 0) {
					$message = "MEMCACHED: Timeout 2";
					$this->connection->loggingObject->log(Logger::CRITICAL,$message);
					return null;
				}
				break;
				case 1:
				if ((rand() & 3) == 0) {
					$message = "MEMCACHED: Timeout 1";
					$this->connection->loggingObject->log(Logger::CRITICAL,$message);
				}
				break;
				default:
				$message = "MEMCACHED: Timeout 0";
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

		$failed=false;
		/*
		* Linux connection module
		*/
		if ($serverKey) {
			if (!$this->cacheLibrary->setByKey($serverKey,$filename, $contents, $secs2cache)){
				$failed = true;
			}
		} else {
			if (!$this->cacheLibrary->set($filename, $contents, $secs2cache)) {
				$failed=true;
			}
		}
		
		if($failed) {
			$message = 'MEMCACHED: Failed to save data at the memcache server';
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
			return false;
		} else if ($this->debug) {
			$message = sprintf('MEMCACHED: Successfully wrote query contents on key %s to memcache server',$filename);
			
			if ($serverKey)	{
				$message .= sprintf(' [using server key %s]',$serverKey);
			}
			
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}	

		return true;
	}

}
