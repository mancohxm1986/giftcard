<?php

if (! defined('ABSPATH')) {
    exit();
}

class PL_WCPT_Reports_List_Table extends PL_WCPT_List_Table {

    private $date_1;
    private $date_2;
    private $allowed_html = array(
	    'input' => array(
	        'type'      => array(),
	        'name'      => array(),
	        'value'     => array(),
	        'checked'   => array()
	    ),
	    'th' => array()
	);

    /**
     * Constructor, we override the parent to pass our own arguments
     * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
     */
    public function __construct()
    {
        $this->delete_array = array();
        $this->message;
        
        parent::__construct( array(
            'singular' => 'wp_list_pl_product', // Singular label
            'plural'   => 'wp_list_pl_products', // plural label, also this well be one of the table css class
            'ajax' 	   => false,
        ) ); // We won't support Ajax for this table
        
        if ( isset( $_REQUEST['pl-search-date1-top'] ) ) {

          // Process filter data into array
          $this->date_1 = sanitize_text_field( $_REQUEST['pl-search-date1-top'] );
          $this->date_2 = sanitize_text_field( $_REQUEST['pl-search-date2-top'] );
        }
        else {
	        $this->date_1 = date( 'Y-m-d', strtotime( '- 1 month' ) );
	        $this->date_2 = date( 'Y-m-d' );
        }
    }
    
    /**
     * Add extra markup in the toolbars before or after the list
     *
     * @param string $which,
     *            helps you decide if you add the markup after (bottom) or before (top) the list
     */
    public function extra_tablenav( $which ) {
	    
	    // Add filter to top tablenav
      if ( $which == 'top' ) {

        $date_1 = $this->date_1;
        $date_2 = $this->date_2;
		        
        echo "<input type='text' class='pl-search-date' name='pl-search-date1-top' id='pl-search-date1-top' placeholder='Select start date' value='" . esc_attr( $date_1 ) . "'/>";
        echo "<input type='text' class='pl-search-date' name='pl-search-date2-top' id='pl-search-date2-top' placeholder='Select end date' value='" . esc_attr( $date_2 ) . "'/>";
        echo '<input type="submit" id="doFilter" class="button action" value="Filter">';
      }
    }

    /**
     * Define the columns that are going to be used in the table
     *
     * @return array $columns, the array of columns to use with the table
     */
    public function get_columns() {
	    
        return array(
	        'cb' 			  => '<input type="checkbox" />',
	        'date'        	  => __( 'Date Time', 'gift-cards-on-demand-free' ),
	        'status'   		  => __( 'Status', 'gift-cards-on-demand-free' ),
	        'total' 		  => __( 'Value', 'gift-cards-on-demand-free' ),
	        'quantity' 		  => __( 'Quantity', 'gift-cards-on-demand-free' ),
	        'balance' 		  => __( 'Balance', 'gift-cards-on-demand-free' ),
            'product' 		  => __( 'Product', 'gift-cards-on-demand-free' ),
            'serial' 		  => __( 'Serial', 'gift-cards-on-demand-free' ),
        );
    }

    public function column_cb( $item ) {
	    
        return sprintf( '<th scope="row" class="check-column"><input type="checkbox" name="%1$s[]" value="%2$s" /></th>', esc_attr( $this->_args['singular'] ), esc_attr( $item->transaction_id ) );
    }

    /**
     * Returns the list of available bulk actions.
     */
    public function get_bulk_actions() {
	    
        $actions = array(
            'delete' => __( 'Delete', 'gift-cards-on-demand-free' ),
        );
        return $actions;
    }
    
    /**
     * How the bulk actions are processed for this table.
     */
    public function process_bulk_action() {
	    
        if ( 'delete' === $this->current_action() ) {
	        
	        if ( isset( $_GET[$this->_args['singular']] ) ) {
		        $items = array_map( 'sanitize_text_field', $_GET[$this->_args['singular']]  );
	            foreach ( $items as $item ) {
	                $this->delete_this_transaction( $item );
	            }
            }
        }
    }

    public function show_message() {
	    
      if ( isset( $this->message ) ) {?>
        <div class="updated">
        <p><?php echo esc_attr( $this->message ) ?></p>
        </div><?php
      }
    }

    /**
     * How the bulk actions are processed for this table.
     */
    public function process_action() {
	    
        return 'show_table';
    }

