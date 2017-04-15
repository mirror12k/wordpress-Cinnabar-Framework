<?php



namespace Cinnabar;

class CustomUserManager extends BasePluginMixin
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

		$custom_user_type = CustomUserModel::get_user_type($user->ID);

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
	}

	public function save_custom_user_profile_fields($user_id)
	{
		if (!current_user_can('edit_user', $user_id))
			return FALSE;

		$new_user_type = $_POST['custom_user_type'];
		if ($new_user_type === "")
			update_usermeta($user_id, 'custom_user_model__user_type', '');
		elseif (in_array($new_user_type, $this->registered_custom_users))
			update_usermeta($user_id, 'custom_user_model__user_type', $new_user_type::$config['user_type']);

	}
}



