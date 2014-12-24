<?php
register_activation_hook(PHC_WOOCOMMERCE_EXPORT_FILE, 'phc_woocommerce_export_install');
function phc_woocommerce_export_install(){
	if( ! is_plugin_active('woocommerce/woocommerce.php') ){
		$plugin= basename(PHC_WOOCOMMERCE_EXPORT_FILE, ".php") . '/' . basename(PHC_WOOCOMMERCE_EXPORT_FILE);
		deactivate_plugins($plugin, false, is_network_admin());
		if ( ! is_network_admin() ){
			update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );
		}
		if ( headers_sent() ){
			echo "<meta http-equiv='refresh' content='" . esc_attr( "0;url=plugins.php?deactivate=true&plugin_status=$status&paged=$page&s=$s" ) . "' />";
		}else{
			wp_redirect(self_admin_url("plugins.php?deactivate=true&plugin_status=$status&paged=$page&s=$s"));
		}
		exit;
	}
}

add_action('load-woocommerce_page_phc_woocommerce_export_settings', 'phc_woocommerce_export');
function phc_woocommerce_export(){
//	echo "data";
//	exit;
	if ( isset($_POST['action']) && 'update' == $_POST['action'] ) {
//		header('content-type: text/plain');
//		print_r($_POST);
//		exit;
		
		// Verifying a Nonce
		if ( 'options' == $_POST['option_page'] && !isset( $_POST['option_page'] ) ) { // This is for back compat and will eventually be removed.
			$unregistered = true;
			check_admin_referer( 'update-options' );
		} else {
			$unregistered = false;
			check_admin_referer( $_POST['option_page'] . '-options' );
		}
//		$_POST['phc_woocommerce_export']['from']= "";
		$date_from= ( ! empty($_POST['phc_woocommerce_export']['from']) ) ? explode("/", $_POST['phc_woocommerce_export']['from']) : "";
		$date_to= ( ! empty($_POST['phc_woocommerce_export']['to']) ) ? explode("/", $_POST['phc_woocommerce_export']['to']) : "";

		// Create CSV file
		$filename= "woocommerce-orders-" . date("Y-m-d-H-i-s") . ".csv";
		$columns= array(
		"order_status"=>"Order Status",
		"order_title"=>"Order ID",
		"order_date"=>"Order Date",
		"order_total"=>"Order Total",
		"customer_message"=>"Customer Message",
		"order_items"=>"Order Items",
		"shipping_address"=>"Shipping Address",
		"order_notes"=>"Order Notes",
//		"order_actions"=>"Order Actions",
		);
		// Hook Filter to modify columns
		$columns= apply_filters('phc_woocommerce_export_columns', $columns);
		
//		header( 'Content-Description: File Transfer' );
//		header( 'Content-Disposition: attachment; filename=' . $filename );
//		header( 'Content-Type: text/csv; charset=' . get_option( 'blog_charset' ), true );
		header('content-type: text/plain');
		
		$args= array('post_type'=>'shop_order', 'nopaging'=>true, 'post_status'=>'any', 'date_query'=>array());
		$condition_date= array();
		$condition_date_from= array();
		if( ! empty($date_from) ){
			$condition_date['after']= array(
			'year'  => $date_from[0],
			'month' => $date_from[1],
			'day'   => $date_from[2],
			'hour'=>'00',
			'minute'=>'00',
			'second'=>'00',
			);
		}
		
		$condition_date_to= array();
		if( ! empty($date_to) ){
			$condition_date['before']= array(
			'year'  => $date_to[0],
			'month' => $date_to[1],
			'day'   => $date_to[2],
			'hour'=>'00',
			'minute'=>'00',
			'second'=>'00',
			);
		}
		$args['date_query']= array($condition_date);
		// Instantiate WP_Query Object
		$the_query= new WP_Query($args);
//		echo "<pre>";
//		print_r($the_query);
//		echo "</pre>";
//		exit;
		// Display Columns
		echo '"' . implode('","', $columns) . '"' . "\r\n";
		
		// Display Order Data
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) {
				$the_query->the_post();
				$post= get_post();
				$custom= get_post_custom($post->ID);
				$the_order = wc_get_order($post->ID);
//				print_r($custom);
//				echo "<br/>#####################################################################################<br/>";				
				$data= array();
				foreach( $columns as $column=>$value ){
					$str= "";
				switch ( $column ) {
					case 'order_status' :
						$str= wc_get_order_status_name($the_order->get_status());
//						echo wrapper_quotes($str);
//						echo ",";
						$data[]= $str;
					break;
					case 'order_date' :
						if ( '0000-00-00 00:00:00' == $post->post_date ) {
							$t_time = $h_time = __( 'Unpublished', 'woocommerce' );
						} else {
							$t_time    = get_the_time( __( 'Y/m/d g:i:s A', 'woocommerce' ), $post );
							$gmt_time  = strtotime( $post->post_date_gmt . ' UTC' );
							$time_diff = current_time( 'timestamp', 1 ) - $gmt_time;
							$h_time    = get_the_time( __( 'Y/m/d', 'woocommerce' ), $post );
						}
						$str= esc_html( apply_filters('post_date_column_time', $h_time, $post));
//						echo wrapper_quotes($str);
//						echo ",";
						$data[]= $str;

					break;
					case 'customer_message' :
						if ( $the_order->customer_message ){
							$str= $the_order->customer_message;
						}else{
							$str='-';
						}
//						echo wrapper_quotes($str);
//						echo ",";
						$data[]= $str;
					break;
					case 'order_items' :
						if ( sizeof( $the_order->get_items() ) > 0 ) {
							foreach ( $the_order->get_items() as $item ) {
								$_product       = apply_filters( 'woocommerce_order_item_product', $the_order->get_product_from_item( $item ), $item );
								$item_meta      = new WC_Order_Item_Meta( $item['item_meta'] );
								$item_meta_html = $item_meta->display( true, true );
								$str.= absint( $item['qty'] ) . " " . apply_filters( 'woocommerce_order_item_name', $item['name'], $item ) . ", ";
							}
							$str= substr($str, 0, -2);
						}else{
							$str= '-';
						}
//						echo wrapper_quotes($str);
//						echo ",";
						$data[]= $str;
					break;
					case 'shipping_address' :
						if ( $the_order->get_formatted_shipping_address() ){
							$str= esc_html( preg_replace( '#<br\s*/?>#i', ', ', $the_order->get_formatted_shipping_address() ) );
						}else{
							$str= '-';
						}
						if ( $the_order->get_shipping_method() ){
							$str.= __(' Via', 'woocommerce' ) . ' ' . esc_html( $the_order->get_shipping_method() );
						}
//						echo wrapper_quotes($str);
//						echo ",";
						$data[]= $str;
					break;
					case 'order_notes' :
						if ( $post->comment_count ) {
							$str= __( 'Yes', 'woocommerce' );
						} else {
							$str= '-';
						}
//						echo wrapper_quotes($str);
//						echo ",";
						$data[]= $str;
					break;
					case 'order_total' :
						if ( $the_order->payment_method_title ) {
							$str= html_entity_decode(strip_tags( $the_order->get_formatted_order_total() )) . ' ' . ( $the_order->payment_method_title );
						}
//						echo wrapper_quotes($str);
//						echo ",";
						$data[]= $str;
					break;
					case 'order_title' :
						$str= esc_attr($the_order->get_order_number());
//						echo wrapper_quotes($str);
						$data[]= $str;
//						echo ",";
					break;
				}
//				echo "\r\n";
				}
				// Hook Filter to add data while add extra columns
				$data= apply_filters('phc_woocommerce_export_columns_data', $data, $post);

				echo '"' . implode('","', $data) . '"';
				echo "\r\n";
			}
		}
		// Restore original Post Data 
		wp_reset_postdata();
		exit;
	}
}

