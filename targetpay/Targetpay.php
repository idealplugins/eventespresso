<?php

/**
 * TargetPay Class
 *
 * Author 		Yellow Melon B.V.
 * @package		Event Espresso TargetPay Gateway
 * @category	Library
 */

class EE_Targetpay extends Espresso_PaymentGateway {

	public $gateway_version = '1.0';

	/**
	 * Initialize the TargetPay gateway
	 *
	 * @param none
	 * @return void
	 */

	public function __construct() {
		parent::__construct();
		$this->gatewayUrl = '';
		$this->ipnLogFile = EVENT_ESPRESSO_UPLOAD_DIR . 'logs/targetpay.ipn_results.log';
		}

	/**
	 * Enables the test mode
	 *
	 * @param none
	 * @return none
	 */

	public function enableTestMode() {
		$this->gatewayUrl = '';
		$this->testMode = TRUE;
	}

	public function logErrors($errors) {
		if ($this->logIpn) {
			// Timestamp
			$text = '[' . date('m/d/Y g:i A') . '] - ';

			// Success or failure being logged?
			$text .= "Errors from IPN Validation:\n";
			$text .= $errors;

			// Write to log
			file_put_contents($this->ipnLogFile, $text, FILE_APPEND)
							or do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, 'could not write to targetpay log file');
		}
	}

	/**
	 * Validate the IPN notification
	 *
	 * @param none
	 * @return boolean
	 */

	public function validateIpn() {
		global $org_options, $wpdb;

		do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');

		/* Get transaction data from database */

		$SQL = "SELECT * FROM `".$wpdb->base_prefix."events_targetpay` WHERE `order_id`=%s";
		$transaction = $wpdb->get_row( $wpdb->prepare( $SQL, $_GET["r_id"] ));
		foreach ($transaction as $key => $value) {
			$this->ipnData["$key"] = $value;
            }

        /* Verify payment */

		$targetpay_settings = get_option('event_espresso_targetpay_settings');
        $rtlo = empty($targetpay_settings['rtlo']) ? '' : $targetpay_settings['rtlo'];
	    if (!$rtlo) {
			$rtlo = 93929; // Default TargetPay
	        }                                      

		$targetPay = new TargetPayCore ($transaction->method, $rtlo,  "71c0ede08d631e269dac7e2b064f92c5", "nl", ($targetpay_settings['use_sandbox']));
        $payResult = $targetPay->checkPayment ($transaction->targetpay_txid);

        if (!$payResult) {
			// echo $targetPay->getErrorMessage();
			// $this->logErrors($targetPay->getErrorMessage());
			// $SQL = "UPDATE `".$wpdb->base_prefix."events_targetpay` SET `targetpay_response` = '".$targetPay->getErrorMessage()."' WHERE `order_id`=%s";
			// $wpdb->get_results( $wpdb->prepare( $SQL, $_GET["r_id"] ));
			// return false;

            } else {

            /* Update temptable */

            $SQL = "UPDATE `".$wpdb->base_prefix."events_targetpay` SET `paid` = now() WHERE `order_id`=%s";
			$wpdb->get_results( $wpdb->prepare( $SQL, $_GET["r_id"] ));

	   		$this->logResults(true);
            return true;
            }

	}

}
