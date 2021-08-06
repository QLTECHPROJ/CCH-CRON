
<?php
include 'common.php';
$db = new DB();
$cron_start_date_time = date("Y-m-d H:i:s");
$table = "cron_run";
$cron_file = 'bookingextrafees.php';
$arrdatacron = array("start_date_time"=>$cron_start_date_time,"cron_file"=>$cron_file);
$cron_id = $db->CommonInsert($table,$arrdatacron);
?>
<?php
	include 'class/cch.class.php';
	include 'zoho/Zoho.php';
    include 'conn.php';
    $success = true;
    $check = 1;
    $checkExtraItem =1;
	$db = new DB();
	$cch = new cch();
	$table1 = "bookingextrafees";
	$extrafees = $cch->GetBookingExtraFees(); // location data get from api 
	// echo "<pre>";
	// print_r($extrafees);
	// exit;

	foreach ($extrafees as $key => $value) 
	{
		// if($value['extrafeesid']!=93)
		// {
			$row_data = json_encode($value);
			$extra_fees_id = $value['extrafeesid']; 
		
			$reservationno = $value['reservationno'];
			$unallocatedreservationno = $value['unallocatedreservationno'];
			
				if($extra_fees_id!=0 &&  $reservationno!=0)
				{
						
					$FetchBookingExtraFees = $db->FetchBookingExtraFeesWithReservation($extra_fees_id,$reservationno); // Booking Extra item data get from database
					$num_rows = $FetchBookingExtraFees->num_rows;

					if($num_rows < 1)  // empty rows 
					{
						if($unallocatedreservationno!=0)
						{
							$FetchBookingExtraFees = $db->FetchBookingExtraFeesWithNonReservation($extra_fees_id,$unallocatedreservationno); // Booking Extra item data get from database
						}
					}
				}
				else if($unallocatedreservationno!=0 && $extra_fees_id!=0)
				{
						
					$FetchBookingExtraFees = $db->FetchBookingExtraFeesWithNonReservation($extra_fees_id,$unallocatedreservationno); // Booking Extra item data get from database
			
				}
				else
				{
						//SEND MAIL
				}	
			// $FetchBookingExtraFees = $db->FetchBookingExtraFees($extra_fees_id,$reservationno,$unallocatedreservationno); // Booking Extra item data get from database
			$num_row = $FetchBookingExtraFees->num_rows;
			if($num_row > 0)
			{	
				while ($row_extrafees=mysqli_fetch_assoc($FetchBookingExtraFees))
				{
					$auto_id = $row_extrafees['auto_id'];
					$updatearray = array(
											'extra_fees_id'=>$value['extrafeesid'],
											'name'=>$value['name'],
				   							'reservation_no'=>$value['reservationno'],
				   							'un_allocated_reservation_no'=>$value['unallocatedreservationno'],
				   							'extra_daily_rate'=>$value['extradailyrate'],
				   							'days' => $value['days'],
				   							'flag' => 2,
				   							'row_data' => $row_data
										);
					$whereClause="auto_id='$auto_id'"; 
					$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
				}	 
			}
			else
			{
				   $arrdata = array(
				   					'extra_fees_id'=>$value['extrafeesid'],
				   					'name'=>$value['name'],
				   					'reservation_no'=>$value['reservationno'],
				   					'un_allocated_reservation_no'=>$value['unallocatedreservationno'],
				   					'extra_daily_rate'=>$value['extradailyrate'],
				   					'days' => $value['days'],
				   					'flag' => 2,
				   					'row_data' => $row_data
				   				);
				   $data = $db->CommonInsert($table1,$arrdata);
			}
	    // }
	}

