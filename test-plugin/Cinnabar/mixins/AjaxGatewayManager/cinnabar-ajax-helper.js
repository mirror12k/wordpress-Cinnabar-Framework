

function cinnabar_ajax_set_error_message(msg) {
	console.log("Cinnabar Error: ", msg);
}

function cinnabar_ajax_action (action, action_data, cb) {
	console.log("cinnabar action [" + action + "] => ", JSON.stringify(action_data)); // DEBUG
	jQuery.ajax({
		type : 'POST',
		url : cinnabar_ajax_config.ajaxurl,
		data : {
			action : 'cinnabar_ajax_action',
			cinnabar_action : action,
			nonce : cinnabar_ajax_config.nonce,
			data : action_data,
		},
		dataType : 'text',
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

function collect_action_form_data(form) {
	var data = {};
	jQuery(form).find("input").each(function () {
		var input = jQuery(this);
		if (input.attr('type') === 'checkbox')
			data[input.attr("name")] = input.prop("checked");
		else
			data[input.attr("name")] = input.attr("value");
	});
	jQuery(form).find("select").each(function () {
		var input = jQuery(this);
		data[input.attr("name")] = input.attr("value");
	});
	jQuery(form).find("textarea").each(function () {
		var input = jQuery(this);
		data[input.attr("name")] = input.attr("value");
	});
	return data;
}

var cinnabar_action_hooks = {};

jQuery(function ($) {

	// hook action forms
	$("form.cinnabar_action_form").each(function () {
		var elm = this;
		// hijack submission to perform cinnabar ajax action
		$(this).submit(function (e) {
			e.preventDefault();
			
			var action = elm.getAttribute("data-ajax-action");

			var hook = {};
			if (cinnabar_action_hooks[action])
				hook = cinnabar_action_hooks[action];

			if (hook.on_request)
				hook.on_request(elm);

			var form_data = collect_action_form_data(elm);
			if (hook.confirm_action === undefined || window.confirm(hook.confirm_action)) {
				cinnabar_ajax_action(
					page,
					action,
					form_data,
					function (data) {
						if (data.status === 'success' && hook.on_success)
							hook.on_success(elm, data);
						else if (data.status === 'error' && hook.on_error)
							hook.on_error(elm, data);
					}
				);
			} else {
			}
		});
	});

});

