<?php

function espresso_display_targetpay($payment_data) {
	global $wpdb;
	extract($payment_data);

    if (!class_exists('TargetPayCore')) {
		include_once ('targetpay.class.php');
        }

	include_once ('Targetpay.php');
	$myTargetpay = new EE_Targetpay();

	global $org_options, $gateway_name;

	$gateway_name = "TargetPay";

	$targetpay_settings = get_option('event_espresso_targetpay_settings');
	$rtlo = empty($targetpay_settings['rtlo']) ? '' : $targetpay_settings['rtlo'];

    if (!$rtlo) {
		$rtlo = 93929; // Default TargetPay
        }

	$targetpay_cur = empty($targetpay_settings['currency_format']) ? '' : $targetpay_settings['currency_format'];
	$no_shipping = isset($targetpay_settings['no_shipping']) ? $targetpay_settings['no_shipping'] : '0';
	$use_sandbox = $targetpay_settings['use_sandbox'];
	if ($use_sandbox) {
		$myTargetpay->enableTestMode();
		}

	do_action('action_hook_espresso_use_add_on_functions');

	// Get attendee_session

	$SQL = "SELECT attendee_session FROM " . EVENTS_ATTENDEE_TABLE . " WHERE id=%d";
	$session_id = $wpdb->get_var( $wpdb->prepare( $SQL, $attendee_id ));

	// Now get all registrations for that session

	$SQL = "SELECT a.final_price, a.orig_price, a.quantity, ed.event_name AS event_name, a.price_option, a.fname, a.lname ";
	$SQL .= " FROM " . EVENTS_ATTENDEE_TABLE . " a ";
	$SQL .= " JOIN " . EVENTS_DETAIL_TABLE . " ed ON a.event_id=ed.id ";
	$SQL .= " WHERE attendee_session=%s ORDER BY a.id ASC";

	$items = $wpdb->get_results( $wpdb->prepare( $SQL, $session_id ));

    $totalAmount = 0;
    $lastEvent = "";

	foreach ( $items as $key => $item ) {
    	$lastEvent = $item->event_name;
        $totalAmount += $item->final_price * absint($item->quantity);   
//			$adjustment = abs( $item->orig_price - $item->final_price );
		}


	$home = home_url();
	$targetPay = new TargetPayCore ("AUTO", $rtlo,  "71c0ede08d631e269dac7e2b064f92c5", "nl", ($targetpay_settings['use_sandbox']));

    if (!$_POST["bank"]) {

		$banks = false;
		$temp = $targetPay->getBankList();

		echo "<form method=\"POST\" name=\"gateway_form\" target=\"_blank\">";

		if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/targetpay/targetpay-logo.png")) {
			$button_url = EVENT_ESPRESSO_GATEWAY_URL . "/targetpay/targetpay-logo.png";
		} else {
			$button_url = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/targetpay/targetpay-logo.png";
		}

        foreach ($_POST as $key => $value) {
        	if (!is_array($value)) {
        		echo "<input type=\"hidden\" name=\"".htmlspecialchars($key)."\" value=\"".htmlspecialchars($value)."\">";
        		}
        	}
        	
        echo "<select name=\"bank\" style=\"border: 1px solid #ccc; border-radius: 3px; font-family: inherit; padding: 0.428571429rem; margin: 0 1em 0 0\">";
		foreach ($temp as $key=>$value) {
			echo '<option value="'.$key.'">'.$value.'</option>';
			}

        echo "</select>";
        echo "<input type=\"submit\" value=\" ". __('Pay', 'targetpay') ." \">";

        echo "<a href=\"https://www.targetpay.com\" target=\"_blank\" title=\"TargetPay.com\"><img src=\"". $button_url ."\" style=\"position:relative; left: 4em; top: .8em; border: none; box-shadow: none\"></a>  ";
		echo "</p>\n";
        echo "</form>";

	   	} else {
		$targetPay->setAmount (round ($totalAmount*100));
		$targetPay->setDescription ( $lastEvent );
		$targetPay->setReturnUrl ($home.'/?page_id='.$org_options['return_url'] . '&r_id=' . $registration_id. '&type=targetpay');
		$targetPay->setCancelUrl ($home.'/?page_id='.$org_options['cancel_return']. '&r_id=' . $registration_id. '&type=targetpay');

		if ($targetpay_settings['use_sandbox']) {  	// We want to see the normal return page in sandbox mode after cancelling 
			$targetPay->setCancelUrl ($home.'/?page_id='.$org_options['return_url'] . '&r_id=' . $registration_id. '&type=targetpay');
		}
		
		$targetPay->setReportUrl ($home.'/?page_id='.$org_options['notify_url'].'&id=' .$attendee_id.'&r_id='.$registration_id.'&event_id='.$event_id .'&attendee_action=post_payment&form_action=payment&type=targetpay');

		if (isset($_POST["bank"])) $targetPay->setBankId ($_POST["bank"]);
		if (isset($_POST["country"])) $targetPay->setCountryId ($_POST["country"]);
		$url = $targetPay->startPayment();

        if (!$url) {
        	echo "<h3>". __('Couldn\'t start payment', 'targetpay'). "</h3>";
            echo $targetPay->getErrorMessage();
        	} else {

            /* Create temp table if not exists */

			$SQL = "CREATE TABLE IF NOT EXISTS `".$wpdb->base_prefix."events_targetpay` (
					`order_id` VARCHAR(64) DEFAULT NULL,
					`method` VARCHAR(6) DEFAULT NULL,
					`targetpay_txid` VARCHAR(64) DEFAULT NULL,
					`targetpay_response` VARCHAR(128) DEFAULT NULL,
					`paid` DATETIME DEFAULT NULL,
					PRIMARY KEY (`order_id`))";
			$wpdb->query ($SQL);

            /* Save transaction data */

            $SQL = "INSERT INTO `".$wpdb->base_prefix."events_targetpay` SET `order_id` = %s, `method` = %s, `targetpay_txid` = %s ".
                   "ON DUPLICATE KEY UPDATE `paid` = NULL, `method` = %s, `targetpay_txid` = %s";
			$wpdb->get_results( $wpdb->prepare( $SQL,
                                $registration_id,
                                $targetPay->getPayMethod(),
                                $targetPay->getTransactionId(),
                                $targetPay->getPayMethod(),
                                $targetPay->getTransactionId()
                                ));

            /* Redirect to bank */

            echo  __('You\'re being redirected to the bank...', 'targetpay');
	        echo "<script>";
	        echo "location='".$url."';";
	        echo "</script>";
		}
	}
}

add_action('action_hook_espresso_display_offsite_payment_gateway', 'espresso_display_targetpay');
