<?php
/*
Plugin Name: HWCOE Syllabi Display
Description: This plugin allows admin to display a dynamic table of entries using the Syllabus Upload custom_post_type. Use this shortcode to display the table: <strong>[syllabi-table]</strong>.
Requirements: Advanced Custom Fields with the Student Registration Modules field group; hwcoe-ufl-child theme with career fair modifications; Gravity Forms with the Syllabi Uploads form and Gravity Forms + Custom Post Types plugin. 
Version: 1.8.3
Author: Allison Logan
Author URI: http://allisoncandreva.com/
*/

function create_post_type() {
  register_post_type( 'hwcoe-syllabi',
    array(
      'labels' => array(
        'name' => __( 'Syllabi Form Entries' ), //Top of page when in post type
        'singular_name' => __( 'Entry' ), //per post
		'menu_name' => __('Course Syllabi'), //Shows up on side menu
		'all_items' => __('All Entries'), //On side menu as name of all items
      ),
      'public' => true,
	  'menu_position' => 4,
	  'menu_icon' => 'dashicons-text-page',
      'has_archive' => true,
    )
  );
}
add_action( 'init', 'create_post_type' );

/* Enqueue assets */
add_action( 'wp_enqueue_scripts', 'hwcoe_syllabi_assets' );
function hwcoe_syllabi_assets() {
    wp_register_style( 'hwcoe-syllabi-datatables', plugins_url( '/css/datatables.min.css' , __FILE__ ) );
    wp_register_style( 'hwcoe-syllabi', plugins_url( '/css/hwcoesyllabi.css' , __FILE__ ) );

    wp_register_script( 'hwcoe-syllabi-datatables', plugins_url( '/js/datatables.min.js' , __FILE__ ), array( 'jquery' ), null, true );
    wp_register_script( 'hwcoe-syllabi', plugins_url( '/js/hwcoesyllabi.js' , __FILE__ ), array( 'jquery' ), null, true );
}

if( is_admin() ){
    include( 'admin-entries.php' );
}

/*Convert Name field to Title Case*/
$theformID = RGFormsModel::get_form_id('Syllabi Upload');
//$thefieldID = RGFormsModel::get_field($theformID, 'name_first');

add_action('gform_pre_submission', 'titlecase_fields');
function titlecase_fields($form){
	// add all the field IDs you want to titlecase, to this array
	$form  = GFAPI::get_form( $GLOBALS['theformID'] );
	$fields_to_titlecase = array(
						'input_8_3',
						'input_8_6');
	foreach ($fields_to_titlecase as $each) {
			// for each field, convert the submitted value to lowercase and then title case and assign back to the POST variable
			// the rgpost function strips slashes
			$lowercase = strtolower(rgpost($each));
			$_POST[$each] = ucwords($lowercase, " \t\r\n\f\v'.-");
		} 
	// return the form, even though we did not modify it
	return $form;
}//end field titlecaseing

add_action('gform_pre_submission', 'upperecase_fields');
function upperecase_fields($form){
	// add all the field IDs you want to uppercase, to this array
	$form  = GFAPI::get_form( $GLOBALS['theformID'] );
	$fields_to_uppercase = array(
						'input_14');
	foreach ($fields_to_uppercase as $each) {
			// for each field, convert the submitted value to uppercase and assign back to the POST variable
			// the rgpost function strips slashes
			$_POST[$each] = strtoupper(rgpost($each));
		} 
	// return the form, even though we did not modify it
	return $form;
}//end field uppercasing

require_once plugin_dir_path( __FILE__ ) . 'inc/gw-gravity-forms-rename-uploaded-files.php';


/*Plugin shortcode*/
function syllabi_table_shortcode() {

	// Assets 
	wp_enqueue_style( 'hwcoe-syllabi-datatables' );
    wp_enqueue_style( 'hwcoe-syllabi' );
    wp_enqueue_script( 'hwcoe-syllabi-datatables' );
    wp_enqueue_script( 'hwcoe-syllabi' );
	
	//Query
	$the_query = new WP_Query(array( 'post_type' => 'hwcoe-syllabi', 'posts_per_page' => -1 ));
	
	//Table
	$output = '<table id="syllabi-table">
				<thead>
					<tr>
						<th>Title (click to open)</th>
						<th>Course Number</th>
						<th>Section(s)</th>
						<th>Instructor</th>
						<th>Semester</th>
						<th>Year</th>
					</tr>
				</thead>
				<tbody>';
	
	while ( $the_query->have_posts() ) : $the_query->the_post();
		$upload = get_field( 'su_syllabi_upload' );
		$coursenumber = get_field( 'su_course_number' );
		$coursesections = get_field( 'su_course_sections' );
		$instructor = get_field( 'su_instructor' );
		$semester = get_field( 'su_semester' );
		$year = get_field( 'su_year' );
		
			$output .= '<tr>
							<td><a href="' . esc_url($upload) . '" target="_blank">' .get_field( 'su_course_title' ). '</a></td>
							<td>' . esc_html($coursenumber) . '</td>';
				if ( $coursesections ):  //if the field is not empty
					$output .= '<td>' . esc_html($coursesections) . '</td>'; //display it
					else: 
					$output .= '<td>All Sections</td>';
					endif; 		
				$output .= '<td>' . esc_html($instructor) . '</td>
							<td>' . esc_html($semester) . '</td>
							<td>' . esc_html($year) . '</td>';
			$output .= '</tr>';
	endwhile;
	wp_reset_query();
	
	$output .= '</tbody>
				</table>';
	
	//Return code
	return $output;
}

add_shortcode('syllabi-table', 'syllabi_table_shortcode'); 


// Add field groups for Syllabi Uploads
add_filter('acf/settings/save_json', 'hwcoe_syllabi_acf_json_save_point');

if (!function_exists('hwcoe_syllabi_acf_json_save_point')) { 
	function hwcoe_syllabi_acf_json_save_point( $path ) {
		// update path
		$paths[] = plugin_dir_path(__FILE__) . 'inc/acf-json';
		return $path; 
	}
}

add_filter('acf/settings/load_json', 'hwcoe_syllabi_acf_json_load_point');

if (!function_exists('hwcoe_syllabi_acf_json_load_point')) {
	function hwcoe_syllabi_acf_json_load_point( $paths ) {	
		// remove original path (optional)
		unset($paths[0]);

		// append path
		$paths[] = plugin_dir_path(__FILE__) . 'inc/acf-json';
		
		// return
		return $paths;
	}
}
