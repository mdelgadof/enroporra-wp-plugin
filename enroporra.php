<?php
/**
 * Plugin Name: Enroporra
 * Plugin URI: https://www.enroporra.es/
 * Description: Wordpress plugin with all the logic, data model and assets needed for the famous Enroporra.
 * Version: 1.0
 * Author: Miguel Delgado Fernández
 * Author URI: https://github.com/mdelgadof
 * Text domain: enroporra
 **/
define('ENROPORRA_VERSION','20221202-1');
define('ENROPORRA_PATH',plugin_dir_path(__FILE__));
define('ENROPORRA_PLUGIN_URI',plugin_dir_url(__FILE__));

spl_autoload_register( function ($class_name) {
    $CLASSES_DIR = __DIR__ . DIRECTORY_SEPARATOR . 'model' . DIRECTORY_SEPARATOR;
    $file = $CLASSES_DIR . $class_name . '.php';
    if( file_exists( $file ) ) include $file;  // only include if file exists, otherwise we might enter some conflicts with other pieces of code which are also using the spl_autoload_register function
} );

function enroporra_dependencies() {
    if ( is_admin() && current_user_can( 'activate_plugins' ) &&  !is_plugin_active( 'advanced-custom-fields-pro/acf.php' ) &&  !is_plugin_active( 'advanced-custom-fields/acf.php' ) ) {
        add_action( 'admin_notices', function() {
            ?><div class="error"><p><?php echo sprintf(__('Para activar el plugin Enroporra es necesario tener instalado y activado el plugin %s.','enroporra'),'<a href="/wp-admin/plugin-install.php?tab=plugin-information&plugin=advanced-custom-fields&TB_iframe=true&width=772&height=877" class="thickbox open-plugin-details-modal">Advanced Custom Fields</a>'); ?></p></div><?php
            }
        );

        deactivate_plugins( plugin_basename( __FILE__ ) );

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}
add_action( 'admin_init', 'enroporra_dependencies' );

add_action( 'admin_enqueue_scripts', function ( $hook ) {
    if ( 'edit.php' != $hook && 'post-new.php' != $hook && 'post.php' != $hook) {
        return;
    }
    wp_enqueue_script( 'enroporra-admin-js', plugin_dir_url( __FILE__ ) . 'js/admin.js', array('jquery'), ENROPORRA_VERSION );
    wp_enqueue_style('enroporra-admin-css', plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), ENROPORRA_VERSION );
	wp_localize_script('enroporra-admin-js','global_vars',
		array(
			'pluginUrl' => ENROPORRA_PLUGIN_URI,
			'ajaxUrl' => admin_url( 'admin-ajax.php' ), //url for php file that process ajax request to WP
		)
	);

});

function enroporra_scripts() {
	wp_enqueue_script('jquery');
}
add_action( 'wp_enqueue_scripts', 'enroporra_scripts' );

add_action( 'acf/save_post', function($post_id) {
    if (get_post_type($post_id)=="competition") {
	    $competition = new EP_Competition( $post_id );
	    $competition->updateTeamsOnFixtures();
	    $competition->setCompetitionPoints();
    }
    else if (get_post_type($post_id)=="fixture") {
        $fixture = new EP_Fixture($post_id);
        if ($fixture->getGoals(1)>$fixture->getGoals(2)) $fixture->setWinner("1");
        else if ($fixture->getGoals(1)<$fixture->getGoals(2)) $fixture->setWinner("2");
        else if ($fixture->getTournament()=="groups") $fixture->setWinner("X");
    }
},20,1);

function cmp_table_step1($a,$b) {
	global $teams_for_next_step;
	if ($a["points"]>$b["points"]) return false;
	if ($a["points"]<$b["points"]) return true;
	if (($a["goals_for"]-$a["goals_against"])>($b["goals_for"]-$b["goals_against"])) return false;
	if (($a["goals_for"]-$a["goals_against"])<($b["goals_for"]-$b["goals_against"])) return true;
	if ($a["goals_for"]>$b["goals_for"]) return false;
	if ($a["goals_for"]<$b["goals_for"]) return true;
	$teams_for_next_step[]=$a["label"];
	$teams_for_next_step[]=$b["label"];
	return false;
}

function cmp_scorers($a,$b) {
    return ($a["minute"]>$b["minute"]);
}

function cmp_fixtures($a,$b) {
    return ($a->getRawDate()>$b->getRawDate());
}

// Register post types
include ( plugin_dir_path( __FILE__ )."custom-post-types.php");

// Manage admin
include ( plugin_dir_path( __FILE__ )."admin.php");

// Metaboxes
include ( plugin_dir_path( __FILE__ )."metaboxes.php");

// Other functions
include ( plugin_dir_path( __FILE__ )."inc/spanish-names.php");
