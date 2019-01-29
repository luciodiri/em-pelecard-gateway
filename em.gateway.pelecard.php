<?php
/**
 * Created by PhpStorm.
 * User: Shushka
 * Date: 1/18/2019
 * Time: 10:30 AM
 */

class EM_Gateway_Pelecard extends EM_Gateway {

    var $gateway = 'emp_pelecard';
    var $title = 'Pelecard';
    var $status = 4;
    var $status_txt = 'Processing (Pelecrad)';
    var $payment_return = true;
    var $button_enabled = false; //we can's use a button here
    var $supports_multiple_bookings = false;
    var $registered_timer = 0;

    function __construct() {
        parent::__construct();
        if($this->is_active()) {
            //Force SSL for booking submissions, since we have card info
            if(get_option('em_'.$this->gateway.'_mode') == 'live'){ //no need if in sandbox mode
                $this->testmode="no";
            } else {
                $this->testmode="yes";
            }
            add_action('em_booking_js', array(&$this,'em_booking_js'));
         add_action('em_template_my_bookings_header',array(&$this,'say_thanks')); //say thanks on my_bookings page
            add_filter('em_bookings_table_booking_actions_4', array(&$this,'bookings_table_actions'),1,2);
        }
    }

    /*
 * --------------------------------------------------
 * Gateway Settings Functions
 * --------------------------------------------------
 */
    /**
     * Outputs some JavaScript during the em_gateway_js action, which is run inside a script html tag, located in assets/gateway.emp.pelecard.js
     */
    function em_booking_js(){
        include(dirname(__FILE__).'/assets/gateway.emp.pelecard.js');
    }

    /**
     * Outputs custom Pelecard setting fields in the settings page
     */
    function mysettings() {
        global $EM_options;
        ?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><?php _e('Success Message', 'em-pelecard') ?></th>
                <td><input type="text" name="_booking_feedback" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback" )); ?>" style='width: 40em;' />
                    <br />
                    <em>
                        <?php _e('The message that is shown to a user when a booking is successful and payment has been taken.','em-stripe'); ?>
                    </em></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Success Free Message', 'em-pelecard') ?></th>
                <td><input type="text" name="_booking_feedback_free" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback_free" )); ?>" style='width: 40em;' />
                    <br />
                    <em>
                        <?php _e('If some cases if you allow a free ticket (e.g. pay at gate) as well as paid tickets, this message will be shown and the user will not be charged.','em-pelecard'); ?>
                    </em></td>
            </tr>
            </tbody>
        </table>
        <h3><?php echo sprintf(__('%s Options','dbem'),'Pelecard')?></h3>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><?php _e('Pelecard Currency', 'em-pelecard') ?></th>
                <td><?php echo esc_html(get_option('dbem_bookings_currency','ILS')); ?><br />
                    <i><?php echo sprintf(__('Set your currency in the <a href="%s">settings</a> page.','dbem'),EM_ADMIN_URL.'&amp;page=events-manager-options#bookings'); ?></i></td>
            </tr>
<!--            <tr valign="top">-->
<!--                <th scope="row">--><?php //_e('Mode', 'em-stripe'); ?><!--</th>-->
<!--                <td><select name="_mode">-->
<!--                        --><?php //$selected = get_option('em_'.$this->gateway.'_mode'); ?>
<!--                        <option value="sandbox" --><?php //echo ($selected == 'sandbox') ? 'selected="selected"':''; ?><!-->-->
<!--                            --><?php //_e('Sandbox','emp-stripe'); ?>
<!--                        </option>-->
<!--                        <option value="live" --><?php //echo ($selected == 'live') ? 'selected="selected"':''; ?><!-->-->
<!--                            --><?php //_e('Live','emp-stripe'); ?>
<!--                        </option>-->
<!--                    </select></td>-->
<!--            </tr>-->
<!--            <tr valign="top">-->
<!--                <th scope="row">--><?php //_e('Test Secret Key', 'emp-stripe') ?><!--</th>-->
<!--                <td><input type="text" name="_test_secret_key" value="--><?php //esc_attr_e(get_option( 'em_'. $this->gateway . "_test_secret_key", "" )); ?><!--" style='width: 40em;' /></td>-->
<!--            </tr>-->
<!--            <tr valign="top">-->
<!--                <th scope="row">--><?php //_e('Test Publishable Key', 'emp-stripe') ?><!--</th>-->
<!--                <td><input type="text" name="_test_publishable_key" value="--><?php //esc_attr_e(get_option( 'em_'. $this->gateway . "_test_publishable_key", "" )); ?><!--" style='width: 40em;' /></td>-->
<!--            </tr>-->
<!--            <tr valign="top">-->
<!--                <th scope="row">--><?php //_e('Live Secret Key', 'emp-stripe') ?><!--</th>-->
<!--                <td><input type="text" name="_live_secret_key" value="--><?php //esc_attr_e(get_option( 'em_'. $this->gateway . "_live_secret_key", "" )); ?><!--" style='width: 40em;' /></td>-->
<!--            </tr>-->
<!--            <tr valign="top">-->
<!--                <th scope="row">--><?php //_e('Live Publishable Key', 'emp-stripe') ?><!--</th>-->
<!--                <td><input type="text" name="_live_publishable_key" value="--><?php //esc_attr_e(get_option( 'em_'. $this->gateway . "_live_publishable_key", "" )); ?><!--" style='width: 40em;' /></td>-->
<!--            </tr>-->
<!--            <tr valign="top">-->
<!--                <th scope="row">--><?php //_e('Debug Mode', 'em-stripe'); ?><!--</th>-->
<!--                <td><select name="_debug">-->
<!--                        <option value="no" --><?php //if (get_option('em_'. $this->gateway . "_debug" ) == 'no') echo 'selected="selected"'; ?><!-->-->
<!--                            --><?php //_e('Off', 'em-stripe') ?>
<!--                        </option>-->
<!--                        <option value="yes" --><?php //if (get_option('em_'. $this->gateway . "_debug" ) == 'yes') echo 'selected="selected"'; ?><!-->-->
<!--                            --><?php //_e('On', 'em-stripe') ?>
<!--                        </option>-->
<!--                    </select></td>-->
<!--            </tr>-->
<!--            <tr valign="top">-->
<!--                <th scope="row">--><?php //_e('Manually approve completed transactions?', 'em-stripe') ?><!--</th>-->
<!--                <td><input type="checkbox" name="_manual_approval" value="1" --><?php //echo (get_option('em_'. $this->gateway . "_manual_approval" )) ? 'checked="checked"':''; ?><!-- />-->
<!--                    <br />-->
<!--                    <em>-->
<!--                        --><?php //_e('By default, when someone pays for a booking, it gets automatically approved once the payment is confirmed. If you would like to manually verify and approve bookings, tick this box.','em-stripe'); ?>
<!--                    </em><br />-->
<!--                    <em>--><?php //echo sprintf(__('Approvals must also be required for all bookings in your <a href="%s">settings</a> for this to work properly.','em-stripe'),EM_ADMIN_URL.'&amp;page=events-manager-options'); ?><!--</em></td>-->
<!--            </tr>-->
            </tbody>
        </table>
        <?php
    }

