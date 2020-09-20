<?php
/**
* Database specific metafunctions for mysqli
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\database\drivers\mysqli;

final class ADOMetaFunctions extends \ADOdb\meta\ADOMetaFunctions
{
	
	protected string $metaColumnsSQL = "SHOW COLUMNS FROM `%s`";
	
	protected string $metaTablesSQL = "SELECT
			TABLE_NAME,
			CASE WHEN TABLE_TYPE = 'VIEW' THEN 'V' ELSE 'T' END
		FROM INFORMATION_SCHEMA.TABLES
		WHERE TABLE_SCHEMA=";

	/**
	* metaprocedures
	*
	* @param str $namePattern
	* @param str $catalog
	* @param str $schemapattern
	*
	* @return array
	*/
	final public function metaProcedures(
		string $NamePattern = null,
		string $catalog  = null, 
		string $schemaPattern  = null) : array
	{
		$this->connection->fetchMode = $this->connection::ADODB_FETCH_NUM;

		$procedures = array ();

		// get index details

		$likepattern = '';
		if ($NamePattern) {
			$likepattern = " LIKE '".$NamePattern."'";
		}
		$rs = $this->connection->execute('SHOW PROCEDURE STATUS'.$likepattern);
		if (is_object($rs)) {

			// parse index data into array
			while ($row = $rs->FetchRow()) {
				$procedures[$row[1]] = array(
					'type' => 'PROCEDURE',
					'catalog' => '',
					'schema' => '',
					'remarks' => $row[7],
				);
			}
		}

		$rs = $this->connection->execute('SHOW FUNCTION STATUS'.$likepattern);
		if (is_object($rs)) {
			// parse index data into array
			while ($row = $rs->FetchRow()) {
				$procedures[$row[1]] = array(
					'type' => 'FUNCTION',
					'catalog' => '',
					'schema' => '',
					'remarks' => $row[7]
				);
			}
		}

		$this->connection->fetchMode = $this->connection->coreFetchMode;
		
		return $procedures;
	}
	
	/**
	* Lists all the available databases
	*
	* @return array
	*/
	final public function metaDatabases() : ?array
	{
		$query = "SHOW DATABASES";
		$ret = $this->connection->execute($query);
		if ($ret && is_object($ret)){
			$arr = array();
			while (!$ret->EOF){
				$db = $ret->Fields('Database');
				if ($db != 'mysql') $arr[] = $db;
				$ret->MoveNext();
			}
			return $arr;
		}
		return $ret;
	}