    /**
     * Checks between action and action2.
     */
    public function current_action() {
	    
        if (isset($_REQUEST['action']) && - 1 != $_REQUEST['action'])
            return sanitize_text_field( $_REQUEST['action'] );

        if (isset($_REQUEST['action2']) && - 1 != $_REQUEST['action2'])
            return sanitize_text_field( $_REQUEST['action2'] );

        return false;
    }

    /**
     * Decide which columns to activate the sorting functionality on
     *
     * @return array $sortable, the array of columns that can be sorted by the user
     *
     */
    public function get_sortable_columns() {
	    
        return array(
            'transaction_id' => array(
                'transaction_id',
                false
            ),
        );
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    public function prepare_items() {
	    
        global $wpdb;
        $screen = get_current_screen();

        /* -- Check if there are bulk actions -- */
        $this->process_bulk_action();
        
        $date_1  = $this->date_1 ? $this->date_1 : date( 'Y-m-d', strtotime( '-20 years' ) );
        $date_2  = $this->date_2 ? $this->date_2 : date( 'Y-m-d', strtotime( '+1 month' ) );
        $orderby = ( isset( $_GET['orderby'] ) && $_GET['orderby'] ) ? ( sanitize_text_field( $_GET["orderby"] ) ) : 'date';
        $order 	 = ( isset( $_GET['order'] ) && $_GET['order'] ) ? ( sanitize_text_field( $_GET["order"] ) ) : 'DESC';
        $status  = 'Voucher sale';
        $date_2  = $date_2 . ' 23:59:59';
        $base_query = "SELECT * FROM {$wpdb->prefix}wcpt_transactions WHERE status != %s AND date > %s && date < %s";

        /* -- Preparing your query -- */        
        $query = $wpdb->prepare( $base_query, $status, $date_1, $date_2 );
        
        if ( ! empty( $orderby ) && ! empty( $order ) ) {
	        if ( $order == 'asc' ) {
		        $base_query .= ' ORDER BY %i ASC';
	        } else {
		        $base_query .= ' ORDER BY %i DESC';
	        }
        }
	                
        /* -- Pagination parameters -- */
        // Number of elements in your table?
        $totalitems = $wpdb->query( $query ); // return the total number of affected rows
                                            // How many to display per page?
        $perpage = 25;
        // Which page is this?
        $paged = ! empty( $_GET["paged"] ) ? ( sanitize_text_field( $_GET["paged"] ) ) : '';
        // Page Number
        if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
            $paged = 1;
        }
        // How many pages do we have in total?
        $totalpages = ceil( $totalitems / $perpage );
        // adjust the query to take pagination into account
        if (! empty( $paged ) && ! empty( $perpage ) ) {
            $offset = ($paged - 1) * $perpage;
            $limit_query = ' LIMIT %d,%d';
            $query = $wpdb->prepare( $base_query . $limit_query , $status, $date_1, $date_2, $orderby, (int) $offset, (int) $perpage );
        }
                
        /* -- Register the pagination -- */
        $this->set_pagination_args( array(
            'total_items' => $totalitems,
            'total_pages' => $totalpages,
            'per_page' 	  => $perpage,
        ) );
        // The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = array(
            $columns,
            $hidden,
            $sortable,
        );
                				
