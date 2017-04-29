


// jQuery(function ($) {

// 	function reindex_input_fields(input_array) {
// 		var field_name = $(input_array).data('field-name');
// 		$(input_array).find('.cpm-input-array-container .cpm-input-array-field').each(function (index) {
// 			var field = this;
// 			$(field).find('.field-name-holder').attr('name', field_name + '[' + index + ']');
// 		})
// 	}

// 	function add_input_field (input_array, e) {
// 		var field = $(input_array).find('.cpm-input-array-template .cpm-input-array-field').clone();

// 		field.find('.cpm-input-array-remove-button').click(remove_input_field.bind(undefined, input_array, field));
// 		$(input_array).find('.cpm-input-array-container').append(field);
// 		reindex_input_fields(input_array);
// 	}

// 	function remove_input_field (input_array, field, e) {
// 		field.remove();
// 		reindex_input_fields(input_array);
// 	}

// 	$('.cpm-input-array').each(function () {
// 		var input_array = this;

// 		$(input_array).find('.cpm-input-array-add-button').click(add_input_field.bind(undefined, input_array));
// 		$(input_array).find('.cpm-input-array-container .cpm-input-array-field').each(function () {
// 			var field = this;
// 			$(field).find('.cpm-input-array-remove-button').click(remove_input_field.bind(undefined, input_array, field));
// 		});

// 	});

// });


