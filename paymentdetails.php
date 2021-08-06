<?php
ini_set('max_execution_time', 10800); // 3 Hour
ini_set("memory_limit", "-1");
set_time_limit(0);
include 'common.php';
$db = new DB();
$cron_start_date_time = date("Y-m-d H:i:s");
$table = "cron_run";
$cron_file = 'paymentdetails.php';
$arrdatacron = array("start_date_time"=>$cron_start_date_time,"cron_file"=>$cron_file);
$cron_id = $db->CommonInsert($table,$arrdatacron);
?>
<?php
	include 'class/cch.class.php';
	include 'zoho/Zoho.php';
    include 'conn.php';
    $success = true;
    $check = 1;
    $checkRes=1;
	$db = new DB();
	$cch = new cch();
	$table = "paymentdetails";
	$Getpaymentdetails = $cch->Getpaymentdetails(); // payment data get from api Reservation_Rates
	// echo "<PRE>";
	// print_r($Getpaymentdetails);
	// exit;
	foreach ($Getpaymentdetails as $key => $value) {
		$row_data = json_encode($value);
		$reservation_no = $value['reservationno'];
		$unallocatedreservationno = $value['unallocatedreservationno'];
		$seasonid = $value['seasonid'];

				if($seasonid!=0 &&  $reservation_no!=0)
				{
					
					$FetchPaymentDetails = $db->FetchPaymentDetailsWithReservation($reservation_no,$seasonid); // payment data get from database
					$num_rows = $FetchPaymentDetails->num_rows;

					if($num_rows < 1)  // empty rows 
					{
						if($unallocatedreservationno!=0)
						{
							$FetchPaymentDetails = $db->FetchPaymentDetailsWithNonReservation($unallocatedreservationno,$seasonid); // payment data get from database
						}
					}
				}
				else if($unallocatedreservationno!=0 && $seasonid!=0 )
				{

					$FetchPaymentDetails = $db->FetchPaymentDetailsWithNonReservation($unallocatedreservationno,$seasonid); // payment data get from database
		
				}
				else
				{
					//send email
	 			}
		// $FetchPaymentDetails = $db->FetchPaymentDetails($reservation_no,$unallocatedreservationno,$seasonid); // payment data get from database

		$num_row = $FetchPaymentDetails->num_rows;
		if($num_row > 0)
		{	
			while ($row_PaymentDetails=mysqli_fetch_assoc($FetchPaymentDetails))
			{
			
					$auto_id = $row_PaymentDetails['auto_id'];
				$updatearray = array(
										'reservation_no'=>$value['reservationno'],
										'un_allocated_reservation_no'=>$value['unallocatedreservationno'],
										'days' =>$value['days'],
										'rate'=>$value['rate'],
										'season_id' => $value['seasonid'],	
										'rate_name'=>$value['ratename'],
										'standard_rate'=>$value['standardrate'],
										'discount_perc'=>$value['discountperc'],
										'discount_id' =>$value['discountid'],
										'discount_type'=>$value['discounttype'],
										'discount_name' =>$value['discountname'],
										'trip_rates'=>$value['triprates'],
										'extend_rate'=>$value['extendrate'],
										'no_hours' =>$value['nohours'],
										'flag' => 2,
										'row_data' => $row_data	
									);
				$whereClause="auto_id='$auto_id'"; 
				$result = $db->CommonUpdate($table,$updatearray, $whereClause);
			}	 
				//$update =   $data = $db->CommonUpdate($table,$value);
		}
		else
		{
			   $arrdata = array(		
										'reservation_no'=>$value['reservationno'],
										'un_allocated_reservation_no'=>$value['unallocatedreservationno'],
										'days' =>$value['days'],
										'rate'=>$value['rate'],
										'season_id' => $value['seasonid'],	
										'rate_name'=>$value['ratename'],
										'standard_rate'=>$value['standardrate'],
										'discount_perc'=>$value['discountperc'],
										'discount_id' =>$value['discountid'],
										'discount_type'=>$value['discounttype'],
										'discount_name' =>$value['discountname'],
										'trip_rates'=>$value['triprates'],
										'extend_rate'=>$value['extendrate'],
										'no_hours' =>$value['nohours'],
										'flag' => 2,
										'row_data' => $row_data	
			   				);
			   $data = $db->CommonInsert($table,$arrdata);
		}
	}
	//Zoho Code
	$objZoho = new Zoho();

	$select_query=mysqli_query($conn,"SELECT * FROM paymentdetails WHERE flag = 2");
	while($row = mysqli_fetch_assoc($select_query))
	{
		$table1 ="paymentdetails";
		$crmLog = "";
		$auto_id = $row['auto_id'];
		$reservation_no = $row['reservation_no'];
		$un_allocated_reservation_no = $row['un_allocated_reservation_no'];
		$days = $row['days'];
		$rate = $row['rate'];
		$season_id = $row['season_id'];
		$rate_name = $row['rate_name'];
		$standard_rate = $row['standard_rate'];
		$discount_perc = $row['discount_perc'];
		$discount_id = $row['discount_id'];
		$discount_type = $row['discount_type'];
		$discount_name = $row['discount_name'];
		$trip_rates = $row['trip_rates'];
		$extend_rate = $row['extend_rate'];
		$no_hours = $row['no_hours'];
		if($reservation_no!=0)

		{
			$final_reservation_no = $reservation_no;
		}
		try
		{
			if($objZoho->checkTokens())
			{

				//for season lookup
				$criteria_season="((Season_ID_R:equals:".$season_id."))";
				$arrParams_season['criteria']=$criteria_season;
				$arrTrigger=["workflow"];
				$respSearchSeason=$objZoho->searchRecords("Seasons",$arrParams_season,$arrTrigger);
				
			    if(count($respSearchSeason['data']))
				{
				$season = $respSearchSeason['data'][0]['id'];
				}
				else
				{
					$arrSeason=[
							
								'Season_ID_R'=>$season_id,
								'Cron_Name'=>'paymentdetails.php',
							
							];
					$arrInsertSeason=[];
					$arrInsertSeason[]=$arrSeason;
					$arrTrigger=["workflow"];
					$respInsertSeason=$objZoho->insertRecord("Seasons",$arrInsertSeason,$arrTrigger);
					if($respInsertSeason)
					{
						if($respInsertSeason['data'][0]['code']=="SUCCESS")
						{
							$season=$respInsertSeason['data'][0]['details']['id'];
							$crmLog.="Insert Season through paymentdetails: ".$season.", ";    
						}
						else
						{
							$crmLog.="Failed to Season through paymentdetails !=suceess ";
							$success=false;
						}
					}
					else
						{
							$crmLog.="Failed to Season through paymentdetails  ";
							$success=false;
						}
				}


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
							if(empty($respSearchReservations['data']))
							{
								$checkRes = 0;
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
					if(empty($respSearchReservations['data']))
					{
						$checkRes = 0;
					}
				}
				else
				{
						$checkRes = 0;
						
				}
				


				if($checkRes==1)
				{
					if(count($respSearchReservations['data']))
					{
						$reservationId = $respSearchReservations['data'][0]['id'];
					}
				}
				// else
				// {
				// 		$arrReservations=[
				// 			'Reservation_No_R'=>$reservation_no,
				// 			'Name'=>"Reservation_".$auto_id,
				// 			'Cron_Name'=>'paymentdetails.php',
				// 			'Unallocated_Reservation_No_R'=>$un_allocated_reservation_no,
						
				// 		];
				// 		$arrInsertReservations=[];
				// 		$arrInsertReservations[]=$arrReservations;
				// 		$arrTrigger=["workflow"];
				// 		$respInsertReservations=$objZoho->insertRecord("Reservations",$arrInsertReservations,$arrTrigger);
					
				// 		if($respInsertReservations)
				// 		{
				// 			if($respInsertReservations['data'][0]['code']=="SUCCESS")
				// 			{
				// 				$reservationId=$respInsertReservations['data'][0]['details']['id'];
				// 				$crmLog.="Inserted Reservations through paymentdetails: ".$reservationId.", "; 
				// 				$updatearray = array('flag'=>0);
				// 				$whereClause="auto_id='$auto_id'"; 
				// 				$result = $db->CommonUpdate("repbooking_export",$updatearray, $whereClause);
				// 				$arrReservations[] ="";   
				// 			}
				// 			else
				// 			{
				// 				$crmLog.="Failed to insert Reservations through paymentdetails !=suceess ";
				// 				$success=false;
				// 			}
				// 		}
				// 		else
				// 		{
				// 			$crmLog.="Failed to insert Reservations through paymentdetails ";
				// 			$success=false;
				// 		}
				// }



				//for booking etra item Extra_Items_Booked2
				//season id and res no && season ID and unallocated no 


				if($season_id!=0 &&  $reservation_no!=0)
				{
					
					$criteria="((Reservation_No_R:equals:".$reservation_no.")  and (Season_ID_R:equals:".$season_id."))";
					
					$arrParams['criteria']=$criteria;
					$arrTrigger=["workflow"];
					$respSearchPaymentDetails=$objZoho->searchRecords("Reservation_Rates",$arrParams,$arrTrigger);
				
					if(empty($respSearchPaymentDetails['data']))
					{
							
						if($un_allocated_reservation_no!=0)
						{
							$criteria="((Season_ID_R:equals:".$season_id.") and (Unallocated_Reservation_No_R:equals:".$un_allocated_reservation_no."))";
							
							$arrParams['criteria']=$criteria;
							$arrTrigger=["workflow"];
							$respSearchPaymentDetails=$objZoho->searchRecords("Reservation_Rates",$arrParams,$arrTrigger);
							if(empty($respSearchPaymentDetails['data']))
							{
								
								$check = 0;
							}	
						}

					}
				}
				else if($un_allocated_reservation_no!=0 && $season_id!=0 )
				{

					
						$criteria="((Season_ID_R:equals:".$season_id.") and (Unallocated_Reservation_No_R:equals:".$un_allocated_reservation_no."))";
						$arrParams['criteria']=$criteria;
						$arrTrigger=["workflow"];
						$respSearchPaymentDetails=$objZoho->searchRecords("Reservation_Rates",$arrParams,$arrTrigger);
						if(empty($respSearchPaymentDetails['data']))
						{
							
							$check = 0;
						}
				}
				else
				{
					$check = 0;
				}	
			

				if($check == 1)
				{
					if(count($respSearchPaymentDetails['data']))
					{
					
						$arrPaymentDetails['Name']="RATE_".$auto_id;
						$arrPaymentDetails['Reservation_No_R']=$reservation_no;
						$arrPaymentDetails['Reservation']=$reservationId;   //lookup
						$arrPaymentDetails['Total_Rental_Days']=$days;
						$arrPaymentDetails['Unallocated_Reservation_No_R']=$un_allocated_reservation_no;
						$arrPaymentDetails['Rate']=$rate;
						$arrPaymentDetails['Trip_Rates_2']=$trip_rates;
						$arrPaymentDetails['Rate_Name']=$rate_name;
						$arrPaymentDetails['No_Of_Hours']=$no_hours;
						$arrPaymentDetails['Standard_Rate_3']=$standard_rate;
						$arrPaymentDetails['Season_ID']=$season_id; 
						$arrPaymentDetails['Season_Lookup']=$season;  // lookup
						$arrPaymentDetails['Discount_Name']=$discount_name;
						$arrPaymentDetails['Discount_ID']=$discount_id;
						$arrPaymentDetails['Discount']=$discount_perc;
						$arrPaymentDetails['Discount_Type']=$discount_type;
						$arrPaymentDetails['Is_Extend_Rate']=$no_hours;
						$arrPaymentDetails['Cron_Name']='paymentdetails.php';
						$paymentdeatilid=$respSearchPaymentDetails['data'][0]['id'];
						$arrUpdatePaymentDetails=[];
						$arrUpdatePaymentDetails[]=$arrPaymentDetails;
						$arrTrigger=["workflow"];
						$respUpdatePaymentDetails=$objZoho->updateRecord("Reservation_Rates",$paymentdeatilid,$arrUpdatePaymentDetails,$arrTrigger);
						// print_r($respUpdatePaymentDetails);
						if($respUpdatePaymentDetails)
						{
							if($respUpdatePaymentDetails['data'][0]['code']=="SUCCESS")
							{
								$crmLog.="Updated Payment Details through paymentdetails: ".$paymentdeatilid.", ";
								$updatearray = array('flag'=>0);
								$whereClause="auto_id='$auto_id'"; 
								$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
								$arrPaymentDetails[] ="";
							}
							else
							{
								$crmLog.="Failed to update Payment Details through paymentdetails !=success: ".$paymentdeatilid.", ";	
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to update Payment Details through paymentdetails: ".$paymentdeatilid.", ";	
							$success=false;
						}
					}
					else
					{
						$crmLog.="Error : Unable to Retreive the record With Resevation No  : ".$reservation_no.", Or Unallocated ReservationNo  ".$un_allocated_reservation_no."or Season ID".$season_id ;  
					}
				}
				else
				{
				
					$arrPaymentDetails=[
						'Name'=>"RATE_".$auto_id,
						'Reservation_No_R'=>$reservation_no,
						'Reservation'=>$reservationId,
						'Total_Rental_Days'=>$days,
						'Unallocated_Reservation_No_R'=>$un_allocated_reservation_no,
						'Rate'=>$rate,
						'Trip_Rates_2'=>$trip_rates,
						'Rate_Name'=>$rate_name,
						'No_Of_Hours'=>$no_hours,
						'Standard_Rate_3'=>$standard_rate,
						'Season_ID'=>$season_id,
						'Season_Lookup'=>$season,
						'Discount_Name'=>$discount_name,
						'Discount_ID'=>$discount_id,
						'Discount'=>$discount_perc,
						'Discount_Type'=>$discount_type,
						'Cron_Name'=>'paymentdetails.php',
						'Is_Extend_Rate'=>$no_hours
					];
					$arrInsertPaymentDetails=[];
					$arrInsertPaymentDetails[]=$arrPaymentDetails;
					$arrTrigger=["workflow"];
					$respInsertPaymentDetails=$objZoho->insertRecord("Reservation_Rates",$arrInsertPaymentDetails,$arrTrigger);
					// print_r($respInsertPaymentDetails);
					if($respInsertPaymentDetails)
					{
						if($respInsertPaymentDetails['data'][0]['code']=="SUCCESS")
						{
							$paymentdeatilid=$respInsertPaymentDetails['data'][0]['details']['id'];
							$crmLog.="Payment Details Inserted through paymentdetails: ".$paymentdeatilid.", ";	
							$updatearray = array('flag'=>0);
							$whereClause="auto_id='$auto_id'"; 
							$result = $db->CommonUpdate($table1,$updatearray, $whereClause);

						}
						else
						{
							$crmLog.="Failed to insert Payment Details through paymentdetails !=success ";
							$success=false;
						}
					}
					else
					{
						$crmLog.="Failed to insert Payment Details through paymentdetails ";
						$success=false;
					}
				}


			}  // token if
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