<?php
/*
Plugin Name: azurecurve Comment Validator
Plugin URI: http://wordpress.azurecurve.co.uk/plugins/comment-validator/

Description: Checks comment to ensure they are longer than the minimum, shorter than the maximum and also allows comments to be forced into moderation based on length.
Version: 1.0.0

Author: azurecurve
Author URI: http://wordpress.azurecurve.co.uk/

Text Domain: azurecurve-comment-validator
Domain Path: /languages

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

The full copy of the GNU General Public License is available here: http://www.gnu.org/licenses/gpl.txt
*/

function azc_cv_load_plugin_textdomain(){
	$loaded = load_plugin_textdomain( 'azurecurve-comment-validator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	//if ($loaded){ echo 'true'; }else{ echo 'false'; }
}
add_action('plugins_loaded', 'azc_cv_load_plugin_textdomain');
 
function azc_cv_set_default_options($networkwide) {
	
	$new_options = array(
				'min_length' => 10,
				'max_length' => 500,
				'mod_length' => 250,
				'use_network' => 1
			);
	
	// set defaults for multi-site
	if (function_exists('is_multisite') && is_multisite()) {
		// check if it is a network activation - if so, run the activation function for each blog id
		if ($networkwide) {
			global $wpdb;

			$blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
			$original_blog_id = get_current_blog_id();

			foreach ( $blog_ids as $blog_id ) {
				switch_to_blog( $blog_id );

				if ( get_option( 'azc_cv_options' ) === false ) {
					add_option( 'azc_cv_options', $new_options );
				}
			}

			switch_to_blog( $original_blog_id );
		}else{
			if ( get_option( 'azc_cv_options' ) === false ) {
				add_option( 'azc_cv_options', $new_options );
			}
		}
		if ( get_site_option( 'azc_cv_options' ) === false ) {
			add_site_option( 'azc_cv_options', $new_options );
		}
	}
	//set defaults for single site
	else{
		if ( get_option( 'azc_cv_options' ) === false ) {
			add_option( 'azc_cv_options', $new_options );
		}
	}
}
register_activation_hook( __FILE__, 'azc_cv_set_default_options' );

function azc_cv_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=azurecurve-comment-validator">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}
add_filter('plugin_action_links', 'azc_cv_plugin_action_links', 10, 2);

function azc_cv_settings_menu() {
	add_options_page( 'azurecurve Comment Validator Settings',
	'azurecurve Comment Validator', 'manage_options',
	'azurecurve-comment-validator', 'azc_cv_config_page' );
}
add_action( 'admin_menu', 'azc_cv_settings_menu' );

function azc_cv_config_page() {
	if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'azurecurve-comment-validator'));
    }
	
	// Retrieve plugin configuration options from database
	$options = get_option( 'azc_cv_options' );
	?>
	<div id="azc-cv-general" class="wrap">
		<fieldset>
			<h2><?php _e('azurecurve Comment Validator Settings', 'azurecurve-comment-validator'); ?></h2>
			<?php if( isset($_GET['settings-updated']) ) { ?>
				<div id="message" class="updated">
					<p><strong><?php _e('Settings have been saved.') ?></strong></p>
				</div>
			<?php } ?>
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="save_azc_cv_options" />
				<input name="page_options" type="hidden" value="min_length,max_length,mod_length,use_network" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field( 'azc_cv_nonce', 'azc_cv_nonce' ); ?>
				<table class="form-table">
				<tr><th scope="row"><?php _e('Use Network Settings', 'azurecurve-comment-validator'); ?></th><td>
					<fieldset><legend class="screen-reader-text"><span>Use Network Settings</span></legend>
					<label for="use_network"><input name="use_network" type="checkbox" id="use_network" value="1" <?php checked( '1', $options['use_network'] ); ?> /><?php _e('Settings below will be ignored in preference of network settings', 'azurecurve-comment validator'); ?></label>
					</fieldset>
				</td></tr>
				<tr><th scope="row"><label for="min_length"><?php _e('Minimum Length', 'azurecurve-comment-validator'); ?></label></th><td>
					<input type="text" name="min_length" value="<?php echo esc_html( stripslashes($options['min_length']) ); ?>" class="small-text" />
					<p class="description"><?php _e('Minimum comment length; set to 0 for no minimum', 'azurecurve-comment-validator'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="max_length"><?php _e('Maximum Length', 'azurecurve-comment-validator'); ?></label></th><td>
					<input type="text" name="max_length" value="<?php echo esc_html( stripslashes($options['max_length']) ); ?>" class="small-text" />
					<p class="description"><?php _e('Maximum comment length; set to 0 for no maximum', 'azurecurve-comment-validator'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="mod_length"><?php _e('Moderation Length', 'azurecurve-comment-validator'); ?></label></th><td>
					<input type="text" name="mod_length" value="<?php echo esc_html( stripslashes($options['mod_length']) ); ?>" class="small-text" />
					<p class="description"><?php _e('Moderation comment length; set to 0 for no moderation', 'azurecurve-comment-validator'); ?></p>
				</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary"/>
			</form>
		</fieldset>
	</div>
<?php }


function azc_cv_admin_init() {
	add_action( 'admin_post_save_azc_cv_options', 'process_azc_cv_options' );
}
add_action( 'admin_init', 'azc_cv_admin_init' );

function process_azc_cv_options() {
	// Check that user has proper security level
	echo 'here';
	if ( !current_user_can( 'manage_options' ) ){
		wp_die( __('You do not have permissions to perform this action', 'azurecurve-comment-validator') );
	}
	// Check that nonce field created in configuration form is present
	if ( ! empty( $_POST ) && check_admin_referer( 'azc_cv_nonce', 'azc_cv_nonce' ) ) {
	
		// Retrieve original plugin options array
		$options = get_option( 'azc_cv_options' );
		
		$option_name = 'min_length';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		$option_name = 'max_length';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		$option_name = 'mod_length';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		$option_name = 'use_network';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = 1;
		}else{
			$options[$option_name] = 0;
		}
		
		// Store updated options array to database
		update_option( 'azc_cv_options', $options );
		
		// Redirect the page to the configuration form that was processed
		wp_redirect( add_query_arg( 'page', 'azurecurve-comment-validator&settings-updated', admin_url( 'admin.php' ) ) );
		exit;
	}
}

