//********************************************************
// start the engine
//********************************************************
jQuery(document).ready( function($) {

//********************************************************
// handle repeating fields for info
//********************************************************

	$( 'input#add-info' ).on('click', function() {

		// remove any existing messages
		$('#wpbody div#message').remove();

		// clone the fields
		var newfield = $( 'div.empty-info-row.screen-reader-text' ).clone(true);

		// make it visible
		newfield.removeClass( 'empty-row screen-reader-text' );

		// and now insert it
		newfield.insertAfter( 'div#dgm-info-table div.info-entry:last' );

		// add the class
		newfield.addClass('info-entry');

		// and move the cursor
		newfield.find('input.info-header').focus();

	});

	$( 'span.remove-info' ).on('click', function() {
		$(this).parents('div.info-entry').find('input [type="text"]').val('');
		$(this).parents('div.info-entry').find('textarea').val('');
		$(this).parents('div.info-entry').remove();
	});


//********************************************************
// handle repeating fields for code
//********************************************************

	$( 'input#add-code' ).on('click', function() {

		// remove any existing messages
		$('#wpbody div#message').remove();

		// clone the fields
		var newfield = $( 'div.empty-code-row.screen-reader-text' ).clone(true);

		// make it visible
		newfield.removeClass( 'empty-row screen-reader-text' );

		// and now insert it
		newfield.insertAfter( 'div#dgm-code-table div.code-entry:last' );

		// add the class
		newfield.addClass('code-entry');

		// and move the cursor
//		newfield.find('input.code-label').focus();

	});

	$( 'span.remove-code' ).on('click', function() {
		$(this).parents('div.code-entry').find('input [type="text"]').val('');
		$(this).parents('div.code-entry').remove();
	});

//********************************************************
// AJAX drag and drop sort
//********************************************************

	$('div.custom-sort-wrap').each(function() {

		var sortList = $('ul#custom-type-list');

		sortList.sortable({
			update: function(event, ui) {
				$('#loading-animation').show(); // Show the animate loading gif while waiting

				opts = {
					url: ajaxurl, // ajaxurl is defined by WordPress and points to /wp-admin/admin-ajax.php
					type: 'POST',
					async: true,
					cache: false,
					dataType: 'json',
					data:{
						action: 'save_sort', // Tell WordPress how to handle this ajax request
						order: sortList.sortable('toArray').toString() // Passes ID's of list items in	1,3,2 format
					},
					success: function(response) {
						$('div#message').remove();
						$('#loading-animation').hide(); // Hide the loading animation
						$('div.custom-sort-wrap h2:first').after('<div id="message" class="updated sort-message below-h2"><p>Item sort order has been saved</p></div>');
						$('div.sort-message').delay(3000).slideUp('slow');
						return;
					},
					error: function(xhr,textStatus,e) {
						$('#loading-animation').hide(); // Hide the loading animation
						$('div.custom-sort-wrap h2:first').after('<div id="message" class="error sort-message below-h2"><p>There was an error saving the sort order. Please try again later.</p></div>');
						$('div.sort-message').delay(3000).slideUp('slow');
						return;
					}
				};
				$.ajax(opts);
			}
		});
	});

//********************************************************
// that's all folks. we're done here
//********************************************************

});
