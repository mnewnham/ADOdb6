<?php
/**
* Data Dictionary functions for the mysqli driver
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\database\drivers\mysqli;

class ADODataDictionary extends \ADOdb\database\dictionary\ADODataDictionary {
	
	var $databaseType = 'mysql';
	protected string  $alterCol = ' MODIFY COLUMN';
	protected bool $alterTableAddIndex = true;
	protected string $dropTable = 'DROP TABLE IF EXISTS %s'; 
	protected string $dropIndex = 'DROP INDEX %s ON %s';
	protected string $renameColumn = 'ALTER TABLE %s CHANGE COLUMN %s %s %s';	// needs column-definition!

	protected bool $blobAllowsNotNull 		= true;
	protected bool $blobAllowsDefaultValue 	= false;
	
	
	/**
	* Returns the actual database specific type for a meta
	*
	* @param str $meta
	*
	* @return string
	*/
	final public function actualType(
				string $meta) : string {
		
		switch(strtoupper($meta)) {
		case 'C': 
			return 'VARCHAR';
		case 'XL':
			return 'LONGTEXT';
		case 'X': 
			return 'TEXT';

		case 'C2': 
			return 'VARCHAR';
		case 'X2': 
			return 'LONGTEXT';

		case 'B': 
			return 'LONGBLOB';

		case 'D': 
			return 'DATE';
		case 'TS':
		case 'T': 
			return 'DATETIME';
		case 'L': 
			return 'TINYINT';

		case 'R':
		case 'I4':
		case 'I': 
			return 'INTEGER';
		case 'I1': 
			return 'TINYINT';
		case 'I2': 
			return 'SMALLINT';
		case 'I8': 
			return 'BIGINT';

		case 'F': 
			return 'DOUBLE';
		case 'N': 
			return 'NUMERIC';
		
		
        /*
        * Geometry Types
        */
        case $this->connection::ADODB_METATYPE_GEOMETRY: 
			return 'GEOMETRY';
        case $this->connection::ADODB_METATYPE_POINT: 
			return 'POINT';
        case $this->connection::ADODB_METATYPE_POLYGON: 
			return 'POLYGON';
        case $this->connection::ADODB_METATYPE_LINESTRING: 
			return 'LINESTRING';

        case $this->connection::ADODB_METATYPE_MULTIPOINT: 
			return 'MULTIPOINT';
        case $this->connection::ADODB_METATYPE_MULTIPOLYGON: 
			return 'MULTIPOLYGON';
        case $this->connection::ADODB_METATYPE_MULTILINESTRING: 
			return 'MULTILINESTRING';
        case $this->connection::ADODB_METATYPE_GEOMETRYCOLLECTION: 
			return 'GEOMETRYCOLLECTION';
		
        /*
		* JSON
		*/
		case $this->connection::ADODB_METATYPE_JSON: 
			return 'JSON';

		default:
			return $meta;
		}
	}

	// return string must begin with space
		/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final protected function _createSuffix(
				string $fname,
				string &$ftype,
				bool $fnotnull,
				string $fdefault,
				bool $fautoinc,
				string $fconstraint,
				string $funsigned) : string	{
					
		$suffix = '';
		if ($funsigned) $suffix .= ' UNSIGNED';
		if ($fnotnull) $suffix .= ' NOT NULL';
		if (strlen($fdefault)) $suffix .= " DEFAULT $fdefault";
		if ($fautoinc) $suffix .= ' AUTO_INCREMENT';
		if ($fconstraint) $suffix .= ' '.$fconstraint;
		return $suffix;
	}

	/*
	CREATE [TEMPORARY] TABLE [IF NOT EXISTS] tbl_name [(create_definition,...)]
		[table_options] [select_statement]
		create_definition:
		col_name type [NOT NULL | NULL] [DEFAULT default_value] [AUTO_INCREMENT]
		[PRIMARY KEY] [reference_definition]
		or PRIMARY KEY (index_col_name,...)
		or KEY [index_name] (index_col_name,...)
		or INDEX [index_name] (index_col_name,...)
		or UNIQUE [INDEX] [index_name] (index_col_name,...)
		or FULLTEXT [INDEX] [index_name] (index_col_name,...)
		or [CONSTRAINT symbol] FOREIGN KEY [index_name] (index_col_name,...)
		[reference_definition]
		or CHECK (expr)
	*/

	/*
	CREATE [UNIQUE|FULLTEXT] INDEX index_name
		ON tbl_name (col_name[(length)],... )
	*/

	final protected function _indexSQL(
				string $idxname, 
				string $tabname, 
				array $flds, 
				array $idxoptions) : array {
					
		$sql = array();

		if ( isset($idxoptions['REPLACE']) || isset($idxoptions['DROP']) ) {
			if ($this->alterTableAddIndex) 
				$sql[] = "ALTER TABLE $tabname DROP INDEX $idxname";
			else 
				$sql[] = sprintf($this->dropIndex, $idxname, $tabname);

			if ( isset($idxoptions['DROP']) )
				return $sql;
		}

		if ( empty ($flds) ) {
			return $sql;
		}

		if (isset($idxoptions['FULLTEXT'])) {
			$unique = ' FULLTEXT';
		} elseif (isset($idxoptions['UNIQUE'])) {
			$unique = ' UNIQUE';
		} else {
			$unique = '';
		}

		if ( is_array($flds) ) $flds = implode(', ',$flds);

		if ($this->alterTableAddIndex) 
			$s = "ALTER TABLE $tabname ADD $unique INDEX $idxname ";
		else 
			$s = 'CREATE' . $unique . ' INDEX ' . $idxname . ' ON ' . $tabname;

		$s .= ' (' . $flds . ')';

		if ( isset($idxoptions[$this->upperName]) )
			$s .= $idxoptions[$this->upperName];

		$sql[] = $s;

		return $sql;
	}
}