        /* -- Fetch the items -- */
        $this->items = $wpdb->get_results( $query );        
    }

    /**
     * Display the rows of records in the table
     *
     * @return string, echo the markup of the rows
     *
     */
    public function display_rows() {

        // Get the records registered in the prepare_items method
        $records = $this->items;
        
        // Get the columns registered in the get_columns and get_sortable_columns methods
        list ( $columns, $hidden ) = $this->get_column_info();
        
        $conversion_value = ( get_option( 'pl_wcpt_api_currency_conversion_value' ) ) ? get_option( 'pl_wcpt_api_currency_conversion_value' ) : 1;        
        $balances 		  = self::get_initial_balance();
        $store_currency   = get_woocommerce_currency_symbol();
        
        // Loop for each record
        if ( !empty( $records ) ) {
            foreach ( $records as $rec ) {
                // Open the line
                echo '<tr id="record_' . esc_attr( $rec->transaction_id ) . '">';
                
                $args = array(
			        'post_type'      => 'product',
			        'post_status'	    => 'publish',
			        'posts_per_page' => 1,
			        'fields'		    => 'ids',
			        'meta_query' => array(
			            array(
			                'key' => '_pt_product',
			                'value' => $rec->product_id
			            )
			        )
			   );
			   
                $products = get_posts( $args );
                
                if ( $products ) {
                    
                    $product   = wc_get_product( $products[0] ); 
                    $title	   = $product->get_title();
                    $permalink = get_edit_post_link( $products[0] );
                    
                    $linked = "<a href='" . esc_attr( $permalink ) . "'>" . esc_attr( $title ) . "</a>";
                }
                else {            
                    $linked = '-';
                }
                
                $serial      = $rec->serial ? $rec->serial : '';
                $total_val   = number_format( (float) $rec->total * $conversion_value, 2, '.', '' );
                $total       = ( $rec->status == 'Voucher purchase' ) ? '-' . $store_currency . $total_val: $store_currency . $total_val;
                $balance_val = isset( $balances[ $rec->transaction_id ] ) ? number_format( (float) $balances[ $rec->transaction_id ] * $conversion_value, 2, '.', '' ) : '';
                $balance     = $balance_val ? $store_currency . $balance_val : '';

                foreach ( $columns as $column_name => $column_display_name ) {

                    // Style attributes for each col
                    $class = "class='$column_name column-$column_name'";
                    $style = '';
                    if ( in_array( $column_name, $hidden ) )
                        $style = ' style="display:none;"';
                    $attributes = $class . $style;
                                                                                                    
                    // Display the cell
                    switch ( $column_name ) {
                        case 'product':
                            echo '<td ' . esc_attr( $attributes ) . '>' . wp_kses_post( $linked ) . '</td>';
                            break;
                        case 'quantity':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $rec->quantity ) .'</td>';
                            break;
					   case 'total':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $total ) .'</td>';
                            break;
                        case 'date':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $rec->date ) . '</td>';
                            break;
                        case 'status':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $rec->status ) . '</td>';
                            break;
                       case 'balance':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $balance ) . '</td>';
                            break;
                       case 'serial':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $serial ) . '</td>';
                            break;
                        case 'cb':
                            echo wp_kses( $this->column_cb( $rec ), $this->allowed_html );
                            break;
                    }
                }

                // Close the line
                echo '</tr>';
            }
        }
    }
    
    public static function get_initial_balance() {
	    
	    $pt_api   		 = new PL_WCPT_API();
		$account_ballance = $pt_api->get_account_ballance();
		
		$status  = ( $account_ballance && isset( $account_ballance['RESELLER_Balance'] ) && isset( $account_ballance['RESELLER_Currency'] ) ) ? 'Online' : 'Offline';
		$balance = ( $account_ballance && isset( $account_ballance['RESELLER_Balance'] ) ) ? $account_ballance['RESELLER_Balance'] : '';
		$offset  = 0;
		if ( $status == 'Online' ) {
			
			global $wpdb;			
			$query  = "SELECT * FROM {$wpdb->prefix}wcpt_transactions WHERE status != 'Voucher sale' ORDER BY date DESC";	        
			$transactions = $wpdb->get_results( $query );
						
			$balance_array = array();
						
			foreach ( $transactions as $key => $transaction ) {
				
				$t_balance = $transaction->balance > 0 ? $transaction->balance : 0;
				
				if ( $t_balance == 0 ) {
				
					$total    = ( $transaction->status == 'Voucher purchase' ) ? -$transaction->total : $transaction->total;
							
					if ( $key && $offset ) {
						$balance += $offset;
					}
					$total  = -$total;
					$offset = $total;
					
					$balance_array[ $transaction->transaction_id ] = $balance;
				} else {
					
					$total    = ( $transaction->status == 'Voucher purchase' ) ? -$transaction->total : $transaction->total;
					$balance  = $t_balance;
					$total    = -$total;
					$offset   = $total;
					
					$balance_array[ $transaction->transaction_id ] = $t_balance;
				}
			}
									
			return $balance_array;
		}
		else {
			return array();
		}

    }

    public function delete_this_transaction( $id ) {
	    
        global $wpdb;
        /* -- Preparing the delete query to avoid SQL injection -- */
        $query = $wpdb->prepare( "DELETE FROM $this->table_name WHERE transaction_id = %d", intval( $id ) );

        $wpdb->query( $query );
    }
}