
jQuery(function ($) {


	function reindex_input_fields(input_array) {
		var field_name = $(input_array).data('field-name');
		
		$(input_array).find('.input-array-container .input-array-field').each(function (index) {
			var field = this;
			$(field).find('.field-name-holder').attr('name', field_name + '[' + index + ']');
		});
		if ($(input_array).find('.input-array-container .input-array-field').length === 0) {
			$(input_array).append("<input class='input-array-empty-value' type='hidden' name='" + field_name + "' value='' />");
		} else {
			$(input_array).find('.input-array-empty-value').remove();
		}
	}

	function add_input_field(input_array, e) {
		var field = $(input_array).find('.input-array-template .input-array-field').clone();
		hook_dynamic_input_containers(field);
		hook_color_field_values(field);

		field.find('.input-array-remove-button').click(remove_input_field.bind(undefined, input_array, field));
		$(input_array).find('.input-array-container').append(field);
		reindex_input_fields(input_array);
	}

	function remove_input_field(input_array, field, e) {
		field.remove();
		reindex_input_fields(input_array);
	}

	$('.input-array').each(function () {
		var input_array = this;

		$(input_array).find('.input-array-add-button').click(add_input_field.bind(undefined, input_array));
		$(input_array).find('.input-array-container .input-array-field').each(function () {
			var field = this;
			$(field).find('.input-array-remove-button').click(remove_input_field.bind(undefined, input_array, field));
		});

		reindex_input_fields(input_array);
	});

	function hook_dynamic_input_containers(dom) {
		dom.find('.dynamic-input-container').each(function () {
			var container = $(this);
			var dynamic_input_name = container.data('dynamic-input-name');
			container.find('.dynamic-input-search').on('input', function () {
				var query = $(this).val();
				container.find('.dynamic-input-search-results').empty();

				if (query !== '') {
					cinnabar_ajax_action('input_helper_library__dynamic_input_query', { dynamic_input_name: dynamic_input_name, query: query }, function (data) {
						container.find('.dynamic-input-search-results').empty();
						for (var i = 0; i < data.data.length; i++) {
							var tab = $(data.data[i]);
							tab.click((function (tab, e) {
								e.preventDefault();
								e.stopPropagation();

								var value = tab.data('value');

								container.find('.dynamic-input-selected').empty();
								container.find('.dynamic-input-selected').show();
								container.find('.dynamic-input-selected').append(tab.clone());
								container.find('.dynamic-input-field').attr('value', value);
								container.find('.dynamic-input-search-results').empty();
								container.find('.dynamic-input-search').attr('value', '');
								container.find('.dynamic-input-search').hide();
								container.find('.dynamic-input-clear').show();
								container.find('.dynamic-input-item-link').show();
							}).bind(undefined, tab));
							container.find('.dynamic-input-search-results').append(tab);
						}
					});
				}
			});

			container.find('.dynamic-input-clear').click(function (e) {
				e.preventDefault();
				e.stopPropagation();

				container.find('.dynamic-input-selected').empty();
				container.find('.dynamic-input-selected').hide();
				container.find('.dynamic-input-field').attr('value', '');
				container.find('.dynamic-input-search').show();
				container.find('.dynamic-input-clear').hide();
				container.find('.dynamic-input-item-link').hide();
			});

			container.find('.dynamic-input-item-link').click(function (e) {
				e.preventDefault();
				e.stopPropagation();

				window.location = '/wp-admin/post.php?post=' + container.find('.dynamic-input-field').val() + '&action=edit';
			});
		});
	}

	function hook_color_field_values(dom) {

		dom.find('.field-name-holder[type="color"]').on('change', function () {
			var name = $(this).attr('name');
			$('.field-color-value[for="' + name + '"]').val($(this).val());
		});
		dom.find('.field-color-value').on('change', function () {
			var name = $(this).attr('for');
			$('.field-name-holder[type="color"][name="' + name + '"]').val($(this).val());
		});
	}

	hook_dynamic_input_containers($(document.body));
	hook_color_field_values($(document.body));
});
