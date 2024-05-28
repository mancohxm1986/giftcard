<?php

if ( !defined( 'ABSPATH' ) ) {
  exit;
}

/**
 * Abstract class which has helper functions to get data from the database
 * Based on: https://gist.github.com/paulund/6687336
 */
 
abstract class PL_WCPT_Database {
	
    /**
     * The current table name
     *
     * @var boolean
     */
    protected $tableName = false;

    /**
     * Constructor for the database class to inject the table name
     *
     * @param String $tableName - The current table name
     */
    public function __construct() {}

    /**
     * Insert data into the current data
     *
     * @param  array  $data - Data to enter into the database table
     *
     * @return InsertQuery Object
     */
    public function insert( array $data ) {
	    
        global $wpdb;

        if( empty( $data ) ) {
            return false;
        }

        $wpdb->insert( $this->tableName, $data );
        return $wpdb->insert_id;
    }

    /**
     * Update a table record in the database
     *
     * @param  array  $data           - Array of data to be updated
     * @param  array  $conditionValue - Key value pair for the where clause of the query
     *
     * @return Updated object
     */
    public function update( array $data, array $conditionValue ) {
	    
        global $wpdb;
        
        if( empty( $data ) ){
            return false;
        }

        $updated = $wpdb->update( $this->tableName, $data, $conditionValue );
        return $updated;
    }

    /**
     * Delete row on the database table
     *
     * @param  array  $conditionValue - Key value pair for the where clause of the query
     *
     * @return Int - Num rows deleted
     */
    public function delete( array $conditionValue ) {
        
        global $wpdb;
        $deleted = $wpdb->delete( $this->tableName, $conditionValue );
        return $deleted;
    }

	/**
	 * [count counts the rows of a specific table]
	 * @return [int] [count]
	 */
	public function count() {
		
		global $wpdb;
		$count = $wpdb->get_var( "SELECT COUNT(*) FROM $this->tableName" );
		return $count;
	}
}