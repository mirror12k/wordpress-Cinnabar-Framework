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
		add_action('save_post', array($this, 'wordpress_save_post'), 10, 2);
		add_filter('post_type_link', array($this, 'wordpress_post_type_link'), 1, 2);
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

	public function wordpress_save_post($post_id, $post)
	{
		// Check if the user has permissions to save data.
		if (!current_user_can('edit_post', $post_id))
			return;

		// Check if it's not an autosave.
		if (wp_is_post_autosave($post_id))
			return;

		// Check if it's not a revision.
		if (wp_is_post_revision($post_id))
			return;

		$class = $this->get_custom_post_class_by_post_type($post->post_type);
		if (isset($class))
		{
			$post = $class::from_post($post);
			foreach ($class::$config['fields'] as $name => $field)
			{
				error_log("got value for $name: " . $_POST[$name]);
				$post->$name = $_POST[$name];
			}
		}
	}

	public function wordpress_post_type_link($url, $post)
	{
		if (isset($post))
		{
			$class = $this->get_custom_post_class_by_post_type($post->post_type);
			if (isset($class) && isset($class::$config['custom_url_callback']))
			{
				$callback = $class::$config['custom_url_callback'];
				return $callback($class::from_post($post));
			}
		}
		return $url;
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
				<?php

				if (isset($field['cast']) && $field['cast'] === 'bool')
				{
					?>
					<input type='checkbox' id="<?php echo htmlspecialchars($name); ?>" name="<?php echo htmlspecialchars($name); ?>" value='1' <?php echo (1 == $value ? "checked='checked'" : ""); ?> />
					<?php
				}
				elseif (isset($field['cast']) && ($field['cast'] === 'int' || $field['cast'] === 'string'))
				{
					?>
					<input type="text" id="<?php echo htmlspecialchars($name); ?>" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>" />
					<?php
				}
				elseif (isset($field['cast']) && $field['cast'] === 'option')
				{
					?>
					<select id="<?php echo htmlspecialchars($name); ?>" name="<?php echo htmlspecialchars($name); ?>">
						<option value="">--</option>
						<?php foreach ($field['option_values'] as $key => $desc) { ?>
							<option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($key === $value ? "selected='selected'" : ""); ?>><?php echo htmlentities($desc); ?></option>
						<?php } ?>
					</select>
					<?php
				}
				else
				{
					?>
					<input type="text" id="<?php echo htmlspecialchars($name); ?>" name="<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value); ?>" />
					<?php
				}

				?>
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


