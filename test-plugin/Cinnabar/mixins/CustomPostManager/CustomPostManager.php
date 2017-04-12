<?php



namespace Cinnabar;

class CustomPostManager extends BasePluginMixin
{
	public $registered_custom_posts = array();



	public function register_custom_post_type($class)
	{
		$this->registered_custom_posts[] = $class;
	}

	public function load_hooks()
	{
		add_action('add_meta_boxes', array($this, 'wordpress_add_meta_boxes'), 10, 2);
	}

	public function wordpress_add_meta_boxes($post_type, $post)
	{
		$class = $this->get_custom_post_class_by_post_type($post_type);
		if (isset($class))
			add_meta_box(
				$class::$config['post_type'] . '-config',
				__( 'Extended Fields', $class::$config['post_type']),
				array($this, 'render_meta_boxes'),
				$class::$config['post_type'],
				'normal',
				'default'
			);
	}

	public function render_meta_boxes($post)
	{
		$class = $this->get_custom_post_class_by_post_type($post->post_type);
		if (!isset($class))
			return;

		$post = $class::from_post($post);

		echo '<table class="form-table">';

		foreach ($class::$config['fields'] as $name => $field)
		{
			if (isset($field['description']))
				$description = $field['description'];
			else
				$description = $name;

			$value = $post->$name;

			?>
			<tr>
				<th><label for="<?php echo htmlspecialchars($name); ?>" class="<?php echo htmlspecialchars($name); ?>_label"><?php echo htmlentities(__($description, $class::$config['post_type'])); ?></label></th>
				<td>
					<input type="text" id="<?php echo htmlspecialchars($name); ?>" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>" />
				</td>
			</tr>
			<?php
		}

		echo '</table>';
	}

	public function get_custom_post_class_by_post_type($post_type)
	{
		foreach ($this->registered_custom_posts as $class)
			if ($class::$config['post_type'] === $post_type)
				return $class;
	}
}


