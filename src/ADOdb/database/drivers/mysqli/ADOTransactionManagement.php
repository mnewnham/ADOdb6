<?php
/**
* Methods associated with transaction management for this driver
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\database\drivers\mysqli;

use \Monolog\Logger;
use \ADOdb\database\debug;

class ADOTransactionManagement extends \ADOdb\addons\ADOTransactionManagement
{
	/**
	* Starts a non-smart transaction process
	*
	* @return bool true
	*/
	final public function beginTrans() : bool
	{
		if ($this->transOff) 
			return true;
		
		$this->moreTransCnt();
		
		/*
		* Switch off autocommit - irrelevant for myisam tables
		*/
		@mysqli_autocommit($this->connection->_connectionID, false);
		
		/*
		* Begin transaction
		*/
		$this->connection->execute('BEGIN');
		
		if ($this->debug)
		{
			$message = sprintf('Starting transaction layer %s',$this->transCnt);
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}
		
		return true;
	}

	/**
	* Commits a non-smart transaction`
	*
	* @param bool [$ok]  If false, rolls back
	*
	* @return bool success
	*/
	final public function commitTrans(bool $ok=true) : bool
	{
		if ($this->transOff) 
			return true;
		
		if (!$ok) 
		{
			return $this->rollbackTrans();
		}
		
		if ($this->debug)
		{
			$message = sprintf('Completing transaction layer %s',$this->transCnt);
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}
		
		return true;

		if ($this->getTransCnt())
			$this->lessTransCnt();
		
		$this->connection->execute('COMMIT');

		/*
		* Restore autocommit
		*/
		@mysqli_autocommit($this->connection->_connectionID, true);
		return true;
	}

	/**
	* Rolls back a non-smart transaction
	*
	* @return bool
	*/
	final public function rollbackTrans() : bool
	{
		if ($this->transOff) 
			return true;

		if ($this->debug) {
			
			$message = sprintf('Rolling back transaction layer %s',$this->transCnt);
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}
		
		if ($this->getTransCnt())
			$this->lessTransCnt();
		
		$this->connection->execute('ROLLBACK');
		
		/*
		* Restore autocommit
		*/
		@mysqli_autocommit($this->connection->_connectionID, true);
		return true;
	}

}