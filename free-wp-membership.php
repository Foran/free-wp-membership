<?php
/*
Plugin Name: Free WP-Membership Plugin
Plugin URI: http://free-wp-membership.foransrealm.com/
Description: Allows the ability to have a membership based page restriction. (previously by Synergy Software Group LLC)
Version: 1.1.9
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
    along with Free WP-Membership.  If not, see <http://www.gnu.org/licenses/>.

*/

global $wp_membership_min_php_version;
global $wp_membership_min_wp_version;

$wp_membership_min_php_version = '5.3.0';
$wp_membership_min_wp_version = '2.8.0';
$free_wp_membership_min_requirements = true;

// Sanity checks, so we can give sane error messages
if(version_compare(PHP_VERSION, $wp_membership_min_php_version, '<')) {
	$free_wp_membership_min_requirements = false;
	add_action('admin_notices', create_function('', "echo '<div class=\"error\"><p>Free WP-Membership requires PHP ".$wp_membership_min_php_version." or Greater; detected version ".PHP_VERSION."</p></div>';"));
}
if(!function_exists('simplexml_load_string')) {
	$free_wp_membership_min_requirements = false;
	add_action('admin_notices', create_function('', "echo '<div class=\"error\"><p>Free WP-Membership requires SimpleXml to be installed</p></div>';"));
}
if(version_compare(get_bloginfo('version', 'raw'), $wp_membership_min_wp_version, '<')) {
	$free_wp_membership_min_requirements = false;
	add_action('admin_notices', create_function('', "echo '<div class=\"error\"><p>Free WP-Membership requires Wordpress ".$wp_membership_min_wp_version." or Greater; detected version ".get_bloginfo('version', 'raw')."</p></div>';"));
}
if(!function_exists("curl_init")) {
	$free_wp_membership_min_requirements = false;
	add_action('admin_notices', create_function('', "echo '<div class=\"error\"><p>Free WP-Membership requires CURL to be installed.</p></div>';"));
}
if(!function_exists("ereg")) {
	$free_wp_membership_min_requirements = false;
	add_action('admin_notices', create_function('', "echo '<div class=\"error\"><p>Free WP-Membership requires eReg regular expression support to be installed.</p></div>';"));
}

