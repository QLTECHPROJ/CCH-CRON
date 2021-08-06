<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include 'common.php';
$db = new DB();
$cron_start_date_time = date("Y-m-d H:i:s");
$table = "cron_run";
$cron_file = 'expanded_reservation_data.php';
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
	$table = "repbooking_export";
	

//reservation rates
	$season_data= array();
	$linked_res_data = array();
	$hire_date_season = array();
	$season_query=mysqli_query($conn,"SELECT  paymentdetails.rate,paymentdetails.days,paymentdetails.season_id as season_id,seasons.Season_Name as season_name , seasons.StartDate as season_start, seasons.EndDate as season_end 
		from repbooking_export INNER JOIN paymentdetails ON repbooking_export.reservationno = paymentdetails.reservation_no
		INNER JOIN seasons ON paymentdetails.season_id = seasons.SeasonID
		WHERE repbooking_export.flag = '0'  and repbooking_export.reservationno='6654'");
		while($row_season = mysqli_fetch_assoc($season_query))
		{
			$season_data[] = $row_season;
		}

		


//linked reservation 
		$linkedReservation= array();
		$linked_res_query = mysqli_query($conn,"SELECT  resnotes.notes_id as notes_id, resnotes.subject as subject, resnotes.notes as notes 
		from repbooking_export 
		INNER JOIN resnotes ON repbooking_export.reservationno = resnotes.reservation_no
		WHERE repbooking_export.flag = '0'  and repbooking_export.reservationno='6654'");
		while($row_linked_res_no = mysqli_fetch_assoc($linked_res_query))
		{
			$linked_res_data[] = $row_linked_res_no;
		}

		foreach($linked_res_data as $key => $linked_val){
			if($linked_val['subject'] == "Linked Reservation No"){
				$linkedReservation['Linked_Reservation_No_R']=$linked_val['notes'];
			}
			 if($linked_val['subject'] == "Linked Travel Companion"){
				$linkedReservation['Linked_Travel_Companion_R']=$linked_val['notes'];
			}

		}
		
		// echo "<pre>";
		// print_r($linked_res_data);

	$select_query=mysqli_query($conn,"SELECT * FROM repbooking_export WHERE flag = 0  and reservationno='6654'");
	
	$sql_query=mysqli_query($conn,"SELECT * FROM reservation_hire_dates WHERE  reservationno='6654'");
//6596 6603 5130
	while($row = mysqli_fetch_assoc($select_query))
	{
		echo "<pre>";
		print_r($row);


		$crmLog = "";
		$auto_id = $row['auto_id'];
		$reservationno = $row['reservationno'];
		$pickupdatetime = ConvertDate($row['pickupdatetime']);
		$dropoffdatetime = ConvertDate($row['dropoffdatetime']);
		$dateentered = ConvertDateTime($row['dateentered']);
		$lastdateupdated = ConvertDateTime($row['lastdateupdated']);
		$bookingtype = $row['bookingtype'];
		$totalrentaldays = $row['totalrentaldays'];
		$daily_rate = $row['bookingtotal'] / $row['totalrentaldays'];   // with calculation it comes wrong as actual daily rate
		$rental_value=  $daily_rate * $row['totalrentaldays'];
		$lro_value = $row['insurancedailyrate'] * $row['totalrentaldays'];
		$comission_paid = $row['agentcommission'];
		$date_booked = ConvertDateTime($row['dateentered']);
		$total_kms = $row['kmsin'] - $row['kmsout'];
		$avg_kms = $total_kms / $row['totalrentaldays'];
		// $lead_time= datediff('d',$pickupdatetime,$date_booked);
		$lead_time = dateDiffInDays($pickupdatetime,$date_booked);
		$assets_rental_value = $rental_value - $lro_value;


		//to be added
		$kms_in = $row['kmsin'];
		$kms_out = $row['kmsout'];
		$fuel_out = $row['fuelout'];
		$fuel_in = $row['fuelin'];



		

		$json = json_decode($row['row_data'], true);
		$revenue =  $json['extrarevenue'];
		$referenceno =  $json['reservationref'];
				

		//post API DATA
	 	$show_json = post_api_call($referenceno);		
		echo "post API DATA";
		echo "<pre>";
		print_r($show_json);
		$array = json_decode(json_encode($show_json), true);
		$bookings = $array['results']['bookinginfo'][0];
		$customer =$array['results']['customerinfo'][0];
		$companyinfo =$array['results']['companyinfo'][0];
		$rateinfo =$array['results']['rateinfo'][0];
		$extrafees =$array['results']['extrafees'][0];
		$paymentinfo = $array['results']['paymentinfo'][0];
		if(!empty($array['results']['extradrivers'])){
			$extradrivers =$array['results']['extradrivers'][0];
		}
		
		echo "<pre>";
		print_r($extradrivers);
		//extra drivers 


		$youngest_driver = array();
	
		foreach($array['results']['extradrivers'] as $key  => $youngest)
		{
			
			$youngest_driver[] = $youngest['dateofbirth']; 
			$extra_cus_id = $extradrivers['customerid'];
			$extra_cus_firstname = $extradrivers['firstname'];
			$extra_cus_lastname = $extradrivers['lastname'];
			$extra_cus_dateofbirth = $extradrivers['dateofbirth'];
			$extra_cus_firstname = $extradrivers['firstname'];
			$extra_cus_firstname = $extradrivers['firstname'];
			$extra_cus_firstname = $extradrivers['firstname'];
			$extra_cus_firstname = $extradrivers['firstname'];
			$extra_cus_firstname = $extradrivers['firstname'];
			
		}

		$youngest_driver_dob = ConvertDate(min($youngest_driver));
		
		$youngest_driver_age = $youngest_driver_dob - $pickupdatetime;
		

		





		$actual_daily_rate = $bookings['dailyrate'];

		//to be added
		$status = $bookings['reservationstatus'];
		$unallocated_reservation_no = $row['unallocatedreservationno'];
		$pickup_location = $bookings['pickuplocationname'];
		$dropoff_location = $bookings['dropofflocationname'];
		$pickup_time = $bookings['pickuptime'];
		$dropoff_time = $bookings['dropofftime'];
		$rental_source = $bookings['rentalsource'];


		$bookedby = $row['bookingoperator'];
		$customer_age = datediff('yyyy', $customer['dateofbirth'], $pickupdatetime);
		


		// //bond amount 
		$bond_amount = 0;
		foreach($array['results']['extrafees'] as $key  => $fees)
		{
			
			if($fees['isbondfee'] == 1 && $fees['totalfeeamount'] > 0)
			{
			 $bond_amount = $fees['totalfeeamount'];
			
			}
			
		}
		$total_rental_value = $rental_value - $bond_amount;


		//agency info 
		$booking_agency = $row['bookingagency'];
		$agency_email = $row['agencybranchemail'];
				
		//rateinfo 
		$rate_index_count = count($array['results']['rateinfo']);
				

		// if($rate_index_count > 1){
			$reservation_rate = array();
			$payment_rate = 0;
			$gst = $bookings['gst'];
			foreach($array['results']['rateinfo'] as $key  => $rate)
			{
				
				$season['season_name']= $rate['season'];
				$season['season_numberofdays'] = $rate['numberofdays'];
				$season['dailyratebeforediscount'] = $rate['dailyratebeforediscount'];
				$season['seasonsubtotal'] = $rate['seasonsubtotal'];
		
				$reservation_rate[] = $season;
				$payment_rate += $season['seasonsubtotal']; 
			} 
			
			$payment_rate_gst = $payment_rate + $gst;
			$payment_rate_without_gst = $payment_rate;

			

		//vehicle info 
		$car_rego = $bookings['vehicle_registrationnumber'];
		$fleet_no = $bookings['vehicle_fleetnumber'];
		$no_travelling = $bookings['numbertravelling'];
		$master_vehicle_category = $row['bookedcategorytype'];
		$sub_vehicle_category = $row['vehiclecategory'];
		$vehicle_brand = $row['sbrand'];

		//to be added
		$vehicle_id = $bookings['vehicle_id'];

		// 	//customer info
		$customr_id = $customer['customerid'];
		$customer_name = $customer['firstname']." ".strtoupper($customer['lastname']);
		$customer_email =$customer['email'];
		$customer_dob = $customer['dateofbirth'];
		$customer_phone = $customer['phone'];
		$customer_mobile = $customer['mobile'];
		$licence_country = $customer['licenseissued'];
		$licence_no =$customer['licenseno'];
		$license_expires = $customer['licenseexpires'];


		$full_address = $customer['fulladdress'];
		$custom_address = $customer['address'];
		$city = $customer['city'];
		$state = $customer['state'];
		$postcode = $customer['postcode'];


		$country_state_origin = $customer['country']. "/".$customer['state'];
		$customer_address = $customer['country'];
		if($customer_address == "Australia")
		{
			$address = "Domestic";
			$customer_class = "Domestic";
		}
		else
		{
			$address = "International";
			$customer_class = "International";
		}


		//state name 
		$aus_state_name = $customer['state'];
		if($aus_state_name == "New South Wales" || $aus_state_name == "NSW"){
			$state_name = "NSW";
		}
		else if($aus_state_name == "Queensland" || $aus_state_name == "QLD"){
			$state_name = "QLD";
		}
		else if($aus_state_name == "South Australia" || $aus_state_name == "SA"){
			$state_name = "SA";
		}
		else if($aus_state_name == "Tasmania" || $aus_state_name == "TAS"){
			$state_name = "TAS";
		}
		else if($aus_state_name == "Victoria" || $aus_state_name == "VIC"){
			$state_name = "VIC";
		}
		else if($aus_state_name == "Western Australia" || $aus_state_name == "WA"){
			$state_name = "WA";
		}
		else if($aus_state_name == "Australian Capital Territory" || $aus_state_name == "ACT"){
			$state_name = "ACT";
		}
		else if($aus_state_name == "Northern Territory" || $aus_state_name == "NT"){
			$state_name = "NT";
		}
		else if($aus_state_name == "Jervis Bay Territory" || $aus_state_name == "NT"){
			$state_name = "NT";
		}
		else if($aus_state_name == ""){
			$state_name = "";
		}


	 	if(mysqli_num_rows($sql_query) < 1)
      	{
      		echo "khk";
			$hire_date = getDatetimerange($pickupdatetime,$dropoffdatetime);
			// print_r($hire_date);

		


			foreach($hire_date as $key => $val)
			{
				$week_of_month = weekOfMonth(strtotime($val));
				$week_of_year = weekOfYear(strtotime($val));
				$day_of_year = date('l', strtotime($val));
				$year = date('Y',strtotime($val));
					
				$ss_new = array();
					foreach($season_data as $key => $sdate)
					{
			
					$season_start = ConvertDate($sdate['season_start']);
					$season_end = ConvertDate($sdate['season_end']);
					$rate = $sdate['rate'];
					$dayss = $sdate['days'];
						if (($val >= $season_start) && ($val <= $season_end))
						{
						  
						    	$ss_new['s_start'] = ConvertDate($sdate['season_start']);
								$ss_new['s_end'] = ConvertDate($sdate['season_end']);
								$ss_new['rate'] = $sdate['rate'];
								$ss_new['dayss'] = $sdate['days'];
								$ss_new['season_name'] = $sdate['season_name'];


						}
					}

					$season_subtotal = $ss_new['dayss'] * $ss_new['rate'];
				
				$hireDate=[
										
							'reservation_no'=>$reservationno,
							'pickup_date'=>$pickupdatetime,
							'drop_date'=>$dropoffdatetime,
							'hire_date'=>$val,
							'booking_type'=> $bookingtype,
							'totalrentaldays'=>$totalrentaldays,
							// 'daily_rate' => $daily_rate,
							'daily_rate' => $actual_daily_rate,
							'rental_value' => $rental_value,
							'LRO_value' => $lro_value,
							'comission_paid' => $comission_paid,
							'extra_revenue' => $revenue,
							'customer_id' => $customr_id,
							'customer_name' => $customer_name,
							'customer_phone' => $customer_phone,
							'customer_mobile' => $customer_mobile,
							'customer_dob' => $customer_dob,
							'customer_address' =>$custom_address,
							'customer_class' => $customer_class,
							'licence_contry' => $licence_country,
							'fulladdress' => $full_address,
							'address' => $address,
							'city' => $city,
							'state' => $state,
							'postcode' => $postcode,
							'agency_name' => $booking_agency,
							'agency_email' => $agency_email,
							'date_booked' => $date_booked,
							'lead_time' => $lead_time."days",
							'australian_state_name' => $state_name,
							'total_kms_travelled' => $total_kms,
							'avg_kms_per_day' => $avg_kms,
							// 'country_state_origin' =>$country_state_origin,
							'vehicle_brand' => $vehicle_brand,
							'car_rego' => $car_rego,
							'fleet_no' => $fleet_no,
							'no_travelling' => $no_travelling,
							'master_vehicle_category' => $master_vehicle_category,
							'sub_vehicle_category' => $sub_vehicle_category,
							'week_of_month' => $week_of_month,
							'week_of_year' => $week_of_year,
							'day_of_year' => $day_of_year,
							'payment_rate_gst' => $payment_rate_gst,
							'payment_rate_without_gst' => $payment_rate_without_gst,
							'gst' => $gst,
							'year' => $year,
							'bond_amount' => $bond_amount,
							'total_rental_value' => $total_rental_value,
							'assets_rental_value' => $assets_rental_value,
							'vehicle_id' => $vehicle_id,
							'status' => $status,
							'unallocated_reservation_no' => $unallocated_reservation_no,
							'pickup_location' => $pickup_location,
							'dropoff_location' => $dropoff_location,
							'pickup_time' => $pickup_time,
							'dropoff_time' => $dropoff_time,
							'rental_source' => $rental_source,
							'kms_in' => $kms_in,
							'kms_out' => $kms_out,
							'fuel_out' => $fuel_out,
							'fuel_in' => $fuel_in,
							'season_name' => $ss_new['season_name'],
							'season_numberofdays' => $ss_new['dayss'],
							'dailyratebeforediscount' => $ss_new['rate'],
							'seasonsubtotal' => $season_subtotal ,
							'customer_age' => $customer_age,
							'booked_by' => $bookedby,
							'licence_no' => $licence_no,
							'license_expires' => $license_expires,
							'linked_reservation_no'=> $linkedReservation['Linked_Reservation_No_R'],
							'linked_travel_companion'=> $linkedReservation['Linked_Travel_Companion_R'],

	
							
						];
							echo "<pre>";
							print_r($hireDate);
				$data = $db->CommonInsert("reservation_hire_dates",$hireDate);
			}			
		}
		else
		{

			echo "hre";
			$hire_date = getDatetimerange($pickupdatetime,$dropoffdatetime);

			$fetch_sql = mysqli_query($conn,"delete FROM `reservation_hire_dates` WHERE `reservation_no` = '$reservationno'");

		// echo "out of loop";
		// echo "<pre>";
		// print_r($hire_date);

		// $season_extend = array();
		// $days = array();
		// $match_date =array();
		
		// $match_dtee= array();
		// foreach($season_data as $key => $sdate)
		// {
		// 	echo "kkk<pre>";
		// 	print_r($sdate);
		// 	$season_start = ConvertDate($sdate['season_start']);
		// 	$season_end = ConvertDate($sdate['season_end']);
		// 	$rate = $sdate['rate'];
		// 	$dayss = $sdate['days'];

		// 	$hire_date_season = getDatetimerange($season_start,$season_end);
		// 	if (count(array_intersect($hire_date_season,$hire_date)) === 0) {
		// 	  echo "<br>no";
		// 	} else {
		// 		echo "yes<br>";
		// 		$days[$key]['days'] = $dayss;
		// 		$days[$key]['rate'] = $rate;
		// 		// $days[$key]['days'] = count(array_intersect($hire_date_season,$hire_date));
		// 		$days[$key]['season_start'] = $season_start;
		// 		$days[$key]['season_end'] = $season_end;
		// 		$days[$key]['matched_date'] = array_intersect($hire_date_season,$hire_date);
		// 		$days[$key]['hire_date'] =$hire_date ;	

		// 		// $season_extend[] = $hire_date_season;
		// 		$match_dtee[] = array_intersect($hire_date_season,$hire_date);
		// 	}	
		// }


			
			// echo "<pre>";
			// print_r($days);

			// foreach($days as $show => $match){
			// 	echo "<pre>";
			// 	print_r($match);
				
			// }
			



			// print_r($season_extend);


			foreach($hire_date as $key => $val)
			{

				
				$week_of_month = weekOfMonth(strtotime($val));
				$week_of_year = weekOfYear(strtotime($val));
				$day_of_year = date('l', strtotime($val));
				$year = date('Y',strtotime($val));

				    
					$ss_new = array();

					foreach($season_data as $key => $sdate)
					{
			
					$season_start = ConvertDate($sdate['season_start']);
					$season_end = ConvertDate($sdate['season_end']);
					$rate = $sdate['rate'];
					$dayss = $sdate['days'];
						if (($val >= $season_start) && ($val <= $season_end))
						{
						  
						    	$ss_new['s_start'] = ConvertDate($sdate['season_start']);
								$ss_new['s_end'] = ConvertDate($sdate['season_end']);
								$ss_new['rate'] = $sdate['rate'];
								$ss_new['dayss'] = $sdate['days'];
								$ss_new['season_name'] = $sdate['season_name'];


						}
					}

	
					$season_subtotal = $ss_new['dayss'] * $ss_new['rate'];
				$arrHireDate=[
										
							'reservation_no'=>$reservationno,
							'pickup_date'=>$pickupdatetime,
							'drop_date'=>$dropoffdatetime,
							'hire_date'=>$val,
							'booking_type'=> $bookingtype,
							'totalrentaldays'=>$totalrentaldays,
							// 'daily_rate' => $daily_rate,
							'daily_rate' => $actual_daily_rate,
							'rental_value' => $rental_value,
							'LRO_value' => $lro_value,
							'comission_paid' => $comission_paid,
							'extra_revenue' => $revenue,
							'customer_id' => $customr_id,
							'customer_name' => $customer_name,
							'customer_phone' => $customer_phone,
							'customer_mobile' => $customer_mobile,
							'customer_dob' => $customer_dob,
							'customer_address' =>$custom_address,
							'customer_class' => $customer_class,
							'licence_contry' => $licence_country,
							'fulladdress' => $full_address,
							'address' => $address,
							'city' => $city,
							'state' => $state,
							'postcode' => $postcode,
							'agency_name' => $booking_agency,
							'agency_email' => $agency_email,
							'date_booked' => $date_booked,
							'lead_time' => $lead_time."days",
							'australian_state_name' => $state_name,
							'total_kms_travelled' => $total_kms,
							'avg_kms_per_day' => $avg_kms,
							// 'country_state_origin' =>$country_state_origin,
							'vehicle_brand' => $vehicle_brand,
							'car_rego' => $car_rego,
							'fleet_no' => $fleet_no,
							'no_travelling' => $no_travelling,
							'master_vehicle_category' => $master_vehicle_category,
							'sub_vehicle_category' => $sub_vehicle_category,
							'week_of_month' => $week_of_month,
							'week_of_year' => $week_of_year,
							'day_of_year' => $day_of_year,
							'payment_rate_gst' => $payment_rate_gst,
							'payment_rate_without_gst' => $payment_rate_without_gst,
							'gst' => $gst,
							'year' => $year,
							'bond_amount' => $bond_amount,
							'total_rental_value' => $total_rental_value,
							'assets_rental_value' => $assets_rental_value,
							'vehicle_id' => $vehicle_id,
							'status' => $status,
							'unallocated_reservation_no' => $unallocated_reservation_no,
							'pickup_location' => $pickup_location,
							'dropoff_location' => $dropoff_location,
							'pickup_time' => $pickup_time,
							'dropoff_time' => $dropoff_time,
							'rental_source' => $rental_source,
							'kms_in' => $kms_in,
							'kms_out' => $kms_out,
							'fuel_out' => $fuel_out,
							'fuel_in' => $fuel_in,
							'season_name' => $ss_new['season_name'],
							'season_numberofdays' => $ss_new['dayss'],
							'dailyratebeforediscount' => $ss_new['rate'],
							'seasonsubtotal' => $season_subtotal ,
							'customer_age' => $customer_age,
							'booked_by' => $bookedby,
							'licence_no' => $licence_no,
							'license_expires' => $license_expires,
							'linked_reservation_no'=> $linkedReservation['Linked_Reservation_No_R'],
							'linked_travel_companion'=> $linkedReservation['Linked_Travel_Companion_R'],
							
						];
							echo "<pre>";
							print_r($arrHireDate);
						$data = $db->CommonInsert("reservation_hire_dates",$arrHireDate);
					
			}
		}		
	}
			

			
	


	$table = "cron_run";
	$cron_end_date_time = date("Y-m-d H:i:s");
	$cron_duration = strtotime($cron_end_date_time) - strtotime($cron_start_date_time);
	$arrcrondata = array("end_date_time"=>$cron_end_date_time,
		"duration"=>$cron_duration,
		"cron_completed"=>1);
	$whereClause = "id='$cron_id'";
	// $result = $db->CommonUpdate($table,$arrcrondata, $whereClause);



function weekOfMonth($date) {
    //Get the first day of the month.
    $firstOfMonth = strtotime(date("Y-m-01", $date));
    //Apply above formula.
    return weekOfYear($date) - weekOfYear($firstOfMonth) + 1;
}

function weekOfYear($date) {
    $weekOfYear = intval(date("W", $date));
    if (date('n', $date) == "1" && $weekOfYear > 51) {
        // It's the last week of the previos year.
        return 0;
    }
    else if (date('n', $date) == "12" && $weekOfYear == 1) {
        // It's the first week of the next year.
        return 53;
    }
    else {
        // It's a "normal" week.
        return $weekOfYear;
    }
}


	function datediff($interval, $datefrom, $dateto, $using_timestamps = false)
{
    /*
    $interval can be:
    yyyy - Number of full years
    q    - Number of full quarters
    m    - Number of full months
    y    - Difference between day numbers
           (eg 1st Jan 2004 is "1", the first day. 2nd Feb 2003 is "33". The datediff is "-32".)
    d    - Number of full days
    w    - Number of full weekdays
    ww   - Number of full weeks
    h    - Number of full hours
    n    - Number of full minutes
    s    - Number of full seconds (default)
    */

    if (!$using_timestamps) {
        $datefrom = strtotime($datefrom, 0);
        $dateto   = strtotime($dateto, 0);
    }

    $difference        = $dateto - $datefrom; // Difference in seconds
    $months_difference = 0;

    switch ($interval) {
        case 'yyyy': // Number of full years
            $years_difference = floor($difference / 31536000);
            if (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom), date("j", $datefrom), date("Y", $datefrom)+$years_difference) > $dateto) {
                $years_difference--;
            }

            if (mktime(date("H", $dateto), date("i", $dateto), date("s", $dateto), date("n", $dateto), date("j", $dateto), date("Y", $dateto)-($years_difference+1)) > $datefrom) {
                $years_difference++;
            }

            $datediff = $years_difference;
        break;

        case "q": // Number of full quarters
            $quarters_difference = floor($difference / 8035200);

            while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($quarters_difference*3), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                $months_difference++;
            }

            $quarters_difference--;
            $datediff = $quarters_difference;
        break;

        case "m": // Number of full months
            $months_difference = floor($difference / 2678400);

            while (mktime(date("H", $datefrom), date("i", $datefrom), date("s", $datefrom), date("n", $datefrom)+($months_difference), date("j", $dateto), date("Y", $datefrom)) < $dateto) {
                $months_difference++;
            }

            $months_difference--;

            $datediff = $months_difference;
        break;

        case 'y': // Difference between day numbers
            $datediff = date("z", $dateto) - date("z", $datefrom);
        break;

        case "d": // Number of full days
            $datediff = floor($difference / 86400);
        break;

        case "w": // Number of full weekdays
            $days_difference  = floor($difference / 86400);
            $weeks_difference = floor($days_difference / 7); // Complete weeks
            $first_day        = date("w", $datefrom);
            $days_remainder   = floor($days_difference % 7);
            $odd_days         = $first_day + $days_remainder; // Do we have a Saturday or Sunday in the remainder?

            if ($odd_days > 7) { // Sunday
                $days_remainder--;
            }

            if ($odd_days > 6) { // Saturday
                $days_remainder--;
            }

            $datediff = ($weeks_difference * 5) + $days_remainder;
        break;

        case "ww": // Number of full weeks
            $datediff = floor($difference / 604800);
        break;

        case "h": // Number of full hours
            $datediff = floor($difference / 3600);
        break;

        case "n": // Number of full minutes
            $datediff = floor($difference / 60);
        break;

        default: // Number of full seconds (default)
            $datediff = $difference;
        break;
    }

    return $datediff;
}




