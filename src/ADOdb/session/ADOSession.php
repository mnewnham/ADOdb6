<?php
/**
* Core session management functionality for the Sessions package
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADODb\session;
use Monolog\Logger;

class ADOSession implements \SessionHandlerInterface{

	
	protected ?string $sessionHasExpired = null;
	
	/*
	* The database connection
	*/
	protected ?object $connection = null;
	
	/*
	* The table in use
	*/
	protected ?string $tableName = null;
	
	protected ?string $selfName  = null;
	
	/*
	* The maximum number of retries to update the database
	* before we give up
	*/
	protected int $collisionRetries = 10;
	
	protected int $lifetime = 0;
	
	protected string $encryptionKey = 'CRYPTED ADODB SESSIONS ROCK!';
	
	protected string $binaryOption = '';
	
	/*
	* Whether we should optimaize the table (if supported)
	*/
	protected bool $optimizeTable = false;
	/*
	* The SQL statement that optimizes the table
	*/
	protected ?string $optimizeSql = null;

	/*
	* Holds CRC data on the record
	*/
	protected ?string $recordCRC = null;
	protected ?string $_clob = null;
	
	/*
	* Filters, such as compression or encryption, applied
	* to database read/write operations
	*/
	protected array $readWriteFilters = array();

	/*
	* Configuration for the session
	*/
	protected ?object $sessionDefinition = null;
	
	/*
	* Standalone debuggong for sessions
	*/
	protected int $debug = 0;
	
	
	/*
	* Defines the crypto method. Default none
	
	const CRYPTO_NONE 	= 0;
	const CRYPTO_MD5  	= 1;
	const CRYPTO_MCRYPT = 2;
	const CRYPTO_SHA1   = 3;
	const CRYPTO_SECRET = 4;
	*/
	
	protected $cryptoPluginClasses = array(
		'',
		'MD5Crypt',
		'MCrypt',
		'SHA1Crypt',
		'HordeSecret'
		);
		
	protected $compressionPluginClasses = array(
		'',
		'BZIP2Compress',
		'GZIPCompress'
		);
	
	/*
	* Loads a non-default serializarion method. Best value is
	* php_serialize which is better than the older ones because you
	* dont need a custom unserializer. This is the default
	* WDDX needs support added
	*/
	protected $serializarionMethods = array(
		'',
		'php',
		'php_binary',
		'php_serialize',
		'wddx'
		);
		
	final public function __construct(
			object $connection,
			object $sessionDefinition)	{
				
		$this->sessionDefinition = $sessionDefinition;
		
		if ($sessionDefinition->serializationMethod !== null)
		{
			$serHandler = (int)$sessionDefinition->serializationMethod;
			if ($serHandler > 0 && $serHandler < 5)
				ini_set('session.serialize_handler',$this->serializarionMethods[$serHandler]);
		}
		$this->connection = $connection;
		
		$selfName = get_class();
		
		$this->tableName = $sessionDefinition->tableName;

		$this->optimizeTable = $sessionDefinition->optimizeTable;
		
		/*
		* Load filters from the sessionDefinitions
		*/
		if ($sessionDefinition->cryptoMethod > 0)
		{
			/* 
			* PHP must have the correct crypto method available
			*/
			if (array_key_exists($sessionDefinition->cryptoMethod,$this->cryptoPluginClasses))
			{
				$plugin 	 = $this->cryptoPluginClasses[$sessionDefinition->cryptoMethod];
				$cryptoClass = '\\ADOdb\\session\\plugins\\' . $plugin;
				$this->readWriteFilters[] = new $cryptoClass($connection);
			}
		}
		
		if ($sessionDefinition->compressionMethod > 0)
		{
			/*
			* Compress the data per the scheme requested
			* Note that compression does not work if the session data column isnt
			* a blob fields
			*/
			if (array_key_exists($sessionDefinition->compressionMethod,$this->compressionPluginClasses))
			{
				$plugin 	 = $this->compressionPluginClasses[$sessionDefinition->compressionMethod];
				$compressionClass = '\\ADOdb\\session\\plugins\\' . $plugin;
				$this->readWriteFilters[] = new $compressionClass($connection);
			}
		}
		
		session_set_save_handler($this);
		
		$this->debug = $sessionDefinition->debug;
		
		if ($this->debug){
			$message = 'SESSION: Opening session on driver ' . $this->connection->connectionDefinitions->driver;
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}

	}
	
	/**
	* Just a stub for the session implementation
	*
	* @param string $savePath
	* @param string $sessionName
	*
	* @return bool always true
	*/
	final public function open($savePath, $sessionName) : bool {
		
		/*
		* Because we have already opened the database connection
		* just return success
		*/
		return true;
	}
	
	/**
	* Just a stub for the session implementation
	*
	* @return bool always true
	*/
	final public function close() : bool {
		
		/*
		* We do no shutdowns just return true;
		*/
		return true;
	}
	
	/**
	* Manual routine to regenerate the session id
	*
	* @return bool success
	*/
	final public function adodb_session_regenerate_id() : bool {
	

		$old_id = session_id();
		session_regenerate_id();
		
		$new_id = session_id();
		
		$p1 = $this->connection->param('p1');
		$p2 = $this->connection->param('p2');

		$bind = array('p1'=>$new_id,'p2'=>$old_id);
		
		$sql = sprintf('UPDATE %s SET sesskey=%s WHERE sesskey=%s',
				$this->tableName,$p1,$p2);
		
		$ok = false;
		$tries = 0;
		while (!$ok && $tries < $this->collisionRetries)
		{
			$ok = $this->connection->execute($sql,$bind);
			$tries++;
			
		}
		
		return $ok ? true : false;
	}

	
	/**
	* Overrides the previously set lifetime
	*
	* @param int 	$lifetime
	*
	* @return int
	*/
	final public function lifetime(int $lifetime = null) : int
	{
		
		if (!is_null($lifetime)) {
			$this->lifetime = (int) $lifetime;
			if ($this->debug)
			{
				$message = 'SESSION: set lifetime to ' . $lifetime;
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}
		} 
			
		if (!$this->lifetime) {
			$this->lifetime = ini_get('session.gc_maxlifetime');
			if ($this->lifetime <= 1) {
				$this->lifetime = 1440;
			}
			
			if ($this->debug)
			{
				$message = 'SESSION: set lifetime from default to ' . $lifetime;
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}
			
		}

		return $this->lifetime;
	}


	/**
	* 	use this function to create the encryption key for crypted sessions
	*	crypt the used key, ADODB_Session::encryptionKey() as key and session_id() as salt
	*
	* @return string
	*/
	public function _sessionKey():string {
		return crypt($this->encryptionKey, session_id());
	}
	
	protected function expireNotify($expire_notify = null)
	{

		if (!is_null($expire_notify)) {
			$this->sessionHasExpired = $expire_notify;
		}

		return $this->sessionHasExpired;
	}


	/**
	* Slurp in the session variables and return the serialized string
	* cannot type hint implemented class
	*
	* @param	string 	$key
	*
	* @return string
	*/
	final public function read($key) {
		
		$filter	= $this->readWriteFilters;
		
		$p0 = $this->connection->param(0);
		$bind = array($key);

		$sql = sprintf("SELECT %s FROM %s WHERE sesskey = %s %s AND expiry >= %s",
					$this->sessionDefinition->readFields,
					$this->tableName,
					$this->binaryOption,
					$p0,
					$this->connection->sysTimeStamp);

		$rs = $this->connection->execute($sql, $bind);
		
		//ADODB_Session::_dumprs($rs);
		if ($rs) {
			if ($rs->EOF) {
				$v = '';
			} else {
				$v = reset($rs->fields);
				$filter = array_reverse($filter);
				foreach ($filter as $f) {
					if (is_object($f)) {
						$v = $f->read($v, $this->_sessionKey());
					}
				}
				$v = rawurldecode($v);
			}

			$rs->close();

			$this->recordCRC = strlen($v) . crc32($v);
			return $v;
		}

		return '';
	}

	/*!
	* Write the serialized data to a database.
	* cannot type hint implemented class
	*
	* If the data has not been modified since the last read(), we do not write.
	*/
	final public function write($key, $oval)
	{

		if ($this->sessionDefinition->readOnly)
			return false;
		
		$lifetime		= $this->lifetime();

		$sysTimeStamp = $this->connection->sysTimeStamp;

		$expiry = $this->connection->OffsetDate($lifetime/(24*3600),$sysTimeStamp);

		$binary = $this->binaryOption;
		$crc	= $this->recordCRC;
		$table  = $this->tableName;
		
		$expire_notify	= $this->expireNotify();
		$filter         = $this->readWriteFilters;
		
		$clob			= $this->sessionDefinition->largeObject;
		// crc32 optimization since adodb 2.1
		// now we only update expiry date, thx to sebastian thom in adodb 2.32
		if ($crc !== '00' && $crc !== false && $crc == (strlen($oval) . crc32($oval))) {
			if ($this->debug) {
				$message = 'SESSION: Only updating date - crc32 not changed';
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}

			$expirevar = '';
			if ($expire_notify) {
				$var = reset($expire_notify);
				global $$var;
				if (isset($$var)) {
					$expirevar = $$var;
				}
			}
			
			$p0 = $this->connection->param('0');
			$p1 = $this->connection->param('1');

			$bind = array($expirevar,$key);

			$sql = "UPDATE $table 
					   SET expiry = $expiry ,expireref=$p0 modified = $sysTimeStamp 
					 WHERE $binary sesskey = $p1 
					 AND expiry >= $sysTimeStamp";
					 
			$rs = $this->connection->execute($sql,$bind);
			return true;
		}
		$val = rawurlencode($oval);
		foreach ($filter as $f) {
			if (is_object($f)) {
				$val = $f->write($val, $this->_sessionKey());
			}
		}

		$expireref = '';
		if ($expire_notify) {
			$var = reset($expire_notify);
			global $$var;
			if (isset($$var)) {
				$expireref = $$var;
			}
		}

		if (!$clob) {	// no lobs, simply use replace()
		
			$p0 = $this->connection->param(0);
			$bind = array($key);
			
			$sql = "SELECT COUNT(*) AS cnt 
					  FROM $table 
					 WHERE $binary sesskey = $p0";
		
			$rs = $this->connection->execute($sql,$bind);
			if ($rs) 
				$rs->Close();

			$p1 = $this->connection->param(1);
			$p2 = $this->connection->param(2);
			
			$bind = array($val,$expireref,$key);
			
			if ($rs && reset($rs->fields) > 0) {
				
				$sql = "UPDATE $table 
						  SET expiry=$expiry, sessdata=$p0, expireref=$p1,modified=$sysTimeStamp 
						WHERE sesskey = $p2";

			} else {
				$sql = "INSERT INTO $table (expiry, sessdata, expireref, sesskey, created, modified)
									VALUES ($expiry, $p0,$p1,$p2, $sysTimeStamp, $sysTimeStamp)";
			}


			$rs = $this->connection->Execute($sql,$bind);

		} else {

			$lob_value = $this->getLobValue($clob);

			$this->connection->startTrans();

			$p0 = $this->connection->param(0);
			$p1 = $this->connection->param(1);
			
			$bind = array($key);
			
			$sql = "SELECT COUNT(*) AS cnt 
					 FROM $table 
					WHERE $binary sesskey=$p0";
					
			$rs = $this->connection->execute($sql,$bind);
			
			$bind = array($expireref,$key);
			
			if ($rs && reset($rs->fields) > 0) {
				
				$sql = "UPDATE $table 
						   SET expiry=$expiry, sessdata=$lob_value, expireref=$p0,modified=$sysTimeStamp 
						 WHERE sesskey = $p1";

			} else {
				
				$sql = "INSERT INTO $table (expiry, sessdata, expireref, sesskey, created, modified)
					VALUES ($expiry,$lob_value, $p0, $p1, $sysTimeStamp, $sysTimeStamp)";
			}

			$rs = $this->connection->execute($sql,$bind);

			$qkey = $this->connection->qstr($key);
			$rs2 = $this->connection->updateBlob($table, 'sessdata', $val, " sesskey=$qkey", strtoupper($clob));
			if ($this->debug) {
				$this->connection->loggingObject->log(Logger::DEBUG,$oval);
			}
			
			$rs = @$this->connection->completeTrans();


		}

		if (!$rs) {
			$message = 'Session Replace: ' . $this->connection->errorMsg();
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
			return false;
		}  
		return $rs ? true : false;
	}

	/*
	* Session estruction - Part of sessionHandlerInterface
	*
	* @param string $key
	*
	* @return bool
	*/
	final public function destroy($key) {
		
		$expire_notify	= $this->expireNotify();

		
		$qkey = $this->connection->quote($key);
		$binary = $this->binaryOption;
		$table  = $this->tableName;

		if ($expire_notify) {
			reset($expire_notify);
			
			$fn = next($expire_notify);
			
			$this->connection->setFetchMode($this->connection::ADODB_FETCH_NUM);
			
			$sql = "SELECT expireref, sesskey 
					  FROM $table 
					 WHERE $binary sesskey = $qkey";
					 
			$rs = $this->connection->execute($sql);
			
			$this->connection->setFetchMode($this->connection->coreFetchMode);
			if (!$rs) {
				return false;
			}
			if (!$rs->EOF) {
				$ref = $rs->fields[0];
				$key = $rs->fields[1];
				$fn($ref, $key);
			}
			$rs->close();
		}

		$sql = "DELETE FROM $table 
				 WHERE $binary sesskey = $qkey";
				 
		$rs = $this->connection->execute($sql);
		if ($rs) {
			$rs->close();
			if ($this->debug){
				$message = 'SESSION: Successfully destroyed and cleaned up';
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}
		}

		return $rs ? true : false;
	}

	/*
	* Garbage Collection - Part of sessionHandlerInterface
	*
	* @param int $maxlifetime
	*
	* @return bool
	*/
	final public function gc($maxlifetime)
	{

		$expire_notify	= $this->expireNotify();
		$optimize		= $this->optimizeTable;

		if ($this->debug) {
			$COMMITNUM = 2;
		} else {
			$COMMITNUM = 20;
		}
	
		$sysTimeStamp = $this->connection->sysTimeStamp;
		
		$time = $this->connection->offsetDate(-$maxlifetime/24/3600,$sysTimeStamp);
		
		$binaryOption = $this->binaryOption;

		$table = $this->tableName;
		
		if ($expire_notify) {
			reset($expire_notify);
			$fn = next($expire_notify);
		} else {
			$fn = false;
		}

		$this->connection->SetFetchMode($this->connection::ADODB_FETCH_NUM);
		$sql = "SELECT expireref, sesskey 
			      FROM $table 
				 WHERE expiry < $time 
	               ORDER BY 2"; # add order by to prevent deadlock
		$rs = $this->connection->selectLimit($sql,1000);
		if ($this->debug) 
		{
			//$debugger = new \ADOdb\database\debug\debugger($this->connection);
			//$debugger->doBacktrace();

		}
		$this->connection->setFetchMode($this->connection->coreFetchMode);
		
		if ($rs) {
			$this->connection->beginTrans();
			
			$keys = array();
			$ccnt = 0;
			
			while (!$rs->EOF) {
				
				$ref = $rs->fields[0];
				$key = $rs->fields[1];
				if ($fn) 
					$fn($ref, $key);
				
				$p0 = $this->connection->param('0');
				$bind = array($key);
				
				$sql = "DELETE FROM $table WHERE sesskey=$p0";
				
				$del = $this->connection->execute($sql,$bind);
				
				$rs->MoveNext();
				$ccnt += 1;
				
				if ($ccnt % $COMMITNUM == 0) {
					if ($this->debug) {
						$message = 'SESSION: Garbage Collecton complete';
						$this->connection->loggingObject->log(Logger::DEBUG,$message);
					}
					$this->connection->commitTrans();
					$this->connection->beginTrans();
				}
			}
			$rs->close();

			$this->connection->commitTrans();
		}


		if ($optimize) {
			
			$sql = $this->getOptimizationSql();
			
			if ($sql) {
				$this->connection->execute($sql);
			}
		}


		return true;
	}

	/*
	* Returns the db specific optimization sql
	*
	* @return ?string
	*/
	protected function getOptimizationSql(): ?string {
		return null;
	}
}
