<?php
   include("connect.php");
   include("include/front_security.php");
   $page = "Integrations";
   $uid = $_SESSION[SESS_PRE.'_SESS_USER_ID'];
   
   if( isset($_POST['add_gateway']) ) {
       $integration_mode = isset($_POST['integration_mode']) ? $_POST['integration_mode'] : 'add';
       $integration_id  = isset($_POST['integration_id']) ? $_POST['integration_id'] : '0';
       $p_gateway = isset($_POST['p_gateway']) ? $_POST['p_gateway'] : '';
       $account_name = isset($_POST['account_name']) ? $_POST['account_name'] : '';
       $base_currency = isset($_POST['base_currency']) ? $_POST['base_currency'] : '';
       $sp_api_type = isset($_POST['sp_api_type']) ? $_POST['sp_api_type'] : '';
       $sp_merchant_id = isset($_POST['sp_merchant_id']) ? $_POST['sp_merchant_id'] : '';
       $sp_hashkey = isset($_POST['sp_hashkey']) ? $_POST['sp_hashkey'] : '';
       $sp_hashiv = isset($_POST['sp_hashiv']) ? $_POST['sp_hashiv'] : '';
       /* $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : ''; */
       $isPrimary = '1';
   
       if( $p_gateway != '' && $account_name != '' && $base_currency != '' ) {  
   
           if($integration_mode == 'edit' && $integration_id > 0) {
               $rows = array(
                           "uid" => $uid,
                           "p_gateway" => $p_gateway,
                           "account_name" => $account_name,
                           "base_currency" => $base_currency,
                           "sp_api_type" => $sp_api_type,
                           "sp_merchant_id" => $sp_merchant_id,
                           "sp_hashkey" => $sp_hashkey,
                           "sp_hashiv" => $sp_hashiv,
                           /* "redirect_to" => $redirect_to, */
                       );
               $where = " id = ".$integration_id;
               $db->rp_update('integrations', $rows, $where);
           } else {
               $primary_rows = array("isPrimary" => '0');
               $primary_where = " uid=".$uid." and sp_api_type='".$sp_api_type."'";
               $db->rp_update('integrations', $primary_rows, $primary_where);
   
               $rows   = array(
                           "uid",
                           "p_gateway",
                           "account_name",
                           "base_currency",
                           "sp_api_type",
                           "sp_merchant_id",
                           "sp_hashkey",
                           "sp_hashiv",
                           /* "redirect_to", */
                           "isPrimary",
                       );
               $values = array(
                       $uid,
                       $p_gateway,
                       $account_name,
                       $base_currency,
                       $sp_api_type,
                       $sp_merchant_id,
                       $sp_hashkey,
                       $sp_hashiv,
                       /* $redirect_to, */
                       $isPrimary,
                   );
               $db->rp_insert('integrations',$values,$rows);
           }
       } 
   
       $db->rp_location(SITEURL."integrations/");
   }
   
   $ctable_r = $db->rp_getData("integrations", "*", " isDelete=0 AND uid=".$uid, "id DESC ");
   
    $service = "";
    $ser_account_name = "";
    $api_url = "";
    $api_key = "";
    $w_f_notification = 0;
    $email_service_tab = 0;
    $email_ser = $db->rp_getData("user_email_int_ervice", "*", " isDelete=0 AND user_id=".$uid);
    if( $email_ser && mysqli_num_rows($email_ser) > 0 ) 
    {   
        $email_s_d = mysqli_fetch_array($email_ser) ; 
        $email_service_tab = 1;
        $service = isset($email_s_d['service']) ? $email_s_d['service'] : '';
        $ser_account_name = isset($email_s_d['name']) ? $email_s_d['name'] : '';
        $api_url = isset($email_s_d['api_url']) ? $email_s_d['api_url'] : '';
        $api_key = isset($email_s_d['api_key']) ? $email_s_d['api_key'] : '';
        $w_f_notification = isset($email_s_d['w_f_notification']) ? $email_s_d['w_f_notification'] : '';
    }

     $mem_ship_ser = $db->rp_getData("user_membership_ervice", "*", " isDelete=0 AND user_id=".$uid);
     if( $mem_ship_ser && mysqli_num_rows($mem_ship_ser) > 0 ) 
    {   
        $mem_ship_d = mysqli_fetch_array($mem_ship_ser) ; 
       //print_r($mem_ship_d );
        $ms_service = isset($mem_ship_d['service']) ? $mem_ship_d['service'] : '';
        
        if($ms_service== 'Whishlist')
        {
          $ms_wish_url = isset($mem_ship_d['api_url']) ? $mem_ship_d['api_url'] : '';
          $ms_wish_apikey = isset($mem_ship_d['api_key']) ? $mem_ship_d['api_key'] : '';
        }
        if($ms_service== 'OptimizeMember')
        {
          $ms_om_url = isset($mem_ship_d['api_url']) ? $mem_ship_d['api_url'] : '';
          $ms_om_apikey = isset($mem_ship_d['api_key']) ? $mem_ship_d['api_key'] : '';
        }
        if($ms_service== 'WooCommerce')
        {
          $ms_wc_url = isset($mem_ship_d['api_url']) ? $mem_ship_d['api_url'] : '';
          $ms_wc_apikey = isset($mem_ship_d['api_key']) ? $mem_ship_d['api_key'] : '';
        }

 
        $api_url = isset($email_s_d['api_url']) ? $email_s_d['api_url'] : '';
        $api_key = isset($email_s_d['api_key']) ? $email_s_d['api_key'] : '';
        
    }

   ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <title><?php echo $page." - ".SITETITLE; ?></title>
      <?php include("include_css.php"); ?>
      
   </head>
   <body class="app header-fixed sidebar-fixed aside-menu-fixed aside-menu-hidden">
      <?php include("include_header.php"); ?>
      <div class="app-body">
         <?php include("include_left.php"); ?>
         <main class="main">
            <ol class="breadcrumb">
               <li class="breadcrumb-item"><a href="<?php echo SITEURL; ?>">Dashboard</a></li>
               <li class="breadcrumb-item active"><?php echo $page; ?></li>
               <button type="" class="btn btn-success pull-right" id="open_gateway_mdl"><i class="fa fa-plus"></i> &nbsp; Add Gateway </button>
            </ol>
            <ul class="tabs">
               <li class="" style="pointer-events: none;">&nbsp;</li>
               <li class="tab-link current" data-tab="payment">Payment</li>
               <li class="tab-link" data-tab="invoice">Invoice</li>
               <li class="tab-link " data-tab="email">Email</li>
               <li class="tab-link " data-tab="membership">Membership</li>
            </ul>
            <div class="container">
               <div id="payment" class="tab-content current">
                  <div class="row">
                     <div class="col-md-12 col-xs-12">
                        <img src="<?php echo SITEURL."img/gateway/spgateway.png"; ?>" height="45" />
                        <hr/>
                     </div>
                     <div class="col-md-12 col-xs-12 integration_container" >
                        <?php
                           $no_integration = "No any integration founds.";
                           if( $ctable_r && mysqli_num_rows($ctable_r) > 0 ) {
                               while($integrations = mysqli_fetch_array($ctable_r)) {
                                   $integration_id = $db->clean($integrations['id']);
                                   $p_gateway = $db->clean($integrations['p_gateway']);
                                   $account_name = $db->clean($integrations['account_name']);
                                   $base_currency = $db->clean($integrations['base_currency']);
                                   $sp_api_type = $db->clean($integrations['sp_api_type']);
                                   $sp_merchant_id = $db->clean($integrations['sp_merchant_id']);
                                   $sp_hashkey = $db->clean($integrations['sp_hashkey']);
                                   $sp_hashiv = $db->clean($integrations['sp_hashiv']);
                                   /* $redirect_to = $db->clean($integrations['redirect_to']); */
                                   $isPrimary = $db->clean($integrations['isPrimary']);
                           
                                   $edit_data = ' data-integration_id="'.$integration_id.'"';
                                   $edit_data.= ' data-p_gateway="'.$p_gateway.'"';
                                   $edit_data.= ' data-account_name="'.$account_name.'"';
                                   $edit_data.= ' data-base_currency="'.$base_currency.'"';
                                   $edit_data.= ' data-sp_api_type="'.$sp_api_type.'"';
                                   $edit_data.= ' data-sp_merchant_id="'.$sp_merchant_id.'"';
                                   $edit_data.= ' data-sp_hashkey="'.$sp_hashkey.'"';
                                   $edit_data.= ' data-sp_hashiv="'.$sp_hashiv.'"';
                                   /* $edit_data.= ' data-redirect_to="'.$redirect_to.'"'; */
                           
                                   ?>
                        <div class="row integration" id="integration_<?php echo $integration_id;?>">
                           <div class="col-md-6 col-xs-6">
                              <h4>
                                 <?php echo $account_name; ?>
                                 <span class="is_primary_<?php echo $sp_api_type; ?> primary_<?php echo $integration_id;?>">
                                 <?php if($isPrimary) { ?>
                                 <small class="int_lbl">Current</small>
                                 <?php } ?>
                                 </span>
                              </h4>
                           </div>
                           <div class="col-md-3 col-xs-3">
                              <h6>API Type : <?php echo $sp_api_type; ?></h6>
                           </div>
                           <div class="col-md-3 col-xs-3">
                              <div class="pull-right">
                                 <a href="javascript:;" class="btn btn-primary btn-xs add-tooltip default_gateway" <?php echo $edit_data; ?>><i class="fa fa-star"></i></a>
                                 <a href="javascript:;" class="btn btn-primary btn-xs add-tooltip edit_gateway" <?php echo $edit_data; ?> ><i class="fa fa-edit"></i></a>
                                 <a href="javascript:;" class="btn btn-danger btn-xs add-tooltip del_gateway_btn"<?php echo $edit_data; ?> ><i class="fa fa-remove"></i></a> 
                              </div>
                           </div>
                        </div>
                        <?php
                           }
                           } else {
                           echo "<div class='row integration'>".$no_integration."</div>";
                           }
                           ?>
                     </div>
                  </div>
               </div>
               <div id="invoice" class="tab-content">
                  Invoice
               </div>
               <div id="email" class="tab-content " style="padding: 15px 15px 30px;">
                  <div id="div2" <?php echo ($email_service_tab==1)?"style='display:none'":""; ?> >
                     <div class="no-email-service">
                        <i class="fa fa-envelope"></i>
                        <h2>No email service connected</h2>
                        <p>Connect your email auto-responders to PayKickstart so you can automatically<br> add customers to your email list upon purchase.</p>
                        <a class="btn btn-success" href="javascript:;" id="email-btn"> <i class="fa fa-plus"></i> Add an Email Intigration</a>
                     </div>
                  </div>
                  <div id="div1" <?php echo ($email_service_tab==0)?"style='display:none'":""; ?> >
                     <div class="no-email-service">
                        <div class="row">
                           <div class="col-md-8 offset-md-2">
                              <form action="" id="mail_service" method="post">
                                 <h2>Select An Email Intigration Service</h2>
                                 <hr>
                                 <div class="white-box">
                                    <div class="form-group">
                                       <label>Email Service Available <i class="fa fa-book text-blue"></i></label>
                                       <select class="form-control" name="service" id="service" required >
                                          <!-- <option <?php echo ($service == 'ActiveCampaign')?"SELECTED":""; ?> value="ActiveCampaign">ActiveCampaign</option> -->
                                          <option <?php echo ($service == 'projects')?"SELECTED":""; ?> value="projects">projects</option>
                                       </select>
                                    </div>
                                    <div class="form-group">
                                       <label>Account Name (for Display Purposes)</label>
                                       <input type="text" class="form-control" name="ser_account_name" value="<?php echo $ser_account_name; ?>" required >
                                    </div>
                                    <div class="form-group" id="api_url_div" >
                                       <label>API Url</label>
                                       <input type="text" class="form-control" name="api_url" value="<?php echo $api_url; ?>" required >
                                    </div>
                                    <div class="form-group" id="api_key_div" >
                                       <label>API Key</label>
                                       <input type="text" class="form-control" name="api_key" value="<?php echo $api_key; ?>" required >
                                    </div>
                                 </div>
                                 <hr>
                                 <div class="row">
                                    <div class="col-md-10">
                                       <div class="white-box web-hook">
                                          <div class="row Aligner">
                                             <div class="col-md-7">
                                                <h3>Webhook failure notifications</h3>
                                                <p>Have the ability to enable or disable email notification for IPN, Webinar, Email Service or any other webhook failure.</p>
                                             </div>
                                             <div class="col-md-5 text-center radio-toolbar"> 
                                                <input type="radio" id="enable" name="w_f_notification" value="0" <?php echo ($w_f_notification == 0)?"checked":""; ?>>
                                                <label for="enable">Enable</label>
                                                <input type="radio" id="disable" name="w_f_notification" value="1" <?php echo ($w_f_notification == 1)?"checked":""; ?>>
                                                <label for="disable">Disable</label>
                                             </div>
                                          </div>
                                       </div>
                                    </div>
                                 </div>
                                 <button type="submit" class="btn btn-primary mt-3 btn-block submit-btn">Submit</button>
                                 <div class="erro_suc_msg" style="display: none;text-align: center;"></div>
                              </form>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
               <div id="membership" class="tab-content " style="padding: 15px 15px 30px;">
                  <div  >
                     <div class="no-email-service">
                        <div class="row">
                           <div class="col-md-8 offset-md-2">
                              <form action="" id="membership_service" method="post">
                                 <h2>SELECT AN MEMBERSHIP INTEGRATION SERVICE</h2>
                                 <hr>
                                 <div class="white-box mb-4">
                                    <div class="form-group custom-my-select mb-0">
                                       <label class="form-control-label" for="create_user">membership service</label>
                                       <select  class="form-control" smultiple data-rel="chosen" id="ms_service" name="ms_service" >
                                          <option>Select</option>
                                          
                                          <option <?php echo ($ms_service == 'Whishlist')?"SELECTED":""; ?> value="Whishlist">Whishlist</option>
                                          <option <?php echo ($ms_service == 'OptimizeMember')?"SELECTED":""; ?> value="OptimizeMember">OptimizeMember</option>
                                          <option <?php echo ($ms_service == 'WooCommerce')?"SELECTED":""; ?> value="WooCommerce">WooCommerce</option>
                                       </select>
                                    </div>
                                 </div>
                                  <div id="Whishlist">
                                    <h4>Wishlist</h4>
                                    <div class="custom-card mb-4">
                                          <div class="form-group custom-my-select mb-2">
                                              <div class="row">
                                                <div class="col-md-6">
                                                  <label class="form-control-label">URL</label>
                                                  <input type="text" class="form-control" name="ms_wish_url" value="<?php echo $ms_wish_url ?>" placeholder="">
                                                </div>
                                                <div class="col-md-6 pl-0 pr-30">
                                                  <label class="form-control-label">API Key</label>
                                                  <input type="text" class="form-control" name="ms_wish_apikey" value="<?php echo $ms_wish_apikey ?>" placeholder="">
                                                </div>
                                                <i class="fa fa-times delete-box"></i>
                                              </div>
                                          </div>
                                          <div class="add-item-section">
                                            <i class="fa fa-plus-square-o"></i>
                                            <span>Add Item</span>
                                          </div>
                                    </div>
                                  </div>
                                  <div id="OptimizeMember">
                                    <h4>OptimizeMember</h4>
                                    <div class="custom-card">
                                        <div class="form-group custom-my-select mb-2">
                                            <div class="row">
                                              <div class="col-md-6">
                                                <label class="form-control-label">URL</label>
                                                <input type="text" class="form-control" name="ms_om_url" value="<?php echo $ms_om_url ?>"  placeholder="">
                                              </div>
                                              <div class="col-md-6 pl-0 pr-30">
                                                <label class="form-control-label">API Key</label>
                                                <input type="text" class="form-control" name="ms_om_apikey" value="<?php echo $ms_om_apikey ?>" placeholder="">
                                              </div>
                                              <i class="fa fa-times delete-box"></i>
                                            </div>
                                        </div>
                                        <div class="add-item-section">
                                          <i class="fa fa-plus-square-o"></i>
                                          <span>Add Item</span>
                                        </div>
                                    </div>
                                  </div>
                                  <div id="WooCommerce">
                                  <h4>WooCommerce</h4>
                                  <div class="custom-card">
                                        <div class="form-group custom-my-select mb-2">
                                            <div class="row">
                                              <div class="col-md-6">
                                                <label class="form-control-label">URL</label>
                                                <input type="text" class="form-control" name="ms_wc_url" value="<?php echo $ms_wc_url ?>"  placeholder="">
                                              </div>
                                              <div class="col-md-6 pl-0 pr-30">
                                                <label class="form-control-label">API Key</label>
                                                <input type="text" class="form-control" name="ms_wc_apikey" value="<?php echo $ms_wc_apikey ?>" placeholder="">
                                              </div>
                                              <i class="fa fa-times delete-box"></i>
                                            </div>
                                        </div>
                                        <div class="add-item-section">
                                          <i class="fa fa-plus-square-o"></i>
                                          <span>Add Item</span>
                                        </div>
                                  </div>
                                  </div>
                                  <button type="submit" class="btn btn-primary mt-3 btn-block submit-btn">Submit</button>
                                 <div class="erro_suc_msg" style="display: none;text-align: center;"></div>
                              </form>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
         </main>
      </div>
      <?php include("include_js.php"); ?>
      <div id="myModal" class="modal fade" role="dialog">
         <div class="modal-dialog modal-lg">
            <div class="modal-content">
               <div class="modal-header">
                  <h4 class="modal-title">
                     <center>Select A Payment Integration Service</center>
                  </h4>
                  <button type="button" class="close" data-dismiss="modal">&times;</button>
               </div>
               <form method="post" name="add_integrataion" id="add_integration">
                  <div class="modal-body">
                     <input type="hidden" name="integration_mode" id="integration_mode" value="add" />
                     <input type="hidden" name="integration_id" id="integration_id" value="0" />
                     <div class="form-group">
                        <div class="col-sx-12">
                           <label for="">Payment Services Available</label>
                           <select class="form-control change-gateway" name="p_gateway" id="p_gateway">
                              <option value="">Select Gateway</option>
                              <option value="spgateway">Spgateway</option>
                           </select>
                        </div>
                     </div>
                     <div class="form-group">
                        <div class="col-sx-12">
                           <label for="">Account Name <small>(for display purposes)</small></label>
                           <input class="form-control class" type="text" name="account_name" id="account_name">
                        </div>
                     </div>
                     <div class="form-group">
                        <div class="col-sx-12">
                           <label for="">Base Currency</label>
                           <select class="form-control base-currency" name="base_currency" id="base_currency">
                              <option value="">Select Base Currency</option>
                              <option value="TWD">Taiwanese dollar (TWD)</option>
                           </select>
                        </div>
                     </div>
                     <!-- <Fields for spgateway> -->
                     <div class="row spgateway-sec">
                        <div class="col-md-6">
                           <div class="form-group Spgateway">
                              <label for="">SPgateway API</label>
                              <select class="form-control change-gateway" name="sp_api_type" id="sp_api_type">
                                 <option value="">Select API</option>
                                 <option value="standard">Standard API</option>
                                 <option value="cashflow">Cashflow API</option>
                              </select>
                           </div>
                        </div>
                        <div class="col-md-6">
                           <div class="form-group Spgateway">
                              <label for="">SPgateway Merchant ID</label>
                              <input class="form-control class" type="text" name="sp_merchant_id" id="sp_merchant_id">
                           </div>
                        </div>
                        <div class="col-md-6">
                           <div class="form-group Spgateway">
                              <label for="">Spgateway HashKey</label>
                              <input class="form-control class" type="text" name="sp_hashkey" id="sp_hashkey">
                           </div>
                        </div>
                        <div class="col-md-6">
                           <div class="form-group Spgateway">
                              <label for="">Spgateway HashIV</label>
                              <input class="form-control class" type="text" name="sp_hashiv" id="sp_hashiv">
                           </div>
                        </div>
                        <?php /* <div class="col-md-12">
                           <div class="form-group Spgateway">
                              <label for="">Redirect To <small>( After Complete Payment )</small></label>
                              <input class="form-control class" type="text" name="redirect_to" id="redirect_to">
                           </div>
                           </div> */ ?>
                     </div>
                     <!-- </Fields for spgateway> -->
                  </div>
                  <div class="modal-footer">
                     <input type="submit" name="add_gateway" id="add_gateway" class="btn btn-success btn-block connect_btn" value="Connect" />
                  </div>
               </form>
            </div>
         </div>
      </div>
      <script>
         $(document).ready(function(){
              $("#Whishlist").hide();
              $("#OptimizeMember").hide();
              $("#WooCommerce").hide();
              $("#"+$("#ms_service").val() ).show();
            $("#ms_service").change(function(){
              var val = $(this).val();
              $("#Whishlist").hide();
              $("#OptimizeMember").hide();
              $("#WooCommerce").hide();
              $("#"+val).show();
            });

             $('ul.tabs li').click(function(){
                 var tab_id = $(this).attr('data-tab');
         
                 $('ul.tabs li').removeClass('current');
                 $('.tab-content').removeClass('current');
         
                 $(this).addClass('current');
                 $("#"+tab_id).addClass('current');
             })
            
            jQuery(document).on("change", "#service", function() {
              changeService()
            });
            changeService()
         });
         function changeService()
         {
         
            //Darshit, 11:03 AM  when user select projects then show API key , but select ActiveCampaign then show API url & API key
            var service = $("#service").val();
            //alert(service);
            if(service == "projects")
            { 
              $("#api_key_div").show();
              $("#api_url_div").hide();
            }
            if(service == "ActiveCampaign")
            {
              $("#api_key_div").show();
              $("#api_url_div").show();
            }
         
         }
         jQuery(document).on("change", "#p_gateway", function() {
             if(jQuery(this).val() == 'spgateway') {
                 jQuery(".spgateway-sec").attr("style", "display: inline-flex");
             } else {
                 jQuery(".spgateway-sec").hide();
             }
         })
         
         jQuery(document).on("click", "#open_gateway_mdl", function() {
             $('#integration_mode').val('add');
             $('#integration_id').val('');
             $('#p_gateway').val('');
             $('#account_name').val('');
             $('#base_currency').val('');
             $('#sp_api_type').val('');
             $('#sp_merchant_id').val('');
             $('#sp_hashkey').val('');
             $('#sp_hashiv').val('');
             /* $('#redirect_to').val(''); */
             $('#myModal').modal( 'show' );
         });
         
         jQuery(document).on("click", ".edit_gateway", function() {
             $('#integration_mode').val('edit');
             $('#integration_id').val($(this).attr('data-integration_id'));
              if($(this).attr('data-p_gateway') == 'spgateway') {
                 jQuery(".spgateway-sec").attr("style", "display: inline-flex");
             }
             $('#p_gateway').val($(this).attr('data-p_gateway'));
             $('#account_name').val($(this).attr('data-account_name'));
             $('#base_currency').val($(this).attr('data-base_currency'));
             $('#sp_api_type').val($(this).attr('data-sp_api_type'));
             $('#sp_merchant_id').val($(this).attr('data-sp_merchant_id'));
             $('#sp_hashkey').val($(this).attr('data-sp_hashkey'));
             $('#sp_hashiv').val($(this).attr('data-sp_hashiv'));
             /*  $('#redirect_to').val($(this).attr('data-redirect_to')); */
             $('#myModal').modal( 'show' );
         });
         
         jQuery(document).on("click", ".del_gateway_btn", function() {
             var integration_id = $(this).attr('data-integration_id');
             var api_type =  $(this).attr('data-sp_api_type');
             swal({
                   title: 'Warning!',
                   text: "Are you sure you want to delete the gateway?",
                   type: 'warning',
                   showCancelButton: true,
                   confirmButtonColor: '#3085d6',
                   cancelButtonColor: '#d33',
                   confirmButtonText: 'Yes, delete it!'
                 }).then((result) => {
                     if(result.value) {
                         $.ajax({
                             url : "<?php echo SITEURL; ?>ajax_remove_integration.php",
                             data: {"integration_id":integration_id, "api_type":api_type, "mode":"delete"},
                             success: function(result) {
                                 
                                 $("#integration_"+integration_id).remove();
                                 if( jQuery(".integration").length <= 0 ) {
                                     jQuery(".integration_container").html("<div class='row integration'><?php echo $no_integration; ?></div>");
                                 }
         
                                 if( result > 0 ) {
                                     jQuery(".primary_"+result).html('<small class="int_lbl"> Current </small>');
                                 }
                             }
                         });
                     }
                 });
         });
         
         jQuery(document).on("click", ".default_gateway", function() {
             var integration_id = $(this).attr('data-integration_id');
             var api_type =  $(this).attr('data-sp_api_type');
             $.ajax({
                 url : "<?php echo SITEURL; ?>ajax_remove_integration.php",
                 data : {"integration_id":integration_id, "api_type":api_type, "mode":"make_primary"},
                 success: function(result) {
                     jQuery(".is_primary_"+api_type).html('');
                     jQuery(".primary_"+integration_id).html('<small class="int_lbl"> Current </small>');
                 }
             })
         });
         
         
         jQuery(document).submit("#mail_service", function(event) {
             event.preventDefault();
             $(".mmloader").show();
             $.ajax({
                 url : "<?php echo SITEURL; ?>ajax_save_email_intigration.php",
                 method:"post",
                 data : $("#mail_service").serialize(),
                 success: function(result) {
                    $("#mail_service .erro_suc_msg").show();
                    if(result)
                        {  $("#mail_service .erro_suc_msg").html("<lable style='color:green'>Details Saved Successfully.<lable>");}
                    else
                        { $("#mail_service .erro_suc_msg").html("<lable style='color:red'>Invalid Request.<lable>");}
                    $(".mmloader").hide();
                    setTimeout(function () {
                         $("#mail_service .erro_suc_msg").html("");$("#mail_service .erro_suc_msg").hide();
                    }, 2000);
                 }
             })
         });

         jQuery(document).submit("#membership_service", function(event) {
             event.preventDefault();
             $(".mmloader").show();
             $.ajax({
                 url : "<?php echo SITEURL; ?>ajax_save_membership_service.php",
                 method:"post",
                 data : $("#membership_service").serialize(),
                 success: function(result) {
                    $("#membership_service .erro_suc_msg").show();
                    if(result)
                        {  $("#membership_service .erro_suc_msg").html("<lable style='color:green'>Details Saved Successfully.<lable>");}
                    else
                        { $("#membership_service .erro_suc_msg").html("<lable style='color:red'>Invalid Request.<lable>");}
                    $(".mmloader").hide();
                    setTimeout(function () {
                         $("#membership_service .erro_suc_msg").html("");$("#membership_service .erro_suc_msg").hide();
                    }, 2000);
                 }
             })
         });
         
      </script>
      <script>
         $(document).ready(function(){
             //$("#div1").hide();
             $("#email-btn").on("click",function(){
                 $("#div1").show();
                 $("#div2").hide();
             });
         });
      </script>
   </body>
</html>