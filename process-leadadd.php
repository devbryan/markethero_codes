<?php

include("connect.php");

include("include/front_security.php");



$user_id    = $_SESSION[SESS_PRE.'_SESS_USER_ID'];

$fname 		= $db->clean($_POST['fname']);

$lname 		= $db->clean($_POST['lname']);

$email 		= $db->clean($_POST['email']);

$cdata 		= $db->clean($_POST['cdata']);

$tags 		= json_decode(stripslashes($_POST['tags']), true);

$automations= json_decode(stripslashes($_POST['automations']), true);

$product_tag= json_decode(stripslashes($_POST['product_tag']), true);

$fb_tag     = json_decode(stripslashes($_POST['fb_tag']), true);

$tags_array = array();

$automations_array = array();

$fb_tag_array = array();

$id = $db->clean($_POST['id']);



function lead_set_tags($tags_array, $user_id, $lead_id, $isFbTag = false) {

    global $db;

    if( !empty($tags_array) ) {

        foreach($tags_array as $key)

        {

            $dup_where = "tag_name = '".$key."' AND  user_id='".$user_id."' AND isDelete=0 AND isStartTag != 1 AND stream_id = 0";

            $dup_where.= ($isFbTag) ? ' AND isFbTag = 1' : ' AND isFbTag != 1';



            $check_dup = $db->rp_getData( "lead_tags", 'id', $dup_where );

            if( $check_dup )

            {

                $check_dupd = mysql_fetch_array($check_dup);

                $tags_id = $check_dupd['id'];

            }

            else

            {   

                $lead_tags_value = array( $key, $user_id, ($isFbTag) ? 1 : 0 );

                $lead_tags_rows = array( "tag_name", "user_id", "isFbTag" );

                $insertid = $db->rp_insert( "lead_tags", $lead_tags_value, $lead_tags_rows );

                $tags_id = $insertid ;

            }

            $db->rp_insert("lead_tag_conn",

                array($user_id, $lead_id ,$tags_id),

                array("user_id", "lead_id", "tag_id")

            );

        }

    }

}



// check lead limit and update lead limit

$user_total_lead = get_user_total_lead( $db, $user_id );

$leads_limit = $db->rp_getValue("user", "leads_limit", "id=".$user_id);

$charge_exceed_leads = $db->rp_getValue("user", "charge_exceed_leads", "id=".$user_id);



if( $charge_exceed_leads == 1 && $user_total_lead >= $leads_limit ) {

    // curl code to shortcart with campaign details and product details.

    update_lead_limit( $db, $user_id );

}



