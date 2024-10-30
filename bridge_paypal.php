<?php
/**
 * Plugin Name: Bridge - Paypal to Email Lists
 * Plugin URI: http://erinhookkelly.com/bridge
 * Description: This plugin acts as a bridge between paypal purchases and your email list service.
 * Version: 1.0.1
 * Author: The Grumpy Developer
 * Author URI: http://thegrumpydev.com/
 * License: GPL2
 */
 

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
 
// create custom plugin settings menu
add_action('admin_menu', 'grumpy_bpte_create_menu');

register_activation_hook( __FILE__, 'grumpy_bpte_activate' );

function grumpy_bpte_activate() {
	update_option( 'grumpy_bpte_transactions', '' );
}
function grumpy_bpte_create_menu() {

	//create new top-level menu
	add_menu_page('Bridge Plugin Settings', 'Bridge Settings', 'administrator', __FILE__, 'grumpy_bpte_settings_page' );

	//call register settings function
	add_action( 'admin_init', 'grumpy_bpte_register_settings' );
}


function grumpy_bpte_register_settings() {
	//register our settings
	register_setting( 'grumpy_bpte_settings_group', 'grumpy_bpte_activation_key' );
	register_setting( 'grumpy_bpte_settings_group', 'grumpy_bpte_service' );
	register_setting( 'grumpy_bpte_settings_group', 'grumpy_bpte_list_from' );
	register_setting( 'grumpy_bpte_settings_group', 'grumpy_bpte_list_to' );

	register_setting( 'grumpy_bpte_settings_group', 'grumpy_bpte_ac_url' );
	register_setting( 'grumpy_bpte_settings_group', 'grumpy_bpte_ac_api_key' );
}

function grumpy_bpte_settings_page() {
	$l_sPaypalUrl = site_url()."/?bridge_action=paypal_ipn";

	$l_asFrom = get_option('grumpy_bpte_list_from');
	$l_asTo = get_option('grumpy_bpte_list_to');

	$l_bActivated = grumpy_bpte_get_activated_status(get_option('grumpy_bpte_activation_key'));
	
	$l_asTransactions = get_option('grumpy_bpte_transactions');
	if(is_array($l_asTransactions)){
		$l_asTransactions = array_reverse(get_option('grumpy_bpte_transactions'));
	}

	$l_sEmailAcUrl = get_option('grumpy_bpte_ac_url');
	$l_sEmailAcKey = get_option('grumpy_bpte_ac_api_key');
	$l_bAcLoggedIn = false;

	if($l_sEmailAcUrl && $l_sEmailAcKey){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $l_sEmailAcUrl."/admin/api.php?api_action=automation_list&api_key=".$l_sEmailAcKey."&api_output=json");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$l_xEmailAcListJson = curl_exec($ch);
		curl_close($ch);

		$l_asEmailAcLists = json_decode($l_xEmailAcListJson,true);
		
		if(is_array($l_asEmailAcLists)){
			$l_bAcLoggedIn = true;
		}
		
		$l_sEmailAcListSelect = '<select name="grumpy_bpte_list_to[]"><option value="">(select)</option>';
		if($l_bAcLoggedIn){
			foreach($l_asEmailAcLists as $l_asEmailAcList){
				if(!is_array($l_asEmailAcList)){continue;}
				$l_sEmailAcListSelect .= '<option value="'.$l_asEmailAcList["id"].'">'.$l_asEmailAcList["name"].'</option>';
			}
		}
		$l_sEmailAcListSelect .= '</select>';
	}


?>
<style>
	.bridge_table td{
		padding: 5px;
	}
		.bridge_table td .page-title-action{
			top: 0px;
		}
</style>

<div class="wrap">
<h2>Bridge (Paypal to Email Lists)</h2>
<hr>

