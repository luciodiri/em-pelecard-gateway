<?php
/**
 * Main Gateway class
 * Extend EMP Gateway and handle payments and verification
 */

class EM_Gateway_Pelecard extends EM_Gateway {

    var $gateway = 'emp_pelecard';
    var $title = 'Pelecard';
    var $status = 4;
    var $status_txt = 'Processing (Pelecrad)';
    var $payment_return = true; // flag for processing verification reqyests fro GW
    var $button_enabled = false; //we can's use a button here
    var $supports_multiple_bookings = false;
    var $registered_timer = 0;
    var $pelecard_txn_validation_url = "https://gateway20.pelecard.biz/PaymentGW/ValidateByUniqueKey";

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
     * Outputs some JavaScript during the em_gateway_js action, which is run inside a script html tag
     */
    function em_booking_js(){
        include(dirname(__FILE__).'/assets/gateway.emp.pelecard.js');
    }

    /**
     * Outputs custom Pelecard setting fields in the Admin settings page
     */
    function mysettings() {
        global $EM_options;
        ?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row"><?php _e('Success Message', 'emp-pelecard') ?></th>
                <td><input type="text" name="_booking_feedback" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback" )); ?>" style='width: 40em;' />
                    <br />
                    <em>
                        <?php _e('The message that is shown to a user when a booking is successful and payment has been taken. only if embedded payments are enabled','emp-pelecard'); ?>
                    </em></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Success Free Message', 'em-pelecard') ?></th>
                <td><input type="text" name="_booking_feedback_free" value="<?php esc_attr_e(get_option('em_'. $this->gateway . "_booking_feedback_free" )); ?>" style='width: 40em;' />
                    <br />
                    <em>
                        <?php _e('If some cases if you allow a free ticket (e.g. pay at gate) as well as paid tickets, this message will be shown and the user will not be charged.','emp-pelecard'); ?>
                    </em></td>
            </tr>
            </tbody>
        </table>
        <h3><?php echo sprintf(__('%s Options','dbem'),'Pelecard')?></h3>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row"><?php _e('Mode', 'em-pelecard') ?></th>
                <td>
                    <select name="_pelecard_mode">
                        <?php $selected = get_option('em_'.$this->gateway.'_pelecard_mode'); ?>
                        <option value="1" <?php echo ($selected) ? 'selected="selected"':''; ?>><?php esc_html_e_emp('Live','em-pelecard'); ?></option>
                        <option value="0" <?php echo (!$selected) ? 'selected="selected"':''; ?>><?php esc_html_e_emp('Sandbox','em-pelecard'); ?></option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Pelecard Terminal #', 'em-pelecard') ?></th>
                <td>
                    <input type="text" name="_pelecard_terminal" value="<?php echo esc_html(get_option('em_'. $this->gateway . '_pelecard_terminal')); ?>"<br />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Pelecard User', 'em-pelecard') ?></th>
                <td>
                    <input type="text" name="_pelecard_user" value="<?php echo esc_html(get_option('em_'. $this->gateway . '_pelecard_user')); ?>"<br />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Pelecard Password', 'em-pelecard') ?></th>
                <td>
                    <input type="password" name="_pelecard_pwd" value="<?php echo esc_html(get_option('em_'. $this->gateway . '_pelecard_pwd')); ?>"<br />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e('Pelecard thank you page', 'em-pelecard') ?></th>
                <td>
                    <input type="text" name="_pelecard_thank_you_page" value="<?php echo esc_html(get_option('em_'. $this->gateway . '_pelecard_thank_you_page')); ?>"<br />
                    <em>Relative URL path: www.yoursite.com/[insert path here]</em>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Save settings in admin section
     */
    function update() {

        parent::update();

        $gateway_options = array(
            $this->gateway . "_booking_feedback" => wp_kses_data($_REQUEST[ '_booking_feedback' ]),
            $this->gateway . "_booking_feedback_free" => wp_kses_data($_REQUEST[ '_booking_feedback_free' ]),
            $this->gateway . "_pelecard_terminal" => wp_kses_data($_REQUEST[ '_pelecard_terminal' ]),
            $this->gateway . "_pelecard_user" => wp_kses_data($_REQUEST[ '_pelecard_user' ]),
            $this->gateway . "_pelecard_pwd" => wp_kses_data($_REQUEST[ '_pelecard_pwd' ]),
            $this->gateway . "_pelecard_thank_you_page" => wp_kses_data($_REQUEST[ '_pelecard_thank_you_page' ]),
            $this->gateway . "_pelecard_mode" => wp_kses_data($_REQUEST[ '_pelecard_mode' ])
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
 * Do the booking actions from here!
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
                if(!$pelecard_url) {
                    $return['message'] = "הזמנתכם נרשמה אך היתה בעיה בהפניה לתשלום. אנא פנו למנהל האתר להשלמת התהליך";
                }
                $pelecard_vars = array( // add other pelecard vars here if you need
                    'emp_pelecard_url'=>$pelecard_url
                );
                $return = array_merge($return, $pelecard_vars);
            }else{
                //returning a free message
                $return['message'] = get_option('em_'. $this->gateway . "_booking_feedback_free" );
            }
        }
        return $return; //remember this, it's a filter!
    }

