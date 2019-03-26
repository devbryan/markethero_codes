<?php 

include("connect.php");
include("include/front_security.php");
include("include/notification.class.php");

// echo "<pre>";
// print_r($_REQUEST);
// echo "<pre><br/><br/>";

$user_id = $_SESSION[SESS_PRE.'_SESS_USER_ID'];

if( isset( $_REQUEST['parent_broadcast_id'] ) && !empty( $_REQUEST['parent_broadcast_id'] ) ) {
	$lead_ids = array(); // store leads which is not open/read mail or fb message.
	
	$parent_bid = $_REQUEST['parent_broadcast_id'];

	$sub_parent_id = $db->rp_getValue("broadcast", "id", " parent_bid=".$parent_bid." and status=1 ORDER BY id DESC LIMIT 1");

	$temp_parent_bid = !empty($sub_parent_id) ? $sub_parent_id : $parent_bid; 


	$broadcast_r = $db->rp_getData( "broadcast", "*", "id=".$temp_parent_bid, "id DESC ");
	if( $broadcast_r ) {

		// get broadcast message ids
		$msg_id_arr = array();
		$broadcast_msg_ids = "SELECT message_id FROM `broadcast_detail` WHERE broadcast_id = ".$temp_parent_bid." GROUP BY message_id";
		$broadcast_msg_ids = mysql_query( $broadcast_msg_ids );
		if( mysql_num_rows($broadcast_msg_ids) > 0 ) {
		    while( $msg = mysql_fetch_array($broadcast_msg_ids) ) {
		        $msg_id_arr[] = $msg['message_id'];
		    }
		}
		$msg_ids = "'" . implode( "','", $msg_id_arr ) . "'";

	


		// checking broadcast is email or fbm
		$broadcast_d = mysql_fetch_array($broadcast_r);
		if( !empty($broadcast_d['mail_subject']) && !empty($broadcast_d['from_name']) ) {
			// process for email
			$unread_users_q = "SELECT l.id as id, l.fb_lead_id, l.email as email, GROUP_CONCAT(event) as events FROM `lead` AS l JOIN `sendgrid_events` AS s on s.email = l.email WHERE msg_id IN ( " . $msg_ids . " ) AND l.isDelete = 0 GROUP BY email HAVING events NOT LIKE '%open%' ";
			$unread_users_e = mysql_query( $unread_users_q );
			if( mysql_num_rows($unread_users_e) > 0 ) {
				while( $lead = mysql_fetch_array($unread_users_e) ){
					$lead_ids[$lead['id']] = array(
						'id' => $lead['id'],
						'fb_lead_id' => $lead['fb_lead_id'],
						'email' => $lead['email'],
						'name' => ''
					);
				}
			}

		} else {
			// process for FBM
			$unread_users_q = "SELECT l.id as id, l.fb_lead_id, l.email as email, l.fb_fname, l.fb_lname FROM `lead` AS l JOIN `fb_events` AS f on f.sender = l.fb_lead_id WHERE mid IN ( " . $msg_ids . " ) AND event < 3 AND l.isDelete = 0";
			$unread_users_e = mysql_query( $unread_users_q );
			if( mysql_num_rows($unread_users_e) > 0 ) {
				while( $lead = mysql_fetch_array($unread_users_e) ){
					$lead_ids[$lead['id']] = array(
						'id' => $lead['id'],
						'fb_lead_id' => $lead['fb_lead_id'],
						'email' => $lead['email'],
						'fname' => $lead['fb_fname'],
						'lname' => $lead['fb_lname'],
						'name' => ''
					);
				}
			}
		}
	}

	$confirmation = $_REQUEST['confirmation'];
	if($confirmation){
		$hours = $_REQUEST['hours'];
		$minutes = $_REQUEST['minutes'];
	}else{
		$hours = 0;
		$minutes = 0;
	}
	
	$broadcast_type = isset( $_REQUEST['broadcast_type'] ) && !empty( $_REQUEST['broadcast_type'] ) ? $_REQUEST['broadcast_type'] : '';
	if( !empty($lead_ids) && $broadcast_type == 'email'  ) {

		$broadcast_name = ( isset($_REQUEST['broadcast_name']) && !empty($_REQUEST['broadcast_name']) ) ? $_REQUEST['broadcast_name'] : '';
	    $mail_subject = ( isset($_REQUEST['mail_subject']) && !empty($_REQUEST['mail_subject']) ) ? $_REQUEST['mail_subject'] : '';
	    $from_email = ( isset($_REQUEST['from_email']) && !empty($_REQUEST['from_email']) ) ? $_REQUEST['from_email'] : '';
	    $from_name = ( isset($_REQUEST['from_name']) && !empty($_REQUEST['from_name']) ) ? $_REQUEST['from_name'] : '';
	    $selected_template = ( isset($_REQUEST['selected_template']) && !empty($_REQUEST['selected_template']) ) ? $_REQUEST['selected_template'] : '';

	    $adate  = date("Y-m-d H:i:s");
	    $rows   = array(
	    			"parent_bid",
                    "user_id",
                    "name",
                    "mail_subject",
                    "from_name",
                    "from_email",
                    "tags",
                    "template_id",
                    "adate",
                    "updateAt",
                    "status",
                    "message_type",
                    "hour",
                    "minute",
                );
        $values = array(
        			$parent_bid,
                    $user_id,
                    $broadcast_name,
                    $mail_subject,
                    $from_name,
                    $from_email,
                    '',
                    $selected_template,
                    $adate,
                    $adate,
                    "0",
                    "email",
                    $hours,
                    $minutes,
                );
       $broadcast_id = $db->rp_insert("broadcast",$values,$rows);

		if(!$confirmation || ($hours==0 && $minutes==0)){
			$e_template_r = $db->rp_getData("bal_email_builder",'*'," id='".$selected_template ."' AND UserId='".$user_id."' ");
	        
	        if(mysql_num_rows($e_template_r) > 0)
	        {
	            
	            $e_template_d = mysql_fetch_array($e_template_r);
	            $html_file = $e_template_d['html_file'];
	            $zip_file = $e_template_d['zip_file'];
	            $content = $e_template_d['content'];
	         
	            $body = '';
	            if(file_exists(EXPORTS_DIRECTORY.$html_file) && $html_file!="")
	            {
	                $body = file_get_contents( EXPORTS_DIRECTORY.$html_file);
	            } else {
	                $body = htmlspecialchars_decode ( $content );
	            }
	            
	            if($body != '') {
	                
	                $subject = $mail_subject;
	                
	                $nt = new Notification();
	                $response= $nt->mm_sendMultipleMail($lead_ids, $subject, $body, $from_email, $from_name, $db, $user_id, $broadcast_id); 

	                $rows   = array( "broadcast_id"  => $broadcast_id, );
	                $db->rp_update('broadcast_detail',$rows,"broadcast_id='".$response."'");

	                $rows   = array( "status" => "1", "message_id" => count($emails) );
	                $db->rp_update('broadcast',$rows,"  id='".$response."'");
	            }
	        }  
	    }
         $_SESSION['MSG'] = 'EMAIL_MESSAGE_SEND';

	} else if( !empty($lead_ids) && $broadcast_type == 'fbm' ) {

		$broadcast_name = ( isset($_REQUEST['fb_broadcast_name']) && !empty($_REQUEST['fb_broadcast_name']) ) ? $_REQUEST['fb_broadcast_name'] : '';
		$fb_page = ( isset($_REQUEST['fb_page']) && !empty($_REQUEST['fb_page']) ) ? $_REQUEST['fb_page'] : '';
		$message = ( isset($_REQUEST['fbm_message']) && !empty($_REQUEST['fbm_message']) ) ? $_REQUEST['fbm_message'] : '';
		$msg_link = ( isset($_REQUEST['fbm_link']) && !empty($_REQUEST['fbm_link']) ) ? $_REQUEST['fbm_link'] : '';
		$msg_link_text = ( isset($_REQUEST['fbm_link_text']) && !empty($_REQUEST['fbm_link_text']) ) ? $_REQUEST['fbm_link_text'] : '';
		$adate  = date("Y-m-d H:i:s");

		$user_page_id = $db->rp_getValue("fb_pages","fb_page_id"," fb_page_id=".$fb_page." AND is_connected=1 AND user_id=".$user_id); //is_primary=1
		$user_page_access_token = $db->rp_getValue("fb_pages", "fb_page_access_token", " fb_page_id=".$fb_page." AND is_connected=1 AND user_id=".$user_id); // is_primary=1

		/*** insert fb message in fb_template table start ***/
		$fb_templalte_rows   = array(
		            "user_id",
		            "msg",
		            "button_txt",
		            "button_link",
		            "adate",
		        );
		$fb_templalte_values = array(
		            $user_id,
		            $message,
		            $msg_link_text,
		            $msg_link,
		            $adate,
		        );
		$fb_template_id = $db->rp_insert( "fb_template", $fb_templalte_values, $fb_templalte_rows );
		/*** insert fb message in fb_template table end ***/

		/****** insert data into broadcast table start ***************/
		$rows   = array(
					"parent_bid",
		            "user_id",
		            "name",
		            "mail_subject",
		            "from_name",
		            "from_email",
		            "tags",
		            "template_id",
		            "adate",
		            "updateAt",
		            "status",
		            "message_type",
		            "hour",
                    "minute",
		        );
		$values = array(
					$parent_bid,
		            $user_id,
		            $broadcast_name,
		            '',
		            '',
		            $fb_page,
		            '',
		            $fb_template_id,
		            $adate,
		            $adate,
		            "0",
		            "fbm",
		             $hours,
                    $minutes,
		        );
		$broadcast_id = $db->rp_insert("broadcast",$values,$rows);
		/****** insert data into broadcast table end ***************/

		foreach ($lead_ids as $lead) {
            $message_id = '';
            /****** insert data into broadcast detail table start ***************/
            $rows   = array(
                        "user_id",
                        "broadcast_id",
                        "message_id",
                        "lead_id",
                        "email",
                        "name",
                        "adate"
                    );
            $values = array(
                        $user_id,
                        $broadcast_id,
                        $message_id,
                        $lead['id'],
                        $lead['fb_lead_id'],
                        $lead['fname']." ".$lead['lname'],
                        $adate
                    );
            $broadcast_detail_id = $db->rp_insert("broadcast_detail",$values,$rows);
            /****** insert data into broadcast detail table end ***************/

			if(!$confirmation || ($hours==0 && $minutes==0)){
	            $msg_link_ref = SITEURL.'/fb_event/'.$broadcast_detail_id ;
	            $msg_data = array();
	            $message_data = array();

	            $data = sendFbMessage($db, $lead['fb_lead_id'], $message, $msg_link_ref, $msg_link_text, $user_page_access_token, $user_id);
	            $fb_data = json_decode($data, true);

	            $message_id = $fb_data['message_id'];

	            /****** update status in broadcast table start ************/
	            $db->rp_update("broadcast_detail", array("status" => "1" ) , "id=".$broadcast_id);
	            /****** update status in broadcast table start ************/


	            /****** update message_id in broadcast detail table start ************/
	            $db->rp_update("broadcast_detail", array("message_id" => $message_id ) , "id=".$broadcast_detail_id);
	            /****** update message_id in broadcast detail table end ************/
	        }

            $_SESSION['MSG'] = 'FBM_MESSAGE_SEND';
		}

	}else{
		$_SESSION['MSG'] = 'NO_MESSAGE_SEND';
	}

}

$db->rp_location( SITEURL . 'broadcast/' );


?>