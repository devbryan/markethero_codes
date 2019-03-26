<?php 
if(isset($_POST['submit'])) {
    $pid = $_POST['pid'];
    $af_id = $_POST['af_id'];
    $old_order_id = (isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id'])) ? $_REQUEST['order_id'] : '';

    $get_product = $db->rp_getData("product", "*", "id=".$pid." AND campaign_id > 0 AND isDelete=0 ", "id DESC ");
    if($get_product && mysqli_num_rows($get_product) > 0) {
        $product = mysqli_fetch_array($get_product);


        $vender_id      = $product['user_id'];
        $vender_email   = $db->rp_getValue("user","email", "id=".$vender_id);

        $campaign_id    = $product['campaign_id'];
        if(!empty($old_order_id)){
            $price          = 1;
        }else{
            $price          = $product['price'];
        }
        
        $product_name   = $product['name'];
        $ip             = $db->rp_get_client_ip();

        $date = date('y-m-d H:i:s');
        $orderstatus = 1;
        $subtotal = $price; 
        $payment_mode = ($db->rp_getValue("payment_mode", "spgateway", "id=1")) ? "Live" : "Test";
        $payment_process = "spgateway";
        $payment_method = "spgateway";
        $fineltotal = $subtotal;
        $fname = isset($_POST['fname']) ? $_POST['fname'] : '';
        $lname = isset($_POST['lname']) ? $_POST['lname'] : ''; 
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
        $business_name = isset($_POST['business_name']) ? $_POST['business_name'] : '';
        $tax_id = isset($_POST['business_tax_no']) ? $_POST['business_tax_no'] : '';

        /********** fetch campaign detail start *************/
        $campaign_result= $db->rp_getData("campaign", "*", "id=".$campaign_id." AND isDelete=0 " ); 
        $campaign_data = mysqli_fetch_array($campaign_result);
        $campaign_name = $campaign_data['campaign_name'];
        $p_gateway     = $campaign_data['payment_gateway'];
        $error_page    = $campaign_data['error_page'];
        $sp_api_type   = $campaign_data['api_type'];
        /********** fetch campaign detail end *************/


        /********** fetch integration detail start *************/
        $get_integration = $db->rp_getData("integrations", "*", "uid=".$vender_id." AND p_gateway='".$p_gateway."' AND sp_api_type='".$sp_api_type."' AND isPrimary=1 AND isDelete=0", "id DESC");
        if(!$get_integration || mysqli_num_rows($get_integration) <= 0) {
            $get_integration = $db->rp_getData("integrations", "*", "uid=".$vender_id." AND p_gateway=".$p_gateway." AND sp_api_type=".$sp_api_type." AND isPrimary=1 AND isDelete=0", "id DESC");
        }
        $integration   = mysqli_fetch_array($get_integration);
        $integration_id= $integration['id'];
        $p_gateway     = $integration['p_gateway'];
        $account_name  = $integration['account_name'];
        $base_currency = $integration['base_currency'];
        $sp_api_type   = $integration['sp_api_type'];
        $sp_merchant_id= $integration['sp_merchant_id'];
        $sp_hashkey    = $integration['sp_hashkey'];
        $sp_hashiv     = $integration['sp_hashiv'];
        /********** fetch integration detail end *************/
        

        /********** insert cart detail start *************/
        $rows = array(  "campaign_id",
                        "vender_id",
                        "pid",
                        "af_id",
                        "integration_id",
                        "payment_process",
                        "payment_mode",
                        "orderdate", 
                        "orderstatus", 
                        "subtotal", 
                        "payment_method", 
                        "finaltotal", 
                        "fname", 
                        "lname", 
                        "email", 
                        "phone", 
                        "business_name",
                        "tax_id",
                        "ip"
                    );

        $values = array($campaign_id, 
                        $vender_id,
                        $pid, 
                        $af_id,
                        $integration_id,
                        $payment_process,
                        $payment_mode,
                        $date, 
                        $orderstatus, 
                        $subtotal, 
                        $payment_method, 
                        $fineltotal, 
                        $fname, 
                        $lname, 
                        $email, 
                        $phone, 
                        $business_name,
                        $tax_id,
                        $ip
                    );

        if( !empty($old_order_id) ) {
            $parent_cart_id = $db->rp_getValue("cartdetails", "cart_id", " transaction_id ='".$old_order_id."' " );
            array_push($rows, 'parent_cart_id');
            array_push($values, $parent_cart_id);
        }

        $inserted_id = $db->rp_insert("cartdetails", $values, $rows);
        $transaction_id = SC_ORDER_PREFIX.$inserted_id;
 
        $update_rows = array('transaction_id' => $transaction_id, "notes" => $transaction_id);
        $where = " cart_id = ".$inserted_id;
        $db->rp_update("cartdetails", $update_rows, $where);
        /********** insert cart detail end *************/


        /*********** Redirect To SPgateway Process Start ****************/
    	$ReturnURL = SITEURL.'thankyou/?error='.urlencode($error_page);
    	$NotifyURL = SITEURL.'thankyou';
    	$CustomerURL = SITEURL.'thankyou';
    	$ClientBackURL = SITEURL.'thankyou';
        if($p_gateway=='spgateway' && $sp_api_type=='standard' && $sp_merchant_id!='' && $sp_hashkey!='' && $sp_hashiv!='') {

            $form_action = $db->get_spgateway_url();
            $MerchantID = $sp_merchant_id;
            $Key = $sp_hashkey;
            $IV = $sp_hashiv;

            $Version = '1.4'; 
            $RespondType = 'JSON'; // Options 1) String 2) JSON
            $TimeStamp = time();
            $LangType = 'en'; // Option 1) en 2) zh-tw
            $MerchantOrderNo = $transaction_id; 
            $Amt = $fineltotal;
            $ItemDesc = $product_name;
            $email = $email;
            $LoginType = '0';  // Options 1) 1 for Spgateway membership is required  2) 0 for Spgateway membership is not required
            

            $parameter = array('MerchantID' => $MerchantID, 'RespondType' => $RespondType, 'TimeStamp' => $TimeStamp,
            'Version' => $Version, 'MerchantOrderNo' => $MerchantOrderNo, 'Amt' => $Amt, 'ItemDesc' => $ItemDesc );
            $TradeInfo = $db->create_mpg_aes_decrypt($parameter, $Key, $IV);
 
            $TradeSha_data = "HashKey=".$Key."&".$TradeInfo."&HashIV=".$IV;
            $TradeSha = strtoupper(hash("SHA256", $TradeSha_data));
            ?>
            <form name='Spgateway' id='Spgateway' method='post' action='<?php echo $form_action; ?>'>
                <input type='hidden' name='MerchantID' value='<?php echo $MerchantID; ?>'/>
                <input type="hidden" name="TradeInfo" value="<?php echo $TradeInfo; ?>" />
                <input type="hidden" name="TradeSha" value="<?php echo $TradeSha; ?>" />
                <input type="hidden" name="Version" value="<?php echo $Version; ?>" />
                <input type="hidden" name="RespondType" value="<?php echo $RespondType; ?>" />
                <input type="hidden" name="TimeStamp" value="<?php echo $TimeStamp; ?>" />
                <input type="hidden" name="LangType" value="<?php echo $LangType; ?>" />
                <input type="hidden" name="MerchantOrderNo" value="<?php echo $MerchantOrderNo; ?>" />
                <input type="hidden" name="Amt" value="<?php echo $Amt; ?>" />
                <input type="hidden" name="ItemDesc" value="<?php echo $ItemDesc; ?>" />
                <input type="hidden" name="Email" value="<?php echo $email; ?>" />
                <input type="hidden" name="LoginType" value="<?php echo $LoginType; ?>" />
                <input type="hidden" name="ReturnURL" value="<?php echo $ReturnURL; ?>" />
            </form>
            <script> setTimeout(function(){ document.getElementById("Spgateway").submit(); }, 5000); </script>
            <style> body{ text-align: center; margin-top: 100px; } </style>
            <img src="<?php echo SITEURL.'img/loader.gif'; ?>" />
            <?php
            exit;
        } elseif($p_gateway=='spgateway' && $sp_api_type=='cashflow' && $sp_merchant_id!='' && $sp_hashkey!='' && $sp_hashiv!='') {

            $is_amount_zero = false;
            if( empty($fineltotal) ) {
                $is_amount_zero = true;
                $fineltotal = 1;
            }
            
            $input_array = array(
                'TimeStamp' => time(),
                'Version' => "1.0",
                'MerchantOrderNo' => $transaction_id,
                'Amt' => $fineltotal,
                'ProdDesc' => $product_name,
                'PayerEmail' => $email,
                'CardNo' => $_REQUEST['cardno'],
                'Exp' => $_REQUEST['exp_year'].$_REQUEST['exp_month'],
                'CVC' => $_REQUEST['cvc'],
                'TokenSwitch' => 'get',
                'TokenTerm' => $email,
                'IP' => "77.104.168.15",
                'ReturnURL' => $ReturnURL,
        		'NotifyURL' => $NotifyURL,
        		'ClientBackURL' => $CustomerURL
            );

            $post_data_str = http_build_query($input_array);
            $post_data = $db->spgateway_encrypt($sp_hashkey, $sp_hashiv, $post_data_str);
            $url = $db->get_spgateway_cashflow_url();
            $param = array(
                "MerchantID_" => $sp_merchant_id,
                "Pos_"        => 'JSON',
                "PostData_"   => $post_data,
            );
            $param_str = http_build_query($param);
            $spg_reply = $db->curl_work($url, $param_str);
      
            $file    = __DIR__.'/kp_log_error_log.txt';    
            $content = json_encode($spg_reply);
            $file_content = "=============== Write At => " . date( "y-m-d H:i:s" ) . " =============== \r\n";
            $file_content .= $content . "\r\n\r\n";
            file_put_contents( $file, $file_content, FILE_APPEND | LOCK_EX );
    
    	    //$string = 
	    

            if(isset($spg_reply['http_status']) && $spg_reply['http_status'] == '200' && isset($spg_reply['web_info'])) {
            
                // echo preg_replace("/[\r\n]+/", " ", $spg_reply['web_info']);
            
                $response = json_decode($spg_reply['web_info']);
                $sp_update_rows = array(
                    'gateway_type' => 'cashflow', 
                    'gateway_response' => addslashes($spg_reply['web_info']),
                    'cardno' => $_REQUEST['cardno']
                );
                $sp_where = " cart_id = ".$inserted_id;
                $db->rp_update("cartdetails", $sp_update_rows, $sp_where);


                //***** fetch product recurring cycle and store into cart_recurring_time table Start ****
                if( empty($old_order_id) ) {
                    $get_p_cycle = $db->rp_getData("recurring_time", "*", " pid=".$pid, "display_order DESC ");
                    $ort_count = 0;
                    if($get_p_cycle && mysqli_num_rows($get_p_cycle) > 0) {
                        while( $cycle_data = mysqli_fetch_array($get_p_cycle) ) {

                            $rows = array( "cart_id",
                                        "is_after", 
                                        "year_after", 
                                        "month_after", 
                                        "day_after", 
                                        "price", 
                                        "credit_point",
                                        "currency", 
                                        "period", 
                                        "times", 
                                        "year", 
                                        "month", 
                                        "day", 
                                        "display_order" 
                                    );
                            $values = array( $inserted_id, 
                                        $cycle_data['is_after'], 
                                        $cycle_data['year_after'], 
                                        $cycle_data['month_after'], 
                                        $cycle_data['day_after'], 
                                        $cycle_data['price'], 
                                        $cycle_data['credit_point'], 
                                        $cycle_data['currency'], 
                                        $cycle_data['period'], 
                                        $cycle_data['times'], 
                                        $cycle_data['year'], 
                                        $cycle_data['month'], 
                                        $cycle_data['day'], 
                                        $cycle_data['display_order']
                                    );
                    
                            $db->rp_insert('cart_recurring_time', $values, $rows);

                        }
                    }
                }
                //***** fetch product recurring cycle and store into cart_recurring_time table End ******
                //$response->Status= 'SUCCESS';
                if(isset($response->Status) && $response->Status == 'SUCCESS' ) {

                    if( !empty($old_order_id) ) {
                        $sp_update_rows = array(
                            'gateway_type' => 'cashflow', 
                            'gateway_response' => addslashes($spg_reply['web_info']),
                            'cardno' => $_REQUEST['cardno']
                        );
                        $sp_where = " transaction_id = '".$old_order_id."' ";
                        $db->rp_update("cartdetails", $sp_update_rows, $sp_where);
                        //echo '<input type="hidden" name="payment_info" id="payment_info" value="1" />';
                    }

                    $sp_update_rows = array( 'orderstatus' => '2' );    
                    $sp_where = " cart_id = ".$inserted_id;
                    $db->rp_update("cartdetails", $sp_update_rows, $sp_where);

                    //******** Start deauthorize payment for zero amount product and change creditcard details *********
                    if( ( !empty( $old_order_id ) || $is_amount_zero ) && !empty( $response->Result ) ) {
                        $result = $response->Result;
                        $cancel_merchantorderno = isset($result->MerchantOrderNo) ? $result->MerchantOrderNo : '';
                        $cancel_tradeno = isset($result->TradeNo) ? $result->TradeNo : '';

                        $cancel_input_array = array(
                            'RespondType' => 'JSON',
                            'Version' => '1.0',
                            'Amt' => $fineltotal,
                            'MerchantOrderNo' => $cancel_merchantorderno,
                            'TradeNo' => $cancel_tradeno,
                            'IndexType' => '1',
                            'TimeStamp' => time(),
                            'NotifyURL' => SITEURL
                        );
                        
                        $cancel_post_data_str = http_build_query($cancel_input_array);
                        $cancel_post_data = $db->spgateway_encrypt($sp_hashkey, $sp_hashiv, $cancel_post_data_str);
                        $cancel_url = $db->get_spgateway_cashflow_cancel_url();
                        $cancle_param = array(
                            "MerchantID_" => $sp_merchant_id,
                            "Pos_"        => 'JSON',
                            "PostData_"   => $cancel_post_data,
                        );
                        $cancel_param_str = http_build_query($cancle_param);
                        $spg_reply = $db->curl_work($cancel_url, $cancel_param_str);

                    }
                    //******** End deauthorize payment for zero amount product and change creditcard details *********

                    //********** Checkout page projects intigration **************//
                    
                    //print_r($product);
                    //$product['em_intigration']
                    //$product['em_success_trans']
                    //$product['em_service']
                    //$product['em_tags']
                    
                    if($product['em_intigration'] == 0  )
                    { 
                        $tags = $product['em_tags'];
                        $get_email_int_e = $db->rp_getData("user_email_int_ervice", "*", "service='".$product['em_service']."' AND user_id=".$vender_id, "id DESC ");
                        if( $product['em_service'] == "projects" )
                        { 
                           if($get_email_int_e && mysqli_num_rows($get_email_int_e) > 0) 
                            {
                                $get_email_int_d = mysqli_fetch_array($get_email_int_e);
                                $api_key = $get_email_int_d['api_key'];

                                $url = 'https://.com/save_shortcart_plead.php';
                                $queryStrung = "api_key=".$api_key."&ptags=".$tags."&fname=".$fname."&lname=".$lname."&email=".$email."";
                                 $fUrl = $url."?".$queryStrung;
                                $res  = file_get_contents($fUrl);
                                //echo $res  ;

                            }
                        }

                    }
                    //exit;

                    //********** End Checkout page projects intigration **************//

                    echo '<style> body{ text-align: center; margin-top: 100px; } </style>';
                    echo '<img src="'.SITEURL.'img/loader.gif" />';
                    echo '<form name="Spgateway_CF" method="post" id="Spgateway_CF" action="'.SITEURL."thank-you/?error=".urlencode($error_page).'">';
                    echo '<input type="hidden" name="SP_transaction_id" id="SP_transaction_id" value="'.$transaction_id.'" />';
                    if( !empty($old_order_id) ) {
                        echo '<input type="hidden" name="payment_info" id="payment_info" value="1" />';
                    }
                    echo '</form>'; 
                    echo '<script> setTimeout(function(){ document.getElementById("Spgateway_CF").submit(); }, 5000); </script>';
                    exit;
                } else {
                    $_REQUEST['error_msg'] = $response->Message;    
                } 
            } else {
                $_REQUEST['error_msg'] = 'Something went wrong please try again.';
            }
        } else {
            ?><script>
                alert("sorry you will be not able to payment please contact to <?php echo $vender_email; ?>");
            </script><?php
        }
        /*********** Redirect To SPgateway Process End ******************/
    }
}

?>