<form method="post" action="options.php">
    <?php settings_fields( 'grumpy_bpte_settings_group' ); ?>
    <?php do_settings_sections( 'grumpy_bpte_settings_group' ); ?>
    
    <h3>General</h3>
    <table class="form-table">
        <tr valign="top">
	        <th scope="row">Activation Key</th>
	        <td><input type="text" name="grumpy_bpte_activation_key" class="regular-text" value="<?php echo esc_attr( get_option('grumpy_bpte_activation_key') ); ?>" /></td>
        </tr>
        <?php
	        if(!$l_bActivated){
	    ?>
	        <tr valign="top">
		        <th>&nbsp;</th>
		        <td>Need to map more than one field?  <a href="http://erinhookkelly.com/bridge" target="_blank">Get the Pro Version</a>.</td>
	        </tr>
	    <?php
		    }else{
		?>
	        <tr valign="top">
		        <th>&nbsp;</th>
		        <td>PRO Version Activated!</td>
	        </tr>
		<?php
		    }
		?>
    </table>
	<hr>

    <h3>Paypal</h3>
    <table class="form-table">
        <tr valign="top">
	        <th scope="row">IPN URL</th>
			<td><?php echo $l_sPaypalUrl; ?></td>
        </tr>
        <tr valign="top">
	        <th>&nbsp;</th>
	        <td><i>Need to know where to put your IPN URL?  <a href="https://developer.paypal.com/docs/classic/ipn/integration-guide/IPNSetup/" target="_blank">Click Here</a></i></td>
        </tr>
    </table>
	<hr>

    <h3>Email Service</h3>
    <table class="form-table">
        <tr valign="top">
        <th scope="row">Service</th>
        <td>
	        <select name="grumpy_bpte_service" id="bridge_service" onchange="changeService()">
		        <option value="">(select)</option>
		        <option value="active_campaign" <?php if(esc_attr(get_option('grumpy_bpte_service'))=='active_campaign'){echo "selected=\"selected\"";} ?>>Active Campaign</option>
		        <!--<option value="mailchimp" <?php if(esc_attr(get_option('grumpy_bpte_service'))=='mailchimp'){echo "selected=\"selected\"";} ?>>Mailchimp</option>-->
	        </select>
        </td>
        </tr>
    </table>

    <table class="form-table" id="email_table_ac" style="<?php if(esc_attr(get_option('grumpy_bpte_service'))!='active_campaign'){echo "display:none;";} ?>">
        <tr valign="top">
	        <th scope="row">Active Campaign - API URL</th>
	        <td><input type="text" name="grumpy_bpte_ac_url" class="regular-text" value="<?php echo esc_attr( get_option('grumpy_bpte_ac_url') ); ?>" /></td>
        </tr>
        <tr valign="top">
	        <th scope="row">Active Campaign - API Key</th>
	        <td><input type="text" name="grumpy_bpte_ac_api_key" class="regular-text" value="<?php echo esc_attr( get_option('grumpy_bpte_ac_api_key') ); ?>" /></td>
        </tr>
        <tr valign="top">
	        <th>&nbsp;</th>
	        <td><i>Need to know where to get this?  <a href="http://www.activecampaign.com/help/using-the-api/" target="_blank">Click Here</a></i></td>
        </tr>
    </table>
    <hr>

    <h3>List Mapping</h3>
    <?php
	    if(!$l_bAcLoggedIn){
	?>
    	<p><i>Must have valid "Email Service" configured first.</i></p>
    <?php
	    }else{
	?>
		    <table class="bridge_table">
			    <tr>
				    <th>Paypal Button Name</th>
				    <th>Email List</th>
			    </tr>
			    <tbody id="bridge_mapping_table">
				    <?php
					    $l_nCounter = 0;
					    if(is_array($l_asFrom)){
						    foreach($l_asFrom as $l_sFrom){
					?>
						        <tr class="mapping_row">
							        <td>
								        <input type="text" class="regular-text" name="grumpy_bpte_list_from[]" value="<?php echo esc_attr($l_asFrom[$l_nCounter]) ?>">
							        </td>
							        <td>
								        <select name="grumpy_bpte_list_to[]">
									        <option value="">(select)</option>
											<?php
												foreach($l_asEmailAcLists as $l_asEmailAcList){
													if(!is_array($l_asEmailAcList)){continue;}
													if($l_asTo[$l_nCounter] == $l_asEmailAcList["id"]){
														echo '<option value="'.$l_asEmailAcList["id"].'" selected="selected">'.$l_asEmailAcList["name"].'</option>';										
													}else{
														echo '<option value="'.$l_asEmailAcList["id"].'">'.$l_asEmailAcList["name"].'</option>';
													}
												}
											?>
								        </select>
							        </td>
							        <td>
								        <a class="page-title-action" href="javascript:void(0)" onclick="deleteRow(this)">Delete Row</a>
							        </td>
						        </tr>
			        <?php
				        		$l_nCounter++;
				        	}
				        }else{
				    ?>
							<tr class="mapping_row">
								<tr><td><input type="text" class="regular-text" name="grumpy_bpte_list_from[]"></td><td><?php echo $l_sEmailAcListSelect ?></td><td><a class="page-title-action" href="javascript:void(0)" onclick="deleteRow(this)">Delete Row</a></td></tr>
							</tr>
				    <?php
					    }
					?>
			    </tbody>
		        <tr>
			        <td>
				        &nbsp;
			        </td>
			        <td>
				        &nbsp;
			        </td>
			        <td>
				        <?php
					        if($l_bActivated){
				        ?>
						        <a class="page-title-action" href="javascript:void(0)" onclick="bridgeAddRow()">Add Row</a>
						<?php
							}else{
						?>
						        <a class="page-title-action" href="javascript:void(0)" onclick="bridgeAddUpgradeRow()">Add Row</a>
						<?php
							}
						?>
			        </td>
		        </tr>
		    </table>
    <?php
	    }
	?>

    <?php submit_button(); ?>

	<hr>
    <h3>Transaction Log &nbsp;&nbsp;&nbsp;&nbsp;<a class="page-title-action" href="javascript:void(0)" onclick="toggleTransactions()">toggle</a></h3>
    <table class="form-table" id="transaction_log" style="display:none;">
	    <tr>
		    <th>Date</th>
		    <th>Status</th>
		    <th>Message</th>
	    </tr>
	    <?php
		    if(is_array($l_asTransactions)){
		?>
				<tr class="trans_row">
					<td colspan="3"><a class="page-title-action" href="javascript:void(0)" onclick="clearTransactions()">clear transaction history</a></td>
				</tr>		
		<?php
			    foreach($l_asTransactions as $l_asTransaction){
		?>
					<tr class="trans_row">
						<td><?php echo $l_asTransaction[0] ?></td>
						<td><?php echo $l_asTransaction[1] ?></td>
						<td><?php echo $l_asTransaction[2] ?></td>						
					</tr>
		<?php
				}
			}else{
		?>
				<tr>
					<td colspan="3"><i>No Recent Transactions</i></td>
				</tr>
		<?php
			}
		?>
    </table>

