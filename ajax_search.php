<?php 
/*
Plugin Name: Ajax Search
*/


add_action('wp_enqueue_scripts' , function() {
	wp_enqueue_style( 'ajax_search_style', plugin_dir_url( __FILE__ ) . '/ajax_search_style.css' );
});
function add_query_vars_filter( $vars ){
	$vars[] = "location-search";
	return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );

function wptuts_add_endpoints() {
	add_rewrite_endpoint('location-search', EP_ALL);
}
add_action( 'init', 'wptuts_add_endpoints');

add_filter('the_content' , function($the_content) {
	ob_start();


	if (get_query_var( 'location-search', 1 ) != 1 ) {
		if (strlen(get_query_var( 'location-search', 1 )) < 1) {
			?>
			<style>

			</style>
			<div class="search_area">
				<input name="location-search" type="text" id="ajax_search_input">
				<div id="result"></div>
				<div class="spinner" style="background: url('<?php echo site_url(); ?>/wp-admin/images/wpspin_light.gif') no-repeat;"></div>
			</div>

			<script>
				var search = "";
				jQuery(document).ready(function($) {

					jQuery(document).on('click', '.search_result', function(event) {
						event.preventDefault();
						jQuery("#ajax_search_input").val(jQuery(this).html().trim());
						search = jQuery(this).attr("data-slug");
						location.href = search;
					});
					jQuery('#ajax_search_input').keypress(function (e) {
						if (jQuery("#search_target").length > 0 && jQuery("#ajax_search_input").val().length >= 3) {
							if (e.which == 13) {
								location.href = search;
								return false;   
							}
						}
					});

					jQuery(document).on('input', '#ajax_search_input', function(event) {
						if (jQuery("#ajax_search_input").val().length >= 3) {
							jQuery.ajax({
								url: '<?php echo admin_url("admin-ajax.php"); ?>',
								type: 'POST',
								dataType: 'json',
								data: {
									'action':'ajax_search_query',
									'search_value' : jQuery("#ajax_search_input").val()
								},
								beforeSend: function() {
									jQuery('.spinner').show();
								}
							}).done(function(data) {
								jQuery('.spinner').hide();
								jQuery('#result').html('');
								if (data.length < 1) {
									jQuery('#result').html('<div class="no_results">No Results Found</div>');
								} else {
									for (var i = 0 ; i < data.length; i++) {
										if (i == 0) {
											jQuery('#result').append('<div data-slug="'+ data[i].slug +'" class="search_result" id="search_target">' + data[i].location + '</div>');
											search = data[i].slug;
										} else {
											jQuery('#result').append('<div data-slug="'+ data[i].slug +'" class="search_result">' + data[i].location + '</div>');
										}
									}
								}
							});
						} else {
							jQuery('#result').html('');
						}
					});
});
</script>
<?php
} else {
	global $wpdb;
	$table_name = $wpdb->prefix . 'population';
	$results = $wpdb->get_results( "SELECT * FROM $table_name WHERE slug = '".get_query_var( 'location-search', 1  )."' LIMIT 1" , ARRAY_A );
	?>
	<h2>Search Results</h2>
	<?php
	foreach ($results as $key => $value) {
		?>
		<p>
			Location: <?php echo $value['location']; ?>
		</p>
		<p>
			Population:	<?php echo $value['population']; ?>
		</p>

		<?php
	}

}
}  
$temp = ob_get_contents();
ob_end_clean();
$the_content .= $temp;
return $the_content;
});


function ajax_search_query() {
	if ( isset($_REQUEST['search_value']) ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'population';
		$results = $wpdb->get_results( "SELECT * FROM $table_name WHERE location LIKE '%" . $_REQUEST['search_value'] . "%' ORDER BY population DESC LIMIT 10" , ARRAY_A );
// $results = $wpdb->get_results( "SELECT post_id FROM wp_postmeta WHERE meta_key = 'category' AND meta_value = '$id'" , ARRAY_A );
// echo $_REQUEST['region'];
		echo json_encode($results);
	}
	die();
}
add_action('wp_ajax_ajax_search_query' , 'ajax_search_query'); 
add_action("wp_ajax_nopriv_ajax_search_query", "ajax_search_query");


function ajax_search_activation() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name = $wpdb->prefix . 'population';

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		location varchar(150) NOT NULL,
		slug varchar(150) NOT NULL,
		population int(10) NOT NULL,
		UNIQUE KEY id (id)
		) $charset_collate;";


require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );

$csv = array_map('str_getcsv', file(plugin_dir_url( __FILE__ ) . '/data.csv'));

foreach ($csv as $key => $value) {
	$data = explode('	', $value[1]);
	$location = str_replace('\N', '', $value[0]) . ', ' . $data[0]; 
	$slug =  str_replace(' ' , '-' , $data[1]); 
	$population = $data[2]; 
	$table_name = $wpdb->prefix . 'population';
	$wpdb->insert( 
		$table_name, 
		array( 'location' => $location , 'slug' =>  $slug, 'population' =>  $population, ), 
		array( '%s' ,'%s' ,'%d' ) );
}


}
register_activation_hook( __FILE__, 'ajax_search_activation' );


function ajax_search_remove_database() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'population';
	$sql = "DROP TABLE IF EXISTS $table_name;";
	$wpdb->query($sql);
}

register_deactivation_hook( __FILE__, 'ajax_search_remove_database' );


