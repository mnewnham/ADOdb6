<?php
/**
* This class encapsulates the concept of a recordset created in memory
* as an array. This is useful for the creation of cached recordsets.
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\common;

class ADORecordSetArray extends \ADOdb\database\drivers\mysqli\ADORecordSet
{
	/*
	* Need to access this from ADOCacheMethods
	*/
	public string $databaseType = 'array';

	/*
	* holds the 2-dimensional data array
	*/
	public array $_array = array();
	
	/*
	* the array of types of each column (C B I L M)
	*/
	protected array $_types = array();
	
	/* 
	* names of each column in array
	*/
	protected array $_colnames = array();
	
	/*
	* holds array of field objects
	*/
	protected array $_fieldobjects = array(); 
	
	
	public function __construct(?object $queryID,object $connection)
	{
		if ($queryID == null)
		{
			/*
			* Only happens when called by memcache
			*/
			$this->connection = $connection;
			return;
		}
		
		parent::__construct($queryID,$connection);
	}

	
	/**
	* Setup the Array and datatype file objects
	*
	* @param array		is a 2-dimensional array holding the data.
	*			The first row should hold the column names
	*			unless paramter $colnames is used.
	* @param fieldarr	holds an array of ADOFieldObject's.
	*/
	final public function initArrayFields(
		array &$array,
		array &$fieldarr) : void {
		
		$this->_array = $array;
		
		if ($fieldarr) {
			$this->_fieldobjects = $fieldarr;
		}
		$this->init();
	}

	/**
	* Function not described
	*
	* @param int $nrows
	*
	* @return array
	*/
	public function getArray(int $nRows=-1) : array {
		if ($nRows == -1 && $this->_currentRow <= 0) {
			return $this->_array;
		} else {
			$arr = ADORecordSet::GetArray($nRows);
			return $arr;
		}
	}

	/**
	* Internal recordset initialization
	*
	* @return void
	*/
	protected function _initrs() : void {
		$this->_numOfRows =  sizeof($this->_array);
		
		$this->_numOfFields = (isset($this->_fieldobjects))
			? sizeof($this->_fieldobjects)
			: sizeof($this->_types);
	}

	/**
	* Get the value of a field in the current row by column name.
	* Will not work if ADODB_FETCH_MODE is set to ADODB_FETCH_NUM.
	*
	* @param colname  is the field to access
	*
	* @return the value of $colname column
	*/
	public function fields(string $colname) : ?string {
		
		$mode = isset($this->adodbFetchMode) ? $this->adodbFetchMode : $this->fetchMode;

		if ($this->connection->fetchMode == $this->connection::ADODB_FETCH_ASSOC
		&& $this->connection->connectionDefinitions->assocCase == $this->connection::ADODB_ASSOC_CASE_LOWER)
		{
		//if ($mode & ADODB_FETCH_ASSOC) {
			if (!isset($this->fields[$colname]) && !is_null($this->fields[$colname])) {
				$colname = strtolower($colname);
			}
			return $this->fields[$colname];
		}
		if (!$this->bind) {
			$this->bind = array();
			for ($i=0; $i < $this->_numOfFields; $i++) {
				$o = $this->FetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}
		return $this->fields[$this->bind[strtoupper($colname)]];
	}

	/**
	* Gets specified field info from a numeric index
	*
	* @param $fieldoffset
	*
	* @return obj
	*/
	public function fetchField(int $fieldOffset = -1) : ?object {
		
		if (isset($this->_fieldobjects)) {
			return $this->_fieldobjects[$fieldOffset];
		}
		
		$o =  new \ADOdb\common\ADOFieldObject();
		
		$o->name = $this->_colnames[$fieldOffset];
		$o->type =  $this->_types[$fieldOffset];
		$o->max_length = -1; // length not known

		return $o;
	}
	
	/**
	* Moves the record pointer to a specific row
	* if not directly supported by the database
	*
	* @param int $row
	*
	* @return bool success
	*/
	protected function x_seek(int $row) : bool {
		if (sizeof($this->_array) && 0 <= $row && $row < $this->_numOfRows) {
			$this->_currentRow = $row;
			
			$this->fields = $this->_array[$row];
			return true;
		}
		return false;
	}

	/**
	* Move to next record in the recordset.
	*
	* @return true if there still rows available, or false if there are no more rows (EOF).
	*/
	public function xmoveNext() : bool {

		if (!$this->EOF) {
			$this->_currentRow++;

			$pos = $this->_currentRow;

			if ($this->_numOfRows <= $pos) {
				$this->fields = null;
			} else {
				
				$this->fields = $this->_array[$pos];
				return true;
			}
			$this->EOF = true;
		}

		return false;
	}

	/**
	* Function not described
	*
	* @return bool
	*/
	protected function _fetch() : bool {
		$pos = $this->_currentRow;

		if ($this->_numOfRows <= $pos) {
			$this->fields = null;
			return false;
		}
		
		$this->fields = $this->_array[$pos];
		return true;
	}

	/**
	* Internal close function
	*
	* @return bool
	*/
	protected function _close() :bool {
		return true;
	}

}