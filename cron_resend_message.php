<?php

/*
to check cron 
$myfile = fopen("resend_cron_check.txt", "a");
$txt = "\nexecuted- ".time();
fwrite($myfile, $txt);
fclose($myfile); */

include("connect.php");
include("include/notification.class.php");

/*** Get all broadcast resend messages ****/
$resend_broadcast = "Select * from broadcast where status=0 and parent_bid !=0 and (hour!=0 || minute!= 0) ORDER BY id desc";
$resend_broadcast_r  =  mysql_query($resend_broadcast);

if( $resend_broadcast_r ) {
	if( mysql_num_rows($resend_broadcast_r) > 0 ) {

		while( $resend_broadcast_d = mysql_fetch_assoc($resend_broadcast_r) ){
		
			/*** Check wait time ***/
			$hour = $resend_broadcast_d['hour'];
			$minute = $resend_broadcast_d['minute'];
			$date = $resend_broadcast_d['adate'];
			$wait = date('Y-m-d H:i:s',strtotime('+'.$hour.' hour +'.$minute.' minutes',strtotime($date)));
			$currentTime = strtotime(date('Y-m-d H:i:s'));
			$waitTime = strtotime($wait);

			$pastOneMin= date('Y-m-d H:i:s', strtotime('-1 minutes'));
                        $pastOneMinTime = strtotime($pastOneMin);

		
$log = '';
$log.= "<br/>";
$log.= $resend_broadcast_d['id'];
$log.= "<br/>";
$log.= $pastOneMinTime." < ".$waitTime." && ".$waitTime." <= ".$currentTime;
$log.= "<br/>";



$file    = __DIR__.'/kp_log_error_log.txt';    
$content = $log;
$file_content = "=============== Write At => " . date( "y-m-d H:i:s" ) . " =============== \r\n";
$file_content .= $content . "\r\n\r\n";
file_put_contents( $file, $file_content, FILE_APPEND | LOCK_EX );



            if($pastOneMinTime < $waitTime && $waitTime <= $currentTime ) {
			//if($waitTime < $currentTime){

$file    = __DIR__.'/kp_log_error_log.txt';    
$content = $resend_broadcast_d['id'];
$file_content = "=============== Write At => " . date( "y-m-d H:i:s" ) . " =============== \r\n";
$file_content .= $content . "\r\n\r\n";
file_put_contents( $file, $file_content, FILE_APPEND | LOCK_EX );


				/*** Get main breadcast message detail ***/
				$parent_bid = $resend_broadcast_d['parent_bid'];

				$sub_parent_id = $db->rp_getValue("broadcast", "id", " parent_bid=".$parent_bid." and status=1 ORDER BY id DESC LIMIT 1");

				$temp_parent_bid = !empty($sub_parent_id) ? $sub_parent_id : $parent_bid; 

				$broadcast_r = $db->rp_getData( "broadcast", "*", "id=".$temp_parent_bid, "id DESC ");
				if( $broadcast_r ) {

					/** Get main broadcast message ids **/
					$msg_id_arr = array();
					$broadcast_msg_ids = "SELECT message_id FROM `broadcast_detail` WHERE broadcast_id = ".$temp_parent_bid." GROUP BY message_id";
					$broadcast_msg_ids = mysql_query( $broadcast_msg_ids );
					if( mysql_num_rows($broadcast_msg_ids) > 0 ) {
					    while( $msg = mysql_fetch_array($broadcast_msg_ids) ) {
					        $msg_id_arr[] = $msg['message_id'];
					    }
					}
					$msg_ids = "'" . implode( "','", $msg_id_arr ) . "'";


					/*** Checking main broadcast is email or fbm  and get all leads***/
					$broadcast_d = mysql_fetch_array($broadcast_r);
					if( !empty($broadcast_d['mail_subject']) && !empty($broadcast_d['from_name']) ) {
						/*** Process for email ***/
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
						/*** Process for FBM ***/
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

				/*** Get resend message type ***/
				$broadcast_type = $resend_broadcast_d['message_type'];

				if( !empty($lead_ids) && $broadcast_type == 'email'  ) {

					/*** If resend message type is email ***/

					/*** Get details for resend message ***/
					$broadcast_name = $resend_broadcast_d['name'];
					if($resend_broadcast_d['mail_subject'] == ''){
						$mail_subject = ' ';
					}else{
						$mail_subject = $resend_broadcast_d['mail_subject'];
					}
					$from_email = $resend_broadcast_d['from_email'];
					$from_name  = $resend_broadcast_d['from_name'];
					$selected_template = $resend_broadcast_d['template_id'];
					$adate = $resend_broadcast_d['adate'];
					$user_id = $resend_broadcast_d['user_id'];
					$broadcast_id = $resend_broadcast_d['id'];
					$e_template_r = $db->rp_getData("bal_email_builder",'*'," id='".$selected_template ."' AND UserId='".$user_id."' ");
		        
		        	/*** Send email message ***/
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
			                $response= $nt->mm_sendMultipleMail($lead_ids,$subject,$body,$from_email,$from_name,$db, $user_id, $broadcast_id); 

			                $rows   = array( "broadcast_id"  => $broadcast_id, );
			                $db->rp_update('broadcast_detail',$rows,"broadcast_id='".$response."'");

			                $rows   = array( "status" => "1", "message_id" => count($emails) );
			                $db->rp_update('broadcast',$rows,"  id='".$response."'");
			            }
			        }  
				} else if( !empty($lead_ids) && $broadcast_type == 'fbm' ){

					/*** If resend message type is fbm ***/
	            	$broadcast_id = $resend_broadcast_d['id'];	
					foreach ($lead_ids as $lead) {
	            		
	            		/*** Get details for resend message ***/
	            		$fb_page = $resend_broadcast_d['from_email'];
	            		$user_id = $resend_broadcast_d['user_id'];
	            		$fb_template_id =  $resend_broadcast_d['template_id'];

	            		/*** Get template details ***/
	            		$template = $db->rp_getData( "fb_template", "*", "id=".$fb_template_id, "id DESC ");
						$template_r = mysql_fetch_array($template);
						
						/*** Get broadcast details ***/
	            		$broadcast_detail = $db->rp_getData( "broadcast_detail", "*", "broadcast_id=".$broadcast_id." AND lead_id =".$lead['id']." AND email=".$lead['fb_lead_id'], "id DESC ");
						$broadcast_detail_r = mysql_fetch_array($broadcast_detail);
	            		
	            		/*** Get details for sending fbm message ***/
	            		$broadcast_detail_id = $broadcast_detail_r['id'];
	            		$message = $template_r['msg'];
	            		$msg_link_ref = SITEURL.'/fb_event/'.$broadcast_detail_id ;
	            		$msg_link_text = $template_r['button_txt'];
	            		$user_page_access_token = $db->rp_getValue("fb_pages", "fb_page_access_token", " fb_page_id=".$fb_page." AND is_connected=1 AND user_id=".$user_id);
	            	
	 					/*** Send fbm message ***/
			            $data = sendFbMessage($db, $lead['fb_lead_id'], $message, $msg_link_ref, $msg_link_text, $user_page_access_token, $user_id);
			            $fb_data = json_decode($data, true);
			            $message_id = $fb_data['message_id'];

			            /*** Update message_id in broadcast detail table ***/
			            $db->rp_update("broadcast_detail", array("message_id" => $message_id ) , "id=".$broadcast_detail_id);
			           
	        		}
	        		$rows   = array( "status" => "1" );
			        $db->rp_update('broadcast',$rows,"  id='".$broadcast_id."'");
	        	}
			}			
		}	
	}
}
?>