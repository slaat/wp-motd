<?php
/*
    Plugin Name: Greet motd
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

global $greetmotd_db_version;
$greetmotd_db_version = '1.0';

function my_custom_redirect() {
    global $post;
    if (is_page() || is_object($post)) {
        if ($redirect = get_post_meta($post->ID, 'redirect', true)) {
            wp_redirect($redirect);
            exit;
        }
    }
}

//Install
function greetmotd_install() {
    global $wpdb;
    global $greetmotd_db_version;
    $table_name = $wpdb->prefix . "greetmotd";
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            motd text NOT NULL,
            day TINYINT NOT NULL,
            PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    add_option( 'greetmotd_db_version', $greetmotd_db_version );
}

function greetmotd_install_data() {
    global $wpdb;

    $sample_motd = array();
    $sample_motd[0] = 'Welcome, it is Monday';
    $sample_motd[1] = 'Welcome, it is Tuesday';
    $sample_motd[2] = 'Welcome, it is Wednesday';
    $sample_motd[3] = 'Welcome, it is Thursday';
    $sample_motd[4] = 'Welcome, it is Friday';
    $sample_motd[5] = 'Welcome, it is Saturday';
    $sample_motd[6] = 'Welcome, it is Sunday';

    $table_name = $wpdb->prefix . 'greetmotd';

    for($i = 0; $i < 7; $i++) {
        $wpdb->insert(
            $table_name,
            array(
                'motd' => $sample_motd[$i],
                'day' => $i,
            )
        );
    }
}
register_activation_hook( __FILE__, 'greetmotd_install' );
register_activation_hook( __FILE__, 'greetmotd_install_data' );
add_action('admin_menu', 'greetmotd_admin_menu');

if (!function_exists('greetmotd_admin_menu')) {
  function greetmotd_admin_menu(){
    if(empty($GLOBALS['admin_page_hooks']['greetmotd_admin_menu'])){
      add_menu_page('Information','Greet MOTD','manage_options','greetmotd_admin_menu','greetmotd_callback','dashicons-clock',40);
    }
  }
}

function greetmotd_callback() {
	global $wpdb;
	//Add
	if(isset($_POST['update_motds_nonce']) 
	 && wp_verify_nonce($_POST['update_motds_nonce'], 'update_motds') 
	 && current_user_can('administrator')){
	  $allowed   = array(
				'a' => array(
					'href' => true,
					'title' => true,
					'target' => true,
				),
				'b' => array(),
				'code' => array(),
				'del' => array(
					'datetime' => true,
				),
				'em' => array(),
				'i' => array(),
				'q' => array(
					'cite' => true,
				),
				'strike' => array(),
				'strong' => array(),
	  );
	  if ( isset($_POST['Add'])) {
		$motd = stripslashes(wp_kses( $_POST['motd_0'], $allowed ));
		$day = stripslashes(wp_kses( $_POST['day_0'], $allowed ));
		$addMOTD = $wpdb->insert( $wpdb->prefix."greetmotd",array('motd' => $motd, 'day' => $day));
	  }
	  if ( isset($_POST['Delete'])) {
		$ids=array_keys($_POST['Delete']);
		$id=intval($ids[0]);
		$wpdb->query("delete from ".$wpdb->prefix."greetmotd where `id`='".$id."'");
	  }
	  if ( isset($_POST['Edit'])) {
		$ids=array_keys($_POST['Edit']);
		$id=intval($ids[0]);
		$motd = stripslashes(wp_kses( $_POST['motd_'.$id], $allowed ));    
		$wpdb->update($wpdb->prefix."greetmotd", array('motd' => $motd), array('id' => $id),array('%s'));
	  }
	}
	?>
	<div class="wrap">
	  <div class="icon32" id="icon-options-general"><br /></div>
	  <h2>Greet MOTD</h2>
	<hr/>
	<p>
	To use as a shortcode add [motd] to yout post or page.
	<div id="motds" class="col-md-8">
	<?php greetmotd_show_motds(); ?>    
	</div>
	<?php
}

function get_current_day() {
	$day_of_week = date('N');
	return $day_of_week-1;
}

function greetmotd_motd() {
  global $wpdb;
  $NumRows = $wpdb->get_var("SELECT COUNT(*) FROM `".$wpdb->prefix."greetmotd` WHERE day=".get_current_day());
  if($NumRows >=1){
    $RandNum = rand(0, $NumRows-1);
    $randomMOTD = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."greetmotd WHERE day=".get_current_day()." order by `id`",ARRAY_A);
    return nl2br($randomMOTD[$RandNum]['motd']);
  }else{
    return " ";
  }
}

function greetmotd_show_motds() {
  global $wpdb;
  $res = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."greetmotd order by `id`");
  echo("<form action='' method='post' id='motds'>");
  wp_nonce_field('update_motds', 'update_motds_nonce');
  echo("<table>");
  foreach($res as $row){
    echo("<tr><td><textarea rows=2 cols=40 name='motd_".$row->id."' id='motd_".$row->id."'>".esc_textarea($row->motd)."</textarea></td>");
	echo("<td><select name='day_".$row->id."' id='day_".$row->id."'><option value=0 ".($row->day == 0?'selected':'').">Monday</option><option value=1 ".($row->day == 1?'selected':'').">Tuesday</option><option value=2 ".($row->day == 2?'selected':'').">Wednesday</option><option value=3 ".($row->day == 3?'selected':'').">Thursday</option><option value=4 ".($row->day == 4?'selected':'').">Friday</option><option value=5 ".($row->day == 5?'selected':'').">Saturday</option><option value=6 ".($row->day == 6?'selected':'').">Sunday</option></select></td>");
    echo("<td><input type='submit' name='Edit[".$row->id."]' value='Update'> &nbsp;");
    echo("<input type='submit' name='Delete[".$row->id."]' value='Delete'></tr>");
  }
  echo("<tr><td><textarea rows=2 cols=40 name='motd_0' id='motd_0'></textarea></td>");
  echo("<td><select name='day_0' id='day_0'><option value=0>Monday</option><option value=1>Tuesday</option><option value=2>Wednesday</option><option value=3>Thursday</option><option value=4>Friday</option><option value=5>Saturday</option><option value=6>Sunday</option></select></td>");
  echo("<td><input type='submit' name='Add' value='Add'></td></tr>");
  echo("</table>");
  echo("</form>");
  return;
}


//Shortcode
function greetmotd_shortcode($atts) {
  return "<div id='motds'><p>".greetmotd_motd()."</p></div>";
}
add_shortcode('motd', 'greetmotd_shortcode');
