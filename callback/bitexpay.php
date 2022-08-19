<?php

// Callback 
use WHMCS\Billing\Invoice;
use WHMCS\Billing\Payment\Transaction;

require_once(__DIR__ . '/../../../init.php');

App::load_function('gateway');
$gatewaymodule = "bitexpay"; 
$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated");

function bitexpay_error($msj){
    global $GATEWAY;
    $report = '';

    if(!empty($GATEWAY['bitexpay_email'])){
        $report = 'Email not config';

        if ($msj)
            $report .= "Error Message: ".$msj."\n\n";
        mail($GATEWAY['bitexpay_email'], "Bitexpay Invalid IPN", $report);
    }else
    if(!empty($GATEWAY['bitexpay_api_key'])){
        $report = 'Invalid Api key';

        if ($msj)
            $report .= "Error Message: ".$msj."\n\n";
        mail($GATEWAY['bitexpay_email'], "Bitexpay Api Key error", $report);
    }else
    if(!empty($GATEWAY['bitexpay_send_merchant_id'])){
        $report = 'Invalid Merchant ID';
        
        if ($msj)
            $report .= "Error Message: ".$msj."\n\n";
        mail($GATEWAY['bitexpay_email'], "Merchant id error", $report);
    }   
    
    die($report);
}

$merchantID = $_POST['merchant'];
$apiKey = $_POST['accesskey'];
$status = intval($_POST["status"]);
$status_text = $_POST["status_text"];
$invoiceid = $_POST["invoice"];
$transid = $_POST["txn_id"];
$amount1 = floatval($_POST["amount1"]);
$currency1 = $_POST["currency1"];

if(trim($merchantID) != trim($GATEWAY['bitexpay_send_merchant_id'])){
    bitexpay_error("Invalid merchant ID!");
}

if(trim($apiKey) != trim($GATEWAY['bitexpay_api_key'])){
    bitexpay_error("Invalid api key!");
}

if($amount1 <= 0) {
	bitexpay_error("Amount must be > 0!");
}

$invoice = Invoice::find($invoiceid);
if (!$invoice) {
    bitexpay_error("Invoice not found!");
}

if($invoice->getCurrencyCodeAttribute() != $currency1) {
	bitexpay_error('Payment currency does not match invoice currency');
}

checkCbTransID($transid);

if($status == 1){   //Complete
    logTransaction($GATEWAY["name"], $_POST, 'Payment Completed');
    $invoice->addPaymentIfNotExists($amount1, $transid, 0, $gatewaymodule);
}else
if($status == 2){   //Incomplete pay
    if ($invoice->getBalanceAttribute()) {
		$invoice->status = 'Payment Pending';
		$invoice->save();
	}

    logTransaction($GATEWAY["name"],$_POST, 'Payment Pending: '.$status_text);
}else{
    // Gateway Log
	logTransaction($GATEWAY["name"], $_POST, 'Payment Error: '.$status_text);  
    
    // Set invoice status to pending if invoice has a balance
	if ($invoice->getBalanceAttribute()) {
		$invoice->status = 'Unpaid';
		$invoice->save();
	}
}

die('IPN OK');