


jQuery(function ($) {

	function add_input_field (input_array, e) {
		var field = $(input_array).find('.cpm-input-array-template .cpm-input-array-field').clone();
		var field_name = $(input_array).data('field-name');
		var field_count = $(input_array).find('.cpm-input-array-container .cpm-input-array-field').length;

		field.find('.field-name-holder').attr('name', field_name + '[' + field_count + ']');
		$(input_array).find('.cpm-input-array-container').append(field);
	}

	$('.cpm-input-array').each(function () {
		var elm = this;

		$(elm).find('.cpm-input-array-add-button').click(add_input_field.bind(undefined, elm));

	});

});


