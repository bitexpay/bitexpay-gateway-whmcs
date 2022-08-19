<?php
/**
 * WHMCS Sample Payment Gateway Module
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function bitexpay_MetaData()
{
    return array(
        'DisplayName' => 'Bitexpay',
        'APIVersion' => '1.0', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}


function bitexpay_config()
{
    
    return array(
       
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Bitexpay',
        ),
      
        'bitexpay_api_key' => array(
            'FriendlyName' => 'Merchant Api Key',
            'Type' => 'text',
            'Size' => '32',
            'Default' => '',
            'Description' => 'Your api key from bitexpay account',
        ),
     
        'bitexpay_secret_key' => array(
            'FriendlyName' => 'Secret Key',
            'Type' => 'password',
            'Size' => '64',
            'Default' => '',
            'Description' => 'Your secret key from bitexpay account',
        ),

        'bitexpay_domain' => array(
            'FriendlyName' => 'Domain',
            'Type' => 'text',
            'Size' => '70',
            'Default' => '',
            'Description' => 'Domain secure of api key',
        ),
        'bitexpay_send_shipping' => array(
            'FriendlyName' => 'Include shipping information',
            'Type' => 'yesno',
            'Default' => 'no',
            'Description' => 'Tick to enable test mode',
        ),
       
        'bitexpay_send_merchant_id' => array(
            'FriendlyName' => 'Merchant ID',
            'Type' => 'text',
            'Size' => '70',
            'Default' => '',
            'Description' => 'Your Merchant ID can be found under the account',
        ),
        'bitexpay_monetize' => array(
            'FriendlyName' => 'Monetize',
            'Type' => 'yesno',
            'Default' => 'no',
            'Description' => 'Monetize all orden',
        ),

        'bitexpay_email' => array(
            'FriendlyName' => 'Email Debug',
            'Type' => 'text',
            'Size' => '70',
            'Default' => '',
            'Description' => 'Email Debug',
        ),
    );
}

/**
 * Payment link.
 *
 */
function bitexpay_link($params)
{
    // Gateway Configuration Parameters
    $bitexpay_args = [
        'merchant' 		=> $params['bitexpay_send_merchant_id'],
        'currency' 		=> $params['currency'],
        'reset' 		=> 1,
        'success_url' 	=> urlencode(urldecode($params['systemurl'])),
        'cancel_url'	=> urlencode(urldecode($params['systemurl'])),
        'item_name' => urlencode(urldecode($params["description"])),
        'want_shipping' => $params['bitexpay_send_shipping'] ? '1':'0',

        // Order key + ID
        'invoice'		=> $params['invoiceid'],

        // IPN
        'ipn_url'		=> urlencode(urldecode($params['systemurl'].'/modules/gateways/callback/bitexpay.php')),

        // Billing Address info
        'first_name'	=> urlencode(urldecode($params['clientdetails']['firstname'])),
        'last_name'		=> urlencode(urldecode($params['clientdetails']['lastname'])),
        'email'			=> urlencode(urldecode($params['clientdetails']['email'])),

        //Create order in Bitexpay
        'accesskey'     => $params['bitexpay_api_key'],
        'nonce'         => time(),
        'usd'           => $params['amount'],
        "tipo"          => "private",
        "monetizar"     => $params['bitexpay_monetize']=='no'?false:true,
        "enviarCorreo"  => 'S',
        "descripcion"   => urlencode(urldecode($params["description"])),
        "tipo_fee_monetizar"=> 'owner',
        'domain'        => urlencode(urldecode($params['bitexpay_domain']))
    ];    
    $url='http://pay.bitexblock.com/#/auth/login_/whcm/filter';
    $query = http_build_query( $bitexpay_args, '', '&' );
    $signature = hash_hmac('sha512', $query, $params['bitexpay_secret_key']);
    $url = $url.'?'.$query.'&signature=' . $signature;
    

    $htmlOutput = '<form id="cpsform" action="'.$url.'"  method="get">';;
    foreach ($$bitexpay_args  as $key => $value) {
        $htmlOutput .= '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($value).'">';
    }
    $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';
    

    return $htmlOutput;
}

