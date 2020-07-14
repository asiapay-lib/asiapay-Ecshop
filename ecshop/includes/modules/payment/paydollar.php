<?php
 
/**
 * ECSHOP PayDollar payment module
 */

if (!defined('IN_ECS'))
{
    die('Hacking attempt');
}

$payment_lang = ROOT_PATH . 'languages/' .$GLOBALS['_CFG']['lang']. '/payment/paydollar.php';

if (file_exists($payment_lang))
{
    global $_LANG;

    include_once($payment_lang);
}


if (isset($set_modules) && $set_modules == TRUE)
{
    $i = isset($modules) ? count($modules) : 0;

    $modules[$i]['code']    	= basename(__FILE__, '.php');

    $modules[$i]['desc']    	= 'paydollar_desc';
    $modules[$i]['is_cod']  	= '0';
    $modules[$i]['is_online']  	= '1';
    $modules[$i]['pay_fee'] 	= '0';
    $modules[$i]['author']  	= 'APPH';
    $modules[$i]['website'] 	= 'http://www.paydollar.com';
    $modules[$i]['version'] 	= '1.0';

    $modules[$i]['config'] = array(
    	array('name' => 'paydollar_spnUrl',				'type' => 'text', 'value' => ''),
    	array('name' => 'paydollar_merchantId',			'type' => 'text', 'value' => ''),
        array('name' => 'paydollar_currCode',			'type' => 'text', 'value' => ''),
        array('name' => 'paydollar_lang',				'type' => 'text', 'value' => 'E'),
        array('name' => 'paydollar_payType',			'type' => 'text', 'value' => 'N'),
        array('name' => 'paydollar_payMethod',			'type' => 'text', 'value' => 'ALL'),        
        array('name' => 'paydollar_secureHashSecret',	'type' => 'text', 'value' => ''),
        array('name' => 'paydollar_prefix',				'type' => 'text', 'value' => 'ecshop-'),
    );

    return;
}

class paydollar
{
    /** 
     * @access  public
     * @param
     *
     * @return void
     */
    function paydollar()
    {
    }

    function __construct()
    {
        $this->paydollar();
    }

    /**
     * 
     * @param   array   $order     
     * @param   array   $payment   
     */
    function get_code($order, $payment)
    {
        $spnUrl					= trim($payment['paydollar_spnUrl']);
        
    	$data_merchantId		= trim($payment['paydollar_merchantId']);
    	$data_currCode			= trim($payment['paydollar_currCode']);
    	$data_lang				= trim($payment['paydollar_lang']);
    	$data_payType			= trim($payment['paydollar_payType']);
    	$data_payMethod			= trim($payment['paydollar_payMethod']);
    	
    	$data_successUrl		= $GLOBALS['ecs']->url() . 'user.php?act=order_detail&order_id=' . $order['log_id']; 
    	$data_failUrl			= $GLOBALS['ecs']->url() . 'user.php?act=order_detail&order_id=' . $order['log_id'];
    	$data_cancelUrl			= $GLOBALS['ecs']->url() . 'user.php?act=order_detail&order_id=' . $order['log_id'];
    	
    	$data_amount			= $order['order_amount'];
    	
    	$prefix					= trim($payment['paydollar_prefix']);
    	$data_orderRef			= $prefix . $order['log_id'];
    	$data_remark			= $order['order_sn'];
            	
    	$secureHashSecret		= trim($payment['paydollar_secureHashSecret']);
    	$data_secureHash 		= '';
    	if(trim($secureHashSecret)!= ''){
    		$data_secureHash 	= $this->generatePaymentSecureHash($data_merchantId, $data_orderRef, $data_currCode, $data_amount, $data_payType, $secureHashSecret);
    	}
    	
    	$def_url  = '<br />';
	    $def_url .= '<form style="text-align:center;" method=post action="'. $spnUrl .'" >';
	    
        $def_url .= '<input type="hidden" name="merchantId" value="'.$data_merchantId.'">';
        $def_url .= '<input type="hidden" name="currCode" 	value="'.$data_currCode.'">';
        $def_url .= '<input type="hidden" name="lang" 		value="'.$data_lang.'">';
        $def_url .= '<input type="hidden" name="payType" 	value="'.$data_payType.'">';
        $def_url .= '<input type="hidden" name="payMethod" 	value="'.$data_payMethod.'">';
        $def_url .= '<input type="hidden" name="orderRef" 	value="'.$data_orderRef.'">';        
        $def_url .= '<input type="hidden" name="amount" 	value="'.$data_amount.'">';
        $def_url .= '<input type="hidden" name="successUrl"	value="'.$data_successUrl.'">';
        $def_url .= '<input type="hidden" name="failUrl" 	value="'.$data_failUrl.'">';
        $def_url .= '<input type="hidden" name="cancelUrl" 	value="'.$data_cancelUrl.'">';
        $def_url .= '<input type="hidden" name="secureHash"	value="'.$data_secureHash.'">';
        $def_url .= '<input type="hidden" name="remark"	value="'.$data_remark.'">';
        
	    $def_url .= '<input type="submit" value="'.$GLOBALS['_LANG']['paydollar_button'].'">';
        $def_url .= '</form>';
                
        return $def_url;
    }
    
