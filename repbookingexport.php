<?php
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);*/
include 'common.php';
$db = new DB();
$cron_start_date_time = date("Y-m-d H:i:s");
$table = "cron_run";
$cron_file = 'repbookingexport.php';
$arrdatacron = array("start_date_time"=>$cron_start_date_time,"cron_file"=>$cron_file);
$cron_id = $db->CommonInsert($table,$arrdatacron);
?>
<?php
	include 'class/cch.class.php';
	include 'zoho/Zoho.php';
    include 'conn.php';
    $success = true;
    $check = 1;
    $checkDeals = 1;
    $checkVehicle = 1;
    $checkCustomer = 1;
    $checkInsurance=1;
	$db = new DB();
	$cch = new cch();
	$table = "repbooking_export";
	$repbooking_export = $cch->GetRepbookingExport(); // rep data get from api 
	
	
	foreach ($repbooking_export as $key => $value) 
	{

		$reservationno = $value['reservationno'];
		$unallocatedreservationno = $value['unallocatedreservationno'];
		//new fields added 
		$phone = $value['phone'];
		$mobile = $value['mobile'];
		$extrarevenue= $value['extrarevenue'];
		$cancellationreason = $value['cancellationreason'];
		$datecancelled = ConvertDateTime($value['datecancelled']);
		$canceloperator = $value['canceloperator'];
		$reservationref = $value['reservationref'];
		$campaigncode = $value['campaigncode'];
		$agentcommissionvalue = $value['agentcommissionvalue'];

		$row_data = json_encode($value);
		if($reservationno!=0)
		{
			$FetchRepbookingExport = $db->FetchRepbookingExportReservation($reservationno); 
			$num_rows = $FetchRepbookingExport->num_rows;

			if($num_rows < 1)  // empty rows 
			{
				if($unallocatedreservationno!=0)
				{
					$FetchRepbookingExport = $db->FetchRepbookingExportUnallocatedReservation($unallocatedreservationno);
				}
			}
		}
		else if($reservationno==0 && $unallocatedreservationno!=0)
		{
				$FetchRepbookingExport = $db->FetchRepbookingExportUnallocatedReservation($unallocatedreservationno);
		}
		else
		{
			//send a mail
		}
		$num_row = $FetchRepbookingExport->num_rows;
		if($num_row > 0)
		{	
			while ($row_vehicle=mysqli_fetch_assoc($FetchRepbookingExport))
			{
				$auto_id = $row_vehicle['auto_id'];
				$updatearray = array(
									'bookingtype' => $value['bookingtype'],
			   						'reservationno' => $value['reservationno'],
									'unallocatedreservationno' => $value['unallocatedreservationno'],
									'pickuplocationid' => $value['pickuplocationid'],
									'rentaldaysinreportperiod' => $value['rentaldaysinreportperiod'],
									'totalrentaldays' => $value['totalrentaldays'],
									'dailyrate' => $value['dailyrate'],
									'insurancedailyrate' => $value['insurancedailyrate'],
									'insuranceid' => $value['insuranceid'],
									'rentalsource' => $value['rentalsource'],
									'pickupdatetime' => $value['pickupdatetime'],
									'dropoffdatetime' => $value['dropoffdatetime'],
									'dropofflocation' => $value['dropofflocation'],
									'dropofflocationid' => $value['dropofflocationid'],
									'pickuplocation' => $value['pickuplocation'],
									'dateentered' => $value['dateentered'],
									'lastdateupdated' => $value['lastdateupdated'],
									'paymenttype' => $value['paymenttype'],
									'bookingoperator' => $value['bookingoperator'],
									'sbrand' => $value['sbrand'],
									'bookedcategorytype' => $value['bookedcategorytype'],
									'vehiclecategory' => $value['vehiclecategory'],
									'carid' => $value['carid'],
									'customerid' => $value['customerid'],
									'bookingagency' => $value['bookingagency'],
									'companyname' => $value['companyname'],
									'customeremail' => $value['customeremail'],
									'customerfirstname' => $value['customerfirstname'],
									'customerlastname' => $value['customerlastname'],
									'dob' => $value['dob'],
									'stampduty' => $value['stampduty'],
									'gst' => $value['gst'],
									'agentcommission' => $value['agentcommission'],
									'agentcollected' => $value['agentcollected'],
									'kmsout' => $value['kmsout'],
									'kmsin' => $value['kmsin'],
									'fuelout' => $value['fuelout'],
									'fuelin' => $value['fuelin'],
									'agencybranch' => $value['agencybranch'],
									'agencybranchcontact' => $value['agencybranchcontact'],
									'agencybranchemail' => $value['agencybranchemail'],
									'referenceno' => $value['referenceno'],
									'closed' => $value['closed'],
									'dateclosed' => $value['dateclosed'],
									'bookingtotal' => $value['bookingtotal'],
									'actualcategorytype' => $value['actualcategorytype'],
									'actualcategory' => $value['actualcategory'],

									'phone' => $value['phone'],
									'mobile' => $value['mobile'],
									'extrarevenue'=> $value['extrarevenue'],
									'cancellationreason' => $value['cancellationreason'],
									'datecancelled' => $datecancelled,
									'canceloperator' => $value['canceloperator'],
									'reservationref' => $value['reservationref'],
									'campaigncode' => $value['campaigncode'],
									'agentcommissionvalue' => $value['agentcommissionvalue'],

									'flag' => 2,
									'row_data' => $row_data
							);
				$whereClause="auto_id='$auto_id'"; 
				$result = $db->CommonUpdate($table,$updatearray, $whereClause);
			}	 
		}
		else
		{
			   $arrdata = array(	
			   						'bookingtype' => $value['bookingtype'],
			   						'reservationno' => $value['reservationno'],
									'unallocatedreservationno' => $value['unallocatedreservationno'],
									'pickuplocationid' => $value['pickuplocationid'],
									'rentaldaysinreportperiod' => $value['rentaldaysinreportperiod'],
									'totalrentaldays' => $value['totalrentaldays'],
									'dailyrate' => $value['dailyrate'],
									'insurancedailyrate' => $value['insurancedailyrate'],
									'insuranceid' => $value['insuranceid'],
									'rentalsource' => $value['rentalsource'],
									'pickupdatetime' => $value['pickupdatetime'],
									'dropoffdatetime' => $value['dropoffdatetime'],
									'dropofflocation' => $value['dropofflocation'],
									'dropofflocationid' => $value['dropofflocationid'],
									'pickuplocation' => $value['pickuplocation'],
									'dateentered' => $value['dateentered'],
									'lastdateupdated' => $value['lastdateupdated'],
									'paymenttype' => $value['paymenttype'],
									'bookingoperator' => $value['bookingoperator'],
									'sbrand' => $value['sbrand'],
									'bookedcategorytype' => $value['bookedcategorytype'],
									'vehiclecategory' => $value['vehiclecategory'],
									'carid' => $value['carid'],
									'customerid' => $value['customerid'],
									'bookingagency' => $value['bookingagency'],
									'companyname' => $value['companyname'],
									'customeremail' => $value['customeremail'],
									'customerfirstname' => $value['customerfirstname'],
									'customerlastname' => $value['customerlastname'],
									'dob' => $value['dob'],
									'stampduty' => $value['stampduty'],
									'gst' => $value['gst'],
									'agentcommission' => $value['agentcommission'],
									'agentcollected' => $value['agentcollected'],
									'kmsout' => $value['kmsout'],
									'kmsin' => $value['kmsin'],
									'fuelout' => $value['fuelout'],
									'fuelin' => $value['fuelin'],
									'agencybranch' => $value['agencybranch'],
									'agencybranchcontact' => $value['agencybranchcontact'],
									'agencybranchemail' => $value['agencybranchemail'],
									'referenceno' => $value['referenceno'],
									'closed' => $value['closed'],
									'dateclosed' => $value['dateclosed'],
									'bookingtotal' => $value['bookingtotal'],
									'actualcategorytype' => $value['actualcategorytype'],
									'actualcategory' => $value['actualcategory'],

									'phone' => $value['phone'],
									'mobile' => $value['mobile'],
									'extrarevenue'=> $value['extrarevenue'],
									'cancellationreason' => $value['cancellationreason'],
									'datecancelled' => $datecancelled,
									'canceloperator' => $value['canceloperator'],
									'reservationref' => $value['reservationref'],
									'campaigncode' => $value['campaigncode'],
									'agentcommissionvalue' => $value['agentcommissionvalue'],
									'flag' => 2,
									'row_data' => $row_data
			   				);
			   $data = $db->CommonInsert($table,$arrdata);	
		}
	}
	// //Zoho Code
	$objZoho = new Zoho();
	// $select_query=mysqli_query($conn,"SELECT * FROM repbooking_export WHERE flag = 2 order by auto_id desc limit 1");
	$select_query=mysqli_query($conn,"SELECT * FROM repbooking_export WHERE  flag=2");
	$reservationsId ="";
	while($row = mysqli_fetch_assoc($select_query))
	{
		echo "data";
		echo "<pre>";
		print_r($row);

	
		//get revenue from raw_data
			$json = json_decode($row['row_data'], true);
			$extra_revenue =  $json['extrarevenue'];

			
		$table1 = "repbooking_export";
		$crmLog = "";
		$auto_id = $row['auto_id'];
		$bookingtype = $row['bookingtype'];
		$reservationno = $row['reservationno'];
		$unallocatedreservationno = $row['unallocatedreservationno'];
		$pickuplocationid = $row['pickuplocationid'];
		$rentaldaysinreportperiod = $row['rentaldaysinreportperiod'];
		$totalrentaldays = $row['totalrentaldays'];
		$dailyrate = $row['dailyrate'];
		$insurancedailyrate = $row['insurancedailyrate'];
		$insuranceid = $row['insuranceid'];
		$rentalsource = $row['rentalsource'];
		$pickupdatetime = ConvertDateTime($row['pickupdatetime']);
		$dropoffdatetime = ConvertDateTime($row['dropoffdatetime']);
		$dropofflocation = $row['dropofflocation'];
		$dropofflocationid = $row['dropofflocationid'];
		$pickuplocation = $row['pickuplocation'];
		$dateentered = ConvertDateTime($row['dateentered']);
		$lastdateupdated = ConvertDateTime($row['lastdateupdated']);
		$paymenttype = $row['paymenttype'];
		$bookingoperator = $row['bookingoperator'];
		$sbrand = $row['sbrand'];
		$bookedcategorytype = $row['bookedcategorytype'];
		$vehiclecategory = $row['vehiclecategory'];
		$carid = $row['carid'];
		$customerid = $row['customerid'];
		$bookingagency = $row['bookingagency'];
		$companyname = $row['companyname'];
		$customeremail = $row['customeremail'];
		$customerfirstname = $row['customerfirstname'];
		$customerlastname = $row['customerlastname'];
		$dob = ConvertDate($row['dob']);
		$stampduty = $row['stampduty'];
		$gst = $row['gst'];
		$agentcommission = $row['agentcommission'];
		$agentcollected = $row['agentcollected'];
		$kmsout = $row['kmsout'];
		$kmsin = $row['kmsin'];
		$fuelout = $row['fuelout'];
		$fuelin = $row['fuelin'];
		$agencybranch = $row['agencybranch'];
		$agencybranchcontact = $row['agencybranchcontact'];
		$agencybranchemail = $row['agencybranchemail'];
		$referenceno = $row['referenceno'];
		$closed = $row['closed'];
		$dateclosed = $row['dateclosed'];
		$bookingtotal = $row['bookingtotal'];
		$actualcategorytype = $row['actualcategorytype'];
		$actualcategory = $row['actualcategory'];
		$pickupdate_deals = ConvertDate($row['pickupdatetime']);
		$dropoffdate_deals = ConvertDate($row['dropoffdatetime']);
        $first_name=ucfirst(strtolower(trim($customerfirstname)));
		$last_name=strtoupper(trim($customerlastname));
		$email_ac = strtolower(trim($customeremail));

		if($sbrand=="Red Dirt 4WD Rentals")
		{
			$business_arm = "RDR";
		}
		else
		{
			$business_arm = "CCH";
		}
		if($bookingagency!="")
		{
			$reservation_type = "Agency";
		}
		else
		{
			$reservation_type = "Direct";
		}
		if($closed==0)
		{
			$closed_final = true;
		}
		else
		{
			$closed_final  = false;
		}
		try
		{
			if($objZoho->checkTokens())
			{
				
				//for vehcile lookup
				if($carid!=0)
				{
					$criteria_vehcile="((Vehicle_ID_R:equals:".$carid."))";
					$arrParams_vehcile['criteria']=$criteria_vehcile;
					$arrTrigger=["workflow"];
					$respSearchVehcile=$objZoho->searchRecords("Vehicles",$arrParams_vehcile,$arrTrigger);
					
			      	if(count($respSearchVehcile['data']))
					{
						echo "vehicle update";
						$vehcile_name = $respSearchVehcile['data'][0]['id'];

						//new fields
						$vehcile_brand = $respSearchVehcile['data'][0]['sbrand'];
						$vehcile_rego = $respSearchVehcile['data'][0]['Registration_Number'];
						$vehcile_fleet_no = $respSearchVehcile['data'][0]['Fleet_Number'];
						$vehcile_grade = $respSearchVehcile['data'][0]['Grade'];
						$vname = $respSearchVehcile['data'][0]['Name'];
					}
					else
					{
						$arrVehicle =[	
									'Vehicle_ID_R'=>$carid,
									'Cron_Name'=>'repbookingexport.php',	
									];

						$arrInsertVehicle=[];
						$arrInsertVehicle[]=$arrVehicle;
						$arrTrigger=["workflow"];
						$respInsertVehicle=$objZoho->insertRecord("Vehicles",$arrInsertVehicle,$arrTrigger);
						echo "<pre>";
						print_r($respInsertVehicle);
						if($respInsertVehicle)
						{
							if($respInsertVehicle['data'][0]['code']=="SUCCESS")
							{
								$vehcile_name=$respInsertVehicle['data'][0]['details']['id'];
								$crmLog.="Insert Vehicle through repbookingexport cron: ".$vehcile_name.", ";    
							}
							else
							{
								$crmLog.="Failed to insert Vehicle through repbookingexport cron != SUCCESS, ";
							}
						}
						else
						{
							$crmLog.="Failed to insert Vehicle through repbookingexport cron, ";
						}
					}
				}

				// // For booking agency
				if($agencybranch!="")
				{
					$criteria_booking_agency="((Name:equals:".$agencybranch."))";
					// print_r($criteria_booking_agency);
					$arrParams_booking_agency['criteria']=$criteria_booking_agency;
					$arrTrigger=["workflow"];
					$respSearchBookingAgency=$objZoho->searchRecords("Agency_Branch",$arrParams_booking_agency,$arrTrigger);
				
			      	if(count($respSearchBookingAgency['data']))
					{
						$agency_booking_name = $respSearchBookingAgency['data'][0]['id'];
					}
					else
					{
						$arrBranch=[
											
									'Name'=>$agencybranch,
									'Cron_Name'=>'repbookingexport.php',
									'Email' => $agencybranchemail,
									];
						$arrInsertBranch=[];
						$arrInsertBranch[]=$arrBranch;
						$arrTrigger=["workflow"];
						$respInsertBranch=$objZoho->insertRecord("Agency_Branch",$arrInsertBranch,$arrTrigger);
							echo "<pre>";
						print_r($respInsertBranch);
						if($respInsertBranch)
						{
							if($respInsertBranch['data'][0]['code']=="SUCCESS")
							{
								$agency_booking_name=$respInsertBranch['data'][0]['details']['id'];
								$crmLog.="Insert Agency Branch through repbookingexport cron: ".$agency_booking_name.", ";    
							}
							else
							{
								$crmLog.="Failed to insert Agency Branch through repbookingexport cron != SUCCESS, ";
							}
						}
						else
						{
							$crmLog.="Failed to insert Vehicle through repbookingexport cron, ";
						}
					}
				}

				


				// //for insurance
				if($insuranceid!=0)
				{
					$criteria_insurance="((Insurance_ID_R:equals:".$insuranceid."))";
					$arrParams_insurance['criteria']=$criteria_insurance;
					$arrTrigger=["workflow"];
					$respSearchInsurance=$objZoho->searchRecords("Insurance_items",$arrParams_insurance,$arrTrigger);

			      	if(count($respSearchInsurance['data']))
					{
						
						$insurance_name = $respSearchInsurance['data'][0]['id'];
					}
					else
					{
						$arrInsurance=[
											
										'Insurance_ID_R'=>$insuranceid,
										'Cron_Name'=>'repbookingexport.php',
										
									];
						$arrInsertInsurance=[];
						$arrInsertInsurance[]=$arrInsurance;
						$arrTrigger=["workflow"];
						$respInsertInsurance=$objZoho->insertRecord("Insurance_items",$arrInsertInsurance,$arrTrigger);
						if($respInsertInsurance)
						{
							if($respInsertInsurance['data'][0]['code']=="SUCCESS")
							{
								$insurance_name=$respInsertInsurance['data'][0]['details']['id'];
								$crmLog.="Insert Insurance items through repbookingexport cron: ".$insurance_name.", ";    
							}
							else
							{
								$crmLog.="Failed to insert Insurance items != Success, ";
							}
						}
						else
						{
							$crmLog.="Failed to insert Vehicle through repbookingexport cron, ";
						}
					}
				}


				// 	//for customer
			 	$criteria="((Customer_ID_R:equals:".$customerid."))";
				$arrParams['criteria']=$criteria;
				$arrTrigger=["workflow"];
				$respSearchCustomer=$objZoho->searchRecords("Contacts",$arrParams,$arrTrigger);
				if(empty($respSearchCustomer['data']))
				{
				 	if($email_ac !="")
					{
						$criteria ="((Email:equals:".$email_ac.")and (First_Name:equals:".$first_name."))";
						$arrParams['criteria'] = $criteria;
						$arrTrigger=['workflow'];
						$respSearchCustomer=$objZoho->searchRecords("Contacts",$arrParams,$arrTrigger);
						if(empty($respSearchCustomer['data'])){
							$checkCustomer = 0;
						}
					}
					
				}
				else
				{
					$checkCustomer = 0;
				}

				if($checkCustomer==1)
				{
						$customer_name = $respSearchCustomer['data'][0]['id'];

						//new fields
						$customer_email = $respSearchCustomer['data'][0]['Email']; 
						$customer_first_name = $respSearchCustomer['data'][0]['First_Name']; 
						$customer_last_name = $respSearchCustomer['data'][0]['Last_Name'];
						$customer_full_name = $respSearchCustomer['data'][0]['Full_Name'];  
						$customer_dob = $respSearchCustomer['data'][0]['Date_of_Birth']; 
						$customer_mobile = $respSearchCustomer['data'][0]['Mobile']; 
						$customer_phone = $respSearchCustomer['data'][0]['Phone'];  

				}
				else
				{
					$arrCustomer=[
									'First_Name'=>$first_name,
									'Last_Name'=>$last_name,
									'Email'=>$email_ac,
									'Customer_ID_R'=>$customerid,
									'Cron_Name'=>'repbookingexport.php',
									'Date_of_Birth'=>$dob
								];
					$arrInsertCustomer=[];
					$arrInsertCustomer[]=$arrCustomer;
					$arrTrigger=["workflow"];
					$respInsertCustomer=$objZoho->insertRecord("Contacts",$arrInsertCustomer,$arrTrigger);
					
					if($respInsertCustomer)
					{
						if($respInsertCustomer['data'][0]['code']=="SUCCESS")
						{
							$customer_name=$respInsertCustomer['data'][0]['details']['id'];
							$crmLog.="Insert Customer through repbookingexport cron: ".$customer_name.", "; 
							$updatearray = array('flag'=>0);
								$whereClause="ac_id='$customerid'"; 
								$result = $db->CommonUpdate("customer",$updatearray, $whereClause);
								$arrCustomer[] ="";   
						}
						else
						{
							$crmLog.="Failed to insert Customer through repbookingexport cron != success, ";
							$success=false;
						}
					}
					else
					{
						$crmLog.="Failed to insert Customer through repbookingexport cron, ";
					}
				}
				



				if($reservationno!=0)
				{
					
					$criteria_reservations="((Reservation_No_R:equals:".$reservationno."))";
					$arrParams_reservations['criteria']=$criteria_reservations;
					$arrTrigger=["workflow"];
					$respSearchReservations=$objZoho->searchRecords("Reservations",$arrParams_reservations,$arrTrigger);
					if(empty($respSearchReservations['data']))
					{
						
						if($unallocatedreservationno!=0)
						{
							
							$criteria_reservations="((Unallocated_Reservation_No_R:equals:".$unallocatedreservationno."))";
							$arrParams_reservations['criteria']=$criteria_reservations;
							$arrTrigger=["workflow"];
							$respSearchReservations=$objZoho->searchRecords("Reservations",$arrParams_reservations,$arrTrigger);
								if(empty($respSearchReservations['data']))
								{
									
									$check = 0;
								}
						}
						
					}
				}
				else if($reservationno==0 && $unallocatedreservationno!=0)
				{
					
						$criteria_reservations="((Unallocated_Reservation_No_R:equals:".$unallocatedreservationno."))";
					
						$arrParams_reservations['criteria']=$criteria_reservations;
						$arrTrigger=["workflow"];
						$respSearchReservations=$objZoho->searchRecords("Reservations",$arrParams_reservations,$arrTrigger);
						if(empty($respSearchReservations['data']))
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

					if(count($respSearchReservations['data']))
					{
						

						$daily_rate = $bookingtotal / $totalrentaldays;
						$rental_value = $totalrentaldays * $dailyrate;
						$lro = $insurancedailyrate * $totalrentaldays;
						$commision = $agentcommission;
						$revenue_from_rawdata = $extra_revenue;  // taken from row data check for it
						$total_kms_travelled = $kmsin - $kmsout;
						$avg_km_traveled_per_day = $total_kms_travelled / $totalrentaldays;

						$arrReservations['Reservation_No_R']=$reservationno;
						$arrReservations['Name']="RESERVATION_".$auto_id;
						$arrReservations['Reservation_Type']=$reservation_type;
						$arrReservations['Unallocated_Reservation_No_R']=$unallocatedreservationno;
						$arrReservations['Booking_Type_R']=$bookingtype;
						$arrReservations['Kms_Out']=$kmsout;
						$arrReservations['Kms_In']=$kmsin;
						$arrReservations['Fuel_Out']=$fuelout;
						$arrReservations['Fuel_In']=$fuelin;
						$arrReservations['Date_Time_Entered']=$dateentered;
						$arrReservations['Date_Time_Last_Updated']=$lastdateupdated;
						$arrReservations['Is_Closed']=$closed_final;
						// $arrReservations['Payment_Type']=$paymenttype;
						$arrReservations['Booking_Operator']=$bookingoperator;
						$arrReservations['Brand']=$sbrand;
						$arrReservations['Business_Arm']=$business_arm;  
						$arrReservations['Pick_Up_Location2']=$pickuplocationid;
						$arrReservations['Pick_Up_Date_Time']=$pickupdatetime;
						$arrReservations['Pick_Up_Location_Code']=$pickuplocation;
						$arrReservations['Drop_Off_Location2']=$dropofflocationid;
						$arrReservations['Drop_Off_Date_Time']=$dropoffdatetime;
						$arrReservations['Drop_Off_Location_Code']=$dropofflocation;
						$arrReservations['Customer_ID']=$customerid;
						$arrReservations['Customer']=$customer_name; //lookup 
						$arrReservations['Vehicles']=$vehcile_name;
						$arrReservations['Actual_Vehicle_Category']=$actualcategory;
						$arrReservations['Vehicle_ID']=$carid; 
						$arrReservations['Actual_Vehicle_Category_Type_A']=$actualcategorytype;
						$arrReservations['Booked_Vehicle_Category']=$vehiclecategory;
						$arrReservations['Booked_Vehicle_Category_Type_A']=$bookedcategorytype;
					 //    $arrReservations['Booking_Agency_Branch']=$agency_booking_name;  
						$arrReservations['Commission']=$agentcommission;
						// $arrReservations['Booking_Agency']=$bookingagency;
						$arrReservations['Booking_Total']=$bookingtotal;
						$arrReservations['Total_Rental_Days']=$totalrentaldays;
						$arrReservations['Stamp_Duty']=$stampduty;
						$arrReservations['Daily_Rate']=$dailyrate;
						$arrReservations['GST']=$gst;
						$arrReservations['Insurance_ID']=$insuranceid;
						$arrReservations['Insurance_Daily_Rate']=$insurancedailyrate;
						$arrReservations['Insurance_Item']=$insurance_name;  // lookup
						$arrReservations['Rental_Source']=$rentalsource;
						$arrReservations['No_Of_Days']=$totalrentaldays;
						$arrReservations['Cron_Name']='repbookingexport.php';

						//new fields
					 	$arrReservations['First_Name']=$customer_first_name ;
						$arrReservations['Last_Name']=	$customer_last_name ;
						$arrReservations['Full_Name']=	$customer_full_name ;
						$arrReservations['Date_Of_Birth']=	$customer_dob ;
						$arrReservations['Mobile_Number']=	$customer_mobile ;
						$arrReservations['Phone_Number']=	$customer_phone ;


						$arrReservations['Vehicle_Brand']=	$vehcile_brand ;
						$arrReservations['Vehicle_Rego']=	$vehcile_rego ;
						$arrReservations['Fleet_Number']=	$vehcile_fleet_no ;
						$arrReservations['Vehicle_Grade']=	$vehcile_grade ;
						$arrReservations['Vehicle_Name']=	$vname ;


						$arrReservations['Booking_Daily_Rate']=$daily_rate;
						$arrReservations['Rental_Value']=$rental_value;
						$arrReservations['LRO']=$lro;
						$arrReservations['Agent_Commision']=$commision;
						$arrReservations['Extra_Revenue']=$revenue_from_rawdata;
						// $arrReservations['Total_Kms_Travelled']=$total_kms_travelled;
						// $arrReservations['Average_Kms_Travelled_Per_Day']=$avg_km_traveled_per_day;


						$reservationsId=$respSearchReservations['data'][0]['id'];
						$arrUpdateReservations=[];
						$arrUpdateReservations[]=$arrReservations;
						$arrTrigger=["workflow"];
						$respUpdateReservations=$objZoho->updateRecord("Reservations",$reservationsId,$arrUpdateReservations,$arrTrigger);
							echo "<pre>";
						print_r($respUpdateReservations);		
						if($respUpdateReservations)
						{
							if($respUpdateReservations['data'][0]['code']=="SUCCESS")
							{

								$crmLog.="Updated Reservations through repbookingexport cron: ".$reservationsId.", ";
								$updatearray = array('flag'=>0);
								$whereClause="auto_id='$auto_id'"; 
								$result = $db->CommonUpdate($table1,$updatearray, $whereClause);

							}
							else
							{
								$crmLog.="Failed to update  Reservations through repbookingexport cron: != success ".$reservationsId.", ";    
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to update Reservations through repbookingexport cron: ".$reservationsId.", ";    
							$success=false;
						}
					}
					else
					{
						$crmLog.="no record found for reservation through repbookingexport cron" ;
					}	
				}
				else
				{

					$daily_rate = $bookingtotal / $totalrentaldays;
					$rental_value = $totalrentaldays * $dailyrate;
					$lro = $insurancedailyrate * $totalrentaldays;
					$commision = $agentcommission;
					$revenue_from_rawdata = $extra_revenue;  // taken from row data check for it
					$total_kms_travelled = $kmsin - $kmsout;
					$avg_km_traveled_per_day = $total_kms_travelled / $totalrentaldays;


					$arrReservations=[
						'Reservation_No_R'=>$reservationno,
						'Name'=>"RESERVATION_".$auto_id,
						'Reservation_Type'=>$reservation_type,
						'Unallocated_Reservation_No_R'=>$unallocatedreservationno,
						'Booking_Type_R'=>$bookingtype,
						'Kms_Out'=>$kmsout,
						'Kms_In'=>$kmsin,
						'Fuel_Out'=>$fuelout,
						'Fuel_In'=>$fuelin,
						'Date_Time_Entered'=>$dateentered,
						'Date_Time_Last_Updated'=>$lastdateupdated,
						'Is_Closed'=>$closed_final,
						'Payment_Type'=>$paymenttype,
						'Booking_Operator'=>$bookingoperator,
						'Brand'=>$sbrand,
						'Business_Arm'=>$business_arm,
						'Reference_No'=>$referenceno,
						'Pick_Up_Location2'=>$pickuplocationid,
						'Pick_Up_Date_Time'=>$pickupdatetime,
						'Pick_Up_Location_Code'=>$pickuplocation,
						'Drop_Off_Location2'=>$dropofflocationid,
						'Drop_Off_Date_Time'=>$dropoffdatetime,
						'Drop_Off_Location_Code'=>$dropofflocation,
						'Customer_ID'=>$customerid,
						'Customer'=>$customer_name,
						'Vehicles'=>$vehcile_name,
						'Actual_Vehicle_Category'=>$actualcategory,
						'Vehicle_ID'=>$carid,
						'Actual_Vehicle_Category_Type_A'=>$actualcategorytype,
						'Booked_Vehicle_Category'=>$vehiclecategory,
						'Booked_Vehicle_Category_Type_A'=>$bookedcategorytype,
						// 'Booking_Agency_Branch'=>$agency_booking_name,
						'Commission'=>$agentcommission,
						// 'Booking_Agency'=>$bookingagency,
						'Booking_Total'=>$bookingtotal,
						'Total_Rental_Days'=>$totalrentaldays,
						'Stamp_Duty'=>$stampduty,
						'Daily_Rate'=>$dailyrate,
						'GST'=>$gst,
						'Insurance_ID'=>$insuranceid,
						'Insurance_Daily_Rate'=>$insurancedailyrate,
						'Insurance_Item'=>$insurance_name,
						'Rental_Source'=>$rentalsource,
						'Cron_Name'=>'repbookingexport.php',
						'No_Of_Days'=>$totalrentaldays,


						//new fields
					 	'First_Name'=> $customer_first_name,
						'Last_Name'=>	$customer_last_name,
						'Full_Name'=>	$customer_full_name,
						'Date_Of_Birth'=>	$customer_dob,
						'Mobile_Number'=>	$customer_mobile,
						'Phone_Number'=>	$customer_phone,

						'Vehicle_Brand'=>	$vehcile_brand,
						'Vehicle_Rego'=>	$vehcile_rego ,
						'Fleet_Number'=>	$vehcile_fleet_no,
						'Vehicle_Grade'=>	$vehcile_grade,
						'Vehicle_Name'=>	$vname,

						'Booking_Daily_Rate'=>$daily_rate,
						'Rental_Value'=>$rental_value,
						'LRO'=>$lro,
						'Agent_Commision'=>$commision,
						'Extra_Revenue'=>$revenue_from_rawdata,
						// 'Total_Kms_Travelled'=>$total_kms_travelled,
						// 'Average_Kms_Travelled_Per_Day'=>$avg_km_traveled_per_day,
					];


					$arrInsertReservations=[];
					$arrInsertReservations[]=$arrReservations;
					$arrTrigger=["workflow"];
					$respInsertReservations=$objZoho->insertRecord("Reservations",$arrInsertReservations,$arrTrigger);
					
					if($respInsertReservations)
					{
						if($respInsertReservations['data'][0]['code']=="SUCCESS")
						{
							$reservationssId=$respInsertReservations['data'][0]['details']['id'];
							$crmLog.="Inserted Reservations through repbookingexport cron: ".$reservationssId.", ";    
							$updatearray = array('flag'=>0);
							$whereClause="auto_id='$auto_id'"; 
							$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
							// $vehcile_name = "";
							// $agency_booking_name = "";
							// $insurance_name = "";
							// $customer_name = "";
							// $reservationsId="";
						}
						else
						{
							$crmLog.="Failed to insert Reservations through repbookingexport cron !=success, ";
							$success=false;
						}
					}
					else
					{
						$crmLog.="Failed to insert Reservations through repbookingexport cron, ";
						$success=false;
					}
				}

				

					

				// //for vechile item booked

			if($carid!=0)
			{
				if($reservationno!=0)
				{
					$criteria="((Vehicle_ID_R:equals:".$carid.") and (Reservation_No:equals:".$reservationno."))";
					$arrParams['criteria']=$criteria;
					$respSearchVecBooked=$objZoho->searchRecords("Vehicles",$arrParams);
					if(empty($respSearchVecBooked['data']))
					{		
						if($unallocatedreservationno!=0)
						{
							// vehicle no and unallocated  no 
							$criteria="((Vehicle_ID_R:equals:".$carid.") and (Unallocated_Reservation_No_R:equals:".$unallocatedreservationno."))";
							$arrParams['criteria']=$criteria;
							$respSearchVecBooked=$objZoho->searchRecords("Vehicles",$arrParams);
							if(empty($respSearchVecBooked['data']))
							{
								$checkVehicle = 0;
							}
						
						}

					}
				}
				else if($unallocatedreservationno!=0 && $carid!=0)
				{
					// vehicle no and unallocated  no 
					$criteria="((Vehicle_ID_R:equals:".$carid.") and (Unallocated_Reservation_No_R:equals:".$unallocatedreservationno."))";
					$arrParams['criteria']=$criteria;
					$respSearchVecBooked=$objZoho->searchRecords("Vehicles",$arrParams);
						if(empty($respSearchVecBooked['data'])){
							$checkVehicle = 0;
						}
				}
				else
				{
						$checkVehicle = 0;
				}	

				if($checkVehicle == 1)
				{
					if(count($respSearchVecBooked['data']))
					{
						$arrVehileBooked['Vehicle_ID_R']=$carid;
						$arrVehileBooked['Unallocated_Reservation_No_R']=$unallocatedreservationno;
						$arrVehileBooked['Reservation_No']=$reservationno;
						$arrVehileBooked['Vehicles']=$vehcile_name; //lookup
						$arrVehileBooked['Reservations']=$reservationsId;
						$arrVehileBooked['Cron_Name']='repbookingexport.php';
						$vechbookedid=$respSearchVecBooked['data'][0]['id'];
						$arrUpdateVechBooked=[];
					    $arrUpdateVechBooked[]=$arrVehileBooked;
						$arrTrigger=["workflow"];
						$respUpdateVechBooked=$objZoho->updateRecord("Vehicles_Booked2",$vechbookedid,$arrUpdateVechBooked,$arrTrigger);
						
						if($respUpdateVechBooked)
						{
							if($respUpdateVechBooked['data'][0]['code']=="SUCCESS")
							{
								$crmLog.="Updated Vechicle Booked through repbookingexport cron: ".$vechbookedid.", ";
								$updatearray = array('flag'=>0);
								$whereClause="auto_id='$auto_id'"; 
								$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
								
							}
							else
							{
								$crmLog.="Failed to update Vechicle Booked through repbookingexport cron != success : ".$vechbookedid.", ";	
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to update Vechicle Booked through repbookingexport cron: ".$vechbookedid.", ";	
							$success=false;
						}
					}
					else
					{
						$crmLog.="no record found for Vechicle Booked  through repbookingexport cron ".$reservationsId ;
					}
					
				}

				else
				{
					$arrVehileBooked =
					[
						'Vehicle_ID_R'=>$carid,
						'Unallocated_Reservation_No_R'=>$unallocatedreservationno,
						'Reservation_No'=>$reservationno,
						'Reservations'=>$reservationsId,
						'Cron_Name'=>'repbookingexport.php',
						'Vehicles'=>$vehcile_name
					];
					$arrInsertVechBooked=[];
					$arrInsertVechBooked[]=$arrVehileBooked;
					$arrTrigger=["workflow"];
					$respInsertVechBooked=$objZoho->insertRecord("Vehicles_Booked2",$arrInsertVechBooked,$arrTrigger);
					// print_r($respInsertVechBooked);
					if($respInsertVechBooked)
					{
						if($respInsertVechBooked['data'][0]['code']=="SUCCESS")
						{
							$vechileid=$respInsertVechBooked['data'][0]['details']['id'];
							$crmLog.="Vechicle Booked through repbookingexport cron: ".$vechileid.", ";	
							$updatearray = array('flag'=>0);
							$whereClause="auto_id='$auto_id'"; 
							$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
						
						}
						else
						{
							$crmLog.="Failed to insert Vechicle Booked through repbookingexport cron != success, ";
							$success=false;
						}
					}
					else
					{
						$crmLog.="Failed to insert Vechicle Booked through repbookingexport cron, ";
						$success=false;
					}
			
				}
			}
				



				// //for insurance item booked
			if($insuranceid!=0)
			{
				if($reservationno!=0)
				{
					// echo "yes";
					//insurance id and reservation no 
					$criteria="((Insurance_ID_R:equals:".$insuranceid.")  and (Reservation_No:equals:".$reservationno."))";
					$arrParams['criteria']=$criteria;
					$respSearchInsBooked=$objZoho->searchRecords("Insurance_Items_Booked",$arrParams);
					if(empty($respSearchInsBooked['data']))
					{	
						if($unallocatedreservationno!=0)
						{
							$criteria="((Insurance_ID_R:equals:".$insuranceid.")  and (Unallocated_Reservation_No_R:equals:".$unallocatedreservationno."))";
							$arrParams['criteria']=$criteria;
							$respSearchInsBooked=$objZoho->searchRecords("Insurance_Items_Booked",$arrParams);
							if(empty($respSearchInsBooked['data']))
							{
								$checkInsurance = 0;
							}
						
						}
					}
				}
				else if($unallocatedreservationno!=0 && $insuranceid!=0)
				{
					// echo "no";
						//for insurance item booked
					//insurance id and unallocated no 
					$criteria="((Insurance_ID_R:equals:".$insuranceid.")  and (Unallocated_Reservation_No_R:equals:".$unallocatedreservationno."))";
					$arrParams['criteria']=$criteria;
					$respSearchInsBooked=$objZoho->searchRecords("Insurance_Items_Booked",$arrParams);
						if(empty($respSearchInsBooked['data'])){
							$checkInsurance = 0;
						}
				}
				else
				{
					
					$checkInsurance = 0;
				}	

				if($checkInsurance == 1)
				{
					if(count($respSearchInsBooked['data']))
					{
						
						
						$arrIns['Insurance_ID_R']=$insuranceid;
						$arrIns['Unallocated_Reservation_No_R']=$unallocatedreservationno;
						$arrIns['Reservation_No']=$reservationno;
						$arrIns['Reservation_ID']=$reservationsId;  //lookup
						$arrIns['Insurance_Item']=$insurance_name;  //lookup
						$arrIns['Insurance_Daily_Rate']=$insurancedailyrate;
						$arrIns['No_of_Days']=$totalrentaldays;
						$arrIns['Cron_Name']='repbookingexport.php';
						$insurance_id=$respSearchInsBooked['data'][0]['id'];
						$arrUpdateIns=[];
					    $arrUpdateIns[]=$arrIns;
						$arrTrigger=["workflow"];
						$respUpdateIns=$objZoho->updateRecord("Insurance_Items_Booked",$insurance_id,$arrUpdateIns,$arrTrigger);
						
						if($respUpdateIns)
						{
							if($respUpdateIns['data'][0]['code']=="SUCCESS")
							{
								
								$crmLog.="Updated Insurance Booked through repbookingexport cron : ".$insurance_id.", ";
								$updatearray = array('flag'=>0);
								$whereClause="auto_id='$auto_id'"; 
								$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
							}
							else
							{
								$crmLog.="Failed to update Insurance Booked through repbookingexport cron != success: ".$insurance_id.", ";	
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to update Insurance Booked through repbookingexport cron: ".$insurance_id.", ";	
							$success=false;
						}
				
					}
					else
					{
						$crmLog.="no record found for Insurance Booked  through repbookingexport cron ".$reservationsId ;
					}		
					
				}
				else
				{
					
					$arrIns=[
						'Insurance_ID_R'=>$insuranceid,
						'Unallocated_Reservation_No_R'=>$unallocatedreservationno,
						'Reservation_No'=>$reservationno,
						'Reservation_ID'=>$reservationsId,
						'Insurance_Daily_Rate'=>$insurancedailyrate,
						'No_of_Days'=>$totalrentaldays,
						'Cron_Name'=>'repbookingexport.php',
						'Insurance_Item'=>$insurance_name, //lookup
					];
					$arrInsertInsu=[];
					$arrInsertInsu[]=$arrIns;
					$arrTrigger=["workflow"];
					$respInsertIns=$objZoho->insertRecord("Insurance_Items_Booked",$arrInsertInsu,$arrTrigger);
					// echo "<br>";
					// print_r($respInsertIns);
					if($respInsertIns)
					{
						if($respInsertIns['data'][0]['code']=="SUCCESS")
						{
							$insid=$respInsertIns['data'][0]['details']['id'];
							$crmLog.=" Insurance Item Booked through repbookingexport cron: ".$insid.", ";
							$updatearray = array('flag'=>0);
								$whereClause="auto_id='$auto_id'"; 
								$result = $db->CommonUpdate($table1,$updatearray, $whereClause);	
						}
						else
						{
							$crmLog.="Failed to insert Insurance Item Booked through repbookingexport cron !=success, ";
							$success=false;
						}
					}
					else
					{
						$crmLog.="Failed to insert Insurance Item Booked through repbookingexport cron, ";
						$success=false;
					}
					
				}
			}
				


				// 	//for prospect module - 21-10-2020 (not sure about this module)
				// if($bookingtype=="Unallocated Quotation" || $bookingtype=="Unallocated Reservation")
				// {
				// 	//echo"if";

				// 	//for pickupfullname location
				// 	$criteria_pickup="((LocationID:equals:".$pickuplocationid."))";
				// 	$arrParams_pickup['criteria']=$criteria_pickup;
				// 	$arrTrigger=["workflow"];
				// 	$respSearchPickUpLocation=$objZoho->searchRecords("Locations",$arrParams_pickup,$arrTrigger);
				// 	if(count($respSearchPickUpLocation['data']))
				// 	{

				// 			$pickup_location_name=$respSearchPickUpLocation['data'][0]['Name'];
				// 	}
				// 	//for dropofffullname location
				// 	$criteria_dropoff="((LocationID:equals:".$dropofflocationid."))";
				// 	$arrParams_dropoff['criteria']=$criteria_dropoff;
				// 	$arrTrigger=["workflow"];
				// 	$respSearchDropOffLocation=$objZoho->searchRecords("Locations",$arrParams_dropoff,$arrTrigger);
				// 	if(count($respSearchDropOffLocation['data']))
				// 	{

				// 			$dropoff_location_name=$respSearchDropOffLocation['data'][0]['Name'];
				// 	}

				// }



				// // For Deals
				if($unallocatedreservationno!=0)
				{
					
					$criteria="((Unallocated_Reservation_No_R:equals:".$unallocatedreservationno."))";
					$arrParams['criteria']=$criteria;
					$arrTrigger=["workflow"];
					$respSearchDeals=$objZoho->searchRecords("Deals",$arrParams,$arrTrigger);
					if(empty($respSearchDeals['data']))
					{
						if($reservationno!=0)
						{
							$criteria="((Reservation_No_R:equals:".$reservationno."))";
							$arrParams['criteria']=$criteria_reservations;
							$arrTrigger=["workflow"];
							$respSearchDeals=$objZoho->searchRecords("Deals",$arrParams,$arrTrigger);
							if(empty($respSearchDeals['data']))
							{
								$checkDeals = 0;
							}
						
						}
					}
				}
				else if($reservationno!=0 && $unallocatedreservationno==0)
				{
						$criteria="((Reservation_No_R:equals:".$reservationno."))";
						$arrParams['criteria']=$criteria_reservations;
						$arrTrigger=["workflow"];
						$respSearchDeals=$objZoho->searchRecords("Deals",$arrParams,$arrTrigger);
						if(empty($respSearchDeals['data'])){
							$checkDeals = 0;
						}
				}
				else
				{
					$checkDeals = 0;
						
				}
				if($checkDeals==1)
				{
					if(count($respSearchDeals['data']))
					{
						$arrDeals['Deal_Name']=$first_name.' '.$last_name.' '."Deal";
						$arrDeals['Stage']="QUOTED";
						$arrDeals['Agency']=$bookingagency;
						$arrDeals['Amount']=$bookingtotal;
						$arrDeals['Business_Arm']=$business_arm;
						$arrDeals['Form']="RCM";
						$arrDeals['Pick_Up_Location']=$pickup_location_name;
						$arrDeals['Drop_Off_Location']=$dropoff_location_name;
						$arrDeals['Pick_Up_Date_Time']=$pickupdatetime;
						$arrDeals['Drop_Off_Date_Time']=$dropoffdatetime;
						$arrDeals['Pick_Up_Date']=$pickupdate_deals;
						$arrDeals['Drop_Off_Date']=$dropoffdate_deals;
						$arrDeals['Hire_Requirements']=$vehiclecategory;
						$arrDeals['Lead_Source']=$rentalsource;
						$arrDeals['Quote_or_Unallocated_Booking']=$reservationsId; //lookup
						$arrDeals['Reservation_No_R']=$reservationno; 
						$arrDeals['Unallocated_Reservation_No_R']=$unallocatedreservationno;
						$arrDeals['Booking_Type_R']=$bookingtype;
						$arrDeals['No_Of_Days']=$totalrentaldays;
						$arrDeals['Contact_Name']=$customer_name; //lookup
						$arrDeals['Cron_Name']='repbookingexport.php';
						$deal_Id=$respSearchDeals['data'][0]['id'];
						$arrUpdateDeal=[];
						$arrUpdateDeal[]=$arrDeals;
						$arrTrigger=["workflow"];
						$respUpdateDeal=$objZoho->updateRecord("Deals",$deal_Id,$arrUpdateDeal,$arrTrigger);
								
						if($respUpdateDeal)
						{
							if($respUpdateDeal['data'][0]['code']=="SUCCESS")
							{
								
								$crmLog.="Updated Deal through repbookingexport cron: ".$deal_Id.", ";
								$updatearray = array('flag'=>0);
								$whereClause="auto_id='$auto_id'"; 
								$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
							}
							else
							{
								$crmLog.="Failed to update  Deals through repbookingexport cron !=success : ".$deal_Id.", ";    
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to update Deals through repbookingexport cron: ".$deal_Id.", ";    
							$success=false;
						}
					}
					else
					{
						$crmLog.="no record found for  Deals  through repbookingexport cron ".$reservationsId ;
					}	
					
				}
				else
				{
					$arrDeals=[
						'Deal_Name'=>$first_name.' '.$last_name.' '."Deal",
						'Stage'=>"QUOTED",
						'Agency'=>$bookingagency,
						'Amount'=>$bookingtotal,
						'Business_Arm'=>$business_arm,
						'Form'=>"RCM",
						'Pick_Up_Location'=>$pickup_location_name,
						'Drop_Off_Location'=>$dropoff_location_name,
						'Pick_Up_Date_Time'=>$pickupdatetime,
						'Drop_Off_Date_Time'=>$dropoffdatetime,
						'Pick_Up_Date'=>$pickupdate_deals,
						'Drop_Off_Date'=>$dropoffdate_deals,
						'No_Of_Days'=>$totalrentaldays,
						'Hire_Requirements'=>$vehiclecategory,
						'Lead_Source'=>$rentalsource,
						'Quote_or_Unallocated_Booking'=>$reservationsId,
						'Reservation_No_R'=>$reservationno,
						'Booking_Type_R'=>$bookingtype,
						'Unallocated_Reservation_No_R'=>$unallocatedreservationno,
						'Contact_Name'=>$customer_name,
						'Cron_Name'=>'repbookingexport.php',
					];
					$arrInsertDeals=[];
					$arrInsertDeals[]=$arrDeals;
					$arrTrigger=["workflow"];
					$respInsertDeals=$objZoho->insertRecord("Deals",$arrInsertDeals,$arrTrigger);
					if($respInsertDeals)
					{
						if($respInsertDeals['data'][0]['code']=="SUCCESS")
						{
							$dealId=$respInsertDeals['data'][0]['details']['id'];
							$crmLog.="Deals Interaction through repbookingexport cron: ".$dealId.", ";  
							$updatearray = array('flag'=>0);
								$whereClause="auto_id='$auto_id'"; 
								$result = $db->CommonUpdate($table1,$updatearray, $whereClause); 
						}
						else
						{
							$crmLog.="Failed to insert Deals through repbookingexport cron !=success, ";
							$success=false;
						}
					}
					else
						{
							$crmLog.="Failed to insert Deals through repbookingexport cron, ";
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

	}// end of while loop



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