function post_api_call($referenceno){
	$ch = curl_init();
	$post_fields = 'username=QXVDcmlrZXlDYW1wZXIyNTF8UUxUZWNofDZnR28ySXYw&password=1LL66kuivJlmNLoAbHooArwAo6LnkiM0&grant_type=password';
	curl_setopt($ch, CURLOPT_URL,"https://api.rentalcarmanager.com/v32/token");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS,$post_fields);  //Post Fields
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$headers = [
		'Content-Type: application/x-www-form-urlencoded',
	];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$server_output = curl_exec ($ch);
	$server_output = json_decode($server_output,true);
	curl_close ($ch);
	$access_token = $server_output['access_token'];
	$expires = strtotime($server_output['.expires']);
	$json_data = json_encode(array("method" => "bookinginfo","reservationref" => "$referenceno"));
	$authorization = "Authorization: Bearer ".$access_token;
	$url = 'https://api.rentalcarmanager.com/v32/api';
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json' , $authorization ));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$result = curl_exec($ch);
	curl_close($ch);
	// echo "<pre>";
	$json_show = json_decode($result);
	return $json_show;
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


function getDatetimerange($Date1, $Date2){


// Declare an empty array
$array = array();
  
// Use strtotime function
$Variable1 = strtotime($Date1);
$Variable2 = strtotime($Date2);

print_r($Variable1);
  
// Use for loop to store dates into array
// 86400 sec = 24 hrs = 60*60*24 = 1 day
	$array = array();
	for ($currentDate = $Variable1; $currentDate <= $Variable2; $currentDate += (86400)) 
	{
		// print_r($currentDate)
	    $Store = date('Y-m-d H:i:s',$currentDate);
	    $array[]= $Store;
	}

	return $array;

}


function dateDiffInDays($date1, $date2) 
{
    // Calculating the difference in timestamps
    $diff = strtotime($date2) - strtotime($date1);
      
    // 1 day = 24 hours
    // 24 * 60 * 60 = 86400 seconds
    return abs(round($diff / 86400));
}









  
// // Function to get all the dates in given range
// function getDatesFromRange($start, $end, $format = 'Y-m-d') {
      
//     // Declare an empty array
//     $array = array();
      
//     // Variable that store the date interval
//     // of period 1 day
//     $interval = new DateInterval('P1D');
  
//     $realEnd = new DateTime($end);
//     $realEnd->add($interval);
  
//     $period = new DatePeriod(new DateTime($start), $interval, $realEnd);
  
//     // Use loop to store date into array
//     foreach($period as $date) {                 
//         $array[] = $date->format($format); 
//     }
  
//     // Return the array elements
//     return $array;
// }
  
// // Function call with passing the start date and end date
// $Date = getDatesFromRange('2010-10-01', '2010-10-05');
  
// var_dump($Date);
