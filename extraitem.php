<?php
include 'common.php';
$db = new DB();
$cron_start_date_time = date("Y-m-d H:i:s");
$table = "cron_run";
$cron_file = 'extraitem.php';
$arrdatacron = array("start_date_time"=>$cron_start_date_time,"cron_file"=>$cron_file);
$cron_id = $db->CommonInsert($table,$arrdatacron);
?>
<?php
include 'class/cch.class.php';
include 'zoho/Zoho.php';
include 'conn.php';
$success = true;
$db = new DB();
$cch = new cch();
$table1 = "extraitem";
	$GetExtraItem = $cch->GetExtraItem(); // location data get from api 
	// echo "<PRE>";
	// print_r($GetExtraItem);
	// exit;
	foreach ($GetExtraItem as $key => $value) {
		$row_data = json_encode($value);
		$id = $value['id'];
		$FetchExtraItem = $db->FetchExtraItem($id); // location data get from database
		$num_row = $FetchExtraItem->num_rows;
		if($num_row > 0)
		{	
			while ($row_FetchExtraItem=mysqli_fetch_assoc($FetchExtraItem))
			{
				$updatearray = array(
										'extra_type'=>$value['extratype'],
										'category_type' =>$value['categorytype'],
										'category'=>$value['category'],
										'description' => $value['description'],	
										'max_price'=>$value['maxprice'],
										'rate'=>$value['rate'],
										'excess_fee'=>$value['excessfee'],
										'flag' => 1,
										'row_data' => $row_data	
									);
				$whereClause="id='$id'"; 
				$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
			}	 
				//$update =   $data = $db->CommonUpdate($table,$value);
		}
		else
		{
			   $arrdata = array(		
										'id	'=>$value['id'],
										'extra_type'=>$value['extratype'],
										'category_type' =>$value['categorytype'],
										'category'=>$value['category'],
										'description' => $value['description'],	
										'max_price'=>$value['maxprice'],
										'rate'=>$value['rate'],
										'excess_fee'=>$value['excessfee'],
										'flag' => 1,
										'row_data' => $row_data	
			   				);
			   $data = $db->CommonInsert($table1,$arrdata);
		}
	}
			//Zoho Code
	$objZoho = new Zoho();
	$select_query=mysqli_query($conn,"SELECT * FROM extraitem WHERE flag = 1");
	// $select_query=mysqli_query($conn,"SELECT *  FROM `extraitem` WHERE auto_id='577'");
	$ct = "";
	$id = "";
	$vechileCategoryId="";
	while($row = mysqli_fetch_assoc($select_query))
	{
		
		$table1 = "extraitem";
		$crmLog = "";
		$id = $row['id'];
		$extra_type = $row['extra_type'];
		$category_type = $row['category_type'];
		$category = $row['category'];
		$description = $row['description'];
		$max_price = $row['max_price'];
		$rate = $row['rate'];
		$excess_fee = $row['excess_fee'];
		try
		{
			if($objZoho->checkTokens())
			{
				
				// if($category !="")
				// {
				
					//for category type lookup
					$criteria="((Name:equals:".$category."))";
					$arrParams_vehicle_catgory['criteria']=$criteria;
					$respSearchVechCategory=$objZoho->searchRecords("Vehicle_Category",$arrParams_vehicle_catgory,$arrTrigger);
						if(!empty($respSearchVechCategory['data'])){


					      if(count($respSearchVechCategory['data']))
							{
								// echo "updated code";
								// print_r($respSearchVechCategory['data'][0]['id']);
								$vechileCategoryId = $respSearchVechCategory['data'][0]['id'];
							}
							else 
							{
								$arrvechilecategory=[
									'Name'=>$category,
									'Cron_Name'=>'extraitem.php',
									'Vehicle_Category_Type'=>$category_type

								];
								
								$arrInsertVechileCategory=[];
								$arrInsertVechileCategory[]=$arrvechilecategory;
								$arrTrigger=["workflow"];
								$respInsertVechileCategory=$objZoho->insertRecord("Vehicle_Category",$arrInsertVechileCategory,$arrTrigger);
								print_r($respInsertVechileCategory);
								if($respInsertVechileCategory)
								{
									if($respInsertVechileCategory['data'][0]['code']=="SUCCESS")
									{
										$vechileCategoryId=$respInsertVechileCategory['data'][0]['details']['id'];
										// echo "with success";
										// print_r($vechileCategoryId);
										$crmLog.="Vechile Category inserted through extra item cron: ".$vechileCategoryId.", ";	
									}
									else
									{
										$crmLog.="Failed to insert Vechile Category through extra item cron != success ";
										$success=false;
									}
								}
								else
								{
									$crmLog.="Failed to insert Vechile Category through extra item cron ";
									$success=false;
								}
							}
						}
				// }

				
			
				if($extra_type=="Extra Item")
				{
						
					$criteria="((ExtraitemID:equals:".$id."))";
					$arrParams['criteria']=$criteria;
					$arrTrigger=["workflow"];
					$respSearchExtraItem=$objZoho->searchRecords("Extraitems",$arrParams,$arrTrigger);
					if(count($respSearchExtraItem['data']))
					{
						$arrExtraItem['ExtraitemID']=$id;
						$arrExtraItem['Name']=$description;
						$arrExtraItem['Max_Price']=$max_price;
						$arrExtraItem['Rate']=$rate;
						$arrExtraItem['Vehicle_Category_Type']=$category_type;
						$arrExtraItem['Vehicle_Category']=$vechileCategoryId;
						$arrExtraItem['Excess_Fee']=$excess_fee;
						$arrExtraItem['Cron_Name']='extraitem.php';
						$extraitemId=$respSearchExtraItem['data'][0]['id'];
						$arrUpdateExtraItem=[];
						$arrUpdateExtraItem[]=$arrExtraItem;
						$arrTrigger=["workflow"];
						$respSearchExtraItem=$objZoho->updateRecord("Extraitems",$extraitemId,$arrUpdateExtraItem,$arrTrigger);
										//print_r($respUpdateCustomer);
						if($respSearchExtraItem)
						{
							if($respSearchExtraItem['data'][0]['code']=="SUCCESS")
							{
								$crmLog.="Updated Extra item through extra item cron : ".$extraitemId.", ";
								$updatearray = array('flag'=>0);
								$whereClause="id='$id'"; 
								$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
							}
							else
							{
								$crmLog.="Failed to update Extra item through extra item cron != success: ".$extraitemId.", ";    
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to update Extra item through extra item cron: ".$extraitemId.", ";    
							$success=false;
						}
					}
					else
					{
						$arrExtraItem=[
							'ExtraitemID'=>$id,
							'Name'=>$description,
							'Max_Price'=>$max_price,
							'Rate'=>$rate,
							'Vehicle_Category_Type'=>$category_type,
							'Vehicle_Category'=>$vechileCategoryId,
							'Cron_Name'=>'extraitem.php',
							'Excess_Fee'=>$excess_fee
						];
						$arrInsertExtraItem=[];
						$arrInsertExtraItem[]=$arrExtraItem;
						$arrTrigger=["workflow"];
						$respInsertExtraItem=$objZoho->insertRecord("Extraitems",$arrInsertExtraItem,$arrTrigger);
										//echo "insert";
						print_r($respInsertExtraItem);
						if($respInsertExtraItem)
						{
							if($respInsertExtraItem['data'][0]['code']=="SUCCESS")
							{
								$extraitemId=$respInsertExtraItem['data'][0]['details']['id'];
								$crmLog.="extraitemId Extra item through extra item cron: ".$extraitemId.", ";    
								$updatearray = array('flag'=>0);
								$whereClause="id='$id'"; 
								$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
							}
							else
							{
								$crmLog.="Failed to insert Extra item through extra item cron !=success ";
								$success=false;
							}
						}
						else
						{
							$crmLog.="Failed to insert Extra item through extra item cron ";
							$success=false;
						}
					}
				}

				if($extra_type=="Insurance")
				{
						$criteria="((Insurance_ID_R:equals:".$id."))";
						$arrParams['criteria']=$criteria;
						$arrTrigger=["workflow"];
						$respSearchExtraItemInsurance=$objZoho->searchRecords("Insurance_items",$arrParams,$arrTrigger);
						// print_r($respSearchExtraItemInsurance);
						if(count($respSearchExtraItemInsurance['data']))
						{
							$arrExtraItemInsurance['Insurance_ID_R']=$id;
							$arrExtraItemInsurance['Name']=$description;
							$arrExtraItemInsurance['Max_Price']=$max_price;
							$arrExtraItemInsurance['Rate']=$rate;
							$arrExtraItemInsurance['Vehicle_Category_Type']=$category_type;
							$arrExtraItemInsurance['Vehicle_Category']=$vechileCategoryId;
							$arrExtraItemInsurance['Excess_Fee']=$excess_fee;
							$arrExtraItemInsurance['Cron_Name']='extraitem.php';
							$extraiteminsuraceId=$respSearchExtraItemInsurance['data'][0]['id'];
							$arrUpdateExtraItemInsurance=[];
							$arrUpdateExtraItemInsurance[]=$arrExtraItemInsurance;
							$arrTrigger=["workflow"];
							$respInsurance=$objZoho->updateRecord("Insurance_items",$extraiteminsuraceId,$arrUpdateExtraItemInsurance,$arrTrigger);
												//print_r($respUpdateCustomer);
							if($respInsurance)
							{
								if($respInsurance['data'][0]['code']=="SUCCESS")
								{
									$crmLog.="Updated Extra item  through extra item cron: ".$extraiteminsuraceId.", ";
									$updatearray = array('flag'=>0);
									$whereClause="id='$id'"; 
									$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
								}
								else
								{
									$crmLog.="Failed to update Extra item through extra item cron !=success: ".$extraiteminsuraceId.", ";    
									$success=false;
								}
							}
							else
							{
								$crmLog.="Failed to update Extra item through extra item cron: ".$extraiteminsuraceId.", ";    
								$success=false;
							}
						}
						else
						{
							$arrExtraItemInsu=[
								'Insurance_ID_R'=>$id,
								'Name'=>$description,
								'Max_Price'=>$max_price,
								'Rate'=>$rate,
								'Vehicle_Category_Type'=>$category_type,
								'Vehicle_Category'=>$vechileCategoryId,
								'Cron_Name'=>'extraitem.php',
								'Excess_Fee'=>$excess_fee
							];
							$arrInsertExtraItemIns=[];
							$arrInsertExtraItemIns[]=$arrExtraItemInsu;
							$arrTrigger=["workflow"];
							$respInsertExtraItemIn=$objZoho->insertRecord("Insurance_items",$arrInsertExtraItemIns,$arrTrigger);
												//echo "insert";
							print_r($respInsertExtraItemIn);
							if($respInsertExtraItemIn)
							{
								if($respInsertExtraItemIn['data'][0]['code']=="SUCCESS")
								{
									$extraiteminsuraceId=$respInsertExtraItemIn['data'][0]['details']['id'];
									$crmLog.="extraitemId Extra item through extra item cron: ".$extraiteminsuraceId.", ";    
									$updatearray = array('flag'=>0);
									$whereClause="id='$id'"; 
									$result = $db->CommonUpdate($table1,$updatearray, $whereClause);
								}
								else
								{
									$crmLog.="Failed to insert Extra item through extra item cron != success";
									$success=false;
								}
							}
							else
							{
								$crmLog.="Failed to insert Extra item through extra item cron ";
								$success=false;
							}
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
		$id= "";
		crmLog($crmLog,$success);
		$crmLog = "";
			}  //end of while loop
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
			?>