function add_azc_cv_network_settings_page() {
	if (function_exists('is_multisite') && is_multisite()) {
		add_submenu_page(
			'settings.php',
			'azurecurve Comment Validator Settings',
			'azurecurve Comment Validator',
			'manage_network_options',
			'azurecurve-comment-validator',
			'azc_cv_network_settings_page'
			);
	}
}
add_action('network_admin_menu', 'add_azc_cv_network_settings_page');

function azc_cv_network_settings_page(){
	if(!current_user_can('manage_network_options')) wp_die(__('You do not have permissions to perform this action', 'azurecurve-comment-validator'));
	$options = get_site_option('azc_cv_options');

	?>
	<div id="azc-cv-general" class="wrap">
		<fieldset>
			<h2><?php _e('azurecurve Comment Validator Settings', 'azurecurve-comment-validator'); ?></h2>
			<form method="post" action="admin-post.php">
				<input type="hidden" name="action" value="save_azc_cv_options" />
				<input name="page_options" type="hidden" value="smallest, largest, number" />
				
				<!-- Adding security through hidden referrer field -->
				<?php wp_nonce_field( 'azc_cv_nonce', 'azc_cv_nonce' ); ?>
				<table class="form-table">
				<tr><th scope="row"><label for="min_length"><?php _e('Minimum Length', 'azurecurve-comment-validator'); ?></label></th><td>
					<input type="text" name="min_length" value="<?php echo esc_html( stripslashes($options['min_length']) ); ?>" class="small-text" />
					<p class="description"><?php _e('Minimum comment length; set to 0 for no minimum', 'azurecurve-comment-validator'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="max_length"><?php _e('Maximum Length', 'azurecurve-comment-validator'); ?></label></th><td>
					<input type="text" name="max_length" value="<?php echo esc_html( stripslashes($options['max_length']) ); ?>" class="small-text" />
					<p class="description"><?php _e('Maximum comment length; set to 0 for no maximum', 'azurecurve-comment-validator'); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="mod_length"><?php _e('Moderation Length', 'azurecurve-comment-validator'); ?></label></th><td>
					<input type="text" name="mod_length" value="<?php echo esc_html( stripslashes($options['mod_length']) ); ?>" class="small-text" />
					<p class="description"><?php _e('Moderation comment length; set to 0 for no moderation', 'azurecurve-comment-validator'); ?></p>
				</td></tr>
				</table>
				<input type="submit" value="Submit" class="button-primary"/>
			</form>
		</fieldset>
	</div>
	<?php
}

function process_azc_cv_network_options(){     
	if(!current_user_can('manage_network_options')) wp_die(__('You do not have permissions to perform this action', 'azurecurve-comment-validator'));
	if ( ! empty( $_POST ) && check_admin_referer( 'azc_cv_nonce', 'azc_cv_nonce' ) ) {
		// Retrieve original plugin options array
		$options = get_site_option( 'azc_cv_options' );
		$option_name = 'min_length';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		$option_name = 'max_length';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		$option_name = 'mod_length';
		if ( isset( $_POST[$option_name] ) ) {
			$options[$option_name] = sanitize_text_field(intval($_POST[$option_name]));
		}
		
		update_site_option( 'azc_cv_options', $options );

		wp_redirect(network_admin_url('settings.php?page=azurecurve-comment-validator&settings-updated'));
		exit;  
	}
}
add_action('network_admin_edit_update_azc_cv_network_options', 'process_azc_cv_network_options');


function azc_cv_validate_comment( $commentdata ) {
	$options = get_option('azc_cv_options');
	if ($options['use_network'] == 1){
		$options = get_site_option('azc_cv_options');
	}
	if (strlen($commentdata['comment_content']) < $options['min_length']){
		$error = new WP_Error( 'not_found', __('<strong>ERROR</strong>: this comment is shorter than the minimum allowed size.' , 'azurecurve-comment-validator'), array( 'response' => '200' ) );
		if( is_wp_error($error) ){
			wp_die( $error, '', $error->get_error_data() );
		}
	}elseif (strlen($commentdata['comment_content']) > $options['max_length'] && $options['max_length'] > 0){
		$error = new WP_Error( 'not_found', __('<strong>ERROR</strong>: this comment is longer than the maximum allowed size.', 'azurecurve-comment-validator'), array( 'response' => '200' ) );
		if( is_wp_error($error) ){
			wp_die( $error, '', $error->get_error_data() );
		}
	}elseif (strlen($commentdata['comment_content']) > $options['mod_length'] && $options['mod_length'] > 0){
		add_filter( 'pre_comment_approved', 'azc_cv_return_moderated', '99', 2 );
	}
    return $commentdata;
}
add_filter( 'preprocess_comment' , 'azc_cv_validate_comment', 20 );

function azc_cv_return_moderated( $approved, $commentdata ) {
	if ( 'spam' != $approved ) return 0;
	else return $approved;
}

?>