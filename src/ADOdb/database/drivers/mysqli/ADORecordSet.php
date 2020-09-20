<?php
/**
* Full recordset for returned records for the mysqli driver
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADODb\database\drivers\mysqli;

class ADORecordSet extends \ADOdb\common\ADORecordSet{

	/*
	* Need to access this from ADOCacheMethods
	*/
	public string $databaseType = "mysqli";
	/*
	* indicates that seeking is supported by the 
	* database driver::data_see
	*/
	protected bool $canSeek = true;	
	

	protected $adodbFetchMode;

	public function __construct(?object $queryID, object $connection)
	{
		parent::__construct($queryID,$connection);
		
		switch ($connection->fetchMode) {
			case $connection::ADODB_FETCH_NUM:
				$this->fetchMode = MYSQLI_NUM;
				break;
			case $connection::ADODB_FETCH_ASSOC:
				$this->fetchMode = MYSQLI_ASSOC;
				break;
			case $connection::ADODB_FETCH_DEFAULT:
			case $connection::ADODB_FETCH_BOTH:
			default:
				$this->fetchMode = MYSQLI_BOTH;
				break;
		}
		$this->adodbFetchMode = $connection->fetchMode;
	}

	/**
	* Initializes the fields
	*
	* @return void
	*/	 	
	protected function _initrs() : void
	{

		$countRecords = $this->connection->connectionDefinitions->countRecords;
		$this->_numOfRows = $countRecords ? @mysqli_num_rows($this->_queryID) : -1;
		$this->_numOfFields = @mysqli_num_fields($this->_queryID);
		
		$this->_fetchFields();
		
	}
	
	/**
	* Returns: an array of objects containing field information.
	*
	* Get column information in the Recordset object. _fetchField()
	* returns information about all fields in a
	* certain query result. 
	*
	* @return void
	*/
	protected function _fetchFields() : void
	{
		if ($this->fieldObjectsRetrieved) {
			return;
		}
		
		$this->fieldObjectsRetrieved = true;
		/*
		 * Retrieve all metadata in one go. This is always returned as a
		 * numeric array.
		 */
		
		if ($this->_queryID instanceof ADORecordSet)
		{
			return;
		}
	
		$fieldMetaData = mysqli_fetch_fields($this->_queryID);
		
		if (!$fieldMetaData)
			/*
		     * Not a statement that gives us metaData
			 */
			return;

		foreach ($fieldMetaData as $myKey=>$myObject)
		{
			/*
			* Standardized return object
			*/
			$afo = new \ADOdb\common\ADOFieldObject;
			
			/*
			* Set core elements	
			*/
			foreach ($myObject as $k=>$v)
				$afo->$k = $v;
		
			if ( !isset($afo->flags) ) {
				$afo->flags = 0;
			}
			/*
			* Properties of an ADOFieldObject as set by MetaColumns 
			*/
			$afo->primary_key 	= $myObject->flags & MYSQLI_PRI_KEY_FLAG;
			$afo->not_null 		= $myObject->flags & MYSQLI_NOT_NULL_FLAG;
			$afo->auto_increment= $myObject->flags & MYSQLI_AUTO_INCREMENT_FLAG;
			$afo->binary 		= $myObject->flags & MYSQLI_BINARY_FLAG;
			// $o->blob = $o->flags & MYSQLI_BLOB_FLAG; /* not returned by MetaColumns */
			$afo->unsigned 		= $myObject->flags & MYSQLI_UNSIGNED_FLAG;
			
			/*
			 * Caution - keys are case-sensitive, must respect
			 * casing of values. we now have an available
			 * array of ADOfieldObjects that we don't
			 * need to iterate over every time we need one
			 */
					
			$this->fieldObjectsByIndex[$myKey] = $afo;

			$this->fieldObjectNativeXref[$myKey] = $myObject->name;
			$this->fieldObjectUpperXref[$myKey]  = strtoupper($myObject->name);
			$this->fieldObjectLowerXref[$myKey]  = strtolower($myObject->name);

		}
	}


