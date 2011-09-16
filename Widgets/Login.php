<?php
if(class_exists('WP_Widget') && !class_exists('FreeWPMembershipLoginWidget')) {
	class FreeWPMembershipLoginWidget extends WP_Widget {
		function __construct() {
			parent::__construct( 'FWP_LoginWidget', 'Free WP-Membership Login', array('description' => "Login form for Free WP-Membership"));
		}
		
		function form($instance) {
			if ( $instance ) {
				$title = esc_attr( $instance[ 'title' ] );
				$showLogout = esc_attr( $instance[ 'showLogout' ] );
			}
			else {
				$title = __( 'Login', 'text_domain' );
//				$showLogout = __( 'false', 'text_domain' );
			}
			?>
			<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
			</p>
			<p>
			<label for="<?php echo $this->get_field_id('showLogout'); ?>"><?php _e('Show Logout:'); ?></label> 
			<input id="<?php echo $this->get_field_id('showLogout'); ?>" name="<?php echo $this->get_field_name('showLogout'); ?>" type="checkbox" value="true"<?php if(@$showLogout == 'true') echo ' CHECKED'; ?> />
			</p>
			<?php 
		}
		
		function update($new_instance, $old_instance) {
			$instance = $old_instance;
			$instance['title'] = strip_tags($new_instance['title']);
			$instance['showLogout'] = strip_tags($new_instance['showLogout']);
			return $instance;
		}
		
		function widget($args, $instance) {
			extract( $args );
			$title = apply_filters( 'widget_title', $instance['title'] );
			global $wpdb, $wp_query, $wp_membership_plugin;
			
			load_plugin_textdomain('wp-membership', false, $wp_membership_plugin->language_path);

		    $page_id = isset($wp_query->queried_object->ID) ? $wp_query->queried_object->ID : "";
			if(@$_SESSION['wp-membership_plugin']['wp-membership_user_id'] > 0) {
				//**FIXME**//
				//Add an optional Logout button
				//**END_FIXME**//
			}
			else {
				echo $before_widget;
				if ( $title )
					echo $before_title . $title . $after_title;
				if(@$_REQUEST['forgot_password'] == '1') {
					echo "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\"><input type=\"hidden\" name=\"fwpm_form_type\" value=\"forgot_password_widget\" /><input name=\"do_forgot_password\" type=\"hidden\" value=\"1\" />";
					echo "<table border=\"0\">";
					echo "<tbody>";
					echo "<tr>";
					echo "<td>".__('Email', 'wp-membership');
					$username_query = $wpdb->prepare("SELECT COUNT(*) AS Total FROM {$wpdb->prefix}wp_membership_users WHERE Username!=NULL");
					if($username_row = $wpdb->get_row($username_query, ARRAY_A)) {
						if($username_row['Total'] > 0) echo " / ".__('Username', 'wp-membership');
					}
					echo "</td>";
					echo "<td><input style=\"background-color: #ffffa0;\" name=\"email\" type=\"text\" value=\"".htmlentities(@$_REQUEST['email'])."\" /></td>";
					echo "</tr>";
					echo "<tr>";
					echo "<td colspan=\"2\"><input type=\"submit\" value=\"".__('Forgot Password', 'wp-membership')."\" /></td>";
					echo "</tr>";
					echo "</tbody></table>";
					echo "</form>";
				}
				else {
					echo "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\"><input type=\"hidden\" name=\"fwpm_form_type\" value=\"login_widget\" /><input name=\"do_login\" type=\"hidden\" value=\"1\" />";
					echo "<table border=\"0\">";
					echo "<tbody>";
					echo "<tr>";
					echo "<td>".__('Email', 'wp-membership');
					$username_query = $wpdb->prepare("SELECT COUNT(*) AS Total FROM {$wpdb->prefix}wp_membership_users WHERE Username!=NULL");
					if($username_row = $wpdb->get_row($username_query, ARRAY_A)) {
						if($username_row['Total'] > 0) echo " / ".__('Username', 'wp-membership');
					}
					echo "</td>";
					echo "<td><input style=\"background-color: #ffffa0;\" name=\"email\" type=\"text\" /></td>";
					echo "</tr>";
					echo "<tr>";
					echo "<td>".__('Password', 'wp-membership')."</td>";
					echo "<td><input name=\"password\" type=\"password\" /></td>";
					echo "</tr>";
					echo "<tr>";
					echo "<td colspan=\"2\"><input type=\"submit\" value=\"".__('Login', 'wp-membership')."\" /></td>";
					echo "</tr>";
					echo "</tbody></table>";
					echo "</form>";
		    		if(@$attributes['show_forgot_password'] == "1") {
		    			echo "<div class=\"prompt_password\"><a href=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."&forgot_password=1&email=".urlencode(@$_REQUEST['email'])."\">Forgot Password?</a></div>";
		    		}
				}
				echo $after_widget;
			}
		}
	}	
}
?>