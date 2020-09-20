<?php
/**
* Methods associated with record paging & pageExecute
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

final class ADOPagingFunctions
{
	
	final public function __construct(object $connection)
	{	
		$this->connection = $connection;
	}
	
		
	/**
	* Page execution with End Of File marker
	*
	* @param string $sql
	* @param int $nrows
	* @param int $page
	* @param array $inputarr
	* @param int $secs2cache
	*
	* @return obj
	*/
	final public function _adodb_pageexecute_all_rows(
				string $sql, 
				int $nrows, 
				int $page,
				?array $inputarr=null,
				int $secs2cache=0) : object{
					
		$atfirstpage = false;
		$atlastpage = false;
		$lastpageno=1;

		// If an invalid nrows is supplied,
		// we assume a default value of 10 rows per page
		if (!isset($nrows) || $nrows <= 0) 
			$nrows = 10;

		$qryRecs = false; //count records for no offset

		$qryRecs = $this->_adodb_getcount($sql,$inputarr,$secs2cache);
		
		$lastpageno = (int) ceil($qryRecs / $nrows);
		$this->connection->_maxRecordCount = $qryRecs;

		// ***** Here we check whether $page is the last page or
		// whether we are trying to retrieve
		// a page number greater than the last page number.
		if ($page >= $lastpageno) {
			$page = $lastpageno;
			$atlastpage = true;
		}

		// If page number <= 1, then we are at the first page
		if (empty($page) || $page <= 1) {
			$page = 1;
			$atfirstpage = true;
		}

		// We get the data we want
		$offset = $nrows * ($page-1);
		if ($secs2cache > 0)
			$rsreturn = $this->connection->cacheSelectLimit($secs2cache, $sql, $nrows, $offset, $inputarr);
		else
			$rsreturn = $this->connection->selectLimit($sql, $nrows, $offset, $inputarr, $secs2cache);

		/*
		* Before returning the RecordSet, we set the pagination 
		* properties we need
		*/
		if ($rsreturn) {
			$rsreturn->setMaxRecordCount($qryRecs);
			$rsreturn->setRowsPerPage($nrows);
			$rsreturn->absolutePage($page);
			$rsreturn->atFirstPage($atfirstpage);
			$rsreturn->atLastPage($atlastpage);
			$rsreturn->lastPageNo($lastpageno);
		}
		
		return $rsreturn;
	}

	/**
	* Page execution function with limitless rows
	*
	* @param string $sql
	* @param int $nrows
	* @param int $page
	* @param array $inputarr
	* @param int $secs2cache
	*
	* @return obj
	*/
	final public function _adodb_pageexecute_no_last_page(
				string $sql, 
				int $nrows, 
				int $page, 
				?array $inputarr=null,
				int $secs2cache=0) : object {

		$atfirstpage = false;
		$atlastpage = false;

		if (!isset($page) || $page <= 1) {
			// If page number <= 1, then we are at the first page
			$page = 1;
			$atfirstpage = true;
		}
		if ($nrows <= 0) {
			// If an invalid nrows is supplied, we assume a default value of 10 rows per page
			$nrows = 10;
		}

		$pagecounteroffset = ($page * $nrows) - $nrows;

		// To find out if there are more pages of rows, simply increase the limit or
		// nrows by 1 and see if that number of records was returned. If it was,
		// then we know there is at least one more page left, otherwise we are on
		// the last page. Therefore allow non-Count() paging with single queries
		// rather than three queries as was done before.
		$test_nrows = $nrows + 1;
		if ($secs2cache > 0) {
			$rsreturn = $this->connection->cacheSelectLimit($secs2cache, $sql, $nrows, $pagecounteroffset, $inputarr);
		} else {
			$rsreturn = $this->connection->selectLimit($sql, $test_nrows, $pagecounteroffset, $inputarr, $secs2cache);
		}

		/*
		* Now check to see if the number of rows returned was the 
		* higher value we asked for or not.
		*/
		if ( $rsreturn->_numOfRows == $test_nrows ) {
			// Still at least 1 more row, so we are not on last page yet...
			// Remove the last row from the RS.
			$rsreturn->_numOfRows = ( $rsreturn->_numOfRows - 1 );
		} elseif ( $rsreturn->_numOfRows == 0 && $page > 1 ) {
			// Likely requested a page that doesn't exist, so need to find the last
			// page and return it. Revert to original method and loop through pages
			// until we find some data...
			$pagecounter = $page + 1;
			$pagecounteroffset = ($pagecounter * $nrows) - $nrows;

			$rstest = $rsreturn;
			if ($rstest) {
				while ($rstest && $rstest->EOF && $pagecounter > 0) {
					$atlastpage = true;
					$pagecounter--;
					$pagecounteroffset = $nrows * ($pagecounter - 1);
					$rstest->Close();
					if ($secs2cache>0) {
						$rstest = $this->connection->cacheSelectLimit($secs2cache, $sql, $nrows, $pagecounteroffset, $inputarr);
					}
					else {
						$rstest = $this->connection->selectLimit($sql, $nrows, $pagecounteroffset, $inputarr, $secs2cache);
					}
				}
				if ($rstest) $rstest->Close();
			}
			if ($atlastpage) {
				// If we are at the last page or beyond it, we are going to retrieve it
				$page = $pagecounter;
				if ($page == 1) {
					// We have to do this again in case the last page is the same as
					// the first page, that is, the recordset has only 1 page.
					$atfirstpage = true;
				}
			}
			// We get the data we want
			$offset = $nrows * ($page-1);
			if ($secs2cache > 0) {
				$rsreturn = $this->connection->cacheSelectLimit($secs2cache, $sql, $nrows, $offset, $inputarr);
			}
			else {
				$rsreturn = $this->connection->selectLimit($sql, $nrows, $offset, $inputarr, $secs2cache);
			}
		} elseif ( $rsreturn->_numOfRows < $test_nrows ) {
			// Rows is less than what we asked for, so must be at the last page.
			$atlastpage = true;
		}

		/*
		* Before returning the RecordSet, we set the pagination 
		* properties we need
		*/
		if ($rsreturn) 
		{
			$rsreturn->setRowsPerPage($nrows);
			$rsreturn->absolutePage($page);
			$rsreturn->atFirstPage($atfirstpage);
			$rsreturn->atLastPage($atlastpage);
		}
		
		return $rsreturn;
	}
	
	/*
	* Count the number of records this sql statement will return by using
	* query rewriting heuristics...
	*
	* @param string $sql,
	* @param array $inputarr=null,
	* @param int $secs2cache=0
	*
	* @return int
	*/
	final protected function _adodb_getcount(
		string $sql,
		?array $inputarr=null,
		int $secs2cache=0) {
			
		$qryRecs = 0;
		
		if ($secs2cache)
			/*
			* We are going to use the memcache server
			*/
			$memcacheObject = $this->connection->memcacheObject;

		if (!empty($this->connection->_nestedSQL) 
		|| preg_match("/^\s*SELECT\s+DISTINCT/is", $sql) 
		|| preg_match('/\s+GROUP\s+BY\s+/is',$sql) 
		|| preg_match('/\s+UNION\s+/is',$sql)) {

			$rewritesql = $this->adodb_strip_order_by($sql);

			// ok, has SELECT DISTINCT or GROUP BY so see if we can use a table alias
			// but this is only supported by oracle and postgresql...
			if ($this->connection->dataProvider == 'oci8') {
				// Allow Oracle hints to be used for query optimization, Chris Wrye
				if (preg_match('#/\\*+.*?\\*\\/#', $sql, $hint)) {
					$rewritesql = "SELECT ".$hint[0]." COUNT(*) FROM (".$rewritesql.")";
				} else
					$rewritesql = "SELECT COUNT(*) FROM (".$rewritesql.")";

			} else if (strncmp($this->connection->databaseType,'postgres',8) == 0
				|| strncmp($this->connection->databaseType,'mysql',5) == 0
			|| strncmp($this->connection->databaseType,'mssql',5) == 0
				|| strncmp($this->connection->dsnType,'sqlsrv',5) == 0
				|| strncmp($this->connection->dsnType,'mssql',5) == 0
			){
				$rewritesql = "SELECT COUNT(*) FROM ($rewritesql) _ADODB_ALIAS_";
			} else {
				$rewritesql = "SELECT COUNT(*) FROM ($rewritesql)";
			}
		} else {
			// now replace SELECT ... FROM with SELECT COUNT(*) FROM
			if ( strpos($sql, '_ADODB_COUNT') !== FALSE ) {
				$rewritesql = preg_replace('/^\s*?SELECT\s+_ADODB_COUNT(.*)_ADODB_COUNT\s/is','SELECT COUNT(*) ',$sql);
			} else {
				$rewritesql = preg_replace('/^\s*SELECT\s.*\s+FROM\s/Uis','SELECT COUNT(*) FROM ',$sql);
			}
			
			$rewritesql = $this->adodb_strip_order_by($rewritesql);
		}

		if (isset($rewritesql) && $rewritesql != $sql) {
			if (preg_match('/\sLIMIT\s+[0-9]+/i',$sql,$limitarr)) $rewritesql .= $limitarr[0];

			if ($secs2cache) {
				// we only use half the time of secs2cache because the count can quickly
				// become inaccurate if new records are added
				$qryRecs = $memcacheObject->cacheGetOne($secs2cache/2,$rewritesql,$inputarr);

			} else {
				$qryRecs = $this->connection->getOne($rewritesql,$inputarr);
			}
			if ($qryRecs !== false) return $qryRecs;
		}
		//--------------------------------------------
		// query rewrite failed - so try slower way...


		// strip off unneeded ORDER BY if no UNION
		if (preg_match('/\s*UNION\s*/is', $sql)) $rewritesql = $sql;
		else $rewritesql = $rewritesql = $this->adodb_strip_order_by($sql);

		if (preg_match('/\sLIMIT\s+[0-9]+/i',$sql,$limitarr)) $rewritesql .= $limitarr[0];

		if ($secs2cache) {
			$rstest = $memcacheObject->cacheExecute($secs2cache,$rewritesql,$inputarr);
			if (!$rstest) 
				$rstest = $memcacheObject->cacheExecute($secs2cache,$sql,$inputarr);
		} else {
			$rstest = $this->connection->execute($rewritesql,$inputarr);
			if (!$rstest) 
				$rstest = $this->connection->execute($sql,$inputarr);
		}
		if ($rstest) {
				$qryRecs = $rstest->recordCount();
			if ($qryRecs == -1) {
				while(!$rstest->EOF) {
					$rstest->moveNext();
				}
				$qryRecs = $rstest->_currentRow;
			}
			$rstest->close();
			if ($qryRecs == -1) 
				return 0;
		}
		return $qryRecs;
	}
	
	/**
	* Removes the order by from a statement
	*
	* @param str $sql
	*
	* @return string
	*/
	final protected function adodb_strip_order_by(string $sql): string	{
		
		$rez = preg_match('/(\sORDER\s+BY\s(?:[^)](?!LIMIT))*)/is', $sql, $arr);
		if ($arr)
			if (strpos($arr[1], '(') !== false) {
				$at = strpos($sql, $arr[1]);
				$cntin = 0;
				for ($i=$at, $max=strlen($sql); $i < $max; $i++) {
					$ch = $sql[$i];
					if ($ch == '(') {
						$cntin += 1;
					} elseif($ch == ')') {
						$cntin -= 1;
						if ($cntin < 0) {
							break;
						}
					}
				}
				$sql = substr($sql,0,$at).substr($sql,$i);
			} else {
				$sql = str_replace($arr[1], '', $sql);
			}
		return $sql;
	}

}