<?php
// Adding Custom Field - WooCommerce Export Order
add_filter('phc_woocommerce_export_columns' , 'custom_phc_woocommerce_export_columns');
function custom_phc_woocommerce_export_columns($columns){
	$columns["_billing_referral"]= "Referral";
	$columns["_billing_gender"]= "Gender";
	return $columns;
}

// Adding Data for Custom Field - WooCommerce Export Order
add_filter('phc_woocommerce_export_columns_data' , 'custom_phc_woocommerce_export_columns_data', 10, 2);
function custom_phc_woocommerce_export_columns_data($data, $post){
	$custom= get_post_custom($post->ID);
	$data[]= isset($custom['_billing_referral'][0]) ? $custom['_billing_referral'][0] : "";
	$data[]= isset($custom['_billing_gender'][0]) ? $custom['_billing_gender'][0] : "";
	return $data;
}
?>