/*
1      = MYSQLI_NOT_NULL_FLAG
2      = MYSQLI_PRI_KEY_FLAG
4      = MYSQLI_UNIQUE_KEY_FLAG
8      = MYSQLI_MULTIPLE_KEY_FLAG
16     = MYSQLI_BLOB_FLAG
32     = MYSQLI_UNSIGNED_FLAG
64     = MYSQLI_ZEROFILL_FLAG
128    = MYSQLI_BINARY_FLAG
256    = MYSQLI_ENUM_FLAG
512    = MYSQLI_AUTO_INCREMENT_FLAG
1024   = MYSQLI_TIMESTAMP_FLAG
2048   = MYSQLI_SET_FLAG
32768  = MYSQLI_NUM_FLAG
16384  = MYSQLI_PART_KEY_FLAG
32768  = MYSQLI_GROUP_FLAG
65536  = MYSQLI_UNIQUE_FLAG
131072 = MYSQLI_BINCMP_FLAG
*/

	/**
	* Returns an ADOfieldobject object containing information about the
	* nth field of a recordset row
	*
	* @param int $fieldOffset
	* 
	@return object, null or array
	*/
	public function fetchField(int $fieldOffset = -1) {
		
		if (!$this->fieldObjectsRetrieved)
			/*
			* Should not happen as _fetchFields is called when
			* the recordset is initialized
			*/
			$this->_fetchFields();
		
		if ($fieldOffset == -1)
			return $this->fieldObjectsByIndex;
		
		if (!array_key_exists($fieldOffset,$this->fieldObjectsByIndex))
			return null;
		
		return $this->fieldObjectsByIndex[$fieldOffset];
		
		
		$fieldnr = $fieldOffset;
		if ($fieldOffset != -1) {
			$fieldOffset = @mysqli_field_seek($this->_queryID, $fieldnr);
		}
		$o = @mysqli_fetch_field($this->_queryID);
		if (!$o) 
			return null;
		
		/*
		* Standardized return object
		*/
		$afo = new \ADOdb\common\ADOFieldObject;
		
		/*
		* Set core elements	
		*/
		foreach ($o as $k=>$v)
			$afo->$k = $v;
		
		//Fix for HHVM
		if ( !isset($afo->flags) ) {
			$afo->flags = 0;
		}
		/*
		* Properties of an ADOFieldObject as set by MetaColumns 
		*/
		$afo->primary_key = $o->flags & MYSQLI_PRI_KEY_FLAG;
		$afo->not_null = $o->flags & MYSQLI_NOT_NULL_FLAG;
		$afo->auto_increment = $o->flags & MYSQLI_AUTO_INCREMENT_FLAG;
		$afo->binary = $o->flags & MYSQLI_BINARY_FLAG;
		// $o->blob = $o->flags & MYSQLI_BLOB_FLAG; /* not returned by MetaColumns */
		$afo->unsigned = $o->flags & MYSQLI_UNSIGNED_FLAG;
		
		return $afo;
	}

	/**
	* Returns a row as associative if the fetch mode is numeric
	*
	* @param int $assocCase	The casing of keys of the returned data 
	*
	* @return array
	*/
	public function getRowAssoc(int $assocCase = 2) : ?array
	{
		
		if (!$this->fieldObjectsRetrieved)
		{
			$this->_fetchFields();
		}
		
		if ($this->fieldObjectsByIndex === null)
			return null;
		
		if (count($this->fieldObjectLowerXref) == count($this->fieldObjectsByIndex))
		{
			/*
			* Should always be true
			*/
			switch ($assocCase)
			{
				case $this->connection::ADODB_ASSOC_CASE_LOWER:
				return array_combine($this->fieldObjectLowerXref,$this->fields);
				case $this->connection::ADODB_ASSOC_CASE_UPPER:
				return array_combine($this->fieldObjectUpperXref,$this->fields);
				case $this->connection::ADODB_ASSOC_CASE_NATIVE:
				return array_combine($this->fieldObjectNativeXref,$this->fields);
			}
		}

		$row = parent::getRowAssoc($assocCase);
		return $row;
	}

	/**
	* Use associative array to get fields array 
	*
	* @param string $colname
	*
	* @return ?string
	*/
	public function fields(string $colname) : ?string
	{
		if ($this->fetchMode != MYSQLI_NUM) {
			return @$this->fields[$colname];
		}

		if (!$this->bind) {
			$this->bind = array();
			for ($i = 0; $i < $this->_numOfFields; $i++) {
				$o = $this->fetchField($i);
				$this->bind[strtoupper($o->name)] = $i;
			}
		}
		return $this->fields[$this->bind[strtoupper($colname)]];
	}

	/**
	* Uses the database driver to move the recordset
	* cursor to a specific row
	*
	* @param int	$row
	*
	* @return array
	*/
	protected function _seek(int $row) : bool
	{
		if ($this->_numOfRows == 0 || $row < 0) {
			return false;
		}

		@mysqli_data_seek($this->_queryID, $row);
		$this->EOF = false;
		return true;
	}

	/**
	* For a multiuery, get the next recordset
	*
	* @return bool success
	*/
	public function nextRecordSet() : bool
	{

		$countRecords = $this->connection->connectionDefinitions->countRecords;
		@mysqli_free_result($this->_queryID);
		$this->_queryID = -1;
		// Move to the next recordset, or return false if there is none. In a stored proc
		// call, mysqli_next_result returns true for the last "recordset", but mysqli_store_result
		// returns false. I think this is because the last "recordset" is actually just the
		// return value of the stored proc (ie the number of rows affected).
		if(!@mysqli_next_result($this->connection->_connectionID)) {
		return false;
		}
		// CD: There is no $this->_connectionID variable, at least in the ADO version I'm using
		$this->_queryID = ($countRecords) ? @mysqli_store_result( $this->connection->_connectionID )
						: @mysqli_use_result( $this->connection->_connectionID );
		if(!$this->_queryID) {
			return false;
		}
		$this->_inited = false;
		$this->bind = false;
		$this->_currentRow = -1;
		$this->Init();
		return true;
	}

	/**
	* Move to next record in the recordset.
	*
	* @return true if there still rows available, or false if there are no more rows (EOF).
	*/
	public function moveNext() : bool
	{
		if ($this->EOF) return false;
		$this->_currentRow++;
		$this->fields = @mysqli_fetch_array($this->_queryID,$this->fetchMode);

		if (is_array($this->fields)) {
			$this->_updatefields();
			return true;
		}
		$this->EOF = true;
		return false;
	}

	/**
	* DB specific array fetch 
	*
	* @return bool
	*/
	protected function _fetch() : bool
	{
		$this->fields = @mysqli_fetch_array($this->_queryID,$this->fetchMode);
		$this->_updatefields();
		return is_array($this->fields);
	}

	/**
	* Internal recordset shutdown
	*
	* @return bool
	*/
	protected function _close() : bool
	{
		//if results are attached to this pointer from Stored Proceedure calls, the next standard query will die 2014
		//only a problem with persistant connections

		if(isset($this->connection->_connectionID) && $this->connection->_connectionID) {
			while(@mysqli_more_results($this->connection->_connectionID)){
				@mysqli_next_result($this->connection->_connectionID);
			}
		}

		if($this->_queryID instanceof mysqli_result) {
			@mysqli_free_result($this->_queryID);
		}
		$this->_queryID = false;
		
		return true;
	}

	
	

} // rs class