function wrapper_quotes($string){
	return '"' . $string . '"';
}

//add_action('admin_menu', 'phc_woocommerce_export_create_menu_settings');
add_action('admin_menu', 'phc_woocommerce_export_create_menu_settings');
function phc_woocommerce_export_create_menu_settings(){
	$function= "phc_woocommerce_export_settings_page";
	$page= add_submenu_page(PHC_WOOCOMMERCE_EXPORT_MENU_SLUG, PHC_WOOCOMMERCE_EXPORT_PAGE_TITLE_SETTINGS, 
	PHC_WOOCOMMERCE_EXPORT_MENU_TITLE_SETTINGS, PHC_WOOCOMMERCE_EXPORT_SUBMENU_CAPABILITY, 
	PHC_WOOCOMMERCE_EXPORT_MENU_SLUG_SETTINGS, $function);
	
	add_action('admin_init', 'phc_woocommerce_export_register_settings');
}

function phc_woocommerce_export_register_settings(){
	register_setting('phc_woocommerce_export_settings_page_vars', 
	'phc_woocommerce_export_settings_vars');
//	register_setting('phc_woocommerce_export_settings_page_vars', 
//	'phc_woocommerce_export_settings_vars');
}

function phc_woocommerce_export_settings_page(){
	global $wp_query;
	
	wp_enqueue_style(PHC_WOOCOMMERCE_EXPORT_ID_SCRIPT . '_jquery_ui_min_css', 
	PHC_WOOCOMMERCE_EXPORT_PATH_URL_CSS . "jquery-ui.min.css");
	wp_enqueue_style(PHC_WOOCOMMERCE_EXPORT_ID_SCRIPT . '_settings_css', 
	PHC_WOOCOMMERCE_EXPORT_PATH_URL_CSS . "settings.css");
	wp_enqueue_script(PHC_WOOCOMMERCE_EXPORT_ID_SCRIPT . '_settings_js', 
	PHC_WOOCOMMERCE_EXPORT_PATH_URL . "js/settings/settings.js", array("jquery-ui-datepicker", "jquery-ui-tabs"));
	$phc_woocommerce_export_settings_vars= get_option('phc_woocommerce_export_settings_vars');
	if( ! empty($phc_woocommerce_export_settings_vars) ){
		extract($phc_woocommerce_export_settings_vars);
	}
?>
	<div class="wrap" id="<?php echo PHC_WOOCOMMERCE_EXPORT_IDENTIFIER; ?>">
	<h2><?php _e('WooCommerce Export', PHC_WOOCOMMERCE_EXPORT_IDENTIFIER); ?></h2>
	<?php
	/*
	<input type="hidden" name="page" value="<?php echo PHC_WOOCOMMERCE_EXPORT_MENU_SLUG_SETTINGS; ?>" />
	<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce("update"); ?>" />
	*/
	?>
	<div id="tabs">
		<ul>
		<li><a href="#tabs-orders"><?php _ex("Order", "Settings Tab", PHC_WOOCOMMERCE_EXPORT_IDENTIFIER); ?></a></li>
		<!--<li><a href="#tabs-customer"><?php _ex("Customer", "Settings Tab", PHC_WOOCOMMERCE_EXPORT_IDENTIFIER); ?></a></li>-->
		</ul>
		<div id="tabs-orders">
		<form method="POST" action="">
			<?php settings_fields('phc_woocommerce_export_settings_page_vars'); ?>
			<input type="hidden" name="phc_woocommerce_export[type]" value="order" />
			<div class="info-msg">
			<p>Choose date to export Orders from specific date</p>
			</div>
			<table class="form-table">
			<tr valign="top">
			    <th scope="row">
				<?php _e("From", PHC_WOOCOMMERCE_EXPORT_IDENTIFIER); ?>
				</th>
			    <td>
				<input type="text" name="phc_woocommerce_export[from]" value="" class="regular-text date" placeholder="yyyy-mm-dd" />
			   	</td>
		   	</tr>
			<tr valign="top">
			    <th scope="row">
				<?php _e("To", PHC_WOOCOMMERCE_EXPORT_IDENTIFIER); ?>
				</th>
			    <td>
				<input type="text" name="phc_woocommerce_export[to]" value="" class="regular-text date" placeholder="yyyy-mm-dd" />
			   	</td>
		   	</tr>
			</table>
			<div class="btn-group-controls">
			<input type="submit" name="save" value="<?php esc_attr_e("Download Export File", PHC_WOOCOMMERCE_EXPORT_IDENTIFIER); ?>" class="button-primary" />
			</div>
		</form>
		</div>
<!--		<div id="tabs-customer">
		<form method="POST" action="">
			<?php settings_fields('phc_woocommerce_export_settings_page_vars'); ?>
			<input type="hidden" name="phc_woocommerce_export[type]" value="customer" />
			<div class="updated woocommerce-message below-h2">
			<p>You can export Order Data from specific date</p>
			</div>
			<div class="btn-group-controls">
			<input type="submit" name="save" value="<?php esc_attr_e("Download Export File", PHC_WOOCOMMERCE_EXPORT_IDENTIFIER); ?>" class="button-primary" />
			</div>
		</form>
		</div>-->
	</div>
	</div>
<?php
}

final class PHC_WooCommerce_Export {
	function __construct() {
	}
	
	function get_orders_data(){
	}
}
?>