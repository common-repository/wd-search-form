// Initialize global vars
var options = wdsf_options;
var searchWrapper = "div.wd_search";
var background = "div.shadowBackground";
var form = searchWrapper + " form";
var resultsField = searchWrapper + " div.acResults";
var searchField = searchWrapper + " input[name=s]";
var submitBtn = searchWrapper + " input[type=submit]";
var animateSpeed = 300;
var animate = options.animate == "on" ? true : false;
var showPoweredBy = options.show_powered_by == "on" ? true : false;

jQuery(document).ready(function ($) { // wordpress uses 'jquery' instead of '$'
	
	// Add bottom margin if "Powered By" link is visible
	if(showPoweredBy)
		$(searchWrapper).css({"margin-bottom" : "+=10"});
	
	// Remove animation effects if user unchecked "animate"
	if(!animate)
		$(searchWrapper).css({
			"background-color" : "white",
			"opacity" : "1"
		});
		
	// Disable submit to prevent empty form submission
	var disableSubmit = function(event) {
		// Prevent form submission
		event.preventDefault();
		// Focus on searchField
		$(searchField).focus();
		// Unbind this event 
		$(this).unbind('click.disableSubmit');
	}
	$(submitBtn).bind('click.disableSubmit', disableSubmit);
		
	// Focus/blur effect
	$(searchField).focus(function() {	
					
		// Change class and animate
		$(this).removeClass("idleField")
			   .addClass("focusField");
		
		// Add focus animation
		if(animate) {
			$(searchWrapper).animate({opacity : "1"}, animateSpeed);
			$(background).animate({opacity : "1"}, animateSpeed);
		}
		
		if (this.value == this.defaultValue){  
			this.value = ''; 
			
		}  
		if(this.value != this.defaultValue){  
			this.select();
		}
	})
	.blur(function() {
				
		// Change class and animate
		$(this).removeClass("focusField")
			   .addClass("idleField");
		
		// Add blur animation   
		if(animate) {
			$(searchWrapper).animate({opacity : "0.8"}, animateSpeed);
			$(background).animate({opacity : "0.2"}, animateSpeed);
		}
		
		// The search input is empty
		if ($.trim(this.value) == '') {  
			// Disable submit
			$(submitBtn).bind('click.disableSubmit', disableSubmit);
			
			this.value = this.defaultValue;
		}
	});
	
	// Autocomplete ajax call (as JSON data)    			
	if(options.enableAC == 'on') { // User has enabled autocomplete
		$("#tags").autocomplete({
			source: function( request, response ) {
				$.getJSON( 
					// Url
					options.ajaxUrl, 
					
					// Data
					{
						action: 'autocompleteCallback' ,
						nonce: options.nonce ,
						term: request.term ,
					}, 
					
					// Response
					response
				);
			},
			minLength: options.minLength,
			appendTo: resultsField,
			// Redirect on click
			select: function( event, ui ) { 
				window.location = ui.item.url;
			},
			autoFocus: true // Automatically selects the first item
		});
	}
});