    /*
     * Run when saving settings, saves the settings available in EM_Gateway_Authorize_AIM::mysettings()
     */

    function update() {

        parent::update();

        $gateway_options = array(
//            $this->gateway . "_option_name" => $_REQUEST[ 'em_emp_stripe_option_name' ],
//            $this->gateway . "_mode" => $_REQUEST[ '_mode' ],
//            $this->gateway . "_test_publishable_key" => $_REQUEST[ '_test_publishable_key' ],
//            $this->gateway . "_test_secret_key" => $_REQUEST[ '_test_secret_key' ],
//            $this->gateway . "_live_publishable_key" => $_REQUEST[ '_live_publishable_key' ],
//            $this->gateway . "_live_secret_key" => ($_REQUEST[ '_live_secret_key' ]),
//            $this->gateway . "_email_customer" => ($_REQUEST[ '_email_customer' ]),
//            $this->gateway . "_header_email_receipt" => $_REQUEST[ '_header_email_receipt' ],
//            $this->gateway . "_footer_email_receipt" => $_REQUEST[ '_footer_email_receipt' ],
//            $this->gateway . "_manual_approval" => $_REQUEST[ '_manual_approval' ],
            $this->gateway . "_booking_feedback" => wp_kses_data($_REQUEST[ '_booking_feedback' ]),
            $this->gateway . "_booking_feedback_free" => wp_kses_data($_REQUEST[ '_booking_feedback_free' ]),
//            $this->gateway . "_debug" => $_REQUEST['_debug' ]
        );

        foreach($gateway_options as $key=>$option){
            update_option('em_'.$key, stripslashes($option));
        }

        //default action is to return true
        return true;

    }

