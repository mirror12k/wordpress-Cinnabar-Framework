

function cinnabar_ajax_set_error_message(msg) {
	console.log("Cinnabar Error: ", msg);
}

function cinnabar_ajax_action (action, action_data, cb) {
	console.log("cinnabar action [" + action + "] => ", JSON.stringify(action_data.data), action_data.form_data); // DEBUG

	// var formData = new FormData();
	var formData = action_data.form_data;
	formData.append('action', 'cinnabar_ajax_action');
	formData.append('cinnabar_action', action);
	formData.append('nonce', cinnabar_ajax_config.nonce);
	formData.append('data', JSON.stringify(action_data.data));

	jQuery.ajax({
		type : 'POST',
		url : cinnabar_ajax_config.ajaxurl,
		// data : {
		// 	action : 'cinnabar_ajax_action',
		// 	cinnabar_action : action,
		// 	nonce : cinnabar_ajax_config.nonce,
		// 	data : action_data,
		// },
		// dataType : 'text',
		// contentType: 'multipart/form-data',

		data : formData,
		dataType: 'text',
		processData: false, // Don't process the files
		contentType: false, // Set content type to false as jQuery will tell the server its a query string request
		
		success : function(data) {
			var json_data;
			console.log("cinnabar action [" + action + "] <=", JSON.stringify(data)); // DEBUG

			try {
				// if it's valid json, proceed as necessary
				json_data = JSON.parse(data);
			} catch (e) {
				// catch any json parsing exceptions
				if (e instanceof SyntaxError)
					return cinnabar_ajax_set_error_message("Server Error: " + data);
				else
					throw e;
			}

			// update the nonce
			cinnabar_ajax_config.nonce = json_data.nonce;
			cb(json_data);
			if (json_data.status === 'error') {
				cinnabar_ajax_set_error_message(json_data.error);
			} else if (json_data.status === 'success') {
				if (json_data.action === 'refresh')
					location.reload();
				else if (json_data.action === 'redirect')
					location.replace(json_data.redirect);
				else if (json_data.action !== undefined)
					console.error("unknown response action: " + json_data.action);
			} else {
				console.error("unknown response status: " + json_data.status);
			}
		},
		error : function(xhr, status, error) {
			console.log("cinnabar action [" + action + "] error", "ajax error performing cinnabar ajax action: " + status, error); // DEBUG
			if (xhr.status == 500)
				cinnabar_ajax_set_error_message("Internal Server Error: " + xhr.responseText);
			else
				cinnabar_ajax_set_error_message("Ajax Error: '" + status + "', see console");
		},
	});
}

function collect_action_form_data(action_form) {
	var data = {};

	var form_data = new FormData();

	jQuery(action_form).find("input,select,textarea").each(function () {
		var input = jQuery(this);
		var name = input.attr("name");
		var value;

		if (name !== undefined) {
			if (input.attr('type') === 'file') {
				value = input[0].files[0];
				if (value !== undefined)
					form_data.append(name, value, value.name);
			} else {
				if (input.attr('type') === 'checkbox')
					value = input.prop("checked");
				// else if (input.attr('type') === 'file')
				// 	value = input[0].files[0];
				else
					value = input.val();

				if (name.endsWith("[]")) {
					name = name.substring(0, name.length - 2);
					if (data[name] === undefined)
						data[name] = [];
					data[name].push(value);
				} else {
					data[name] = value;
				}
			}
		}
	});

	console.log("data:", data);

	return {
		form_data: form_data,
		data: data,
	};
}

var cinnabar_action_hooks = {};

jQuery(function ($) {

	// hook action forms
	$(".cinnabar_action_form").each(function () {
		var action_form = this;
		// hijack submission to perform cinnabar ajax action
		var callback = function (e) {
			e.preventDefault();
			e.stopPropagation();
			
			var action = action_form.getAttribute("data-ajax-action");

			var hook = {};
			if (cinnabar_action_hooks[action])
				hook = cinnabar_action_hooks[action];

			if (hook.on_request)
				hook.on_request(action_form);

			var form_data = collect_action_form_data(action_form);
			if (hook.confirm_action === undefined || window.confirm(hook.confirm_action)) {
				cinnabar_ajax_action(
					action,
					form_data,
					function (data) {
						if (data.status === 'success' && hook.on_success)
							hook.on_success(action_form, data);
						else if (data.status === 'error' && hook.on_error)
							hook.on_error(action_form, data);
					}
				);
			} else {
			}
		};

		$(this).find('.submitter').click(callback);
		$(this).submit(callback);
	});

});

