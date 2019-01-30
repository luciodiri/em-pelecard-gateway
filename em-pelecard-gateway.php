<?php
/**
 * Plugin Name: Event Manager Pro Pelecard Gateway
 * Author: Luciodiri
 * Version: 1.0
 * Description: Add Pelecard payment gateway to Events-Manager Pro
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

define( 'EM_PELECARD_VERSION', '0.1' );
define('EM_PELE_NAME','Event Manager Pro Pelecard Gateway');

/**
 * On plugin activation: check if Events Manager pro is installed. if not exit with message
 */
function em_pele_gw_activation() {
    if(!is_plugin_active('events-manager-pro/events-manager-pro.php')) {
        exit('this plugin should only be used when events-manager-pro is active');
    }
}
register_activation_hook( __FILE__, 'em_pele_gw_activation' );

/**
 * Register plugin. check that events manager pro is active
 * and register the gateway to EM_Gateways
 */
function em_pelecard_register() {
    //check that EM Pro is installed
    if( ! defined( 'EMP_VERSION' ) ) {
        add_action( 'admin_notices', 'em_pelecard_prereq' );
        return false; //don't load plugin further
    }

    if (class_exists('EM_Gateways')) {
        require_once( plugin_dir_path( __FILE__ ) . 'class.emp.gateway.pelecard.php' );
        require_once( plugin_dir_path( __FILE__ ) . 'includes/class.emp.pelecard-api.php' );

        EM_Gateways::register_gateway('emp_pelecard', 'EM_Gateway_Pelecard');
    }
    load_plugin_textdomain("emp-pelecard");
}

function em_pelecard_prereq() {
    ?> <div class="error"><p><?php _e('Please ensure you have <a href="http://eventsmanagerpro.com/">Events Manager Pro</a> installed, as this is a requirement for the pelecard add-on.','em-stripe'); ?></p>
    </div>
    <?php
}

add_action( 'plugins_loaded', 'em_pelecard_register', 1000);
