<?php



namespace Cinnabar\Mixin;

class TemplateRendererManager extends \Cinnabar\BasePluginMixin
{
	public $registered_synthetic_pages = array();

	public $active_synthetic_page;
	public $active_view_controller;

	public $twig_loader;
	public $twig;

	public function __construct($app)
	{
		parent::__construct($app);
		$this->twig_loader = new \Twig_Loader_Filesystem($this->app->plugin_dir());
		$this->twig = new \Twig_Environment($this->twig_loader);
	}

	public function register()
	{
		global $template_rendering_manager;
		$template_rendering_manager = $this;
	}

	public function render_template($template_path, $template_args)
	{
		$template_args = array_merge($template_args, array( 'app' => $this->app ));
		return $this->twig->render($template_path, $template_args);
	}
}


