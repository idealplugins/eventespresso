<?php

function espresso_transactions_targetpay_get_attendee_id($attendee_id) {
	if (!empty($_REQUEST['id'])) {
		$attendee_id = $_REQUEST['id'];
	}
	return $attendee_id;
}

function espresso_process_targetpay($payment_data) {
	do_action('action_hook_espresso_log', __FILE__, __FUNCTION__, '');
	$payment_data['txn_type'] = 'TargetPay';
	$payment_data['txn_id'] = 0;
	$payment_data['payment_status'] = 'Incomplete';
	$payment_data['txn_details'] = serialize($_REQUEST);

    if (!class_exists('TargetPayCore')) {
		include_once ('targetpay.class.php');
        }    

	include_once ('Targetpay.php');
	$myTargetpay = new EE_Targetpay();

	$targetpay_settings = get_option('event_espresso_targetpay_settings');

	if ($myTargetpay->validateIpn()) {
		$payment_data['payment_status'] = 'Completed';
		$payment_data['txn_details'] = serialize($myTargetpay->ipnData);
		$payment_data['txn_id'] = $myTargetpay->ipnData['targetpay_txid'];

		if ($targetpay_settings['use_sandbox']) {
			// For this, we'll just email ourselves ALL the data as plain text output.
			$subject = 'Instant Payment Notification - Gateway Variable Dump';
			$body = "An instant payment notification was successfully recieved\n";
			$body .= "from " . $myTargetpay->ipnData['payer_email'] . " on " . date('m/d/Y');
			$body .= " at " . date('g:i A') . "\n\nDetails:\n";
			foreach ($myTargetpay->ipnData as $key => $value) {
				$body .= "\n$key: $value\n";
			}
			wp_mail($payment_data['contact'], $subject, $body);
		}
    }

	add_action('action_hook_espresso_email_after_payment', 'espresso_email_after_payment');
	return $payment_data;
}