    /**
     * Hook for handling reply from gateway (reply url defined @get_payment_return_url)
     * //      Pelecard return values example:
    //        &em_payment_gateway (action) =emp_pelecard
    //        &PelecardTransactionId=079ef264-b502-4a17-ae13-6e5b15df1736
    //        &PelecardStatusCode=000
    //        &ApprovalNo=8782
    //        &Token=
    //        &ConfirmationKey=80fb1de073cdb7301012ff10517ee8c0
    //        &ParamX= booking_id:event_id
    //        &UserKey=
    //        em_ajax = true
     */
    function handle_payment_return() {

        if($_REQUEST[em_payment_gateway] == 'emp_pelecard') {

            // get params and validate transaction with Pelecard
            $booking_details = explode(':',$_REQUEST['ParamX']);
            $pelecard_input = array(
                "status_code" => $_REQUEST[PelecardStatusCode],
                "approval_number" => $_REQUEST[ApprovalNo],
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
//                $user_id = $EM_Booking->person_id;

                // process Pelecard response
                $this->handle_payment_status($EM_Booking, $pelecard_input, $price);
            }
        }
    }

    /**
     * Process Gateway response. record the transaction and approve booking
     * @param $EM_Booking
     * @param $pelecard_input
     * @param $price
     */
    function handle_payment_status($EM_Booking, $pelecard_input, $price) {
        $currency = get_option('dbem_bookings_currency','ILS');
        $timestamp = $timestamp = date('Y-m-d H:i:s');
        $txn_id = $pelecard_input["approval_number"];
        $status = "Completed";
        $note = 'Txn id:' . $pelecard_input["transaction_id"];

        $this->record_transaction($EM_Booking, $price, $currency, $timestamp, $txn_id, $status, $note);

        // Optional: validate if the full amount was paid
        //        if( $price >= $EM_Booking->get_price() && (!get_option('em_'.$this->gateway.'_manual_approval', false) || !get_option('dbem_bookings_approval')) ){
        //            $EM_Booking->approve(true, true); //approve and ignore spaces
        //        }else{
        //            // do something if pp payment not enough
        //            $EM_Booking->set_status(4); //Set back to normal "pending"
        //        }

        $EM_Booking->approve(true, true); //approve and ignore spaces
        do_action('em_payment_processed', $EM_Booking, $this);

        $location = site_url( get_option('em_'. $this->gateway . '_pelecard_thank_you_page'), 'thank-you' );
        wp_safe_redirect($location);

        exit();
    }

    /**
     * When transaction confirmation arrives from pelecard.
     * validate that the txn is authentic
     * @param $pelecard_input
     * @param $price
     * @return bool
     */
    private function validate_pelecard_ipn($pelecard_input,$price) {

        $request = array(
            "ConfirmationKey" => $pelecard_input["confirmation_key"],
            "UniqueKey" => $pelecard_input["transaction_id"],
            "TotalX100" => $price
        );
        $response = wp_remote_post(
            $this->pelecard_txn_validation_url,
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
            EM_Pro::log( array('PelecardIpnValidationRequestError', 'WP_Error'=> $response, 'Transaction details' => $pelecard_input) );
        }
        $validation_response = json_decode( wp_remote_retrieve_body( $response ), true );
        if ($validation_response == 1) {
           return true;
        } else {
            // validation failed. log and notify
            EM_Pro::log( array('PelecardIpnValidationFailed', 'WP_Error'=> $response, 'Transaction details' => $pelecard_input, "Validation_response" => $validation_response ) );
        }
    }

    /*
     * Get a custom URL + txn ID from pelecrad for the txn
     * @param Object $EM_Booking
     * @return String|bool URL
     */
    private function get_pelecard_txn_init_url($EM_Booking) {
        $args = array(
            "terminal" => get_option('em_'. $this->gateway . '_pelecard_terminal'),
            "user" => get_option('em_'. $this->gateway . '_pelecard_user'),
            "password" => get_option('em_'. $this->gateway . '_pelecard_pwd'),
            'GoodURL' => $this->get_payment_return_url(),
            "Currency" => 1, // always ILS
            "total" => $EM_Booking->booking_price * 100, //price in cents
            // use ParamX to identify the booking when confirmation returns
            'ParamX' => $EM_Booking->booking_id.':'.$EM_Booking->event_id
        );
        $response = EM_Pelecard_Api::request($args);
        if($response) {
            return $response['URL'];
        } else {
            // could not get url, request details are already logged
            return false;
        }

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
     * Use this if you want to edit the booking form itself.
     */
    function booking_form() {

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