    /*
 * --------------------------------------------------
 * Do the booking from here!!!
 * Booking UI - modifications to booking pages and tables containing Paypal Pro bookings
 * --------------------------------------------------
 */
    /**
     * Triggered by the em_booking_add_yourgateway action, hooked in EM_Gateway. Overrides EM_Gateway to account for non-ajax bookings (i.e. broken JS on site).
     * @param EM_Event $EM_Event
     * @param EM_Booking $EM_Booking
     * @param boolean $post_validation
     */
    function booking_add($EM_Event,$EM_Booking, $post_validation = false){
        parent::booking_add($EM_Event, $EM_Booking, $post_validation);
        if( !defined('DOING_AJAX') ){ //we aren't doing ajax here, so we should provide a way to edit the $EM_Notices ojbect.
            add_action('option_dbem_booking_feedback', array(&$this, 'booking_form_feedback_fallback'));
        }
    }

    /**
     * Intercepts return JSON and adjust feedback messages when booking with this gateway. This filter is added only when the em_booking_add function is triggered by the em_booking_add filter.
     * @param array $return
     * @param EM_Booking $EM_Booking
     * @return array
     */
    function booking_form_feedback( $return, $EM_Booking ){
        //Double check $EM_Booking is an EM_Booking object and that we have a booking awaiting payment.
         if( is_object($EM_Booking) && $this->uses_gateway($EM_Booking) ){
            if( !empty($return['result']) && $EM_Booking->get_price() > 0 && $EM_Booking->booking_status == $this->status ){

                $return['message'] = "הנכם מועברים לביצוע תשלום מאובטח באתר פלאכרד";
                $pelecard_url = $this->get_pelecard_txn_init_url($EM_Booking);
                $pelecard_vars = array(
                    'emp_pelecard_url'=>$pelecard_url
                );
                $return = array_merge($return, $pelecard_vars);
            }else{
                //returning a free message
//                $return['message'] = get_option('em_paypal_booking_feedback_free');
                $return['message'] = "something went wrong";
            }
        }
        return $return; //remember this, it's a filter!
    }

    function handle_payment_return() {
//      Pelecard return values example:
//        &em_payment_gateway (action) =emp_pelecard
//        &PelecardTransactionId=079ef264-b502-4a17-ae13-6e5b15df1736
//        &PelecardStatusCode=000
//        &ApprovalNo=8782
//        &Token=
//        &ConfirmationKey=80fb1de073cdb7301012ff10517ee8c0
//        &ParamX= booking_id:event_id
//        &UserKey=
//        em_ajax = true
        if($_REQUEST[em_payment_gateway] == 'emp_pelecard') {
            $booking_details = explode(':',$_REQUEST['ParamX']);
            $pelecard_input = array(
                "status_code" => $_REQUEST[PelecardStatusCode],
                "transaction_id" => $_REQUEST["PelecardTransactionId"],
                "confirmation_key" => $_REQUEST["ConfirmationKey"],
                "booking_id" => $booking_details[0],
                "event_id" => $booking_details[1]
            );
            $EM_Booking = em_get_booking($pelecard_input["booking_id"]);
            $price = $EM_Booking->get_price();
            $this->validate_pelecard_ipn($pelecard_input,$price*100);
            // check if booking exists
            if( !empty($EM_Booking->booking_id) && count($booking_details) == 2 ){
                //booking exists
                $EM_Booking->manage_override = true; //since we're overriding the booking ourselves.
                $user_id = $EM_Booking->person_id;

                // process PayPal response
                $this->handle_payment_status($EM_Booking, $pelecard_input, $price);
            }
        }
    }

    function handle_payment_status($EM_Booking, $pelecard_input, $price) {
        $currency = "USD";
        $timestamp = $timestamp = date('Y-m-d H:i:s');
        $txn_id = substr($pelecard_input["transaction_id"],0,29);
        $status = "Completed";
        $note = 'full txn id:' . $pelecard_input["transaction_id"];

        $this->record_transaction($EM_Booking, $price, $currency, $timestamp, $txn_id, $status, $note);
        $EM_Booking->approve(true, true);
        do_action('em_payment_processed', $EM_Booking, $this); // redirect ?
        $location = site_url("/אישור-תשלום/"); // TODO: use url from gateway options
        wp_safe_redirect($location);
        exit();
    }

    private function validate_pelecard_ipn($pelecard_input,$price) {
        $validation_url = "https://gateway20.pelecard.biz/PaymentGW/ValidateByUniqueKey";
        $request = array(
            "ConfirmationKey" => $pelecard_input["confirmation_key"],
            "UniqueKey" => $pelecard_input["transaction_id"],
            "TotalX100" => $price
        );
        $response = wp_remote_post(
            $validation_url,
            array(
                'method' 		=> "POST",
                'body' 			=>  json_encode( $request ),
                'headers'       =>  array(
                    'Content-Type: application/json; charset=UTF-8'
                )
            )
        );
        if ( is_wp_error( $response ) ) {
            // log api error and output error
        }
        $validation_response = json_decode( wp_remote_retrieve_body( $response ), true );
        if ($validation_response == 1) {
           return true;
        } else {
            // validation failed. log and notify
        }
    }

