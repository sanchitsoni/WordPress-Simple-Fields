<?php

/**
 * Simple FIelds options page for import and export
 */
class simple_fields_options_page_import_export {

	var 
		$slug = "import_export",
		$sf = null;
	
	function __construct() {		

		add_action("simple_fields_init", array($this, "init"));

	}

	function init() {

		global $sf;
		$this->sf = $sf;

		add_action("admin_init", array($this, "maybe_download_export_file") );
		add_action("simple_fields_after_last_options_nav_tab", array($this, "print_nav_tab"));
		add_action("simple_fields_subpage_$this->slug", array($this, "output_page"));
		add_action("wp_ajax_simple_fields_get_export", array($this, "ajax_get_export") );

	}

	/**
	 * Get name of this options page tab
	 *
	 * @return string
	 */
	function get_name() {
		return _e('Import & Export', 'simple-fields');
	}

	/**
	 * Print the tab for this tab
	 * 
	 * @param string $subpage Name of current tab
	 */
	function print_nav_tab($subpage) {
		?>
		<a href="<?php echo add_query_arg(array("sf-options-subpage" => $this->slug), SIMPLE_FIELDS_FILE) ?>" class="nav-tab <?php echo $this->slug === $subpage ? "nav-tab-active" : "" ?>"><?php esc_html( $this->get_name() ) ?></a>
		<?
	}

	/**
	 * Output contents for this options page
	 */
	function output_page() {
		
		do_action("simple_fields_options_print_nav_tabs", $this->slug);

		?>
		<div class="simple-fields-tools-export-import">
			<script>
				
				jQuery(function($) {
				
					var custom_wrapper = $(".simple-fields-export-custom-wrapper"),
						form = $("form[name='simple-fields-tools-export-form']"),
						textarea = form.find("[name='export-json']");
						btnSubmit = form.find("input[type='submit']");
						ajaxPost = null;

					// Click on radio button "export all" or "export custom"
					// = enable download button, show textarea, update export json
					$(document).on("click", ".simple-fields-tools-export-import .simple-fields-export-what", function(e) {
						
						custom_wrapper.toggle( this.value == "custom" );
						textarea.show();
						update_export_preview();
						btnSubmit.removeClass("button-disabled").removeAttr("disabled");

					});

					// Update json export when a checkbox is clicked
					$(document).on("click", ".simple-fields-export-custom-wrapper input[type='checkbox']", function(e) {
						update_export_preview();
					});

					// Get json export from server via ajax
					function update_export_preview() {
						
						// Abort prev call
						if (ajaxPost && ajaxPost.readyState !== 4) {
							console.log("aborted");
							ajaxPost.abort();
						}

						// Get all checked things
						textarea.text("Getting JSON ...");
						var postData = form.serializeArray();
						ajaxPost = $.post(ajaxurl, postData, function(data) {
							textarea.text(data);
						}, "text");
					}

				});

			</script>
			<style>
				.simple-fields-export-custom-wrapper table th,
				.simple-fields-export-custom-wrapper table td {
					vertical-align: top;
					text-align: left;
					padding: 0 20px 0 0;
				}
				.simple-fields-export-custom-wrapper ul {
					margin: 0;
					list-style-type: none;
				}
				form[name=simple-fields-tools-export-form] textarea {
					font-family: Consolas,Monaco,monospace;
					font-size: 12px;
					width: 50em;
					background: #f9f9f9;
					outline: 0;
				}
			</style>
			<?php

			// Collect for export...
			$field_groups_for_export = $this->sf->get_field_groups(false);
			$post_connectors_for_export = $this->sf->get_post_connectors();
			$post_type_defaults_for_export = $this->sf->get_post_type_defaults();

			// Remove deleted connectors and possibly make other selection
			foreach ($post_connectors_for_export as $key => $val) {
				if ($val["deleted"]) unset( $post_connectors_for_export[$key] );
			}

			?>

			<form method="post" action="" name="simple-fields-tools-export-form">
			
				<p><?php _e("Export Field Groups, Post Connectors and Post Type Defaults as JSON.", "simple-fields") ?></p>

				<p>
					<label><input type="radio" name="export-what" class="simple-fields-export-what" value="all"> <?php _e("Export all data", "simple-fields") ?></label>
					<br>
					<label><input type="radio" name="export-what" class="simple-fields-export-what" value="custom"> <?php _e("Choose what to export", "simple-fields") ?></label>
				</p>

				<div class="simple-fields-export-custom-wrapper hidden">
					
					<table>
						<tr>
							<th>
								<?php _e("Field Groups", "simple-fields"); ?>
							</th>
							<th>
								<?php _e("Post connectors", "simple-fields"); ?>
							</th>
							<th>
								<?php _e("Post type defaults", "simple-fields"); ?>
							</th>
						</tr>

						<tr>
							<td>
								
								<?php
								echo "<ul class='simple-fields-export-custom-field-groups'>";
								foreach ($field_groups_for_export as $one_field_group) {
									printf('
										<li>
											<label>
												<input type="checkbox" value="%2$d" name="field-groups[]">
													%1$s
											</label>
										</li>
										', 
										esc_html( $one_field_group["name"] ),
										$one_field_group["id"]
									);
								}
								echo "</ul>";
								?>
							</td>
							
							<td>
								<?php
								echo "<ul class='simple-fields-export-custom-post-connectors'>";
								foreach ($post_connectors_for_export as $one_post_connector) {
									printf('
										<li>
											<label>
												<input type="checkbox" value="%2$d" name="post-connectors[]">
													%1$s
											</label>
										</li>
										', 
										esc_html( $one_post_connector["name"] ),
										$one_post_connector["id"]
									);
								}
								echo "</ul>";
								?>
							</td>

							<td>
								<?php
								echo "<ul class='simple-fields-export-custom-post-type-defaults'>";
								foreach ($post_type_defaults_for_export as $one_post_type_default_post_type => $one_post_type_default_key) {
									printf('
										<li>
											<label>
												<input type="checkbox" value="%1$s" name="post-type-defaults[]">
													%1$s
											</label>
										</li>
										', 
										esc_html( $one_post_type_default_post_type ),
										$one_post_type_default_key
									);
								}
								echo "</ul>";
								?>
							</td>

						</tr>
					</table>

				</div>

				<?php
				// Get array with all export data
				$arr_export_data = $this->get_export();

				// beautify json if php version is more than or including 5.4.0
				if ( version_compare ( PHP_VERSION , "5.4.0" ) >= 0 ) {
					$export_json_string = json_encode( $arr_export_data , JSON_PRETTY_PRINT);
				} else {
					$export_json_string = json_encode( $arr_export_data );
				}

				?>	
				<textarea class="hidden" name="export-json" readonly cols=100 rows=10><?php echo $export_json_string ;?></textarea>

				<p>
					<input type="submit" class="button button-disabled" disabled value="Download export">
					<input type="hidden" name="action" value="simple_fields_get_export">
				</p>
			
			</form>

		</div><!-- simple-fields-tools-export-import -->
		<?php

	}

