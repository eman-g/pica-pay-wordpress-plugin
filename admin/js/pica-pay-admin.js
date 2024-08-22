(function( $ ) {
	'use strict';

	$(document).ready(function() {
		// Bind to the quick edit save event
		$('#the-list').on('click', '.editinline', function () {
			var post_id = $(this).closest('tr').attr('id').replace('post-', '');
			var $checkbox = $('#edit-' + post_id).find('input[name="pica_pay_paid"]');

			// Get the current value of the meta field
			var is_paid = $('#post-' + post_id).find('.column-pica_pay_paid').text().trim() === 'Yes';
			let checkboxSet = false;

			// Wait for the quick edit form to be displayed
			$(document).ajaxComplete(function () {
				if (!checkboxSet) {
					$checkbox = $('#edit-' + post_id).find('input[name="pica_pay_paid"]');
					$checkbox.prop('checked', is_paid);
				}

				checkboxSet = true;
			});

		});

		// Save the quick edit data
		$('#bulk_edit').on('click', '#bulk_edit_save', function () {
			var $bulk_edit = $('#bulk-edit');
			var post_ids = [];
			$bulk_edit.find('#bulk-titles').children().each(function () {
				post_ids.push($(this).attr('id').replace('post-', ''));
			});

			var is_paid = $bulk_edit.find('input[name="pica_pay_paid"]').prop('checked') ? '1' : '';
			var nonce = $bulk_edit.find('input[name="pica_pay_paid_quick_edit_nonce"]').val();

			// Save the data via AJAX
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'save_bulk_edit',
					post_ids: post_ids,
					pica_pay_paid: is_paid,
					_wpnonce: nonce
				}
			});
		});
	});

})( jQuery );
