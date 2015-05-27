<?php

function event_espresso_targetpay_payment_settings() {
	global $active_gateways;
	if (isset($_POST['update_targetpay'])) {
		$targetpay_settings['rtlo'] = $_POST['rtlo'];
		$targetpay_settings['currency_format'] = $_POST['currency_format'];
		$targetpay_settings['use_sandbox'] = empty($_POST['use_sandbox']) ? false : true;
		update_option('event_espresso_targetpay_settings', $targetpay_settings);
		echo '<div id="message" class="updated fade"><p><strong>' . __('TargetPay settings saved', 'targetpay') . '</strong></p></div>';
	}

	$targetpay_settings = get_option('event_espresso_targetpay_settings');
	if (empty($targetpay_settings)) {

    /*
		if (file_exists(EVENT_ESPRESSO_GATEWAY_DIR . "/targetpay/btn_stdCheckout2.gif")) {
			$button_url = EVENT_ESPRESSO_GATEWAY_URL . "/targetpay/btn_stdCheckout2.gif";
		} else {
			$button_url = EVENT_ESPRESSO_PLUGINFULLURL . "gateways/targetpay/btn_stdCheckout2.gif";
		}
*/
		$targetpay_settings['rtlo'] = 93929; // Default TargetPay
		$targetpay_settings['currency_format'] = 'EUR';
		$targetpay_settings['use_sandbox'] = false;
		if (add_option('event_espresso_targetpay_settings', $targetpay_settings, '', 'no') == false) {
			update_option('event_espresso_targetpay_settings', $targetpay_settings);
		}
	}

	//Open or close the postbox div
	if (empty($_REQUEST['deactivate_targetpay'])
					&& (!empty($_REQUEST['activate_targetpay'])
					|| array_key_exists('targetpay', $active_gateways))) {
		$postbox_style = '';
	} else {
		$postbox_style = 'closed';
	}
	?>

	<div class="metabox-holder">
		<div class="postbox <?php echo $postbox_style; ?>">
			<div title="Click to toggle" class="handlediv"><br /></div>
			<h3 class="hndle">
				<?php _e('TargetPay Settings', 'targetpay'); ?>
			</h3>
			<div class="inside">
				<div class="padding">
					<?php
					if (!empty($_REQUEST['activate_targetpay'])) {
						$active_gateways['targetpay'] = dirname(__FILE__);
						update_option('event_espresso_active_gateways', $active_gateways);
					}
					if (!empty($_REQUEST['deactivate_targetpay'])) {
						unset($active_gateways['targetpay']);
						update_option('event_espresso_active_gateways', $active_gateways);
					}
					echo '<ul>';
					if (array_key_exists('targetpay', $active_gateways)) {
						echo '<li id="deactivate_targetpay" style="width:30%;" onclick="location.href=\'' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=payment_gateways&deactivate_targetpay=true\';" class="red_alert pointer"><strong>' . __('Deactivate TargetPay?', 'targetpay') . '</strong></li>';
						event_espresso_display_targetpay_settings();
					} else {
						echo '<li id="activate_targetpay" style="width:30%;" onclick="location.href=\'' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=payment_gateways&activate_targetpay=true\';" class="green_alert pointer"><strong>' . __('Activate TargetPay?', 'targetpay') . '</strong></li>';
					}
					echo '</ul>';
					?>
				</div>
			</div>
		</div>
	</div>
	<?php
}

//TargetPay Settings Form
function event_espresso_display_targetpay_settings() {
	$targetpay_settings = get_option('event_espresso_targetpay_settings');
	?>
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
		<table width="99%" border="0" cellspacing="5" cellpadding="5">
			<tr>
				<td valign="top"><ul>
						<li>
							<label for="rtlo">
								<?php _e('TargetPay layoutcode', 'targetpay'); ?>
							</label>
							<input type="text" name="rtlo" size="35" value="<?php echo $targetpay_settings['rtlo']; ?>">
							<br />
							<?php _e('Supplied while registering at TargetPay.com or login and go to Account > Subaccounts', 'targetpay'); ?>
						</li>
						<li>
							<label for="currency_format">
								<?php _e('Select the Currency for Your Country', 'targetpay'); ?> <a class="thickbox" href="#TB_inline?height=300&width=400&inlineId=currency_info"><img src="<?php echo EVENT_ESPRESSO_PLUGINFULLURL ?>/images/question-frame.png" width="16" height="16" /></a>
							</label>
							<select name="currency_format">
								<option value="<?php echo $targetpay_settings['currency_format']; ?>"><?php echo $targetpay_settings['currency_format']; ?></option>
								<option value="EUR">
									<?php _e('Euros (&#8364;)', 'targetpay'); ?>
								</option>
							</select>
							 </li>

					</ul></td>
				<td valign="top"><ul>
						<li>
							<label for="use_sandbox">
								<?php _e('Use the Debugging Feature', 'targetpay'); ?> <a class="thickbox" href="#TB_inline?height=300&width=400&inlineId=targetpay_sandbox_info"><img src="<?php echo EVENT_ESPRESSO_PLUGINFULLURL ?>/images/question-frame.png" width="16" height="16" /></a>
							</label>
							<input name="use_sandbox" type="checkbox" value="1" <?php echo $targetpay_settings['use_sandbox'] ? 'checked="checked"' : '' ?> />
							<br />
						</li>

					</ul></td>
			</tr>
		</table>
			<input type="hidden" name="update_targetpay" value="update_targetpay">
			<input class="button-primary" type="submit" name="Submit" value="<?php _e('Update TargetPay Settings', 'targetpay') ?>" id="save_targetpay_settings" />
		</p>
	</form>
	<div id="targetpay_sandbox_info" style="display:none">
		<h2><?php _e('TargetPay debugging', 'targetpay'); ?></h2>
		<p><?php _e('The debugging feature will accept all payment attempts, even when cancelled (for testing purposes)', 'targetpay'); ?></p>
	</div>
	<div id="currency_info" style="display:none">
		<h2><?php _e('TargetPay Currency', 'targetpay'); ?></h2>
		<p><?php _e('TargetPay uses 3-character ISO-4217 codes for specifying currencies in fields and variables. </p><p>The default currency code is Euros (EUR). If you want to require or accept payments in other currencies, select the currency you wish to use. The dropdown lists all currencies that TargetPay (currently) supports.', 'targetpay'); ?> </p>
	</div>

	<?php
}

add_action('action_hook_espresso_display_gateway_settings','event_espresso_targetpay_payment_settings');