</form>
</div>

<script>
	function clearTransactions(){

		var data = {
			'action': 'grumpy_bpte_clear_transactions'
		};

		jQuery.post(ajaxurl, data, function(response) {
			jQuery('.trans_row').remove();
		});

	}
	function changeService(){
		jQuery('#email_table_ac').hide();
		
		if(jQuery('#bridge_service').val() == 'active_campaign'){
			jQuery('#email_table_ac').show();
		}
	}
	function toggleTransactions(){
		jQuery('#transaction_log').toggle();
	}
	function bridgeAddRow(){
		jQuery('#bridge_mapping_table').append('<tr class="mapping_row"><td><input type="text" class="regular-text" name="grumpy_bpte_list_from[]"></td><td><?php echo addslashes($l_sEmailAcListSelect) ?></td><td><a class="page-title-action" href="javascript:void(0)" onclick="deleteRow(this)">Delete Row</a></td></tr>');
	}
	function bridgeAddUpgradeRow(){
		jQuery('#bridge_mapping_table').append('<tr class="mapping_row"><td colspan="2">Need to map more than one field?  <a href="http://erinhookkelly.com/bridge" target="_blank">Get the Pro Version</a>.</td><td><a class="page-title-action" href="javascript:void(0)" onclick="deleteRow(this)">Delete Row</a></td></tr>');
	}
	function deleteRow(p_xRow){
		var l_nCount = jQuery('#bridge_mapping_table tr.mapping_row').size();
		if(l_nCount > 1){
			jQuery(p_xRow).parent().parent().remove();
		}else{
			alert("Must have at least one row.");
		}
	}
</script>
<?php
}

add_action( 'wp_ajax_grumpy_bpte_clear_transactions', 'grumpy_bpte_clear_transactions_callback' );

function grumpy_bpte_clear_transactions_callback() {
	global $wpdb; // this is how you get access to the database

	$l_axLog = get_option('grumpy_bpte_transactions');
	$l_axLog = '';
	update_option( 'grumpy_bpte_transactions', $l_axLog );
	echo "done";

	wp_die(); // this is required to terminate immediately and return a proper response
}

function grumpy_bpte_get_activated_status($l_sActivationKey){
	if($l_sActivationKey == md5(1) || $l_sActivationKey == md5(2) || $l_sActivationKey == md5(3) || $l_sActivationKey == md5(4) || $l_sActivationKey == md5(5) || $l_sActivationKey == md5(6) || $l_sActivationKey == md5(7) || $l_sActivationKey == md5(8) || $l_sActivationKey == md5(9) || $l_sActivationKey == md5(10)){
		return true;
	}else{
		return false;
	}
}

