<?php
/**
* Global methods associated with transaction management
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\addons;

use \Monolog\Logger;
use \ADOdb\database\debug;


class ADOTransactionManagement
{
	protected int $transOff = 0;
	protected int $transCnt = 0;
	
	/*
	* Indicates if a transaction can b committed
	*/
	protected bool $_transOK = false;
	
	protected int $debug;
	
	/**
	* Constructor
	*
	* @param object $connection
	*/
	public function __construct(object &$connection)
	{	
		$this->connection = $connection;
		$this->debug	  = (int)$connection->connectionDefinitions->debug;
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
	*
	* @param  str $errfn
	*
	* @return bool
	*/
	final public function startTrans(
		string $errfn = 'ADODB_TransMonitor') : bool {
		
		if ($this->getTransOff() > 0) {
			if ($this->debug)
			{
				$message = 'TRANS: Adding additional transactional layer';
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}
			$this->setTransOff($this->getTransOff() + 1);
			return true;
		}

		$this->_oldRaiseFn = $this->connection->raiseErrorFn;
		$this->connection->raiseErrorFn = $errfn;
		$this->_transOK = true;

		if ($this->transCnt > 0) {
			$message = "TRANS: Bad Transaction: StartTrans called within BeginTrans";
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);

		}
		else if ($this->debug == 10)
		{
			$message = "TRANS: StartTrans successfully started";
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}
		$ok = $this->beginTrans();
		$this->setTransOff(1);
		return $ok;
	}
	
	/**
	 * Begin a Transaction. Must be followed by CommitTrans() or RollbackTrans().
	 *
	 * @return bool true if succeeded or false if database does not support transactions
	 */
	public function beginTrans() : bool {
		
		if ($this->debug) {
			$message = 'TRANS: Transactions not supported for this driver';
			$this->connection->loggingObject->log(Logger::DEBUG,$message);
		}
		
		return false;
	}
	
	/**
	* Used together with StartTrans() to end a transaction. Monitors connection
	* for sql errors, and will commit or rollback as appropriate.
	*
	* @param bool $autoComplete if true, monitor sql errors and commit and rollback as appropriate,
	*  and if set to false force rollback even if no SQL error detected.
	*
	* @returns true on commit, false on rollback.
	*/
	final public function completeTrans(bool $autoComplete = true) : bool
	{
		
		if ($this->getTransOff() > 1) {
			$this->setTransOff($this->getTransOff() - 1);
			if ($this->debug == 10)
			{
				$message = 'Multi-level internal transaction ended';
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}				
			return true;
		}
		//$this->raiseErrorFn = $this->_oldRaiseFn;

		$this->setTransOff(0);
		
		if ($this->_transOK && $autoComplete) {
			if (!$this->commitTrans()) {
				$this->_transOK = false;
				if ($this->debug) {
					$message = "TRANS: Smart Commit Failed";
					$this->connection->loggingObject->log(Logger::CRITICAL,$message);
				}
			} else {
				
				$message = "TRANS: Smart Commit occurred";
				$this->connection->loggingObject->log(Logger::DEBUG,$message);

			}
		} else {
			$this->_transOK = false;
			$this->rollbackTrans();
			if ($this->debug) {
				$message = "Smart Rollback occurred";
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
			}
		}

		return $this->_transOK;
	}

	/**
	*	During a StartTrans/CompleteTrans block, causes a rollback.
	*
	* @return bool
	*/	
	final public function failTrans() : void 
	{
		if ($this->debug) {

			if ($this->getTransOff() == 0) {
				$message = 'TRANS: FailTrans outside StartTrans/CompleteTrans';
				$this->connection->loggingObject->log(Logger::CRITICAL,$message);
			} else if ($this->debug == 10){
				$message = 'TRANS: FailTrans was called';
				$this->connection->loggingObject->log(Logger::DEBUG,$message);
				//$debugger->doBacktrace();
			}
		}
		
		$this->_transOK = false;
	}
	
	/**
	* Sets the transaction level count
	*
	* @param  int $count The level
	*
	* @return void
	*/
	final public function setTransCnt($count) : void
	{
		$this->transCnt = $count;
	}
	
	/**
	* Shortcut to increase the transaction level count
	*
	* @return void
	*/
	final public function moreTransCnt() : void
	{
		$this->transCnt++ ;
		$this->connection->transCnt++;
	}
	
	/**
	* Shortcut to decrease the transaction level count
	*
	* @return void
	*/
	final public function lessTransCnt() : void
	{
		$this->transCnt--;
	}
	
	/**
	* Gets the transaction level count
	*
	* @return int
	*/
	final public function getTransCnt() : int
	{
		return $this->transCnt;
	}
	
	/**
	* Sets the transaction switch to the requested value
	*
	* @param  bool $count the value
	*
	* @return void
	*/
	final public function setTransOff(int $count) : void
	{
		$this->transOff = $count;
	}
	
	/**
	* Gets the transaction switch level
	*
	* @return int
	*/
	final public function getTransOff() : int
	{
		return $this->transOff;
	}
	
	/**
	* A routine for trapping transaction problems
	*
	* @param string $dbms
	* @param string $fn 	Function triggering error
	* @param int	$errno
	* @param string $errmsg
	* @param string $p1
	* @param string $p2
	*
	* @return void
	*/
	public function ADODB_TransMonitor($dbms, $fn, $errno, $errmsg, $p1, $p2) : void
	{
	
		$message = "TRANS: $fn errno:$errno errmsg:$errmsg";
		$this->connection->loggingObject->log(Logger::CRITICAL,$message);

		$this->_transOK = false;
		if ($this->connection->_oldRaiseFn) {
			$errfn = $this->connection->_oldRaiseFn;
			$errfn($dbms, $fn, $errno, $errmsg, $p1, $p2,$this->connection);
		}
	}
}