/**
	 * Get a list of indexes on the specified table.
	 *
	 * @param string $table The name of the table to get indexes for.
	 * @param bool $primary (Optional) Whether or not to include the primary key.
	 * @param bool $owner (Optional) Unused.
	 *
	 * @return array|bool An array of the indexes, or false if the query to get the indexes failed.
	 */
	final public function metaIndexes (
			string $table, 
			bool $primary = false, 
			?string $owner = null) : ?array {
		
		// save old fetch mode
		$this->connection->fetchMode = $this->connection::ADODB_FETCH_NUM;
		// get index details
		$rs = $this->connection->execute(sprintf('SHOW INDEXES FROM %s',$table));
		$this->connection->fetchMode = $this->connection->coreFetchMode;
		if (!is_object($rs)) {
			return null;
		}

		$indexes = array ();
		
		/*
		* Extended index attributes are provided as follows:
		0 table The name of the table
		1 non_unique 1 if the index can contain duplicates, 0 if it cannot.
		2 key_name The name of the index. The primary key index always has the name of PRIMARY.
		3 seq_in_index The column sequence number in the index. The first column sequence number starts from 1.
		4 column_name The column name
		5 collation Collation represents how the column is sorted in the index. A means ascending, B means descending, or NULL means not sorted.
		6 cardinality The cardinality returns an estimated number of unique values in the index. Note that the higher the cardinality, the greater the chance that the query optimizer uses the index for lookups.
		7 sub_part The index prefix. It is null if the entire column is indexed. Otherwise, it shows the number of indexed characters in case the column is partially indexed.
		8 packed indicates how the key is packed; NUL if it is not.
		9 null YES if the column may contain NULL values and blank if it does not.
		10 index_type represents the index method used such as BTREE, HASH, RTREE, or FULLTEXT.
		11 comment The information about the index not described in its own column.
		12 index_comment shows the comment for the index specified when you create the index with the COMMENT attribute.
		13 visible Whether the index is visible or invisible to the query optimizer or not; YES if it is, NO if not.
		14 expression If the index uses an expression rather than column or column prefix value, the expression indicates the expression for the key part and also the column_name column is NULL.
		*/
		$extendedAttributeNames = array(
			'table','non_unique','key_name','seq_in_index',
			'column_name','collation','cardinality','sub_part',
			'packed','null','index_type','comment',
			'index_comment','visible', 'expression');
			
		
		/*
		* These items describe the index itself
		*/
		
		$indexExtendedAttributeNames = array_flip(array(
			'table','non_unique','key_name','cardinality',
			'packed','index_type','index_comment',
			'visible', 'expression'));
			
		/*
		* These items describe the column attributes in the index
		*/
		$columnExtendedAttributeNames = array_flip(array(
			'seq_in_index',
			'column_name','collation','sub_part',
			'null'));		
			
		/*
		*  parse index data into array
		*/
		while ($row = $rs->FetchRow()) {
			
			if ($primary == FALSE AND $row[2] == 'PRIMARY') {
				continue;
			}
			
			/*
			* Prepare the extended attributes for use
			*/
			if (!$this->suppressExtendedMetaIndexes)
			{
				$rowCount = count($row);
				$earow = array_merge($row,array_fill($rowCount,15 - $rowCount,''));
				$extendedAttributes = array_combine($extendedAttributeNames,$earow);
			}
			
			if (!isset($indexes[$row[2]])) 
			{
				if ($this->suppressExtendedMetaIndexes)
					$indexes[$row[2]] = $this->legacyMetaIndexFormat;
				else
					$indexes[$row[2]] = $this->extendedMetaIndexFormat;
				
				$indexes[$row[2]]['unique'] = ($row[1] == 0);
				
				if (!$this->suppressExtendedMetaIndexes)
				{
					/*
					* We need to extract the 'index' specific itema
					* from the extended attributes
					*/
					$iAttributes = array_intersect_key($extendedAttributes,$indexExtendedAttributeNames);
					$indexes[$row[2]]['index-attributes'] = $iAttributes;
				}
			}

			$indexes[$row[2]]['columns'][$row[3] - 1] = $row[4];
			
			if (!$this->suppressExtendedMetaIndexes)
			{
				/*
				* We need to extract the 'column' specific itema
				* from the extended attributes
				*/
				$cAttributes = array_intersect_key($extendedAttributes,$columnExtendedAttributeNames);
				$indexes[$row[2]]['column-attributes'][$cAttributes['column_name']] = $cAttributes;
			}
		}

		// sort columns by order in the index
		foreach ( array_keys ($indexes) as $index )
		{
			ksort ($indexes[$index]['columns']);
		}

		return $indexes;
	}
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function oldmetaIndexes (
			string $table, 
			bool $primary = false, 
			?string $owner = null) : array {
		// save old fetch mode
		$this->connection->fetchMode = $this->connection::ADODB_FETCH_NUM;


		// get index details
		$rs = $this->connection->execute(sprintf('SHOW INDEXES FROM %s',$table));
		
		$this->connection->fetchMode = $this->connection->coreFetchMode;

		if (!is_object($rs)) {
			return false;
		}

		$indexes = array ();

		// parse index data into array
		while ($row = $rs->FetchRow()) {
			if ($primary == false AND $row[2] == 'PRIMARY') {
				continue;
			}

			if (!isset($indexes[$row[2]])) {
				$indexes[$row[2]] = array(
					'unique' => ($row[1] == 0),
					'columns' => array()
				);
			}

			$indexes[$row[2]]['columns'][$row[3] - 1] = $row[4];
		}

		// sort columns by order in the index
		foreach ( array_keys ($indexes) as $index )
		{
			ksort ($indexes[$index]['columns']);
		}

		return $indexes;
	}
	
	/**
	* Returns a list of foreeign keys
	*
	* @param str $table
	* @param str $owner
	* @param bool $upper
	* @param bool associative (Ignored for driver)
	*
	* @return array
	*/	
	final public function metaForeignKeys( 
		string $table, 
		string $owner = null, 
		bool $upper = FALSE, 
		bool $associative = FALSE ) : array
	{
		
		$this->connection->setFetchMode($this->connection::ADODB_FETCH_ASSOC);
		
		if ( !empty($owner) ) {
			$table = "$owner.$table";
		}
		
		$sql = sprintf('SHOW CREATE TABLE %s', $table); print $sql;
		$a_create_table = $this->connection->getRow($sql);
		
		
		$create_sql = isset($a_create_table["Create Table"]) ? $a_create_table["Create Table"] : $a_create_table["Create View"];

		$matches = array();

		if (!preg_match_all("/FOREIGN KEY \(`(.*?)`\) REFERENCES `(.*?)` \(`(.*?)`\)/", $create_sql, $matches))
			return array();
		$foreign_keys = array();
		$num_keys = count($matches[0]);
		for ( $i = 0; $i < $num_keys; $i ++ ) {
			$my_field  = explode('`, `', $matches[1][$i]);
			$ref_table = $matches[2][$i];
			$ref_field = explode('`, `', $matches[3][$i]);

			if ( $upper ) {
				$ref_table = strtoupper($ref_table);
			}

			// see https://sourceforge.net/p/adodb/bugs/100/
			if (!isset($foreign_keys[$ref_table])) {
				$foreign_keys[$ref_table] = array();
			}
			$num_fields = count($my_field);
			for ( $j = 0; $j < $num_fields; $j ++ ) {
				if ( $associative ) {
					$foreign_keys[$ref_table][$ref_field[$j]] = $my_field[$j];
				} else {
					$foreign_keys[$ref_table][] = "{$my_field[$j]}={$ref_field[$j]}";
				}
			}
		}

		$this->connection->setFetchMode($this->connection->coreFetchMode);
		
		return $foreign_keys;
	}

	/**
	 * Retrieves a list of tables based on given criteria
	 *
	 * @param string $ttype Table type = 'TABLE', 'VIEW' or false=both (default)
	 * @param string $showSchema schema name, false = current schema (default)
	 * @param string $mask filters the table by name
	 *
	 * @return array list of tables
	 */
	final public function metaTables(
		string $ttype=null,
		string $showSchema=null,
		string $mask=null) : array
	{
		$save = $this->metaTablesSQL;
		if ($showSchema && is_string($showSchema)) {
			$this->metaTablesSQL .= $this->connection->qstr($showSchema);
		} else {
			$this->metaTablesSQL .= "schema()";
		}

		if ($mask) {
			$mask = $this->connection->qstr($mask);
			$this->metaTablesSQL .= " AND table_name LIKE $mask";
		}
		
		$this->connection->fetchMode = $this->connection::ADODB_FETCH_NUM;
		
		$rs = $this->connection->execute($this->metaTablesSQL);
		
		$this->connection->fetchMode = $this->connection->coreFetchMode;
		$this->metaTablesSQL = $save;

		if ($rs === false) {
			return false;
		}
		$arr = $rs->GetArray();
	
		$arr2 = array();

		if ($hast = ($ttype && isset($arr[0][1]))) {
			$showt = strncmp($ttype,'T',1);
		}

		for ($i=0; $i < sizeof($arr); $i++) {
			if ($hast) {
				if ($showt == 0) {
					if (strncmp($arr[$i][1],'T',1) == 0) {
						$arr2[] = trim($arr[$i][0]);
					}
				} else {
					if (strncmp($arr[$i][1],'V',1) == 0) {
						$arr2[] = trim($arr[$i][0]);
					}
				}
			} else
				$arr2[] = trim($arr[$i][0]);
		}
		$rs->Close();
		return $arr2;
	}
	
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function MetaColumns(
		string $table, 
		bool $normalize=true) : array
	{
		if (!$this->metaColumnsSQL)
			return false;

		$this->connection->fetchMode = $this->connection::ADODB_FETCH_NUM;
		
		$rs = $this->connection->execute(sprintf($this->metaColumnsSQL,$table));
		
		$this->connection->fetchMode = $this->connection->coreFetchMode;

		if (!is_object($rs))
			return false;

		$retarr = array();
		while (!$rs->EOF) {
			$fld = new \ADOdb\common\ADOFieldObject();
			$fld->name = $rs->fields[0];
			$type = $rs->fields[1];

			// split type into type(length):
			$fld->scale = null;
			if (preg_match("/^(.+)\((\d+),(\d+)/", $type, $query_array)) {
				$fld->type = $query_array[1];
				$fld->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
				$fld->scale = is_numeric($query_array[3]) ? $query_array[3] : -1;
			} elseif (preg_match("/^(.+)\((\d+)/", $type, $query_array)) {
				$fld->type = $query_array[1];
				$fld->max_length = is_numeric($query_array[2]) ? $query_array[2] : -1;
			} elseif (preg_match("/^(enum)\((.*)\)$/i", $type, $query_array)) {
				$fld->type = $query_array[1];
				$arr = explode(",",$query_array[2]);
				$fld->enums = $arr;
				$zlen = max(array_map("strlen",$arr)) - 2; // PHP >= 4.0.6
				$fld->max_length = ($zlen > 0) ? $zlen : 1;
			} else {
				$fld->type = $type;
				$fld->max_length = -1;
			}
			$fld->not_null = ($rs->fields[2] != 'YES');
			$fld->primary_key = ($rs->fields[3] == 'PRI');
			$fld->auto_increment = (strpos($rs->fields[5], 'auto_increment') !== false);
			$fld->binary = (strpos($type,'blob') !== false);
			$fld->unsigned = (strpos($type,'unsigned') !== false);
			$fld->zerofill = (strpos($type,'zerofill') !== false);

			if (!$fld->binary) {
				$d = $rs->fields[4];
				if ($d != '' && $d != 'NULL') {
					$fld->has_default = true;
					$fld->default_value = $d;
				} else {
					$fld->has_default = false;
				}
			}

			if ($this->connection->coreFetchMode == $this->connection::ADODB_FETCH_NUM) {
				$retarr[] = $fld;
			} else {
				$retarr[strtoupper($fld->name)] = $fld;
			}
			$rs->MoveNext();
		}

		$rs->Close();
		return $retarr;
	}
	
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function metaColumnNames(
		string $table, 
		bool $numIndexes=false,
		bool $useattnum=false) : array {
			
		$objarr = $this->metaColumns($table);
		if (!is_array($objarr)) {
			return false;
		}
		$arr = array();
		if ($numIndexes) {
			$i = 0;
			if ($useattnum) {
				foreach($objarr as $v)
					$arr[$v->attnum] = $v->name;

			} else
				foreach($objarr as $v) $arr[$i++] = $v->name;
		} else
			foreach($objarr as $v) $arr[strtoupper($v->name)] = $v->name;

		return $arr;
	}
	
	/**
	* @returns an array with the primary key columns in it.
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/	
	final public function metaPrimaryKeys(
			string $table, 
			string $owner=null): array 
	{
		
		$p = array();
		$objs = $this->metaColumns($table);
		if ($objs) {
			foreach($objs as $v) {
				if (!empty($v->primary_key)) {
					$p[] = $v->name;
				}
			}
		}
		if (sizeof($p)) {
			return $p;
		}
		return false;
	}
	
	/*

0 = MYSQLI_TYPE_DECIMAL
1 = MYSQLI_TYPE_CHAR
1 = MYSQLI_TYPE_TINY
2 = MYSQLI_TYPE_SHORT
3 = MYSQLI_TYPE_LONG
4 = MYSQLI_TYPE_FLOAT
5 = MYSQLI_TYPE_DOUBLE
6 = MYSQLI_TYPE_NULL
7 = MYSQLI_TYPE_TIMESTAMP
8 = MYSQLI_TYPE_LONGLONG
9 = MYSQLI_TYPE_INT24
10 = MYSQLI_TYPE_DATE
11 = MYSQLI_TYPE_TIME
12 = MYSQLI_TYPE_DATETIME
13 = MYSQLI_TYPE_YEAR
14 = MYSQLI_TYPE_NEWDATE
247 = MYSQLI_TYPE_ENUM
248 = MYSQLI_TYPE_SET
249 = MYSQLI_TYPE_TINY_BLOB
250 = MYSQLI_TYPE_MEDIUM_BLOB
251 = MYSQLI_TYPE_LONG_BLOB
252 = MYSQLI_TYPE_BLOB
253 = MYSQLI_TYPE_VAR_STRING
254 = MYSQLI_TYPE_STRING
255 = MYSQLI_TYPE_GEOMETRY
*/

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
			$fieldobj = false): string {
		
		if (is_object($t)) {
			$fieldobj = $t;
			$t = $fieldobj->type;
			$len = $fieldobj->max_length;
		}


		$len = -1; // mysql max_length is not accurate
		switch (strtoupper($t)) {
		case 'STRING':
		case 'CHAR':
		case 'VARCHAR':
		case 'TINYBLOB':
		case 'TINYTEXT':
		case 'ENUM':
		case 'SET':

		case MYSQLI_TYPE_TINY_BLOB :
		#case MYSQLI_TYPE_CHAR :
		case MYSQLI_TYPE_STRING :
		case MYSQLI_TYPE_ENUM :
		case MYSQLI_TYPE_SET :
		case 253 :
			if ($len <= $this->connection->getMinBlobSize())
				return 'C';

		case 'TEXT':
		case 'LONGTEXT':
		case 'MEDIUMTEXT':
			return 'X';

		// php_mysql extension always returns 'blob' even if 'text'
		// so we have to check whether binary...
		case 'IMAGE':
		case 'LONGBLOB':
		case 'BLOB':
		case 'MEDIUMBLOB':

		case MYSQLI_TYPE_BLOB :
		case MYSQLI_TYPE_LONG_BLOB :
		case MYSQLI_TYPE_MEDIUM_BLOB :
			return !empty($fieldobj->binary) ? 'B' : 'X';

		case 'YEAR':
		case 'DATE':
		case MYSQLI_TYPE_DATE :
		case MYSQLI_TYPE_YEAR :
			return 'D';

		case 'TIME':
		case 'DATETIME':
		case 'TIMESTAMP':

		case MYSQLI_TYPE_DATETIME :
		case MYSQLI_TYPE_NEWDATE :
		case MYSQLI_TYPE_TIME :
		case MYSQLI_TYPE_TIMESTAMP :
			return 'T';

		case 'INT':
		case 'INTEGER':
		case 'BIGINT':
		case 'TINYINT':
		case 'MEDIUMINT':
		case 'SMALLINT':

		case MYSQLI_TYPE_INT24 :
		case MYSQLI_TYPE_LONG :
		case MYSQLI_TYPE_LONGLONG :
		case MYSQLI_TYPE_SHORT :
		case MYSQLI_TYPE_TINY :
			if (!empty($fieldobj->primary_key)) return 'R';
			return 'I';

		
		case 'GEOMETERY':
		case 'LINESTRING':
		case 'POINT':
		case 'POLYGON':
		case 'MULTIPOINT':
		case 'MULTILINESTRING':
		case 'MULTIPOLYGON':
		case 'GEOMETRYCOLLECTION':
		case 255:
			return 'G';
			break;
			
		/*
		* Json support
		*/
		case 'JSON':
		case 245:
			return 'J';
		
		// Added floating-point types
		// Maybe not necessery.
		case 'FLOAT':
		case 'DOUBLE':
//		case 'DOUBLE PRECISION':
		case 'DECIMAL':
		case 'DEC':
		case 'FIXED':
		default:
			return $this->connection->connectionDefinitions->defaultMetaType;
		}
	} // function

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function metaTransaction( string $mode) : string
	{
		$mode = strtoupper($mode);
		$mode = str_replace('ISOLATION LEVEL ','',$mode);

		switch($mode) {

		case 'READ UNCOMMITTED':
			return 'ISOLATION LEVEL READ UNCOMMITTED';
			break;

		case 'READ COMMITTED':
			return 'ISOLATION LEVEL READ COMMITTED';
			break;

		case 'REPEATABLE READ':
			return 'ISOLATION LEVEL REPEATABLE READ';
			break;

		case 'SERIALIZABLE':
			return 'ISOLATION LEVEL SERIALIZABLE';
			break;

		default:
			return $mode;
		}
	}
}