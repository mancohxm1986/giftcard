<?php

if (! defined('ABSPATH')) {
    exit();
}

class PL_WCPT_Reserved_Stock_List_Table extends PL_WCPT_List_Table {

    private $search_term;
    private $order_status;
    private $allowed_html = array(
	    'input' => array(
	        'type'      => array(),
	        'name'      => array(),
	        'value'     => array(),
	        'checked'   => array()
	    ),
	    'option' => array(
		    'selected' => array(),
		    'value'	   => array(),
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
            'singular' => 'wp_list_pl_reserved_stock', // Singular label
            'plural'   => 'wp_list_pl_reserved_stocks', // plural label, also this well be one of the table css class
            'ajax' 	   => false,
        ) ); // We won't support Ajax for this table        
        
        if ( isset( $_REQUEST['pl-search-term-top'] ) ) {

          // Process filter data into array
          $this->search_term = sanitize_text_field( $_REQUEST['pl-search-term-top'] );
        }
        
        if ( isset( $_REQUEST['pl-order-status-top'] ) ) {

          // Process filter data into array
          $this->order_status = sanitize_text_field( $_REQUEST['pl-order-status-top'] );
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
	      
        $term 	      = $this->search_term;
        $order_status = $this->order_status;
        
        $is_proc 	    = ( $order_status == 'processing' ) ? 'selected' : '';
        $is_comp 	    = ( $order_status == 'completed' )  ? 'selected' : '';
        $code_purchase  = ( get_option( 'pl_wcpt_api_code_purchase' ) ) ? get_option( 'pl_wcpt_api_code_purchase' ) : 'processing';
        $processing_opt = $code_purchase == 'completed' ? '' : "<option " . esc_attr( $is_proc ) . " value='processing'>Processing</option>";
        
        echo "<input type='text' name='pl-search-term-top' id='pl-search-term-top' placeholder='Search Term'' value='". esc_attr( $term ) ."'/>";
        echo "<select name='pl-order-status-top' id='pl-order-status-top'>
		  <option value=''>All</option>
		  " . wp_kses( $processing_opt, $this->allowed_html ) . "
		  <option ". esc_attr( $is_comp ) ." value='completed'>Completed</option>
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
	        'cb' 			    => '<input type="checkbox" />',
	        'order_id'          => __( 'Order', 'gift-cards-on-demand-free' ),
	        'status'            => __( 'Status', 'gift-cards-on-demand-free' ),
            'product_title'     => __( 'Product', 'gift-cards-on-demand-free' ),
            'quantity'          => __( 'Quantity', 'gift-cards-on-demand-free' ),
        );
    }

    public function column_cb( $item ) {
	    
        return sprintf( '<th scope="row" class="check-column"><input type="checkbox" name="%1$s[]" value="%2$s" /></th>', esc_attr( $this->_args['singular'] ), esc_attr( $item->order_id ) );
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
	                $this->delete_this_voucher( $item );
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
            'product_title' => array(
                'product_title',
                false
            ),
            'order_id' => array(
                'order_id',
                false
            ),
            'status' => array(
                'status',
                false
            )
        );
    }
    
    public function high_performance_order_storage() {
	    if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) && Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() && get_option( 'woocommerce_custom_orders_table_data_sync_enabled' ) != 'yes' ) {
		    return true;
		}
		
		return false;
    }

    /**
     * Prepare the table with different parameters, pagination, columns and table elements
     */
    public function prepare_items() {
	    
        global $wpdb;
        $screen = get_current_screen();

        /* -- Check if there are bulk actions -- */
        $this->process_bulk_action();
        
        $stock_table    = "{$wpdb->prefix}wcpt_vouchers";
		$order_itemmeta = "{$wpdb->prefix}woocommerce_order_itemmeta";
		$order_items    = "{$wpdb->prefix}woocommerce_order_items";
		$products       = "{$wpdb->prefix}wcpt_products";
		$is_hpos 		= $this->high_performance_order_storage();
		$posts		    = $wpdb->posts;		
		$postmeta       = $wpdb->postmeta;
		$statuses       = array( 'wc-processing', 'wc-completed' );
		
		$term 		    = $this->search_term;
		$order_status   = $this->order_status;
		if ( $order_status == 'completed' ) {
			unset( $statuses[0] );
		} else if( $order_status == 'processing' ) {
			unset( $statuses[1] );
		}
		
		$code_purchase = ( get_option( 'pl_wcpt_api_code_purchase' ) ) ? get_option( 'pl_wcpt_api_code_purchase' ) : 'processing';
		if ( $code_purchase == 'completed' ) {
			$statuses       = array( 'wc-completed' );
		}
		
		$cutoff_date = date( 'Y-m-d', strtotime( '-2 months' ) );
		$p_args 	 = array();
		
		$status_placeholder = substr( str_repeat( ',%s', count( $statuses ) ), 1 );
		
		if ( $is_hpos ) {
			$orders_db = Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore::get_orders_table_name();
			$query = "SELECT order_id, im3.meta_value as quantity, pt.status as status, im.meta_value as product_id, ptt.post_title as product_title from $order_itemmeta im LEFT JOIN $order_itemmeta im2 ON im.order_item_id = im2.order_item_id AND im2.meta_key = '_pl_voucher_to_user' INNER JOIN $order_items oi ON im.order_item_id = oi.order_item_id INNER JOIN $order_itemmeta im3 ON im.order_item_id = im3.order_item_id AND im3.meta_key = '_qty' INNER JOIN $orders_db as pt ON oi.order_id = pt.ID INNER JOIN $postmeta pm ON pm.meta_key = '_pt_product' AND pm.post_id = im.meta_value INNER JOIN $posts ptt ON ptt.ID = im.meta_value WHERE im.meta_key = '_product_id' AND ( im2.meta_key IS NULL OR im2.meta_value = '' ) AND pt.status IN ( $status_placeholder ) AND pt.date_created_gmt >= %s AND pm.meta_key != ''";
		} else {
			$query = "SELECT order_id, im3.meta_value as quantity, pt.post_status as status, im.meta_value as product_id, ptt.post_title as product_title from $order_itemmeta im LEFT JOIN $order_itemmeta im2 ON im.order_item_id = im2.order_item_id AND im2.meta_key = '_pl_voucher_to_user' INNER JOIN $order_items oi ON im.order_item_id = oi.order_item_id INNER JOIN $order_itemmeta im3 ON im.order_item_id = im3.order_item_id AND im3.meta_key = '_qty' INNER JOIN $posts as pt ON oi.order_id = pt.ID INNER JOIN $postmeta pm ON pm.meta_key = '_pt_product' AND pm.post_id = im.meta_value INNER JOIN $posts ptt ON ptt.ID = im.meta_value WHERE im.meta_key = '_product_id' AND ( im2.meta_key IS NULL OR im2.meta_value = '' ) AND pt.post_status IN ( $status_placeholder ) AND pt.post_date >= %s AND pm.meta_key != ''";
		}
		
		$p_args   = array_merge( $p_args, $statuses );
		$p_args[] = $cutoff_date;
				
		if ( $term ) {
			$term_clean = trim( $term );
			$query .= " AND ( ptt.post_title=%s || order_id=%d || ptt.post_title=%s)";
			$p_args[] = $term_clean;
			$p_args[] = $term_clean;
			$p_args[] = $term_clean;
		}		
		        
        /* -- Ordering parameters -- */
        // Parameters that are going to be used to order the result
        $orderby = ! empty( $_GET["orderby"] ) ? sanitize_text_field( $_GET["orderby"] ) : 'product_title';
        $order 	 = ! empty( $_GET["order"] ) ? sanitize_text_field( $_GET["order"] ) : 'asc';
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
        $perpage = 25;
        // Which page is this?
        $paged = ! empty( $_GET["paged"] ) ? sanitize_text_field( $_GET["paged"] ) : '';
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
        
        // Loop for each record
        if ( !empty( $records ) ) {
            foreach ( $records as $k => $rec ) {
                // Open the line
                echo '<tr id="record_' . esc_attr( $k ) . '">';

                foreach ( $columns as $column_name => $column_display_name ) {

                    // Style attributes for each col
                    $class = "class='$column_name column-$column_name'";
                    $style = '';
                    if ( in_array( $column_name, $hidden ) )
                        $style = ' style="display:none;"';
                    $attributes = $class . $style;
                    
                    $status	   = str_replace( 'wc-', '', $rec->status );
                    $order_obj = $rec->order_id ? wc_get_order( $rec->order_id ) : array();
                    $order     = $order_obj ? "<a href='" . esc_url( $order_obj->get_edit_order_url() ) . "'>" . "Order " . esc_attr( $rec->order_id ) . "</a>" : 'N/A';
                    $product   = $rec->product_id ? "<a href='" . esc_url( get_edit_post_link( $rec->product_id ) ) . "'>" . esc_attr( $rec->product_title ) . "</a>" : 'N/A';
                                                            
                    // Display the cell
                    switch ( $column_name ) {
                        case 'product_title':
                            echo '<td ' . esc_attr( $attributes ) . '>' . wp_kses_post( $product ) . '</td>';
                            break;
                        case 'quantity':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $rec->quantity ) . '</td>';
                            break;
                        case 'order_id':
                            echo '<td ' . esc_attr( $attributes ) . '>' . wp_kses_post( $order ) . '</td>';
                            break;
                        case 'status':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( ucfirst( $status ) ) . '</td>';
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

    public function delete_this_voucher( $id ) {
	    
        global $wpdb;
        /* -- Preparing the delete query to avoid SQL injection -- */
        $query = $wpdb->prepare( "DELETE FROM $this->table_name WHERE voucher_id = %d", intval( $id ) );

        $wpdb->query( $query );
    }
}