    /*
     * Get a custom URL + txn ID from pelecrad for the txn
     */
    private function get_pelecard_txn_init_url($EM_Booking) {
        $args = array(
            "terminal" => "0962210",
            "user" => "testpelecard3",
            "password" => "Q3EJB8Ah",
            'GoodURL' => $this->get_payment_return_url(),
            "Currency" => 1,
            "total" => $EM_Booking->booking_price * 100, //price in cents
            // use ParamX to identify the booking when confirmation returns
            'ParamX' => $EM_Booking->booking_id.':'.$EM_Booking->event_id
        );
        $response = EM_Pelecard_Api::request($args);
        return $response['URL'];
    }

    /**
     * Returns the notification URL which gateways sends return messages to, e.g. notifying of payment status.
     *
     * Your URL would correspond to http://yoursite.com/admin-ajax.php?action=em_payment&em_payment_gateway=yourgateway
     * @return string
     */
    function get_payment_return_url(){
        return admin_url('admin-ajax.php?action=em_payment&em_payment_gateway='.$this->gateway);
    }

    /**
     * Outputs custom content and credit card information.
     */

    function booking_form() {
        // initialize transaction with pelecrad:
        // send JSON with gateway +txn parmas
        // get url + txn id
        // show payment iframe
        // details: terminal: 0962210 | user: testpelecard3 | pwd: Q3EJB8Ah
//            {
//            "terminal": "1234567",
//            "user": "JohnDoe",
//            "password": "132456789",
//            "TransactionId":"1a5b1c-1d1f6g-9h8j"
//            }
//        echo "Pelecrad payment iframe should be here: ";
//
//        $args = array(
//            "terminal" => "0962210",
//            "user" => "testpelecard3",
//            "password" => "Q3EJB8Ah",
//            'GoodURL' => "dev.iyengar-yoga.org.il/events/אירוע-ניסיון/",
//            "Currency" => 1,
//            "total" => 10000
//        );
//        $response = EM_Pelecard_Api::request($args);
////        echo ($response['URL'] . "<br/><br/>");
////        echo ($response["ConfirmationKey"]  . "<br/><br/>");
//        $this->get_iframe($response);
    }

    /**
     * Outputs the payment iframe.
     *
     * @param  array $api_response
     * @param  int   $order_id
     */
    protected function get_iframe( $api_response, $order_id = 0 ) {
        if ( isset( $api_response['URL'], $api_response['Error']['ErrCode'] ) && 0 === $api_response['Error']['ErrCode'] ) {
            printf( '<div id="pelecard-iframe-container"><iframe height="600px" src="%s" frameBorder="0"></iframe></div>', $api_response['URL'] );
        } elseif ( isset( $api_response['Error']['ErrCode'], $api_response['Error']['ErrMsg'] ) ) {
            echo("Error: " . $api_response['Error']['ErrCode']);
//            wc_add_notice( sprintf( __( 'Pelecard error: %s', 'woo-pelecard-gateway' ), $api_response['Error']['ErrMsg'] ), 'error' );
//            wc_get_logger()->error( sprintf( 'Error Code %s: %s', $api_response['Error']['ErrCode'], $api_response['Error']['ErrMsg'] ) );
        }
    }

    function say_thanks() {
        if (!empty($_REQUEST['thanks'])) {
            echo "<div class='em-booking-message em-booking-message-success'>" . get_option('em_' . $this->gateway . '_booking_feedback_completed') . '</div>';
        }
    }
    /**
     * Adds relevant actions to booking shown in the bookings table
     * @param EM_Booking $EM_Booking
     */
    function bookings_table_actions( $actions, $EM_Booking ){
        return array(
            'approve' => '<a class="em-bookings-approve em-bookings-approve-offline" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_approve', 'booking_id'=>$EM_Booking->booking_id)).'">'.esc_html__emp('Approve','events-manager').'</a>',
            'delete' => '<span class="trash"><a class="em-bookings-delete" href="'.em_add_get_params($_SERVER['REQUEST_URI'], array('action'=>'bookings_delete', 'booking_id'=>$EM_Booking->booking_id)).'">'.esc_html__emp('Delete','events-manager').'</a></span>',
            'edit' => '<a class="em-bookings-edit" href="'.em_add_get_params($EM_Booking->get_event()->get_bookings_url(), array('booking_id'=>$EM_Booking->booking_id, 'em_ajax'=>null, 'em_obj'=>null)).'">'.esc_html__emp('Edit/View','events-manager').'</a>',
        );
    }
}