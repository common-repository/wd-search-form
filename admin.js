// Toggle the fieldset on/off by checking the checkbox
jQuery(document).ready(function ($) {
	// Toggle disable #autocomplete fieldset using live
	// to make it available after ajax calls
	$("input.enableAC").live("change", function() {
	    $("fieldset#autocomplete").prop('disabled', !$(this).prop('checked'));
	});
});