if($email!="" && !filter_var($email, FILTER_VALIDATE_EMAIL) === false){

	

	$where_dup = " email = '".$email."' AND isDelete=0 AND  user_id='".$_SESSION[SESS_PRE.'_SESS_USER_ID']."' " ;

	if($id != "") {	$where_dup .= " AND id!= '".$id."' " ; }

		

	$check_dup = $db->rp_getData("lead",'id',$where_dup);

	if( $check_dup )

	{

		$_SESSION['MSG'] = "DUPLICATE";

		$db->rp_location(SITEURL."add-lead/".$id);

	}

	

	if( !empty( $tags ) ) {

        foreach ($tags as $key => $value) {

            $tags_array[] = $value['value'];

        }

	}



	if( !empty( $automations ) ) {

        foreach ($automations as $key => $value) {

            $automations_array[] = $value['value'];

        }

    }



    if( !empty( $product_tag ) ) {

        foreach ($product_tag as $key => $value) {

            $product_tag_array[] = $value['value'];

        }

    }



    if( !empty( $fb_tag ) ) {

        foreach ($fb_tag as $key => $value) {

            $first_character = substr($value['value'], 0, 1);

            if($first_character != '%') {

                $fb_tag_array[] = '%'.$value['value'];

            } else {

                $fb_tag_array[] = $value['value'];

            }   

        }

    }

	

	if($id=="")

	{   

        /*Insert On Boadring checklist for Process Start */

        $usercheck = $db->rp_getTotalRecord("onboarding_checklist",'user_id='.$user_id.' ');

        if($usercheck == 0){



            $onboarding_check_row   = array("user_id","lead");

            $onboarding_check_values = array($user_id,'100');

            $db->rp_insert("onboarding_checklist",$onboarding_check_values,$onboarding_check_row);



        }else{

            $rows = array("lead" => '100');                

            $db->rp_update('onboarding_checklist',$rows,"user_id='".$user_id."'");

        }

        /*Insert On Boadring checklist for Process Start */



		$adate 	= date("Y-m-d H:i:s");

		$reg_ip	= $db->rp_get_client_ip();

        $lead_status = 4;

		$rows 	= array(

					 "user_id",

					"fname",

					"lname",

					"email",

					"custom_data",

					"adate",

                    "status",

				);

		$values = array(

					$user_id,

					$fname,

					$lname,

					$email,

					$cdata,

					$adate,

                    $lead_status,

				);

		$uid = $db->rp_insert("lead",$values,$rows);

		

        // insert simple tag

        lead_set_tags($tags_array, $user_id, $uid);

            

        // insert flow tag

        if( !empty($automations_array) ) {

            foreach($automations_array as $key)

            {

                $check_dup = $db->rp_getData("lead_tags", 'id', "tag_name = '".$key."' AND  user_id='".$user_id."' AND isDelete=0 AND isStartTag!=0 AND stream_id !=0");

                if(mysql_num_rows($check_dup) >= 1)

                {

                    $check_dupd = mysql_fetch_array($check_dup);

                    $tags_id = $check_dupd['id'];

                    $db->rp_insert("lead_tag_conn",

                        array($user_id, $uid, $tags_id),

                        array("user_id", "lead_id", "tag_id")

                    );

                }

            }

        }



        // insert product tag

        if( !empty($product_tag_array) ) {

            foreach($product_tag_array as $key)

            {

                $check_dup = $db->rp_getData("product", 'id', "tag = '".$key."' AND  user_id='".$user_id."' AND isDelete=0 ");

                if(mysql_num_rows($check_dup) >= 1)

                {

                    $check_dupd = mysql_fetch_array($check_dup);

                    $product_tag_id = $check_dupd['id'];

                    $db->rp_insert("lead_product_tag_conn",

                        array($user_id, $uid, $product_tag_id),

                        array("user_id", "lead_id", "product_tag_id")

                    );

                }

            }

        }



        // insert Facebook Messenger tag

        if( !empty($fb_tag_array) ) {

            lead_set_tags($fb_tag_array, $user_id, $uid, true);

        }





        // Trigger flow when apply tag

        $leads = array();

        $leads[$id] = array("email" => $email);

        kp_trigger_apply_tag( $leads, $tags, $user_id );



		$_SESSION['MSG'] = "SUCCESS_REG";

		$db->rp_location(SITEURL."lead/");

	}

    else

	{

		$rows = array(

				"fname" => $fname,

				"lname" => $lname,

				"email" => $email,

				"custom_data" => $cdata,

			);

			

		$db->rp_update('lead',$rows,"id='".$id."'");

        

        $qry = "SELECT ln.id, lt.isStartTag, lt.stream_id, lt.isFbTag FROM lead_tag_conn ln 

                JOIN  lead_tags lt  ON  lt.id = ln.tag_id

                WHERE ln.lead_id = '".$id."' AND ln.user_id='". $user_id."' AND lt.isDelete = 0";

        $lead_tagsr = mysql_query($qry);

        $delete_start_arr = array();

        $delete_tags_array = array();

        $delete_stream_arr = array();

        $delete_fb_tag_arr = array();



        if(mysql_num_rows($lead_tagsr) > 0)

        {

            while ($lead_tagsd = mysql_fetch_array($lead_tagsr))

            {

                if( $lead_tagsd['isStartTag'] > 0 ) {

                    $delete_start_arr[] = $lead_tagsd['id'];

                } else if($lead_tagsd['stream_id'] > 0 ) {

                    $delete_stream_arr[] = $lead_tagsd['id'];

                } else if($lead_tagsd['isFbTag'] > 0 ) {

                    $delete_fb_tag_arr[] = $lead_tagsd['id'];

                } else {

                    $delete_tags_array[] = $lead_tagsd['id'];

                }

            }

        }

		

        // insert simple tags

        $delete_tag_conn = implode(',',$delete_tags_array);

      	$db->rp_delete("lead_tag_conn","lead_id = '".$id."' AND user_id='". $user_id."' AND id IN (". $delete_tag_conn .")");

        lead_set_tags($tags_array, $user_id, $id);

        /*

        if( !empty($tags_array) ) {



			foreach($tags_array as $key)

			{

				$check_dup = $db->rp_getData("lead_tags",'id',"tag_name = '".$key."' AND  user_id='".$user_id."' AND isDelete=0 AND isStartTag != 1 AND stream_id = 0");

				if(mysql_num_rows($check_dup) < 1)

				{

					$insertid = $db->rp_insert("lead_tags", 

                        array($key, $user_id),

                        array("tag_name","user_id")

                    );

					$tags_id = $insertid;

				}

				else

				{ 	$check_dupd = mysql_fetch_array($check_dup);

					$tags_id = $check_dupd['id'];

				}

				$db->rp_insert("lead_tag_conn",

                    array($user_id, $id, $tags_id),

                    array("user_id","lead_id","tag_id")

                );

			}	

        }*/



        // insert flow tag

        if( !empty($automations_array) ) {



            //$db->rp_delete("lead_tag_conn","lead_id = '".$id."' AND user_id='".$_SESSION[SESS_PRE.'_SESS_USER_ID']."' AND isStartTag != 0 AND stream_id != 0");

            foreach($automations_array as $key)

            {

                $check_dup = $db->rp_getData("lead_tags",'id',"tag_name = '".$key."' AND  user_id='".$user_id."' AND isDelete=0 AND ( isStartTag!=0 OR stream_id!=0 ) ");

                if(mysql_num_rows($check_dup) >= 1)

                {

                    $check_dupd = mysql_fetch_array($check_dup);

                    $tags_id = $check_dupd['id'];

                    $db->rp_insert("lead_tag_conn",

                        array($user_id, $uid, $tags_id),

                        array("user_id", "lead_id", "tag_id")

                    );

                }

            }

        }



        // remove / add product tag

        $db->rp_delete("lead_product_tag_conn","lead_id = '".$id."' AND user_id='".$_SESSION[SESS_PRE.'_SESS_USER_ID']."' ");

        if( !empty($product_tag_array) ) {	

            foreach($product_tag_array as $key)

            {



                $check_dup = $db->rp_getData("product", 'id', "tag = '".$key."' AND  user_id='".$user_id."' AND isDelete=0 ");

                if(mysql_num_rows($check_dup) >= 1)

                {

                    $check_dupd = mysql_fetch_array($check_dup);

                    $product_tag_id = $check_dupd['id'];



                    $get_product_conn = $db->rp_getData("lead_product_tag_conn",'id'," product_tag_id ='".$product_tag_id."' AND lead_id ='".$id."' ");

					if( $get_product_conn )

					{	

						$row_product_conn = mysql_fetch_array($get_product_conn);

						$rows = array("product_tag_id" => $product_tag_id);

							

						$db->rp_update('lead_product_tag_conn',$rows,"id='".$row_product_conn["id"]."'");





					}else{



	                    $db->rp_insert("lead_product_tag_conn",

	                        array($user_id, $id, $product_tag_id),

	                        array("user_id", "lead_id", "product_tag_id")

	                    );

	                }

                }

            }

        }





        // delete and add facebook messenger tag

        $delete_fb_tag_conn = implode(',',$delete_fb_tag_arr);

        $db->rp_delete("lead_tag_conn","lead_id = '".$id."' AND user_id='". $user_id."' AND id IN (". $delete_fb_tag_conn .")");

        if( !empty($fb_tag_array) ) {

            lead_set_tags($fb_tag_array, $user_id, $id, true);

        }



        // Trigger flow when apply tag

        $leads = array();

        $leads[$id] = array("email" => $email);

        kp_trigger_apply_tag( $leads, $tags, $user_id );



		$_SESSION['MSG'] = "SUCCESS_TAG_UPDATE";

		$db->rp_location(SITEURL."lead/");

	}

		

}else{

	$_SESSION['MSG'] = "WRONGDATA";

	$db->rp_location(SITEURL."add-lead/");

}

?>