<?php
/**
* Lightweight recordset when there are no records to be returned
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\common;

class ADORecordSetEmpty implements \IteratorAggregate
{
	var $dataProvider = 'empty';
	var $databaseType = false;
	var $EOF = true;
	var $_numOfRows = 0;
	var $fields = false;
	var $connection = false;

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function rowCount() : int {
		return 0;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function recordCount() : int {
		return 0;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function po_recordCount() : int {
		return 0;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function close() : bool {
		return true;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function fetchRow() : ?object  {
		return null;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function fieldCount() {
		return 0;
	}

	/**
	* final public function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function init() {}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function getIterator() {
		return new ADOIteratorEmpty($this);
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function getAssoc() {
		return array();
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function getArray() {
		return array();
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function getAll() {
		return array();
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function getArrayLimit() {
		return array();
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function getRows() {
		return array();
	}

	/**
	* function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function getRowAssoc() {
		return array();
	}

	/**
	* function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function maxRecordCount() {
		return 0;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function numRows() {
		return 0;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function numCols() {
		return 0;
	}
}
