<?php

include("connect.php");
include("checkout_process.php");
$pid = '';
if( isset($_REQUEST['sc_affid']) && !empty($_REQUEST['sc_affid']) && isset($_REQUEST['sc_pid']) && !empty($_REQUEST['sc_pid']) ) {
	 //$qs = explode('_', $_REQUEST['qs']);		
	 $pid = ( isset($_REQUEST['sc_pid']) && !empty($_REQUEST['sc_pid']) ) ? $_REQUEST['sc_pid'] : '';
	 $af_id = ( isset($_REQUEST['sc_affid']) && !empty($_REQUEST['sc_affid']) ) ? $_REQUEST['sc_affid'] : '';

	}

if( isset($_REQUEST['qs']) && !empty($_REQUEST['qs']) ) {
	$qs = explode('_', $_REQUEST['qs']);
	$pid = ( isset($qs[1]) && !empty($qs[1]) ) ? $qs[1] : '';
	$af_id =( isset($qs[2]) && !empty($qs[2]) ) ? $qs[2] : '';
}
$page = "Checkout";

$p_gateway     = '';
$sp_api_type   = '';
$basic_info_attr = '';
$form_control_attr = '';
$order_id = '';
$button_text = 'Buy Now !';

//if( isset($_REQUEST['sc_affid']) && !empty($_REQUEST['sc_affid']) && isset($_REQUEST['sc_pid']) && !empty($_REQUEST['sc_pid']) ) {
	//$qs = explode('_', $_REQUEST['qs']);
	// $pid = ( isset($_REQUEST['sc_pid']) && !empty($_REQUEST['sc_pid']) ) ? $_REQUEST['sc_pid'] : '';
	// $cid = ( isset($_REQUEST['sc_affid']) && !empty($_REQUEST['sc_affid']) ) ? $_REQUEST['sc_affid'] : '';
//echo $pid; exit;	
if( isset($pid) && !empty($pid) && $pid!=''  )
{
	$get_product = $db->rp_getData("product", "*", "id=".$pid." AND campaign_id > 0 AND isDelete=0 ", "id DESC ");
	if($get_product && mysqli_num_rows($get_product) > 0) {
        $product = mysqli_fetch_array($get_product);
        $user_id 		= $product['user_id'];
		$campaign_id	= $product['campaign_id'];
		$name 			= $product['name'];
        $subtotal       = number_format($product['price'],2);
		$tag 			= $product['tag'];
		$image			= $product['image'];
        $short_descr    = $product['short_descr'];
        $descr_header   = $product['descr_header'];
		$descr			= html_entity_decode($product['descr']);
		$condition_desc = $product['condition_desc'];
		$category_id	= $product['category_id'];

		$testimonials   = $db->rp_getData("testimony", "*", "pid=".$pid." AND isDelete=0 ", "id DESC" ); 

        $campaign_result= $db->rp_getData("campaign", "*", "id=".$campaign_id." AND isDelete=0 " ); 
        $campaign_data = mysqli_fetch_array($campaign_result);
        $campaign_name = $campaign_data['campaign_name'];
        $p_gateway     = $campaign_data['payment_gateway'];
        $chk_login     = $campaign_data['chk_login'];
        $sp_api_type   = $campaign_data['api_type'];

        $tracking_header_code = json_decode($product['tracking_header_code']);
        $tracking_body_code = json_decode($product['tracking_body_code']);

        if($chk_login == 'facebook') {
            $basic_info_attr = 'readonly';
            $form_control_attr = 'style="display:none;"';
        }

        $price          = ( $p_gateway == 'spgateway' && $sp_api_type == 'cashflow') ? '1.00' : number_format($subtotal,2);
    } else {
    	$db->rp_location(SITEURL);	
    }
} else {
	$db->rp_location(SITEURL);
}

if( isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id']) ) {
    $order_id = $_REQUEST['order_id'];
    $subtotal = 1.00;
    $button_text = 'Update';
    $get_order = $db->rp_getData("cartdetails", "*", " pid=".$pid." AND campaign_id > 0 AND transaction_id='".$order_id."' ", "cart_id DESC ");
    if($get_order && mysqli_num_rows($get_order) > 0) {
        $order_data = mysqli_fetch_array($get_order);
        $_REQUEST['fname'] = $order_data['fname'];
        $_REQUEST['lname'] = $order_data['lname'];
        $_REQUEST['email'] = $order_data['email'];
        $_REQUEST['phone'] = $order_data['phone'];
        $_REQUEST['business_name'] = $order_data['business_name'];
        $_REQUEST['business_tax_no'] = $order_data['tax_id'];
    }
}

