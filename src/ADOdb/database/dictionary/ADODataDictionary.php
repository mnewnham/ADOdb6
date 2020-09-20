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
namespace ADOdb\database\dictionary;
use ADOdb;
class ADODataDictionary
{
	
	/*
	* The SQL to drop a table
	*/
	protected string $dropTable = 'DROP TABLE %s';
	
	/*
	* The SQL to rename a table
	*/
	protected string $renameTable = 'RENAME TABLE %s TO %s';
	
	/*
	* The SQL to drop an index
	*/
	protected string $dropIndex = 'DROP INDEX %s';
	
	/*
	* The prefix to a a column
	*/
	protected  string $addCol = ' ADD';
	
	/*
	* The prefix to alter a column
	*/
	protected string $alterCol = ' ALTER COLUMN';
	
	/*
	* The prefix to drop a column
	*/
	protected string $dropCol = ' DROP COLUMN';
	
	/*
	* The substitution to rename a column
	* @param table
	* @param old column
	* @param new column
	* @param column definitions
	*/
	protected string $renameColumn = 'ALTER TABLE %s RENAME COLUMN %s TO %s';	
	
	/*
	* Determines if column or table contains special characters
	* if so, quote
	*/
	protected string $nameRegex = '\w';
	protected string $nameRegexBrackets = 'a-zA-Z0-9_\(\)';
	
	/*
	* Prepends schema name if available
	*/
	protected ?string $schema = null;
	
	/*
	* Flags if field is auto-increment
	*/
	protected bool $autoIncrement = false;
	
	/*
	* These are types for changeTableSQL that
	* we cannot change the size of
	*/
	protected array $invalidResizeTypes4 = array(
		'CLOB','BLOB','TEXT','DATE','TIME');
	
	/*
	* any varchar/char field this size or greater 
	* is treated as a blob in other words, we use a text 
	* area for editing.
	*/
	protected int $blobSize = 100; 	

	/*
	* Indicates whether a BLOB/CLOB field will allow a NOT NULL setting
	* The type is whatever is matched to an X or X2 or B type. We must 
	* explicitly set the value in the driver to switch the behaviour on
	*/
	protected bool $blobAllowsNotNull = true;
	/*
	* Indicates whether a BLOB/CLOB field will allow a DEFAULT set
	* The type is whatever is matched to an X or X2 or B type. We must 
	* explicitly set the value in the driver to switch the behaviour on
	*/
	protected bool $blobAllowsDefaultValue = true;
	
	protected bool $alterTableAddIndex = false;
	
	protected ?string $upperName;

	
	public $connection;
	
