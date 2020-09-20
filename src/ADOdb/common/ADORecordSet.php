<?php
/**
* The ADORecordset Class
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
abstract class ADORecordSet implements \IteratorAggregate
{

	/**
	 * public variables
	 */
	var $dataProvider = "native";
	
	/* 
	* holds the current row data
	*/
	public ?array $fields = null;
	
	/*
	* any varchar/char field this size or greater is treated as 
	* a blobin other words, we use a text area for editing.
	*/
	protected int $blobSize = 100;	

	/*
	* indicates that seeking is supported by the 
	* database driver::data_seek
	*/
	protected bool $canSeek = false;	
	
	/*
	* sql text
	*/
	public string $sql;
	
	/* 
	* Indicates that the current record position is after the 
	* last record in a Recordset object.
	*/
	public bool $EOF = false;		

	//var $emptyTimeStamp = '&nbsp;'; /// what to display when $time==0
	//var $emptyDate = '&nbsp;'; /// what to display when $time==0
	//var $debug = false;
	
	/* 
	* datetime in Unix format rs created -- for cached recordsets
	*/
	public int $timeCreated = 0;

	protected ?array $bind = null;		/// used by Fields() to hold array - should be private?
	
	protected object $connection; /// the parent connection

	/**
	 *	private variables
	 */
	/*
	 * Holds a cached version of the metadata, retrieved
	 * on first call of a recordset load. 
	 */
	
	/*
	* Holds the metadata by index
	*/
	protected ?array $fieldObjectsByIndex = null;

	/*
	 * Flags if we have retrieved the metadata
	 */
	protected bool $fieldObjectsRetrieved = false;
	
	/*
	* Fast xref indexing for upper,lower and native casing
	* Gets the name=>index xref
	*/
	protected array $fieldObjectNativeXref = array();
	protected array $fieldObjectUpperXref  = array();
	protected array $fieldObjectLowerXref  = array();
	/*
	* Needs access from paging functions
	*/
	public int $_numOfRows = -1;	/** number of rows, or -1 */

	protected int $_numOfFields = -1;	/** number of fields in recordset */
	protected $_queryID = -1;		/** This variable keeps the result link identifier.	*/
	protected int $_currentRow = -1;	/** This variable keeps the current row in the Recordset.	*/
	protected bool $_closed = false;	/** has recordset been closed */
	protected bool $_inited = false;	/** Init() should only be called once */
	
	
	protected object $_obj;				/** Used by FetchObj */
	protected array  $_names;			/** Used by FetchObj */

	/*
	* Recordses pagination page number
	*/
	protected int $_currentPage = -1;
	
	/*
	* At first page of recordset pagination 
	*/
	protected bool $_atFirstPage = false;	
	
	/*
	* At last page of recordset pagination 
	*/
	protected bool $_atLastPage = false;	
	
	/*
	* Pagination last page number
	*/
	protected int $_lastPageNo = -1;
	
	/*
	* Pagination max record count
	*/
	protected int $_maxRecordCount = 0;
	
	/*
	* Pagination rows per page
	*/
	protected int $rowsPerPage = 0;
	
	
	/****** ORACLE ONLY ***********/
	protected $datetime = false;

	/**
	 * Constructor
	 *
	 * @param $queryID this is the queryID returned by ADOConnection->_query()
	 *
	 */
	public function __construct(?object $queryID,object $connection)
	{
		$this->_queryID = $queryID;
		$this->connection = $connection;
	}

	/**
	* Class destructor closes off recordset connections
	*
	* Closing the class too early often causes problems
	*
	* @return void
	*/
	public function __destruct() {
		$this->close();
	}
	
	/**
	* Function not described
	*
	* @return object
	*/
	public function getIterator() : object {
		return new \ADOdb\common\ADOIterator($this);
	}

	/**
	* Sort of legacy constructor
	*
	* @return void
	*/
	public function init() : void {
		
		if ($this->_inited) {
			return;
		}
		$this->_inited = true;
		
		
		if ($this->_queryID) {
			$this->_initrs();
		} else {
			$this->_numOfRows = 0;
			$this->_numOfFields = 0;
		}
		if ($this->_numOfRows != 0 && $this->_numOfFields && $this->_currentRow == -1) {
			$this->_currentRow = 0;
			if ($this->EOF = ($this->_fetch() === false)) {
				$this->_numOfRows = 0; // _numOfRows could be -1on _
			}
		} else {
			$this->EOF = true;
		}
	}

	/**
	* Returns: an object containing field information.
	*
	* Get column information in the Recordset object. fetchField()
	* can be used in order to obtain information about fields in a
	* certain query result. If the field offset isn't specified,
	* the next field that wasn't yet retrieved by fetchField()
	* is retrieved.
	*
	* $param int $fieldOffset (optional default=-1 for all
	* @return mixed an ADOFieldObject, or array of objects
	*/
	protected function _fetchField($fieldOffset = -1)
	{
		if (!$this->fieldObjectsRetrieved)
			$this->fetchFields;
		
		if (!$this->fieldObjectsByIndex)
			return null;
		
				
		if ($fieldOffset == -1)
			return $this->fieldObjectsByIndex;
		else
			return $this->fieldObjectsByIndex[$fieldOffset];
		
	}

	/**
	 * Generate a SELECT tag string from a recordset, and return the string.
	 * If the recordset has 2 cols, we treat the 1st col as the containing
	 * the text to display to the user, and 2nd col as the return value. Default
	 * strings are compared with the FIRST column.
	 *
	 * @param name			name of SELECT tag
	 * @param [defstr]		the value to hilite. Use an array for multiple hilites for listbox.
	 * @param [blank1stItem]	true to leave the 1st item in list empty
	 * @param [multiple]		true for listbox, false for popup
	 * @param [size]		#rows to show for listbox. not used by popup
	 * @param [selectAttr]		additional attributes to defined for SELECT tag.
	 *				useful for holding javascript onChange='...' handlers.
	 & @param [compareFields0]	when we have 2 cols in recordset, we compare the defstr with
	 *				column 0 (1st col) if this is true. This is not documented.
	 *
	 * @return HTML
	 *
	 * changes by glen.davies@cce.ac.nz to support multiple hilited items
	 */
	public function getMenu(
			string $name,
			string $defstr='',
			bool $blank1stItem=true,
			bool $multiple=false,
			int $size=0, 
			string $selectAttr='',
			bool $compareFields0=true) : string
	{
		
		$menu = new \ADOdb\addons\ADOMenuBuilder($this);
		return $menu->getMenu($name,$defstr,$blank1stItem,$multiple,
			$size, $selectAttr,$compareFields0);
	}

	/**
	 * Generate a SELECT tag string from a recordset, and return the string.
	 * If the recordset has 2 cols, we treat the 1st col as the containing
	 * the text to display to the user, and 2nd col as the return value. Default
	 * strings are compared with the SECOND column.
	 *
	 */
	public function getMenu2(
			string $name,
			string $defstr='',
			bool $blank1stItem=true,
			bool $multiple=false,
			int $size=0, 
			string $selectAttr='') : string {
				
		$menu = new \ADOdb\addons\ADOMenuBuilder($this);
		return $menu->getMenu2($name,$defstr,$blank1stItem,$multiple,
			$size, $selectAttr);
	}

	/*
		Grouped Menu
	*/
	public function getMenu3(
				string $name,
				string $defstr='',
				bool $blank1stItem=true,
				bool $multiple=false,
				int $size=0, 
				string $selectAttr='')
	{
		$menu = new \ADOdb\addons\ADOMenuBuilder($this);
		return $menu->getMenu3($name,$defstr,$blank1stItem,$multiple,$size,$selectAttr);
	}

	/**
	 * return recordset as a 2-dimensional array.
	 *
	 * @param [nRows]  is the number of rows to return. -1 means every row.
	 *
	 * @return an array indexed by the rows (0-based) from the recordset
	 */
	public function getArray(int $nRows = -1) : array {
		$results = array();
		$cnt = 0;
		while (!$this->EOF && $nRows != $cnt) {
			$results[] = $this->fields;
			$this->moveNext();
			$cnt++;
		}
		return $results;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	public function getAll(int $nRows = -1) : array {
		$arr = $this->getArray($nRows);
		return $arr;
	}

	/*
	* Some databases allow multiple recordsets to be returned. This function
	* will return true if there is a next recordset, or false if no more.
	*/
	public function nextRecordSet() : bool {
		return false;
	}

	/**
	 * return recordset as a 2-dimensional array.
	 * Helper function for ADOConnection->SelectLimit()
	 *
	 * @param offset	is the row to start calculations from (1-based)
	 * @param [nrows]	is the number of rows to return
	 *
	 * @return an array indexed by the rows (0-based) from the recordset
	 */
	public function getArrayLimit(int $nrows, int $offset=-1) : array {
		if ($offset <= 0) {
			$arr = $this->GetArray($nrows);
			return $arr;
		}

		$this->move($offset);

		$results = array();
		$cnt = 0;
		while (!$this->EOF && $nrows != $cnt) {
			$results[$cnt++] = $this->fields;
			$this->moveNext();
		}

		return $results;
	}

	/**
	 * return whole recordset as a 2-dimensional associative array if
	 * there are more than 2 columns. The first column is treated as the
	 * key and is not included in the array. If there is only 2 columns,
	 * it will return a 1 dimensional array of key-value pairs unless
	 * $force_array == true. This recordset method is currently part of
	 * the API, but may not be in later versions of ADOdb. By preference, use
	 * ADOconnnection::getAssoc()
	 *
	 * @param bool	$force_array	(optional) Has only meaning if we have 2 data
	 *								columns. If false, a 1 dimensional
	 * 								array is returned, otherwise a 2 dimensional
	 *								array is returned. If this sounds confusing,
	 * 								read the source.
	 *
	 * @param bool	$first2cols 	(optional) Means if there are more than
	 *								2 cols, ignore the remaining cols and
	 * 								instead of returning
	 *								array[col0] => array(remaining cols),
	 *								return array[col0] => col1
	 *
	 * @return mixed
	 *
	 */
	public function getAssoc(
		bool $force_array = false, 
		bool $first2cols = false) : array
	{
		
		/*
		* Insufficient rows to show data
		*/
		if ($this->_numOfFields < 2)
			  return array();

		/*
		* Empty recordset
		*/
		if (!$this->fields) {
			return array();
		}

		$numberOfFields = $this->_numOfFields;
		$fetchMode      = $this->connection->connectionDefinitions->fetchMode;
		$assocCase		= $this->connection->connectionDefinitions->assocCase;
		
		if ($fetchMode == $this->connection::ADODB_FETCH_BOTH)
		{
			/*
			* build a template of numeric keys. you could improve the
			* speed by caching this, indexed by number of keys
			*/
			$testKeys = array_fill(0,$numberOfFields,0);

			/*
			* We use the associative method if ADODB_FETCH_BOTH
			*/
			$fetchMode = $this->connection::ADODB_FETCH_ASSOC;
		}

		$showArrayMethod = 0;

		if ($numberOfFields == 2)
			/*
			* Key is always value of first element
			* Value is alway value of second element
			*/
			$showArrayMethod = 1;

		if ($force_array)
			$showArrayMethod = 0;

		if ($first2cols)
			$showArrayMethod = 1;

		$results  = array();

		while (!$this->EOF){

			$myFields = $this->fields;

			if ($fetchMode == $this->connection::ADODB_FETCH_BOTH)
			{
				/*
				* extract the associative keys
				*/
				$myFields = array_diff_key($myFields,$testKeys);
			}

			/*
			* key is value of first element, rest is data,
			* The key is not case processed
			*/
			$key = array_shift($myFields);
			
			switch ($showArrayMethod)
			{
			case 0:

				if ($fetchMode == $this->connection::ADODB_FETCH_ASSOC)
				{
					/*
					* The driver should have already handled the key
					* casing, but in case it did not. We will check and force
					* this in later versions of ADOdb
					*/
					if ($assocCase == $this->connection::ADODB_ASSOC_CASE_UPPER)
						$myFields = array_change_key_case($myFields,CASE_UPPER);

					elseif ($assocCase == $this->connection::ADODB_ASSOC_CASE_LOWER)
						$myFields = array_change_key_case($myFields,CASE_LOWER);

					/*
					* We have already shifted the key off
					* the front, so the rest is the value
					*/
					$results[$key] = $myFields;

				}
				else
					/*
					 * I want the values in a numeric array,
					 * nicely re-indexed from zero
					 */
					$results[$key] = array_values($myFields);
				break;

			case 1:

				/*
				 * Don't care how long the array is,
				 * I just want value of second column, and it doesn't
				 * matter whether the array is associative or numeric
				 */
				$results[$key] = array_shift($myFields);
				break;
			}

			$this->moveNext();
		}
		/*
		 * Done
		 */
		return $results;
	}

	/**
	 *
	 * @param v		is the character timestamp in YYYY-MM-DD hh:mm:ss format
	 * @param fmt	is the format to apply to it, using date()
	 *
	 * @return a timestamp formated as user desires
	 */
	public function userTimeStamp(
				string $v,
				string $fmt='Y-m-d H:i:s') : string {
					
		$dateTimeDefinitions = $this->connection->connectionDefinitions->dateTimeDefinitions;
		$dateTimeClass = new \ADOdb\time\ADODateTime($dateTimeDefinitions);
		if (is_numeric($v) && strlen($v)<14) {
			return $dateTimeClass->adodb_date($fmt,$v);
		}
		
		$tt = $this->unixTimeStamp($v);
		// $tt == -1 if pre TIMESTAMP_FIRST_YEAR
		if (($tt === false || $tt == -1) && $v != false) {
			return $v;
		}
		if ($tt === 0) {
			return $dateTimeDefinitions->emptyTimeStamp;
		}
		return $dateTimClass->adodb_date($fmt,$tt);
	}


	/**
	 * @param v		is the character date in YYYY-MM-DD format, returned by database
	 * @param fmt	is the format to apply to it, using date()
	 *
	 * @return a date formated as user desires, via the
	 * database functions
	 */
	final public function userDate(
			string $v,
			string $fmt='Y-m-d') : string
	{
		$dClass = $this->connection->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dClass($this->connection);
		return $dt->userDate($v,$fmt);
	}


	/**
	 * @param $v is a date string in YYYY-MM-DD format
	 *
	 * @return date in unix timestamp format, or 0 if before TIMESTAMP_FIRST_YEAR, or false if invalid date format
	 */
	final public function unixDate(string $v) : string {
		$dClass = $this->connection->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dClass($this->connection);
		return $dt->unixDate($v,$fmt);
	}

	/**
	 * @param $v is a timestamp string in YYYY-MM-DD HH-NN-SS format
	 *
	 * @return date in unix timestamp format, or 0 if before TIMESTAMP_FIRST_YEAR, or false if invalid date format
	 */
	final public function unixTimeStamp(string $v) : string
	{
		$dClass = $this->connection->driverPath . 'ADODbDateTimeFunctions';
		$dt = new $dClass($this->connection);
		
		return $dt->unixTimeStamp($v);
	}

	/**
	* Fetch a row, returning false if no more rows.
	* This is PEAR DB compat mode.
	*
	* @return false or array containing the current record
	*/
	final public function fetchRow() 
	{
		if ($this->EOF) {
			return false;
		}
		$arr = $this->fields;
		$this->_currentRow++;
		if (!$this->_fetch()) {
			$this->EOF = true;
		}
		return $arr;
	}

	/**
	* Fetch a row into a passed by reference array, 
	* returning PEAR_Error if no more rows.
	*
	* @return DB_OK or error object
	*/
	final public function fetchInto(array &$arr) : int 
	{
		if ($this->EOF) {
			return 0;
		}
		$arr = $this->fields;
		$this->moveNext();
		return 1; // DB_OK
	}


	/**
	* Move to the first row in the recordset. Many databases do
	* NOT support this.
	*
	* @return true or false
	*/
	final public function moveFirst() : bool{
		if ($this->_currentRow == 0) {
			return true;
		}
		return $this->move(0);
	}


	/**
	 * Move to the last row in the recordset.
	 *
	 * @return true or false
	 */
	final public function moveLast() : bool {
		if ($this->_numOfRows >= 0) {
			return $this->move($this->_numOfRows-1);
		}
		if ($this->EOF) {
			return false;
		}
		while (!$this->EOF) {
			$f = $this->fields;
			$this->moveNext();
		}
		$this->fields = $f;
		$this->EOF = false;
		return true;
	}


	/**
	* Move to next record in the recordset.
	*
	* @return true if there still rows available, or false if there are no more rows (EOF).
	*/
	public function moveNext() : bool {
		if (!$this->EOF) {
			$this->_currentRow++;
			if ($this->_fetch()) {
				return true;
			}
		}
		$this->EOF = true;
		/* -- tested error handling when scrolling cursor -- seems useless.
		$conn = $this->connection;
		if ($conn && $conn->raiseErrorFn && ($errno = $conn->ErrorNo())) {
			$fn = $conn->raiseErrorFn;
			$fn($conn->databaseType,'MOVENEXT',$errno,$conn->ErrorMsg().' ('.$this->sql.')',$conn->host,$conn->database);
		}
		*/
		return false;
	}


	/**
	* Random access to a specific row in the recordset. Some 
	* databases do not support access to previous rows in the
	* databases (no scrolling backwards).
	*
	* @param rowNumber is the row to move to (0-based)
	*
	* @return true if there still rows available, or false if there are no more rows (EOF).
	*/
	final public function move(int $rowNumber = 0) : bool {
		$this->EOF = false;
		if ($rowNumber == $this->_currentRow) {
			return true;
		}
		if ($rowNumber >= $this->_numOfRows) {
			if ($this->_numOfRows != -1) {
				$rowNumber = $this->_numOfRows-2;
			}
		}

		if ($rowNumber < 0) {
			$this->EOF = true;
			return false;
		}

		if ($this->canSeek) {
			if ($this->_seek($rowNumber)) {
				$this->_currentRow = $rowNumber;
				if ($this->_fetch()) {
					return true;
				}
			} else {
				$this->EOF = true;
				return false;
			}
		} else {
			if ($rowNumber < $this->_currentRow) {
				return false;
			}
			while (! $this->EOF && $this->_currentRow < $rowNumber) {
				$this->_currentRow++;

				if (!$this->_fetch()) {
					$this->EOF = true;
				}
			}
			return !($this->EOF);
		}

		$this->fields = false;
		$this->EOF = true;
		return false;
	}


	/**
	 * Get the value of a field in the current row by column name.
	 * Will not work if ADODB_FETCH_MODE is set to ADODB_FETCH_NUM.
	 *
	 * @param colname  is tgetrowas
	 he field to access
	 *
	 * @return the value of $colname column
	 */
	public function fields(string $colname) : ?string {
		return $this->fields[$colname];
	}
	
	/*
	* Fetches the current row, but does not update
	* the current record pointer
	*
	* @return array
	*/
	final public function fetchFields() : array {
		return $this->fields;
	}

	/**
	 * Defines the function to use for table fields case conversion
	 * depending on ADODB_ASSOC_CASE
	 * @return string strtolower/strtoupper or false if no conversion needed
	 */
	final protected function assocCaseConvertFunction(int $case = -1) : string
	{
		switch($case) {
			case $this->connection::ADODB_ASSOC_CASE_UPPER:
				return 'strtoupper';
			case $this->connection::ADODB_ASSOC_CASE_LOWER:
				return 'strtolower';
			case $this->connection::ADODB_ASSOC_CASE_NATIVE:
			default:
				return false;
		}
	}

	/**
	 * Builds the bind array associating keys to recordset fields
	 *
	 * @param int $upper Case for the array keys, defaults to uppercase
	 *                   (see ADODB_ASSOC_CASE_xxx constants)
	 */
	final protected function getAssocKeys(int $assocKeys = 2) : void {
		if ($this->bind) {
			return;
		}
		$this->bind = array();

		print "CALL GETASSOCKEYS"; exit;
		// Define case conversion function for ASSOC fetch mode
		$fn_change_case = $this->AssocCaseConvertFunction($assocKeys);

		// Build the bind array
		for ($i=0; $i < $this->_numOfFields; $i++) {
			$o = $this->fetchField($i);

			// Set the array's key
			if(is_numeric($o->name)) {
				// Just use the field ID
				$key = $i;
			}
			elseif( $fn_change_case ) {
				// Convert the key's case
				$key = $fn_change_case($o->name);
			}
			else {
				$key = $o->name;
			}

			$this->bind[$key] = $i;
		}
	}

		/**
	* Returns a row as associative if the fetch mode is numeric
	*
	* @param int $assocCase	The casing of keys of the returned data 
	*
	* @return array
	*/
	public function getRowAssoc(int $assocCase = 2) : ?array {

		$record = array();
		$this->getAssocKeys($assocCase);
		
			
		foreach($this->bind as $k => $v) {
			if( array_key_exists( $v, $this->fields ) ) {
				$record[$k] = $this->fields[$v];
			} elseif( array_key_exists( $k, $this->fields ) ) {
				$record[$k] = $this->fields[$k];
			} else {
				# This should not happen... trigger error ?
				$record[$k] = null;
			}
		}
		return $record;
	}

	/**
	 * Clean up recordset
	 *
	 * @return true or false
	 */
	public function close() : bool {
		// free connection object - this seems to globally free the object
		// and not merely the reference, so don't do this...
		// $this->connection = false;
		if (!$this->_closed) {
			$this->_closed = true;
			return $this->_close();
		} else
			return true;
	}

	/**
	 * synonyms Record Count and RowCount
	 *
	 * @return the number of rows or -1 if this is not supported
	 */
	final public function recordCount() : int {
		return $this->_numOfRows;
	}
	
	/**
	 * externally sets the record count
	 *
	 * @return void
	 */
	final public function setRecordCount(int $count) : void {
		$this->_numOfRows = $count;
	}


	/*
	* If we are using PageExecute(), this will return the maximum possible rows
	* that can be returned when paging a recordset.
	*/
	final public function maxRecordCount() : int {
		return ($this->_maxRecordCount) ? $this->_maxRecordCount : $this->recordCount();
	}

	/**
	 * synonyms RecordCount and RowCount
	 *
	 * @return the number of rows or -1 if this is not supported
	 */
	final public function rowCount() : int {
		return $this->_numOfRows;
	}

	/**
	* Portable RecordCount. Pablo Roca <pabloroca@mvps.org>
	*
	* @return  the number of records from a previous SELECT. All databases upport this.
	*
	* But aware possible problems in multiuser environments. For better speed the table
	* must be indexed by the condition. Heavy test this before deploying.
	*/
	public function po_recordCount(
		string $table="",
		string $condition="") : int {

		$lnumrows = $this->_numOfRows;
		// the database doesn't support native recordcount, so we do a workaround
		if ($lnumrows == -1 && $this->connection) {
			if ($table) {
				if ($condition) {
					$condition = " WHERE " . $condition;
				}
				$resultrows = $this->connection->Execute("SELECT COUNT(*) FROM $table $condition");
				if ($resultrows) {
					$lnumrows = reset($resultrows->fields);
				}
			}
		}
		return $lnumrows;
	}


	/**
	 * @return the current row in the recordset. If at EOF, will return the last row. 0-based.
	 */
	final public function currentRow() : array {
		return $this->_currentRow;
	}

	/**
	 * @return the number of columns in the recordset. Some databases will set this to 0
	 * if no records are returned, others will return the number of columns in the query.
	 */
	final public function fieldCount() : int {
		return $this->_numOfFields;
	}


	/**
	* Get the ADOFieldObject of a specific column.
	*
	* @param fieldoffset	is the column position to access(0-based).
	*
	* @return the ADOFieldObject for that column, or null or an array.
	*/
	abstract public function fetchField(int $fieldOffset = -1);

	/**
	 * Get the ADOFieldObjects of all columns in an array.
	 *
	 * @return object
	 */
	final public function fieldTypesArray() : array {
		static $arr = array();
		if (empty($arr)) {
			for ($i=0, $max=$this->_numOfFields; $i < $max; $i++) {
				$arr[] = $this->FetchField($i);
			}
		}
		return $arr;
	}

	/**
	* Return the fields array of the current row as an object for convenience.
	* The default case is lowercase field names.
	*
	* @return the object with the properties set to the fields of the current row
	*/
	final public function fetchObj() : object {
		$o = $this->fetchObject(false);
		return $o;
	}

	/**
	* Return the fields array of the current row as an object for convenience.
	* The default case is uppercase.
	*
	* @param $isupper to set the object property names to uppercase
	*
	* @return the object with the properties set to the fields of the current row
	*/
	final public function fetchObject(bool $isupper=true) : object {
		
		if (empty($this->_obj)) {
			$this->_obj = new ADOFetchObj();
			$this->_names = array();
			for ($i=0; $i <$this->_numOfFields; $i++) {
				$f = $this->FetchField($i);
				$this->_names[] = $f->name;
			}
		}
		$i = 0;
		$o = clone($this->_obj);

		for ($i=0; $i <$this->_numOfFields; $i++) {
			$name = $this->_names[$i];
			if ($isupper) {
				$n = strtoupper($name);
			} else {
				$n = $name;
			}

			$o->$n = $this->Fields($name);
		}
		return $o;
	}

	/**
	* Return the fields array of the current row as an object for convenience.
	* The default is lower-case field names.
	*
	* @return the object with the properties set to the fields of the current row,
	*	or false if EOF
	*
	*/
	final public function fetchNextObj() : object {
		$o = $this->fetchNextObject(false);
		return $o;
	}


	/**
	* Return the fields array of the current row as an object for convenience.
	* The default is upper case field names.
	*
	* @param $isupper to set the object property names to uppercase
	*
	* @return the object with the properties set to the fields of the current row,
	*	or false if EOF
	*/
	final public function FetchNextObject(bool $isupper=true) : object {
		$o = false;
		if ($this->_numOfRows != 0 && !$this->EOF) {
			$o = $this->fetchObject($isupper);
			$this->_currentRow++;
			if ($this->_fetch()) {
				return $o;
			}
		}
		$this->EOF = true;
		return $o;
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
	public function metaType(
			$t, 
			int $len = -1, 
			$fieldobj = false): string
	{
		
		$mtClass = $this->connection->driverPath . 'ADOMetaFunctions';
		$mt = new $mtClass($this->connection);
		return $mt->metaType($t,$len,$fieldobj);

	}

	/**
	* Convert case of field names associative array, if needed
	*
	* @return void
	*/
	protected function _updatefields() : void
	{
		if( empty($this->fields)) {
			return;
		}

		// Determine case conversion function
		$fn_change_case = $this->AssocCaseConvertFunction();
		if(!$fn_change_case) {
			// No conversion needed
			return;
		}

		$arr = array();

		// Change the case
		foreach($this->fields as $k => $v) {
			if (!is_integer($k)) {
				$k = $fn_change_case($k);
			}
			$arr[$k] = $v;
		}
		$this->fields = $arr;
	}
	
		/**
	* set/returns the current recordset page when paginating
	*
	* int $page
	*
	* @return int
	*/
	final public function absolutePage(int $page=-1) : int{
		if ($page != -1) {
			$this->_currentPage = $page;
		}
		return $this->_currentPage;
	}

	/**
	 * set/returns the status of the atFirstPage flag when paginating
	 *
	 * @param bool $status	Used as a setter
	 *
	 * @return bool
	 */
	final public function atFirstPage( bool $status=false) : bool {
		if ($status != false) {
			$this->_atFirstPage = $status;
		}
		return $this->_atFirstPage;
	}

	/**
	 * set/returns the value of the last page number when paginating
	 *
	 * @param int $page When Used as a setter
	 *
	 * @return int
	 */
	final public function lastPageNo(int $page=null) : int {
		
		if ($page != false) {
			$this->_lastPageNo = $page;
		}
		return $this->_lastPageNo;
	}

	/**
	 * set/returns the status of the atLastPage flag when paginating
	 *
	 * @param bool $status	Used as a setter
	 *
	 * @return bool
	 */
	final public function atLastPage(bool $status=false) : bool {
		if ($status != false) {
			$this->_atLastPage = $status;
		}
		return $this->_atLastPage;
	}
	
	/**
	 * sets the _maxRecordCount value when paginating
	 *
	 * @param int $count
	 */
	final public function setMaxRecordCount(int $count) : void {
		$this->_maxRecordCount = $count;
	}
	
	/**
	 * gets the _maxRecordCount value when paginating
	 *
	 * @return int $count
	 */
	final public function getMaxRecordCount() : int {
		return $this->_maxRecordCount;
	}
	/**
	 * sets the rowsperpage value when paginating
	 *
	 * @param int $count
	 */
	final public function setRowsPerPage(int $count) : void {
		$this->rowsPerPage = $count;
	}
	
	/**
	 * gets the rowsperpage value when paginating
	 *
	 * @return int $count
	 */
	final public function getRowsPerPage() : int {
		return $this->rowsPerPage;
	}
	
	/**
	 * sets the time created for cached recordsets
	 *
	 * @param int $timestamp
	 */
	final public function setTimeCreated(int $timestamp) : void {
		$this->timeCreated = $timestamp;
	}
	
	/**
	 * gets the time created for cached recordsets
	 *
	 * @return int $timeCreated
	 */
	final public function getTimeCreated() : int {
		return $this->timeCreated;
	}

	/**
	* Performs an internal close methodology
	*
	* @return bool
	*/
	protected function _close() : bool {}

	

} // end class ADORecordSet