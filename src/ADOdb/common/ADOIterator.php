<?php
/**
* The ADOIterator Class
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\common;
use ADOdb;

class ADOIterator implements Iterator {

	private object $rs;


	function __construct($rs) {
		$this->rs = $rs;
	}
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	function rewind() {
		$this->rs->MoveFirst();
	}
	
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	function valid() {
		return !$this->rs->EOF;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	function key() {
		return $this->rs->_currentRow;
	}

	function current() {
		return $this->rs->fields;
	}

	function next() {
		$this->rs->MoveNext();
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	function __call($func, $params) {
		return call_user_func_array(array($this->rs, $func), $params);
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	function hasMore() {
		return !$this->rs->EOF;
	}

}