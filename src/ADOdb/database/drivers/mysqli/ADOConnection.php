<?php
/**
* The ADOdb connector class for the mysqli driver
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace ADODb\database\drivers\mysqli;
  
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

final class ADOConnection extends \ADOdb\ADOConnection {

	protected array $actualTypes = array(
		self::ADODB_METATYPE_C=>'VARCHAR',
		self::ADODB_METATYPE_C2=>'VARCHAR',
		self::ADODB_METATYPE_B=>'LONGBLOB',
		self::ADODB_METATYPE_D=>'DATE',
		self::ADODB_METATYPE_F=>'DOUBLE',
		self::ADODB_METATYPE_L=>'TINYINT',
		self::ADODB_METATYPE_I=>'INTEGER',
		self::ADODB_METATYPE_I1=>'TINYINT',
		self::ADODB_METATYPE_I2=>'SMALLINT',
		self::ADODB_METATYPE_I4=>'INTEGER',
		self::ADODB_METATYPE_I8=>'BIGINT',
		self::ADODB_METATYPE_N=>'NUMERIC',
		self::ADODB_METATYPE_R=>'REAL',
		self::ADODB_METATYPE_T=>'DATETIME',
		self::ADODB_METATYPE_XL=>'LONGTEXT',
		self::ADODB_METATYPE_X=>'TEXT',
		self::ADODB_METATYPE_X2=>'LONGTEXT',
		'TS'=>'DATETIME'
		);	
	
	var $databaseType = 'mysqli';
	var $dataProvider = 'mysql';
	var $hasInsertID = true;
	var $hasAffectedRows = true;
	
	public string $fmtTimeStamp = "'Y-m-d H:i:s'";
	
	var $hasLimit = true;
	
	/*
	* Can we scroll backwards
    */
	public bool $hasMoveFirst = true;
	
	/*
	* Does the database support GenID
    */
	public bool $hasGenID = true;
	
	/*
	* The driver directly accepts dates in ISO format
	*/
	protected bool $isoDates = true;			
	
	/*
	* Name of the function to return the current date
	*/
	protected ?string $sysDate = 'CURDATE()';
	
	/*
	* name of function that returns the current timestamp.
	* SessionHandler needs access so public
	*/
	public ?string $sysTimeStamp = 'NOW()';
	
	var $clientFlags = 0;
	
	/*
	* Name of the function that represents substring
	*/
	public string $substr = "SUBSTRING";
	
	/*
	* string to use to quote identifiers and names
	*/
	public string $nameQuote = '`';
	
	
		// See http://www.mysql.com/doc/M/i/Miscellaneous_functions.html
	// Reference on Last_Insert_ID on the recommended way to simulate sequences
	var $_genIDSQL = "update %s set id=LAST_INSERT_ID(id+1);";
	var $_genSeqSQL = "create table if not exists %s (id int not null)";
	var $_genSeqCountSQL = "select count(*) from %s";
	var $_genSeq2SQL = "insert into %s values (%s)";
	var $_dropSeqSQL = "drop table if exists %s";

	/**
	 * Tells the insert_id method how to obtain the last value, depending on whether
	 * we are using a stored procedure or not
	 */
	private bool $usePreparedStatement = false;
	private bool $useLastInsertStatement = false;
	
	/*
	* Mysqli driver has no official way of accepting DSN strings
	*/
	protected bool $supportsDsnStrings = false;
	
	/**
	 * Sets the isolation level of a transaction.
	 *
	 * @link https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:settransactionmode
	 *
	 * @param string $transaction_mode The transaction mode to set.
	 *
	 * @return void
	 */
	final public function setTransactionMode( string $transaction_mode ) : void
	{
		$this->_transmode = $transaction_mode;
		if (empty($transaction_mode)) {
			$this->execute('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
			return;
		}
		if (!stristr($transaction_mode,'isolation')) 
			$transaction_mode = 'ISOLATION LEVEL '.$transaction_mode;
		
		$this->execute("SET SESSION TRANSACTION ".$transaction_mode);
	}

	/**
	* Connect to database using provided parameters
	*
	* @param string $argHostname 
	* @param string $argUsername 
	* @param string $argPassword 
	* @param string $argDatabasename 
	* @param bool $persist
	*
	* @return bool success
	*/
	final protected function _connect(
				?string $argHostname = NULL,
				?string $argUsername = NULL,
				?string $argPassword = NULL,
				?string $argDatabasename = NULL, 
				bool $persist=false) : bool	{
		
		
		$connectPort   = (int)$this->oemFlags->port;
		$connectSocket = $this->oemFlags->socket;
				
		$mysqliFlags   = $this->oemFlags->flags;
		$mysqliOptions = $this->oemFlags->options;
		
		
		$this->_connectionID = @mysqli_init();

		if (is_null($this->_connectionID)) {
			$message = "mysqli_init() failed : "  . $this->ErrorMsg();
			$this->loggingObject->log(Logger::CRITICAL,$message);
			return false;
		}
		/*
		* Now read the preconnection options from th connectionDefinitions
		*/
		foreach($mysqliOptions as $arr) {
			@mysqli_options($this->_connectionID,$arr[0],$arr[1]);
		}
		
		//http ://php.net/manual/en/mysqli.persistconns.php
		if ($persist && strncmp($argHostname,'p:',2) != 0)
			$argHostname = 'p:'.$argHostname;

		if ($this->connectionDefinitions->debug)
		{
			$message = sprintf('Connect to mysqli using Host=%s User=%s DB=%s Port=%s, Socket=%s Flags=%s',
					$argHostname,
					$argUsername,
					$argDatabasename,
					$connectPort,
					$connectSocket,
					$mysqliFlags
					);
			
			$this->loggingObject->log(Logger::DEBUG,$message);

					
		}
		$sslOptions = $this->oemFlags->ssl;
		
		/*
		* SSL Connections for MySQLI
		*/
		if ($sslOptions['ssl_key'] || $sslOptions['ssl_cert'] 
		 || $sslOptions['ssl_ca'] || $sslOptions['ssl_capath'] 
		 || $sslOptions['ssl_cipher']) {
			@mysqli_ssl_set(
				$this->_connectionID, 
				$sslOptions['ssl_key'],
				$sslOptions['ssl_cert'],
				$sslOptions['ssl_ca'],
				$sslOptions['ssl_capath'],
				$sslOptions['ssl_cipher']
				);
		}
		
		/* 
		* Lets connect
		*/
		$ok = @mysqli_real_connect($this->_connectionID,
					$argHostname,
					$argUsername,
					$argPassword,
					$argDatabasename,
					$connectPort,
					$connectSocket,
					$mysqliFlags);

		if ($ok) {
			if ($argDatabasename)  
				return $this->selectDB($argDatabasename);
			
			return true;
		
		} else {
			$message = "Could not connect : "  . $this->ErrorMsg();
			$this->loggingObject->log(Logger::CRITICAL,$message);

			$this->_connectionID = null;
			return false;
		}
	}

	/**
	*  How to force a persistent connection
	*
	* @param str $argHostname
	* @param str $argUsername
	* @param str $argUsername
	* @param str $argDatabasename
	*
	* @return success
	*/
	final protected function _pconnect(
				string $argHostname, 
				string $argUsername, 
				string $argPassword, 
				string $argDatabasename) : bool	{
					
		return $this->_connect($argHostname, $argUsername, $argPassword, $argDatabasename, true);
	
	}

	/**
	*  How to force a new connection
	*
	* @param str $argHostname
	* @param str $argUsername
	* @param str $argUsername
	* @param str $argDatabasename
	*
	* @return success
	*/
	final protected function _nconnect(
				string $argHostname, 
				string $argUsername, 
				string $argPassword, 
				string $argDatabasename) : bool	{
		
		if ($this->debug)
		{
			$message = 'Forcing new connections is not supported in the mysqli driver';
			$this->loggingObject->log(Logger::DEBUG,$message);
		}
		
		return $this->_connect($argHostname, $argUsername, $argPassword, $argDatabasename);
		
	}

	/**
	 * Replaces a null value with a specified replacement.
	 *
	 * @link https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:ifnull
	 *
	 * @param mixed $field The field in the table to check.
	 * @param mixed $ifNull The value to replace the null value with if it is found.
	 *
	 * @return string
	 */
	final public function ifNull( string $field, string $ifNull ) : string
	{
		return " IFNULL($field, $ifNull) "; 
	}
	
	/**
	* Returns the server info version
	*
	* @return array
	*/
	final public function serverInfo() : array	{
		
		$arr['description'] = $this->GetOne("select version()");
		$arr['version'] = $this->_findvers($arr['description']);
		return $arr;
	}

	/**
	* Attempts to lock a row or table(s) for update
	*
	* @param str $tables
	* @param str $where
	* @param str $col
	*
	* @return bool
	*/	
	final public function rowLock(
		string $tables,
		?string $where=null,
		?string $col='1 as adodbignore') : bool
	{
		if ($this->transCnt==0) 
			$this->beginTrans();
		if ($where) 
			$where = ' WHERE '.$where;
		
		$rs = $this->execute("SELECT $col FROM $tables $where FOR UPDATE");
		
		return !empty($rs);
	}

	/**
	 * Quotes a string to be sent to the database
	 * When there is no active connection,
	 * @param string $s The string to quote
	 * @param boolean $magic_quotes If false, use mysqli_real_escape_string()
	 *     if you are quoting a string extracted from a POST/GET variable,
	 *     then pass get_magic_quotes_gpc() as the second parameter. This will
	 *     ensure that the variable is not quoted twice, once by qstr() and
	 *     once by the magic_quotes_gpc.
	 *     Eg. $s = $db->qstr(_GET['name'],get_magic_quotes_gpc());
	 * @return string Quoted string
	 */
	final public function qStr(?string $s=null) : string
	{
		if (is_null($s)) 
			return 'NULL';
		
		// mysqli_real_escape_string() throws a warning when the given
		// connection is invalid
		if ($this->_connectionID) {
			return "'" . mysqli_real_escape_string($this->_connectionID, $s) . "'";
		}

		if ($this->replaceQuote[0] == '\\') {
			$s = str_replace(array('\\',"\0"), array('\\\\',"\\\0") ,$s);
		}
		return "'" . str_replace("'", $this->replaceQuote, $s) . "'";
	}

	/**
	* Returns the last insert if for the connection
	*
	* @return int
	*/
	final public function _insertid()
	{
		if ($this->useLastInsertStatement)
			$result = $this->getOne('SELECT LAST_INSERT_ID()');
		else
		$result = @mysqli_insert_id($this->_connectionID);
		if ($result == -1) 
		{
			$message = 'mysqli_insert_id() failed : '  . $this->ErrorMsg();
			$this->connection->loggingObject->log(Logger::CRITICAL,$message);
		}
		
		// reset prepared statement flags
		$this->usePreparedStatement   = false;
		$this->useLastInsertStatement = false;
		return $result;
	}

	/**
	* Returns  INSERT, UPDATE and DELETE query count
	*
	* @return int
	*/
	final public function _affectedrows()
	{
		$result =  @mysqli_affected_rows($this->_connectionID);
		if ($result == -1) {
			if ($this->debug) {
				$message = "mysqli_affected_rows() failed : "  . $this->ErrorMsg();
				$this->connection->loggingObject->log(Logger::WARNING,$message);
			}
		}
		return $result;
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	function createSequence($seqname='adodbseq',$startID=1)
	{
		if (empty($this->_genSeqSQL)) return false;
		$u = strtoupper($seqname);

		$ok = $this->Execute(sprintf($this->_genSeqSQL,$seqname));
		if (!$ok) return false;
		return $this->Execute(sprintf($this->_genSeq2SQL,$seqname,$startID-1));
	}

	/**
	* Function not described
	*
	* @param str $1
	* @param str $2
	*
	* @return
	*/
	final public function genID(
		string $seqname='adodbseq',
		int $startID=1) : int
	{
		// post-nuke sets hasGenID to false
		if (!$this->hasGenID) 
			return false;

		$getnext = sprintf($this->_genIDSQL,$seqname);
		$holdtransOK = $this->_transOK; // save the current status
		
		$rs = @$this->execute($getnext);
		
		if (!$rs) {
			if ($holdtransOK) 
				$this->_transOK = true; //if the status was ok before reset
			
			$u = strtoupper($seqname);
			$this->execute(sprintf($this->_genSeqSQL,$seqname));
			
			$cnt = $this->GetOne(sprintf($this->_genSeqCountSQL,$seqname));
			
			if (!$cnt) 
				$this->execute(sprintf($this->_genSeq2SQL,$seqname,$startID-1));
			
			$rs = $this->execute($getnext);
		}

		if ($rs) 
		{
			$this->genID = @mysqli_insert_id($this->_connectionID);
			$rs->Close();
		} else
			$this->genID = 0;

		return $this->genID;
	}

	/**
	* Format date column in sql string given an input format that understands Y M D
	*
	* @param str $fmt
	* @param bool $col
	*
	* @return string
	*/
	final public function sqlDate(string $fmt, bool $col=false) : string
	{
		if (!$col) $col = $this->sysTimeStamp;
		$s = 'DATE_FORMAT('.$col.",'";
		$concat = false;
		$len = strlen($fmt);
		for ($i=0; $i < $len; $i++) {
			$ch = $fmt[$i];
			switch($ch) {
			case 'Y':
			case 'y':
				$s .= '%Y';
				break;
			case 'Q':
			case 'q':
				$s .= "'),Quarter($col)";

				if ($len > $i+1) $s .= ",DATE_FORMAT($col,'";
				else $s .= ",('";
				$concat = true;
				break;
			case 'M':
				$s .= '%b';
				break;

			case 'm':
				$s .= '%m';
				break;
			case 'D':
			case 'd':
				$s .= '%d';
				break;

			case 'H':
				$s .= '%H';
				break;

			case 'h':
				$s .= '%I';
				break;

			case 'i':
				$s .= '%i';
				break;

			case 's':
				$s .= '%s';
				break;

			case 'a':
			case 'A':
				$s .= '%p';
				break;

			case 'w':
				$s .= '%w';
				break;

			case 'l':
				$s .= '%W';
				break;

			default:

				if ($ch == '\\') {
					$i++;
					$ch = substr($fmt,$i,1);
				}
				$s .= $ch;
				break;
			}
		}
		$s.="')";
		
		if ($concat) 
			$s = "CONCAT($s)";
		return $s;
	}

	// returns concatenated string
	// much easier to run "mysqld --ansi" or "mysqld --sql-mode=PIPES_AS_CONCAT" and use || operator
	final public function concat() : string
	{
		$s = "";
		/*
		* Get as many as we like
		*/
		$arr = func_get_args();

		// suggestion by andrew005@mnogo.ru
		$s = implode(',',$arr);
		
		if (strlen($s) > 0) {
			return "CONCAT($s)";
		}
		else 
		{
			return '';
		}
	}

	/**
	* dayFraction is a day in floating point
	*
	* @param int $dayFraction
	* @param str $date
	*
	* @return
	*/
	final public function offsetDate($dayFraction,$date=false)
	{
		if (!$date) 
			$date = $this->sysDate;

		$fraction = $dayFraction * 24 * 3600;
		return $date . ' + INTERVAL ' .	 $fraction.' SECOND';

	}

	/**
	* Select the working database
	*
	* @param str $dbName
	*
	* @return bool success
	*/
	final public function selectDb(string $dbName) : bool
	{

		if ($this->_connectionID) {
			$result = @mysqli_select_db($this->_connectionID, $dbName);
			if (!$result) {
				
				$message = sprintf('Select of database %s failed. %s',
							$dbName,
							$this->errorMsg()
							);
				$this->connection->loggingObject->log(Logger::WARNING,$message);

			}
			else
				$this->database = $dbName;

			return $result;
		}
		return false;
	}

	/**
	* parameters use PostgreSQL convention, not MySQL
	*
	* @param	str		$sql
	* @param	int		$nrows
	* @param	int		$offset
	* @param	string[] $inputarr
	* @param	int		$secs
	*
	* @return mixed
	*/
	final public function selectLimit(
				string $sql,
				int $nrows = -1,
				int $offset = -1,
				array $inputarr = null,
				int $secs = 0) : ?object {
					
		$nrows = (int) $nrows;
		$offset = (int) $offset;
		$offsetStr = ($offset >= 0) ? "$offset," : '';
		
		if ($nrows < 0) 
			$nrows = '18446744073709551615';

		if ($secs)
			$rs = $this->cacheExecute($secs, $sql . " LIMIT $offsetStr$nrows" , $inputarr );
		else
			$rs = $this->execute($sql . " LIMIT $offsetStr$nrows" , $inputarr );

		return $rs;
	}

	/**
	* Prepares an SQL statement and returns a handle to use.
	*
	* @link https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:prepare
	* @todo update this function to handle prepared statements correctly
	*
	* @param string $sql The SQL to prepare.
	*
	* @return string The original SQL that was provided.
	*/
	final public function prepare(string $sql) : string
	{
		/*
		* Flag the insert_id method to use the correct retrieval method
		*/
		$this->usePreparedStatement = true;

		/*
		* Prepared statements are not yet handled correctly
		*/
		return $sql;
		$stmt = $this->_connectionID->prepare($sql);
		if (!$stmt) {
			echo $this->errorMsg();
			return $sql;
		}
		return array($sql,$stmt);;
	}


	/**
	 * Return the query id.
	 *
	 * @param string|array $sql
	 * @param array $inputarr
	 *
	 * @return bool|mysqli_result
	 */
	final public function _query(
				string $sql, 
				?array $inputarr)  {
					
		// Move to the next recordset, or return false if there is none. In a stored proc
		// call, mysqli_next_result returns true for the last "recordset", but mysqli_store_result
		// returns false. I think this is because the last "recordset" is actually just the
		// return value of the stored proc (ie the number of rows affected).
		// Commented out for reasons of performance. You should retrieve every recordset yourself.
		//	if (!mysqli_next_result($this->connection->_connectionID))	return false;

		if (is_array($sql)) {

			// Prepare() not supported because mysqli_stmt_execute does not return a recordset, but
			// returns as bound variables.

			$stmt = $sql[1];
			$a = '';
			foreach($inputarr as $k => $v) {
				if (is_string($v)) $a .= 's';
				else if (is_integer($v)) $a .= 'i';
				else $a .= 'd';
			}
			
			/*
			 * set prepared statement flags
			 */
			if ($this->usePreparedStatement)
				$this->useLastInsertStatement = true;


			$fnarr = array_merge( array($stmt,$a) , $inputarr);
			$ret = @call_user_func_array('mysqli_stmt_bind_param',$fnarr);
			$ret = @mysqli_stmt_execute($stmt);
			return $ret;
		}
		else
		{
			/*
			* reset prepared statement flags, in case we set them
			* previously and didn't use them
			*/
			$this->usePreparedStatement   = false;
			$this->useLastInsertStatement = false;
		}
		
		/*
		* Multiquery must be specifically enabled in OEM flags
		*/
		if ($this->oemFlags->multiQuery) {
			$rs = @mysqli_multi_query($this->_connectionID, $sql.';');
			if ($rs) {
				$rs = ($this->connectionDefinitions->countRecords) ? @mysqli_store_result( $this-_connectionID ) : @mysqli_use_result( $this->_connectionID );
				return $rs ? $rs : true; // mysqli_more_results( $this->_connectionID )
			}
		} else {
			$rs = @mysqli_query($this->_connectionID, $sql, $this->connectionDefinitions->countRecords ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT);

			if ($rs) return $rs;
		}

		//if($this->debug)
		//	ADOConnection::outp("Query: " . $sql . " failed. " . $this->ErrorMsg());

		return false;

	}

	/**
	* Returns: the last error message from previous database operation	
	*/
	final public function errorMsg() : string
	{
		if (empty($this->_connectionID))
			$this->_errorMsg = @mysqli_connect_error();
		else
			$this->_errorMsg = @mysqli_error($this->_connectionID);
		return $this->_errorMsg;
	}

	/**
	* Returns: the last error number from previous database operation	
	*/
	final public function errorNo() : int
	{
		if (empty($this->_connectionID))
			return @mysqli_connect_errno();
		else
			return @mysqli_errno($this->_connectionID);
	}

	// returns true or false
	function _close()
	{
		@mysqli_close($this->_connectionID);
		$this->_connectionID = false;
	}

	/*
	* Maximum size of C field
	*/
	final public function charMax() : int
	{
		return 255;
	}

	/*
	* Maximum size of X field
	*/
	final public function textMax() : int
	{
		return 4294967295;
	}


	/**
	* Get the name of the character set the client connection is using now.
	*
	* @return ?string The name of the character set, or false if it can't be determined.
	*/
	final public function getCharSet() : ?string
	{
		//we will use ADO's builtin property charSet
		if (!method_exists($this->_connectionID,'character_set_name'))
			return null;

		$this->charSet = @$this->_connectionID->character_set_name();
		if (!$this->charSet) {
			return null;
		} else {
			return $this->charSet;
		}
	}

	/**
	 * Sets the character set for database connections (limited databases).
	 *
	 * @link https://adodb.org/dokuwiki/doku.php?id=v5:reference:connection:setcharset
	 *
	 * @param string $charset_name The character set to switch to.
	 *
	 * @return bool True if the character set was changed successfully, otherwise false.
	 */
	final public function setCharSet(string $charset_name) : bool
	{
		if (!method_exists($this->_connectionID,'set_charset')) {
			return false;
		}

		if ($this->charSet !== $charset_name) {
			$if = @$this->_connectionID->set_charset($charset_name);
			return ($if === true & $this->getCharSet() == $charset_name);
		} else {
			return true;
		}
	}
}
