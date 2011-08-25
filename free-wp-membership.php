<?php
/*
Plugin Name: Free WP-Membership Plugin
Plugin URI: http://free-wp-membership.foransrealm.com/
Description: Allows the ability to have a membership based page restriction. (previously by Synergy Software Group LLC)
Version: 1.1.4
Author: Ben M. Ward
Author URI: http://free-wp-membership.foransrealm.com/

This file is part of Free WP-Membership.

    Free WP-Membership is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Free WP-Membership is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.

*/

$wp_membership_min_php_version = '5.1.0';

if(version_compare(PHP_VERSION, $wp_membership_min_php_version, '>=') && !interface_exists("wp_membership_payment_gateway")) {
	interface wp_membership_payment_gateway {
		function get_Name();
		function get_Description();
		function get_Capabilities();
		function do_SettingsEdit();
		function show_SettingsEdit();
		function need_PaymentForm();
		function has_BuyNow_Button();
		function get_BuyNow_Button($id, $caption, $amount);
		function has_Subscription_Button();
		function get_Subscription_Button($id, $caption, $amount, $duration, $delay = null);
		function has_Unsubscribe_Button();
		function get_Unsubscribe_Button($id, $caption);
		function has_Process_Charge();
		function Process_Charge($id, $caption, $amount, $cc_num, $cc_exp, $cc_cvv2, $cc_name, $bill_name, $bill_address, $bill_address2, $bill_city, $bill_state, $bill_zip, $bill_phone, $bill_country);
		function has_Process_Refund();
		function Process_Refund($transactionid, $amount = null);
		function has_Install_Subscription();
		function Install_Subscription($id, $caption, $amount, $cc_num, $cc_exp, $cc_cvv2, $cc_name, $bill_name, $bill_address, $bill_address2, $bill_city, $bill_state, $bill_zip, $bill_phone, $bill_country, $duration, $delay = null);
		function has_Uninstall_Subscription();
		function Uninstall_Subscription($subscription_id);
		function Install();
		function Uninstall();
		function has_Transactions();
		function get_Transactions();
		function get_Transaction($transactionid);
		function has_Subscriptions();
		function get_Subscriptions();
		function get_Subscription($subscriptionid);
		function find_Subscription($userlevelid);
		function callback_PostBack($callback);
		function has_Subscription($userlevelid);
		function delete_User($userid);
		function get_Hidden_Pages();
		function is_Currency_Supported($currency = null);
	}
}

