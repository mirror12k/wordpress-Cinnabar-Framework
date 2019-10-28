<?php



namespace Cinnabar\Mixin;

class EmailManager extends \Cinnabar\BasePluginMixin
{

	public function register()
	{
		// plugin settings specifically for the updater
		// we use the first listed version history as the default
		$this->app->register_plugin_options(array(
			'cinnabar-email-manager-settings' => array(
				'title' => 'Cinnabar Email Manager Section',
				'fields' => array(
					'cinnabar-email-manager-enable-emailing' => array(
						'label' => 'enable emailing globally',
						'default' => 0,
						'option_type' => 'boolean',
					),
					'cinnabar-email-manager-default-from-field' => array(
						'label' => 'default from field',
						'default' => 'Staff <noreply@example.com>',
					),
				),
			),
		));
	}

	public function email_user_by_template($userid, $template_path, $template_args)
	{
		$email_data = $this->render_email($template_path, $template_args);
		return $this->email_user($userid, $email_data['email_subject'], $email_data['email_message']);
	}

	public function email_user($userid, $subject, $message)
	{
		// check global emailing flag
		if ($this->app->get_plugin_option('cinnabar-email-manager-enable-emailing'))
			return false;

		$from_field = $this->app->get_plugin_option('cinnabar-email-manager-default-from-field');

		$user = get_user_by('ID', $userid);
		if (strlen($user->user_email) > 0)
			return wp_mail($user->user_email, $subject, $message, array("From: $from_field"));
		else
			return false;
	}

	public function email_by_template($to_email, $template_path, $template_args)
	{
		$email_data = $this->render_email($template_path, $template_args);

		// check global emailing flag
		if (!$this->app->get_plugin_option('cinnabar-email-manager-enable-emailing')) {
			error_log("[EmailManager] global emailing is disabled, email rejected");
			return false;
		}

		$from_field = $this->app->get_plugin_option('cinnabar-email-manager-default-from-field');

		if (strlen($to_email) > 0)
			return wp_mail($to_email, $email_data['email_subject'], $email_data['email_message'], array("From: $from_field"));
		else
			return false;
	}

	public function render_email($template_path, $template_args)
	{
		$loader = new \Twig_Loader_Filesystem($this->app->plugin_dir());
		$twig = new \Twig_Environment($loader);
		$template = $twig->load($template_path);

		$email_subject = $template->renderBlock('email_subject', $template_args);
		$email_message = $template->renderBlock('email_message', $template_args);
		return array('email_subject' => $email_subject, 'email_message' => $email_message);
	}
}


