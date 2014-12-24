jQuery(document).ready( function($){
	// Set Tabs
	$( "#tabs" ).tabs();
	
	// Set Datepicker
	datepickerArgs= {
	dateFormat: "yy/mm/dd",
	changeMonth: true,
	changeYear: true
	}
	$(".date").datepicker(datepickerArgs);
})