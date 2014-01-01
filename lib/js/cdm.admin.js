//********************************************************
// start the engine
//********************************************************
jQuery(document).ready( function($) {


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
		newfield.insertAfter( 'div#cdm-code-table div.code-entry:last' );

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
// allow tabbing on textareas
//********************************************************

	$( 'span.code-block' ).each(function() {

		$( this ).delegate( 'textarea', 'keydown', function(e) {
			var keyCode = e.keyCode || e.which;

			if ( keyCode == 9 ) {
				e.preventDefault();
				var start	= $( this ).get(0).selectionStart;
				var end		= $( this ).get(0).selectionEnd;

				// set textarea value to: text before caret + tab + text after caret
				$( this ).val( $( this ).val().substring( 0, start )
					+ "\t"
					+ $( this ).val().substring( end ) );

				// put caret at right position again
				$( this ).get(0).selectionStart =
				$( this ).get(0).selectionEnd = start + 1;
			}
		});

	});

//********************************************************
// sortable code blocks
//********************************************************

	$( 'div.cdm-data-table' ).each(function() {
		$( 'div.code-table-content' ).sortable({
			handle: '.cdm-sort-trigger',
			cursor: 'move',
		});
	});

//********************************************************
// AJAX drag and drop sort
//********************************************************
/*
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
*/
//********************************************************
// that's all folks. we're done here
//********************************************************

});
