<?php

if (! defined('ABSPATH')) {
    exit();
}

class PL_WCPT_Stock_List_Table extends PL_WCPT_List_Table {

    private $search_term;
    private $stock_status;
    private $stock_qtty;
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
        global $wpdb;
        $this->delete_array = array();
        $this->message;
        
        parent::__construct( array(
            'singular' => 'wp_list_pl_stock', // Singular label
            'plural'   => 'wp_list_pl_stocks', // plural label, also this well be one of the table css class
            'ajax' 	   => false,
        ) ); // We won't support Ajax for this table
        
        if ( isset( $_REQUEST['pl-search-term-top'] ) ) {

          // Process filter data into array
          $this->search_term = sanitize_text_field( $_REQUEST['pl-search-term-top'] );
        }
        
        if ( isset( $_REQUEST['pl-stock-status-top'] ) ) {

          // Process filter data into array
          $this->stock_status = sanitize_text_field( $_REQUEST['pl-stock-status-top'] );
        }
        
        if ( isset( $_REQUEST['pl-stock-qtty-top'] ) ) {

          // Process filter data into array
          $this->stock_qtty = sanitize_text_field( $_REQUEST['pl-stock-qtty-top'] );
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

        $term 		  = $this->search_term;
        $stock_status = $this->stock_status;
        $stock_qtty   = $this->stock_qtty;
        
        $is_sold    = ( $stock_status == 'sold' )    ? 'selected' : '';
        $is_instock = ( $stock_status == 'instock' ) ? 'selected' : '';
        
        $is_150 = ( $stock_qtty == '150' ) ? 'selected' : '';
        $is_250 = ( $stock_qtty == '250' ) ? 'selected' : '';
        
        $perpage = 50;
		        
        echo "<input type='text' name='pl-search-term-top' id='pl-search-term-top' placeholder='Search Term'' value='" . esc_attr( $term ) . "'/>";
        echo "<select name='pl-stock-status-top' id='pl-stock-status-top'>
		  <option value=''>All</option>
		  <option " . esc_attr( $is_sold ) . " value='sold'>Sold</option>
		  <option " . esc_attr( $is_instock ) . " value='instock'>In Stock</option>
		</select>";
		echo "<select name='pl-stock-qtty-top' id='pl-stock-qtty-top'>
		  <option value=''>" . esc_attr( $perpage ) . " per page</option>
		  <option " . esc_attr( $is_150 ) . " value='150'>150 per page</option>
		  <option " . esc_attr( $is_250 ) . " value='250'>250 per page</option>
		</select>";
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
	        'cb' 			 => '<input type="checkbox" />',
            'voucher_pin'    => __( 'PIN', 'gift-cards-on-demand-free' ),
            'voucher_serial' => __( 'Serial', 'gift-cards-on-demand-free' ),
            'product_name'   => __( 'Product', 'gift-cards-on-demand-free' ),
            'purchase_date'  => __( 'Purchase Date', 'gift-cards-on-demand-free' ),
            'expires'        => __( 'Expire Date', 'gift-cards-on-demand-free' ),
            'status'         => __( 'Status', 'gift-cards-on-demand-free' ),
		    'order_id' 		 => __( 'Order ID', 'gift-cards-on-demand-free' ),
        );
    }

    public function column_cb( $item ) {
	    
        return sprintf( '<th scope="row" class="check-column"><input type="checkbox" name="%1$s[]" value="%2$s" /></th>', esc_attr( $this->_args['singular'] ), esc_attr( $item->voucher_id ) );
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
	                $this->delete_this_product( $item );
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
            'purchase_date' => array(
                'purchase_date',
                false
            ),
            'expires' => array(
                'expires',
                false
            )
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
        
        $stock_table = $wpdb->prefix . 'wcpt_vouchers';

        /* -- Preparing your query -- */        
        $query  = "SELECT * FROM $stock_table";
        $p_args = array();
                
        $term 		  = $this->search_term;
        $stock_status = $this->stock_status;
        $term_array   = explode( ',', $term );
        $view		  = isset( $_GET['pl_view'] )     ? sanitize_text_field( $_GET['pl_view'] )     : '';
        $s_order_id   = isset( $_GET['pl-order-id'] ) ? intval( sanitize_text_field( $_GET['pl-order-id'] ) ) : '';
                
        if ( $term_array || $stock_status ) {
	        $query .= " WHERE ";
	        $first  = true;
        }
        if ( $term_array ) {
	        foreach ( $term_array as $term_val ) {
		        
		        $term_clean = trim( $term_val );
		        if ( $first ) {
			        if ( $view == 'orders' ) {
				        $query .= "( order_id LIKE %d";
				        $p_args[] = $term_clean;
			        }
			        else {
				        if ( !$s_order_id ) {
	        				$query .= "( voucher_serial LIKE %s OR voucher_pin LIKE %s OR product_name LIKE %s";
	        				$p_args[] = "%$term_clean%";
	        				$p_args[] = "%$term_clean%";
	        				$p_args[] = "%$term_clean%";
	        			}
	        			else {
		        			$query .= "( voucher_serial LIKE %s OR voucher_pin LIKE %s OR order_id=%d OR product_name LIKE %s";
		        			$p_args[] = "%$term_clean%";
		        			$p_args[] = "%$term_clean%";
		        			$p_args[] = $term_clean;
		        			$p_args[] = "%$term_clean%";
	        			}
	        		}
		        		$first = false;
		        }
		        else {
			        if ( $view == 'orders' ) {
				        $query .= " OR order_id=%d";
				        $p_args[] = $term_clean;
			        }
			        else {
		        		$query .= " OR order_id=%d";
		        		$p_args[] = $term_clean;
			        }
		        }
	        }
	        
	        $query .= ' )';
	        
	        if ( $s_order_id ) {
		        $query .= " AND order_id=%d";
		        $p_args[] = $s_order_id;
	        }
        }
                
        if ( $stock_status ) {
	        $sold = ( $stock_status == 'sold' ) ? 1 : 0;
	        if ( $first ) {
		        if ( $sold ) {
		        		$query .= "sold=%d";
		        		$p_args[] = $sold;
		        }
		        else {
			        $query .= "sold IS NULL";
		        }
	        }
	        else {
		        if ( $sold ) {
			        $query .= " AND sold=%d";
			        $p_args[] = $sold;
		        }
		        else {
			        $query .= " AND sold IS NULL";
		        }
	        }
        }
        
        /* -- Ordering parameters -- */
        // Parameters that are going to be used to order the result
        $orderby = ! empty( $_GET["orderby"] ) ? ( sanitize_text_field( $_GET["orderby"] ) ) : 'voucher_id';
        $order 	 = ! empty( $_GET["order"] ) ? ( sanitize_text_field( $_GET["order"] ) ) : 'ASC';
        if ( ! empty( $orderby ) && ! empty( $order ) ) {
	        if ( $order == 'asc' ) {
		        $query .= ' ORDER BY %i ASC';
	        } else {
		        $query .= ' ORDER BY %i DESC';
	        }
            $p_args[] = $orderby;
        }
        
        /* -- Pagination parameters -- */
        // Number of elements in your table?
        $totalitems = $wpdb->query( $wpdb->prepare( $query, $p_args ) ); // return the total number of affected rows
                                            // How many to display per page?
        $perpage = 50;
        if ( $this->stock_qtty ) {
	        $perpage = $this->stock_qtty;
        }
                        
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
            $query .= ' LIMIT %d,%d';
            $p_args[] = (int) $offset;
            $p_args[] = (int) $perpage;
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
        $this->items = $wpdb->get_results( $wpdb->prepare( $query, $p_args ) );
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
        $offset   = (float) get_option( 'gmt_offset' );
        $offset_s = $offset * 3600;
        
        // Loop for each record
        if ( !empty( $records ) ) {
            foreach ( $records as $rec ) {
	            
                // Open the line
                echo '<tr id="record_' . esc_attr( $rec->voucher_id ) . '">';
				$date = $rec->purchase_date;
				if ( $offset_s ) {
					$date = date( 'Y-m-d H:i:s', strtotime( $date ) + $offset_s );
				}
				
                foreach ( $columns as $column_name => $column_display_name ) {

                    // Style attributes for each col
                    $class = "class='$column_name column-$column_name'";
                    $style = '';
                    if ( in_array( $column_name, $hidden ) )
                        $style = ' style="display:none;"';
                    $attributes = $class . $style;
                    
                    $status = $rec->sold     ? 'sold'         : 'instock';
                    $order  = $rec->order_id ? $rec->order_id : 'N/A';
                                                            
                    // Display the cell
                    switch ( $column_name ) {
                        case 'voucher_pin':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $rec->voucher_pin ) . '</td>';
                            break;
                        case 'voucher_serial':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $rec->voucher_serial ) . '</td>';
                            break;
                        case 'product_name':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $rec->product_name ) . '</td>';
                            break;
                        case 'purchase_date':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $date ) . '</td>';
                            break;
					   case 'expires':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $rec->expires ) .'</td>';
                            break;
                        case 'status':
                        	   echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $status ) . '</td>';
                            break;
                        case 'order_id':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $order ) . '</td>';
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

    public function delete_this_product( $id ) {
	    
        global $wpdb;
        /* -- Preparing the delete query to avoid SQL injection -- */
        $query = $wpdb->prepare( "DELETE FROM $this->table_name WHERE voucher_id = %d", intval( $id ) );

        $wpdb->query( $query );
    }
}