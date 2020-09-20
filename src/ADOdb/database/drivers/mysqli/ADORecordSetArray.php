<?php 
/**
* Full recordset for returned records
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADODb\database\drivers\mysqli;

class ADORecordSetArray extends \ADOdb\common\ADORecordSetArray {

	/*
	* indicates that seeking is supported by the 
	* database driver::data_seek
	*/
	protected bool $canSeek = true;	
	
	/**
	* Return the ADOdb metatype for the db type
	*
	* @param string|object $t`
	* @param int $len
	* @param object|bool $fieldobj
	*
	* @return string
	*/
	final public function metaType(
			$t, 
			int $len = -1, 
			$fieldobj = false): string
	{
		$metaClass = $this->connection->connectionDefinitions->driverPath . 'ADOMetaFunctions';
		$meta = new $metaClass($this->connection);
		
		return $meta->metaType($t, $len, $fieldobj);
	}
	
	/**
	* Returns a specified column by associative index
	*
	* @param str $colname
	*
	* @return string
	*/
	final public function fields(string $colname) : ?string
	{

		if ($this->fetchMode != MYSQLI_NUM) {
			return @$this->fields[$colname];
		}

		if (!$this->bind) {
			$this->bind = array();
			for ($i = 0; $i < $this->_numOfFields; $i++) {
				$o = $this->FetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}
		return $this->fields[$this->bind[strtoupper($colname)]];
	}
}