// 	//Zoho Code
	$objZoho = new Zoho();
	$select_query=mysqli_query($conn,"SELECT * FROM bookingextrafees WHERE flag = 2");
	while($row = mysqli_fetch_assoc($select_query))
	{
		$crmLog = "";
		$auto_id = $row['auto_id'];
		// echo $auto_id;
		$extra_fees_id = $row['extra_fees_id'];
		$name = $row['name'];
		$reservation_no = $row['reservation_no'];
		$un_allocated_reservation_no = $row['un_allocated_reservation_no'];
		$extra_daily_rate = $row['extra_daily_rate'];
		$days = $row['days'];
		// if($reservation_no!=0)
		// {
		// 	$final_reservation_no = $reservation_no;
		// }
		try
		{
			if($objZoho->checkTokens())
			{
				// for extra item lookup Extraitems  -- Unique ID 
				$criteria_extra_item="((ExtraitemID:equals:".$extra_fees_id."))";
				$arrParams_extra_item['criteria']=$criteria_extra_item;
				$arrTrigger=["workflow"];
				$respSearchExtraItem=$objZoho->searchRecords("Extraitems",$arrParams_extra_item,$arrTrigger);
			      if(count($respSearchExtraItem['data']))
					{
						$extra_item_id = $respSearchExtraItem['data'][0]['id'];
					}else
					{
						$arrExtraItem=[
							'ExtraitemID'=>$extra_fees_id,
							'Cron_Name'=>'bookingextrafees.php',
							// 'Name'=> $name,

						];
						$arrInsertExtraItem=[];
						$arrInsertExtraItem[]=$arrExtraItem;
						$arrTrigger=["workflow"];
						$respInsertExtraItem=$objZoho->insertRecord("Extraitems",$arrInsertExtraItem,$arrTrigger);
						// print_r($respInsertExtraItem);
						if($respInsertExtraItem)
						{
							if($respInsertExtraItem['data'][0]['code']=="SUCCESS")
							{

								$extra_item_id=$respInsertExtraItem['data'][0]['details']['id'];
								$crmLog.="Extra Item through bookingextrafees: ".$extra_item_id.", ";	
							}
							else
							{
								$crmLog.="Failed to insert Extra Item through bookingextrafees !=success ";
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to insert Extra Item through bookingextrafees ";
							$success=false;
						}
					}



				// For reservation lookup
		
				if($reservation_no!=0)
				{

						$criteria_reservations="((Reservation_No_R:equals:".$reservation_no."))";
					$arrParams_reservations['criteria']=$criteria_reservations;
					$arrTrigger=["workflow"];
					$reservationData=$objZoho->searchRecords("Reservations",$arrParams_reservations,$arrTrigger);
				
					if(empty($reservationData['data']))
					{
						if($un_allocated_reservation_no!=0)
						{
							$criteria_reservations="((Unallocated_Reservation_No_R:equals:".$un_allocated_reservation_no."))";
							$arrParams_reservations['criteria']=$criteria_reservations;
							$arrTrigger=["workflow"];
							$reservationData=$objZoho->searchRecords("Reservations",$arrParams_reservations,$arrTrigger);
							if(empty($reservationData['data']))
							{
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
					$reservationData=$objZoho->searchRecords("Reservations",$arrParams_reservations,$arrTrigger);
					if(empty($reservationData['data']))
					{
						$check = 0;
					}
				}
				else
				{
					$check = 0;
				}
				if($check==1)
				{
					if(count($reservationData['data']))
					{
					$reservationId=$reservationData['data'][0]['id'];
						
					}
					else
					{
						$crmLog.="Error : Unable to Retreive the record With Resevation No  : ".$reservation_no.", Or Unallocated ReservationNo  ".$un_allocated_reservation_no ;  
					}
				}
				// else
				// {
				// 	$arrReservations=[
				// 		'Reservation_No_R'=>$reservation_no,
				// 		'Name'=>"Reservation_".$auto_id,
				// 		'Cron_Name'=>'bookingextrafees.php',
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
				// 			$reservationId=$respInsertReservations['data'][0]['details']['id'];
				// 			$crmLog.="Inserted Reservations through bookingextrafees: ".$reservationId.", "; 
				// 			// $updatearray = array('flag'=>0);
				// 			// $whereClause="auto_id='$auto_id'"; 
				// 			// $result = $db->CommonUpdate($table1,$updatearray, $whereClause);   
						
				// 		}
				// 		else
				// 		{
				// 			$crmLog.="Failed to insert Reservations through bookingextrafees !=success ";
				// 			$success=false;
				// 		}
				// 	}
				// 	else
				// 	{
				// 		$crmLog.="Failed to insert Reservations through bookingextrafees ";
				// 		$success=false;
				// 	}
				// }



					//for the reservation extra item
					$reservation_extra = $objZoho->getRecordById("Reservations",$reservationData);
					//echo "<pre>";
					//print_r($reservation_extra);
					$Business_Arm = $reservation_extra['data'][0]['Business_Arm'];
					$Pick_Up_Date_Time = $reservation_extra['data'][0]['Pick_Up_Date_Time'];
					$Drop_Off_Date_Time = $reservation_extra['data'][0]['Drop_Off_Date_Time'];
					$Booking_Total = $reservation_extra['data'][0]['Booking_Total'];
					$Status = $reservation_extra['data'][0]['Status'];
					$Drop_Off_Location_Code = $reservation_extra['data'][0]['Drop_Off_Location_Code'];
					$Pick_Up_Location_Code = $reservation_extra['data'][0]['Pick_Up_Location_Code'];
					

					
					//lookup for drop off locations

				$criteria_location_drop="((City_Code:equals:".$Drop_Off_Location_Code."))";
				$arrParams_location_drop['criteria']=$criteria_location_drop;
				$arrTrigger=["workflow"];
				$respSearchLocationDrop=$objZoho->searchRecords("Locations",$arrParams_location_drop,$arrTrigger);
			      if(count($respSearchLocationDrop['data']))
					{
						$drop_off_lookup = $respSearchLocationDrop['data'][0]['id'];
					}
					else
					{
						$arrDropOff=[
							'City_Code'=>$Drop_Off_Location_Code,
							'Cron_Name'=>'bookingextrafees.php',
							

						];
						$arrInsertDropOff=[];
						$arrInsertDropOff[]=$arrDropOff;
						$arrTrigger=["workflow"];
						$respInsertDropOff=$objZoho->insertRecord("Locations",$arrInsertDropOff,$arrTrigger);
						// print_r($respInsertDropOff);
						if($respInsertDropOff)
						{
							if($respInsertDropOff['data'][0]['code']=="SUCCESS")
							{
				
								$drop_off_lookup=$respInsertDropOff['data'][0]['details']['id'];
								$crmLog.="Drop Off Location through bookingextrafees: ".$drop_off_lookup.", ";	
							}
							else
							{
								$crmLog.="Failed to insert Drop Off Location through bookingextrafees !=suucess ";
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to insert Drop Off Location through bookingextrafees ";
							$success=false;
						}
					}


					//lookup for pick up location

				$criteria_location_pick="((City_Code:equals:".$Pick_Up_Location_Code."))";
				$arrParams_location_pick['criteria']=$criteria_location_pick;
				$arrTrigger=["workflow"];
				$respSearchLocationPick=$objZoho->searchRecords("Locations",$arrParams_location_pick,$arrTrigger);
			      if(count($respSearchLocationPick['data']))
					{
						$pick_off_lookup = $respSearchLocationPick['data'][0]['id'];
					}
					else
					{
						$arrPickUp=[
							'City_Code'=>$Pick_Up_Location_Code,
							'Cron_Name'=>'bookingextrafees.php',
						
						];
						$arrInsertPickUp=[];
						$arrInsertPickUp[]=$arrPickUp;
						$arrTrigger=["workflow"];
						$respInsertPickUp=$objZoho->insertRecord("Locations",$arrInsertPickUp,$arrTrigger);
						// print_r($respInsertDropOff);
						if($respInsertPickUp)
						{
							if($respInsertPickUp['data'][0]['code']=="SUCCESS")
							{
				
								$pick_off_lookup=$respInsertPickUp['data'][0]['details']['id'];
								$crmLog.="Pick Up Location through bookingextrafees: ".$pick_off_lookup.", ";	
							}
							else
							{
								$crmLog.="Failed to insertPick Up Location through bookingextrafees !=success ";
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to insert Pick Up Location through bookingextrafees ";
							$success=false;
						}
					}




					if($extra_fees_id!=0 &&  $reservation_no!=0)
					{
						
						//for booking etra item Extra_Items_Booked2
						$criteria="((Reservation_No:equals:".$reservation_no.") and (Extra_Item_No_R:equals:".$extra_fees_id."))";
						$arrParams['criteria']=$criteria;
						$arrTrigger=["workflow"];
						$respSearchExtraItem=$objZoho->searchRecords("Extra_Items_Booked2",$arrParams,$arrTrigger);
						if(empty($respSearchExtraItem['data']))
						{
							if($un_allocated_reservation_no!=0)
							{
								$criteria="((Extra_Item_No_R:equals:".$extra_fees_id.") and (Unallocated_Reservation_No:equals:".$un_allocated_reservation_no."))";
								$arrParams['criteria']=$criteria;
								$arrTrigger=["workflow"];
								$respSearchExtraItem=$objZoho->searchRecords("Extra_Items_Booked2",$arrParams,$arrTrigger);
								if(empty($respSearchExtraItem['data']))
								{
									$checkExtraItem = 0;
								}
							}
						}
					}
					else if($un_allocated_reservation_no!=0 && $extra_fees_id!=0)
					{
						$criteria="((Extra_Item_No_R:equals:".$extra_fees_id.") and (Unallocated_Reservation_No:equals:".$un_allocated_reservation_no."))";
						$arrParams['criteria']=$criteria;
						$arrTrigger=["workflow"];
						$respSearchExtraItem=$objZoho->searchRecords("Extra_Items_Booked2",$arrParams,$arrTrigger);
							if(empty($respSearchExtraItem['data']))
							{
								$checkExtraItem = 0;
							}
					}
					else
					{
							$checkExtraItem = 0;
					}	
					

					if($checkExtraItem == 1)
					{
						// if(count($respSearchExtraItem['data']))
						// {
							$arrExtraBookedItem['Extra_Item_No_R']=$extra_fees_id;
							$arrExtraBookedItem['Extra_Items']=$extra_item_id;
							$arrExtraBookedItem['Reservation_No']=$reservation_no;
							$arrExtraBookedItem['Reservations']=$reservationId;
							$arrExtraBookedItem['Unallocated_Reservation_No']=$un_allocated_reservation_no;
							$arrExtraBookedItem['Extra_Item_Daily_Rate']=$extra_daily_rate;
							$arrExtraBookedItem['No_of_Days']=$days;
							$arrExtraBookedItem['Business_Arm']=$Business_Arm;
							$arrExtraBookedItem['Pick_Up_Date_Time']=$Pick_Up_Date_Time;
							$arrExtraBookedItem['Drop_Off_Date_Time']=$Drop_Off_Date_Time;
							$arrExtraBookedItem['Total']=$Booking_Total;
							$arrExtraBookedItem['Status']=$Status;
							$arrExtraBookedItem['Pick_Up_Location']=$pick_off_lookup;
							$arrExtraBookedItem['Drop_Off_Location']=$drop_off_lookup;
							$arrExtraBookedItem['Cron_Name']='bookingextrafees.php';
							$extrabookeditemid=$respSearchExtraItem['data'][0]['id'];
							$arrUpdateExtraBookedItem=[];
							$arrUpdateExtraBookedItem[]=$arrExtraBookedItem;
							$arrTrigger=["workflow"];
							$respUpdateExtraBookedItem=$objZoho->updateRecord("Extra_Items_Booked2",$extrabookeditemid,$arrUpdateExtraBookedItem,$arrTrigger);
							//print_r($respUpdateExtraBookedItem);
							if($respUpdateExtraBookedItem)
							{
								if($respUpdateExtraBookedItem['data'][0]['code']=="SUCCESS")
								{
									$crmLog.="Updated Extra Booked Item through bookingextrafees: ".$extrabookeditemid.", ";
									$updatearray = array('flag'=>0);
									$whereClause="auto_id='$auto_id'"; 
									$result = $db->CommonUpdate("bookingextrafees",$updatearray, $whereClause);
								}
								else
								{
									$crmLog.="Failed to update Extra Booked Item through bookingextrafees !=success: ".$extrabookeditemid.", ";	
									$success=false;
								}
							}
							else
							{
								$crmLog.="Failed to update Extra Booked Item through bookingextrafees: ".$extrabookeditemid.", ";	
								$success=false;
							}
						// }
						// else
						// {
						// 	$crmLog.="Error : Unable to Retreive the record With Resevation No  : ".$reservation_no.", Or Unallocated ReservationNo  ".$un_allocated_reservation_no." or extra fee id".$extra_fees_id ;  
						// }
					}
					else
					{

						
							$arrExtraBookedItem=[
								'Extra_Item_No_R'=>$extra_fees_id,
								'Extra_Items'=>$extra_item_id,
								'Reservation_No'=>$reservation_no,
								'Reservations'=>$reservationId,
								'Unallocated_Reservation_No'=>$un_allocated_reservation_no,
								'Extra_Item_Daily_Rate'=>$extra_daily_rate,
								'No_of_Days'=>$days,
								'Business_Arm'=>$Business_Arm,
								'Pick_Up_Date_Time'=>$Pick_Up_Date_Time,
								'Drop_Off_Date_Time'=>$Drop_Off_Date_Time,
								'Total'=>$Booking_Total,
								'Status'=>$Status,
								'Pick_Up_Location'=>$pick_off_lookup,
								'Cron_Name'=>'bookingextrafees.php',
								'Drop_Off_Location'=>$drop_off_lookup
							];
							$arrInsertExtraBookedItem=[];
							$arrInsertExtraBookedItem[]=$arrExtraBookedItem;
							$arrTrigger=["workflow"];
							$respInsertExtraBookedItem=$objZoho->insertRecord("Extra_Items_Booked2",$arrInsertExtraBookedItem,$arrTrigger);
							print_r($respInsertExtraBookedItem);
							if($respInsertExtraBookedItem)
							{
								if($respInsertExtraBookedItem['data'][0]['code']=="SUCCESS")
								{
									$extrabookeditemid=$respInsertExtraBookedItem['data'][0]['details']['id'];
									$crmLog.="Extra Booked Item Inserted through bookingextrafees: ".$extrabookeditemid.", ";	
									$updatearray = array('flag'=>0);
									$whereClause="auto_id='$auto_id'"; 
									$result = $db->CommonUpdate("bookingextrafees",$updatearray, $whereClause);
								}
								else
								{
									$crmLog.="Failed to insert Extra Booked Item through bookingextrafees !=success";
									$success=false;
								}
							}
							else
							{
								$crmLog.="Failed to insert Extra Booked Item through bookingextrafees ";
								$success=false;
							}
					}
					
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
	} //end of while loop

	$table = "cron_run";
		$cron_end_date_time = date("Y-m-d H:i:s");
		$cron_duration = strtotime($cron_end_date_time) - strtotime($cron_start_date_time);
		$arrcrondata = array("end_date_time"=>$cron_end_date_time,
			"duration"=>$cron_duration,
			"cron_completed"=>1);
		$whereClause = "id='$cron_id'";
		$result = $db->CommonUpdate($table,$arrcrondata, $whereClause);
	             //function to convert date for zoho
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
		         //function to convert date for zoho
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
?>