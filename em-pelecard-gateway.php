<?php
/**
 * Plugin Name: Event Manager Pelecard Gateway
 * Author: Guy Aviv
 * Version: 0.1
 * Description: Add Pelecard payment gateway to Events-Manager Pro
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

define( 'EM_PELECARD_VERSION', '0.1' );
define('EM_PELE_NAME','Event Manager Pelecard Gateway');

/*
 * On plugin activation: check if Events Manager pro is installed. if not exit with message
 */
function em_pele_gw_activation() {
    if(!is_plugin_active('events-manager-pro/events-manager-pro.php')) {
        exit('this plugin should only be used when events-manager-pro is active');
    }
}
register_activation_hook( __FILE__, 'em_pele_gw_activation' );


//// Start the gateway class - extend EM Gateway class ////

/**
 * events manager pro is a pre-requirements on activation
 */
function em_pelecard_prereq() {
    ?> <div class="error"><p><?php _e('Please ensure you have <a href="http://eventsmanagerpro.com/">Events Manager Pro</a> installed, as this is a requirement for the pelecard add-on.','em-stripe'); ?></p>
    </div>
    <?php
}

function em_pelecard_register() {
    //check that EM Pro is installed
    if( ! defined( 'EMP_VERSION' ) ) {
        add_action( 'admin_notices', 'em_pelecard_prereq' );
        return false; //don't load plugin further
    }

    if (class_exists('EM_Gateways')) {
        require_once( plugin_dir_path( __FILE__ ) . 'em.gateway.pelecard.php' );
        require_once( plugin_dir_path( __FILE__ ) . 'includes/class-em-pelecard-api.php' );

        EM_Gateways::register_gateway('emp_pelecard', 'EM_Gateway_Pelecard');
    }
    // LOCALIZATION
//    load_plugin_textdomain('em-stripe', false, dirname( plugin_basename( __FILE__ ) ).'/languages');
}
add_action( 'plugins_loaded', 'em_pelecard_register', 1000);
