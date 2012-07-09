<?php
/*
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
require_once(FWP_MEMBERSHIP_PATH.'interfaces/IWPMembershipSettingsTab.php');

global $wp_membership_plugin;
if(isset($wp_membership_plugin) && class_exists('wp_membership_plugin') && is_a($wp_membership_plugin, 'wp_membership_plugin')) {
	if(!class_exists('wp_membership_SettingsTab_Levels')) {
		class wp_membership_SettingsTab_Levels implements IWPMembershipSettingsTab {
			function get_File() {
				return __FILE__;
			}
			function DisplayTab() {
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
		}
	}
}
?>
