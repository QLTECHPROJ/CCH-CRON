<?php
ini_set('max_execution_time', 10800); // 3 Hour
ini_set("memory_limit", "-1");
set_time_limit(0);
include 'common.php';
$db = new DB();
$cron_start_date_time = date("Y-m-d H:i:s");
$table = "cron_run";
$cron_file = 'resnotes.php';
$arrdatacron = array("start_date_time"=>$cron_start_date_time,"cron_file"=>$cron_file);
$cron_id = $db->CommonInsert($table,$arrdatacron);
?>
<?php
include 'class/cch.class.php';
include 'zoho/Zoho.php';
include 'conn.php';
$success = true;
$check = 1;
$db = new DB();
$cch = new cch();
$table = "resnotes";
	$GetResNotes = $cch->GetResNotes(); // ResNotes data get from api 
// echo "<PRE>";
// print_r($GetResNotes);
// echo "<br/>";
	foreach ($GetResNotes as $key => $value) {
		$row_data = json_encode($value);
		$notes_id = $value['notesid'];
		$FetchResNotes = $db->FetchResNotes($notes_id); // location data get from database
		$num_row = $FetchResNotes->num_rows;
		if($num_row > 0)
		{	
			while ($row_FetchResNotes=mysqli_fetch_assoc($FetchResNotes))
			{

				// echo "<pre>";
				// print_r($row_FetchResNotes);
				// exit;
				$updatearray = array(
										'reservation_no'=>$value['reservationno'],
										'un_allocated_reservation_no' =>$value['unallocatedreservationno'],
										'priority'=>$value['priority'],
										'status' => $value['status'],	
										'subject'=>$value['subject'],
										'date_entered'=>$value['dateentered'],
										'entered_byid'=>$value['enteredbyid'],
										'last_updated' =>$value['lastupdated'],
										'last_update_dbyid'=>$value['lastupdatedbyid'],
										'follow_up_operator_id' =>$value['followupoperatorid'],
										'notes'=>$value['notes'],
										'flag' =>2, 
										'row_data' => $row_data	
									);
				$whereClause="notes_id='$notes_id'"; 
				$result = $db->CommonUpdate($table,$updatearray, $whereClause);
			}	 
		}
		else
		{			
			   $arrdata = array(		
										'notes_id'=>$value['notesid'],
										'reservation_no'=>$value['reservationno'],
										'un_allocated_reservation_no' =>$value['unallocatedreservationno'],
										'priority'=>$value['priority'],
										'status' => $value['status'],	
										'subject'=>$value['subject'],
										'date_entered'=>$value['dateentered'],
										'entered_byid'=>$value['enteredbyid'],
										'last_updated' =>$value['lastupdated'],
										'last_update_dbyid'=>$value['lastupdatedbyid'],
										'follow_up_operator_id' =>$value['followupoperatorid'],
										'notes'=>$value['notes'],
										'flag' =>2, 
										'row_data' => $row_data	
			   				);
			   $data = $db->CommonInsert($table,$arrdata);	
		}
	}
	//                      //Zoho code   Reservation_Notes
	$objZoho = new Zoho();
	$select_query=mysqli_query($conn,"SELECT * FROM resnotes WHERE 	flag= 2 ");
	
	$reservationsId ="";
	while ($row = mysqli_fetch_assoc($select_query))
	{
			echo "<pre>";
		print_r($row);
		
		$table1 = "resnotes";
		
		$crmLog = "";
		$auto_id = $row['auto_id'];
		$notes_id = $row['notes_id'];
		$reservation_no = $row['reservation_no'];
		$un_allocated_reservation_no = $row['un_allocated_reservation_no'];
		$priority = $row['priority'];
		$status = $row['status'];
		$subject = $row['subject'];
		$date_entered = ConvertDate($row['date_entered']);
		$entered_byid = $row['entered_byid'];
		$last_updated = ConvertDate($row['last_updated']);
		$last_update_dbyid = $row['last_update_dbyid'];
		$follow_up_operator_id = $row['follow_up_operator_id'];
		$notes = $row['notes'];
		echo $subject."<br/>";
		try
		{
			if($objZoho->checkTokens())
			{

	
				// For reservation lookup
			

				if($reservation_no!=0)
				{
					
					$criteria_reservations="((Reservation_No_R:equals:".$reservation_no."))";
					$arrParams_reservations['criteria']=$criteria_reservations;
					$arrTrigger=["workflow"];
					$respSearchReservations=$objZoho->searchRecords("Reservations",$arrParams_reservations,$arrTrigger);
					if(empty($respSearchReservations['data']))
					{
						
						if($un_allocated_reservation_no!=0)
						{
							
							$criteria_reservations="((Unallocated_Reservation_No_R:equals:".$un_allocated_reservation_no."))";
							$arrParams_reservations['criteria']=$criteria_reservations;
							$arrTrigger=["workflow"];
							$respSearchReservations=$objZoho->searchRecords("Reservations",$arrParams_reservations,$arrTrigger);
							if(empty($respSearchReservations['data'])){
								$check = 0;
							}
						}
						
					}
				}
				else if($reservation_no==0 && $un_allocated_reservation_no!=0)
				{
					
						$criteria_reservations="((Unallocated_Reservation_No_R:equals:".$un_allocated_reservation_no."))";
						$arrParams_reservations['criteria']=$criteria_reservations;
						$arrTrigger=["workflow"];
						$respSearchReservations=$objZoho->searchRecords("Reservations",$arrParams_reservations,$arrTrigger);
						if(empty($respSearchReservations['data'])){
							$check = 0;
						}
				}
				else
				{
						$check = 0;
						
				}
				


				if($check==1)
				{
					
					if(count($respSearchReservations['data']))
					{
						$reservationsId = $respSearchReservations['data'][0]['id'];
						echo "<pre>";
						print_r($reservationsId);
					}
				

				// else
				// {
					
				// 	$arrReservations=[
				// 		'Reservation_No_R'=>$reservation_no,
				// 		'Name'=>"RESERVATION_".$auto_id,
				// 		'Cron_Name'=>'resnotes.php',
				// 		'Unallocated_Reservation_No_R'=>$un_allocated_reservation_no,
					
				// 	];
				// 	$arrInsertReservations=[];
				// 	$arrInsertReservations[]=$arrReservations;
				// 	$arrTrigger=["workflow"];
				// 	$respInsertReservations=$objZoho->insertRecord("Reservations",$arrInsertReservations,$arrTrigger);
				
				// 	if($respInsertReservations)
				// 	{
				// 		if($respInsertReservations['data'][0]['code']=="SUCCESS")
				// 		{
				// 			$reservationsId=$respInsertReservations['data'][0]['details']['id'];
				// 			$crmLog.="Inserted Reservations through resnotes : ".$reservationsId.", ";    
					  
						
				// 		}
				// 		else
				// 		{
				// 			$crmLog.="Failed to insert Reservations through resnotes !=success ";
				// 			$success=false;
				// 		}
				// 	}
				// 	else
				// 	{
				// 		$crmLog.="Failed to insert Reservations through resnotes ";
				// 		$success=false;
				// 	}
				// }
				
			

				//for Reservation Notes
				$criteria="((Notes_ID_R:equals:".$notes_id."))";
				$arrParams['criteria']=$criteria;
					$arrTrigger=["workflow"];
				$respSearchResNotes=$objZoho->searchRecords("Reservation_Notes",$arrParams,$arrTrigger);
				if(count($respSearchResNotes['data']))
				{
					$arrResNotes['Notes_ID_R']=$notes_id;
					$arrResNotes['Reservation']=$reservationsId;
					$arrResNotes['Reservation_No']=$reservation_no;
					$arrResNotes['Unallocated_Reservation_No']=$un_allocated_reservation_no;
					$arrResNotes['Priority']=$priority;
					$arrResNotes['Status']=$status;
					$arrResNotes['Subject']=$subject;
					$arrResNotes['Date_Entered']=$date_entered;
					$arrResNotes['Follow_Up_Operator']=$follow_up_operator_id;
					$arrResNotes['Notes1']=$notes;
					$arrResNotes['Cron_Name']='resnotes.php';
					$resnotesId=$respSearchResNotes['data'][0]['id'];
					$arrUpdateResNotes=[];
					$arrUpdateResNotes[]=$arrResNotes;
					$arrTrigger=["workflow"];
					$respUpdateResNotes=$objZoho->updateRecord("Reservation_Notes",$resnotesId,$arrUpdateResNotes,$arrTrigger);
					print_r($respUpdateResNotes);
					if($respUpdateResNotes)
					{
						if($respUpdateResNotes['data'][0]['code']=="SUCCESS")
						{
							$resnotesId = $respUpdateResNotes['data'][0]['details']['id'];	
							$crmLog.="Updated Res Notes through resnotes: ".$resnotesId.", ";
							$updatearray = array('flag'=>0);
							$whereClause="notes_id='$notes_id'";  
							$result = $db->CommonUpdate("resnotes",$updatearray, $whereClause);
							// $arrResNotes[] ="";
						}
						else
						{
							$crmLog.="Failed to update Res Notes through resnotes !=success: ".$resnotesId.", ";	
							$success=false;
						}
					}
					else
					{
						$crmLog.="Failed to update Res Notes  through resnotes: ".$resnotesId.", ";	
						$success=false;
					}
				}
				else
				{
					$arrResNotes=[
						'Notes_ID_R'=>$notes_id,
						'Reservation'=>$reservationsId,
						'Reservation_No'=>$reservation_no,
						'Unallocated_Reservation_No'=>$un_allocated_reservation_no,
						'Priority'=>$priority,
						'Status'=>$status,
						'Subject'=>$subject,
						'Date_Entered'=>$date_entered,
						'Follow_Up_Operator'=>$follow_up_operator_id,
						'Cron_Name'=>'resnotes.php',
						'Notes1'=>$notes
					];
					$arrInsertResNotes=[];
					$arrInsertResNotes[]=$arrResNotes;
					$arrTrigger=["workflow"];
					$respInsertResNotes=$objZoho->insertRecord("Reservation_Notes",$arrInsertResNotes,$arrTrigger);
					print_r($respInsertResNotes);
					if($respInsertResNotes)
					{
						if($respInsertResNotes['data'][0]['code']=="SUCCESS")
						{
							
							$resnotesId=$respInsertResNotes['data'][0]['details']['id'];
							$crmLog.="Inserted Res Notes through resnotes : ".$resnotesId.", ";	
							$updatearray = array('flag'=>0);
							$whereClause="notes_id='$notes_id'";  
							$result = $db->CommonUpdate("resnotes",$updatearray, $whereClause);
							// $arrResNotes[] ="";
						}
						else
						{
							$crmLog.="Failed to insert Res Notes through resnotes !=success ";
							$success=false;
						}
					}
					else
					{
						$crmLog.="Failed to insert Res Notes through resnotes ";
						$success=false;
					}
				}
			

				
				if($subject == 'Linked Reservation No')
				{
					
					
						$arrReservation['Linked_Reservation_No_R']=$notes;
						$arrReservation['Linked_Travel_Companion_R']='';
						$arrReservation['Cron_Name']='resnotes.php';
						$arrUpdateReservation=[];
					    $arrUpdateReservation[]=$arrReservation;
						$arrTrigger=["workflow"];
						$respUpdateReservation=$objZoho->updateRecord("Reservations",$reservationsId,$arrUpdateReservation,$arrTrigger);
					
						if($respUpdateReservation)
						{
							if($respUpdateReservation['data'][0]['code']=="SUCCESS")
							{
								$crmLog.="Updated Reservation through resnotes : ".$reservationsId.", ";
								// $updatearray = array('flag'=>0);
								// $whereClause="reservationno='$reservation_no'"; 
								// $result = $db->CommonUpdate('repbooking_export',$updatearray, $whereClause);
							}
							else
							{
								$crmLog.="Failed to update Reservation through resnotes !=success: ".$reservationsId.", ";	
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to update Reservation through resnotes: ".$reservationsId.", ";	
							$success=false;
						}
				}

				if($subject == 'Linked Travel Companion')
				{
					
					// $reservationNoTravel = $respSearchReservationTravel['data'][0]['id'];
						// $reservation_nameTravel = $respSearchReservationNoTravel['data'][0]['id'];
						$arrReservationTravel['Linked_Travel_Companion_R']=$notes;
						$arrReservationTravel['Linked_Reservation_No_R']='';
						$arrReservationTravel['Cron_Name']='resnotes.php';
						$arrUpdateReservationTravel=[];
					    $arrUpdateReservationTravel[]=$arrReservationTravel;
						$arrTrigger=["workflow"];
						$respUpdateReservationTravel=$objZoho->updateRecord("Reservations",$reservationsId,$arrUpdateReservationTravel,$arrTrigger);
						if($respUpdateReservation)
						{
							if($respUpdateReservationTravel['data'][0]['code']=="SUCCESS")
							{
								$crmLog.="Updated Reservation  through resnotes: ".$reservationsId.", ";
								// $updatearray = array('flag'=>0);
								// $whereClause="reservationno='$reservation_no'"; 
								// $result = $db->CommonUpdate('repbooking_export',$updatearray, $whereClause);
							
							}
							else
							{
								$crmLog.="Failed to update Reservation through resnotes !=success: ".$reservationsId.", ";	
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to update Reservation through resnotes: ".$reservationsId.", ";	
							$success=false;
						}
				}
			} // if check = 1 closed

			}
			else
			{
				$crmLog.=", Token-Error";
				$success=false;
			}
		}
		catch(Exception $e)
		{
			$crmLog.="Exception : ".$e->getMessage().", ";
			$success=false;
		}
		crmLog($crmLog,$success);
		$crmLog = "";
	}//end of while loop


			$table = "cron_run";
			$cron_end_date_time = date("Y-m-d H:i:s");
			$cron_duration = strtotime($cron_end_date_time) - strtotime($cron_start_date_time);
			$arrcrondata = array("end_date_time"=>$cron_end_date_time,
				"duration"=>$cron_duration,
				"cron_completed"=>1);
			$whereClause = "id='$cron_id'";
			$result = $db->CommonUpdate($table,$arrcrondata, $whereClause);
			function ConvertDateTime($date)
			{
				if($date!="0000-00-00 00:00:00" && $date!="")
				{
					$convert = str_replace('/', '-', $date);
					$date_time = strtotime($convert);
					$final_date =  date("Y-m-d\TH:i:s+08:00", $date_time);
				}
				else
				{
					$final_date = "";
				}
				return $final_date;
			}
			function ConvertDate($date)
			{
				if($date!="0000-00-00 00:00:00" && $date!="")
				{
					$convert = str_replace('/', '-', $date);
					$date_time = strtotime($convert);
					$final_date =  date('Y-m-d', $date_time);
				}
				else
				{
					$final_date = "";
				}
				return $final_date;
			}

			
			?>