	/**
	* Constructor
	*
	* @param obj $connection
	*
	*/	
	public function __construct(object $connection)
	{
		$this->connection = $connection;
		$this->upperName  = strtoupper($connection->connectionDefinitions->driver);
		$this->blobSize   = $connection->getMinBlobSize();

	}
	
	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	public function getCommentSQL(string $table, string $col) : ?string
	{
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
	public function setCommentSQL(string $table,string $col,?string $cmt) : bool
	{
		return false;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	public function metaTables() : array
	{
		if (!$this->connection->isConnected()) 
			return array();
		return $this->connection->metaTables();
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	public function metaColumns(
				string $tab, 
				bool $upper=true, 
				?string $schema=null) : array {
		if (!$this->connection->isConnected()) 
			return array();
		
		return $this->connection->metaColumns(
				$this->tableName($tab), 
				$upper, 
				$schema);
	}
	
	/**
	* @returns an array with the primary key columns in it
	*
	* @param str $table
	* @param str $owner
	*
	* @return array
	*/
	final public function metaPrimaryKeys(
		string $table, 
		string $owner=null): array {
	
		if (!$this->connection->isConnected()) 
			return array();
		
		return $this->connection->metaPrimaryKeys(
					$this->tableName($tab), 
					$owner);
	}

	/**
	* Lists the indexes 
	*
	* @param string table  table name to query
	* @param bool primary true to only show primary keys. 
	* @param string owner
	*
	* @return array of indexes
	*/
	public function metaIndexes (
			string $table, 
			bool $primary = false, 
			?string $owner = null) : array {

		if (!$this->connection->isConnected()) 
			return array();
		
		return $this->connection->metaIndexes(
			$this->tableName($table), $primary, $owner);
	}

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
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	public function nameQuote(
				?string $name = NULL,
				bool $allowBrackets=false) : ?string
	{
		if (!is_string($name)) {
			return null;
		}

		$name = trim($name);

		if ( !is_object($this->connection) ) {
			return $name;
		}

		//$quote = $this->connection->nameQuote;

		// if name is of the form `name`, quote it
		if ( preg_match('/^`(.+)`$/', $name, $matches) ) {
			//return $quote . $matches[1] . $quote;ie
			return $this->connection->quoteField($matches[1],'NATIVE');
		}

		// if name contains special characters, quote it
		$regex = ($allowBrackets) ? $this->nameRegexBrackets : $this->nameRegex;

		if ( !preg_match('/^[' . $regex . ']+$/', $name) ) {
			//return $quote . $name . $quote;
			return $this->connection->quoteField($name,'NATIVE');

		}

		return $name;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	public function tableName(string $name)
	{
		if ( $this->schema ) {
			return $this->nameQuote($this->schema) .'.'. $this->nameQuote($name);
		}
		return $this->nameQuote($name);
	}

	/**
	* Executes the sql array returned by getTableSQL and getIndexSQL
	*
	* @param array $sql
	* @param bool  $continueOnError
	*
	* @return int
	*/	
	final public function executeSQLArray(
		array $sql, 
		bool $continueOnError = true) : int {
			
		$rez = 2;
		$conn = $this->connection;
		$saved = $conn->debug;
		
		foreach($sql as $line) {

			//if ($this->debug) $conn->debug = true;
			$ok = $conn->execute($line);
			//$conn->debug = $saved;
			if (!$ok) {
				//if ($this->debug) ADOConnection::outp($conn->errorMsg());
				if (!$continueOnError) 
					return 0;
				$rez = 1;
			}
		}
		return $rez;
	}

	/**
	* Returns the actual type given a character code.
	*
	* @paeam string
	*
	* @return string
	*/
	public function actualType(string $meta) : string {
		return $meta;
	}

	/**
	* Creates a database
	*
	* @param str $dbname
	* @param str[] $options
	*
	* @return
	*/
	public function createDatabase(
			string $dbname,
			?array $options=null) : array{
		
		$options = $this->_options($options);
		$sql = array();

		$s = 'CREATE DATABASE ' . $this->nameQuote($dbname);
		if (isset($options[$this->upperName]))
			$s .= ' '.$options[$this->upperName];

		$sql[] = $s;
		return $sql;
	}

	/**
	* Generates the SQL to create index. Returns an array of sql strings.
	*
	* @param str $idxname
	* @param str $tabname
	* @param str $flds
	* @param str[] idxoptions
	*
	* @return array
	*/
	public function createIndexSQL(
			string $idxname, 
			string $tabname, 
			string $flds, 
			?array $idxoptions = null): array{
				
		if (!is_array($flds)) {
			$flds = explode(',',$flds);
		}

		foreach($flds as $key => $fld) {
			/*
			* some indexes can use partial fields, 
			* eg. index first 32 chars of "name" with NAME(32)
			*/
			$flds[$key] = $this->nameQuote($fld,$allowBrackets=true);
		}

		return $this->_indexSQL(
				$this->nameQuote($idxname), 
				$this->tableName($tabname), 
				$flds, 
				$this->_options($idxoptions));
	}

	/**
	* Drops the specified index from a table
	*
	* @param str $idxname
	* @param str $tabname
	*
	* @return str
	*/
	public function dropIndexSQL (
				string $idxname, 
				?string $tabname = NULL) : array {
					
		return array(
				sprintf($this->dropIndex, 
						$this->nameQuote($idxname), 
						$this->tableName($tabname))
						);
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	public function setSchema(string $schema) : void
	{
		$this->schema = $schema;
	}

	/**
	* Adds a column to a tab;e
	*
	* @param str $1
	* @param str $2
	*
	* @return str[]
	*/
	public function addColumnSQL(
				string $tabname, 
				string $flds) : array	{
					
		$tabname = $this->tableName($tabname);
		$sql = array();
		list($lines,$pkey,$idxs) = $this->_genFields($flds);
		// genfields can return FALSE at times
		if ($lines  == null) 
			$lines = array();
		
		$alter = 'ALTER TABLE ' . $tabname . $this->addCol . ' ';
		
		foreach($lines as $v) {
			$sql[] = $alter . $v;
		}
		if (is_array($idxs)) {
			foreach($idxs as $idx => $idxdef) {
				$sql_idxs = $this->createIndexSql(
						$idx, 
						$tabname, 
						$idxdef['cols'], 
						$idxdef['opts']);
						
				$sql = array_merge($sql, $sql_idxs);
			}
		}
		return $sql;
	}

	/**
	 * Change the definition of one column
	 *
	 * As some DBMs can't do that on their own, you need to supply the complete definition of the new table,
	 * to allow recreating the table and copying the content over to the new table
	 * @param string $tabname table-name
	 * @param string $flds column-name and type for the changed column
	 * @param string $tableflds='' complete definition of the new table, eg. for postgres, default ''
	 * @param array/string $tableoptions='' options for the new table see createTableSQL, default ''
	 * @return array with SQL strings
	 */
	public function alterColumnSQL(
			string $tabname, 
			string $flds, 
			?string $tableflds=null,
			?array $tableoptions=null) : array {
				
		$tabname = $this->tableName($tabname);
		$sql = array();
		list($lines,$pkey,$idxs) = $this->_genFields($flds);
		// genfields can return FALSE at times
		if ($lines == null) 
			$lines = array();
		
		$alter = 'ALTER TABLE ' . $tabname . $this->alterCol . ' ';
		
		foreach($lines as $v) {
			$sql[] = $alter . $v;
		}
		if (is_array($idxs)) {
			foreach($idxs as $idx => $idxdef) {
				$sql_idxs = $this->createIndexSql(
						$idx, 
						$tabname, 
						$idxdef['cols'], 
						$idxdef['opts']);
						
				$sql = array_merge($sql, $sql_idxs);
			}

		}
		return $sql;
	}

	/**
	 * Rename one column
	 *
	 * Some DBMs can only do this together with changeing the type of the column (even if that stays the same, eg. mysql)
	 * @param string $tabname table-name
	 * @param string $oldcolumn column-name to be renamed
	 * @param string $newcolumn new column-name
	 * @param string $flds='' complete column-definition-string like for addColumnSQL, only used by mysql atm., default=''
	 * @return array with SQL strings
	 */
	public function renameColumnSQL(
				string $tabname,
				string $oldcolumn,
				string $newcolumn,
				?string $flds=null)	{
					
		$tabname = $this->tableName($tabname);
		if ($flds) {
			list($lines,$pkey,$idxs) = $this->_genFields($flds);
			// genfields can return FALSE at times
			if ($lines == null) $lines = array();
			$first  = current($lines);
			list(,$column_def) = preg_split("/[\t ]+/",$first,2);
		}
		return array(sprintf(
					$this->renameColumn,
					$tabname,
					$this->nameQuote($oldcolumn),
					$this->nameQuote($newcolumn),
					$column_def)
					);
	}

	/**
	 * Drop one column
	 *
	 * Some DBM's can't do that on their own, you need to supply the complete definition of the new table,
	 * to allow, recreating the table and copying the content over to the new table
	 * @param string $tabname table-name
	 * @param string $flds column-name and type for the changed column
	 * @param string $tableflds='' complete definition of the new table, eg. for postgres, default ''
	 * @param array/string $tableoptions='' options for the new table see createTableSQL, default ''
	 * @return array with SQL strings
	 */
	public function dropColumnSQL(
				string $tabname, 
				string $flds, 
				?string $tableflds=null,
				?array $tableoptions=null) : array {
					
		$tabname = $this->tableName($tabname);
		if (!is_array($flds)) 
			$flds = explode(',',$flds);
		
		$sql = array();
		
		$alter = 'ALTER TABLE ' . $tabname . $this->dropCol . ' ';
		
		foreach($flds as $v) {
			$sql[] = $alter . $this->nameQuote($v);
		}
		return $sql;
	}

	/**
	* Drops a table
	*
	* @param str $tabname
	*
	* @return array
	*/
	public function dropTableSQL(string $tabname) : array
	{
		return array (sprintf($this->dropTable, 
							  $this->tableName($tabname)));
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	function renameTableSQL($tabname,$newname)
	{
		return array (sprintf($this->renameTable, $this->tableName($tabname),$this->tableName($newname)));
	}

	/**
	 Generate the SQL to create table. Returns an array of sql strings.
	*/
		/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function createTableSQL(
		string $tabname, 
		string $flds, 
		array $tableoptions=array()) : array {
			
		list($lines,$pkey,$idxs) = $this->_genFields($flds, true);
		// genfields can return FALSE at times
		if ($lines == null) 
			$lines = array();

		$taboptions = $this->_options($tableoptions);
		$tabname = $this->tableName($tabname);
		$sql = $this->_tableSQL($tabname,$lines,$pkey,$taboptions);

		// ggiunta - 2006/10/12 - KLUDGE:
        // if we are on autoincrement, and table options includes REPLACE, the
        // autoincrement sequence has already been dropped on table creation sql, so
        // we avoid passing REPLACE to trigger creation code. This prevents
        // creating sql that double-drops the sequence
        if ($this->autoIncrement && isset($taboptions['REPLACE']))
        	unset($taboptions['REPLACE']);
		$tsql = $this->_triggers($tabname,$taboptions);
		foreach($tsql as $s) $sql[] = $s;

		if (is_array($idxs)) {
			foreach($idxs as $idx => $idxdef) {
				$sql_idxs = $this->createIndexSql($idx, $tabname,  $idxdef['cols'], $idxdef['opts']);
				$sql = array_merge($sql, $sql_idxs);
			}
		}

		return $sql;
	}


	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	function _genFields($flds,$widespacing=false)
	{
		if (is_string($flds)) {
			$padding = '     ';
			$txt = $flds.$padding;
			$flds = array();
			$flds0 = $this->parseArgs($txt,',');
			$hasparam = false;
			foreach($flds0 as $f0) {
				$f1 = array();
				foreach($f0 as $token) {
					switch (strtoupper($token)) {
					case 'INDEX':
						$f1['INDEX'] = '';
						// fall through intentionally
					case 'CONSTRAINT':
					case 'DEFAULT':
						$hasparam = $token;
						break;
					default:
						if ($hasparam) $f1[$hasparam] = $token;
						else $f1[] = $token;
						$hasparam = false;
						break;
					}
				}
				// 'index' token without a name means single column index: name it after column
				if (array_key_exists('INDEX', $f1) && $f1['INDEX'] == '') {
					$f1['INDEX'] = isset($f0['NAME']) ? $f0['NAME'] : $f0[0];
					// check if column name used to create an index name was quoted
					if (($f1['INDEX'][0] == '"' || $f1['INDEX'][0] == "'" || $f1['INDEX'][0] == "`") &&
						($f1['INDEX'][0] == substr($f1['INDEX'], -1))) {
						$f1['INDEX'] = $f1['INDEX'][0].'idx_'.substr($f1['INDEX'], 1, -1).$f1['INDEX'][0];
					}
					else
						$f1['INDEX'] = 'idx_'.$f1['INDEX'];
				}
				// reset it, so we don't get next field 1st token as INDEX...
				$hasparam = false;

				$flds[] = $f1;

			}
		}
		$this->autoIncrement = false;
		$lines = array();
		$pkey = array();
		$idxs = array();
		foreach($flds as $fld) {
			if (is_array($fld))
				$fld = array_change_key_case($fld,CASE_UPPER);
			$fname = false;
			$fdefault = false;
			$fautoinc = false;
			$ftype = false;
			$fsize = false;
			$fprec = false;
			$fprimary = false;
			$fnoquote = false;
			$fdefts = false;
			$fdefdate = false;
			$fconstraint = false;
			$fnotnull = false;
			$funsigned = false;
			$findex = '';
			$funiqueindex = false;
			$fOptions	  = array();

			print_r($fld);
			//-----------------
			// Parse attributes
			foreach($fld as $attr => $v) {
				if ($attr == 2 && is_numeric($v)) 
					$attr = 'SIZE';
				elseif ($attr == 2 && strtoupper($ftype) == 'ENUM') 
					$attr = 'ENUM';
				else if (is_numeric($attr) && $attr > 1 && !is_numeric($v)) 
					$attr = strtoupper($v);
				switch($attr) {
				case '0':
				case 'NAME': 	$fname = $v; break;
				case '1':
				case 'TYPE': 	$ty = $v;
				$ftype = $this->actualType(strtoupper($v));

				break;

				case 'SIZE':
								$dotat = strpos($v,'.'); if ($dotat === false) $dotat = strpos($v,',');
								if ($dotat === false) $fsize = $v;
								else {
									$fsize = substr($v,0,$dotat);
									$fprec = substr($v,$dotat+1);
								}
								break;
				case 'UNSIGNED': $funsigned = true; break;
				case 'AUTOINCREMENT':
				case 'AUTO':	$fautoinc = true; $fnotnull = true; break;
				case 'KEY':
                // a primary key col can be non unique in itself (if key spans many cols...)
				case 'PRIMARY':	$fprimary = $v; $fnotnull = true; /*$funiqueindex = true;*/ break;
				case 'DEF':
				case 'DEFAULT': $fdefault = $v; break;
				case 'NOTNULL': $fnotnull = $v; break;
				case 'NOQUOTE': $fnoquote = $v; break;
				case 'DEFDATE': $fdefdate = $v; break;
				case 'DEFTIMESTAMP': $fdefts = $v; break;
				case 'CONSTRAINT': $fconstraint = $v; break;
				// let INDEX keyword create a 'very standard' index on column
				case 'INDEX': $findex = $v; break;
				case 'UNIQUE': $funiqueindex = true; break;
				case 'ENUM':
					$fOptions['ENUM'] = $v; break;
				} //switch
			} // foreach $fld


			//--------------------
			// VALIDATE FIELD INFO
			if (!strlen($fname)) {
				$message = 'Name not set for field';
				$this->connection->loggingObject->log(Logger::CRITICAL,$message);
				return false;
			}

			$fid = strtoupper(preg_replace('/^`(.+)`$/', '$1', $fname));
			$fname = $this->nameQuote($fname);

			if (!strlen($ftype)) {
				if ($this->debug) ADOConnection::outp("Undefined TYPE for field '$fname'");
				return false;
			} else {
				$ftype = strtoupper($ftype);
			}

			$ftype = $this->_getSize($ftype, $ty, $fsize, $fprec, $fOptions);
			
			if (($ty == 'X' || $ty == 'X2' || $ty == 'XL' || $ty == 'B') && !$this->blobAllowsNotNull)
				/*
				* some blob types do not accept nulls, so we override the
				* previously defined value
				*/
				$fnotnull = false; 

			if ($fprimary) 
				$pkey[] = $fname;

			if (($ty == 'X' || $ty == 'X2' || $ty == 'XL' || $ty == 'B') && !$this->blobAllowsDefaultValue)
				/*
				* some databases do not allow blobs to have defaults, so we
				* override the previously defined value
				*/
				$fdefault = false;
			// build list of indexes
			if ($findex != '') {
				if (array_key_exists($findex, $idxs)) {
					$idxs[$findex]['cols'][] = ($fname);
					if (in_array('UNIQUE', $idxs[$findex]['opts']) != $funiqueindex) {
						if ($this->debug) ADOConnection::outp("Index $findex defined once UNIQUE and once not");
					}
					if ($funiqueindex && !in_array('UNIQUE', $idxs[$findex]['opts']))
						$idxs[$findex]['opts'][] = 'UNIQUE';
				}
				else
				{
					$idxs[$findex] = array();
					$idxs[$findex]['cols'] = array($fname);
					if ($funiqueindex)
						$idxs[$findex]['opts'] = array('UNIQUE');
					else
						$idxs[$findex]['opts'] = array();
				}
			}
			//--------------------
			// CONSTRUCT FIELD SQL
			if ($fdefts) {
				if (substr($this->connection->databaseType,0,5) == 'mysql') {
					$ftype = 'TIMESTAMP';
				} else {
					$fdefault = $this->connection->sysTimeStamp;
				}
			} else if ($fdefdate) {
				if (substr($this->connection->databaseType,0,5) == 'mysql') {
					$ftype = 'TIMESTAMP';
				} else {
					$fdefault = $this->connection->sysDate;
				}
			} else if ($fdefault !== false && !$fnoquote) {
				if ($ty == 'C' or $ty == 'X' or
					( substr($fdefault,0,1) != "'" && !is_numeric($fdefault))) {

					if (($ty == 'D' || $ty == 'T') && strtolower($fdefault) != 'null') {
						// convert default date into database-aware code
						if ($ty == 'T')
						{
							$fdefault = $this->connection->dbTimeStamp($fdefault);
						}
						else
						{
							$fdefault = $this->connection->dbDate($fdefault);
						}
					}
					else
					if (strlen($fdefault) != 1 && substr($fdefault,0,1) == ' ' && substr($fdefault,strlen($fdefault)-1) == ' ')
						$fdefault = trim($fdefault);
					else if (strtolower($fdefault) != 'null')
						$fdefault = $this->connection->qstr($fdefault);
				}
			}
			$suffix = $this->_createSuffix($fname,$ftype,$fnotnull,$fdefault,$fautoinc,$fconstraint,$funsigned);

			// add index creation
			if ($widespacing) $fname = str_pad($fname,24);

			 // check for field names appearing twice
            if (array_key_exists($fid, $lines)) {
            	 ADOConnection::outp("Field '$fname' defined twice");
            }

			$lines[$fid] = $fname.' '.$ftype.$suffix;

			if ($fautoinc) $this->autoIncrement = true;
		} // foreach $flds

		return array($lines,$pkey,$idxs);
	}

	/**
		 GENERATE THE SIZE PART OF THE DATATYPE
			$ftype is the actual type
			$ty is the type defined originally in the DDL
	*/
		/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	function _getSize($ftype, $ty, $fsize, $fprec, $options=false)
	{
		if (strlen($fsize) && $ty != 'X' && $ty != 'B' && strpos($ftype,'(') === false) {
			$ftype .= "(".$fsize;
			if (strlen($fprec)) $ftype .= ",".$fprec;
			$ftype .= ')';
		}
		
		/*
		* Handle additional options
		*/
		if (is_array($options))
		{
			foreach($options as $type=>$value)
			{
				switch ($type)
				{
					case 'ENUM':
					$ftype .= '(' . $value . ')';
					break;
					
					default:
				}
			}
		}
		
		return $ftype;
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
	protected function _createSuffix(
				string $fname,
				string &$ftype,
				bool $fnotnull,
				string $fdefault,
				bool $fautoinc,
				string $fconstraint,
				string $funsigned) : string	{
					
		$suffix = '';
		if (strlen($fdefault)) $suffix .= " DEFAULT $fdefault";
		if ($fnotnull) $suffix .= ' NOT NULL';
		if ($fconstraint) $suffix .= ' '.$fconstraint;
		return $suffix;
	}

	/**
	* Creates an sql statement to build an index
	*
	* @param str $idxname index name
	* @param str $tabname table name
	* @param array $flds
	* @param array $idxoptions
	*
	* @return array
	*/
	protected function _indexSQL(
				string $idxname, 
				string $tabname, 
				array $flds, 
				array $idxoptions) : array	{
					
		$sql = array();

		if ( isset($idxoptions['REPLACE']) || isset($idxoptions['DROP']) ) {
			$sql[] = sprintf ($this->dropIndex, $idxname);
			if ( isset($idxoptions['DROP']) )
				return $sql;
		}

		if ( empty ($flds) ) {
			return $sql;
		}

		$unique = isset($idxoptions['UNIQUE']) ? ' UNIQUE' : '';

		$s = 'CREATE' . $unique . ' INDEX ' . $idxname . ' ON ' . $tabname . ' ';

		if ( isset($idxoptions[$this->upperName]) )
			$s .= $idxoptions[$this->upperName];

		if ( is_array($flds) )
			$flds = implode(', ',$flds);
		$s .= '(' . $flds . ')';
		$sql[] = $s;

		return $sql;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/ 
	protected function _dropAutoIncrement(string $tabname) : bool
	{
		return false;
	}

	/**
	* Function not described
	*
	* @param str $tabname
	* @param str $lines
	* @param str $pkey
	* @param array $tableoptions
	*
	* @return array
	*/
	protected function _tableSQL(
				string $tabname,
				array $lines,
				array $pkey,
				array $tableoptions) : array {
					
		$sql = array();

		if (isset($tableoptions['REPLACE']) || isset ($tableoptions['DROP'])) {
			$sql[] = sprintf($this->dropTable,$tabname);
			if ($this->autoIncrement) {
				$sInc = $this->_dropAutoIncrement($tabname);
				if ($sInc) $sql[] = $sInc;
			}
			if ( isset ($tableoptions['DROP']) ) {
				return $sql;
			}
		}
		
		$s = "CREATE TABLE $tabname (\n";
		$s .= implode(",\n", $lines);
		if (sizeof($pkey)>0) {
			$s .= ",\n                 PRIMARY KEY (";
			$s .= implode(", ",$pkey).")";
		}
		if (isset($tableoptions['CONSTRAINTS']))
			$s .= "\n".$tableoptions['CONSTRAINTS'];

		if (isset($tableoptions[$this->upperName.'_CONSTRAINTS']))
			$s .= "\n".$tableoptions[$this->upperName.'_CONSTRAINTS'];

		$s .= "\n)";
		if (isset($tableoptions[$this->upperName])) 
			$s .= $tableoptions[$this->upperName];
		
		$sql[] = $s;

		return $sql;
	}

	/**
	* GENERATE TRIGGERS IF NEEDED
	* used when table has auto-incrementing field that is emulated using triggers
	*
	* @param str $tabname
	* @param str $taboptions
	*
	* @return
	*/
	protected function _triggers(
			string $tabname,
			array $taboptions) : array 	{
				
		return array();
	}

	/**
	*	Sanitize options, so that array elements with no keys are promoted to keys
	*
	* @param str $opts
	*
	* @return array
	*/
	protected function _options($opts) : array
	{
		if (!is_array($opts)) return array();
		$newopts = array();
		foreach($opts as $k => $v) {
			if (is_numeric($k)) $newopts[strtoupper($v)] = $v;
			else $newopts[strtoupper($k)] = $v;
		}
		return $newopts;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	protected function _getSizePrec(string $size) : array {
		
		$fsize = false;
		$fprec = false;
		$dotat = strpos($size,'.');
		if ($dotat === false) $dotat = strpos($size,',');
		if ($dotat === false) $fsize = $size;
		else {
			$fsize = substr($size,0,$dotat);
			$fprec = substr($size,$dotat+1);
		}
		return array($fsize, $fprec);
	}

	/**
	* Change an existing table
	*
	* @param str $tablename
	* @param str $flds
	* @param array $tableoptions
	* @param bool $dropOldFields
	*
	* @return str
	*/
	public function changeTableSQL(
				string $tablename, 
				string $flds, 
				?array $tableoptions = null, 
				bool $dropOldFlds=false) : array {
	
		$this->connection->setFetchMode(ADODB_FETCH_ASSOC);

		// check table exists
		$save_handler = $this->connection->raiseErrorFn;
		$this->connection->raiseErrorFn = '';
		$cols = $this->metaColumns($tablename);
		$this->connection->raiseErrorFn = $save_handler;

		$this->connection->setFetchMode($this->connection->coreFetchMode);

		if ( empty($cols)) {
			return $this->createTableSQL($tablename, $flds, $tableoptions);
		}

		if (is_array($flds)) {
		// Cycle through the update fields, comparing
		// existing fields to fields to update.
		// if the Metatype and size is exactly the
		// same, ignore - by Mark Newham
			$holdflds = array();
			foreach($flds as $k=>$v) {
				if ( isset($cols[$k]) && is_object($cols[$k]) ) {
					// If already not allowing nulls, then don't change
					$obj = $cols[$k];
					if (isset($obj->not_null) && $obj->not_null)
						$v = str_replace('NOT NULL','',$v);
					if (isset($obj->auto_increment) && $obj->auto_increment && empty($v['AUTOINCREMENT']))
					    $v = str_replace('AUTOINCREMENT','',$v);

					$c = $cols[$k];
					$ml = $c->max_length;
					$mt = $this->metaType($c->type,$ml);

					if (isset($c->scale)) $sc = $c->scale;
					else $sc = 99; // always force change if scale not known.

					if ($sc == -1) $sc = false;
					list($fsize, $fprec) = $this->_getSizePrec($v['SIZE']);

					if ($ml == -1) $ml = '';
					if ($mt == 'X') $ml = $v['SIZE'];
					if (($mt != $v['TYPE']) || ($ml != $fsize || $sc != $fprec) || (isset($v['AUTOINCREMENT']) && $v['AUTOINCREMENT'] != $obj->auto_increment)) {
						$holdflds[$k] = $v;
					}
				} else {
					$holdflds[$k] = $v;
				}
			}
			$flds = $holdflds;
		}


		// already exists, alter table instead
		list($lines,$pkey,$idxs) = $this->_genFields($flds);
		// genfields can return FALSE at times
		if ($lines == null) $lines = array();
		$alter = 'ALTER TABLE ' . $this->tableName($tablename);
		$sql = array();

		foreach ( $lines as $id => $v ) {
			if ( isset($cols[$id]) && is_object($cols[$id]) ) {

				$flds = $this->parseArgs($v,',');

				//  We are trying to change the size of the field, if not allowed, simply ignore the request.
				// $flds[1] holds the type, $flds[2] holds the size -postnuke addition
				if ($flds && in_array(strtoupper(substr($flds[0][1],0,4)),$this->invalidResizeTypes4)
				 && (isset($flds[0][2]) && is_numeric($flds[0][2]))) {
					if ($this->debug) ADOConnection::outp(sprintf("<h3>%s cannot be changed to %s currently</h3>", $flds[0][0], $flds[0][1]));
					#echo "<h3>$this->alterCol cannot be changed to $flds currently</h3>";
					continue;
	 			}
				$sql[] = $alter . $this->alterCol . ' ' . $v;
			} else {
				$sql[] = $alter . $this->addCol . ' ' . $v;
			}
		}

		if ($dropOldFlds) {
			foreach ( $cols as $id => $v )
			    if ( !isset($lines[$id]) )
					$sql[] = $alter . $this->dropCol . ' ' . $v->name;
		}
		return $sql;
	}
	
	/**
	* Parse arguments, treat "text" (text) and 'text' as quotation marks.
	* To escape, use "" or '' or ))
    *
	* Will read in "abc def" sans quotes, as: abc def
	* Same with 'abc def'.
	* However if `abc def`, then will read in as `abc def`
    *
	* @param endstmtchar    Character that indicates end of statement
	* @param tokenchars     Include the following characters in tokens apart from A-Z and 0-9
	*
	* @returns 2 dimensional array containing parsed tokens.
	*/
	protected function parseArgs(
				string $args,
				string $endstmtchar=',',
				string $tokenchars='_.-') : array {
					
		$pos = 0;
		$intoken = false;
		$stmtno = 0;
		$endquote = false;
		$tokens = array();
		$tokens[$stmtno] = array();
		$max = strlen($args);
	  	$quoted = false;
		$tokarr = array();

		while ($pos < $max) {
			$ch = substr($args,$pos,1);
			switch($ch) {
			case ' ':
			case "\t":
			case "\n":
			case "\r":
				if (!$quoted) {
					if ($intoken) {
						$intoken = false;
						$tokens[$stmtno][] = implode('',$tokarr);
					}
					break;
				}

				$tokarr[] = $ch;
				break;

			case '`':
				if ($intoken) $tokarr[] = $ch;
			case '(':
			case ')':
			case '"':
			case "'":

				if ($intoken) {
					if (empty($endquote)) {
						$tokens[$stmtno][] = implode('',$tokarr);
						if ($ch == '(') $endquote = ')';
						else $endquote = $ch;
						$quoted = true;
						$intoken = true;
						$tokarr = array();
					} else if ($endquote == $ch) {
						$ch2 = substr($args,$pos+1,1);
						if ($ch2 == $endquote) {
							$pos += 1;
							$tokarr[] = $ch2;
						} else {
							$quoted = false;
							$intoken = false;
							$tokens[$stmtno][] = implode('',$tokarr);
							$endquote = '';
						}
					} else
						$tokarr[] = $ch;

				}else {

					if ($ch == '(') $endquote = ')';
					else $endquote = $ch;
					$quoted = true;
					$intoken = true;
					$tokarr = array();
					if ($ch == '`') $tokarr[] = '`';
				}
				break;

			default:

				if (!$intoken) {
					if ($ch == $endstmtchar) {
						$stmtno += 1;
						$tokens[$stmtno] = array();
						break;
					}

					$intoken = true;
					$quoted = false;
					$endquote = false;
					$tokarr = array();

				}

				if ($quoted) $tokarr[] = $ch;
				else if (ctype_alnum($ch) || strpos($tokenchars,$ch) !== false) $tokarr[] = $ch;
				else {
					if ($ch == $endstmtchar) {
						$tokens[$stmtno][] = implode('',$tokarr);
						$stmtno += 1;
						$tokens[$stmtno] = array();
						$intoken = false;
						$tokarr = array();
						break;
					}
					$tokens[$stmtno][] = implode('',$tokarr);
					$tokens[$stmtno][] = $ch;
					$intoken = false;
				}
			}
			$pos += 1;
		}
		if ($intoken) $tokens[$stmtno][] = implode('',$tokarr);

		return $tokens;
	}
} // class
