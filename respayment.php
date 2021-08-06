<?php
ini_set('max_execution_time', 10800); // 3 Hour
ini_set("memory_limit", "-1");
set_time_limit(0);
include 'common.php';
$db = new DB();
$cron_start_date_time = date("Y-m-d H:i:s");
$table = "cron_run";
$cron_file = 'respayment.php';
$arrdatacron = array("start_date_time"=>$cron_start_date_time,"cron_file"=>$cron_file);
$cron_id = $db->CommonInsert($table,$arrdatacron);
?>
<?php
include 'class/cch.class.php';
include 'zoho/Zoho.php';
include 'conn.php';
$success = true;
 $check = 1;
 $checkReservation=1;
$db = new DB();
$cch = new cch();
$table = "respayment";
	$ResPayment = $cch->GetResPayment(); // payment data get from api
	foreach ($ResPayment as $key => $value) {
		$row_data = json_encode($value);
		$payment_id = $value['paymentid'];
		$reservation_no = $value['reservationno'];
		// $FetchResPayment = $db->FetchResPayment($reservation_no); // location data get from database
		$FetchResPayment = $db->FetchResWithPaymentId($payment_id);

		$num_row = $FetchResPayment->num_rows;

		
		if($num_row > 0)
		{	
			while ($row_location=mysqli_fetch_assoc($FetchResPayment))
			{
				// $reservation_no = $value['reservationno'];
				// $payment_id = $value['paymentid'];
				$updatearray = array(
										'reservation_no' => $value['reservationno'],
										'un_allocated_reservation_no'=>$value['unallocatedreservationno'],
										'billing_location_id'=>$value['billinglocationid'],
										'payment_type' =>$value['paymenttype'],
										'paid'=>$value['paid'],
										'payment_date' => $value['paymentdate'],	
										'login_id'=>$value['loginid'],
										'cash_receipt_name'=>$value['cashreceiptname'],
										'other_extra_desc'=>$value['otherextradesc'],
										'dmy_created' =>$value['dmycreated'],
										'inv_payment_id'=>$value['invpaymentid'],
										'login_ip' =>$value['loginip'],
										'payment_token_id'=>$value['paymenttokenid'],
										'payment_id' => $value['paymentid'],
										'flag' => 2,
										'row_data' => $row_data	
									);
				$whereClause="payment_id='$payment_id'"; 
				$result = $db->CommonUpdate($table,$updatearray, $whereClause);
				
			}	 
		}
		else
		{				
		   $arrdata = array(		
					'reservation_no'=>$value['reservationno'],
					'un_allocated_reservation_no'=>$value['unallocatedreservationno'],
					'billing_location_id'=>$value['billinglocationid'],
					'payment_type' =>$value['paymenttype'],
					'paid'=>$value['paid'],
					'payment_date' => $value['paymentdate'],	
					'login_id'=>$value['loginid'],
					'cash_receipt_name'=>$value['cashreceiptname'],
					'other_extra_desc'=>$value['otherextradesc'],
					'dmy_created' =>$value['dmycreated'],
					'inv_payment_id'=>$value['invpaymentid'],
					'login_ip' =>$value['loginip'],
					'payment_token_id'=>$value['paymenttokenid'],
					'payment_id' => $value['paymentid'],
					'flag' => 2,
					'row_data' => $row_data	
		   			);
			   	$data = $db->CommonInsert($table,$arrdata);	
		}

	}


	// //Zoho code Reservation_Payments
	$objZoho = new Zoho();
	$reservationId ="";
	$select_query = mysqli_query($conn,"SELECT * FROM respayment WHERE flag = 2");
	// $select_query = mysqli_query($conn,"SELECT * FROM respayment WHERE payment_id ='11472");
	while ($row = mysqli_fetch_assoc($select_query))
	{
		$table1 = "respayment";
		$crmLog = "";
		$auto_id = $row['auto_id'];
		$reservation_no = $row['reservation_no'];
		$un_allocated_reservation_no = $row['un_allocated_reservation_no'];
		$payment_type = $row['payment_type'];
		$paid = $row['paid'];
		$login_id = $row['login_id'];
		$cash_receipt_name = $row['cash_receipt_name'];
		$other_extra_desc = $row['other_extra_desc'];
		$dmy_created = ConvertDate($row['dmy_created']);
		$inv_payment_id = $row['inv_payment_id'];
		$login_ip = $row['login_ip'];
		$payment_token_id = $row['payment_token_id'];
		$payment_date = ConvertDate($row['payment_date']);
		$billing_location_id  = $row['billing_location_id'];
		$payment_id = $row['payment_id'];

		try
		{
			if($objZoho->checkTokens())
			{

				//for billing location lookup
				$criteria_billing="((LocationID:equals:".$billing_location_id."))";
				$arrParams_billing['criteria']=$criteria_billing;
				$arrTrigger=["workflow"];
				$respSearchBilling=$objZoho->searchRecords("Locations",$arrParams_billing,$arrTrigger);
			    if(count($respSearchBilling['data']))
				{
				$billing_name = $respSearchBilling['data'][0]['id'];
				}
				else
				{
					$arrLocation=[
									
									'LocationID'=>$billing_location_id,
									'Cron_Name'=>'respayment.php',
								];
					$arrInsertLocation=[];
					$arrInsertLocation[]=$arrLocation;
					$arrTrigger=["workflow"];
					$respInsertLocation=$objZoho->insertRecord("Locations",$arrInsertLocation,$arrTrigger);
					if($respInsertLocation)
					{
						if($respInsertLocation['data'][0]['code']=="SUCCESS")
						{
							$billing_name=$respInsertLocation['data'][0]['details']['id'];
							$crmLog.="Insert Billing Location through respayment: ".$billing_name.", ";    
						}
						else
						{
							$crmLog.="Failed to insert Billing Location through respayment  !=success";
							$success=false;
						}
					}
					else
						{
							$crmLog.="Failed to insert Billing Location through respayment ";
						
						}
				}



				//for operator lookup
				$criteria_operator="((User_ID_R:equals:".$login_id."))";
				$arrParams_operator['criteria']=$criteria_operator;
				$arrTrigger=["workflow"];
				$respSearchOperator=$objZoho->searchRecords("RCM_Users",$arrParams_operator,$arrTrigger);
			    if(count($respSearchOperator['data']))
				{
					$operator_name = $respSearchOperator['data'][0]['id'];
				}
				else
				{
					$arrOperation=[
									
									'User_ID_R'=>$login_id,
									'Cron_Name'=>'respayment.php',
								];
					$arrInsertOperator=[];
					$arrInsertOperator[]=$arrOperation;
					$arrTrigger=["workflow"];
					$respInsertOperator=$objZoho->insertRecord("RCM_Users",$arrInsertOperator,$arrTrigger);
					if($respInsertOperator)
					{
						if($respInsertOperator['data'][0]['code']=="SUCCESS")
						{
							$operator_name=$respInsertOperator['data'][0]['details']['id'];
							$crmLog.="Insert Operator through respayment: ".$operator_name.", ";    
						}
						else
						{
							$crmLog.="Failed to insert Operator through respayment !=success ";
							$success=false;
						}
					}
					else
						{
							$crmLog.="Failed to insert Operator through respayment ";
							$success=false;
						}
				}



				
				//For reservation lookup
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
								$checkReservation = 0;
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
							$checkReservation = 0;
						}
				}
				else
				{
						$checkReservation = 0;
						
				}
				


				if($checkReservation==1)
				{
						if(count($respSearchReservations['data']))
						{
							$reservationId = $respSearchReservations['data'][0]['id'];
						}
					
					// else
					// {
					// 	$arrReservations=[
					// 		'Reservation_No_R'=>$reservation_no,
					// 		'Name'=>"Reservation_".$auto_id,
					// 		'Unallocated_Reservation_No_R'=>$un_allocated_reservation_no,
					// 		'Cron_Name'=>'respayment.php',

					// 	];
					// 	// echo "<pre>";

					// 	// print_r($arrReservations);
					// 	$arrInsertReservations=[];
					// 	$arrInsertReservations[]=$arrReservations;
					// 	$arrTrigger=["workflow"];
					// 	$respInsertReservations=$objZoho->insertRecord("Reservations",$arrInsertReservations,$arrTrigger);
					// 					//echo "insert";
					// 	// print_r($respInsertReservations);
					// 	if($respInsertReservations)
					// 	{
					// 		if($respInsertReservations['data'][0]['code']=="SUCCESS")
					// 		{
					// 			$reservationId=$respInsertReservations['data'][0]['details']['id'];
					// 			$crmLog.="Inserted Reservations through respayment: ".$reservationId.", ";    
							
							
					// 		}
					// 		else
					// 		{
					// 			$crmLog.="Failed to insert Reservations through respayment !=success ";
					// 			$success=false;
					// 		}
					// 	}
					// 	else
					// 	{
					// 		$crmLog.="Failed to insert Reservations through respayment ";
					// 		$success=false;
					// 	}
					// }





					//insert or update reservation payment based on payment ID
					$criteria="((Payment_ID_R:equals:".$payment_id."))";
					// print_r($criteria);
					$arrParams['criteria']=$criteria;
					$arrTrigger=["workflow"];
					$respSearchResPayment=$objZoho->searchRecords("Reservation_Payments",$arrParams,$arrTrigger);
					if(count($respSearchResPayment['data']))
					{
						$arrResPayment['Reservation_No']=$reservation_no;
						$arrResPayment['Reservation_ID']=$reservationId;  //lookup
						$arrResPayment['Unallocated_Reservation_No']=$un_allocated_reservation_no;
						$arrResPayment['Payment_Type']=$payment_type;
						$arrResPayment['Amount_Paid']=$paid;
						$arrResPayment['Cash_Receipt_Name']=$cash_receipt_name;
						$arrResPayment['Other_Description']=$other_extra_desc;
						$arrResPayment['Paid_Date']=$payment_date;
						$arrResPayment['Invoice_Payment_ID']=$inv_payment_id;
						$arrResPayment['Created_Date']=$dmy_created;
						$arrResPayment['Payment_Token_ID']=$payment_token_id;
						$arrResPayment['Operator_ID']=$login_id;
						$arrResPayment['Operator']=$operator_name;
						$arrResPayment['Billing_Location']=$billing_name;
						$arrResPayment['Payment_ID_R']=$payment_id;
						$arrResPayment['Cron_Name']='respayment.php';
						$respaymentId=$respSearchResPayment['data'][0]['id'];
						$arrUpdateResPayment=[];
						$arrUpdateResPayment[]=$arrResPayment;
						$arrTrigger=["workflow"];

						$respUpdateResPayment=$objZoho->updateRecord("Reservation_Payments",$respaymentId,$arrUpdateResPayment,$arrTrigger);
						if($respUpdateResPayment)
						{
							if($respUpdateResPayment['data'][0]['code']=="SUCCESS")
							{

								$respaymentId = $respUpdateResNotes['data'][0]['details']['id'];	
						
								$crmLog.="Updated Reservation Payments through respayment: ".$respaymentId.", ";
								$updatearray = array('flag'=>0);
								$whereClause="payment_id='$payment_id'";  
								$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
								// $arrResPayment[] ="";
							}
							else
							{
								$crmLog.="Failed to update Reservation Payments through respayment !=success: ".$respaymentId.", ";	
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to update Reservation Payments through respayment : ".$respaymentId.", ";	
							$success=false;
						}
					}
					else
					{
						$arrResPayment=[
							'Reservation_ID'=>$reservationId,
							'Reservation_No'=>$reservation_no,
							'Unallocated_Reservation_No'=>$un_allocated_reservation_no,
							'Payment_Type'=>$payment_type,
							'Amount_Paid'=>$paid,
							'Cash_Receipt_Name'=>$cash_receipt_name,
							'Other_Description'=>$other_extra_desc,
							'Paid_Date'=>$payment_date,
							'Invoice_Payment_ID'=>$inv_payment_id,
							'Created_Date'=>$dmy_created,
							'Payment_Token_ID'=>$payment_token_id,
							'Operator_ID'=>$login_id,
							'Operator'=>$operator_name,
							'Cron_Name'=>'respayment.php',
							'Billing_Location'=>$billing_name,
							'Payment_ID_R'=>$payment_id,
						];
						$arrInsertResPayment=[];
						$arrInsertResPayment[]=$arrResPayment;
						$arrTrigger=["workflow"];
						$respInsertResPayment=$objZoho->insertRecord("Reservation_Payments",$arrInsertResPayment,$arrTrigger);
						// print_r($respInsertResPayment);
						if($respInsertResPayment)
						{
							if($respInsertResPayment['data'][0]['code']=="SUCCESS")
							{
								$respaymentId=$respInsertResPayment['data'][0]['details']['id'];
								$crmLog.=" Reservation Payments through respayment: ".$respaymentId.", ";	
								$updatearray = array('flag'=>0);
								$whereClause="payment_id='$payment_id'"; 
								$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
								// $arrResPayment[] ="";
							}
							else
							{
								$crmLog.="Failed to insert Reservation Payments through respayment !=success ";
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to insert Reservation Payments through respayment ";
							$success=false;
						}
					}
				}// if check = 1 closed
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