if(!class_exists('wp_membership_plugin') && version_compare(PHP_VERSION, $wp_membership_min_php_version, '>=') && function_exists("curl_init") && function_exists('simplexml_load_string')) {
	class wp_membership_plugin {
		private $plugins = array();
		private $methods = array();
		private $basepath = "";
		private $version = "1.1.4";
		private $admin_notices = array();
		private $admin_messages = array();
		private $public_messages = array();
		private $language_path = 'free-wp-membership';
		
		function __construct() {
			add_action('update_option_update_plugins', array(&$this, 'update_plugins'), 10, 2);
			
			add_option("wp-membership_access_denied_page_id", "1");
			add_option("wp-membership_logout_page_id", "1");
			add_option("wp-membership_login_page_id", "1");
			add_option("wp-membership_login_prompt_forgot_password", "0");
			add_option("wp-membership_logged_in_page_id", "1");
			add_option("wp-membership_loginfrom_macro", "[Login Form]");
			add_option("wp-membership_user_profile_page_id", "1");
			add_option("wp-membership_user_profile_from_macro", "[User Profile]");
			add_option("wp-membership_apply_update", false);
			add_option("wp-membership_cache", false);
			add_option("wp-membership_admin_menu_location", array('Settings'));
			add_option("wp-membership_news", array('last_check' => 0, 'news' => array()));
			add_option("wp-membership_info_https", array('last_check' => 0, 'https' => array(false, '')));
			add_option("wp-membership_info_changelog", array('last_check' => 0, 'changelog' => ""));
			add_option("wp-membership_currency", "USD");
			add_option("wp-membership_country", "usa");

			if(!is_array(get_option('wp-membership_admin_menu_location'))) update_option('wp-membership_admin_menu_location', array('Settings'));
			
			$showmenu = false;
			if(in_array('Settings', get_option("wp-membership_admin_menu_location"))) {
				if(file_exists(dirname(__FILE__)."/old_admin_menu.php")) {
					add_action('admin_menu', array(&$this, 'old_admin_menu'));
					$showmenu = true;
				}
			}
			if(in_array('Top Level', get_option("wp-membership_admin_menu_location"))) {
				add_action('admin_menu', array(&$this, 'admin_menu_init'));
				$showmenu = true;
			}
			if(!$showmenu) {
				update_option('wp-membership_admin_menu_location', array('Top Level'));
				add_action('admin_menu', array(&$this, 'admin_menu_init'));
			}
			add_filter('plugin_action_links', array(&$this, 'plugin_action_links'), 10, 2);
			register_activation_hook(__FILE__, array(&$this, "activation"));
			register_deactivation_hook(__FILE__, array(&$this, "deactivation"));
			add_action('init', array(&$this, 'init'));
			add_filter('the_title', array(&$this, 'the_title'));
			add_filter('the_content', array(&$this, 'the_content'));
			add_filter('the_content_rss', array(&$this, 'the_content'));
			add_filter('wp_list_pages_excludes', array(&$this, 'list_pages'));
			add_action('pre_get_posts', array(&$this, 'get_page'));
			add_filter('posts_where_paged', array(&$this, 'search'));
			add_filter('posts_where', array(&$this, 'search'));
			add_action('admin_notices', array(&$this, 'admin_notices'));
			add_action('edit_page_form', array(&$this, 'edit_page_form'));
			add_action('edit_form_advanced', array(&$this, 'edit_form_advanced'));
			add_action('save_post', array(&$this, 'save_post'));
			add_filter('the_posts', array(&$this, 'the_posts'));
			add_filter('list_cats', array(&$this, 'list_cats'));
			add_filter('get_terms', array(&$this, 'get_terms'), 10, 3);
			//add_action('edit_category_form', array(&$this, 'edit_category_form'));
			add_filter('getarchives_where', array(&$this, "search"));


			$methods = array();
		}
		
		function admin_menu_init() {
			load_plugin_textdomain('wp-membership', false, $this->language_path);
			add_menu_page(__('WP-Membership General Settings', 'wp-membership'), __('WP-Membership', 'wp-membership'), 8, __FILE__, array(&$this, 'display_options_tab_0'));
			add_submenu_page(__FILE__, __('WP-Membership General Settings', 'wp-membership'), __('News &amp; Info', 'wp-membership'), 8, __FILE__, array(&$this, 'display_options_tab_0'));
			add_submenu_page(__FILE__, __('WP-Membership General Settings', 'wp-membership'), __('General Settings', 'wp-membership'), 8, dirname(__FILE__)."/top_level_option_1.php", array(&$this, 'display_options_tab_1'));
			add_submenu_page(__FILE__, __('WP-Membership Users', 'wp-membership'), __('Users', 'wp-membership'), 8, dirname(__FILE__)."/top_level_option_2.php", array(&$this, 'display_options_tab_2'));
			add_submenu_page(__FILE__, __('WP-Membership Levels', 'wp-membership'), __('Levels', 'wp-membership'), 8, dirname(__FILE__)."/top_level_option_3.php", array(&$this, 'display_options_tab_3'));
		    add_submenu_page(__FILE__, __('WP-Membership Register Pages', 'wp-membership'), __('Register Pages', 'wp-membership'), 8, dirname(__FILE__)."/top_level_option_4.php", array(&$this, 'display_options_tab_4'));
			add_submenu_page(__FILE__, __('WP-Membership Payment Gateways', 'wp-membership'), __('Payment Gateways', 'wp-membership'), 8, dirname(__FILE__)."/top_level_option_5.php", array(&$this, 'display_options_tab_5'));
			add_submenu_page(__FILE__, __('WP-Membership Feedback', 'wp-membership'), __('Feedback', 'wp-membership'), 8, dirname(__FILE__)."/top_level_option_6.php", array(&$this, 'display_options_tab_6'));
			add_submenu_page(__FILE__, __('WP-Membership Troubleshooting', 'wp-membership'), __('Troubleshooting', 'wp-membership'), 8, dirname(__FILE__)."/top_level_option_7.php", array(&$this, 'display_options_tab_7'));
		}
		
		function admin_menu() {
			echo '<div class="wrap">';
			echo '<p>Here is where the form would go if I actually had options.</p>';
			echo '</div>';
		}
		
		function get_post_category_ids($post_id) {
			$retval = array();
			
			if(is_array($post_id)) {
				foreach($post_id as $post) {
					$retval += $this->get_post_category_ids($post);
				}
			}
			else if(is_object($post_id)) {
				$retval = $this->get_post_category_ids($post_id->ID);
			}
			else {
				$terms = wp_get_object_terms($post_id, "category");
				if(is_array($terms)) {
					foreach($terms as $term) {
						$retval[$term->term_id] = $term;
					}
				}
			}
			
			return $retval;
		}
		
		function edit_category_form($category) {
			global $wpdb;

			$editmode = isset($category->term_id) ? true : false;
			if($editmode) {
			?><table class="form-table">
		<tr class="form-field form-required">
			<th scope="row" valign="top"><label for="wp_membership_levels">WP-Membership Level(s)</label></th>
			<td><select name="wp_membership_levels[]" id="wp_membership_levels" style="height: 100px; width: 300px;" multiple="true"><?php
			$post_levels = array();
			if($editmode && $level_posts_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_level_categories AS t1 WHERE t1.WP_Term_ID=%s", $category->term_id), ARRAY_A)) {
				foreach($level_posts_rows as $level_posts_row) {
					$post_levels[] = $level_posts_row['Level_ID'];
				}
			}

			if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 ORDER BY t1.Name"), ARRAY_A)) {
				foreach($level_rows as $level_row) {
					echo "<option value=\"".htmlentities($level_row['Level_ID'])."\"";
					if(in_array($level_row['Level_ID'], $post_levels)) echo " SELECTED";
					echo ">".htmlentities($level_row['Name'])."</option>";
				}
			}
	?></select><br />
            The membership level(s) that this category is restricted to</td>
		</tr>
		</table><?php
			}
			else {
			?><div class="form-field">
	<label for="wp_membership_levels">WP-Membership Level(s)</label>
	<select name="wp_membership_levels[]" id="wp_membership_levels" style="height: 100px; width: 300px;" multiple="true"><?php
			$post_levels = array();
			if($editmode && $level_posts_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_level_categories AS t1 WHERE t1.WP_Term_ID=%s", $category->term_id), ARRAY_A)) {
				foreach($level_posts_rows as $level_posts_row) {
					$post_levels[] = $level_posts_row['Level_ID'];
				}
			}

			if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 ORDER BY t1.Name"), ARRAY_A)) {
				foreach($level_rows as $level_row) {
					echo "<option value=\"".htmlentities($level_row['Level_ID'])."\"";
					if(in_array($level_row['Level_ID'], $post_levels)) echo " SELECTED";
					echo ">".htmlentities($level_row['Name'])."</option>";
				}
			}
	?></select>
    <p>The membership level(s) that this category is restricted to</p>
</div><?php
			}
		}
		
		function get_terms($terms, $taxonomies, $args) {
			$retval = $terms;
			
			if(!is_admin()) {
				foreach($terms as $id => $term) {
					if($term->taxonomy == "category") {
						$callstack = debug_backtrace();
						$first = true;
						$func = null;
						$class = null;
						$recurse = false;
						foreach($callstack as $call) {
							if($first) {
								$func = $call['function'];
								$class = $call['class'];
								$first = false;
							}
							else {
								if($func == $call['function'] && $class == $call['class']) $recurse = true;
							}
						}
						if(!$recurse) {
							$posts = $this->the_posts(get_posts(array('numberposts' >= -1, 'category' => $term->term_id)));
							global $wp_membership_the_posts_cache;
							$wp_membership_the_posts_cache = null;
							$retval[$id]->count = count($posts);
							if($args['hide_empty'] && count($posts) <= 0) unset($retval[$id]);
						}
					}
				}
			}
			
			return $retval;
		}
		
		function get_ExtraFieldFromUserData($data) {
			$retval = null;
			
			global $wpdb;
			
			if(isset($data->version) && version_compare($data->version, '0.0.2', '>=') && isset($data->register_page_id)) {
				if($register_row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_register_pages AS t1 WHERE t1.Register_Page_ID=%s", $data->register_page_id))) {
					$extra_fields = @unserialize($register_row['Extra_Fields']);
					if(is_array($extra_fields)) {
						foreach($extra_fields as $extra_field) {
							if(isset($data->name) && isset($extra_field->name)) {
								if($data->name == $extra_field->name) {
									$retval = $extra_field;
									break;
								}
							}
						}
					}
				}
			}
			
			return $retval;
		}
		
		function get_category($cat) {
			$retval = $cat;
			
			$post_ids = @$_SESSION['wp-membership_plugin']['wp-membership_user_id'] <= 0 ? $post_ids = $this->get_Level_Posts() : array_diff($this->get_Level_Posts(), $this->get_Level_Posts($this->get_User_Levels()));
			$cats = get_posts(array('numberposts' >= -1, 'category' => $cat->cat_ID));

			$retval->category_count = 0;
			foreach($cats as $category) {
				if(!in_array($category->ID, $post_ids)) {
					$retval->category_count++;
				}
			}
			
			if($retval->category_count == 0) $retval = false;
			
			var_dump($retval);
		}
		
		function list_cats($cats) {
			
			return $cats;
		}
		
		function the_posts($posts) {
			$retval = $posts;
			
			if(!is_admin()) {
				global $wp_membership_the_posts_cache;
			    if(get_option('wp-membership_cache') && is_array($wp_membership_the_posts_cache)) {
			    	$retval = $wp_membership_the_posts_cache;
			    }
			    else {
					if(@$_SESSION['wp-membership_plugin']['wp-membership_user_id'] <= 0) {
						$post_ids = $this->get_Level_Posts();
						$term_list = $this->get_Level_Categories();
						$term_ids = @implode($term_list);
						if(strlen($term_ids) > 0) {
							$tmp_posts = get_posts(array('numberposts' >= -1, 'category' => $term_ids));
							foreach($tmp_posts as $tmp_post) $post_ids[$tmp_post->ID] = $tmp_post->ID;
						}
						$list = $posts;
						foreach($list as $key => $value) if(in_array($value->ID, $post_ids)) unset($retval[$key]);
				    }
				    else {
						$post_ids = array_diff($this->get_Level_Posts(), $this->get_Level_Posts($this->get_User_Levels()));
						$user_posts = $this->get_Level_Posts($this->get_User_Levels());
						$term_list = array_diff($this->get_Level_Categories(), $this->get_Level_Categories($this->get_User_Levels()));
						if(count($term_list) > 0) {
							$tmp_posts = get_posts(array('numberposts' >= -1, 'category' => @implode(",", $term_list)));
							foreach($tmp_posts as $tmp_post) if(!in_array($tmp_post->ID, $user_posts)) $post_ids[$tmp_post->ID] = $tmp_post->ID;
						}
						$term_list = array_diff($this->get_Level_Categories($this->get_User_Levels()), $this->get_Level_Categories());
						if(count($term_list) > 0) {
							$tmp_posts = get_posts(array('numberposts' >= -1, 'category' => @implode(",", $term_list)));
							foreach($tmp_posts as $tmp_post) unset($post_ids[$tmp_post->ID]);
						}
						$list = $posts;
						foreach($list as $key => $value) if(in_array($value->ID, $post_ids)) unset($retval[$key]);
				    }
					if(get_option('wp-membership_cache')) $wp_membership_the_posts_cache = $retval;
			    }
			}
			
			return $retval;
		}
		
		function save_post($post_id) {
			global $wpdb;
			
			if(@$_REQUEST['wp-membership_do_page_update'] == "1") {
				$levels = is_array(@$_REQUEST['wp-membership_page_levels']) ? $_REQUEST['wp-membership_page_levels'] : array();
				
				if ($the_post = wp_is_post_revision($post_id)){
					$post_id = $the_post;
				}

				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_level_pages WHERE WP_Page_ID=%s", $post_id));
				if(is_array(@$_REQUEST['wp-membership_page_levels'])) {
					foreach($_REQUEST['wp-membership_page_levels'] as $level_id) {
						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_level_pages (WP_Page_ID, Level_ID) VALUES (%s, %s)", @$post_id, $level_id));
					}
				}
			}
			
			if(@$_REQUEST['wp-membership_do_post_update'] == "1") {
				$levels = is_array(@$_REQUEST['wp-membership_post_levels']) ? $_REQUEST['wp-membership_post_levels'] : array();
				
				if ($the_post = wp_is_post_revision($post_id)){
					$post_id = $the_post;
				}

				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_level_posts WHERE WP_Post_ID=%s", $post_id));
				if(is_array(@$_REQUEST['wp-membership_post_levels'])) {
					foreach($_REQUEST['wp-membership_post_levels'] as $level_id) {
						$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_level_posts (WP_Post_ID, Level_ID) VALUES (%s, %s)", @$post_id, $level_id));
					}
				}
			}
		}
		
		function edit_page_form() {
			global $wpdb;
			
			?><h2>WP-Membership Options</h2>
			<div class="postbox">
				<h3><a class="togbox">+</a>Level(s)</h3>
				<div class="inside">
					<div>
						<p><strong>Select Level(s):</strong></p>
						<p>
							<input type="hidden" name="wp-membership_do_page_update" value="1" />
							<select name="wp-membership_page_levels[]" style="height: 100px !important; width: 690px !important;" multiple="true">
								<?php
			$page = get_page(@$_REQUEST['post']);
			$page_levels = array();
			if($level_pages_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_level_pages AS t1 WHERE t1.WP_Page_ID=%s", $page->ID), ARRAY_A)) {
				foreach($level_pages_rows as $level_pages_row) {
					$page_levels[] = $level_pages_row['Level_ID'];
				}
			}

			if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 ORDER BY t1.Name"), ARRAY_A)) {
				foreach($level_rows as $level_row) {
					echo "<option value=\"".htmlentities($level_row['Level_ID'])."\"";
					if(in_array($level_row['Level_ID'], $page_levels)) echo " SELECTED";
					echo ">".htmlentities($level_row['Name'])."</option>";
				}
			}
								?>
							</select>
						</p>
					</div>
				</div>
			</div>
						<?php
		}
		
		function edit_form_advanced() {
			global $wpdb;
			
			?><h2>WP-Membership Options</h2>
			<div class="postbox">
				<h3><a class="togbox">+</a>Level(s)</h3>
				<div class="inside">
					<div>
						<p><strong>Select Level(s):</strong></p>
						<p>
							<input type="hidden" name="wp-membership_do_post_update" value="1" />
							<select name="wp-membership_post_levels[]" style="height: 100px !important; width: 690px !important;" multiple="true">
								<?php
			$post = get_post(@$_REQUEST['post']);
			$post_levels = array();
			if($level_posts_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_level_posts AS t1 WHERE t1.WP_Post_ID=%s", $post->ID), ARRAY_A)) {
				foreach($level_posts_rows as $level_posts_row) {
					$post_levels[] = $level_posts_row['Level_ID'];
				}
			}

			if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 ORDER BY t1.Name"), ARRAY_A)) {
				foreach($level_rows as $level_row) {
					echo "<option value=\"".htmlentities($level_row['Level_ID'])."\"";
					if(in_array($level_row['Level_ID'], $post_levels)) echo " SELECTED";
					echo ">".htmlentities($level_row['Name'])."</option>";
				}
			}
								?>
							</select>
						</p>
					</div>
				</div>
			</div>
						<?php
		}
		
		function check_function($name) {
			return strlen(@$this->methods[$name]) > 0 ? true : false;
		}
		
		function search($where) {
			global $wpdb;
			
			if(!is_admin()) {
				$page_ids = @$_SESSION['wp-membership_plugin']['wp-membership_user_id'] > 0 ? array_diff($this->get_Level_Pages(), $this->get_Level_Pages($this->get_User_Levels())) : $this->get_Level_Pages();
				$where .= " AND ".$wpdb->prefix."posts.ID NOT IN ('".@implode("','", $page_ids)."')";
				$post_ids = @$_SESSION['wp-membership_plugin']['wp-membership_user_id'] > 0 ? array_diff($this->get_Level_Posts(), $this->get_Level_Posts($this->get_User_Levels())) : $this->get_Level_Posts();
				$term_ids = @$_SESSION['wp-membership_plugin']['wp-membership_user_id'] > 0 ? array_diff($this->get_Level_Categories(), $this->get_Level_Categories($this->get_User_Levels())) : $this->get_Level_Categories();
				if(count($term_ids) > 0) {
					$tmp_posts = get_posts(array('numberposts' >= -1, 'category' => @implode("','", $term_ids)));
					foreach($tmp_posts as $tmp_post) $post_ids[$tmp_post->ID] = $tmp_post->ID;
				}
				$where .= " AND ".$wpdb->prefix."posts.ID NOT IN ('".@implode("','", $post_ids)."')";
			}
			
			return $where;
		}
		
		function apply_update($display_message = true) {
			global $wpdb;

			if(strlen(get_option("wp-membership_db_version")) <= 0) update_option('wp-membership_db_version', "0.0.0");
			if($this->dbVersionCheck("0.0.1")) {
				$post_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_level_posts (Post_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, WP_Post_ID VARCHAR(255) NOT NULL, Level_ID BIGINT UNSIGNED NOT NULL, UNIQUE (WP_Post_ID, Level_ID), CONSTRAINT FOREIGN KEY (Level_ID) REFERENCES ".$wpdb->prefix."wp_membership_levels (Level_ID) ON UPDATE CASCADE) ENGINE=INNODB";
				$wpdb->query($post_query);
				update_option('wp-membership_db_version', "0.0.1");
			}
			if($this->dbVersionCheck("0.0.2")) {
				$post_query = "ALTER TABLE ".$wpdb->prefix."wp_membership_users ADD Username VARCHAR(255) UNIQUE AFTER Email";
				$wpdb->query($post_query);
				update_option('wp-membership_db_version', "0.0.2");
			}
			if($this->dbVersionCheck("0.0.3")) {
				$category_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_level_categories (Category_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, WP_Term_ID VARCHAR(255) NOT NULL, Level_ID BIGINT UNSIGNED NOT NULL, UNIQUE (WP_Term_ID, Level_ID), CONSTRAINT FOREIGN KEY (Level_ID) REFERENCES ".$wpdb->prefix."wp_membership_levels (Level_ID) ON UPDATE CASCADE) ENGINE=INNODB";
				$wpdb->query($category_query);
				update_option('wp-membership_db_version', "0.0.3");
			}
			if($this->dbVersionCheck("0.0.4")) {
				$alter_query = "ALTER TABLE ".$wpdb->prefix."wp_membership_users ADD Extra_Fields TEXT AFTER Active";
				$wpdb->query($alter_query);
				$alter_query = "ALTER TABLE ".$wpdb->prefix."wp_membership_register_pages ADD Extra_Fields TEXT AFTER WP_Page_ID";
				$wpdb->query($alter_query);
				update_option('wp-membership_db_version', "0.0.4");
			}
			if($this->dbVersionCheck("0.0.5")) {
				$alter_query = "ALTER TABLE ".$wpdb->prefix."wp_membership_users ADD WP_Password VARCHAR(255) AFTER Password";
				$wpdb->query($alter_query);
				update_option('wp-membership_db_version', "0.0.5");
			}
			if($this->dbVersionCheck("0.0.6")) {
				$alter_query = "ALTER TABLE ".$wpdb->prefix."wp_membership_register_pages MODIFY Extra_Fields LONGTEXT";
				$wpdb->query($alter_query);
				update_option('wp-membership_db_version', "0.0.6");
			}
			if($display_message) $this->admin_messages[] = "Database updates have been applied";
			update_option('wp-membership_apply_update', false);
		}
		
		function dbVersionCheck($version) {
			$retval = false;
			$db_version = get_option('wp-membership_db_version');
			
			if(!is_null($version)) {
				$numbers = explode(".", $db_version);
				$numbers2 = explode(".", $version);
				if(count($numbers) == 3) {
					if($numbers[0] < $numbers2[0]) {
						$retval = true;
					}
					else if($numbers[1] < $numbers2[1]) {
						$retval = true;
					}
					else {
						if(eregi("^([0-9]+)([a-z])?\$", $numbers[2], $regs) && eregi("^([0-9]+)([a-z])?\$", $numbers2[2], $regs2)) {
							if($regs[1] < $regs2[1]) {
								$retval = true;
							}
							else if(strlen(@$regs[2]) > 0 && @ord(strtolower(@$regs[2])) < @ord(strtolower(@$regs2[2]))) {
								$retval = true;
							}
							else if(strlen(@$regs[2]) <= 0 && strlen(@$regs2[2]) > 0) {
								$retval = true;
							}
							else {
								$retval = false;
							}
						}
					}
				}
			}
			
			return $retval;
		}
	
		function init() {
			global $wpdb;
			
			$plugin_dir = basename(dirname(__FILE__));
			if($plugin_dir != 'free-wp-membership') $this->admin_notices[] = 'Warning, the plug-in is not installed in the wp-content/plugins/free-wp-membership folder. Instead it is installed in the wp-content/plugins/'.$plugin_dir.' folder. This may prefent the plug-in from functioning properly.';
			
			if(@$_REQUEST['wp-membership_recreatedb'] == '1') {
				if($this->create_tables()) {
					$this->admin_messages[] = "Successfully recreated the database";
				}
				else {
					$this->admin_notices[] = "Failed to recreate database";
				}
			}
			
			$this->basepath = pathinfo($_SERVER['SCRIPT_FILENAME']);
			$this->basepath = ereg_replace("/wp-admin\$", "", getcwd());//@$this->basepath['dirname']);
			$this->basepath = ereg_replace("/wp-content/plugins/free-wp-membership\$", "", @$this->basepath);
			$methods = array();
			//TODO load data library
			$methods = @file_get_contents(dirname(__FILE__).'/methods/data_library');

				$dh = @opendir(dirname(__FILE__).'/plugins');
				if($dh) {
					while(($file = readdir($dh)) !== false) {
						if(!ereg("[.]", $file)) {
							$option = "wp-membership_plugin_$file";
							add_option($option, "");
							if(get_option($option) == "1") {
								//**FIXME**//
								//Find a way to make the cache work!
								//**END_FIXME**//
								if(($plugin = wp_cache_get($option)) !== false) {
									$this->plugins[$file] = $plugin;
								}
								else {
									$loaded = false;
									$tmp = trim(@mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $wp_membership_key1, @file_get_contents(dirname(__FILE__).'/plugins/'.$file), MCRYPT_MODE_ECB, pack('H*', $wp_membership_key2)));
									if($tmp) {
										if(ereg("class[[:space:]]+([a-zA-Z0-9_]+)", $tmp, $regs)) {
											eval($tmp);
											eval('$this->plugins[$file] = new '.$regs[1].'();');
											if(is_a($this->plugins[$file], 'wp_membership_payment_gateway')) $this->plugins[$file]->Install();
											wp_cache_add($option, $this->plugins[$file]);
											$loaded = true;
										}
									}
									if(!$loaded) {
										$tmp = trim(@mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $wp_membership_key1, @base64_decode(@file_get_contents(dirname(__FILE__).'/plugins/'.$file)), MCRYPT_MODE_ECB, pack('H*', $wp_membership_key2)));
										if($tmp) {
											if(ereg("class[[:space:]]+([a-zA-Z0-9_]+)", $tmp, $regs)) {
												eval($tmp);
												eval('$this->plugins[$file] = new '.$regs[1].'();');
												if(is_a($this->plugins[$file], 'wp_membership_payment_gateway')) $this->plugins[$file]->Install();
												wp_cache_add($option, $this->plugins[$file]);
												$loaded = true;
											}
										}
									}
								}
							}
						}
					}
					closedir($dh);
				}
			if(session_id() == "") session_start();
			$clean_users_query = $wpdb->prepare("DELETE FROM {$wpdb->prefix}wp_membership_user_levels WHERE (Start < %s AND Expiration=%s) OR (Expiration > %s AND Expiration < %s)", @date("Y-m-d H:i:s", @strtotime("-3 days")), @date("Y-m-d H:i:s", 0), @date("Y-m-d H:i:s", 0), @date("Y-m-d H:i:s", @strtotime("-1 month")));
			$wpdb->query($clean_users_query);
		}
		
		function admin_notices() {
			if(is_array($this->admin_notices)) {
				foreach($this->admin_notices as $notice) {
					echo "<div id=\"message\" class=\"error\"><p><em>WP-Membership Plugin Error: </em><strong>$notice</strong></p></div>";
				}
			}
			else echo "<div id=\"message\" class=\"error\"><p><strong>Sombody has Sabotauged The WP-Membership Plugin!</strong></p></div>";
		
			if(is_array($this->admin_messages)) {
				foreach($this->admin_messages as $notice) {
					echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><em>WP-Membership Plugin Message: </em><strong>$notice</strong></p></div>";
				}
			}
			else echo "<div id=\"message\" class=\"error\"><p><strong>Sombody has Sabotauged The WP-Membership Plugin!</strong></p></div>";
		}
		
		function get_redirect_url() {
			$retval = false;
			
			if(isset($_SERVER['REDIRECT_SCRIPT_URL'])) $retval = $_SERVER['REDIRECT_SCRIPT_URL'];
			else if(isset($_SERVER['REQUEST_URI'])) $retval = $_SERVER['REQUEST_URI'];
			
			if($retval !== false) $retval = eregi_replace("^https?://[^/]+", "", $retval);
			
			return $retval;
		}
		
		function get_permalinks() {
			$retval = array();

			$pages = get_pages();
			foreach($pages as $page) {
				$tmp = eregi_replace("^https?://[^/]+", "", get_permalink($page->ID));
				$retval[$tmp] = $page->ID;
			}
			
			return $retval;
		}
		
		function get_page($page) {
			global $wpdb, $_REQUEST;

			//var_dump($page);echo"<br /><br />";
			$redirect_url = $this->get_redirect_url();
			if($redirect_url !== false) {
				$list = @$this->get_permalinks();
				//**FIXME**//
				//All References to page_id should be through $page->ID
				//**END_FIXME**//
				$page_id = isset($page->queried_object->ID) ? $page->queried_object->ID : @$_REQUEST['page_id'];//isset($list[$redirect_url]) ? $list[$redirect_url] : (isset($page->queried_object->ID) ? $page->queried_object->ID : "");
				$_POST['page_id'] = $_GET['page_id'] = $_REQUEST['page_id'] = $page_id;
			}
			$page_id = isset($page->queried_object->ID) ? $page->queried_object->ID : @$_REQUEST['page_id'];
			
		    if($page_id == get_option("wp-membership_login_page_id") && @$_REQUEST['do_login'] == "1") {
		    	$user_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_users AS t1 WHERE (SELECT COUNT(*) FROM ".$wpdb->prefix."wp_membership_user_levels AS t2 WHERE t1.User_ID=t2.User_ID AND (t2.Expiration IS NULL OR t2.Expiration>=NOW()))>0 AND (t1.Email=%s OR t1.Username=%s) AND t1.Password=PASSWORD(%s)", @$_REQUEST['email'], @$_REQUEST['email'], @$_REQUEST['password']);
		    	if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
					$_SESSION['wp-membership_plugin']['wp-membership_user_id'] = $user_row['User_ID'];
				    $page->set('page_id', get_option("wp-membership_logged_in_page_id"));
				    header("Location: ".get_permalink(get_option("wp-membership_logged_in_page_id")));
				    exit;
		    	}
		    	else if(!isset($this->public_messages['bad_password'])) {
		    		$this->public_messages['prompt_password'] = "<div class=\"login_error\">Error: Bad Email or Password.</div>";
		    	}
		    }
		    else if(!isset($this->public_messages['do_password_reset']) && $page_id == get_option("wp-membership_login_page_id") && get_option('wp-membership_login_prompt_forgot_password') == "1" && @$_REQUEST['do_forgot_password'] == "1") {
		    	$user_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_users AS t1 WHERE (SELECT COUNT(*) FROM ".$wpdb->prefix."wp_membership_user_levels AS t2 WHERE t1.User_ID=t2.User_ID AND (t2.Expiration IS NULL OR t2.Expiration>=NOW()))>0 AND (t1.Email=%s OR t1.Username=%s)", @$_REQUEST['email'], @$_REQUEST['email']);
		    	if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
		    		$password = "";
		    		while(strlen($password) < 8) {
		    			$password .= chr(rand(ord("0"), ord("z")));
		    		}
			    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET Password=PASSWORD(%s) WHERE User_ID=%s", $password, $user_row['User_ID']);
			    	if($wpdb->query($update_query)) {
				    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET WP_Password=%s WHERE User_ID=%s", wp_hash_password($password), $user_row['User_ID']);
			    		$wpdb->query($update_query);
			    		$this->public_messages['do_password_reset'] = "<div class=\"forgot_password_message\">Password Successfully Reset. Check your e-mail for the new password.</div>";
			    		wp_mail($user_row['Email'], "Password was reset by request", "Someone (probably you) requested your password be reset. Your new password is $password. This is case sensitive. You can login at: http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\n--\nManagement");
			    	}
			    	else $this->public_messages['do_password_reset'] = "<div class=\"forgot_password_error\">Failed to reset password, please contact your system administrator.</div>";
		    	}
		    	else $this->public_messages['do_password_reset'] = "<div class=\"forgot_password_message\">Password Successfully Reset. Check your e-mail for the new password.</div>";
		    }
		    else if($page_id == get_option("wp-membership_logout_page_id")) {
				unset($_SESSION['wp-membership_plugin']['wp-membership_user_id']);
		    }
			else if($this->is_Register_Page($page_id)) {
				switch(@$_REQUEST['do_register']) {
					case "2":
						if(is_email(@$_REQUEST['email']) && strlen(trim(@$_REQUEST['password'])) > 0 && @$_REQUEST['password'] == @$_REQUEST['password2']) {
							$levels = explode("_", @$_REQUEST['wp-membership_level_id']);
							$user_id = null;
							$insert_query = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_users (Email, Password, Active) VALUES (%s, PASSWORD(%s), 1)", @$_REQUEST['email'], @$_REQUEST['password']);
							if($wpdb->query($insert_query)) {
								$user_id = $wpdb->insert_id;
						    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET WP_Password=%s WHERE User_ID=%s", wp_hash_password(@$_REQUEST['password']), $user_id);
					    		$wpdb->query($update_query);
							}
							else {
								$user_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_users AS t1 WHERE t1.Email=%s AND t1.Password=PASSWORD(%s)", @$_REQUEST['email'], @$_REQUEST['password']);
								if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
									$user_id = $user_row['User_ID'];
								}
							}
							if(!is_null($user_id)) {
								$extra_data = array();
								$register_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 WHERE t1.Register_Page_ID=%s", @$_REQUEST['member_register_page_id']);
								$success = true;
								$messages = '';
								if($register_row = $wpdb->get_row($register_query, ARRAY_A)) {
									$extra_fields = @unserialize($register_row['Extra_Fields']);
									if(!is_array($extra_fields)) $extra_fields = array();
									$first = true;
									foreach($extra_fields as $extra_id => $extra_field) {
										$name = isset($extra_field->name) ? $extra_field->name : "";
										$caption = isset($extra_field->caption) ? $extra_field->caption : "";
										$save = isset($extra_field->save) ? $extra_field->save : false;
										$required = isset($extra_field->required) ? $extra_field->required : false;
										$required_regex = isset($extra_field->required_regex) ? $extra_field->required_regex : "";
										$required_error = isset($extra_field->required_error) ? $extra_field->required_error : "";
										$type = isset($extra_field->type) ? $extra_field->type : "";
										unset($data);
										$data->version = "0.0.2";
										$data->register_page_id = $register_row['Register_Page_ID'];
										$data->id = $extra_id;
										$data->name = $name;
										$data->caption = $caption;
										$data->value = isset($_REQUEST['wp-membership-extra_fields-'.$name]) ? $_REQUEST['wp-membership-extra_fields-'.$name] : null;
										if($required) {
											switch($type) {
												case 'radio':
												case 'checkbox':
												case 'select':
													if(strlen($data->value) <= 0) {
														$success = false;
														if($first) $first = false;
														else $messages .= '<br />';
														$messages .= htmlentities(str_replace("<%caption%>", $caption, $required_error));
													}
													break;
												default:
													if(!ereg($required_regex, $data->value)) {
														$success = false;
														if($first) $first = false;
														else $messages .= '<br />';
														$messages .= htmlentities(str_replace("<%caption%>", $caption, $required_error));
													}
													break;
											}
										}
										if($save) $extra_data[] = $data;
									}									
								}
								if($success) {
									$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_users SET Extra_Fields=%s WHERE User_ID=%s", serialize($extra_data), $user_id);
									$wpdb->query($update_query);
									if(count($levels) == 1) {
										$insert_query = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_user_levels (User_ID, Level_ID, Start) VALUES (%s, %s, NOW())", $user_id, $levels[0]);
									}
									else {
										$insert_query = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_user_levels (User_ID, Level_ID, Level_Price_ID, Start, Expiration) VALUES (%s, %s, %s, NOW(), %s)", $user_id, $levels[0], $levels[1], date("Y-m-d H:i:s", 0));
									}
									global $wp_membership_plugin_register_step;
									if(!is_bool($wp_membership_plugin_register_step)) $wp_membership_plugin_register_step = false;
									if($wp_membership_plugin_register_step || $wpdb->query($insert_query)) {
										$wp_membership_plugin_register_step = true;
										if(count($levels) == 1) {
											$_SESSION['wp-membership_plugin']['wp-membership_user_id'] = $user_id;
										    $page->set('page_id', get_option("wp-membership_logged_in_page_id"));
										}
									}
									else $_REQUEST['do_register'] = 1;
								}
								else {
						    		$this->public_messages['extra_fields_message'] = "<div class=\"extra_fields_message\">$messages</div>";
									$_REQUEST['do_register'] = 1;
								}
							}
							else $_REQUEST['do_register'] = 1;
						}
						else $_REQUEST['do_register'] = 0;
						break;
				}
			}
		    if(@$_SESSION['wp-membership_plugin']['wp-membership_user_id'] <= 0) {
		    	$pages = $this->get_Level_Pages();
		    	foreach(array(get_option("wp-membership_user_profile_page_id")) as $pageid) {
		    		$pages[] = $pageid;
		    		$pages[] = get_permalink($pageid);
		    	}
		    	$posts = $this->get_Level_Posts();
		    	//var_dump($page);echo"<br /><br />";var_dump($posts);echo"<br /><br />";var_dump($pages);echo"<br /><br />";
				if(@in_array($page->queried_object_id, $pages)) {// || @in_array($page->p, $posts)) {
					$page->set('page_id', get_option("wp-membership_access_denied_page_id"));
				}
		    }
			//var_dump($page);echo"<br /><br />";
		    
		    return $page;
		}
		
		function activation() {
			if($this->create_tables()) {
				foreach($this->plugins as $plugin) {
					if(is_a($plugin, 'wp_membership_payment_gateway')) $plugin->Install();
				}
			}
			else {
				$this->admin_notices[] = "Failed to properly create database";
			}
		}
		
		function deactivation() {
			global $wpdb;

//			delete_option('wp-membership_db_version');
//			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."wp_membership_register_page_gateways");
//			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."wp_membership_register_page_levels");
//			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."wp_membership_register_pages");
//			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."wp_membership_level_pages");
//			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."wp_membership_user_levels");
//			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."wp_membership_level_prices");
//			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."wp_membership_levels");
//			$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix."wp_membership_users");
		}
		
		function create_tables() {
		    global $wpdb;
		    
			$user_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_users (User_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, Email VARCHAR(255) NOT NULL UNIQUE, Username VARCHAR(255) UNIQUE, Password VARCHAR(255) NOT NULL, WP_Password VARCHAR(255), Active TINYINT(1) UNSIGNED NOT NULL, Extra_Fields TEXT) ENGINE=INNODB";
			$levels_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_levels (Level_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, Name VARCHAR(255) NOT NULL UNIQUE, Description TEXT NOT NULL) ENGINE=INNODB";
			$level_prices_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_level_prices (Level_Price_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, Level_ID BIGINT UNSIGNED NOT NULL, Price DOUBLE NOT NULL, Duration VARCHAR(255), Delay VARCHAR(255), CONSTRAINT FOREIGN KEY (Level_ID) REFERENCES ".$wpdb->prefix."wp_membership_levels (Level_ID) ON UPDATE CASCADE) ENGINE=INNODB";
			$user_levels_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_user_levels (User_Level_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, User_ID BIGINT UNSIGNED NOT NULL, Level_ID BIGINT UNSIGNED NOT NULL, Level_Price_ID BIGINT UNSIGNED, Start DATETIME, Expiration DATETIME, UNIQUE (User_ID, Level_ID), CONSTRAINT FOREIGN KEY (User_ID) REFERENCES ".$wpdb->prefix."wp_membership_users (User_ID) ON UPDATE CASCADE, CONSTRAINT FOREIGN KEY (Level_ID) REFERENCES ".$wpdb->prefix."wp_membership_levels (Level_ID) ON UPDATE CASCADE, CONSTRAINT FOREIGN KEY (Level_Price_ID) REFERENCES ".$wpdb->prefix."wp_membership_level_prices (Level_Price_ID) ON UPDATE CASCADE) ENGINE=INNODB";
			$page_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_level_pages (Page_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, WP_Page_ID VARCHAR(255) NOT NULL, Level_ID BIGINT UNSIGNED NOT NULL, UNIQUE (WP_Page_ID, Level_ID), CONSTRAINT FOREIGN KEY (Level_ID) REFERENCES ".$wpdb->prefix."wp_membership_levels (Level_ID) ON UPDATE CASCADE) ENGINE=INNODB";
			$post_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_level_posts (Post_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, WP_Post_ID VARCHAR(255) NOT NULL, Level_ID BIGINT UNSIGNED NOT NULL, UNIQUE (WP_Post_ID, Level_ID), CONSTRAINT FOREIGN KEY (Level_ID) REFERENCES ".$wpdb->prefix."wp_membership_levels (Level_ID) ON UPDATE CASCADE) ENGINE=INNODB";
			$category_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_level_categories (Category_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, WP_Term_ID VARCHAR(255) NOT NULL, Level_ID BIGINT UNSIGNED NOT NULL, UNIQUE (WP_Term_ID, Level_ID), CONSTRAINT FOREIGN KEY (Level_ID) REFERENCES ".$wpdb->prefix."wp_membership_levels (Level_ID) ON UPDATE CASCADE) ENGINE=INNODB";
			$register_pages_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_register_pages (Register_Page_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, Name VARCHAR(255) NOT NULL UNIQUE, Description TEXT NOT NULL, Macro VARCHAR(255) NOT NULL, WP_Page_ID VARCHAR(255) NOT NULL, Extra_Fields TEXT) ENGINE=INNODB";
			$register_page_levels_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_register_page_levels (Register_Page_Level_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, Register_Page_ID BIGINT UNSIGNED NOT NULL, Level_ID BIGINT UNSIGNED NOT NULL, UNIQUE (Register_Page_ID, Level_ID), CONSTRAINT FOREIGN KEY (Register_Page_ID) REFERENCES ".$wpdb->prefix."wp_membership_register_pages (Register_Page_ID) ON UPDATE CASCADE, CONSTRAINT FOREIGN KEY (Level_ID) REFERENCES ".$wpdb->prefix."wp_membership_levels (Level_ID) ON UPDATE CASCADE) ENGINE=INNODB";
			$register_page_gateways_query = "CREATE TABLE IF NOT EXISTS ".$wpdb->prefix."wp_membership_register_page_gateways (Register_Page_Gateway_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, Register_Page_ID BIGINT UNSIGNED NOT NULL, Payment_Gateway VARCHAR(255) NOT NULL, UNIQUE (Register_Page_ID, Payment_Gateway), CONSTRAINT FOREIGN KEY (Register_Page_ID) REFERENCES ".$wpdb->prefix."wp_membership_register_pages (Register_Page_ID) ON UPDATE CASCADE) ENGINE=INNODB";
			$queries = array($user_query, $levels_query, $level_prices_query, $user_levels_query, $page_query, $post_query, $category_query, $register_pages_query, $register_page_levels_query, $register_page_gateways_query);
			$result = true;
			foreach($queries as $query) $result &= $wpdb->query($query) === false ? false : true;
			/*
			$wpdb->query($user_query);$result = (bool)$wpdb->result;
			$wpdb->query($levels_query);$result &= (bool)$wpdb->result;
			$wpdb->query($level_prices_query);$result &= (bool)$wpdb->result;
			$wpdb->query($user_levels_query);$result &= (bool)$wpdb->result;
			$wpdb->query($page_query);$result &= (bool)$wpdb->result;
			$wpdb->query($post_query);$result &= (bool)$wpdb->result;
			$wpdb->query($category_query);$result &= (bool)$wpdb->result;
			$wpdb->query($register_pages_query);$result &= (bool)$wpdb->result;
			$wpdb->query($register_page_levels_query);$result &= (bool)$wpdb->result;
			$wpdb->query($register_page_gateways_query);$result &= (bool)$wpdb->result; */
		    if($result) {
				add_option("wp-membership_db_version", "0.0.1");
				$this->apply_update(false);
		    }
		    
		    return $result;
		}
		
		function get_User_Levels() {
			$retval = array();

			global $wpdb;
			
			if(@$_SESSION['wp-membership_plugin']['wp-membership_user_id'] > 0) {
				if($user_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_user_levels AS t1 WHERE t1.User_ID=%s", @$_SESSION['wp-membership_plugin']['wp-membership_user_id']), ARRAY_A)) {
					foreach($user_rows as $user_row) {
						$retval[$user_row['Level_ID']] = $user_row['Level_ID'];
					}
				}
			}
			
			return $retval;
		}
		
		function is_Register_Page($register_id) {
			$retval = false;

			global $wpdb;
			
			$register_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages WHERE LENGTH(Macro) > 0 AND WP_Page_ID=%s", @$_REQUEST['page_id']);
			if($register_rows = $wpdb->get_results($register_query, ARRAY_A)) {
				$retval = true;
			}
			
			return $retval;
		}
		
		function get_Register_Pages($register_id = null) {
			$retval = array();

			global $wpdb;
			
			if($register_id === null) {
				if($register_rows = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT t1.WP_Page_ID FROM ".$wpdb->prefix."wp_membership_register_pages AS t1"), ARRAY_A)) {
					foreach($register_rows as $register_row) {
						$retval[$register_row['WP_Page_ID']] = $register_row['WP_Page_ID'];
					}
				}
			}
			else if(is_array($register_id)) {
				foreach($register_id as $id) {
					$retval += $this->get_Register_Pages($id);
				}
			}
			else if($register_id > 0) {
				if($register_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 WHERE t1.Register_Page_ID=%s", $register_id), ARRAY_A)) {
					foreach($register_rows as $register_row) {
						$retval[$register_row['WP_Page_ID']] = $register_row['WP_Page_ID'];
					}
				}
			}
			
			return $retval;
		}
		
		function get_Level_Pages($level_id = null) {
			$retval = array();

			global $wpdb;
			
			if($level_id === null) {
				if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT t1.WP_Page_ID FROM ".$wpdb->prefix."wp_membership_level_pages AS t1"), ARRAY_A)) {
					foreach($level_rows as $level_row) {
						$retval[$level_row['WP_Page_ID']] = $level_row['WP_Page_ID'];
					}
				}
			}
			else if(is_array($level_id)) {
				foreach($level_id as $id) {
					$retval += $this->get_Level_Pages($id);
				}
			}
			else if($level_id > 0) {
				if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_level_pages AS t1 WHERE t1.Level_ID=%s", $level_id), ARRAY_A)) {
					foreach($level_rows as $level_row) {
						$retval[$level_row['WP_Page_ID']] = $level_row['WP_Page_ID'];
					}
				}
			}
			
			return $retval;
		}
		
		function get_Level_Posts($level_id = null) {
			$retval = array();

			global $wpdb;
			
			if($level_id === null) {
				if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT t1.WP_Post_ID FROM ".$wpdb->prefix."wp_membership_level_posts AS t1"), ARRAY_A)) {
					foreach($level_rows as $level_row) {
						$retval[$level_row['WP_Post_ID']] = $level_row['WP_Post_ID'];
					}
				}
			}
			else if(is_array($level_id)) {
				foreach($level_id as $id) {
					$retval += $this->get_Level_Posts($id);
				}
			}
			else if($level_id > 0) {
				if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_level_posts AS t1 WHERE t1.Level_ID=%s", $level_id), ARRAY_A)) {
					foreach($level_rows as $level_row) {
						$retval[$level_row['WP_Post_ID']] = $level_row['WP_Post_ID'];
					}
				}
			}
			
			return $retval;
		}
		
		function get_Level_Categories($level_id = null) {
			$retval = array();

			global $wpdb;
			
			if($level_id === null) {
				if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT t1.WP_Term_ID FROM ".$wpdb->prefix."wp_membership_level_categories AS t1"), ARRAY_A)) {
					foreach($level_rows as $level_row) {
						$retval[$level_row['WP_Term_ID']] = $level_row['WP_Term_ID'];
					}
				}
			}
			else if(is_array($level_id)) {
				foreach($level_id as $id) {
					$retval += $this->get_Level_Categories($id);
				}
			}
			else if($level_id > 0) {
				if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_level_categories AS t1 WHERE t1.Level_ID=%s", $level_id), ARRAY_A)) {
					foreach($level_rows as $level_row) {
						$retval[$level_row['WP_Term_ID']] = $level_row['WP_Term_ID'];
					}
				}
			}
			
			return $retval;
		}
		
		function list_pages($excludes) {
			$retval = is_array($excludes) ? $excludes : array();

		    global $wp_membership_list_pages_cache;
		    if(get_option('wp-membership_cache') && is_array($wp_membership_list_pages_cache)) {
		    	$retval = $wp_membership_list_pages_cache;
		    }
		    else {
				$retval = array_merge(array(get_option("wp-membership_access_denied_page_id"), get_option("wp-membership_logged_in_page_id")), $excludes);
			    
			    foreach($this->plugins as $plugin) {
			    	$pages = $plugin->get_Hidden_Pages();
			    	if(is_array($pages)) {
			    		foreach($pages as $page_id) $retval[] = $page_id;
			    	}
			    }
			    
			    if(@$_SESSION['wp-membership_plugin']['wp-membership_user_id'] <= 0) {
			    	$retval[] = get_option("wp-membership_logout_page_id");
			    	$retval[] = get_option("wp-membership_user_profile_page_id");
					$page_ids = $this->get_Level_Pages();
					foreach($page_ids as $id) $retval[] = $id;
			    }
			    else {
			    	$retval[] = get_option("wp-membership_login_page_id");
					$page_ids = $this->get_Register_Pages();
					foreach($page_ids as $id) $retval[] = $id;
					$page_ids = array_diff($this->get_Level_Pages(), $this->get_Level_Pages($this->get_User_Levels()));
					foreach($page_ids as $id) $retval[] = $id;
			    }
	
				$tmp = $retval;
				foreach($tmp as $value) {
					$retval[] = get_permalink($value);
				}
				if(get_option('wp-membership_cache')) $wp_membership_list_pages_cache = $retval;
		    }
		    
		    return $retval;
		}
		
		function the_title($title) {
		    return $title;
		}
		
		function the_content($content) {
			$retval = $content;

			global $wpdb, $_REQUEST, $wp_query;

			$redirect_url = $this->get_redirect_url();
			if($redirect_url !== false) {
				$list = @$this->get_permalinks();
				$page_id = isset($wp_query->queried_object->ID) ? $wp_query->queried_object->ID : @$_REQUEST['page_id'];//isset($list[$redirect_url]) ? $list[$redirect_url] : (isset($wp_query->queried_object->ID) ? $wp_query->queried_object->ID : "");
				$_POST['page_id'] = $_GET['page_id'] = $_REQUEST['page_id'] = $page_id;
			}
			$page_id = isset($wp_query->queried_object->ID) ? $wp_query->queried_object->ID : @$_REQUEST['page_id'];
			
			if(is_array($this->public_messages)) foreach($this->public_messages as $message) $retval = $message.$retval;
			
			$macro = get_option("wp-membership_loginfrom_macro");
			if(trim($macro) != "" && $page_id == get_option("wp-membership_login_page_id")) {
				$retval = str_replace($macro, $this->default_loginform(), $retval);
			}
			else if(trim(get_option("wp-membership_user_profile_from_macro")) != "" && $page_id == get_option("wp-membership_user_profile_page_id")) {
				$retval = str_replace(get_option("wp-membership_user_profile_from_macro"), $this->default_userprofileform(), $retval);
			}
			else if($this->is_Register_Page($page_id)) {
				$register_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages WHERE LENGTH(Macro) > 0 AND WP_Page_ID=%s", @$_REQUEST['page_id']);
				if($register_rows = $wpdb->get_results($register_query, ARRAY_A)) {
					foreach($register_rows as $register_row) {
						switch(@$_REQUEST['do_register']) {
							case -1:
								break;
							case 0:
								break;
							case 1:
								if(is_email(@$_REQUEST['email']) && strlen(trim(@$_REQUEST['password'])) > 0 && @$_REQUEST['password'] == @$_REQUEST['password2']) {
									
								}
								else {
									$_REQUEST['do_register'] = 0;
									if(@$_REQUEST['password'] != @$_REQUEST['password2']) $retval = "Passwords do not match<br />".$retval;
									if(trim(@$_REQUEST['password']) == "") $retval = "Password cannot be blank<br />".$retval;
									if(!is_email(@$_REQUEST['email'])) $retval = "A valid email address is required<br />".$retval;
								}
								break;
							case 2:
								if(is_email(@$_REQUEST['email']) && strlen(trim(@$_REQUEST['password'])) > 0 && @$_REQUEST['password'] == @$_REQUEST['password2']) {
									$levels = explode("_", @$_REQUEST['wp-membership_level_id']);
									if(count($levels) > 1) {
									}
									else {
										$_REQUEST['do_register'] = "-1";
									}
								}
								else {
									$_REQUEST['do_register'] = 0;
									if(@$_REQUEST['password'] != @$_REQUEST['password2']) $retval = "Passwords do not match<br />".$retval;
									if(trim(@$_REQUEST['password']) == "") $retval = "Password cannot be blank<br />".$retval;
									if(!is_email(@$_REQUEST['email'])) $retval = "A valid email address is required<br />".$retval;
								}
								break;
							case 3:
								$reqs = array(	'billing_name' => "Billing Name",
												'billing_address' => "Billing Address",
												'billing_city' => "Billing City",
												'billing_address' => "Billing Address",
												'billing_state' => "Billing State",
												'billing_zip' => "Billing Zip/Postal Code",
												'billing_country' => "Billing Country",
												'payment_name' => "Payment Name",
												'payment_ccnum' => "Payment Card Number",
												'payment_ccexp_month' => "Payment Card Expiration Month",
												'payment_ccexp_year' => "Payment Card Expiration Year",
												'payment_cvv2' => "Payment Security Code");
								$errors = '';
								foreach($reqs as $name => $caption) {
									if(strlen(trim(@$_REQUEST[$name])) <= 0) {
										if(strlen($errors) > 0) $errors .= '<br />';
										$errors .= $caption.' can not be empty.';
										
									}
								}
								if(strlen($errors) > 0) {
									$_REQUEST['do_register'] = 2;
									$retval = '<p class="errors">'.$errors.'</p>'.$retval;
								}
								else {
									$level_id = @$_REQUEST['level_id'];
									$price_id = @$_REQUEST['price_id'];
									$level = null;
									$price = null;
									$duration = null;
									$delay = null;
									$level_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_levels AS t1 LEFT JOIN {$wpdb->prefix}wp_membership_level_prices AS t2 ON t1.Level_ID=t2.Level_ID WHERE t1.Level_ID=%s AND t2.Level_Price_ID=%s", $level_id, $price_id);
									if($level_row = $wpdb->get_row($level_query, ARRAY_A)) {
										$level = $level_row['Name'];
										$price = $level_row['Price'];
										$duration = $level_row['Duration'];
										$delay = $level_row['Delay'];
									}
									$user_id = @$_REQUEST['user_id'];
									$user_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_users AS t1 WHERE t1.User_ID=%s", @$_REQUEST['user_id']);
									if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
										$user_id = $user_row['User_ID'];
									}
									$subscription = false;
									$name = "";
									switch($duration) {
										case "+1 month":
											$subscription = true;
											break;
										case "+1 year":
											$subscription = true;
											break;
									}
									$charged = false;
									$date = @strtotime(@$_REQUEST['payment_ccexp_year'].'-'.@$_REQUEST['payment_ccexp_month'].'-01');
									$gateway_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 LEFT JOIN ".$wpdb->prefix."wp_membership_register_page_gateways AS t2 ON t1.Register_Page_ID=t2.Register_Page_ID WHERE t1.Register_Page_ID=%s AND t2.Payment_Gateway=%s ORDER BY t2.Payment_Gateway", $register_row['Register_Page_ID'], @$_REQUEST['processor']);
									if($gateway_row = $wpdb->get_row($gateway_query, ARRAY_A)) {
										if(isset($this->plugins[$gateway_row['Payment_Gateway']])) {
											if($subscription) {
												if($this->plugins[$gateway_row['Payment_Gateway']]->has_Install_Subscription()) {
													$charged = $this->plugins[$gateway_row['Payment_Gateway']]->Install_Subscription($user_id."_".$price_id, $level." Membership at {$_SERVER['HTTP_HOST']}", $price, @$_REQUEST['payment_ccnum'], $date, @$_REQUEST['payment_cvv2'], @$_REQUEST['payment_name'], @$_REQUEST['billing_name'], @$_REQUEST['billing_address'], @$_REQUEST['billing_address2'], @$_REQUEST['billing_city'], @$_REQUEST['billing_state'], @$_REQUEST['billing_zip'], @$_REQUEST['billing_phone'], @$_REQUEST['billing_country'], $duration, $delay);
												}
											}
											else {
												if($this->plugins[$gateway_row['Payment_Gateway']]->has_Process_Charge()) {
													$charged = $this->plugins[$gateway_row['Payment_Gateway']]->Process_Charge($user_id."_".$price_id, $level." Membership at {$_SERVER['HTTP_HOST']}", $price, @$_REQUEST['payment_ccnum'], $date, @$_REQUEST['payment_cvv2'], @$_REQUEST['payment_name'], @$_REQUEST['billing_name'], @$_REQUEST['billing_address'], @$_REQUEST['billing_address2'], @$_REQUEST['billing_city'], @$_REQUEST['billing_state'], @$_REQUEST['billing_zip'], @$_REQUEST['billing_phone'], @$_REQUEST['billing_country']);
												}
											}
										}
									}
									if($charged) {
										$_REQUEST['do_register'] = -1;
									}
									else {
										$_REQUEST['do_register'] = 2;
										$retval = '<p class="errors">Failed to process card</p>'.$retval;
									}
								}
								break;
						}
						$retval = str_replace($register_row['Macro'], $this->default_registerform($register_row), $retval);
					}
				}
			}

			return $retval;
		}
		
		function default_userprofileform() {
			$retval = "";
			
			global $wpdb;
			
			$user_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_users AS t1 WHERE t1.User_ID=%s", @$_SESSION['wp-membership_plugin']['wp-membership_user_id']);
			if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
				if(@$_REQUEST['do_userprofile'] == "1") {
					if(strlen(trim(@$_REQUEST['password'])) > 0 || strlen(trim(@$_REQUEST['password2'])) > 0) {
						if(strlen(trim(@$_REQUEST['password'])) > 0) {
							if(trim(@$_REQUEST['password']) == trim(@$_REQUEST['password2'])) {
								$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_users SET Password=PASSWORD(%s) WHERE User_ID=%s", trim(@$_REQUEST['password']), $user_row['User_ID']);
								if($wpdb->query($update_query) !== false) {
							    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET WP_Password=%s WHERE User_ID=%s", wp_hash_password(trim(@$_REQUEST['password'])), $user_row['User_ID']);
						    		$wpdb->query($update_query);
									$retval .= "<p>Successfully Updated Password</p>";
								}
								else {
									$retval .= "<p>Failed to update password</p>";
								}
							}
							else {
								$retval .= "<p>Passwords must match</p>";
							}
						}
						else {
							$retval .= "<p>Password can not be blank</p>";
						}
					}
					$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_users SET Email=%s WHERE User_ID=%s", trim(@$_REQUEST['email']), $user_row['User_ID']);
					if($wpdb->query($update_query) !== false) {
						$retval .= "<p>Successfully Updated Profile</p>";
						$user_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_users AS t1 WHERE t1.User_ID=%s", @$_SESSION['wp-membership_plugin']['wp-membership_user_id']);
						$tmp = $user_row;
						if(!($user_row = $wpdb->get_row($user_query, ARRAY_A))) {
							$user_row = $tmp;
						}
					}
					else {
						$retval .= "<p>Failed to update profile</p>";
					}
				}
				else if(@$_REQUEST['do_unsubscribe'] == "1") {
					$userlevel_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_user_levels AS t1 WHERE t1.User_ID=%s AND t1.User_Level_ID=%s", @$_SESSION['wp-membership_plugin']['wp-membership_user_id'], $_REQUEST['userlevelid']);
					if($userlevel_row = $wpdb->get_row($userlevel_query, ARRAY_A)) {
						$plugin = null;
						if(is_array($this->plugins)) {
							foreach($this->plugins as $p) {
								if($p->has_Subscription(@$_REQUEST['userlevelid'])) {
									$plugin = $p;
								}
							}
							if(!is_null($plugin)) {
								if($plugin->Uninstall_Subscription($plugin->find_Subscription(@$_REQUEST['userlevelid'])) !== false) {
									$retval .= '<p>Successfully unsubscribed</p>';
								}
								else $retval .= '<p>Failed to unsubscribe</p>';
							}
							else $retval .= '<p>Failed to unsubscribe</p>';
						}
						else $retval .= '<p>Failed to unsubscribe</p>';
					}
					else $retval .= '<p>Failed to unsubscribe</p>';
				}
				$retval .= "<table border=\"0\">";
				$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode(@$_REQUEST['page_id'])."\" method=\"post\"> <input name=\"do_userprofile\" type=\"hidden\" value=\"1\" />";
				$retval .= "<tr>";
				$retval .= "<td>Email</td>";
				$retval .= "<td><input name=\"email\" type=\"text\" value=\"".htmlentities($user_row['Email'])."\" /></td>";
				$retval .= "</tr>";
				if(!is_null($user_row['Username']) && strlen(trim($user_row['Username'])) > 0) {
					$retval .= "<tr>";
					$retval .= "<td>Username</td>";
					$retval .= "<td>".htmlentities($user_row['Username'])."</td>";
					$retval .= "</tr>";
				}
				$retval .= "<tr>";
				$retval .= "<td>Password</td>";
				$retval .= "<td><input name=\"password\" type=\"password\" /></td>";
				$retval .= "</tr>";
				$retval .= "<tr>";
				$retval .= "<td>Confirm Password</td>";
				$retval .= "<td><input name=\"password2\" type=\"password\" /></td>";
				$retval .= "</tr>";
				$retval .= "<tr>";
				$retval .= "<td colspan=\"2\"><input type=\"submit\" value=\"Update\" /></td>";
				$retval .= "</tr>";
				$retval .= "</form>";
				$sub_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_user_levels AS t1, {$wpdb->prefix}wp_membership_levels AS t2 WHERE t1.Level_ID=t2.Level_ID AND t1.User_ID=%s", @$_SESSION['wp-membership_plugin']['wp-membership_user_id']);
				if($sub_rows = $wpdb->get_results($sub_query, ARRAY_A)) {
					$retval .= "<tr><td colspan=\"2\"><h3>Subscription".(count($sub_rows) == 1 ? "" : "s")."</h3></td></tr>";
					foreach($sub_rows as $id => $sub_row) {
						$retval .= "<tr>";
						$retval .= "<td>".htmlentities($sub_row['Name'])."</td>";
						$value = (is_null($sub_row['Expiration']) ? "Never Expires" : ((@strtotime($sub_row['Expiration']) - time()) < 0 ? (@strtotime($sub_row['Expiration']) == 0 ? "Pending" : "Expired") : "Expires ".@date("m-d-Y", @strtotime($sub_row['Expiration']))));
						$plugin = null;
						if(is_array($this->plugins)) {
							foreach($this->plugins as $p) {
								if($p->has_Subscription($sub_row['User_Level_ID'])) {
									$plugin = $p;
								}
							}
						}
						if(strlen($value) > 0 && !is_null($plugin)) $value .= "&nbsp;&nbsp;&nbsp;";
						if(!is_null($plugin)) {
							if($plugin->has_Unsubscribe_Button()) $value .= $plugin->get_Unsubscribe_Button($sub_row['User_ID'].'_'.$sub_row['Level_Price_ID'], $sub_row['Name'].' @ '.@$_SERVER['HTTP_HOST']);
							else if($plugin->has_Uninstall_Subscription()) {
								$value .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode(@$_REQUEST['page_id'])."\" method=\"post\">";
								$value .= '<input type="hidden" name="do_unsubscribe" value="1" />';
								$value .= '<input type="hidden" name="userlevelid" value="'.htmlentities($sub_row['User_Level_ID']).'" />';
								$value .= "<input type=\"submit\" value=\"Unsubscribe\" />";
								$value .= "</form>";
							}
						}
						$retval .= "<td>$value</td>";
						$retval .= "</tr>";
					}
				}
				$retval .= "</table>";
			}
			
			return $retval;
		}
		
		function default_loginform() {
			$retval = "";

			global $wpdb, $wp_query;
			
		    load_plugin_textdomain('wp-membership', false, $this->language_path);

		    $page_id = isset($wp_query->queried_object->ID) ? $wp_query->queried_object->ID : "";
			if(@$_REQUEST['forgot_password'] == "1") {
				$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\"> <input name=\"do_forgot_password\" type=\"hidden\" value=\"1\" />";
				$retval .= "<table border=\"0\">";
				$retval .= "<tbody>";
				$retval .= "<tr>";
				$retval .= "<td>".__('Email', 'wp-membership');
				$username_query = $wpdb->prepare("SELECT COUNT(*) AS Total FROM {$wpdb->prefix}wp_membership_users WHERE Username!=NULL");
				if($username_row = $wpdb->get_row($username_query, ARRAY_A)) {
					if($username_row['Total'] > 0) $retval .= " / ".__('Username', 'wp-membership');
				}
				$retval .= "</td>";
				$retval .= "<td><input style=\"background-color: #ffffa0;\" name=\"email\" type=\"text\" value=\"".htmlentities(@$_REQUEST['email'])."\" /></td>";
				$retval .= "</tr>";
				$retval .= "<tr>";
				$retval .= "<td colspan=\"2\"><input type=\"submit\" value=\"".__('Forgot Password', 'wp-membership')."\" /></td>";
				$retval .= "</tr>";
				$retval .= "</tbody></table>";
				$retval .= "</form>";
			}
			else {
				$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\"> <input name=\"do_login\" type=\"hidden\" value=\"1\" />";
				$retval .= "<table border=\"0\">";
				$retval .= "<tbody>";
				$retval .= "<tr>";
				$retval .= "<td>".__('Email', 'wp-membership');
				$username_query = $wpdb->prepare("SELECT COUNT(*) AS Total FROM {$wpdb->prefix}wp_membership_users WHERE Username!=NULL");
				if($username_row = $wpdb->get_row($username_query, ARRAY_A)) {
					if($username_row['Total'] > 0) $retval .= " / ".__('Username', 'wp-membership');
				}
				$retval .= "</td>";
				$retval .= "<td><input style=\"background-color: #ffffa0;\" name=\"email\" type=\"text\" /></td>";
				$retval .= "</tr>";
				$retval .= "<tr>";
				$retval .= "<td>".__('Password', 'wp-membership')."</td>";
				$retval .= "<td><input name=\"password\" type=\"password\" /></td>";
				$retval .= "</tr>";
				$retval .= "<tr>";
				$retval .= "<td colspan=\"2\"><input type=\"submit\" value=\"".__('Login', 'wp-membership')."\" /></td>";
				$retval .= "</tr>";
				$retval .= "</tbody></table>";
				$retval .= "</form>";
	    		if(get_option('wp-membership_login_prompt_forgot_password') == "1") {
	    			$retval .= "<div class=\"prompt_password\"><a href=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."&forgot_password=1&email=".urlencode(@$_REQUEST['email'])."\">Forgot Password?</a></div>";
	    		}
			}
			
			return $retval;
		}
		
		function default_registerform($register_row) {
			$retval = "";
			
			global $wpdb, $wp_query;
		    load_plugin_textdomain('wp-membership', false, $this->language_path);

			$page_id = isset($wp_query->queried_object->ID) ? $wp_query->queried_object->ID : @$_REQUEST['page_id'];
			if(is_array($register_row)) {
				$step = is_numeric(@$_REQUEST['do_register']) ? (int)@$_REQUEST['do_register'] : 0;
				switch($step) {
					case -1:
						$retval .= "<p>".__('Thank you for signing up', 'wp-membership')."</p>";
						break;
					case 0:
						$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\">";
						$retval .= "<input name=\"do_register\" type=\"hidden\" value=\"".($step + 1)."\" />";
						$retval .= "<input name=\"member_register_page_id\" type=\"hidden\" value=\"".urlencode($register_row['Register_Page_ID'])."\" />";
						$retval .= "<table class=\"wp_membership register step0\">";
						$retval .= "<tr>";
						$retval .= "<td>".__('Email', 'wp-membership')."</td>";
						$retval .= "<td><input name=\"email\" type=\"text\" value=\"".htmlentities(@$_REQUEST['email'])."\" /></td>";
						$retval .= "</tr>";
						$retval .= "<tr>";
						$retval .= "<td>".__('Password', 'wp-membership')."</td>";
						$retval .= "<td><input name=\"password\" type=\"password\" /></td>";
						$retval .= "</tr>";
						$retval .= "<tr>";
						$retval .= "<td>".__('Confirm Password', 'wp-membership')."</td>";
						$retval .= "<td><input name=\"password2\" type=\"password\" /></td>";
						$retval .= "</tr>";
						$retval .= "<tr>";
						$retval .= "<td colspan=\"2\"><input type=\"submit\" value=\"".__('Continue', 'wp-membership')."\" /></td>";
						$retval .= "</tr>";
						$retval .= "</table>";
						$retval .= "</form>";
						break;
					case 1:
						if(isset($this->public_messages['extra_fields_message'])) $retval .= $this->public_messages['extra_fields_message'];
						$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\">";
						$retval .= "<input name=\"do_register\" type=\"hidden\" value=\"".($step + 1)."\" />";
						$retval .= "<input name=\"member_register_page_id\" type=\"hidden\" value=\"".urlencode($register_row['Register_Page_ID'])."\" />";
						$retval .= "<table class=\"wp_membership register step1\">";
						foreach(array("email", "password", "password2") as $key) $retval .= "<input type=\"hidden\" name=\"$key\" value=\"".htmlentities(@$_REQUEST[$key])."\" />";
						$extra_fields = @unserialize($register_row['Extra_Fields']);
						if(!is_array($extra_fields)) $extra_fields = array();
						foreach($extra_fields as $extra_id => $extra_field) {
							$caption = isset($extra_field->caption) ? $extra_field->caption : "";
							$classes = isset($extra_field->classes) ? $extra_field->classes : "";
							$name = isset($extra_field->name) ? $extra_field->name : "";
							$default = isset($extra_field->default) ? $extra_field->default : "";
							$signup = isset($extra_field->signup) ? $extra_field->signup : false;
							$type = isset($extra_field->type) ? $extra_field->type : "";
							$parameters = array();
							$keys = array();
							$raw_parameters = explode(";", isset($extra_field->parameters) ? $extra_field->parameters : "");
							foreach($raw_parameters as $raw_parameter) {
								$values = explode("=", $raw_parameter);
								if(count($values) == 1) $parameters[] = $values[0];
								else if(count($values) == 2) {
									if(isset($parameters[$values[0]])) $parameters[$values[0].(++$keys[$values[0]])] = $values[1];
									else {
										$keys[$values[0]] = 1;
										$parameters[$values[0]] = $values[1];
									}
								}
							}
							if(!$signup) $type = 'hidden';
							switch($type) {
								case "hidden":
									$retval .= "<input type=\"".htmlentities($type)."\"".(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "")." name=\"wp-membership-extra_fields-".htmlentities($name)."\" value=\"".htmlentities($default)."\" />";
									break;
								case 'textarea':
									$retval .= "<tr valign=\"top\">";
									$retval .= "<td>".htmlentities($caption)."</td>";
									$retval .= "<td><textarea name=\"wp-membership-extra_fields-".htmlentities($name)."\"".(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "").(isset($parameters['rows']) ? " rows=\"".htmlentities($parameters['rows'])."\"" : "").(isset($parameters['cols']) ? " cols=\"".htmlentities($parameters['cols'])."\"" : "").">".htmlentities(isset($_REQUEST['wp-membership-extra_fields-'.$name]) ? $_REQUEST['wp-membership-extra_fields-'.$name] : $default)."</textarea></td>";
									$retval .= "</tr>";
									break;
								case 'select':
									$retval .= "<tr valign=\"top\">";
									$retval .= "<td>".htmlentities($caption)."</td>";
									$values = explode(";", $default);
									$retval .= "<td>";
									$defaults = explode(',', @$parameters['default']);
									$retval .= '<select name="wp-membership-extra_fields-'.htmlentities($name).'"'.(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "").(isset($parameters['multiple']) ? " MULTIPLE" : "").(strlen(@$parameters['size']) > 0 ? " size=\"".htmlentities($parameters['size'])."\"" : "").'>';
									foreach($values as $key => $content) {
										$data = explode('=', $content);
										$label = count($data) >= 2 ? $data[0] : null;
										$value = count($data) >= 2 ? $data[1] : $data[0];
										$retval .= "<option value=\"".htmlentities($value)."\"";
										if(isset($_REQUEST['do_register'])) $retval .= @$_REQUEST['wp-membership-extra_fields-'.$name] == $value ? ' SELECTED' : '';
										else $retval .= (in_array($key, $defaults) ? " SELECTED" : "");
										$retval .= ">";
										$retval .= htmlentities(is_null($label) ? $value : $label);
										$retval .= '</option>';
									}
									$retval .= "</td>";
									$retval .= "</tr>";
									break;
								case 'radio':
									$retval .= "<tr valign=\"top\">";
									$retval .= "<td>".htmlentities($caption)."</td>";
									$values = explode(";", $default);
									$retval .= "<td>";
									$defaults = explode(',', @$parameters['default']);
									foreach($values as $key => $content) {
										$data = explode('=', $content);
										$label = count($data) >= 2 ? $data[0] : null;
										$value = count($data) >= 2 ? $data[1] : $data[0];
										$retval .= "<input name=\"wp-membership-extra_fields-".htmlentities($name)."\"".(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "");
										if(isset($_REQUEST['do_register'])) $retval .= @$_REQUEST['wp-membership-extra_fields-'.$name] == $value ? ' CHECKED' : '';
										else $retval .= (in_array($key, $defaults) ? " CHECKED" : "");
										$retval .= " type=\"radio\" value=\"".htmlentities($value)."\" />";
										if(!is_null($label)) $retval .= ' '.htmlentities($label);
									}
									$retval .= "</td>";
									$retval .= "</tr>";
									break;
								case 'checkbox':
									$retval .= "<tr valign=\"top\">";
									$retval .= "<td>".htmlentities($caption)."</td>";
									$retval .= "<td>";
									$data = explode('=', $default);
									$label = count($data) >= 2 ? $data[0] : null;
									$value = count($data) >= 2 ? $data[1] : $data[0];
									$retval .= "<input name=\"wp-membership-extra_fields-".htmlentities($name)."\"".(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "");
									if(isset($_REQUEST['do_register'])) $retval .= @$_REQUEST['wp-membership-extra_fields-'.$name] == $value ? ' CHECKED' : '';
									else $retval .= (isset($parameters['checked']) && in_array($parameters['checked'], array(1, '1', 't', 'true', true)) ? " CHECKED" : "");
									$retval .= " type=\"checkbox\" value=\"".htmlentities($value)."\" />";
									if(!is_null($label)) $retval .= ' '.htmlentities($label);
									$retval .= "</td>";
									$retval .= "</tr>";
									break;
								case 'text':
								default:
									$retval .= "<tr valign=\"top\">";
									$retval .= "<td>".htmlentities($caption)."</td>";
									$retval .= "<td><input name=\"wp-membership-extra_fields-".htmlentities($name)."\"".(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "").(isset($parameters['size']) ? " size=\"".htmlentities($parameters['size'])."\"" : "").(isset($parameters['maxlength']) ? " maxlength=\"".htmlentities($parameters['maxlength'])."\"" : "")." type=\"text\" value=\"".htmlentities(isset($_REQUEST['wp-membership-extra_fields-'.$name]) ? $_REQUEST['wp-membership-extra_fields-'.$name] : $default)."\" /></td>";
									$retval .= "</tr>";
									break;
							}
						}
						$level_query = $wpdb->prepare("SELECT t1.Register_Page_ID, t3.Level_ID, t3.Name, t3.Description, t4.Level_Price_ID, t4.Price, t4.Duration, t4.Delay FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 LEFT JOIN ".$wpdb->prefix."wp_membership_register_page_levels AS t2 ON t1.Register_Page_ID=t2.Register_Page_ID LEFT JOIN ".$wpdb->prefix."wp_membership_levels AS t3 ON t2.Level_ID=t3.Level_ID LEFT JOIN ".$wpdb->prefix."wp_membership_level_prices AS t4 ON t3.Level_ID=t4.Level_ID WHERE t3.Level_ID IS NOT NULL AND t1.Register_Page_ID=%s ORDER BY t3.Name, t4.Price", $register_row['Register_Page_ID']);
						if($level_rows = $wpdb->get_results($level_query, ARRAY_A)) {
							$retval .= "<tr valign=\"top\">";
							$retval .= "<td>".__('Membership Level', 'wp-membership')."</td>";
							$retval .= "<td>";
							$first = true;
							$only_free = true;
							foreach($level_rows as $level_row) {
								if(!$first) $retval .= "<br />";
								$retval .= "<input type=\"radio\" name=\"wp-membership_level_id\" value=\"".htmlentities($level_row['Level_ID']);
								if($level_row['Level_Price_ID'] !== null) $retval .= "_".htmlentities($level_row['Level_Price_ID']);
								$retval .= "\"".($first ? " CHECKED" : "")." />";
								$retval .= " ".htmlentities($level_row['Name']);
								if($level_row['Level_Price_ID'] !== null) {
									$only_free = false;
									$retval .= " - ".trim($this->my_money_format('%(n', $level_row['Price']));
								    load_plugin_textdomain('wp-membership', false, 'wp-membership');
									switch($level_row['Duration']) {
										case "":
											$retval .= " ".__('one time charge', 'wp-membership');
											break;
										case "+1 week":
											$retval .= " ".__('per week', 'wp-membership');
											break;
										case "+1 month":
											$retval .= " ".__('per month', 'wp-membership');
											break;
										case "+1 year":
											$retval .= " ".__('per year', 'wp-membership');
											break;
									}
									switch($level_row['Delay']) {
										case "+3 days":
											$retval .= ", ".__('with a 3 day free trial', 'wp-membership');
											break;
										case "+1 week":
											$retval .= ", ".__('with a 1 week free trial', 'wp-membership');
											break;
										case "+1 month":
											$retval .= ", ".__('with a 1 month free trial', 'wp-membership');
											break;
										case "+1 year":
											$retval .= ", ".__('with a 1 year free trial', 'wp-membership');
											break;
									}
								}
								else $retval .= " - ".__('Free', 'wp-membership');
								if($first) $first = false;
							}
							$retval .= "</td>";
							$retval .= "</tr>";
						}
						$retval .= "<tr>";
						$retval .= "<td colspan=\"2\"><input type=\"submit\" value=\"".($only_free ? __('Register', 'wp-membership') : __('Continue', 'wp-membership'))."\" /></td>";
						$retval .= "</tr>";
						$retval .= "</table>";
						$retval .= "</form>";
						break;
					case 2:
						$retval .= __('Email', 'wp-membership').": ".htmlentities(@$_REQUEST['email'])."<br />";
						$level_id = null;
						$price_id = null;
						$levelprice = explode("_", @$_REQUEST['wp-membership_level_id']);
						if(count($levelprice) == 1) {
							$level_id = $levelprice[0];
						}
						else if(count($levelprice) == 2) {
							$level_id = $levelprice[0];
							$price_id = $levelprice[1];
						}
						$level = null;
						$price = null;
						$duration = null;
						$delay = null;
						$level_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_levels AS t1 LEFT JOIN {$wpdb->prefix}wp_membership_level_prices AS t2 ON t1.Level_ID=t2.Level_ID WHERE t1.Level_ID=%s AND t2.Level_Price_ID=%s", $level_id, $price_id);
						if($level_row = $wpdb->get_row($level_query, ARRAY_A)) {
							$level = $level_row['Name'];
							$price = $level_row['Price'];
							$duration = $level_row['Duration'];
							$delay = $level_row['Delay'];
						}
						$user_id = null;
						$user_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_users AS t1 WHERE t1.Email=%s", @$_REQUEST['email']);
						if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
							$user_id = $user_row['User_ID'];
						}
						$retval .= __('Level', 'wp-membership').": ".htmlentities($level." - ".trim($this->my_money_format('%(n', $price)));
					    load_plugin_textdomain('wp-membership', false, $this->language_path);
						$subscription = false;
						$name = "";
						switch($duration) {
							case "":
								$retval .= " ".__('one time charge', 'wp-membership');
								break;
							case "+1 week":
								$retval .= " ".__('per week', 'wp-membership');
								$subscription = true;
								break;
							case "+1 month":
								$retval .= " ".__('per month', 'wp-membership');
								$subscription = true;
								break;
							case "+1 year":
								$retval .= " ".__('per year', 'wp-membership');
								$subscription = true;
								break;
						}
						switch($delay) {
							case "+3 days":
								$retval .= ", ".__('with a 3 day free trial', 'wp-membership');
								break;
							case "+1 week":
								$retval .= ", ".__('with a 1 week free trial', 'wp-membership');
								break;
							case "+1 month":
								$retval .= ", ".__('with a 1 month free trial', 'wp-membership');
								break;
							case "+1 year":
								$retval .= ", ".__('with a 1 year free trial', 'wp-membership');
								break;
						}
						$gateway_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 LEFT JOIN ".$wpdb->prefix."wp_membership_register_page_gateways AS t2 ON t1.Register_Page_ID=t2.Register_Page_ID WHERE t1.Register_Page_ID=%s ORDER BY t2.Payment_Gateway", $register_row['Register_Page_ID']);
						if($gateway_rows = $wpdb->get_results($gateway_query, ARRAY_A)) {
							$buttons = false;
							foreach($gateway_rows as $gateway_row) {
								if(isset($this->plugins[$gateway_row['Payment_Gateway']])) {
									if($subscription) {
										if($this->plugins[$gateway_row['Payment_Gateway']]->has_Subscription_Button()) {
											$retval .= $this->plugins[$gateway_row['Payment_Gateway']]->get_Subscription_Button($user_id."_".$price_id, $level." ".__('Membership at', 'wp-membership')." {$_SERVER['HTTP_HOST']}", $price, $duration, $delay);
											$buttons = true;
										}
									}
									else {
										if($this->plugins[$gateway_row['Payment_Gateway']]->has_BuyNow_Button()) {
											$retval .= $this->plugins[$gateway_row['Payment_Gateway']]->get_BuyNow_Button($user_id."_".$price_id, $level." ".__('Membership at', 'wp-membership')." {$_SERVER['HTTP_HOST']}", $price);
											$buttons = true;
										}
									}
								}
							}
							$gateways = array();
							foreach($gateway_rows as $gateway_row) {
								if(isset($this->plugins[$gateway_row['Payment_Gateway']])) {
									if($subscription) {
										if($this->plugins[$gateway_row['Payment_Gateway']]->has_Install_Subscription()) {
											$gateways[$gateway_row['Payment_Gateway']] = $this->plugins[$gateway_row['Payment_Gateway']];
										}
									}
									else {
										if($this->plugins[$gateway_row['Payment_Gateway']]->has_Process_Charge()) {
											$gateways[$gateway_row['Payment_Gateway']] = $this->plugins[$gateway_row['Payment_Gateway']];
										}
									}
								}
							}
							if($buttons && count($gateways) > 0) $retval .= "<p>Or</p>";
							if(count($gateways) > 0) {
								$retval .= "<p class=\"required\">* ".__('indicates a required field', 'wp-membership')."</p>";
								$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\">";
								$retval .= "<input name=\"do_register\" type=\"hidden\" value=\"".($step + 1)."\" />";
								$retval .= "<input name=\"user_id\" type=\"hidden\" value=\"".($user_id)."\" />";
								$retval .= "<input name=\"price_id\" type=\"hidden\" value=\"".($price_id)."\" />";
								$retval .= "<input name=\"level_id\" type=\"hidden\" value=\"".($level_id)."\" />";
								foreach(array("email", "password", "password2", "wp-membership_level_id") as $key) $retval .= "<input type=\"hidden\" name=\"$key\" value=\"".htmlentities(@$_REQUEST[$key])."\" />";
								$retval .= "<table>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\" colspan=\"2\">".__('Billing Address', 'wp-membership')."</th>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Name', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_name\" value=\"".htmlentities(@$_REQUEST['billing_name'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Address', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_address\" value=\"".htmlentities(@$_REQUEST['billing_address'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\">".__('Address (Line 2)', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_address2\" value=\"".htmlentities(@$_REQUEST['billing_address2'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('City', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_city\" value=\"".htmlentities(@$_REQUEST['billing_city'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr align=\"left\">";
								$retval .= "<th><span class=\"required\">*</span>".__('State', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_state\" value=\"".htmlentities(@$_REQUEST['billing_state'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Zip / Postal Code', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_zip\" value=\"".htmlentities(@$_REQUEST['billing_zip'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Country', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_country\" value=\"".htmlentities(@$_REQUEST['billing_country'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\">".__('Phone', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_phone\" value=\"".htmlentities(@$_REQUEST['billing_phone'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\" colspan=\"2\">".__('Payment Information', 'wp-membership')."</th>";
								$retval .= "</tr>";
								if(count($gateways) > 1) {
									$retval .= "<tr valign=\"top\">";
									$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Payment Processor', 'wp-membership')."</th>";
									$retval .= "<td>";
									$processors = "";
									foreach($gateways as $key => $gateway) {
										if(strlen($processors) > 0) $processors .= "<br />";
										$processors .= "<input type=\"radio\" name=\"processor\" value=\"".htmlentities($key)."\" /> ".htmlentities($gateway->get_Name());
									}
									$retval .= "</td>";
									$retval .= "</tr>";
								}
								else $retval .= "<input type=\"hidden\" name=\"processor\" value=\"".htmlentities(@implode("", array_keys($gateways)))."\" />";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Name on the Card', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"payment_name\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Card Number', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"payment_ccnum\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Card Expiration', 'wp-membership')."</th>";
								$retval .= "<td>".__('Month', 'wp-membership').": <select name=\"payment_ccexp_month\"><option value=\"\">[Choose One]</option>";
								$start = strtotime("Jan");
								for($date = $start; $date < strtotime("+1 year", $start); $date = strtotime("+1 month", $date)) {
									$retval .= "<option value=\"".date("m", $date)."\">".date("n - F", $date)."</option>";
								}
								$retval .= "</select>";
								$retval .= " / ".__('Year', 'wp-membership').": <select name=\"payment_ccexp_year\"><option value=\"\">[Choose One]</option>";
								$start = strtotime("now");
								for($date = $start; $date < strtotime("+20 years", $start); $date = strtotime("+1 year", $date)) {
									$retval .= "<option value=\"".date("Y", $date)."\">".date("Y", $date)."</option>";
								}
								$retval .= "</select>";
								$retval .= "</td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Security Code', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"payment_cvv2\" size=\"3\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<td colspan=\"2\" align=\"right\"><input type=\"submit\" value=\"".__('Process', 'wp-membership')."\" /></td>";
								$retval .= "</tr>";
								$retval .= "</table>";
								$retval .= "</form>";
							}
						}
						break;
					case 3:
						break;
					default:
						break;
				}
			}
			
			return $retval;
		}
		
		function old_admin_menu() {
			if (current_user_can('edit_plugins')) {
			    add_options_page("WP-Membership Plugin", "WP-Membership Plugin", 8, dirname(__FILE__)."/old_admin_menu.php", array(&$this, "display_options"));
			}

		}
		
		function plugin_action_links($links, $file) {
			if($file == "wp-membership/wp-membership.php") {
				if(in_array('Top Level', get_option("wp-membership_admin_menu_location"))) $links[] = "<a href='admin.php?page=wp-membership/wp-membership.php'>" . __('Settings', 'wp-membership') . "</a>";
				else if(in_array('Settings', get_option("wp-membership_admin_menu_location"))) $links[] = "<a href='options-general.php?page=wp-membership/old_admin_menu.php'>" . __('Settings', 'wp-membership') . "</a>";
			}
			return $links;
		}
		
		function display_options_tab_0() {
			global $wpdb;
			
			$div_wrapper = false;
			if(!isset($query_string)) {
			    load_plugin_textdomain('wp-membership', false, 'wp-membership');
			    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
			    $div_wrapper = true;
			}
			if($div_wrapper) echo '<div class="wrap">';
			?>

			<h3>System Information</h3>
			
			<table class="form-table">
			
			<tr valign="top">
			<th scope="row">Free WP-Membership Version</th>
			<td>1.1.4</td>
			</tr>
			
			<tr valign="top">
			<th scope="row">Installed Add-ons</th>
			<td><?php
			$first = true;
			foreach($this->plugins as $plugin => $file) {
				if($first) $first = false;
				else echo '<br />';
				echo htmlentities($plugin);
			}
			?></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">WordPress Version</th>
			<td><?php
			if(isset($GLOBALS['wp_version'])) {
				echo htmlentities($GLOBALS['wp_version']);
				if(eregi("^([0-9a-z.]+)", $GLOBALS['wp_version'], $regs)) {
					if(version_compare($regs[1], '2.6.0', '<')) {
						echo "<br />Warning: WP-Membership Requires WordPress 2.6.0 or greater";
					}
				}
			}
			else {
				echo 'Failed to get WordPress Version (Possibly not 2.6, 2.7 or 2.8)';
			}
			?></td>
			</tr>

			<tr valign="top">
			<th scope="row">PHP Version</th>
			<td><?php echo htmlentities(PHP_VERSION); ?></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">MySQL Version</th>
			<td><?php
			$version_query = $wpdb->prepare("SELECT VERSION() AS Version");
			if($version_row = $wpdb->get_row($version_query, ARRAY_A)) {
				echo htmlentities($version_row['Version']);
				if(eregi("^([0-9a-z.]+)", $version_row['Version'], $regs)) {
					if(version_compare($regs[1], '5.0.0', '<')) {
						echo "<br />Warning: WP-Membership Requires MySQL 5.0.0 or greater";
					}
				}
			}
			else {
				echo 'Failed to get MySQL Version';
			}
			?></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">HTTPS Support</th>
			<td><?php
			$https_data = get_option("wp-membership_info_https");
			$https = null;
			if(is_array($https_data) && isset($https_data['last_check']) && isset($https_data['https']) && is_array($https_data['https']) && $https_data['last_check'] > strtotime("-1 hour")) {
				$https = $https_data['https'];
			}
			if(!is_array($https)) {
				$ch = curl_init(str_ireplace("http://", "https://", get_option('siteurl')));
				if($ch) {
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$buffer = curl_exec($ch);
					if($buffer !== false) {
						echo 'HTTPS detected';
						update_option("wp-membership_info_https", array('last_check' => time(), 'https' => array(true, 'HTTPS detected')));
					}
					else {
						switch(curl_errno($ch)) {
							case CURLE_SSL_CIPHER:
							case CURLE_SSL_CONNECT_ERROR:
							case CURLE_SSL_ENGINE_NOTFOUND:
							case CURLE_SSL_ENGINE_SETFAILED:
								echo 'Failed to detect HTTPS (may still be present, may be a proxy error)';
						update_option("wp-membership_info_https", array('last_check' => time(), 'https' => array(false, 'Failed to detect HTTPS (may still be present, may be a proxy error)')));
								break;
							case CURLE_SSL_CACERT:
							case CURLE_SSL_CERTPROBLEM:
							case CURLE_SSL_PEER_CERTIFICATE:
								echo 'HTTPS Detected (SSL certificate problem)';
						update_option("wp-membership_info_https", array('last_check' => time(), 'https' => array(false, 'HTTPS Detected (SSL certificate problem)')));
								break;
							default:
								echo 'Failed to detect HTTPS';
						update_option("wp-membership_info_https", array('last_check' => time(), 'https' => array(false, 'Failed to detect HTTPS')));
								break;
						}
					}
					curl_close($ch);
				}
				else {
					echo 'Failed to detect HTTPS (may still be present)';
					update_option("wp-membership_info_https", array('last_check' => 0, 'https' => array(false, 'HTTPS detected')));
				}
			}
			else {
				echo @$https[1];
			}
			?></td>
			</tr>
			
			</table>

			<?php
			if($div_wrapper) echo '</div>';
		}

		function display_options_tab_1() {
			$div_wrapper = false;
			if(!isset($query_string)) {
			    load_plugin_textdomain('wp-membership', false, 'wp-membership');
			    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
			    $div_wrapper = true;
			}
			if($div_wrapper) echo '<div class="wrap">';
			?>
			<h2>Free WP-Membership Plugin - General Options</h2>
			
			<?php
			if(@$_REQUEST['do_update'] == "1") {
				$update_success = true;
				
				$set_country = false;
				if($this->set_Country_Code(@$_REQUEST['wp-membership_country'])) {
					$info = @localeconv();
					if(strlen(trim(@$info['int_curr_symbol'])) > 0) {
						$set_country = true;
						update_option('wp-membership_country', @$_REQUEST['wp-membership_country']);
						update_option('wp-membership_currency', trim(@$info['int_curr_symbol']));
					}
				}
				if(!$set_country) {
					update_option('wp-membership_country', 'usa');
					update_option('wp-membership_currency', 'USD');
				}
				$options = array(	"wp-membership_access_denied_page_id",
									"wp-membership_logout_page_id",
									"wp-membership_loginfrom_macro",
									"wp-membership_login_page_id",
									"wp-membership_login_prompt_forgot_password",
									"wp-membership_logged_in_page_id",
									"wp-membership_user_profile_from_macro",
									"wp-membership_user_profile_page_id",
									"wp-membership_httpproxy_address",
									"wp-membership_httpproxy_port",
									"wp-membership_httpsproxy_address",
									"wp-membership_httpsproxy_port");
				$dh = @opendir(dirname(__FILE__).'/plugins');
				if($dh) {
					while(($file = readdir($dh)) !== false) {
						if(!ereg("[.]", $file)) {
							$option = "wp-membership_plugin_$file";
							if(isset($this->plugins[$file]) && is_a($this->plugins[$file], 'wp_membership_payment_gateway')) {
								if(!method_exists($this->plugins[$file], 'is_Currency_Supported') || !$this->plugins[$file]->is_Currency_Supported()) unset($_REQUEST[$option]);
							}
							else {
							}
							$options[] = $option;
						}
					}
					closedir($dh);
				}
				foreach($options as $option) {
					update_option($option, @$_REQUEST[$option]);
				}
				
				update_option("wp-membership_cache", @$_REQUEST['wp-membership_cache'] == "true" ? true : false);
				
				$admin_menu_location = array();
				switch(@$_REQUEST['wp-membership_admin_menu_location']) {
					case '1':
						$admin_menu_location[] = 'Top Level';
						break;
					case '2':
						$admin_menu_location[] = 'Settings';
						break;
					case '3':
						$admin_menu_location[] = 'Top Level';
						$admin_menu_location[] = 'Settings';
						break;
					default:
						break;
				}
				
				if(count($admin_menu_location) <= 0) {
					$admin_menu_location[] = 'Settings';
				}
				
				update_option("wp-membership_admin_menu_location", $admin_menu_location);
				
				if($update_success === true) {
					echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>Free WP-Membership General Settings Updated Successfully.</strong></p></div>";
				}
				else if($update_success === false) {
					echo "<div id=\"message\" class=\"error\"><p><strong>Free WP-Membership General Settings Failed to Delete.</strong></p></div>";
				}
			}
			?>
			
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo @$_REQUEST['page']; ?>">
			<?php
/*			if(function_exists('wpmu_create_blog')) {
				wp_nonce_field('magic_name-options');
			}
			else {
				wp_nonce_field('update-options');
			} */
			?>
			
			<h3>Standard Page Settings</h3>
			
			<table class="form-table">
			
			<tr valign="top">
			<th scope="row">Access Denied</th>
			<td><select name="wp-membership_access_denied_page_id"><?php
			$pages = get_pages();
			foreach($pages as $page) {
				echo "<option value=\"".htmlentities($page->ID)."\"";
				if(get_option('wp-membership_access_denied_page_id') == $page->ID) echo " SELECTED";
				echo ">".htmlentities($page->post_title)."</option>";
			}
			?></select></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">Logout Page</th>
			<td><select name="wp-membership_logout_page_id"><?php
			foreach($pages as $page) {
				echo "<option value=\"".htmlentities($page->ID)."\"";
				if(get_option('wp-membership_logout_page_id') == $page->ID) echo " SELECTED";
				echo ">".htmlentities($page->post_title)."</option>";
			}
			?></select></td>
			</tr>

			<tr valign="top">
			<th scope="row">Login Form Macro</th>
			<td><input type="text" name="wp-membership_loginfrom_macro" value="<?php echo get_option('wp-membership_loginfrom_macro'); ?>" /></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">Login Page</th>
			<td><select name="wp-membership_login_page_id"><?php
			foreach($pages as $page) {
				echo "<option value=\"".htmlentities($page->ID)."\"";
				if(get_option('wp-membership_login_page_id') == $page->ID) echo " SELECTED";
				echo ">".htmlentities($page->post_title)."</option>";
			}
			?></select></td>
			</tr>

			<tr valign="top">
			<th scope="row">Prompt for forgot password</th>
			<td>Yes <input type="radio" name="wp-membership_login_prompt_forgot_password" value="1"<?php echo get_option('wp-membership_login_prompt_forgot_password') == "1" ? " CHECKED" : ""; ?> /> No <input type="radio" name="wp-membership_login_prompt_forgot_password" value="0"<?php echo get_option('wp-membership_login_prompt_forgot_password') == "0" ? " CHECKED" : ""; ?> /></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">Logged In Page</th>
			<td><select name="wp-membership_logged_in_page_id"><?php
			foreach($pages as $page) {
				echo "<option value=\"".htmlentities($page->ID)."\"";
				if(get_option('wp-membership_logged_in_page_id') == $page->ID) echo " SELECTED";
				echo ">".htmlentities($page->post_title)."</option>";
			}
			?></select></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">User Profile Form Macro</th>
			<td><input type="text" name="wp-membership_user_profile_from_macro" value="<?php echo get_option('wp-membership_user_profile_from_macro'); ?>" /></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">User Profile Page</th>
			<td><select name="wp-membership_user_profile_page_id"><?php
			foreach($pages as $page) {
				echo "<option value=\"".htmlentities($page->ID)."\"";
				if(get_option('wp-membership_user_profile_page_id') == $page->ID) echo " SELECTED";
				echo ">".htmlentities($page->post_title)."</option>";
			}
			?></select></td>
			</tr>
			</table>
			
			<h3>Performance</h3>
			
			<table class="form-table">

			<tr valign="top">
			<th scope="row">Caching Membership Levels</th>
			<td>Enabled <input type="radio" name="wp-membership_cache" value="true"<?php echo get_option('wp-membership_cache') == true ? " CHECKED" : ""; ?> /> Disabled <input type="radio" name="wp-membership_cache" value="false"<?php echo get_option('wp-membership_cache') == false ? " CHECKED" : ""; ?> /></td>
			</tr>

			</table>
			
			<h3>Credit Merchant Account(s)</h3>
			
			<table class="form-table">
			
			<tr valign="top">
			<th scope="row">Payment Gateways</th>
			<td><?php
				$dh = @opendir(dirname(__FILE__).'/plugins');
				if($dh) {
					$first = true;
					while(($file = readdir($dh)) !== false) {
						if(!ereg("[.]", $file)) {
							$option = "wp-membership_plugin_$file";
							if($first) $first = false;
							else echo "<br />";
							?><input type="checkbox" name="<?php echo htmlentities($option); ?>" value="1"<?php if(get_option($option) == "1") echo " CHECKED"; ?>" /><?php echo " ".htmlentities($file);
							if(isset($this->plugins[$file]) && is_a($this->plugins[$file], 'wp_membership_payment_gateway')) {
								if(!method_exists($this->plugins[$file], 'is_Currency_Supported')) echo ' (Add-on appears to be corrupted, please contact support@wp-membership.com)';
								else if(!$this->plugins[$file]->is_Currency_Supported()) echo ' (Selected currency not supported)';
							}
							else {
							}
						}
					}
					closedir($dh);
				}
				?></td>
			</tr>
			
			<?php
			$countries = array(	'aus' => 'Australia',
								'aut' => 'Austria',
								'bel' => 'Belgium',
								'bra' => 'Brazil',
								'can' => 'Canada',
								'chn' => 'China',
								'cze' => 'Czech Republic',
								'dnk' => 'Denmark',
								'fin' => 'Finland',
								'fra' => 'France',
								'deu' => 'Germany',
								'grc' => 'Greece',
								'hkg' => 'Hong Kong SAR',
								'hun' => 'Hungary',
								'isl' => 'Iceland',
								'irl' => 'Ireland',
								'ita' => 'Italy',
								'jpn' => 'Japan',
								'kor' => 'Korea',
								'mex' => 'Mexico',
								'nld' => 'The Netherlands',
								'nzl' => 'New Zealand',
								'nor' => 'Norway',
								'pol' => 'Poland',
								'prt' => 'Portugal',
								'rus' => 'Russia',
								'sgp' => 'Singapore',
								'svk' => 'Slovakia',
								'esp' => 'Spain',
								'swe' => 'Sweden',
								'che' => 'Switzerland',
								'twn' => 'Taiwan',
								'tur' => 'Turkey',
								'gbr' => 'United Kingdom',
								'usa' => 'United States');
			$options = '';
			foreach($countries as $code => $country) {
				if($this->set_Country_Code($code)) {
					$info = localeconv();
					if(strlen(trim(@$info['int_curr_symbol'])) > 0) {
						$options .= '
					<option value="'.htmlentities($code).'"'.(get_option("wp-membership_country") == $code ? " SELECTED" : "").'>'.htmlentities($country).'</option>';
					}
				}
			}
			if(strlen(options) > 0) {
			?>
			<tr valign="top">
			<th scope="row">Currency Symbol</th>
			<td><?php
			$this->set_Country_Code(get_option("wp-membership_country"));
			$info = localeconv();
			echo htmlentities(trim(@$info['int_curr_symbol']).' ('.trim(@$info['currency_symbol']).')');
			?></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">Currency</th>
			<td>
				<select name="wp-membership_country">
				<?php echo $options; ?>
				</select>
			</td>
			</tr>
			<?php
			}
			?>
			</table>
			
			<h3>Admin Menu Settings</h3>
			<table class="form-table">
			
			<tr valign="top">
			<th scope="row">Menu Location</th>
				<td>
					Top Level <input type="radio" name="wp-membership_admin_menu_location" value="1"<?php echo in_array('Top Level', get_option('wp-membership_admin_menu_location')) ? " CHECKED" : ""; ?> />
					Settings <input type="radio" name="wp-membership_admin_menu_location" value="2"<?php echo in_array('Settings', get_option('wp-membership_admin_menu_location')) ? " CHECKED" : ""; ?> />
					Both <input type="radio" name="wp-membership_admin_menu_location" value="3"<?php echo in_array('Top Level', get_option('wp-membership_admin_menu_location')) && in_array('Settings', get_option('wp-membership_admin_menu_location')) ? " CHECKED" : ""; ?> />
				</td>
			</tr>

			</table>
			
			<input type="hidden" name="wp-membership_tab" value="1" />
			<input type="hidden" name="do_update" value="1" />
			
			<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Save Changes', 'wp-membership'); ?>" />
			</p>
			
			</form>
			<?php
			if($div_wrapper) echo '</div>';
		}
		
		private function set_Country_Code($code = null) {
			$retval = false;
			
			if(is_null($code) || is_string($code)) {
				$retval = @setlocale(LC_MONETARY, $this->get_Country_Codes(is_null($code) ? get_option("wp-membership_country") : $code));
			}
						
			return $retval;
		}
		
		private function get_Country_Codes($code) {
			$retval = array($code);
			$codes = array(	'aus' => array("aus", "australia", 'en_AU', 'en_AU.utf8', 'en_AU.UTF-8', 'en_AU.UTF8'),
							'aut' => array("aut", "austria"),
							'bel' => array("bel", "belgium"),
							'bra' => array("bra", "brazil"),
							'can' => array("can", "canada"),
							'chn' => array("chn", "china", "pr china", "pr-china"),
							'cze' => array("cze", "czech"),
							'dnk' => array("dnk", "denmark"),
							'fin' => array("fin", "finland"),
							'fra' => array("fra", "france", 'fr_FR', 'fr_FR.utf8', 'fr_FR.UTF8', 'fr.UTF8', 'fr_FR.UTF-8', 'fr.UTF-8'),
							'deu' => array("deu", "germany", 'de_DE', 'de_DE.utf8', 'de_DE.UTF8', 'de_DE.UTF-8'),
							'grc' => array("grc", "greece"),
							'hkg' => array("hkg", "hong kong", "hong-kong"),
							'hun' => array("hun", "hungary"),
							'isl' => array("isl", "iceland"),
							'irl' => array("irl", "ireland"),
							'ita' => array("ita", "italy"),
							'jpn' => array("jpn", "japan", 'jp_JP', 'jp_JP.utf8', 'jp_JP.UTF-8', 'jp_JP.UTF8'),
							'kor' => array("kor", "korea"),
							'mex' => array("mex", "mexico"),
							'nld' => array("nld", "holland", "netherlands"),
							'nzl' => array("nzl", "new zealand", "new-zealand", "nz"),
							'nor' => array("nor", "norway"),
							'pol' => array("pol", "poland"),
							'prt' => array("prt", "portugal"),
							'rus' => array("rus", "russia"),
							'sgp' => array("sgp", "singapore"),
							'svk' => array("svk", "slovak"),
							'esp' => array("esp", "spain"),
							'swe' => array("swe", "sweden"),
							'che' => array("che", "switzerland"),
							'twn' => array("twn", "taiwan"),
							'tur' => array("tur", "turkey"),
							'gbr' => array("gbr", "britain", "england", "great britain", "uk", "united kingdom", "united-kingdom"),
							'usa' => array('usa', 'en_US', 'en_US.utf8', 'en_US.UTF-8', 'en_US.UTF8', 'america', 'united states', 'united-states', 'us'));
			
			if(isset($codes[$code])) $retval = $codes[$code];
			
			return $retval;
		}
		
		function display_options_tab_2() {
			global $wpdb;
		    load_plugin_textdomain('wp-membership', false, $this->language_path);
		
			$div_wrapper = false;
			if(!isset($query_string)) {
			    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
			    $div_wrapper = true;
			}
			if($div_wrapper) echo '<div class="wrap">';
			?>
			<h2><?php _e('WP-Membership Plugin - Users', 'wp-membership'); ?></h2>
			<?php
			$switchvar = @$_REQUEST['wp-membership_action'];
			$edit_success = null;
			if(@$_REQUEST['wp-membership_do_reset_password'] == "1") {
				$user_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_users AS t1 WHERE t1.User_ID=%s", @$_REQUEST['wp-membership_userid']);
				if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
					$reset_success = false;
		    		$password = "";
		    		while(strlen($password) < 8) {
		    			$password .= chr(rand(ord("0"), ord("z")));
		    		}
			    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET Password=PASSWORD(%s) WHERE User_ID=%s", $password, $user_row['User_ID']);
			    	if($wpdb->query($update_query)) {
				    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET WP_Password=%s WHERE User_ID=%s", wp_hash_password($password), $user_row['User_ID']);
			    		$wpdb->query($update_query);
			    		wp_mail($user_row['Email'], "Password was reset by management", "Management has reset your password. Your new password is $password. This is case sensitive. You can login at: http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\n--\nManagement");
			    		$reset_success = true;
			    		$switchvar = "";
			    	}
				}
			}
			if(@$_REQUEST['wp-membership_do_edit_user'] == "1") {
				$edit_success = false;
				$edit_user_query = "";
				if(strlen(trim(@$_REQUEST['wp-membership_password'])) > 0) {
					if(@$_REQUEST['wp-membership_password'] == @$_REQUEST['wp-membership_password2']) {
						if(strlen(trim(@$_REQUEST['wp-membership_username'])) > 0) $edit_user_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET Email=%s, Username=%s, Password=PASSWORD(%s), Active=%s WHERE User_ID=%s", @$_REQUEST['wp-membership_email'], @$_REQUEST['wp-membership_username'], @$_REQUEST['wp-membership_password'], @$_REQUEST['wp-membership_active'] == "1" ? "1" : "0", @$_REQUEST['wp-membership_userid']);
						else $edit_user_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET Email=%s, Username=NULL, Password=PASSWORD(%s), Active=%s WHERE User_ID=%s", @$_REQUEST['wp-membership_email'], @$_REQUEST['wp-membership_password'], @$_REQUEST['wp-membership_active'] == "1" ? "1" : "0", @$_REQUEST['wp-membership_userid']);
				    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET WP_Password=%s WHERE User_ID=%s", wp_hash_password(@$_REQUEST['wp-membership_password']), @$_REQUEST['wp-membership_userid']);
			    		$wpdb->query($update_query);
					}
				}
				else {
					if(strlen(trim(@$_REQUEST['wp-membership_username'])) > 0) $edit_user_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET Email=%s, Username=%s, Active=%s WHERE User_ID=%s", @$_REQUEST['wp-membership_email'], @$_REQUEST['wp-membership_username'], @$_REQUEST['wp-membership_active'] == "1" ? "1" : "0", @$_REQUEST['wp-membership_userid']);
					else $edit_user_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET Email=%s, Username=NULL, Active=%s WHERE User_ID=%s", @$_REQUEST['wp-membership_email'], @$_REQUEST['wp-membership_active'] == "1" ? "1" : "0", @$_REQUEST['wp-membership_userid']);
				}
				if(strlen($edit_user_query) > 0 && $wpdb->query($edit_user_query) !== false) {
					$user_id = @$_REQUEST['wp-membership_userid'];
					$edit_success = true;
					$switchvar = "";
/*					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_user_levels WHERE User_ID=%s", $user_id));
					if(is_array(@$_REQUEST['wp-membership_level_ids'])) {
						foreach($_REQUEST['wp-membership_level_ids'] as $level_id) {
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_user_levels (User_ID, Level_ID) VALUES (%s, %s)", @$user_id, @$level_id));
						}
					} */
				}
			}
			$delete_success = null;
			if(@$_REQUEST['wp-membership_do_delete_user'] == "1") {
				$delete_success = false;
				if(is_array($this->plugins)) {
					foreach($this->plugins as $plugin) {
						$plugin->delete_User(@$_REQUEST['wp-membership_userid']);
					}
				}
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_user_levels WHERE User_ID=%s", @$_REQUEST['wp-membership_userid']));
				if($wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_users WHERE User_ID=%s", @$_REQUEST['wp-membership_userid'])) !== false) {
					$delete_success = true;
				}
			}
			switch($switchvar) {
				case "edit_user":
					$user_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_users AS t1 WHERE t1.User_ID=%s", @$_REQUEST['wp-membership_userid']);
					if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
						?>
						<h3>Edit User "<?php echo htmlentities($user_row['Email']); ?>"</h3>
						
						<?php
						if(@$_REQUEST['sync_password'] == "1") {
							$wp_user_id = email_exists($user_row['Email']);
							if($wp_user_id !== false) {
								if(@$user_row['WP_Password'] != null) {
							        $user = get_userdata($wp_user_id);
							        $user = add_magic_quotes(get_object_vars($user));
					                $user['user_pass'] = @$user_row['WP_Password'];
							        $user_id = wp_insert_user($user);
							        $current_user = wp_get_current_user();
							        if($current_user->id == $user_id) {
				                        wp_clear_auth_cookie();
				                        wp_set_auth_cookie($user_id);
							        }
									if($user_id !== false) {
										echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WordPress User Password Synced with WP-Membership User Password.</strong></p></div>";
									}
								}
							}
						}
						
						if($reset_success === false) {
							echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Failed to reset password.</strong></p></div>";
						}
						?>
						<?php
						if($edit_success === false) {
							echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership User Failed to Update.</strong></p></div>";
						}
						?>
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						
						<table class="form-table">
						
						<tr valign="top">
						<th scope="row">Email</th>
						<td><input type="text" name="wp-membership_email" value="<?php echo htmlentities($user_row['Email']); ?>" /></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Username</th>
						<td><input type="text" name="wp-membership_username" value="<?php echo htmlentities($user_row['Username']); ?>" /></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Change Password</th>
						<td><input type="password" name="wp-membership_password" /></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Confirm Password</th>
						<td><input type="password" name="wp-membership_password2" /></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Extra Fields</th>
						<td><table><?php
						$extra_fields = @unserialize($user_row['Extra_Fields']);
						if(!is_array($extra_fields)) $extra_fields = array();
						foreach($extra_fields as $extra_id => $data) {
//							$extra_field = $this->get_ExtraFieldFromUserData($data);
							$name = isset($data->name) ? $data->name : '';
//							$caption = isset($extra_field->caption) ? $data->caption : '';
							$value = isset($data->value) ? $data->value : '';
//							$admin = isset($extra_field->admin) ? $extra_field->admin : false;
//							$type = isset($extra_field->type) ? $extra_field->type : 'text';
							echo '<tr valign="top"><th>'.htmlentities($name)."</th><td>".htmlentities($value)."</td></tr>";
						}
						?></table></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Levels</th>
						<td><?php
						$user_levels = array();
						if($user_levels_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_user_levels AS t1 WHERE t1.User_ID=%s", $user_row['User_ID']), ARRAY_A)) {
							foreach($user_levels_rows as $user_levels_row) {
								$user_levels[] = $user_levels_row['Level_ID'];
							}
						}
						
						if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 ORDER BY t1.Name"), ARRAY_A)) {
							$first = true;
							foreach($level_rows as $level_row) {
								if($first) {
									$first = false;
								}
								else {
									echo "<br />";
								}
								echo "<input type=\"checkbox\" name=\"wp-membership_level_ids[]\" disabled value=\"".htmlentities($level_row['Level_ID'])."\"";
								if(in_array($level_row['Level_ID'], $user_levels)) echo " CHECKED";
								echo " />";
								echo " ".htmlentities($level_row['Name']);
							}
						}
						?></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Active</th>
						<td><input type="checkbox" name="wp-membership_active" value="1"<?php echo $user_row['Active'] == "1" ? " CHECKED" : ""; ?> /></td>
						</tr>
						
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="2" />
						<input type="hidden" name="wp-membership_userid" value="<?php echo htmlentities($user_row['User_ID']); ?>" />
						<input type="hidden" name="wp-membership_action" value="edit_user" />
						<input type="hidden" name="wp-membership_do_edit_user" value="1" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Update User', 'wp-membership'); ?>" />
						</p>
						
						</form>
						
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						<input type="hidden" name="wp-membership_tab" value="2" />
						<input type="hidden" name="wp-membership_userid" value="<?php echo htmlentities($user_row['User_ID']); ?>" />
						<input type="hidden" name="wp-membership_action" value="edit_user" />
						<input type="hidden" name="wp-membership_do_reset_password" value="1" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Reset Password', 'wp-membership'); ?>" />
						</p>
						
						</form>
						
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						<input type="hidden" name="wp-membership_tab" value="2" />
						<input type="hidden" name="wp-membership_userid" value="<?php echo htmlentities($user_row['User_ID']); ?>" />
						<input type="hidden" name="wp-membership_action" value="delete_user" />
						<input type="hidden" name="wp-membership_do_delete_user" value="1" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Delete User', 'wp-membership'); ?>" />
						</p>
						
						</form>

						<h3>WordPress Sync</h3>
						<table class="form-table">
						
						<tr valign="top">
						<th scope="row">Sync Password</th>
						<td><?php
						$wp_user_id = email_exists($user_row['Email']);
						if($wp_user_id !== false) {
							$user_data = get_userdata($wp_user_id);
							echo htmlentities($user_data->user_login." (".$user_data->user_email.") ");
							if(@$user_row['WP_Password'] != null) {
								?><a href="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>&wp-membership_tab=2&wp-membership_action=edit_user&wp-membership_userid=<?php echo urlencode($user_row['User_ID']); ?>&sync_password=1">Set WordPress User Password to WP-Membership Password</a><?php
							}
						}
						else {
							?>User not found in WordPress User Database<?php
						}
						?></td>
						</tr>
						
						</table>
						<?php
						break;
					}
					else {
						echo "<div id=\"message\" class=\"error\"><p><strong>Failed to load Specified WP-Membership User to Edit</strong></p></div>";
					}
				default:
					if($reset_success === true) {
						echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership User Password Reset to ".htmlentities($password).".</strong></p></div>";
					}
					if($edit_success === true) {
						echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership User Updated.</strong></p></div>";
					}
					if($delete_success === true) {
						echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership User Deleted.</strong></p></div>";
					}
					else if($delete_success === false) {
						echo "<div id=\"message\" class=\"error\"><p><strong>WP-Membership User Failed to Delete.</strong></p></div>";
					}
					if(@$_REQUEST['wp-membership_action'] == "add_user") {
						if(strlen(trim(@$_REQUEST['wp-membership_email'])) > 0) {
							if(is_email(@$_REQUEST['wp-membership_email'])) {
								if(strlen(trim(@$_REQUEST['wp-membership_password'])) > 0) {
									if(@$_REQUEST['wp-membership_password'] == @$_REQUEST['wp-membership_password2']) {
										$insert_query = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_users (Email, Username, Password) VALUES (%s, %s, PASSWORD(%s))", @$_REQUEST['wp-membership_email'], @$_REQUEST['wp-membership_username'], @$_REQUEST['wp-membership_password']);
										if($wpdb->query($insert_query) !== false) {
											$user_id = $wpdb->insert_id;
									    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET WP_Password=%s WHERE User_ID=%s", wp_hash_password(@$_REQUEST['wp-membership_password']), $user_id);
								    		$wpdb->query($update_query);
											$success = true;
											if(is_array(@$_REQUEST['wp-membership_level_ids'])) {
												foreach($_REQUEST['wp-membership_level_ids'] as $level_id) {
													if($wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_user_levels (User_ID, Level_ID) VALUES (%s, %s)", @$user_id, @$level_id)) === false) $success = false;
												}
											}
											if($success) {
												echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership User Added.</strong></p></div>";
											}
											else {
												echo "<div id=\"message\" class=\"error\"><p><strong>WP-Membership User Added, but not all of the levels where added</strong></p></div>";
											}
										}
									}
									else {
										echo "<div id=\"message\" class=\"error\"><p><strong>Passwords must match</strong></p></div>";
									}
								}
								else {
									echo "<div id=\"message\" class=\"error\"><p><strong>Password can not be empty</strong></p></div>";
								}
							}
							else {
								echo "<div id=\"message\" class=\"error\"><p><strong>Email must be valid</strong></p></div>";
							}
						}
						else {
							echo "<div id=\"message\" class=\"error\"><p><strong>Email can not be empty</strong></p></div>";
						}
					}

					$download = get_option('siteurl')."/wp-content/plugins/wp-membership/wp-membership.php?fetch_user_list=1";
					?>
					<h3>Export User List</h3>
					<form method="post" action="<?php echo $download; ?>">
					
					<p class="submit">
					<input type="submit" name="Submit" value="<?php _e('Download User List', 'wp-membership'); ?>" />
					</p>
					
					</form>
					
					<h3>Add User</h3>
					<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
					
					<table class="form-table">
					
					<tr valign="top">
					<th scope="row">Email</th>
					<td><input type="text" name="wp-membership_email" /></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Username</th>
					<td><input type="text" name="wp-membership_username" /></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Password</th>
					<td><input type="password" name="wp-membership_password" /></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Confirm Password</th>
					<td><input type="password" name="wp-membership_password2" /></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Levels</th>
					<td><?php
					if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 ORDER BY t1.Name"), ARRAY_A)) {
						$first = true;
						foreach($level_rows as $level_row) {
							if($first) {
								$first = false;
							}
							else {
								echo "<br />";
							}
							echo "<input type=\"checkbox\" name=\"wp-membership_level_ids[]\" value=\"".htmlentities($level_row['Level_ID'])."\" />";
							echo " ".htmlentities($level_row['Name']);
						}
					}
					?></td>
					</tr>

					</table>
					
					<input type="hidden" name="wp-membership_tab" value="2" />
					<input type="hidden" name="wp-membership_action" value="add_user" />
					
					<p class="submit">
					<input type="submit" name="Submit" value="<?php _e('Add User', 'wp-membership'); ?>" />
					</p>
					
					</form>
					<?php
					if($user_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_users AS t1 ORDER BY t1.Email"), ARRAY_A)) {
						?>
						<h3>View Users</h3>
						
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						
						<table class="form-table">
						
						<tr valign="top">
						<th scope="row">Users</th>
						<td><select name="wp-membership_userid"><?php
						foreach($user_rows as $user_row) {
							echo "<option value=\"".htmlentities($user_row['User_ID'])."\"";
							echo ">".htmlentities($user_row['Email'])."</option>";
						}
						?></select></td>
						</tr>
						
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="2" />
						<input type="hidden" name="wp-membership_action" value="edit_user" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Edit User', 'wp-membership'); ?>" />
						</p>
						
						</form>
						<?php
					}
					break;
			}
			if($div_wrapper) echo '</div>';
		}
		
		function display_options_tab_3() {
			global $wpdb;
		    load_plugin_textdomain('wp-membership', false, $this->language_path);
		
			$div_wrapper = false;
			if(!isset($query_string)) {
			    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
			    $div_wrapper = true;
			}
			if($div_wrapper) echo '<div class="wrap">';
			?>
			<h2><?php _e('WP-Membership Plugin - Levels', 'wp-membership'); ?></h2>
			<?php
			$switchvar = @$_REQUEST['wp-membership_action'];
			$edit_success = null;
			if(@$_REQUEST['wp-membership_do_edit_level'] == "1") {
				$edit_success = false;
				if($wpdb->query($wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_levels SET Name=%s, Description=%s WHERE Level_ID=%s", @$_REQUEST['wp-membership_name'], @$_REQUEST['wp-membership_description'], @$_REQUEST['wp-membership_level_id'])) !== false) {
					$level_id = @$_REQUEST['wp-membership_level_id'];
					$edit_success = true;
					$switchvar = "";
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_level_pages WHERE Level_ID=%s", @$_REQUEST['wp-membership_level_id']));
					if(is_array(@$_REQUEST['wp-membership_page_ids'])) {
						foreach($_REQUEST['wp-membership_page_ids'] as $page_id) {
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_level_pages (WP_Page_ID, Level_ID) VALUES (%s, %s)", @$page_id, $level_id));
						}
					}
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_level_posts WHERE Level_ID=%s", @$_REQUEST['wp-membership_level_id']));
					if(is_array(@$_REQUEST['wp-membership_post_ids'])) {
						foreach($_REQUEST['wp-membership_post_ids'] as $post_id) {
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_level_posts (WP_Post_ID, Level_ID) VALUES (%s, %s)", @$post_id, $level_id));
						}
					}
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_level_categories WHERE Level_ID=%s", @$_REQUEST['wp-membership_level_id']));
					if(is_array(@$_REQUEST['wp-membership_category_ids'])) {
						foreach($_REQUEST['wp-membership_category_ids'] as $category_id) {
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_level_categories (WP_Term_ID, Level_ID) VALUES (%s, %s)", @$category_id, $level_id));
						}
					}
					$delete_query = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_level_prices WHERE Level_ID=%s AND Level_Price_ID NOT IN ('".@implode("','", @$_REQUEST['wp-membership_price_ids'])."')", $level_id);
					$wpdb->query($delete_query);
					if(is_array(@$_REQUEST['wp-membership_price_ids'])) {
						foreach($_REQUEST['wp-membership_price_ids'] as $price_id) {
							$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_level_prices SET Price=%s, Duration=%s, Delay=%s WHERE Level_Price_ID=%s", @$_REQUEST['wp-membership_price_names'][$price_id], @$_REQUEST['wp-membership_price_durations'][$price_id], @$_REQUEST['wp-membership_price_delays'][$price_id], $price_id);
							$wpdb->query($update_query);
						}
					}
				}
			}
			$delete_success = null;
			if(@$_REQUEST['wp-membership_do_delete_level'] == "1") {
				$delete_success = false;
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_level_prices WHERE Level_ID=%s", @$_REQUEST['wp-membership_level_id']));
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_level_categories WHERE Level_ID=%s", @$_REQUEST['wp-membership_level_id']));
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_level_posts WHERE Level_ID=%s", @$_REQUEST['wp-membership_level_id']));
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_level_pages WHERE Level_ID=%s", @$_REQUEST['wp-membership_level_id']));
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_user_levels WHERE Level_ID=%s", @$_REQUEST['wp-membership_level_id']));
				if($wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_levels WHERE Level_ID=%s", @$_REQUEST['wp-membership_level_id'])) !== false) {
					$delete_success = true;
				}
			}
			switch($switchvar) {
				case "edit_level":
					if(@$_REQUEST['wp-membership_add_price'] == "1") {
						$price_query = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_level_prices (Level_ID) VALUES (%s)", @$_REQUEST['wp-membership_level_id']);
						if($wpdb->query($price_query)) {
						}
						else {
							echo "<div id=\"message\" class=\"error\"><p><strong>Failed to add Price to WP-Membership Level.</strong></p></div>";
						}
					}
					$level_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 WHERE t1.Level_ID=%s", @$_REQUEST['wp-membership_level_id']);
					if($level_row = $wpdb->get_row($level_query, ARRAY_A)) {
						?>
						<h3>Edit Level "<?php echo htmlentities($level_row['Name']); ?>"</h3>
						
						<?php
						if($edit_success === false) {
							echo "<div id=\"message\" class=\"error\"><p><strong>WP-Membership Level Failed to Update.</strong></p></div>";
						}
						?>
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						
						<table class="form-table">
						
						<tr valign="top">
						<th scope="row">Name</th>
						<td><input type="text" name="wp-membership_name" value="<?php echo htmlentities($level_row['Name']); ?>" /></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Description</th>
						<td><textarea name="wp-membership_description" rows="5" cols="80"><?php echo htmlentities($level_row['Description']); ?></textarea></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Pages</th>
						<td><?php
						$level_pages = array();
						if($level_pages_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_level_pages AS t1 WHERE t1.Level_ID=%s", $level_row['Level_ID']), ARRAY_A)) {
							foreach($level_pages_rows as $level_pages_row) {
								$level_pages[] = $level_pages_row['WP_Page_ID'];
							}
						}
						
						$pages = get_pages();
						$first = true;
						foreach($pages as $page) {
							if($first) {
								$first = false;
							}
							else {
								echo "<br />";
							}
							echo "<input type=\"checkbox\" name=\"wp-membership_page_ids[]\" value=\"".htmlentities($page->ID)."\"";
							if(in_array($page->ID, $level_pages)) echo " CHECKED";
							echo " />";
							echo " ".htmlentities($page->post_title);
						}
						?></td>
						<tr valign="top">
						<th scope="row">Posts</th>
						<td><?php
						$level_posts = array();
						if($level_posts_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_level_posts AS t1 WHERE t1.Level_ID=%s", $level_row['Level_ID']), ARRAY_A)) {
							foreach($level_posts_rows as $level_posts_row) {
								$level_posts[] = $level_posts_row['WP_Post_ID'];
							}
						}
						
						$posts = get_posts(array('numberposts' => -1));
						$first = true;
						foreach($posts as $post) {
							if($first) {
								$first = false;
							}
							else {
								echo "<br />";
							}
							echo "<input type=\"checkbox\" name=\"wp-membership_post_ids[]\" value=\"".htmlentities($post->ID)."\"";
							if(in_array($post->ID, $level_posts)) echo " CHECKED";
							echo " />";
							echo " ".htmlentities($post->post_title);
						}
						?></td>
						<tr valign="top">
						<th scope="row">Categories</th>
						<td><?php
						$level_categories = array();
						if($level_categories_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_level_categories AS t1 WHERE t1.Level_ID=%s", $level_row['Level_ID']), ARRAY_A)) {
							foreach($level_categories_rows as $level_categories_row) {
								$level_categories[] = $level_categories_row['WP_Term_ID'];
							}
						}
						
						$categories = get_all_category_ids();
						$first = true;
						foreach($categories as $categoryid) {
							$category = get_category($categoryid);
							if($first) {
								$first = false;
							}
							else {
								echo "<br />";
							}
							echo "<input type=\"checkbox\" name=\"wp-membership_category_ids[]\" value=\"".htmlentities($category->term_id)."\"";
							if(in_array($category->term_id, $level_categories)) echo " CHECKED";
							echo " />";
							echo " ".htmlentities($category->name);
						}
						?></td>
						<tr valign="top">
						<th scope="row">Prices</th>
						<td><?php
						$first = true;
						if($level_prices_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_level_prices AS t1 WHERE t1.Level_ID=%s", $level_row['Level_ID']), ARRAY_A)) {
							foreach($level_prices_rows as $level_prices_row) {
								if($first) {
									$first = false;
								}
								else {
									echo "<br />";
								}
								echo "<input type=\"checkbox\" name=\"wp-membership_price_ids[".htmlentities($level_prices_row['Level_Price_ID'])."]\" value=\"".htmlentities($level_prices_row['Level_Price_ID'])."\" CHECKED />";
								echo " Price: <input type=\"text\" name=\"wp-membership_price_names[".htmlentities($level_prices_row['Level_Price_ID'])."]\" value=\"".htmlentities($level_prices_row['Price'])."\" />";
								echo " Duration: <select name=\"wp-membership_price_durations[".htmlentities($level_prices_row['Level_Price_ID'])."]\">";
								echo "<option value=\"\"".($level_prices_row['Duration'] == "" ? " SELECTED" : "").">Permenent</option>";
								echo "<option value=\"+1 week\"".($level_prices_row['Duration'] == "+1 week" ? " SELECTED" : "").">Weekly</option>";
								echo "<option value=\"+1 month\"".($level_prices_row['Duration'] == "+1 month" ? " SELECTED" : "").">Monthly</option>";
								echo "<option value=\"+1 year\"".($level_prices_row['Duration'] == "+1 year" ? " SELECTED" : "").">Yearly</option>";
								echo "</select>";
								echo " Free Trial: <select name=\"wp-membership_price_delays[".htmlentities($level_prices_row['Level_Price_ID'])."]\">";
								echo "<option value=\"\"".($level_prices_row['Delay'] == "" ? " SELECTED" : "").">None</option>";
								echo "<option value=\"+3 days\"".($level_prices_row['Delay'] == "+3 days" ? " SELECTED" : "").">3 Days</option>";
								echo "<option value=\"+1 week\"".($level_prices_row['Delay'] == "+1 week" ? " SELECTED" : "").">1 Week</option>";
								echo "<option value=\"+1 month\"".($level_prices_row['Delay'] == "+1 month" ? " SELECTED" : "").">1 Month</option>";
								echo "<option value=\"+1 year\"".($level_prices_row['Delay'] == "+1 year" ? " SELECTED" : "").">1 Year</option>";
								echo "</select>";
							}
						}
						if(!$first) {
							echo "<br />";
						}
						echo "<a href=\"{$_SERVER['PHP_SELF']}?page=".urlencode(@$_REQUEST['page'])."&wp-membership_tab=".urlencode(@$_REQUEST['wp-membership_tab'])."&wp-membership_action=".urlencode(@$_REQUEST['wp-membership_action'])."&wp-membership_level_id=".urlencode($level_row['Level_ID'])."&wp-membership_add_price=1\">Add new Price</a>";
						?></td>
						</tr>
						
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="3" />
						<input type="hidden" name="wp-membership_level_id" value="<?php echo htmlentities($level_row['Level_ID']); ?>" />
						<input type="hidden" name="wp-membership_action" value="edit_level" />
						<input type="hidden" name="wp-membership_do_edit_level" value="1" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Update Level', 'wp-membership'); ?>" />
						</p>
						
						</form>
						
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						<input type="hidden" name="wp-membership_tab" value="3" />
						<input type="hidden" name="wp-membership_level_id" value="<?php echo htmlentities($level_row['Level_ID']); ?>" />
						<input type="hidden" name="wp-membership_action" value="delete_level" />
						<input type="hidden" name="wp-membership_do_delete_level" value="1" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Delete Level', 'wp-membership'); ?>" />
						</p>
						
						</form>
						<?php
						break;
					}
					else {
						echo "<div id=\"message\" class=\"error\"><p><strong>Failed to load Specified WP-Membership Level to Edit</strong></p></div>";
					}
				default:
					if($edit_success === true) {
						echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Level Updated.</strong></p></div>";
					}
					if($delete_success === true) {
						echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Level Deleted.</strong></p></div>";
					}
					else if($delete_success === false) {
						echo "<div id=\"message\" class=\"error\"><p><strong>WP-Membership Level Failed to Delete.</strong></p></div>";
					}
					if(@$_REQUEST['wp-membership_action'] == "add_level") {
						$insert_query = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_levels (Name, Description) VALUES (%s, %s)", @$_REQUEST['wp-membership_name'], @$_REQUEST['wp-membership_description']);
						if($wpdb->query($insert_query) !== false) {
							$level_id = $wpdb->insert_id;
							$success = true;
							if(is_array(@$_REQUEST['wp-membership_page_ids'])) {
								foreach($_REQUEST['wp-membership_page_ids'] as $page_id) {
									if($wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_level_pages (WP_Page_ID, Level_ID) VALUES (%s, %s)", @$page_id, $level_id)) === false) $success = false;
								}
							}
							if(is_array(@$_REQUEST['wp-membership_post_ids'])) {
								foreach($_REQUEST['wp-membership_post_ids'] as $post_id) {
									if($wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_level_posts (WP_Post_ID, Level_ID) VALUES (%s, %s)", @$post_id, $level_id)) === false) $success = false;
								}
							}
							if(is_array(@$_REQUEST['wp-membership_category_ids'])) {
								foreach($_REQUEST['wp-membership_category_ids'] as $category_id) {
									if($wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_level_categories (WP_Term_ID, Level_ID) VALUES (%s, %s)", @$category_id, $level_id)) === false) $success = false;
								}
							}
							if($success) {
								echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Level Added.</strong></p></div>";
							}
							else {
								echo "<div id=\"message\" class=\"error\"><p><strong>WP-Membership Level Added, but not all of the pages where added</strong></p></div>";
							}
						}
					}
					?>
					<h3>Add Level</h3>
					
					<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
					
					<table class="form-table">
					
					<tr valign="top">
					<th scope="row">Name</th>
					<td><input type="text" name="wp-membership_name" /></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Description</th>
					<td><textarea name="wp-membership_description" rows="5" cols="80"></textarea></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Pages</th>
					<td><?php
					$pages = get_pages();
					$first = true;
					foreach($pages as $page) {
						if($first) {
							$first = false;
						}
						else {
							echo "<br />";
						}
						echo "<input type=\"checkbox\" name=\"wp-membership_page_ids[]\" value=\"".htmlentities($page->ID)."\" />";
						echo " ".htmlentities($page->post_title);
					}
					?></td>
					</tr>
					<tr valign="top">
					<th scope="row">Posts</th>
					<td><?php
					$posts = get_posts(array('numberposts' => -1));
					$first = true;
					foreach($posts as $post) {
						if($first) {
							$first = false;
						}
						else {
							echo "<br />";
						}
						echo "<input type=\"checkbox\" name=\"wp-membership_post_ids[]\" value=\"".htmlentities($post->ID)."\" />";
						echo " ".htmlentities($post->post_title);
					}
					?></td>
					</tr>
					<tr valign="top">
					<th scope="row">Categories</th>
					<td><?php
					$categories = get_all_category_ids();
					$first = true;
					foreach($categories as $categoryid) {
						$category = get_category($categoryid);
						if($first) {
							$first = false;
						}
						else {
							echo "<br />";
						}
						echo "<input type=\"checkbox\" name=\"wp-membership_category_ids[]\" value=\"".htmlentities($category->term_id)."\" />";
						echo " ".htmlentities($category->name);
					}
					?></td>
					</tr>
					</table>
					
					<input type="hidden" name="wp-membership_tab" value="3" />
					<input type="hidden" name="wp-membership_action" value="add_level" />
					
					<p class="submit">
					<input type="submit" name="Submit" value="<?php _e('Add Level', 'wp-membership'); ?>" />
					</p>
					
					</form>
					<?php
					if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 ORDER BY t1.Name"), ARRAY_A)) {
						?>
						<h3>View Levels</h3>
						
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						<?php wp_nonce_field('update-options'); ?>
						
						<table class="form-table">
						
						<tr valign="top">
						<th scope="row">Levels</th>
						<td><select name="wp-membership_level_id"><?php
						foreach($level_rows as $level_row) {
							echo "<option value=\"".htmlentities($level_row['Level_ID'])."\"";
							echo ">".htmlentities($level_row['Name'])."</option>";
						}
						?></select></td>
						</tr>
						
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="3" />
						<input type="hidden" name="wp-membership_action" value="edit_level" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Edit Level', 'wp-membership'); ?>" />
						</p>
						
						</form>
						<?php
					}
					break;
			}
			if($div_wrapper) echo '</div>';
		}
		
		function display_options_tab_4() {
			global $wpdb;
		    load_plugin_textdomain('wp-membership', false, $this->language_path);

		    $div_wrapper = false;
			if(!isset($query_string)) {
			    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
			    $div_wrapper = true;
			}
			if($div_wrapper) echo '<div class="wrap">';
			?>
			<h2><?php _e('WP-Membership - Register Pages', 'wp-membership'); ?></h2>
			<?php
		
			$switchvar = @$_REQUEST['wp-membership_action'];
			$edit_success = null;
			if(strlen(@$_REQUEST['wp-membership_remove_extra_field']) > 0) {
				$register_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 WHERE t1.Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']);
				if($register_row = $wpdb->get_row($register_query, ARRAY_A)) {
					$extra_fields = @unserialize($register_row['Extra_Fields']);
					if(!is_array($extra_fields)) $extra_fields = array();
					unset($extra_fields[@$_REQUEST['wp-membership_remove_extra_field']]);
					$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_register_pages SET Extra_Fields=%s WHERE Register_Page_ID=%s", serialize($extra_fields), $register_row['Register_Page_ID']);
					$wpdb->query($update_query);
				}
			}
			if(@$_REQUEST['wp-membership_add_extra_field'] == "1") {
				$register_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 WHERE t1.Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']);
				if($register_row = $wpdb->get_row($register_query, ARRAY_A)) {
					$extra_fields = @unserialize($register_row['Extra_Fields']);
					if(!is_array($extra_fields)) $extra_fields = array();
					$extra_field->version = "0.0.2";
					$extra_field->name = "";
					$extra_field->classes = "";
					$extra_field->default = "";
					$extra_field->caption = "";
					$extra_field->type = "text";
					$extra_field->parameters = "";
					$extra_field->required = false;
					$extra_field->required_regex = ".+";
					$extra_field->required_error = "<%caption%> can not be blank";
					$extra_field->save = true;
					$extra_field->signup = true;
					$extra_field->profile = true;
					$extra_field->admin = true;
					$extra_fields[] = $extra_field;
					$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_register_pages SET Extra_Fields=%s WHERE Register_Page_ID=%s", serialize($extra_fields), $register_row['Register_Page_ID']);
					$wpdb->query($update_query);
				}
			}
			if(@$_REQUEST['wp-membership_do_edit_register'] == "1") {
				$edit_success = false;
				$edit_register_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_register_pages SET Name=%s, Description=%s, Macro=%s, WP_Page_ID=%s WHERE Register_Page_ID=%s", @$_REQUEST['wp-membership_name'], @$_REQUEST['wp-membership_description'], @$_REQUEST['wp-membership_macro'], @$_REQUEST['wp-membership_page_id'], @$_REQUEST['wp-membership_register_page_id']);
				if($wpdb->query($edit_register_query) !== false) {
					$register_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 WHERE t1.Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']);
					if($register_row = $wpdb->get_row($register_query, ARRAY_A)) {
						$extra_fields = @unserialize($register_row['Extra_Fields']);
						if(!is_array($extra_fields)) $extra_fields = array();
						$tmp = $extra_fields;
						foreach($tmp as $extra_id => $extra_field) {
							$extra_fields[$extra_id]->name = @$_REQUEST['wp-membership_extra_names'][$extra_id];
							$extra_fields[$extra_id]->classes = @$_REQUEST['wp-membership_extra_classes'][$extra_id];
							$extra_fields[$extra_id]->default = @$_REQUEST['wp-membership_extra_defaults'][$extra_id];
							$extra_fields[$extra_id]->caption = @$_REQUEST['wp-membership_extra_captions'][$extra_id];
							$extra_fields[$extra_id]->type = @$_REQUEST['wp-membership_extra_types'][$extra_id];
							$extra_fields[$extra_id]->parameters = @$_REQUEST['wp-membership_extra_parameters'][$extra_id];
							$extra_fields[$extra_id]->required = @$_REQUEST['wp-membership_extra_requireds'][$extra_id] == "1" ? true : false;
							$extra_fields[$extra_id]->required_regex = @$_REQUEST['wp-membership_extra_required_regexs'][$extra_id];
							$extra_fields[$extra_id]->required_error = @$_REQUEST['wp-membership_extra_required_errors'][$extra_id];
							$extra_fields[$extra_id]->save = @$_REQUEST['wp-membership_extra_saves'][$extra_id] == "1" ? true : false;
							$extra_fields[$extra_id]->signup = @$_REQUEST['wp-membership_extra_signups'][$extra_id] == "1" ? true : false;
							$extra_fields[$extra_id]->profile = @$_REQUEST['wp-membership_extra_profiles'][$extra_id] == "1" ? true : false;
							$extra_fields[$extra_id]->admin = @$_REQUEST['wp-membership_extra_admins'][$extra_id] == "1" ? true : false;
						}
						$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_register_pages SET Extra_Fields=%s WHERE Register_Page_ID=%s", serialize($extra_fields), $register_row['Register_Page_ID']);
						$wpdb->query($update_query);
					}
					$register_id = @$_REQUEST['wp-membership_register_page_id'];
					$edit_success = true;
					$switchvar = "";
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_register_page_gateways WHERE Register_Page_ID=%s", $register_id));
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_register_page_levels WHERE Register_Page_ID=%s", $register_id));
					if(is_array(@$_REQUEST['wp-membership_level_ids'])) {
						foreach($_REQUEST['wp-membership_level_ids'] as $level_id) {
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_register_page_levels (Register_Page_ID, Level_ID) VALUES (%s, %s)", @$register_id, $level_id));
						}
					}
					if(is_array(@$_REQUEST['wp-membership_plugins'])) {
						foreach($_REQUEST['wp-membership_plugins'] as $plugin_id) {
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_register_page_gateways (Register_Page_ID, Payment_Gateway) VALUES (%s, %s)", @$register_id, $plugin_id));
						}
					}
				}
			}
			$delete_success = null;
			if(@$_REQUEST['wp-membership_do_delete_register'] == "1") {
				$delete_success = false;
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_register_page_gateways WHERE Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']));
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_register_page_levels WHERE Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']));
				if($wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_register_pages WHERE Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id'])) !== false) {
					$delete_success = true;
				}
			}
			switch($switchvar) {
				case "edit_register":
					$register_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 WHERE t1.Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']);
					if($register_row = $wpdb->get_row($register_query, ARRAY_A)) {
						?>
						<h3>Edit Register Page "<?php echo htmlentities($register_row['Name']); ?>"</h3>
						
						<?php
						if($edit_success === false) {
							echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Register Page Failed to Update.</strong></p></div>";
						}
						?>
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						
						<table class="form-table">
						
						<tr valign="top">
						<th scope="row">Name</th>
						<td><input type="text" name="wp-membership_name" value="<?php echo htmlentities($register_row['Name']); ?>" /></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Description</th>
						<td><textarea name="wp-membership_description" rows="5" cols="80"><?php echo htmlentities($register_row['Description']); ?></textarea></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Default Page Macro</th>
						<td><input type="text" name="wp-membership_macro" value="<?php echo htmlentities($register_row['Macro']); ?>" /></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Page</th>
						<td><select name="wp-membership_page_id"><option value="">[Choose a Page]</option><?php
						$pages = get_pages();
						foreach($pages as $page) {
							echo "<option value=\"".htmlentities($page->ID)."\"";
							if($register_row['WP_Page_ID'] == $page->ID) echo " SELECTED";
							echo ">".htmlentities($page->post_title)."</option>";
						}
						?></select></td>
						</tr>
						
						<style type="text/css">
							#RegisterExtraFormFields {
								position: absolute;
								display: none;
								border: 1px solid #000000;
								width: 600px;
								height: 331px;
								overflow: hidden;
								background-color: #FFFFFF;
							}

							#RegisterExtraFormFields a#close {
								color: #000000;
								text-decoration: none;
								position: absolute;
								top: 0px;
								right: 0px;
								padding-top: 0px;
								padding-right: 3px;
							}

							#RegisterExtraFormFields #RegisterExtraFormFields_Window {
								position: absolute;
								width: 580px;
								height: 291px;
								overflow: auto;
								top: 20px;
								right: 0px;
								padding: 10px 10px 10px 10px;
							}
							
							#RegisterExtraFormFields #RegisterExtraFormFields_Window h1 {
								padding-bottom: 3px;
								border-bottom: 1px solid #000000;
							}
							
							#RegisterExtraFormFields #RegisterExtraFormFields_Window h3 {
								color: #FFFFFF;
								background-color: #000000;
							}
							
							#RegisterExtraFormFields #RegisterExtraFormFields_Window table table th:first-child {
								width: 0px;
							}
						</style>
						
						<tr valign="top">
						<th scope="row"><div id="RegisterExtraFormFields">
							<a id="close" href="#" onclick="javascript:document.getElementById('RegisterExtraFormFields').style.display='none';return false;">X</a>
							<div id="RegisterExtraFormFields_Window">
								<h1>Extra Form Fields - Quick Guide</h1>
								<p>This is a quick reference for creating custom form fields.</p>
								<h2>Field Types</h2>
								<h3>Checkbox</h3>
								<p>This is the best for any choice you offer the user for options like "I agree"</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>You can specify any CSS classes you'd like to have applied to this field (mostly for advanced users).</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is a value if marked.</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>This is the caption or label that is shown to the user on the left of the field.</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>
											This is a ';' separated list of name/value pairs. (Name=value;Name2=value2;Name3=value3)
											<table>
												<tr valign="top">
													<th width="0px">checked</th>
													<td>Specify true if you wish it to be checked by default or false if you wish it to not be checked by default.</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>Specifies if this field is required.</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>This is ignored for this field type</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>This specifies the error message used when the field is not marked. (&lt;%caption%&gt; is automatically replaced with the given label)</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
								<h3>Drop Down (Select)</h3>
								<p>This is the best for any choice you offer the user where they can only choose one answer and you have many posible answers. Like (State: Al, AK, AZ, ... WA, WV, WI, WY)</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>You can specify any CSS classes you'd like to have applied to this field (mostly for advanced users).</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is a ';' separated list of values. To label a value (to be displayed instead of the value) specify it as a name/value pair like (Male=1;Female=0).</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>This is the caption or label that is shown to the user on the left of the field.</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>
											This is a ';' separated list of name/value pairs. (Name=value;Name2=value2;Name3=value3)
											<table>
												<tr valign="top">
													<th width="0px">default</th>
													<td>This specifies which option is the default. The value is the numeric 0-based index of the option you wish to use. For example to mark "Male" default in the following list (Male=1;Female=0), you would use "default=0"</td>
												</tr>
											<!--	<tr valign="top">
													<th width="0px">multiple</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_select.asp" target="_blank">multiple</a> attribute</td>
												</tr>
												<tr valign="top">
													<th width="0px">size</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_select.asp" target="_blank">size</a> attribute</td>
												</tr> -->
											</table>
										</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>Specifies if this field is required.</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>This is ignored for this field type</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>This specifies the error message used when the field is not marked. (&lt;%caption%&gt; is automatically replaced with the given label)</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
								<h3>Hidden</h3>
								<p>This allows you to specify hidden form values. This is useful if you need to send specific values for other plug-ins.</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is the value of the field.</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
								<h3>Radio</h3>
								<p>This is the best for any choice you offer the user where they can only choose one answer. Like (Gender: Male   Female)</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>You can specify any CSS classes you'd like to have applied to this field (mostly for advanced users).</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is a ';' separated list of values. To label a value (on the right of the radio) specify it as a name/value pair like (Male=1;Female=0).</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>This is the caption or label that is shown to the user on the left of the field.</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>
											This is a ';' separated list of name/value pairs. (Name=value;Name2=value2;Name3=value3)
											<table>
												<tr valign="top">
													<th width="0px">default</th>
													<td>This specifies which option is the default. The value is the numeric 0-based index of the option you wish to use. For example to mark "Male" default in the following list (Male=1;Female=0), you would use "default=0"</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>Specifies if this field is required.</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>This is ignored for this field type</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>This specifies the error message used when the field is not marked. (&lt;%caption%&gt; is automatically replaced with the given label)</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
								<h3>Text</h3>
								<p>This is the most common field type, and the easiest to understand.</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>You can specify any CSS classes you'd like to have applied to this field (mostly for advanced users).</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is the default value. This is used on the sign-up form and is the value that is already in the field when it is first shown to the user.</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>This is the caption or label that is shown to the user on the left of the field.</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>
											This is a ';' separated list of name/value pairs. (Name=value;Name2=value2;Name3=value3)
											<table>
												<tr valign="top">
													<th width="0px">size</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_input.asp" target="_blank">size</a> attribute</td>
												</tr>
												<tr valign="top">
													<th>maxlength</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_input.asp" target="_blank">maxlength</a> attribute</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>Specifies if this field is required.</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>This specifies the rule used to enforce the required field. (The default value of '.+' denotes a general requirement of content.)</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>This specifies the error message used when the Regular Expression fails. (&lt;%caption%&gt; is automatically replaced with the given label)</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
								<h3>Text Area</h3>
								<p>This is the similar to text only it can have multiple lines of content.</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>You can specify any CSS classes you'd like to have applied to this field (mostly for advanced users).</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is the default value. This is used on the sign-up form and is the value that is already in the field when it is first shown to the user.</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>This is the caption or label that is shown to the user on the left of the field.</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>
											This is a ';' separated list of name/value pairs. (Name=value;Name2=value2;Name3=value3)
											<table>
												<tr valign="top">
													<th width="0px">rows</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_textarea.asp" target="_blank">rows</a> attribute</td>
												</tr>
												<tr valign="top">
													<th>cols</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_textarea.asp" target="_blank">cols</a> attribute</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>Specifies if this field is required.</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>This specifies the rule used to enforce the required field. (The default value of '.+' denotes a general requirement of content.)</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>This specifies the error message used when the Regular Expression fails. (&lt;%caption%&gt; is automatically replaced with the given label)</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
							</div>
						</div>Extra Form Fields <span class="tooltip">(<a href="#" title="Quick Guide" onclick="javascript:document.getElementById('RegisterExtraFormFields').style.display='block';return false;">?</a>)</span></th>
						<td><?php
						$first = true;
						$extra_fields = @unserialize($register_row['Extra_Fields']);
						if(!is_array($extra_fields)) $extra_fields = array();
						foreach($extra_fields as $extra_id => $extra_field) {
							if($first) {
								$first = false;
							}
							else {
								echo "<hr />";
							}
							echo "<strong>Extra Field</strong><br />";
							echo "<a href=\"{$_SERVER['PHP_SELF']}?page=".urlencode(@$_REQUEST['page'])."&wp-membership_tab=".urlencode(@$_REQUEST['wp-membership_tab'])."&wp-membership_action=".urlencode(@$_REQUEST['wp-membership_action'])."&wp-membership_register_page_id=".urlencode($register_row['Register_Page_ID'])."&wp-membership_remove_extra_field=".urlencode($extra_id)."\">Remove Extra Field</a>";
							if(isset($extra_field->name)) echo "<br />Name: <input type=\"text\" name=\"wp-membership_extra_names[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->name)."\" />";
							if(isset($extra_field->classes)) echo "<br />Classes: <input type=\"text\" name=\"wp-membership_extra_classes[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->classes)."\" />";
							if(isset($extra_field->default)) echo "<br />Default: <input type=\"text\" name=\"wp-membership_extra_defaults[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->default)."\" />";
							if(isset($extra_field->caption)) echo "<br />Caption: <input type=\"text\" name=\"wp-membership_extra_captions[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->caption)."\" />";
							if(isset($extra_field->type)) {
								$types = array('text' => 'Text', 'textarea' => 'Text Area', 'radio' => 'Radio', 'checkbox' => 'Checkbox', 'hidden' => 'Hidden', 'select' => 'Drop Down (Select)');
								asort($types);
								echo "<br />Type: <select name=\"wp-membership_extra_types[".htmlentities($extra_id)."]\">";
								foreach($types as $type => $caption) echo "<option value=\"$type\"".($extra_field->type == "$type" ? " SELECTED" : "").">$caption</option>";
								echo "</select>";
							}
							if(isset($extra_field->parameters)) echo "<br />Parameters: <input type=\"text\" name=\"wp-membership_extra_parameters[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->parameters)."\" />";
							if(isset($extra_field->required)) echo "<br />Required: <input type=\"checkbox\" name=\"wp-membership_extra_requireds[".htmlentities($extra_id)."]\" value=\"1\"".($extra_field->required ? " CHECKED" : "")." />";
							if(isset($extra_field->required_regex)) echo "<br />Regex: <input type=\"text\" name=\"wp-membership_extra_required_regexs[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->required_regex)."\" />";
							if(isset($extra_field->required_error)) echo "<br />Regex: <input type=\"text\" name=\"wp-membership_extra_required_errors[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->required_error)."\" />";
							if(isset($extra_field->save)) echo "<br />Save: <input type=\"checkbox\" name=\"wp-membership_extra_saves[".htmlentities($extra_id)."]\" value=\"1\"".($extra_field->save ? " CHECKED" : "")." />";
							if(isset($extra_field->signup)) echo "<br />Signup: <input type=\"checkbox\" name=\"wp-membership_extra_signups[".htmlentities($extra_id)."]\" value=\"1\"".($extra_field->signup ? " CHECKED" : "")." />";
							if(isset($extra_field->profile)) echo "<br />Profile: <input type=\"checkbox\" name=\"wp-membership_extra_profiles[".htmlentities($extra_id)."]\" value=\"1\"".($extra_field->profile ? " CHECKED" : "")." />";
							if(isset($extra_field->admin)) echo "<br />Admin: <input type=\"checkbox\" name=\"wp-membership_extra_admins[".htmlentities($extra_id)."]\" value=\"1\"".($extra_field->admin ? " CHECKED" : "")." />";
						}
						if(!$first) {
							echo "<hr />";
						}
						echo "<a href=\"{$_SERVER['PHP_SELF']}?page=".urlencode(@$_REQUEST['page'])."&wp-membership_tab=".urlencode(@$_REQUEST['wp-membership_tab'])."&wp-membership_action=".urlencode(@$_REQUEST['wp-membership_action'])."&wp-membership_register_page_id=".urlencode($register_row['Register_Page_ID'])."&wp-membership_add_extra_field=1\">Add new Extra Field</a>";
						?></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Levels</th>
						<td><?php
						$register_levels = array();
						if($register_levels_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_page_levels AS t1 WHERE t1.Register_Page_ID=%s", $register_row['Register_Page_ID']), ARRAY_A)) {
							foreach($register_levels_rows as $register_levels_row) {
								$register_levels[] = $register_levels_row['Level_ID'];
							}
						}
						
						if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 ORDER BY t1.Name"), ARRAY_A)) {
							$first = true;
							foreach($level_rows as $level_row) {
								if($first) {
									$first = false;
								}
								else {
									echo "<br />";
								}
								echo "<input type=\"checkbox\" name=\"wp-membership_level_ids[]\" value=\"".htmlentities($level_row['Level_ID'])."\"";
								if(in_array($level_row['Level_ID'], $register_levels)) echo " CHECKED";
								echo " />";
								echo " ".htmlentities($level_row['Name']);
							}
						}
						?></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Payment Gateways</th>
						<td><?php
						$register_gateways = array();
						if($register_gateways_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_page_gateways AS t1 WHERE t1.Register_Page_ID=%s", $register_row['Register_Page_ID']), ARRAY_A)) {
							foreach($register_gateways_rows as $register_gateways_row) {
								$register_gateways[] = $register_gateways_row['Payment_Gateway'];
							}
						}
						
						$first = true;
						foreach($this->plugins as $name => $plugin) {
							if($first) {
								$first = false;
							}
							else {
								echo "<br />";
							}
							echo "<input type=\"checkbox\" name=\"wp-membership_plugins[".htmlentities($name)."]\" value=\"".htmlentities($name)."\"";
							if(in_array($name, $register_gateways)) echo " CHECKED";
							echo " />";
							echo " ".htmlentities($name);
						}
						?></td>
						</tr>
	
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="4" />
						<input type="hidden" name="wp-membership_register_page_id" value="<?php echo htmlentities($register_row['Register_Page_ID']); ?>" />
						<input type="hidden" name="wp-membership_action" value="edit_register" />
						<input type="hidden" name="wp-membership_do_edit_register" value="1" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Update Register Page', 'wp-membership'); ?>" />
						</p>
						
						</form>
						
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						<input type="hidden" name="wp-membership_tab" value="4" />
						<input type="hidden" name="wp-membership_register_page_id" value="<?php echo htmlentities($register_row['Register_Page_ID']); ?>" />
						<input type="hidden" name="wp-membership_action" value="delete_register" />
						<input type="hidden" name="wp-membership_do_delete_register" value="1" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Delete Register Page', 'wp-membership'); ?>" />
						</p>
						
						</form>
						<?php
						break;
					}
					else {
						echo "<div id=\"message\" class=\"error\"><p><strong>Failed to load Specified WP-Membership Register Page to Edit</strong></p></div>";
					}
				default:
					if($edit_success === true) {
						echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Register Page Updated.</strong></p></div>";
					}
					if($delete_success === true) {
						echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Register Page Deleted.</strong></p></div>";
					}
					else if($delete_success === false) {
						echo "<div id=\"message\" class=\"error\"><p><strong>WP-Membership Register Page Failed to Delete.</strong></p></div>";
					}
					if(@$_REQUEST['wp-membership_action'] == "add_register") {
						$insert_query = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_register_pages (Name, Description, Macro, WP_Page_ID) VALUES (%s, %s, %s, %s)", @$_REQUEST['wp-membership_name'], @$_REQUEST['wp-membership_description'], @$_REQUEST['wp-membership_macro'], @$_REQUEST['wp-membership_page_id']);
						if($wpdb->query($insert_query) !== false) {
							$register_id = $wpdb->insert_id;
							$success = true;
							if(is_array(@$_REQUEST['wp-membership_level_ids'])) {
								foreach($_REQUEST['wp-membership_level_ids'] as $level_id) {
									if($wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_register_page_levels (Register_Page_ID, Level_ID) VALUES (%s, %s)", @$register_id, $level_id)) === false) $success = false;
								}
							}
							if(is_array(@$_REQUEST['wp-membership_plugins'])) {
								foreach($_REQUEST['wp-membership_plugins'] as $plugin_id) {
									if($wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_register_page_gateways (Register_Page_ID, Payment_Gateway) VALUES (%s, %s)", @$register_id, $plugin_id)) === false) $success = false;
								}
							}
							if($success) {
								echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Level Added.</strong></p></div>";
							}
							else {
								echo "<div id=\"message\" class=\"error\"><p><strong>WP-Membership Level Added, but not all of the pages where added</strong></p></div>";
							}
						}
					}
					?>
					<h3>Add Register Page</h3>
					
					<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
					
					<table class="form-table">
					
					<tr valign="top">
					<th scope="row">Name</th>
					<td><input type="text" name="wp-membership_name" /></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Description</th>
					<td><textarea name="wp-membership_description" rows="5" cols="80"></textarea></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Default Page Macro</th>
					<td><input type="text" name="wp-membership_macro" value="[Register Page]" /></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Page</th>
					<td><select name="wp-membership_page_id"><option value="">[Choose a Page]</option><?php
					$pages = get_pages();
					foreach($pages as $page) {
						echo "<option value=\"".htmlentities($page->ID)."\"";
						echo ">".htmlentities($page->post_title)."</option>";
					}
					?></select></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Levels</th>
					<td><?php
					if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 ORDER BY t1.Name"), ARRAY_A)) {
						$first = true;
						foreach($level_rows as $level_row) {
							if($first) {
								$first = false;
							}
							else {
								echo "<br />";
							}
							echo "<input type=\"checkbox\" name=\"wp-membership_level_ids[".htmlentities($level_row['Level_ID'])."]\" value=\"".htmlentities($level_row['Level_ID'])."\" />";
							echo " ".htmlentities($level_row['Name']);
						}
					}
					?></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Payment Gateways</th>
					<td><?php
					$first = true;
					foreach($this->plugins as $name => $plugin) {
						if($first) {
							$first = false;
						}
						else {
							echo "<br />";
						}
						echo "<input type=\"checkbox\" name=\"wp-membership_plugins[".htmlentities($name)."]\" value=\"".htmlentities($name)."\" />";
						echo " ".htmlentities($name);
					}
					?></td>
					</tr>
					
					</table>
					
					<input type="hidden" name="wp-membership_tab" value="4" />
					<input type="hidden" name="wp-membership_action" value="add_register" />
					
					<p class="submit">
					<input type="submit" name="Submit" value="<?php _e('Add Register Page', 'wp-membership'); ?>" />
					</p>
					
					</form>
					<?php
					if($register_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 ORDER BY t1.Name"), ARRAY_A)) {
						?>
						<h3>View Register Pages</h3>
						
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						<?php wp_nonce_field('update-options'); ?>
						
						<table class="form-table">
						
						<tr valign="top">
						<th scope="row">Register Pages</th>
						<td><select name="wp-membership_register_page_id"><?php
						foreach($register_rows as $register_row) {
							echo "<option value=\"".htmlentities($register_row['Register_Page_ID'])."\"";
							echo ">".htmlentities($register_row['Name'])."</option>";
						}
						?></select></td>
						</tr>
						
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="4" />
						<input type="hidden" name="wp-membership_action" value="edit_register" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Edit Register Page', 'wp-membership'); ?>" />
						</p>
						
						</form>
						<?php
					}
					break;
			}
			if($div_wrapper) echo '</div>';
		}
		
		function display_options_tab_5() {
			global $wpdb;
		    load_plugin_textdomain('wp-membership', false, $this->language_path);
		
			$div_wrapper = false;
			if(!isset($query_string)) {
			    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
			    $div_wrapper = true;
			}
			if($div_wrapper) echo '<div class="wrap">';
			?>
			<h2><?php _e('WP-Membership - Payment Gateways', 'wp-membership'); ?></h2>
			<?php
			$switchvar = @$_REQUEST['wp-membership_action'];
			$edit_success = null;
			if(@$_REQUEST['wp-membership_do_edit_gateway'] == "1") {
				$edit_success = false;
				if(isset($this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']])) {
					$edit_success = $this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']]->do_SettingsEdit();
					//if($edit_success) $switchvar = "";
				}
			}
			switch($switchvar) {
				case "edit_gateway":
					if(isset($this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']])) {
						?>
						<h3>Edit Register Page "<?php echo htmlentities($this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']]->get_Name()); ?>"</h3>
						
						<?php
						if($edit_success === false) {
							echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Payment Gateway Failed to Update.</strong></p></div>";
						}
						?>
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						
						<table class="form-table">
						<?php
						$this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']]->show_SettingsEdit();
						?>
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="5" />
						<input type="hidden" name="wp-membership_payment_gateway_id" value="<?php echo htmlentities(@$_REQUEST['wp-membership_payment_gateway_id']); ?>" />
						<input type="hidden" name="wp-membership_action" value="edit_gateway" />
						<input type="hidden" name="wp-membership_do_edit_gateway" value="1" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Update Payment Gateway', 'wp-membership'); ?>" />
						</p>
						
						</form>
						<?php
//						echo $this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']]->get_Subscription_Button("123test", "This is a test Caption", 12.95, "1 month");
//						echo $this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']]->get_Subscription_Button("123test", "This is a test Caption", 12.95, "1 month", "3 days");
						break;
					}
					else {
						echo "<div id=\"message\" class=\"error\"><p><strong>Failed to load Specified WP-Membership Payment Gateway to Edit</strong></p></div>";
					}
				default:
					if(is_array($this->plugins) && count($this->plugins) > 0) {
						?>
						<h3>View Payment Gateways</h3>
						<?php
						if($edit_success === true) {
							echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Payment Gateway Updated.</strong></p></div>";
						}
						?>
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						
						<table class="form-table">
						
						<tr valign="top">
						<th scope="row">Payment Gateways</th>
						<td><select name="wp-membership_payment_gateway_id"><?php
						foreach($this->plugins as $name => $plugin) {
							echo "<option value=\"".htmlentities($name)."\"";
							echo ">".htmlentities($name)."</option>";
						}
						?></select></td>
						</tr>
						
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="5" />
						<input type="hidden" name="wp-membership_action" value="edit_gateway" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Edit Payment Gateway', 'wp-membership'); ?>" />
						</p>
						
						</form>
						<?php
					}
					else {
						echo "<div id=\"message\" class=\"error\"><p><strong>There are currently no Payment Gateways to configure.</strong></p></div>";
					}
					break;
			}
			if($div_wrapper) echo '</div>';
		}
		
		function display_options_tab_6() {
			$div_wrapper = false;
			if(!isset($query_string)) {
			    load_plugin_textdomain('wp-membership', false, 'wp-membership');
			    echo 'Error: Feedback support is currently broken';
			    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
			    $div_wrapper = true;
			}
			if($div_wrapper) echo '<div class="wrap">';
			?>
			<h2>WP-Membership - Feedback</h2>
			<h3>Send Feedback</h3>
			<?php
			?>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Company</th>
					<td><input type="text" name="wp-membership_company" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Name</th>
					<td><input type="text" name="wp-membership_Name" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Email</th>
					<td><input type="text" name="wp-membership_Email" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">*Feedback</th>
					<td><textarea name="wp-membership_Feedback" rows="5" cols="60"></textarea></td>
				</tr>
			</table>
			
			<input type="hidden" name="wp-membership_tab" value="6" />
			<input type="hidden" name="wp-membership_action" value="send_feedback" />
			
			<p class="submit">
			<input type="submit" name="Submit" value="<?php _e('Send Feedback', 'wp-membership'); ?>" />
			</p>
			</form>
			<?php
			if($div_wrapper) echo '</div>';
		}
		
		function display_options_tab_7() {
			$div_wrapper = false;
			if(!isset($query_string)) {
			    load_plugin_textdomain('wp-membership', false, 'wp-membership');
			    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
			    $div_wrapper = true;
			}
			if($div_wrapper) echo '<div class="wrap">';
			?>
			<h2>WP-Membership - Troubleshooting</h2>
			<?php
			?>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?".$query_string; ?>">
			
			<h3>Database</h3>
			
			<table class="form-table">
			
			<tr valign="top">
			<th scope="row">Recreate</th>
			<td><a href="<?php echo $_SERVER['PHP_SELF'].'?'.$query_string.'&wp-membership_recreatedb=1'; ?>">Recreate the database</a> (does not delete existing data)</td>
			</tr>
			
			</table>
					
			<input type="hidden" name="wp-membership_tab" value="7" />
			<input type="hidden" name="wp-membership_action" value="update_troubleshooting" />
			
			<p class="submit">
<!--			<input type="submit" name="Submit" value="<?php _e('Update Troubleshooting Options', 'wp-membership'); ?>" /> -->
			</p>
			</form>
			<?php
			if($div_wrapper) echo '</div>';
		}
		
		function display_options() {
		    load_plugin_textdomain('wp-membership', false, 'wp-membership');
		    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
		    ?>
		    <style type="text/css">
		    <!--
			    div.wrap>div#wp_membership_tabs>ul#wp_membership_options_menu {
			    	padding-left: 0px;
			    }
	
			    div.wrap>div#wp_membership_tabs>ul#wp_membership_options_menu li {
			    	padding-left: 10px;
			    	list-style-type: none;
			    	display: inline;
			    }
	
			    div.wrap>div#wp_membership_tabs>ul#wp_membership_options_menu li a {
			    	text-decoration: none;
			    }

			    div.wrap>div#wp_membership_tabs>ul#wp_membership_options_menu li a.current {
			    	color: #d54e21;
			    }
		    -->
		    </style>
		    <script language="javascript" type="text/javascript">
		    <!--
		    	function set_wp_membership_options_tab(index) {
		    		return true;
		    		<?php
		    		$tabs = array(	0 => array('Caption' => __('News &amp; Info', 'wp-membership'), 'Is_Virtual' => false),
		    						1 => array('Caption' => __('General Settings', 'wp-membership'), 'Is_Virtual' => false),
		    						2 => array('Caption' => __('Users', 'wp-membership'), 'Is_Virtual' => true),
		    						3 => array('Caption' => __('Levels', 'wp-membership'), 'Is_Virtual' => true),
		    						4 => array('Caption' => __('Register Pages', 'wp-membership'), 'Is_Virtual' => true),
		    						5 => array('Caption' => __('Payment Gateways', 'wp-membership'), 'Is_Virtual' => true),
		    						6 => array('Caption' => __('Feedback', 'wp-membership'), 'Is_Virtual' => false),
		    						7 => array('Caption' => __('Troubleshooting', 'wp-membership'), 'Is_Virtual' => false));
		    		
		    		$num_tabs = count($tabs);
		    		?>
		    		<?php for($i = 0; $i <= $num_tabs; $i++) echo "var tab$i = document.getElementById('wp_membership_tab_$i');\n"; ?>
		    		
		    		<?php for($i = 0; $i <= $num_tabs; $i++) echo "var link$i = document.getElementById('wp_membership_tab_link_$i');\n"; ?>
		    		
		    		switch(index) {
		    			<?php
		    			for($i = 0; $i <= $num_tabs; $i++) {
		    				echo "case $i:\n";
		    				for($j = 0; $j <= $num_tabs; $j++) {
		    					echo "tab$j.style.display='none';\n";
		    					echo "link$j.className='';\n";
		    				}
	    					echo "tab$i.style.display='block';\n";
	    					echo "link$i.className='current';\n";
		    				echo "break;\n";
		    			}
		    			?>
		    		}

		    		return false;
		    	}
		    -->
		    </script>
			<div class="wrap">
				<div id="wp_membership_tabs">
					<ul id="wp_membership_options_menu">
						<?php
						$first = true;
						foreach($tabs as $id => $tab) {
								?><li><a id="wp_membership_tab_link_<?php echo $id; ?>" href="<?php echo $_SERVER['PHP_SELF']."?".$query_string.(strlen($query_string) > 0 ? "&" : "")."wp-membership_tab=$id"; ?>" onclick="javascript:return set_wp_membership_options_tab(<?php echo $id; ?>);"<?php if(($first && trim(@$_REQUEST['wp-membership_tab']) == "") || @$_REQUEST['wp-membership_tab'] == $id) echo " class=\"current\""; ?>><?php echo $tab['Caption']; ?></a></li><?php
							if($first) $first = false;
						}
						?>
					</ul>
				</div>
				<?php
				$first = true;
				foreach($tabs as $id => $tab) {
						?><div id="wp_membership_tab_<?php echo $id; ?>"<?php if((!$first || trim(@$_REQUEST['wp-membership_tab']) != "") && @$_REQUEST['wp-membership_tab'] != $id) {echo " style=\"display: none;\"";} ?>>
				<?php eval('$this->display_options_tab_'.$id.'();'); ?>
				</div><?php
					if($first) $first = false;
				}
				?>
			</div>
		    <?php
		}
		
		function do_Payment_Gateway_Postback() {
			if(isset($this->plugins[@$_REQUEST['gateway_callback']])) {
				if(is_a($this->plugins[@$_REQUEST['gateway_callback']], 'wp_membership_payment_gateway')) {
					if(method_exists($this->plugins[@$_REQUEST['gateway_callback']], 'callback_PostBack')) {
						$this->plugins[@$_REQUEST['gateway_callback']]->callback_PostBack(&$this);
					}
				}
			}
		}
		
		private function list_system_locales() {
			$retval = array();
			
			if(function_exists('ob_start') && function_exists('system') && function_exists('ob_get_contents') && function_exists('ob_end_clean') && function_exists('split') && function_exists('is_array')) {
				@ob_start();
				@system('locale -a'); 
				$str = @ob_get_contents();
				@ob_end_clean();
				$retval = @split("\n", trim($str));
				if(!is_array($retval)) $retval = array();
			}
			
			return $retval;
		}
		
		private function my_money_format($format, $value) {
			$retval = '';
			
			if($this->set_Country_Code()) {
				$retval = money_format($format, $value);
			}
			else {
				$retval = '$'.money_format($format, $value);
			}
			
			return $retval;
		}
	}
}
else if(version_compare(PHP_VERSION, $wp_membership_min_php_version, '<')) {
	add_action('admin_notices', 'wp_membership_admin_notices');
	function wp_membership_admin_notices() {
		echo '<div id="message" class="error"><p><strong>wp-membership requires at least PHP '.$wp_membership_min_php_version.' to function properly.</strong></p></div>';
	}
}
else if(!function_exists("curl_init")) {
	add_action('admin_notices', 'wp_membership_admin_notices');
	function wp_membership_admin_notices() {
		echo '<div id="message" class="error"><p><strong>wp-membership requires CURL to be installed.</strong></p></div>';
	}
}
else if(!function_exists("mcrypt_decrypt")) {
	add_action('admin_notices', 'wp_membership_admin_notices');
	function wp_membership_admin_notices() {
		echo '<div id="message" class="error"><p><strong>wp-membership requires mCrypt to be installed.</strong></p></div>';
	}
}
else if(mcrypt_get_cipher_name(MCRYPT_RIJNDAEL_256) == false) {
	add_action('admin_notices', 'wp_membership_admin_notices');
	function wp_membership_admin_notices() {
		echo '<div id="message" class="error"><p><strong>wp-membership requires mCrypt'."'".'s Rijndael 256 to be installed.</strong></p></div>';
	}
}
else if(!function_exists('simplexml_load_string')) {
	add_action('admin_notices', 'wp_membership_admin_notices');
	function wp_membership_admin_notices() {
		echo '<div id="message" class="error"><p><strong>wp-membership requires SimpleXML to be installed.</strong></p></div>';
	}
}


	if(function_exists('add_option')) {
		$wp_membership_plugin = new wp_membership_plugin();
	}
	else {
		chdir("../../../");
		include_once("wp-config.php");
		if(@$_REQUEST['fetch_user_list'] == "1") {
			
			header("Content-Type: application/download");
			header("Content-Disposition: attachment; filename=user_list.csv;");
			if(current_user_can('edit_plugins')) {
				if($user_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_users AS t1 ORDER BY t1.Email"), ARRAY_A)) {
					$output = array('Email', 'Username', 'Extra_Fields');
					$fh = fopen("php://output", "w");
					if($fh) {
						fputcsv($fh, $output);
						foreach($user_rows as $user_row) {
							$output = array($user_row['Email'], $user_row['Username']);
							$extra_fields = @unserialize($user_row['Extra_Fields']);
							$data = "";
							if(is_array($extra_fields)) {
								foreach($extra_fields as $extra_field) {
									if(isset($extra_field->name) && isset($extra_field->value)) {
										if(strlen($data) > 0) $data .= ",";
										$data .= $extra_field->name."=".$extra_field->value;
									}
								}
							}
							$output[] = $data;
							fputcsv($fh, $output);
						}
						fclose($fh);
					}
				}
			}
			else echo "Access Denied";
			exit;
		}
		$wp_membership_plugin = new wp_membership_plugin();
		$wp_membership_plugin->init();
		$wp_membership_plugin->do_Payment_Gateway_Postback();
	}
?>