	function get_export( array $selection = array()) {

		$arr_export_data = array();

		$field_groups_for_export = $this->sf->get_field_groups(false);
		$post_connectors_for_export = $this->sf->get_post_connectors();
		$post_type_defaults_for_export = $this->sf->get_post_type_defaults();

		// Remove deleted connectors and possibly make other selection
		foreach ($post_connectors_for_export as $key => $val) {
			if ($val["deleted"]) unset( $post_connectors_for_export[$key] );
		}

		// if selection is not empty then only include whats in there
		if ( ! empty( $selection ) && ( "custom" === $selection["export-what"] ) ) {
			
			$field_groups_to_keep = array();
			if ( ! empty( $_POST["field-groups"] ) ) {
				foreach ( (array) $_POST["field-groups"] as $one_field_group_id_to_keep) {
					$field_groups_to_keep[ $one_field_group_id_to_keep ] = $field_groups_for_export[ $one_field_group_id_to_keep ];
				}
			}
			$field_groups_for_export = $field_groups_to_keep;

			$post_connectors_to_keep = array();
			if ( ! empty( $_POST["post-connectors"] ) ) {
				foreach ( (array) $_POST["post-connectors"] as $one_post_connector_id_to_keep ) {
					$post_connectors_to_keep[ $one_post_connector_id_to_keep ] = $post_connectors_for_export[ $one_post_connector_id_to_keep ];
				}
			}
			$post_connectors_for_export = $post_connectors_to_keep;

			$post_type_defaults_to_keep = array();
			if ( ! empty( $_POST["post-type-defaults"] ) ) {
				foreach ( (array) $_POST["post-type-defaults"] as $one_post_type_to_keep) {
					$post_type_defaults_to_keep[ $one_post_type_to_keep ] = $post_type_defaults_for_export[ $one_post_type_to_keep ];
				}
			}
			$post_type_defaults_for_export = $post_type_defaults_to_keep;

		} // if selection


		if ( ! empty( $field_groups_for_export ) ) $arr_export_data["field_groups"] = $field_groups_for_export;
		if ( ! empty( $post_connectors_for_export ) ) $arr_export_data["post_connectors"] = $post_connectors_for_export;
		if ( ! empty( $post_type_defaults_for_export ) ) $arr_export_data["post_type_defaults"] = $post_type_defaults_for_export;
		
		return $arr_export_data;

	} // get_export

	function ajax_get_export() {
		
		$arr_export_data = $this->get_export( $_POST );

		// beautify json if php version is more than or including 5.4.0
		if ( version_compare ( PHP_VERSION , "5.4.0" ) >= 0 ) {
			$export_json_string = json_encode( $arr_export_data , JSON_PRETTY_PRINT);
		} else {
			$export_json_string = json_encode( $arr_export_data );
		}
		
		header('Content-Type: text/plain');
		echo $export_json_string;

		exit;

	} // ajax_get_export


	/**
	 * Check if export file should be downloaded,
	 * and if so send headers and the actual json contents
	 *
	 * @since 1.2.4
	 */
	function maybe_download_export_file() {

		// Don't do anything if this is an ajax call
		if ( defined("DOING_AJAX") && DOING_AJAX ) return;

		// And only do download then all post variables are set
		if ( isset($_POST) && isset( $_POST["action"] ) && ( $_POST["action"] === "simple_fields_get_export" ) ) {

			header('Content-disposition: attachment; filename=simple-fields-export.json');
			header('Content-type: application/json');

			echo stripslashes($_POST["export-json"]);
			exit;
			
		}

	} // maybe_download_export_file

}

new simple_fields_options_page_import_export();
