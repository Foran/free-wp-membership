<?php
/*
Plugin Name: Free WP-Membership Plugin
Plugin URI: http://free-wp-membership.foransrealm.com/
Description: Allows the ability to have a membership based page restriction. (previously by Synergy Software Group LLC)
Version: 1.1.5
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
		private $m_SettingsTabs = array();
		private $methods = array();
		private $basepath = '../wp-content/plugins/free-wp-membership/';
		private $version = "1.1.5";
		private $admin_notices = array();
		private $admin_messages = array();
		private $public_messages = array();
		private $language_path = 'free-wp-membership';
		
		function __construct() {
			$this->m_SettingsTabs['NewsInfo'] = array('title' => 'News & Info', 'class' => 'wp_membership_SettingsTab_NewsInfo');
			$this->m_SettingsTabs['General'] = array('title' => 'General Settings', 'class' => 'wp_membership_SettingsTab_General');
			$this->m_SettingsTabs['Users'] = array('title' => 'Users', 'class' => 'wp_membership_SettingsTab_Users');
			$this->m_SettingsTabs['Levels'] = array('title' => 'Levels', 'class' => 'wp_membership_SettingsTab_Levels');
			$this->m_SettingsTabs['RegisterPages'] = array('title' => 'Register Pages', 'class' => 'wp_membership_SettingsTab_RegisterPages');
			//$this->m_SettingsTabs['PaymentGateways'] = array('title' => 'Payment Gateways', 'class' => 'wp_membership_SettingsTab_PaymentGateways');
			//$this->m_SettingsTabs['Feedback'] = array('title' => 'Feedback', 'class' => 'wp_membership_SettingsTab_Feedback');
			$this->m_SettingsTabs['Troubleshooting'] = array('title' => 'Troubleshooting', 'class' => 'wp_membership_SettingsTab_Troubleshooting');
			
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
			$first = true;
			$parent = "";
			foreach($this->m_SettingsTabs as $name => $tab) {
				require_once($basepath.'SettingsTabs/'.$name.'.php');
				eval('$this->m_SettingsTabs[$name]["instance"] = new '.$tab['class'].'();');
				if($first) {
					$first = false;
					$parent = $this->m_SettingsTabs[$name]['instance']->get_File();
					add_menu_page(__('WP-Membership '.$tab['title'], 'wp-membership'), __('WP-Membership', 'wp-membership'), 8, $parent, array(&$this->m_SettingsTabs[$name]['instance'], 'DisplayTab'));
				}
				add_submenu_page($parent, __('WP-Membership '.$tab['title'], 'wp-membership'), __($tab['title'], 'wp-membership'), 8, $this->m_SettingsTabs[$name]['instance']->get_File(), array(&$this->m_SettingsTabs[$name]['instance'], 'DisplayTab'));
			}
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
						if(ereg('([.]php[0-9]*)$', $file, $regs)) {
							$name = substr($file, 0, strlen($file) - strlen($regs[1]));
							$option = "wp-membership_plugin_$name";
							add_option($option, "");
							if(get_option($option) == "1") {
								//**FIXME**//
								//Find a way to make the cache work!
								//**END_FIXME**//
								if(($plugin = wp_cache_get($option)) !== false) {
									$this->plugins[$name] = $plugin;
								}
								else {
									$tmp = trim(@file_get_contents(dirname(__FILE__).'/plugins/'.$file));
									if($tmp) {
										if(ereg("class[[:space:]]+([a-zA-Z0-9_]+)", $tmp, $regs)) {
											eval($tmp);
											eval('$this->plugins[$name] = new '.$regs[1].'();');
											if(is_a($this->plugins[$name], 'wp_membership_payment_gateway')) $this->plugins[$name]->Install();
											wp_cache_add($option, $this->plugins[$name]);
										}
									}
								}
								if(isset($this->plugins[$name]) && !is_a($this->plugins[$name], 'wp_membership_payment_gateway')) unset($this->plugins[$name]);
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
			    add_options_page("WP-Membership Plugin", "WP-Membership Plugin", 8, __FILE__, array(&$this, "display_options"));
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
		}

		function display_options_tab_1() {
		}
		
		function set_Country_Code($code = null) {
			$retval = false;
			
			if(is_null($code) || is_string($code)) {
				$retval = @setlocale(LC_MONETARY, $this->get_Country_Codes(is_null($code) ? get_option("wp-membership_country") : $code));
			}
						
			return $retval;
		}
		
		function get_Country_Codes($code) {
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
		}
		
		function display_options_tab_3() {
		}
		
		function display_options_tab_4() {
		}
		
		function display_options_tab_5() {
		}
		
		function display_options_tab_6() {
		}
		
		function display_options_tab_7() {
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
			<div class="wrap">
				<div id="wp_membership_tabs">
					<ul id="wp_membership_options_menu">
						<?php
						$first = true;
						foreach($this->m_SettingsTabs as $name => $tab) {
								?><li><a id="wp_membership_tab_link_<?php echo $name; ?>" href="<?php echo $_SERVER['PHP_SELF']."?".$query_string.(strlen($query_string) > 0 ? "&" : "")."wp-membership_tab=$name"; ?>" onclick="javascript:return set_wp_membership_options_tab(<?php echo $tab['name']; ?>);"<?php if(($first && trim(@$_REQUEST['wp-membership_tab']) == "") || @$_REQUEST['wp-membership_tab'] == $name) echo " class=\"current\""; ?>><?php echo $tab['title']; ?></a></li><?php
							if($first) $first = false;
						}
						?>
					</ul>
				</div>
				<?php
				$tab = $this->m_SettingsTabs[trim(@$_REQUEST['wp-membership_tab']) == "" ? 'NewsInfo' : trim(@$_REQUEST['wp-membership_tab'])]['instance'];
				$tab->DisplayTab(); 
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

	global $wp_membership_plugin;

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