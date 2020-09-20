<?php
/**
* Core Methods associated with inserting and updating records
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADOdb\addons;

use ADOdb;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ADORecordHandler
{
	
	const ADODB_TABLE_REGEX =  '([]0-9a-z_\:\"\`\.\@\[-]*)';
	
	const DB_AUTOQUERY_UPDATE = 2;
	const DB_AUTOQUERY_INSERT = 1;


		// ********************************************************
		// Controls $ADODB_FORCE_TYPE mode. Default is ADODB_FORCE_VALUE (3).
		// Used in GetUpdateSql and GetInsertSql functions. Thx to Niko, nuko#mbnet.fi
		//
		/* 0 = ignore empty fields. All empty fields in array are ignored.
	* 1 = force null. All empty, php null and string 'null' fields are
	*     changed to sql NULL values.
	* 2 = force empty. All empty, php null and string 'null' fields are
	*     changed to sql empty '' or 0 values.
	* 3 = force value. Value is left as it is. Php null and string 'null'
	*     are set to sql NULL values and empty fields '' are set to empty '' sql values.
	* 4 = force value. Like 1 but numeric empty fields are set to zero.
    */
	const ADODB_FORCE_IGNORE = 0;
	const ADODB_FORCE_NULL   = 1;
	const ADODB_FORCE_EMPTY  = 2;
	const ADODB_FORCE_VALUE  = 3;
	const ADODB_FORCE_NULL_AND_ZERO = 4;
	// ********************************************************



	protected object $connection;
	
	public function __construct(object $connection)
	{
		$this->connection = $connection;
	}
	
	/**
	 * Generates an Update Query based on an existing recordset.
	 * $arrFields is an associative array of fields with the value
	 * that should be assigned.
	 *
	 * Note: This function should only be used on a recordset
	 *	   that is run against a single table and sql should only
	 *		 be a simple select stmt with no groupby/orderby/limit
	 *
	 * "Jonathan Younger" <jyounger@unilab.com>
	 *
	 * @param	object	$rs
	 * @param	string[]	$arrFields
	 * @param	bool		$forceUpdate
	 * @param	bool		$magicq
	 * @param	int		$force
	 *
	 * @return string
	 */
	final public function getUpdateSQL(
			object &$rs, 
			array $arrFields,
			bool $forceUpdate=false,
			bool $magicq=false,
			$force=self::ADODB_FORCE_IGNORE) {
				
		if ($force === null)
			$force = self::ADODB_FORCE_IGNORE;
		
		return $this->_adodb_getupdatesql($rs,$arrFields,$forceUpdate,$force);
	}
	
	/**
	* Generates an Insert Query based on an existing recordset.
	* $arrFields is an associative array of fields with the value
	* that should be assigned.
	*
	* Note: This function should only be used on a recordset
	*       that is run against a single table.
	*
	* @param	obj			$rs
	* @param	string[]	$arrFields
	* @param	bool		$magicq		Compat - discarded
	* @param	int			$force	
	*
	* @return string
	*/
  	final public function getInsertSQL(
			object &$rs, 
			array  $arrFields,
			bool   $magicq=false,
			int    $force=0) : string {
		
		return $this->_adodb_getinsertsql($rs,$arrFields,$force);
	}
	
	/**
	* Internal function to generate the update SQL
	*
	* @param object &$rs, 
	* @param  string[]  $arrFields,
	* @param bool   $forceUpdate=false,
	* @param 		int    $force=0
	*
	* @return
	*/
	final protected function _adodb_getupdatesql(
			object &$rs, 
			array  $arrFields,
			bool   $forceUpdate=false,
			int    $force=2) : string {
		
		$quoteFieldNames = $this->connection->connectionDefinitions->quoteFieldNames;

		if (!is_a($rs, $this->connection->driverPath .'ADORecordSet_array'))
		{
			$message = 'Bad recordset in \ADODb\addons\ADORecordHandler::getUpdateSQL';
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);

			return false;
		}
		
		if ($this->connection->connectionDefinitions->debug == '10')
		{
			$message = 'Valid recordset received in ADORecordHandler';
			$this->connection->loggingObject->log(LOGGER::DEBUG,$message);
		}

		$fieldUpdatedCount = 0;
		if (is_array($arrFields))
			$arrFields = array_change_key_case($arrFields,CASE_UPPER);

		$hasnumeric = isset($rs->fields[0]);
		$setFields = '';

		// Loop through all of the fields in the recordset
		for ($i=0, $max=$rs->FieldCount(); $i < $max; $i++) {
			// Get the field from the recordset
			$field = $rs->fetchField($i);

			// If the recordset field is one
			// of the fields passed in then process.
			$upperfname = strtoupper($field->name);
			if ($this->adodb_key_exists($upperfname,$arrFields,$force)) {

				// If the existing field value in the recordset
				// is different from the value passed in then
				// go ahead and append the field name and new value to
				// the update query.

				if ($hasnumeric) 
					$val = $rs->fields[$i];
				else if (isset($rs->fields[$upperfname])) 
					$val = $rs->fields[$upperfname];
				else if (isset($rs->fields[$field->name])) 
					$val =  $rs->fields[$field->name];
				else if (isset($rs->fields[strtolower($upperfname)])) 
					$val =  $rs->fields[strtolower($upperfname)];
				else $val = '';


				if ($forceUpdate || strcmp($val, $arrFields[$upperfname])) {
					// Set the counter for the number of fields that will be updated.
					$fieldUpdatedCount++;

					// Based on the datatype of the field
					// Format the value properly for the database
					$type = $rs->metaType($field->type);


					if ($type == 'null') {
						$type = 'C';
					}

					if ((strpos($upperfname,' ') !== false) || ($quoteFieldNames)) {
						$wrapper = $this->connection->nameQuote;
						switch ($quoteFieldNames) {
						case 'BRACKETS':
							$wrapper = '';
							$fnameq = sprintf('%s%s%s',
							$this->connection->leftBracket,
							$upperfname,
							$this->connection->rightBracket);
							break;
						case 'LOWER':
							$fnameq = strtolower($field->name);
							break;
						case 'NATIVE':
							$fnameq = $field->name;
							break;
						case 'UPPER':
						default:
							$fnameq = $upperfname;
							break;
						}
						
						$fnameq = sprintf('%s%s%s',
								$wrapper,
								$fname,
								$wrapper
								);
					} else
						$fnameq = $upperfname;

                //********************************************************//
                if (is_null($arrFields[$upperfname])
					|| (empty($arrFields[$upperfname]) && strlen($arrFields[$upperfname]) == 0)
                    || $arrFields[$upperfname] === $this->connection->null2null
                    )
                {
                    switch ($force) {

                        //case 0:
                        //    //Ignore empty values. This is allready handled in "adodb_key_exists" function.
                        //break;

                        case 1:
                            //Set null
                            $setFields .= $field->name . " = null, ";
                        break;

                        case 2:
                            //Set empty
                            $arrFields[$upperfname] = "";
                            $setFields .= $this->_adodb_column_sql('U', $type, $upperfname, $fnameq,$arrFields);
                        break;
						default:
                        case 3:
                            //Set the value that was given in array, so you can give both null and empty values
                            if (is_null($arrFields[$upperfname]) || $arrFields[$upperfname] === $this->connection->null2null) {
                                $setFields .= $field->name . " = null, ";
                            } else {
                                $setFields .= $this->_adodb_column_sql('U', $type, $upperfname, $fnameq,$arrFields);
                            }
                        break;
                    }
                //********************************************************//
                } else {
						//we do this so each driver can customize the sql for
						//DB specific column types.
						//Oracle needs BLOB types to be handled with a returning clause
						//postgres has special needs as well
						$setFields .= $this->_adodb_column_sql('U', $type, $upperfname, $fnameq, $arrFields);
					}
				}
			}
		}

		// If there were any modified fields then build the rest of the update query.
		if ($fieldUpdatedCount > 0 || $forceUpdate) {
					// Get the table name from the existing query.
			if (!empty($rs->tableName)) 
				$tableName = $rs->tableName;
			else {
				preg_match("/FROM\s+".self::ADODB_TABLE_REGEX."/is", $rs->sql, $tableName);
				$tableName = $tableName[1];
			}
			// Get the full where clause excluding the word "WHERE" from
			// the existing query.
			preg_match('/\sWHERE\s(.*)/is', $rs->sql, $whereClause);

			$discard = false;
			// not a good hack, improvements?
			if ($whereClause) {
			#var_dump($whereClause);
				if (preg_match('/\s(ORDER\s.*)/is', $whereClause[1], $discard));
				else if (preg_match('/\s(LIMIT\s.*)/is', $whereClause[1], $discard));
				else if (preg_match('/\s(FOR UPDATE.*)/is', $whereClause[1], $discard));
				else preg_match('/\s.*(\) WHERE .*)/is', $whereClause[1], $discard); # see https://sourceforge.net/p/adodb/bugs/37/
			} else
				$whereClause = array(false,false);

			if ($discard)
				$whereClause[1] = substr($whereClause[1], 0, strlen($whereClause[1]) - strlen($discard[1]));

			$sql = 'UPDATE '.$tableName.' SET '.substr($setFields, 0, -2);
			if (strlen($whereClause[1]) > 0)
				$sql .= ' WHERE '.$whereClause[1];

			return $sql;

		} else {
			return false;
		}
	}

	/**
	* There is a special case of this function for the oci8 driver.
	* The proper way to handle an insert w/ a blob in oracle requires
	* a returning clause with bind variables and a descriptor blob.
	*
	* @param object 	&$rs,
	* @param string[] 	$arrFields,
	* @param int 		$force
	*
	* @return string the SQL
	*/	
	final protected function _adodb_getinsertsql(
			object &$rs,
			array $arrFields,
			int $force=2)
	{
		static $cacheRS = false;
		static $cacheSig = 0;
		static $cacheCols;
		
		$quoteFieldNames = $this->connection->connectionDefinitions->quoteFieldNames;

		$tableName = '';
		$values = '';
		$fields = '';
		$recordSet = null;
		if (is_array($arrFields))
			$arrFields = array_change_key_case($arrFields,CASE_UPPER);		
		$fieldInsertedCount = 0;

		if (is_string($rs)) 
		{
			//ok we have a table name
			//try and get the column info ourself.
			$tableName = $rs;

			//we need an object for the recordSet
			//because we have to call MetaType.
			//php can't do a $rsclass::MetaType()
			
			$rsclass = $this->connection->driverPath . 'ADORecordSet';
			
			$recordSet = new $rsclass($this,-1,$this->connection->connectionDefinitions->fetchMode);
			//$recordSet->connection = $this->connection;

			if (is_string($cacheRS) && $cacheRS == $rs) {
				$columns = $cacheCols;
			} else {
				$columns = $this->connection->metaColumns( $tableName );
				$cacheRS = $tableName;
				$cacheCols = $columns;
			}
		} else if (is_a($rs, $this->connection->driverPath . 'ADORecordSet')){
			
			if (isset($rs->insertSig) && is_integer($cacheRS) && $cacheRS == $rs->insertSig) {
				$columns = $cacheCols;
			} else {
				for ($i=0, $max=$rs->FieldCount(); $i < $max; $i++)
					$columns[] = $rs->FetchField($i);
				$cacheRS = $cacheSig;
				$cacheCols = $columns;
				$rs->insertSig = $cacheSig++;
			}
			$recordSet = $rs;

		} else {
			$message = 'Bad recordset in \ADODb\addons\ADORecordHandler::getInsertSQL';
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);

			return false;
		}

		// Loop through all of the fields in the recordset
		foreach( $columns as $field ) 
		{
			$upperfname = strtoupper($field->name);
			if ($this->adodb_key_exists($upperfname,$arrFields,$force)) {
				$bad = false;
				if ((strpos($upperfname,' ') !== false) || ($quoteFieldNames)) {
					switch ($quoteFieldNames) {
					case 'BRACKETS':
						$fnameq = $this->connection->leftBracket.$upperfname.$this->connection->rightBracket;break;
					case 'LOWER':
						$fnameq = $this->connection->nameQuote.strtolower($field->name).$this->connection->nameQuote;break;
					case 'NATIVE':
						$fnameq = $this->connection->nameQuote.$field->name.$this->connection->nameQuote;break;
					case 'UPPER':
					default:
						$fnameq = $this->connection->nameQuote.$upperfname.$this->connection->nameQuote;break;
					}
				} else
					$fnameq = $upperfname;

				print "\nMETA CHECK FOR $upperfname = {$field->type}";
				$type = $this->connection->metaType($field->type);
				print " RETURNED $type";
				/********************************************************/
				if (is_null($arrFields[$upperfname])
				|| (empty($arrFields[$upperfname]) && strlen($arrFields[$upperfname]) == 0)
				|| $arrFields[$upperfname] === $this->connection->null2null	) {
					
                    switch ($force) {

                        case self::ADODB_FORCE_IGNORE: // we must always set null if missing
							$bad = true;
							break;

                        case self::ADODB_FORCE_NULL:
                            $values  .= "null, ";
                        break;

                        case self::ADODB_FORCE_EMPTY:
                            //Set empty
                            $arrFields[$upperfname] = "";
                            $values .= _adodb_column_sql('I', $type, $upperfname, $fnameq,$arrFields, $magicq);
                        break;

						default:
                        case self::ADODB_FORCE_VALUE:
                            //Set the value that was given in array, so you can give both null and empty values
							if (is_null($arrFields[$upperfname]) || $arrFields[$upperfname] === $this->connection->null2null) {
								$values  .= "null, ";
							} else {
                        		$values .= $this->_adodb_column_sql('I', $type, $upperfname, $fnameq, $arrFields, $magicq);
             				}
              			break;

						case self::ADODB_FORCE_NULL_AND_ZERO:
							switch ($type)
							{
								case 'N':
								case 'I':
								case 'L':
									$values .= '0, ';
									break;
								default:
									$values .= "null, ";
									break;
							}
						break;

             		} // switch

            /*********************************************************/
			} else {
				//we do this so each driver can customize the sql for
				//DB specific column types.
				//Oracle needs BLOB types to be handled with a returning clause
				//postgres has special needs as well
				$values .= $this->_adodb_column_sql( 'I', $type, $upperfname, $fnameq,$arrFields);
			}

			if ($bad) continue;
			// Set the counter for the number of fields that will be inserted.
			$fieldInsertedCount++;


			// Get the name of the fields to insert
			$fields .= $fnameq . ", ";
		}
	}


		/*
		* If there were any inserted fields then build 
		* the rest of the insert query.
		*/
		if ($fieldInsertedCount <= 0)
			return false;

		// Get the table name from the existing query.
		if (!$tableName) {
			if (!empty($rs->tableName)) 
				$tableName = $rs->tableName;
			else if (preg_match("/FROM\s+".self::ADODB_TABLE_REGEX."/is", $rs->sql, $tableName))
				$tableName = $tableName[1];
			else
				return false;
		}

		// Strip off the comma and space on the end of both the fields
		// and their values.
		$fields = substr($fields, 0, -2);
		$values = substr($values, 0, -2);

		// Append the fields and their values to the insert query.
		return 'INSERT INTO '.$tableName.' ( '.$fields.' ) VALUES ( '.$values.' )';
	}

	/**
	* Similar to PEAR DB's autoExecute(), except that
	* $mode can be 'INSERT' or 'UPDATE' or DB_AUTOQUERY_INSERT or DB_AUTOQUERY_UPDATE
	* If $mode == 'UPDATE', then $where is compulsory as a safety measure.
	*
	* @param string 	$table
	* @param string[]   $fields_values
	* @param string     $mode
	* @param string     $where
	* @param bool 		$forceUpdate means that even if the data has not changed, perform update.
	 */
	final public function autoExecute(
		string $table, 
		array $fields_values, 
		string $mode = 'INSERT', 
		string $where = null, 
		bool $forceUpdate = true) 
	{
		if ($where === null && ($mode == 'UPDATE' || $mode == 2 /* DB_AUTOQUERY_UPDATE */) ) {
			$message = 'Illegal mode=UPDATE with empty WHERE clause in \ADODb\addons\ADORecordHandler::AutoExecute';
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
			return false;
		}

		$sql = "SELECT * FROM $table";
		$rs = $this->connection->selectLimit($sql, 1);
		if (!$rs) {
			return false; // table does not exist
		}

		$rs->tableName = $table;
		if ($where !== false) {
			$sql .= " WHERE $where";
		}
		$rs->sql = $sql;

		switch($mode) {
			case 'UPDATE':
			case self::DB_AUTOQUERY_UPDATE:
				$sql = $this->GetUpdateSQL($rs, $fields_values, $forceUpdate);
				break;
			case 'INSERT':
			case self::DB_AUTOQUERY_INSERT:
				$sql = $this->GetInsertSQL($rs, $fields_values);
				break;
			default:
				$this->outp_throw("AutoExecute: Unknown mode=$mode", 'AutoExecute');
				return false;
		}
		return $sql && $this->Execute($sql);
	}
	
	/**
	* Format a column based on its metatype
	*
	* @param str $action I,U
	* @param str $type the metatype
	* @param str $fname the field name
	* @param str $fnameq the field name, possibly quoted or bracketed
	* @param array $arrFields, the record to write
	*
	* @return string the sql insertion
	*/
	protected function _adodb_column_sql(
		string $action, 
		string $type, 
		string $fname, 
		string $fnameq, 
		array $arrFields) : string {
	
	
		switch($type) {
			case "C":
			case "X":
			case 'B':
			case 'J':
			$val = $this->connection->qStr($arrFields[$fname]);
			break;

			case "D":
			$val = $this->connection->dbDate($arrFields[$fname]);
			break;

			case "T":
			$val = $this->connection->dbTimeStamp($arrFields[$fname]);
			break;

			case "N":
			$val = $arrFields[$fname];
			if (!is_numeric($val)) 
				$val = str_replace(',', '.', (float)$val);
			break;

			case "I":
			case "R":
			$val = $arrFields[$fname];
			if (!is_numeric($val)) 
				$val = (integer) $val;
			break;
			
			case 'G':
            /*
            * Geometry, leave untouched
            */
            $val = $arrFields[$fname];
            break;

			default:
			/*
			* basic sql injection defence
			*/
			$val = str_replace(array("'"," ","("),"",$arrFields[$fname]); 
			if (empty($val)) 
				$val = '0';
			break;
		}

		if ($action == 'I') 
			return $val . ", ";


		return $fnameq . "=" . $val  . ", ";

	}
	
	
	protected function _array_change_key_case($an_array)
	{
	if (is_array($an_array)) {
		//return array_map('strtoupper',$an_array);
		
		$new_array = array();
		foreach($an_array as $key=>$value)
			$new_array[strtoupper($key)] = $value;

	   	
		return $new_array;
   }

	return $an_array;
	}
	
	protected function adodb_key_exists($key, &$arr,$force=2)
	{
		if ($force<=0) {
			// the following is the old behaviour where null or empty fields are ignored
			return (!empty($arr[$key])) || (isset($arr[$key]) && strlen($arr[$key])>0);
		}

		if (isset($arr[$key])) 
			return true;
		
		## null check below
		return array_key_exists($key,$arr);
	}
	
	/**
	* Insert or replace a single record. Note: this is not the same as MySQL's replace.
	* ADOdb's Replace() uses update-insert semantics, not insert-delete-duplicates of MySQL.
	* Also note that no table locking is done currently, so it is possible that the
	* record be inserted twice by two programs...
	*
	* $this->Replace('products', array('prodname' =>"'Nails'","price" => 3.99), 'prodname');
	*
	* $table		table name
	* $fieldArray	associative array of data (you must quote strings yourself).
	* $keyCol		the primary key field name or if compound key, array of field names
	* autoQuote		set to true to use a hueristic to quote strings. Works with nulls and numbers
	*					but does not work with dates nor SQL functions.
	* has_autoinc	the primary key is an auto-inc field, so skip in insert.
	*
	* Currently blob replace not supported
	*
	* returns 0 = fail, 1 = update, 2 = insert
	*/

	public function replace($table, $fieldArray, $keyCol, $autoQuote=false, $has_autoinc=false) {

		return _adodb_replace($table, $fieldArray, $keyCol, $autoQuote, $has_autoinc);
	}
	
	
	protected function _adodb_replace($table, $fieldArray, $keyCol, $autoQuote, $has_autoinc)
	{
		if (count($fieldArray) == 0) 
			return 0;
		$first = true;
		$uSet = '';

		if (!is_array($keyCol)) {
			$keyCol = array($keyCol);
		}
		foreach($fieldArray as $k => $v) {
			if ($v === null) {
				$v = 'NULL';
				$fieldArray[$k] = $v;
			} else if ($autoQuote && /*!is_numeric($v) /*and strncmp($v,"'",1) !== 0 -- sql injection risk*/ strcasecmp($v,$this->connection->null2null)!=0) {
				$v = $this->connection->qstr($v);
				$fieldArray[$k] = $v;
			}
			if (in_array($k,$keyCol)) 
				continue; // skip UPDATE if is key

			if ($first) {
				$first = false;
				$uSet = "$k=$v";
			} else
				$uSet .= ",$k=$v";
		}

		$where = false;
		foreach ($keyCol as $v) {
			if (isset($fieldArray[$v])) {
				if ($where) $where .= ' and '.$v.'='.$fieldArray[$v];
				else $where = $v.'='.$fieldArray[$v];
			}
		}

		if ($uSet && $where) {
			$update = "UPDATE $table SET $uSet WHERE $where";

			$rs = $this->connection->execute($update);


			if ($rs) {
				if ($this->connection->poorAffectedRows) {
				/*
				 The Select count(*) wipes out any errors that the update would have returned.
				http://phplens.com/lens/lensforum/msgs.php?id=5696
				*/
					if ($this->connection->ErrorNo()<>0) return 0;

				# affected_rows == 0 if update field values identical to old values
				# for mysql - which is silly.

					$cnt = $this->connection->GetOne("select count(*) from $table where $where");
					if ($cnt > 0) return 1; // record already exists
				} else {
					if (($this->connection->affected_Rows()>0)) return 1;
				}
			} else
				return 0;
		}

	//	print "<p>Error=".$this->ErrorNo().'<p>';
		$first = true;
		foreach($fieldArray as $k => $v) {
			if ($has_autoinc && in_array($k,$keyCol)) continue; // skip autoinc col

			if ($first) {
				$first = false;
				$iCols = "$k";
				$iVals = "$v";
			} else {
				$iCols .= ",$k";
				$iVals .= ",$v";
			}
		}
		$insert = "INSERT INTO $table ($iCols) VALUES ($iVals)";
		$rs = $this->connection->execute($insert);
		return ($rs) ? 2 : 0;
	}
	
}