if(!class_exists('wp_membership_plugin') && $free_wp_membership_min_requirements) {
	define('FWP_MEMBERSHIP_PATH', plugin_dir_path(__FILE__));
	define('FWP_MEMBERSHIP_URL', plugin_dir_url(__FILE__));
	class wp_membership_plugin {
		public $plugins = array();
		private $m_SettingsTabs = array();
		private $m_Shortcodes = array();
		private $methods = array();
		private $basepath = '';
		public $version = '1.1.9';
		public $admin_notices = array();
		public $admin_messages = array();
		public $public_messages = array();
		public $language_path = 'free-wp-membership';
		
		function __construct() {
			$this->m_SettingsTabs['NewsInfo'] = array('title' => 'News & Info', 'class' => 'wp_membership_SettingsTab_NewsInfo');
			$this->m_SettingsTabs['General'] = array('title' => 'General Settings', 'class' => 'wp_membership_SettingsTab_General');
			$this->m_SettingsTabs['Users'] = array('title' => 'Users', 'class' => 'wp_membership_SettingsTab_Users');
			$this->m_SettingsTabs['Levels'] = array('title' => 'Levels', 'class' => 'wp_membership_SettingsTab_Levels');
			$this->m_SettingsTabs['RegisterPages'] = array('title' => 'Register Pages', 'class' => 'wp_membership_SettingsTab_RegisterPages');
			//$this->m_SettingsTabs['PaymentGateways'] = array('title' => 'Payment Gateways', 'class' => 'wp_membership_SettingsTab_PaymentGateways');
			//$this->m_SettingsTabs['Feedback'] = array('title' => 'Feedback', 'class' => 'wp_membership_SettingsTab_Feedback');
			$this->m_SettingsTabs['Troubleshooting'] = array('title' => 'Troubleshooting', 'class' => 'wp_membership_SettingsTab_Troubleshooting');
			$this->m_Shortcodes[] = array('type' => 'LoginForm', 'class' => 'wp_membership_Shortcode_LoginForm');
			$this->m_Shortcodes[] = array('type' => 'Level', 'class' => 'wp_membership_Shortcode_Level');
			$this->m_Shortcodes[] = array('type' => 'UserProfileForm', 'class' => 'wp_membership_Shortcode_UserProfileForm');
			
			$this->load_register_shortcodes();
			
			add_action('update_option_update_plugins', array(&$this, 'update_plugins'), 10, 2);
			
			add_option("wp-membership_access_denied_page_id", "1");
			add_option("wp-membership_logout_page_id", "1");
			add_option("wp-membership_login_page_id", "1");
			add_option("wp-membership_login_prompt_forgot_password", "0");
			add_option("wp-membership_logged_in_page_id", "1");
			add_option("wp-membership_loginform_shortcode", "LoginForm");
			add_option("wp-membership_user_profile_page_id", "1");
			add_option("wp-membership_user_profile_from_shortcode", "UserProfile");
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
			if(in_array('Settings', get_option('wp-membership_admin_menu_location'))) {
				if(file_exists(dirname(__FILE__).'/free-wp-membership.php')) {
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
			register_activation_hook(__FILE__, array(&$this, 'activation'));
			register_deactivation_hook(__FILE__, array(&$this, 'deactivation'));
			add_action('init', array(&$this, 'init'));
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
			add_filter('getarchives_where', array(&$this, 'search'));
			add_action('wp_dashboard_setup', array(&$this, 'add_dashboard_widgets'));

			$this->init_shortcodes();

			$methods = array();
			
			add_action('wp_ajax_fwpm_download_user_list', array(&$this, 'downloadUserList'));
			
			require_once(FWP_MEMBERSHIP_PATH.'Widgets/Login.php');
			add_action('widgets_init', array(&$this, 'widgets_init'));
			
			require_once(FWP_MEMBERSHIP_PATH.'UnitTestFramework.php');
			$unitTestFramework = new wp_membership_UnitTestFramework($this);
		}

		function downloadUserList() {
			global $wpdb;
			check_admin_referer('execute_unit_test', 'userlist_nonce');
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

		function widgets_init() {
			return register_widget("FreeWPMembershipLoginWidget");
		}
		
		function add_dashboard_widgets() {
			wp_add_dashboard_widget('fwp_membership_dashboard_widget', 'Free WP-Membership News', array(&$this, 'dashboard_widget'));
		}
		
		function dashboard_widget() {
			$milestones = array();
			$ch = curl_init('https://api.github.com/repos/Foran/free-wp-membership/milestones');
			if($ch) {
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$buffer = curl_exec($ch);
				curl_close($ch);
				if($buffer) {
					$data = json_decode($buffer);
					foreach($data as $entry) {
						if(isset($entry->title)) {
							$milestones[$entry->title]['progress'] = (floatval($entry->closed_issues) / (floatval($entry->open_issues) + floatval($entry->closed_issues))) * 100.00;
							$milestones[$entry->title]['title'] = $entry->title;
							$milestones[$entry->title]['link'] = 'https://github.com/Foran/free-wp-membership/issues?state=open&milestone='.$entry->number;
						}
					}
				}
			}
			uasort($milestones, function ($a, $b) {
				return isset($a['title']) && isset($b['title']) ? version_compare($a['title'], $b['title'], '>=') : (isset($b['title']) ? 1 : 0);
			});
			?>
			<div class="table table_milestones" style="float:left; width: 45%;margin-top: -15px;">
				<p class="sub" style="color: #8F8F8F;font-size: 14px;">Milestones</p>
				<table style="width:100%;">
					<?php
					$last = false;
					foreach($milestones as $milestone) {
						?>
						<tr><td class="first b b-milestones"><a href="<?php echo $milestone['link']; ?>"><?php echo htmlentities($milestone['title']); ?></a></td><td class="t milestones"><a href="<?php echo $milestone['link']; ?>"><?php echo htmlentities(number_format($milestone['progress'], 2).'% complete'); ?></a></td></tr>
						<?php
					} ?>
				</table>
			</div>
			<div class="table table_news" style="margin-left: 55%;">
				<p class="sub" style="color: #8F8F8F;font-size: 14px;">News</p>
				<?php
				wp_widget_rss_output('http://feeds.feedburner.com/ForansBlogFreeWp-membership', array('show_summary' => 1, 'show_date' => 1));
				?>
			</div>
			<?php
		}
		
		function load_register_shortcodes() {
			global $wpdb;
			if($register_rows = $wpdb->get_results($wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'wp_membership_register_pages AS t1'), ARRAY_A)) {
				foreach($register_rows as $register_row) {
					$this->m_Shortcodes[] = array('type' => 'RegisterForm', 'class' => 'wp_membership_Shortcode_RegisterForm', 'arg' => $register_row);
				}
			}			
		}
		
		function init_shortcodes() {
			load_plugin_textdomain('wp-membership', false, $this->language_path);
			$first = true;
			$parent = "";
			$basepath = pathinfo($_SERVER['SCRIPT_FILENAME']);
			$basepath = ereg_replace("/wp-admin\$", "", @$basepath['dirname']);
			$basepath = ereg_replace("/wp-content/plugins/free-wp-membership\$", "", $basepath);
			$loaded = array();
			foreach($this->m_Shortcodes as $key => $shortcode) {
				$file = $basepath.'/wp-content/plugins/free-wp-membership/Shortcodes/'.$shortcode['type'].'.php';
				if(file_exists($file)) {
					require_once($file);
					$loaded[$file] = true;
				}
				if(@$loaded[$file] === true) {
					eval('$this->m_Shortcodes[$key]["instance"] = new '.$shortcode['class'].'('.(isset($shortcode['arg']) ? "\$shortcode['arg']" : '').');');
					add_shortcode($this->m_Shortcodes[$key]['instance']->get_Shortcode(), array(&$this->m_Shortcodes[$key]['instance'], 'Handler'));
				}
			}
		}
		
		function admin_menu_init() {
			load_plugin_textdomain('wp-membership', false, $this->language_path);
			$first = true;
			$parent = "";
			$basepath = pathinfo($_SERVER['SCRIPT_FILENAME']);
			$basepath = ereg_replace("/wp-admin\$", "", @$basepath['dirname']);
			$basepath = ereg_replace("/wp-content/plugins/free-wp-membership\$", "", $basepath);
			foreach($this->m_SettingsTabs as $name => $tab) {
				$file = $basepath.'/wp-content/plugins/free-wp-membership/SettingsTabs/'.$name.'.php';
				if(file_exists($file)) {
					require_once($file);
					eval('$this->m_SettingsTabs[$name]["instance"] = new '.$tab['class'].'();');
					if($first) {
						$first = false;
						$parent = $this->m_SettingsTabs[$name]['instance']->get_File();
						add_menu_page(__('WP-Membership '.$tab['title'], 'wp-membership'), __('WP-Membership', 'wp-membership'), 8, $parent, array(&$this->m_SettingsTabs[$name]['instance'], 'DisplayTab'));
					}
					add_submenu_page($parent, __('WP-Membership '.$tab['title'], 'wp-membership'), __($tab['title'], 'wp-membership'), 8, $this->m_SettingsTabs[$name]['instance']->get_File(), array(&$this->m_SettingsTabs[$name]['instance'], 'DisplayTab'));
				}
				else echo 'failed to load '.$file.'<br />';
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
			$this->basepath = ereg_replace("/wp-admin\$", "", $basepath['dirname']);
			$this->basepath = ereg_replace("/wp-content/plugins/free-wp-membership\$", "", $basepath);
				/*
				$dh = @opendir(dirname(__FILE__).'/plugins');
				if($dh) {
					while(($file = readdir($dh)) !== false) {
						if(ereg('([.]php[0-9]*)$', $file, $regs)) {
							$name = substr($file, 0, strlen($file) - strlen($regs[1]));
							$option = "wp-membership_plugin_$name";
							add_option($option, "");
							if(get_option($option) == "1") {
								// **FIXME** //
								//Find a way to make the cache work!
								// **END_FIXME** //
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
				*/
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
			
			//TODO: Detect if the content was passed from a login_shortcode
			if(@$_REQUEST['do_login'] == "1") {
		    	$user_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_users AS t1 WHERE (SELECT COUNT(*) FROM ".$wpdb->prefix."wp_membership_user_levels AS t2 WHERE t1.User_ID=t2.User_ID AND (t2.Expiration IS NULL OR t2.Expiration>=NOW()))>0 AND (t1.Email=%s OR t1.Username=%s) AND t1.Password=PASSWORD(%s)", @$_REQUEST['email'], @$_REQUEST['email'], @$_REQUEST['password']);
		    	if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
					$_SESSION['wp-membership_plugin']['wp-membership_user_id'] = $user_row['User_ID'];
				    $page->set('page_id', get_option("wp-membership_logged_in_page_id"));
				    header("Location: ".get_permalink(get_option("wp-membership_logged_in_page_id")));
				    exit;
		    	}
		    	else if(!isset($this->public_messages['bad_password'])) {
		    		$retval .= "<div class=\"login_error\">Error: Bad Email or Password.</div>";
		    	}
		    }
		    else if(!isset($this->public_messages['do_password_reset']) && get_option('wp-membership_login_prompt_forgot_password') == "1" && @$_REQUEST['do_forgot_password'] == "1") {
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
			    		$retval .= "<div class=\"forgot_password_message\">Password Successfully Reset. Check your e-mail for the new password.</div>";
			    		wp_mail($user_row['Email'], "Password was reset by request", "Someone (probably you) requested your password be reset. Your new password is $password. This is case sensitive. You can login at: http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\n--\nManagement");
			    	}
			    	else $retval .= "<div class=\"forgot_password_error\">Failed to reset password, please contact your system administrator.</div>";
		    	}
		    	else $retval .= "<div class=\"forgot_password_message\">Password Successfully Reset. Check your e-mail for the new password.</div>";
		    }
		    else if($page_id == get_option("wp-membership_logout_page_id")) {
				unset($_SESSION['wp-membership_plugin']['wp-membership_user_id']);
		    }
			else if($this->is_Register_Page($page_id) || $this->is_Register_Page(@$_REQUEST['member_register_page_id'])) {
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
		
		/**
		 * @return bool
		 */
		function check_requirements() {
			$retval = true;
			
			if(!function_exists('curl_init')) {
				$this->admin_notices[] = "Unable to detect Curl";
				$retval = false;
			}
			if(!function_exists('simplexml_load_string')) {
				$this->admin_notices[] = "Unable to detect SimpleXml";
				$retval = false;
			}
			
			return $retval;
		}
		
		function activation() {
			if($this->check_requirements()) {
				if($this->create_tables()) {
					foreach($this->plugins as $plugin) {
						if(is_a($plugin, 'wp_membership_payment_gateway')) $plugin->Install();
					}
				}
				else {
					$this->admin_notices[] = "Failed to properly create database";
				}
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
		
		
		function get_User_Level_Names() {
			$retval = array();

			global $wpdb;
			
			if(@$_SESSION['wp-membership_plugin']['wp-membership_user_id'] > 0) {
				if($user_rows = $wpdb->get_results($wpdb->prepare("SELECT t2.Level_ID, t2.Name FROM ".$wpdb->prefix."wp_membership_user_levels AS t1, ".$wpdb->prefix."wp_membership_levels AS t2 WHERE t1.User_ID=%s AND t1.Level_ID=t2.Level_ID", @$_SESSION['wp-membership_plugin']['wp-membership_user_id']), ARRAY_A)) {
					foreach($user_rows as $user_row) {
						$retval[$user_row['Level_ID']] = $user_row['Name'];
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
		
		/**
		 * Retrieve a list of all posts protected by the given level(s), return all protected posts if no level is specified
		 *
		 * @param array|int $level_id
		 * @return array a list of protected posts
		 */
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
		
		/**
		 * Retrieve a list of all categories protected by the given level(s), return all protected categories if no level is specified
		 *
		 * @param array|int $level_id
		 * @return array a list of protected categories
		 */
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
			
			return $retval;
		}
		
		function old_admin_menu() {
			if (current_user_can('edit_plugins')) {
			    add_options_page("WP-Membership Plugin", "WP-Membership", 8, __FILE__, array(&$this, "display_options"));
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

if(class_exists('wp_membership_plugin') && $free_wp_membership_min_requirements) {
	global $wp_membership_plugin;

	if(function_exists('add_option')) {
		$wp_membership_plugin = new wp_membership_plugin();
	}
	else {
		chdir("../../../");
		include_once("wp-config.php");
		$wp_membership_plugin = new wp_membership_plugin();
		$wp_membership_plugin->init();
		$wp_membership_plugin->do_Payment_Gateway_Postback();
	}
}
else {
	add_action('admin_notices', create_function('', "echo '<div class=\"error\"><p>Free WP-Membership Failed to load, most likely due to not meeting the minimum requirements</p></div>';"));
}
?>