$display_lastname = $db->rp_getValue( "user", "chkout_hide_lastname", "id=".$user_id);

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?php echo $page." - ".SITETITLE; ?></title>
        <script src="<?php echo SITEURL; ?>js/libs/jquery.min.js"></script>
        <link href="<?php echo SITEURL; ?>css/chkout-style.css" rel="stylesheet">
        <!-- Bootstrap -->
        <link href="<?php echo SITEURL; ?>css/chkout-bootstrap.min.css" rel="stylesheet">
		<link href="<?php echo SITEURL; ?>css/font-awesome.min.css" rel="stylesheet">

		<?php if( isset($tracking_header_code) && $tracking_header_code!="" )
    	{ echo $tracking_header_code ; }?>
    </head>
    

    <body>
	
	<?php if( isset($tracking_body_code) && $tracking_body_code!="" )
	{ echo $tracking_body_code ; }?>
	<div class="container-fluid plr0"> <!-- class : bg-offwhite -->
		<div class="container">
			<div class="col-md-12">
				<div class="row">
					<div class="email">
						<i class="fa fa-envelope-o"></i> Email : 
						<a href="mailto:<?php echo SUPPORTMAIL; ?>"><?php echo SUPPORTMAIL; ?></a>
					</div>
				</div>
			</div>
		</div>
	</div>
	
	<div class="container"> <!-- class : bg-offwhite  -->
		<div class="col-md-12">
			<div class="row">
                <?php /*
				<div class="form-header">
					<div class="col-md-3 text-center">
						<img src="<?php echo SITEURL; ?>img/lock1.png" width="50" height="64" class="img-responsive" ><h2>Secure <br> Payment</h2>
					</div>
					<div class="col-md-4 col-sm-7 col-xs-7">
						<p>Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s.</p>	
					</div>
					<div class="privacy col-md-5 col-sm-12 col-xs-12">
						<img src="<?php echo SITEURL; ?>img/norton-horizontal.png" width="130" height="66" class="img img-responsive" >
						<img src="<?php echo SITEURL; ?>img/trust.png" width="130" height="36" class="img img-responsive" >
						<img src="<?php echo SITEURL; ?>img/pci.png" width="130" height="68" class="img img-responsive" >
					</div>
				</div>
                */ ?>
				<div class="clearfix"></div>
				<div class="form-bottom pb15">
					<div class="col-md-6">
						<div class="bg-white">
							<form action="" method="post" onSubmit="return check_form();">
								  <div class="form-group">
                                        <?php if($chk_login == 'facebook' || $chk_login == 'email_facebook') { ?>
                                        <div class="col-md-12 ptb15">
                                            <button type="button" class="btn btn-primary btn-fb active mt-3">
                                                <img src="<?php echo SITEURL; ?>img/fb_logo.png" width="30px" />&nbsp;&nbsp;&nbsp; Login with Facebook
                                            </button>
                                        </div>
                                        <?php } ?>
										<div class="<?php echo ($display_lastname) ? 'col-md-12' : 'col-md-6'; ?>" <?php echo $form_control_attr; ?>>
											<label>姓名 <span>*</span></label>
											<input type="text" class="form-control" name="fname" id="fname" value="<?php echo isset($_REQUEST['fname']) ? $_REQUEST['fname'] : ''; ?>" <?php echo $basic_info_attr; ?> />
										</div>
                                        <?php if(!$display_lastname) { ?>
										<div class="col-md-6" <?php echo $form_control_attr; ?>>
											<label>姓 <span>*</span></label>
											<input type="text" class="form-control" name="lname" id="lname" value="<?php echo isset($_REQUEST['lname']) ? $_REQUEST['lname'] : ''; ?>" <?php echo $basic_info_attr; ?> />
										</div>
                                        <?php } ?>
										<div class="col-md-12 ptb15" <?php echo $form_control_attr; ?>>
                                            <label>Email Address <span>*</span></label>
											<input type="text" class="form-control" name="email" id="email" value="<?php echo isset($_REQUEST['email']) ? $_REQUEST['email'] : ''; ?>" <?php echo $basic_info_attr; ?> />
										</div>
                                        <div class="col-md-12 ptb15" >
                                            <label>Phone <span>*</span></label>
                                            <input type="text" class="form-control"  name="phone" id="phone" value="<?php echo isset($_REQUEST['phone']) ? $_REQUEST['phone'] : ''; ?>"  onkeypress="return isnumber()" />
                                        </div>
                                        <div class="col-md-12 ptb15">
                                            <label>抬頭(三聯式發票)</label>
                                            <input type="text" class="form-control" name="business_name" id="business_name" value="<?php echo isset($_REQUEST['business_name']) ? $_REQUEST['business_name'] : ''; ?>" />
                                        </div>
                                        <div class="col-md-12 ptb15">
											<label>統編(三聯式發票)</label>
											<input type="text" class="form-control" name="business_tax_no" id="business_tax_no" value="<?php echo isset($_REQUEST['business_tax_no']) ? $_REQUEST['business_tax_no'] : ''; ?>" />
										</div>
                                        
										
										<?php /*
										<div class="col-md-12 ptb15">
											<label>label</label>
											<select class="form-control">
												<option>Taiwan</option>
											</select>
										</div>
										<div class="col-md-12 ptb15">
											<input type="text" class="form-control">
										</div>
										<div class="col-md-12 ptb15">
											<input type="text" class="form-control" placeholder="label">
										</div>
										<div class="col-md-6 ptb15">
											<input type="text" class="form-control" placeholder="label">
										</div>
										<div class="col-md-6 ptb15">											
											<select class="form-control">
												<option>Taiwan</option>
											</select>
										</div>
										<div class="col-md-12 ptb15">
											<input type="text" class="form-control" placeholder="label">
										</div>
                                        */ ?>
								  </div>
								
                                <?php /*
								<div class="col-md-12 ptb15">
									<div class="blue-bar">
										<i class="fa fa-check"></i> lorem ipsum
									</div>
								</div>
                                */ ?>
                                

								<div class="clearfix"></div>
                                <?php if( $p_gateway == 'spgateway' && $sp_api_type == 'standard') { ?> 
								<div class="col-md-12 ptb15">
                                    <div class="gateway_box">
                                        <img src="<?php echo SITEURL; ?>/img/gateway/spgateway.jpg" width="80" />
                                    </div>
									<div class="alert alert-danger text-center">
								        You will be sent to SPGateway website for secure payment.
                                	</div>
								</div>
                                <?php } ?>
                                
                                <?php /*
								<div class="col-md-12 ptb15">
									<div class="bg-gray text-center">
										lorem ipsum
									</div>
								</div>
								
								<div class="col-md-6">
									<div class="bg-blue-border text-center">
										<p>1 item</p>
										<span>$100.00</span>
									</div>
								</div>
								
								<div class="col-md-6">											
									<div class="bg-gray-border text-center">
										<p>3 item</p>
										<span>$40.00</span>
									</div>
								</div>
								
								<div class="col-md-7 col-sm-6 col-xs-6 ptb15 pr0">
									<div class="bg-light-blue">
										<label>
											<input type="checkbox">
											<?php echo $campaign_name.' - '.$name; ?>
										</label>
									</div>
								</div>
                                <div class="col-md-5 col-sm-6 col-xs-6 ptb15 pl0">
									<div class="bg-dark-blue text-center">
										$<?php echo $price; ?>
									</div>
								</div>
                                */ ?>
                                
                                <?php if( $p_gateway == 'spgateway' && $sp_api_type == 'cashflow') { ?> 
                                    <div class="form-group">
                                        <div class="col-md-12 ptb15">
                                            <label>信用卡號 <span>*</span></label>
                                            <input type="text" class="form-control" name="cardno" id="cardno" onkeypress="return isnumber()" value="<?php echo isset($_REQUEST['cardno']) ? $_REQUEST['cardno'] : ''; ?>" />
                                        </div>
                                        <div class="col-md-6"> 
                                            <label>截止月 <span>*</span></label>
                                            <select name="exp_month" id="exp_month" class="form-control">
                                                <option value=""> Select Expiry Month</option>
                                                <?php for ($month = 1; $month < 13; $month++) { ?>
                                                    <?php if($month < 10) { $month = '0'.$month; } ?>
                                                    <option value="<?php echo $month; ?>"> <?php echo $month; ?> </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>截止年 <span>*</span></label>
                                            <select name="exp_year" id="exp_year" class="form-control">
                                                <option value=""> Select Expiry Year</option>
                                                <?php for ($year = date('y'); $year < date('y')+20; $year++) { ?>
                                                    <option value="<?php echo $year; ?>"> <?php echo $year; ?> </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-12 ptb30">
                                            <label>CVC(信用卡背面三碼) <span>*</span></label>
                                            <input type="text" class="form-control" name="cvc" id="cvc" onkeypress="return isnumber()" />
                                        </div>
                                    </div>
    							<?php }

                                if(isset($_REQUEST['error_msg']) && $_REQUEST['error_msg'] != '') {
                                    echo '<div class="col-md-12 ptb15">';
                                    echo '<div class="alert alert-danger text-center">';
                                    echo $_REQUEST['error_msg'];
                                    echo '</div></div>';
                                }
                                
                                ?>

								
								<div class="col-md-12">
                                    <div class="short-form">
                                        <h3>Order summary</h3>
                                        <div class="pro_detail"><?php echo $name; ?></div>
                                        <div class="pro_detail">NT $<?php echo $subtotal; ?> Nowadays</div>
										<div class="white-div">
											<div class="info-block">
												Sub Total
												<span>$<?php echo $subtotal; ?></span>
											</div>
											<div class="info-block">
												Transport
												<span>$0.00</span>
											</div>
											<div class="pull-right">
                                                Total:
												<span class="usd">TWD</span>
												$<?php echo $subtotal; ?>
											</div>
											<div class="clearfix"></div>
										</div>
									</div>
								</div>
							
								
								<div class="col-md-12 ptb15 green-btn">
                                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>" />
									<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
									<input type="hidden" name="af_id" value="<?php echo $af_id; ?>" />
									
									<button type="submit" name="submit" id="submit" class="btn btn-success btn-block "><i class="fa fa-shopping-cart"></i><?php echo $button_text; ?></button>
								</div>
								<div class="clearfix"></div>
							</form>
										
							<div class="clearfix"></div>
						</div>
					</div>
					
					<div class="col-md-6">
						<div class="product">
							<div class="col-md-12 col-sm-12 col-xs-12">
								<?php if($image != '' && file_exists(PRODUCT.$image)) { ?>
								<img src="<?php echo SITEURL.PRODUCT.$image; ?>" class="img img-responsive" />
								<?php } /*else { ?>
								<img src="<?php echo SITEURL; ?>img/book.png" class="img img-responsive" />
								<?php }*/ ?>
							</div>
						</div>
                        <?php /*
                        <div class="col-md-8 col-sm-8 col-xs-8">
                            <h2><?php echo $name; ?></h2>
                            <h4><?php echo $short_descr; ?></h4>
                        </div>
                        */ ?>
                        <div class="clearfix"></div>
						<div class="list-detail">
							<?php /* <h2><?php echo $descr_header; ?></h2> */ ?>
							<?php echo $descr; ?>
                            <?php /*
							<?php echo $condition_desc; ?>
                            <ul>
							  <li>Lorem Ipsum is simply dummy text of the printing and typesetting industry.</li>
							  <li>Lorem Ipsum is simply dummy text of the printing and typesetting industry.</li>
							  <li>Lorem Ipsum is simply dummy text of the printing and typesetting industry.</li>
							  <li>Lorem Ipsum is simply dummy text of the printing and typesetting industry.</li>
							</ul>
							*/ ?>
						</div>
						<?php 
						if($testimonials && mysqli_num_rows($testimonials) > 0) {
						    echo '<div class="client-detail">';
                        	while($testimonial = mysqli_fetch_array($testimonials)) {
								$name  = $testimonial['name'];
								$image = $testimonial['image_path'];
								$descr = $testimonial['descr'];
								?>
								<div class="row testimonial">
									<div class="col-md-4 col-sm-4 col-xs-4 text-center">
										<?php if($image != '' && file_exists(TESTIMONY.$image)) { ?>
											<img src="<?php echo SITEURL.TESTIMONY.$image; ?>" class="img img-responsive">
										<?php } else { ?> 
											<img src="<?php echo SITEURL.'img/user.png'; ?>" class="img img-responsive">
										<?php } ?>
									</div>
									<div class="col-md-8 col-sm-8 col-xs-8">
										<h3><?php echo $name; ?></h3>
										<p><?php echo $descr; ?></p>
									</div>
									<div class="clearfix"></div>
								</div>
								<?php
							}
                            echo '</div>';
						}
						?>
						
						<?php /*					
						<div class="alert alert-info mt30">
						    <div class="col-md-1 col-sm-1 col-xs-2">
								<i class="fa fa-calendar fa-2x"></i>
							</div>
							<div class="col-md-11 col-sm-11 col-xs-10">
								<h4>7-Day money back guarantee</h4>
								<p>100% no problem</p>
							</div>
							<div class="clearfix"></div>
						</div>
						*/ ?>
					</div>
					<div class="clearfix"></div>
				</div>
			</div>
			
		
			<div class="row">
				<div class="footer-detail pull-right">
					<p>Copyright © <?php echo date('Y'); ?> - All Rights Reserved</p>
					<i class="fa fa-envelope"></i> Email : <a href="mailto:<?php echo SUPPORTMAIL; ?>"><?php echo SUPPORTMAIL; ?></a>
				</div>
			</div>
		
	
		</div>
	</div>
	
	<script>
		function check_form(){

            if($("#fname").val()=="" || $("#fname").val().split(" ").join("")==""){
                alert("Please enter your first name.");
                $("#fname").focus();
                return false;
            }

            if($("#lname").val()=="" || $("#lname").val().split(" ").join("")==""){
                alert("Please enter your last name.");
                $("#lname").focus();
                return false;
            }

            if($("#email").val()=="" || $("#email").val().split(" ").join("")==""){
                alert("Please enter your email id.");
                $("#email").focus();
                return false;
            }else{
                if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test($("#email").val())){  
                    
                }else{
                    alert('Please enter your valid email id.');
                    $("#email").focus();
                    return false;
                }
            }

            if($("#phone").val()=="" || $("#phone").val().split(" ").join("")==""){
                alert("Please enter your phone number.");
                $("#phone").focus();
                return false;
            }

            /* if($("#business_name").val()=="" || $("#business_name").val().split(" ").join("")==""){
                alert("Please enter your business name.");
                $("#business_name").focus();
                return false;
            } */

            if($("#cardno").length > 0) {
                if($("#cardno").val()=="" || $("#cardno").val().split(" ").join("")==""){
                    alert("Please enter your card number.");
                    $("#cardno").focus();
                    return false;
                }
            }

            if($("#exp_month").length > 0) {
                if($("#exp_month").val()=="" || $("#exp_month").val().split(" ").join("")==""){
                    alert("Please select expiry month.");
                    $("#exp_month").focus();
                    return false;
                }
            }

            if($("#exp_year").length > 0) {
                if($("#exp_year").val()=="" || $("#exp_year").val().split(" ").join("")==""){
                    alert("Please select expiry year.");
                    $("#exp_year").focus();
                    return false;
                }
            }

            if($("#cvc").length > 0) {
                if($("#cvc").val()=="" || $("#cvc").val().split(" ").join("")==""){
                    alert("Please enter your cvc number.");
                    $("#cvc").focus();
                    return false;
                }
            }

        }

        function isnumber() {
            var key = window.event ? event.keyCode : event.which;
            if (event.keyCode === 8 || event.keyCode === 46) {
                return true;
            } else if ( key < 48 || key > 57 ) {
                return false;
            } else {
                return true;
            }
        }

	</script>

    <script>
        jQuery(document).on('click', ".btn-fb", function() {
            FB.login(function(response) {
                if (response.authResponse) {
                    getUserData();
                }
            }, {scope: 'email,public_profile', return_scopes: true});
        });

        function getUserData() {
            var data = '';
            FB.api('/me', { locale: 'en_US', fields: 'name, email, gender,picture' },
            function(data) {
                var fullname = data.name;
                var name = fullname.split(" ");
                jQuery('#email').val(data.email);
                jQuery('#fname').val(name[0]);
                jQuery('#lname').val(name[1]);
            });
        }
         
        window.fbAsyncInit = function() {
            FB.init({
                appId      : <?php echo Facebook_APP_ID; ?>,
                xfbml      : true,
                version    : 'v2.2'
            });
        };
            
        (function(d, s, id){
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) {return;}
            js = d.createElement(s); js.id = id;
            js.src = "//connect.facebook.net/en_US/sdk.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
    </script>
  </body>
</html>

<?php /* ----------------------------------------------------------------- */ ?>