<?php



namespace Cinnabar\Mixin;

class CustomUserManager extends \Cinnabar\BasePluginMixin
{
	public $registered_custom_users = array();



	public function register_custom_user_type($class)
	{
		$this->registered_custom_users[] = $class;
	}


	public function load_hooks()
	{

		add_action('show_user_profile', array($this, 'show_custom_user_profile_fields'));
		add_action('edit_user_profile', array($this, 'show_custom_user_profile_fields'));
		add_action('personal_options_update', array($this, 'save_custom_user_profile_fields'));
		add_action('edit_user_profile_update', array($this, 'save_custom_user_profile_fields'));
	}



	public function show_custom_user_profile_fields($user)
	{

		$custom_user_type = \Cinnabar\CustomUserModel::get_user_type($user->ID);

		?>
		<table class="form-table">
		<tr>
			<th><label for="custom_user_type">Custom User Type</label></th>
		<td>
			<select name='custom_user_type'>
				<option value="">--</option>
				<?php foreach ($this->registered_custom_users as $class) { ?>
					<option value="<?php echo htmlspecialchars($class); ?>" <?php echo ($class::$config['user_type'] === $custom_user_type ? "selected='selected'" : ""); ?>><?php echo htmlentities($class::$config['user_type']); ?></option>
				<?php } ?>
			</select>
			<!-- <span class="description">...</span> -->
		</td>
		</tr>
		</table>
		<?php

		$class = $this->get_custom_user_class_by_user_type($custom_user_type);
		if (isset($custom_user_type) && isset($class))
		{
			$class = $this->get_custom_user_class_by_user_type($custom_user_type);
			$user = $class::from_userdata($user);

			if (isset($class::$config['field_groups']))
			{
				foreach ($class::$config['field_groups'] as $tag => $field_group)
					$this->render_meta_fields($user, $class, $field_group);
			}
			else
			{
				$this->render_meta_fields($user, $class);
			}
			$this->render_slug_field($user, $class);
		}
	}

	public function save_custom_user_profile_fields($user_id)
	{
		if (!current_user_can('edit_user', $user_id))
			return FALSE;

		$new_user_type = $_POST['custom_user_type'];
		if ($new_user_type === "")
			update_user_meta($user_id, 'custom_user_model__user_type', '');
		elseif (in_array($new_user_type, $this->registered_custom_users))
			update_user_meta($user_id, 'custom_user_model__user_type', $new_user_type::$config['user_type']);

		$custom_user_type = \Cinnabar\CustomUserModel::get_user_type($user_id);
		$class = $this->get_custom_user_class_by_user_type($custom_user_type);
		if (isset($custom_user_type) && isset($class))
		{
			$user = $class::get_by_id($user_id);
			if ($user === null)
				die("user is null");
			
			$user->slug = $_POST['slug'];

			foreach ($class::$config['fields'] as $name => $field)
			{
				// error_log("got value for $name: " . json_encode($_POST[$name]));
				if (isset($_POST[$name]))
				{
					$value = $_POST[$name];

					if ($value === '' && $field['type'] === 'meta-array')
						$value = array();
					
					$user->$name = $value;
				}
			}
		}

	}

	public function render_slug_field($user, $class)
	{
		?>
		<h2>User Slug</h2>
		<table class="form-table">
			<tr>
				<th><label for="slug">Slug</label></th>
				<td>
					<input type="text" name='slug' value="<?php echo htmlspecialchars($user->slug); ?>" />
				</td>
			</tr>
		</table>
		<?php
	}

	public function render_meta_fields($user, $class, $field_group=null)
	{
		if ($field_group === null)
		{
			$title = 'Custom User Fields';
			$fields_list = array_keys($class::$config['fields']);
		}
		else
		{
			$title = $field_group['title'];
			$fields_list = $field_group['fields'];
		}
		
		echo "<h2>" . $title . "</h2>";
		echo '<table class="form-table">';

		// foreach ($class::$config['fields'] as $name => $field)
		foreach ($fields_list as $name)
		{
			$field = $class::$config['fields'][$name];

			if (isset($field['description']))
				$description = $field['description'];
			else
				$description = $name;

			$value = $user->$name;


			?>
			<tr>
				<th><label for="<?php echo htmlspecialchars($name); ?>" class="<?php echo htmlspecialchars($name); ?>_label"><?php echo htmlentities(__($description, $class::$config['user_type'])); ?></label></th>
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

	public function get_custom_user_class_by_user_type($user_type)
	{
		if (!isset($user_type))
			return;

		foreach ($this->registered_custom_users as $class)
			if ($class::$config['user_type'] === $user_type)
				return $class;
	}
}



