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
		add_filter('admin_enqueue_scripts', array($this, 'wordpress_admin_enqueue_scripts'));
	}

	public function wordpress_admin_enqueue_scripts()
	{
		wp_enqueue_script('jquery');
		wp_enqueue_script('cinnabar-custom-post-manager-helper', $this->app->plugin_url('/Cinnabar/mixins/CustomPostManager/custom-post-manager-helper.js'));
	}

	public function wordpress_add_meta_boxes($post_type, $post)
	{
		$self = $this;
		$class = $this->get_custom_post_class_by_post_type($post_type);
		if (isset($class))
		{
			if (isset($class::$config['field_groups']))
			{
				foreach ($class::$config['field_groups'] as $tag => $field_group)
				{
					add_meta_box(
						$class::$config['post_type'] . '-' . $tag . '-config',
						__( $field_group['title'], $class::$config['post_type']),
						function ($post) use ($self, $class, $field_group) {
							$post = $class::from_post($post);

							if (isset($field_group['render_callback']))
							{
								$callback = $field_group['render_callback'];
								$callback($self, $post, $field_group);
							}
							else
							{
								$self->render_meta_boxes($post, $class, $field_group);
							}
						},
						$class::$config['post_type'],
						'normal',
						'default'
					);

				}
			}
			else
			{
				add_meta_box(
					$class::$config['post_type'] . '-config',
					__( 'Properties', $class::$config['post_type']),
					function ($post) use ($self) {
						$post = $class::from_post($post);
						$self->render_meta_boxes($post, $class);
					},
					$class::$config['post_type'],
					'normal',
					'default'
				);
			}
		}
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
				// error_log("got value for $name: " . json_encode($_POST[$name]));
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

	public function render_meta_boxes($post, $class, $field_group=null)
	{
		echo '<table class="form-table">';

		if ($field_group === null)
		{
			$fields_list = array_keys($class::$config['fields']);
		}
		else
		{
			$fields_list = $field_group['fields'];
		}

		// foreach ($class::$config['fields'] as $name => $field)
		foreach ($fields_list as $name)
		{
			$field = $class::$config['fields'][$name];

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
					if ($field['type'] === 'meta')
					{
						$this->render_meta_input($class, $field, $name, $value);
					}
					elseif ($field['type'] === 'meta-array')
					{
						$value_array = $value;

						echo "<div data-field-name='" . htmlspecialchars($name) . "' class='cpm-input-array'>";
						echo "<div class='cpm-input-array-template' style='display: none;'>";
						echo "<div class='cpm-input-array-field'>";
						$this->render_meta_input($class, $field, '', '', true);
						echo "<button type='button' class='cpm-input-array-remove-button'>X</button>";
						echo "</div>";
						echo "</div>";
						echo "<div class='cpm-input-array-container'>";
						if (count($value_array) > 0)
							foreach (range(0, count($value_array) - 1) as $i)
							{
								echo "<div class='cpm-input-array-field'>";
								$this->render_meta_input($class, $field, $name . '[' . $i . ']', $value_array[$i]);
								echo "<button type='button' class='cpm-input-array-remove-button'>X</button>";
								echo "</div>";
							}

						echo "</div>";
						echo "<button type='button' class='cpm-input-array-add-button'>+</button>";
						echo "</div>";
					}
				?>
				</td>
			</tr>
			<?php
		}

		echo '</table>';
	}

	public function render_meta_input($class, $field, $input_name, $value, $is_template=false)
	{
		if (isset($field['cast']) && $field['cast'] === 'bool')
		{
			?>
			<input type='checkbox' class='field-name-holder' <?php echo ($is_template ? '' : "name='" . htmlspecialchars($input_name) . "'"); ?> value='1' <?php echo (1 == $value ? "checked='checked'" : ""); ?> />
			<?php
		}
		elseif (isset($field['cast']) && ($field['cast'] === 'int' || $field['cast'] === 'string'))
		{
			?>
			<input type="text" class='field-name-holder' <?php echo ($is_template ? '' : "name='" . htmlspecialchars($input_name) . "'"); ?> value="<?php echo htmlspecialchars($value); ?>" />
			<?php
		}
		elseif (isset($field['cast']) && $field['cast'] === 'option')
		{
			?>
			<select class='field-name-holder' <?php echo ($is_template ? '' : "name='" . htmlspecialchars($input_name) . "'"); ?>>
				<option value="">--</option>
				<?php foreach ($field['option_values'] as $key => $desc) { ?>
					<option value="<?php echo htmlspecialchars($key); ?>" <?php echo ($key === $value ? "selected='selected'" : ""); ?>><?php echo htmlentities($desc); ?></option>
				<?php } ?>
			</select>
			<?php
		}
		elseif (isset($field['cast']) && isset($class::$config['custom_cast_types']) && isset($class::$config['custom_cast_types'][$field['cast']]))
		{
			$callback = $class::$config['custom_cast_types'][$field['cast']]['render_input'];
			$callback($field, $input_name, $value, $is_template);
		}
		else
		{
			?>
			<input type="text" class='field-name-holder' <?php echo ($is_template ? '' : "name='" . htmlspecialchars($input_name) . "'"); ?> value="<?php echo htmlspecialchars($value); ?>" />
			<?php
		}
	}

	public function get_custom_post_class_by_post_type($post_type)
	{
		foreach ($this->registered_custom_posts as $class)
			if ($class::$config['post_type'] === $post_type)
				return $class;
	}
}


