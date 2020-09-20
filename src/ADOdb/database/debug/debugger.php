<?php
/**
* The debugging functions for ADOdb system
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADODb\database\debug;


use Monolog\Logger;
use Monolog\Handler\StreamHandler;


class debugger
{
	protected $connection;
	
	protected int $debug;
	
	public function __construct(object $connection)
	{
		$this->connection = $connection;
		$this->debug = (int)$this->connection->connectionDefinitions->debug;
	}
	
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	public function _query(string $sql, $inputarr)
	{
		$ss = '';
		if ($inputarr) {
			foreach($inputarr as $kk=>$vv) {
				if (is_string($vv) && strlen($vv)>64) $vv = substr($vv,0,64).'...';
				if (is_null($vv)) $ss .= "($kk=>null) ";
				else $ss .= "($kk=>'$vv') ";
			}
			$ss = "[ $ss ]";
		}
		else
			$inputarr = null;
		
		$sqlTxt = is_array($sql) ? $sql[0] : $sql;
		/*str_replace(', ','##1#__^LF',is_array($sql) ? $sql[0] : $sql);
		$sqlTxt = str_replace(',',', ',$sqlTxt);
		$sqlTxt = str_replace('##1#__^LF', ', ' ,$sqlTxt);
		*/

		$dbt = $this->connection->databaseType;
		if (isset($this->connection->dsnType)) 
			$dbt .= '-'.$this->connection->dsnType;
		
		if ($this->debug)
			$this->logMessage(sprintf("CORE: (%s): %s %s",
							$dbt,
							$sqlTxt,
							$ss));
		

		$qID = @$this->connection->_query($sql,$inputarr);

		 if (!$qID) {

			if ($this->debug === -99)
				$this->logMessage(sprintf("CORE:(%s): %s %s",
							$dbt,
							$sqlTxt,
							$ss));
			/*
			$this->logMessage(sprintf("EXECUTE FAILED %s - %s",
							$this->connection->ErrorNo(),
							$this->connection->ErrorMsg()
							),Logger::CRITICAL);
			*/
		}

		//if ($this->debug == 99) 
		//	$this->doBacktrace(true,9999,2);
		return $qID;
	}
	
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function logMessage(string $message,int $level=-1) : void
	{
		if ($level == -1)
			$level = Logger::DEBUG;
		
		$this->connection->loggingObject->log($level,$message);
	}
	
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function doBacktrace(
		bool $printOrArr=true,
		int $levels=9999,
		int $skippy=0) :  void
	{


		$fmt =  "%% line %4d, file: %s";

		$MAXSTRLEN = 128;

		$s = '';

		if (is_array($printOrArr)) 
			$traceArr = $printOrArr;
		else 
			$traceArr = debug_backtrace();
		
		array_shift($traceArr);
		array_shift($traceArr);
		$tabs = sizeof($traceArr)-2;

		foreach ($traceArr as $arr) {
			if ($skippy) {$skippy -= 1; continue;}
			$levels -= 1;
			if ($levels < 0) break;

			$args = array();
			for ($i=0; $i < $tabs; $i++) 
				$s .=  "\t";
			$tabs -= 1;

			if (isset($arr['class'])) 
				$s .= $arr['class'].'.';
			
			if (isset($arr['args']))
			{
				foreach($arr['args'] as $v) {
				
					if (is_null($v)) 
						$args[] = 'null';
					else if (is_array($v)) 
						$args[] = 'Array['.sizeof($v).']';
					else if (is_object($v)) 
						$args[] = 'Object:'.get_class($v);
					else if (is_bool($v)) 
						$args[] = $v ? 'true' : 'false';
					else {
						$v = (string) @$v;
						$str = str_replace(array("\r","\n"),' ',substr($v,0,$MAXSTRLEN));
						if (strlen($v) > $MAXSTRLEN) 
							$str .= '...';
						$args[] = $str;
					}
				}
			}
			$s .= $arr['function'].'('.implode(', ',$args).')';

			$s .= @sprintf($fmt, $arr['line'],$arr['file'],basename($arr['file']));

			$s .= "\n";
		}
		
		if ($printOrArr) 
			print $s;

		$this->connection->loggingObject->log(Logger::CRITICAL,$s);
	}
	
	
}