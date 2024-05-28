<?php

if (! defined('ABSPATH')) {
    exit();
}

class PL_WCPT_Products_List_Table extends PL_WCPT_List_Table {

    private $search_term;
    private $account_currency;
    private $account_currency_symbol;
    private $per_page;
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
            'singular' => 'wp_list_pl_product', // Singular label
            'plural'   => 'wp_list_pl_products', // plural label, also this well be one of the table css class
            'ajax' 	   => false,
        ) ); // We won't support Ajax for this table
        
        if ( isset( $_REQUEST['pl-search-term-top'] ) ) {

          // Process filter data into array
          $this->search_term = sanitize_text_field( $_REQUEST['pl-search-term-top'] );
        }
        
        if ( isset( $_REQUEST['pl-per-page-top'] ) ) {

          // Process filter data into array
          $this->per_page = sanitize_text_field( $_REQUEST['pl-per-page-top'] );
        }
        
        $this->account_currency        = get_option( 'pl_wcpt_api_account_currency' ) ? get_option( 'pl_wcpt_api_account_currency' ) : 'USD';
        $this->account_currency_symbol = get_woocommerce_currency_symbol( $this->account_currency );
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

        $term     = $this->search_term;   
        $per_page = $this->per_page;     
        $is_50    = ( $per_page == '50' )  ? 'selected' : '';
        $is_100   = ( $per_page == '100' ) ? 'selected' : '';
        $page_v   = 25;
        		        
        echo "<input type='text' name='pl-search-term-top' id='pl-search-term-top' placeholder='Search Term'' value='" . esc_attr( $term ) . "'/>";
        
        echo "<select name='pl-per-page-top' id='pl-per-page-top'>
		  <option value=''>" . esc_attr( $page_v ) . " per page</option>
		  <option " . esc_attr( $is_50 ) . " value='50'>50 per page</option>
		  <option " . esc_attr( $is_100 ) . " value='100'>100 per page</option>
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
            'oem' 		  	    => __( 'OEM', 'gift-cards-on-demand-free' ),
            'brand' 		    => __( 'Brand', 'gift-cards-on-demand-free' ),
            'product' 		    => __( 'Product', 'gift-cards-on-demand-free' ),
            'linked' 		  	=> __( 'Linked', 'gift-cards-on-demand-free' ),
            'sell_price'        => $this->account_currency . ' ' . __( 'Buy Price', 'gift-cards-on-demand-free' ),
		    'stock' 		    => __( 'Local Stock', 'gift-cards-on-demand-free' ),
		    'status' 		    => __( 'Status', 'gift-cards-on-demand-free' ),
        );
    }

    public function column_cb( $item ) {
	    
        return sprintf( '<th scope="row" class="check-column"><input type="checkbox" name="%1$s[]" value="%2$s" /></th>', esc_attr( $this->_args['singular'] ), esc_attr( $item->product_id ) );
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
	        $items = array_map( 'sanitize_text_field', $_GET[$this->_args['singular']]  );
            foreach ( $items as $item ) {
                $this->delete_this_product( $item );
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
            'oem' => array(
                'name',
                false
            ),
            'brand' => array(
                'brand_name',
                false
            ),
            'product' => array(
                'product_name',
                false
            ),
            'linked' => array(
                'is_linked',
                false
            ),
            'sell_price' => array(
                'sell_price',
                false
            ),
             'status' => array(
                'discontinued',
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
        
        $table_name     = $wpdb->prefix . 'wcpt_products';
        $oem_table      = $wpdb->prefix . 'wcpt_oem';
        $postmeta_table = $wpdb->postmeta;
        $p_args 		= array();

        /* -- Preparing your query -- */        
        $query = "SELECT p.sell_price, p.unit_price, p.base_currency_symbol ,p.brand_name, p.product_name, p.product_id, o.name, p.image_url, p.discontinued, pm.post_id as wc_product_id, pm2.meta_value as stock, pm3.meta_value as override_price, CASE WHEN pm.post_id is null THEN 0 ELSE 1 END as is_linked from $table_name p INNER JOIN $oem_table o ON p.oem_id = o.oem_id LEFT JOIN $postmeta_table pm ON p.product_id = pm.meta_value AND pm.meta_key = '_pt_product' LEFT JOIN $postmeta_table pm2 ON pm.post_id = pm2.post_id AND pm2.meta_key = '_stock' LEFT JOIN $postmeta_table pm3 ON pm.post_id = pm3.post_id AND pm3.meta_key = '_regular_price'";
                
        $term = $this->search_term;
        if ( $term ) {
	        $query .= " WHERE p.brand_name LIKE %s OR p.product_name LIKE %s OR o.name LIKE %s";
	        $p_args[] = "%$term%";
	        $p_args[] = "%$term%";
	        $p_args[] = "%$term%";
        }

        /* -- Ordering parameters -- */
        // Parameters that are going to be used to order the result
        $orderby = ! empty( $_GET["orderby"] ) ? ( sanitize_text_field( $_GET["orderby"] ) ) : 'name, brand_name, product_name';
        $order 	 = ! empty( $_GET["order"] ) ? ( sanitize_text_field( $_GET["order"] ) ) : 'ASC';
        if ( ! empty( $orderby ) && ! empty( $order ) ) {
	        $order_by_array 	  = explode( ', ', $orderby );
	        $order_by_placeholder = substr( str_repeat( ',%i', count( $order_by_array ) ), 1 );
	        if ( $order == 'asc' ) {
		        $query .= ' ORDER BY ' . $order_by_placeholder . ' ASC';
	        } else {
		        $query .= ' ORDER BY ' . $order_by_placeholder . ' DESC';
	        }
	        $p_args   = array_merge( $p_args, $order_by_array );
        }
        
        $p_query = $p_args ? $wpdb->prepare( $query, $p_args ) : $query;
        
        /* -- Pagination parameters -- */
        // Number of elements in your table?
        $totalitems = $wpdb->query( $p_query ); // return the total number of affected rows
                                            // How many to display per page?
        $perpage = 25;
        if ( $this->per_page ) {
	        $perpage = $this->per_page;
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
        
        $p_query = $p_args ? $wpdb->prepare( $query, $p_args ) : $query;
        
        /* -- Fetch the items -- */
        $this->items = $wpdb->get_results( $p_query );
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
        $markup_val		  = ( get_option( 'pl_wcpt_api_import_markup_val' ) ) ? get_option( 'pl_wcpt_api_import_markup_val' ) : 0;
        $currency_symbol  = get_woocommerce_currency_symbol( get_option( 'woocommerce_currency' ) );
        $stock_db 		  = new PL_WCPT_Vouchers_DB();
        $align_right      = 'pl_align_right';

        // Loop for each record
        if ( !empty( $records ) ) {
            foreach ( $records as $rec ) {
                // Open the line
                echo '<tr id="record_' . esc_attr( $rec->product_id ) . '">';
                $product = 0;
                
                foreach ( $columns as $column_name => $column_display_name ) {
					
                    // Style attributes for each col
                    $class  = "class='$column_name column-$column_name'";
                    $classa = "class='$column_name column-$column_name $align_right'";
                    $style  = '';
                    if ( in_array( $column_name, $hidden ) )
                        $style = ' style="display:none;"';
                    $attributes  = $class . $style;
                    $attributesa = $classa . $style;
                    
                    if ( $rec->wc_product_id ) {
	                    
	                    $products = array( get_post( $rec->wc_product_id ) );
                    } else {
	                    
	                    $args = array(
					        'post_type'         => 'product',
					        'post_status'	    => 'publish',
					        'posts_per_page'    => 1,
					        'fields'		    => 'ids',
					        'meta_query' => array(
					            array(
					                'key' => '_pt_product',
					                'value' => $rec->product_id
					            )
					        )
					   );
					   
					   $products = get_posts( $args );
				   }
                    
                    if ( $products ) {
	                    
	                    $product 	 = wc_get_product( $products[0] ); 
	                    $permalink   = get_edit_post_link( $products[0] );
	                    
	                    $linked      = "<a href='" . esc_attr( $permalink ) . "'>Link</a>";
	                    $stock 		 = $stock_db->get_active_vouchers_count_by_product_id( $rec->product_id );
	                }
	                else {
	                                        
	                    $linked	= '-';
	                    $stock 	= '-';
                    }
                    
                    // Currency Conversion					
				   $price 		   = round( $rec->sell_price * $conversion_value, 2 );
				   $converted_price = wc_price( $price );
				   $price 		  += $price * $markup_val / 100;
				   $base_price    = $product ? $product->get_price() : 0;
				   $status = $rec->discontinued ? "<span style='color:red'>"  . esc_attr__( 'Discontinued', 'gift-cards-on-demand-free' ) . "</span>" : "<span style='color:green'>" . esc_attr__( 'Active', 'gift-cards-on-demand-free' ) . "</span>";
                     
                    // Display the cell
                    switch ( $column_name ) {
                        case 'oem':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $rec->name ) . '</td>';
                            break;
                        case 'brand':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $rec->brand_name ) . '</td>';
                            break;
                        case 'product':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $rec->product_name ) . '</td>';
                            break;
                        case 'linked':
                            echo '<td ' . esc_attr( $attributes ) . '>' . wp_kses_post( $linked ) . '</td>';
                            break;
					   case 'sell_price':
                            echo '<td ' . esc_attr( $attributesa ) . '>' . esc_attr( $this->account_currency_symbol ) . esc_attr( $rec->sell_price ) .'</td>';
                            break;
                        case 'stock':
                            echo '<td ' . esc_attr( $attributes ) . '>' . esc_attr( $stock ) . '</td>';
                            break;
                        case 'status':
                            echo '<td ' . esc_attr( $attributes ) . '>' . wp_kses_post( $status ) . '</td>';
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
        
        $table_name = $wpdb->prefix . 'wcpt_products';
        
        /* -- Preparing the delete query to avoid SQL injection -- */
        $query = $wpdb->prepare( "DELETE FROM $table_name WHERE product_id = %d", intval( $id ) );

        $wpdb->query( $query );
    }
}