add_filter('query_vars','grumpy_bpte_add_trigger');
function grumpy_bpte_add_trigger($vars) {
    $vars[] = 'bridge_action';
    return $vars;
}
 
add_action('template_redirect', 'grumpy_bpte_trigger_check');
function grumpy_bpte_trigger_check() {
    if(get_query_var('bridge_action') == 'paypal_ipn') {

		//header('HTTP/1.1 200 OK');

		// Assign payment notification values to local variables
		$p_sItem_name        = $_POST['item_name'];
		$p_sItem_number      = $_POST['item_number'];
		$p_sPayment_status   = $_POST['payment_status'];
		$p_sPayment_amount   = $_POST['mc_gross'];
		$p_sPayment_currency = $_POST['mc_currency'];
		$p_sTxn_id           = $_POST['txn_id'];
		$p_sReceiver_email   = $_POST['receiver_email'];
		$p_sPayer_email      = $_POST['payer_email'];
		
				
		//************************************ PAYPAL STUFF ************************************
				
		$paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
		//$paypal_url = "https://www.paypal.com/cgi-bin/webscr";
		
		$ch = curl_init($paypal_url);
		if ($ch == FALSE) {
			return FALSE;
		}
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
		if(DEBUG == true) {
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
		}
		// CONFIG: Optional proxy configuration
		//curl_setopt($ch, CURLOPT_PROXY, $proxy);
		//curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, 1);
		// Set TCP timeout to 30 seconds
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Connection: Close'));
		// CONFIG: Please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path
		// of the certificate as shown below. Ensure the file is readable by the webserver.
		// This is mandatory for some environments.
		//$cert = __DIR__ . "./cacert.pem";
		//curl_setopt($ch, CURLOPT_CAINFO, $cert);
		$res = curl_exec($ch);

		if (curl_errno($ch) != 0){
			$l_sTransactionTime = date('m/d/Y g:ia e');
			$l_sTransactionStatus = "Failed";
			$l_sTransactionMessage =  "Can't connect to PayPal to validate IPN message:<hr>" . curl_error($ch);

			$l_axLog = get_option('grumpy_bpte_transactions');
			$l_axLog[] = array($l_sTransactionTime,$l_sTransactionStatus,$l_sTransactionMessage);
			
			update_option( 'grumpy_bpte_transactions', $l_axLog );

			curl_close($ch);
			exit;
		} else {
				/*
				$l_sTransactionTime = date('m/d/Y g:ia e');
				$l_sTransactionStatus = "Succcess";
				$l_sTransactionMessage =  "CURL Success:<hr>" . $res;
				$l_axLog = get_option('grumpy_bpte_transactions');
				$l_axLog[] = array($l_sTransactionTime,$l_sTransactionStatus,$l_sTransactionMessage);				
				update_option( 'grumpy_bpte_transactions', $l_axLog );
				*/
				curl_close($ch);
		}

		// Split response headers and payload, a better way for strcmp
		$tokens = explode("\r\n\r\n", trim($res));
		$res = trim(end($tokens));

		if (strcmp ($res, "VERIFIED") == 0) {
			$l_sTransactionTime = date('m/d/Y g:ia e');
			$l_sTransactionStatus = "Success";
			$l_sTransactionMessage = "Paypal Response VERIFIED: ".$res;

			$l_axLog = get_option('grumpy_bpte_transactions');
			$l_axLog[] = array($l_sTransactionTime,$l_sTransactionStatus,$l_sTransactionMessage);
			
			update_option( 'grumpy_bpte_transactions', $l_axLog );
		}else if (strcmp ($res, "INVALID") == 0) {
			$l_sTransactionTime = date('m/d/Y g:ia e');
			$l_sTransactionStatus = "Failed";
			$l_sTransactionMessage = "Paypal Response Invalid: ".$res;

			$l_axLog = get_option('grumpy_bpte_transactions');
			$l_axLog[] = array($l_sTransactionTime,$l_sTransactionStatus,$l_sTransactionMessage);
			
			update_option( 'grumpy_bpte_transactions', $l_axLog );
		}else{
			$l_sTransactionTime = date('m/d/Y g:ia e');
			$l_sTransactionStatus = "Information";
			$l_sTransactionMessage = "Paypal Response: <hr>".$res;

			$l_axLog = get_option('grumpy_bpte_transactions');
			$l_axLog[] = array($l_sTransactionTime,$l_sTransactionStatus,$l_sTransactionMessage);
			
			update_option( 'grumpy_bpte_transactions', $l_axLog );
		}


			//************************************ BRIDGE STUFF ************************************		
			
			$l_sType = get_option('grumpy_bpte_service');
			
			$l_asFrom = get_option('grumpy_bpte_list_from');
			$l_asTo = get_option('grumpy_bpte_list_to');
			
			$l_sTransactionTime = date('m/d/Y g:ia e');
			$l_sTransactionStatus = null;
			$l_sTransactionMessage = null;
			
			
			if($l_sType == "active_campaign"){
				$l_sEmailAcUrl = get_option('grumpy_bpte_ac_url');
				$l_sEmailAcKey = get_option('grumpy_bpte_ac_api_key');
				$l_sAcButtonId = null;
			
				$l_nCounter = 0;
				foreach($l_asFrom as $l_sFrom){
					if($l_asFrom[$l_nCounter] == $p_sItem_name){
						$l_sAcButtonId = $l_asTo[$l_nCounter];
					}			
					$l_nCounter++;
				}
				if($l_sAcButtonId){
					if($l_sEmailAcUrl && $l_sEmailAcKey){
			
						// Add Contact to System
						$l_asContactAddFields = array(
							'api_key' => $l_sEmailAcKey,
							'api_output' => 'json',
							'email' => $p_sPayer_email
						);
						foreach($l_asContactAddFields as $key=>$value){
							$l_sContactAddString .= $key.'='.$value.'&';
						}
						rtrim($l_sContactAddString, '&');
						
						$l_xContactAddCurl = curl_init();
						curl_setopt($l_xContactAddCurl, CURLOPT_URL, $l_sEmailAcUrl."/admin/api.php?api_action=contact_sync");
						curl_setopt($l_xContactAddCurl,CURLOPT_POST, count($l_asContactAddFields));
						curl_setopt($l_xContactAddCurl,CURLOPT_POSTFIELDS, $l_sContactAddString);
						curl_setopt($l_xContactAddCurl, CURLOPT_RETURNTRANSFER, 1);
						$l_sAcContactPostResponse = curl_exec($l_xContactAddCurl);
						curl_close($l_xContactAddCurl);
			
			
						// Add Contact to Automation
						$l_asAutomationAddFields = array(
							'api_key' => $l_sEmailAcKey,
							'api_output' => 'json',
							'contact_email' => $p_sPayer_email,
							'automation' => $l_sAcButtonId
						);
						foreach($l_asAutomationAddFields as $key=>$value){
							$l_sAutomationAddString .= $key.'='.$value.'&';
						}
						rtrim($l_sAutomationAddString, '&');
						
						$l_xAutomationAddCurl = curl_init();
						curl_setopt($l_xAutomationAddCurl, CURLOPT_URL, $l_sEmailAcUrl."/admin/api.php?api_action=automation_contact_add");
						curl_setopt($l_xAutomationAddCurl,CURLOPT_POST, count($l_asAutomationAddFields));
						curl_setopt($l_xAutomationAddCurl,CURLOPT_POSTFIELDS, $l_sAutomationAddString);
						curl_setopt($l_xAutomationAddCurl, CURLOPT_RETURNTRANSFER, 1);
						$l_sAcPostResponse = curl_exec($l_xAutomationAddCurl);
						curl_close($l_xAutomationAddCurl);
			
						$l_sTransactionStatus = "Success";
						$l_sTransactionMessage = "Active Campaign - Email:".$p_sPayer_email.", Campaign:".$l_sAcButtonId."  ".$l_sAcContactPostResponse." ".$l_sAcPostResponse;
					}else{
						$l_sTransactionStatus = "Failed";
						$l_sTransactionMessage = "Active Campaign - API Information Missing";				
					}
				}else{
					$l_sTransactionStatus = "Failed";
					$l_sTransactionMessage = "Active Campaign - '".$p_sItem_name."' mapping not found";		
				}
			
			
			}else if($l_sType == "mailchimp"){
				// todo: hook up API
			
				$l_sTransactionStatus = "Failed";
				$l_sTransactionMessage = "Mailchimp Not Supported Yet";
			
			}else{
				$l_sTransactionStatus = "Failed";
				$l_sTransactionMessage = "Email Type Not Supported";
			}
			
			$l_axLog = get_option('grumpy_bpte_transactions');
			$l_axLog[] = array($l_sTransactionTime,$l_sTransactionStatus,$l_sTransactionMessage);
			
			update_option( 'grumpy_bpte_transactions', $l_axLog );	
			
					
		exit;

		
    }
}
?>