    function generatePaymentSecureHash($merchantId, $merchantReferenceNumber, $currencyCode, $amount, $paymentType, $secureHashSecret) 
    {
		$buffer = $merchantId . '|' . $merchantReferenceNumber . '|' . $currencyCode . '|' . $amount . '|' . $paymentType . '|' . $secureHashSecret;
		return sha1($buffer);
	}

	function verifyPaymentDatafeed($src, $prc, $successCode, $merchantReferenceNumber, $paydollarReferenceNumber, $currencyCode, $amount, $payerAuthenticationStatus, $secureHashSecret, $secureHash) 
	{
		$buffer = $src . '|' . $prc . '|' . $successCode . '|' . $merchantReferenceNumber . '|' . $paydollarReferenceNumber . '|' . $currencyCode . '|' . $amount . '|' . $payerAuthenticationStatus . '|' . $secureHashSecret;
		$verifyData = sha1($buffer);
		if ($secureHash == $verifyData) {
			return true;
		}
		return false;
	}
    
    function respond()
    {
    	$payment 				= get_payment('paydollar');
    	$prefix					= trim($payment['paydollar_prefix']);
    	
    	//get post data start
		$successcode 			= $_POST ['successcode'];
		$src 					= $_POST ['src']; 
		$prc 					= $_POST ['prc']; 
		$ref 					= $_POST ['Ref'];
		$payRef 				= $_POST ['PayRef'];
		$amt 					= $_POST ['Amt']; 
		$cur 					= $_POST ['Cur']; 
		$payerAuth 				= $_POST ['payerAuth']; 
		$ord 					= $_POST ['Ord']; 
		$holder 				= $_POST ['Holder']; 
		$remark 				= $_POST ['remark']; 
		$authId 				= $_POST ['AuthId']; 
		$eci 					= $_POST ['eci']; 
		$sourceIp 				= $_POST ['sourceIp']; 
		$ipCountry 				= $_POST ['ipCountry']; 		
		$mpsAmt 				= $_POST ['mpsAmt'];
		$mpsCur 				= $_POST ['mpsCur'];
		$mpsForeignAmt 			= $_POST ['mpsForeignAmt'];
		$mpsForeignCur 			= $_POST ['mpsForeignCur'];
		$mpsRate 				= $_POST ['mpsRate']; 
		$cardlssuingCountry 	= $_POST ['cardlssuingCountry']; 
		$payMethod 				= $_POST ['payMethod']; 
		//get post data end

		echo 'OK';
		
		/* Secure Hash Start */
		if(isset( $_POST ['secureHash'] )){
			$secureHash 		= $_POST ['secureHash'];
		}	
		$secureHashSecret		= trim($payment['paydollar_secureHashSecret']);		
		if (isset ( $secureHash ) && $secureHash && $secureHashSecret) {			
			$secureHashs = explode ( ',', $secureHash );
			while ( list ( $key, $value ) = each ( $secureHashs ) ) {
				$verifyResult = $this->verifyPaymentDatafeed ( $src, $prc, $successcode, $ref, $payRef, $cur, $amt, $payerAuth, $secureHashSecret, $value );
				echo '$secureHash=[' . $value . ']';
				if ($verifyResult) {
					echo '-verifyResult= true';
					break;
				} else {
					echo '-verifyResult= false';
				}
			}			
			if (! $verifyResult) {
				echo '-SecureHash Not OK';
				//return false;
				exit();
			} else {
				echo '-SecureHash OK';
			}
		}
		/* Secure Hash End */
		
		$log_id = $ref ;
		
		//Explode reference number and get the value only
		if($prefix != ''){
			$flag = preg_match("/".$prefix."/", $ref);
				
			if ($flag == 1){
				$refArr = explode($prefix,$ref);
				$log_id = $refArr[1];
			}
		}
		
		//retrieve the order record
		//$order = order_info($order_id) ;
		if (isset ( $successcode ) && $successcode == "0") {
			order_paid($log_id);
			echo '-Payment Success';
	        //return true;
			exit();
		}else{
			echo '-Payment Failed';
			//return false;
			exit();
		}
    }   
}

?>