<?php
/**
* Utilities to build the session
*
* This file is part of the ADOdb package.
*
* @copyright 2020 Mark Newnham
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
/*
    Generate database table for session data
    @see http://phplens.com/lens/lensforum/msgs.php?id=12280
    @return 0 if failure, 1 if errors, 2 if successful.
	@author Markus Staab http://www.public-4u.de
*/
function adodb_session_create_table($schemaFile=null,$conn = null)
{
    // set default values
    if ($schemaFile===null) $schemaFile = ADODB_SESSION . '/session_schema2.xml';
    if ($conn===null) $conn = ADODB_Session::_conn();

	if (!$conn) return 0;

    $schema = new adoSchema($conn);
    $schema->ParseSchema($schemaFile);
    return $schema->ExecuteSchema();
}


/*
	Unserialize session data manually. See http://phplens.com/lens/lensforum/msgs.php?id=9821

	From Kerr Schere, to unserialize session data stored via ADOdb.
	1. Pull the session data from the db and loop through it.
	2. Inside the loop, you will need to urldecode the data column.
	3. After urldecode, run the serialized string through this function:

*/
function adodb_unserialize( string $serialized_string ) : string {
	
	$variables = array( );
	$a = preg_split( "/(\w+)\|/", $serialized_string, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE );
	for( $i = 0; $i < count( $a ); $i = $i+2 ) {
		$variables[$a[$i]] = unserialize( $a[$i+1] );
	}
	return( $variables );
}*
