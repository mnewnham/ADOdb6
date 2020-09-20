<?php
namespace ADOdb\meta;

class ADOMetaFunctions
{
	protected $coreFetchMode;
	
	protected $suppressExtendedMetaIndexes = false;
	
	/*
	* The legacy format of each metaindex entry
	*/
	protected $legacyMetaIndexFormat = array(
		'unique'=>0,
		'primary'=>0,
		'columns'=>array()
		);
		
	/*
	* The new extended metaIndex format
	*/
	protected $extendedMetaIndexFormat = array(
		'unique'=>0,
		'primary'=>0,
		'columns'=>array(),
		'index-attributes'=>array(),
		'column-attributes'=>array()
		);
	
	public function __construct($connection)
	{	
		$this->connection = $connection;
		$this->suppressExtendedMetaIndexes = $connection->connectionDefinitions->suppressExtendedMetaIndexes;

	}
	
	/**
	 * return the databases that the driver can connect to.
	 * Some databases will return an empty array.
	 *
	 * @return an array of database names.
	 */
	public function metaDatabases() : ?array
	{
		if (!$this->connection->metaDatabasesSQL) 
			return null;
		
		$this->coreFetchMode = $this->connection->fetchMode;
		
		$arr = $this->connection->getCol($this->connection->metaDatabasesSQL);
		
		$this->connection->fetchMode = $this->coreFetchMode;
		
		return $arr;
	}
}