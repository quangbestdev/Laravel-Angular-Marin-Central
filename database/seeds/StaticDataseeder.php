<?php

use Illuminate\Database\Seeder;

class StaticDataseeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {	
  //   	$currentTime = date("Y-m-d H:i:s");
  //       $categoryArr = [
  //       	['categoryname' => 'Yachts','status' =>'1','created_at' => $currentTime, 'updated_at' => $currentTime],
  //       	['categoryname' => 'Boats','status' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
  //       	['categoryname' => 'Marinas','status' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
  //       	['categoryname' => 'Dealers','status' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
  //       	['categoryname' => 'Supply Stores','status' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
  //       	['categoryname' => 'Fishing Charters','status' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
  //       	['categoryname' => 'Service & Repair','status' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
  //       	['categoryname' => 'Boat Cleaning','status' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
  //       	['categoryname' => 'Others','status' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
  //   	];    
  //       DB::table('category')->delete();
		// foreach($categoryArr as $category){
		// 	DB::table('category')->insert($category);
		// }
    
		// $serviceArr = [
		// 	['service' => 'Outboard Repair','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Outboard Repair','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Outboard Repair','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Outboard Repair','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Outboard Repair','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Inboard Repair','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Inboard Repair','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Inboard Repair','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Inboard Repair','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Inboard Repair','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],



		// 	['service' => 'Generator Repair','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Generator Repair','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Generator Repair','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Generator Repair','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Generator Repair','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Marine Electronics','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Marine Electronics','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Marine Electronics','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Marine Electronics','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Marine Electronics','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Electrical Repair','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Electrical Repair','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Electrical Repair','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Electrical Repair','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Electrical Repair','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'AC Repair','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'AC Repair','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'AC Repair','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'AC Repair','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'AC Repair','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Mobile Mechanics','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Mobile Mechanics','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Mobile Mechanics','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Mobile Mechanics','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Mobile Mechanics','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Diesel Mechanics','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Diesel Mechanics','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Diesel Mechanics','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Diesel Mechanics','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Diesel Mechanics','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Fuel Deliveries','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Fuel Deliveries','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Fuel Deliveries','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Fuel Deliveries','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Fuel Docks','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Fuel Docks','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Fuel Docks','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Fuel Docks','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Pump out Services','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Pump out Services','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Pump out Services','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Pump out Services','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Towers and T-Tops','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Towers and T-Tops','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Towers and T-Tops','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Towers and T-Tops','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Towers and T-Tops','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
			

		// 	['service' => 'Boat Cleaning','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Cleaning','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Cleaning','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Cleaning','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Cleaning','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Cleaning','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Cleaning','category' => '8','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],


		// 	['service' => 'Bottom Cleaning','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Bottom Cleaning','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Bottom Cleaning','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Bottom Cleaning','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Bottom Cleaning','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Bottom Cleaning','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Bottom Cleaning','category' => '8','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Waxing','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Waxing','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Waxing','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Waxing','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Waxing','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Waxing','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Waxing','category' => '8','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Varnishing','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Varnishing','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Varnishing','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Varnishing','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Varnishing','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Varnishing','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Varnishing','category' => '8','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Painting','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Painting','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Painting','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Painting','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Painting','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Painting','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Painting','category' => '8','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
			
		// 	['service' => 'Teak','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Teak','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Teak','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Teak','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Teak','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Teak','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Teak','category' => '8','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Non-skid Repair','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Non-skid Repair','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Non-skid Repair','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Non-skid Repair','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Non-skid Repair','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Non-skid Repair','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],


		// 	['service' => 'Custom Wraps','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Custom Wraps','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Custom Wraps','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Custom Wraps','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Custom Wraps','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Custom Wraps','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],


		// 	['service' => 'Canvas and Upholstery','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Canvas and Upholstery','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Canvas and Upholstery','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Canvas and Upholstery','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Canvas and Upholstery','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Canvas and Upholstery','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Canvas and Upholstery','category' => '8','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
			

		// 	['service' => 'Boat Lifts','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Lifts','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Lifts','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Lifts','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Lifts','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Lifts','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Dock Repairs','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Dock Repairs','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Dock Repairs','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Dock Repairs','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Dock Repairs','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Boat Trailers','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Trailers','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Trailers','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Trailers','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Trailers','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Trailer Rentals','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Trailer Rentals','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Trailer Rentals','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Trailer Rentals','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Trailer Rentals','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Derelict Boats','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Derelict Boats','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Derelict Boats','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
				
		// 	['service' => 'Tow Boats','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Tow Boats','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Tow Boats','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
			
			
		// 	['service' => 'Jet Ski Repair','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Jet Ski Repair','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Jet Ski Repair','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Jet Ski Repair','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Jet Ski Repair','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Jet Ski Repair','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Jet Ski Dealers','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Jet Ski Dealers','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Jet Ski Dealers','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Jet Ski Dealers','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Jet Ski Dealers','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Boat Delivery Service','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Delivery Service','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Delivery Service','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Delivery Service','category' => '7','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Boat Dealers','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Dealers','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Dealers','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Dealers','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Boat Tours','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Tours','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
			

		// 	['service' => 'Boat Brokers','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Brokers','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Brokers','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Brokers','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Yacht Charters','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
			
		// 	['service' => 'Yacht Dealers','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Yacht Dealers','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Yacht Captains','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Yacht Management','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Crew','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Stews','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Day Workers','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Chefs','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Captains','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
			
		// 	['service' => 'Dockage','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Dockage','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Dockage','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Dockage','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// 	['service' => 'Boat Slips','category' => '1','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Slips','category' => '2','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Slips','category' => '3','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Boat Slips','category' => '4','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],


		// 	['service' => 'Supplies','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'parts','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Fishing Gear','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],
		// 	['service' => 'Rod and Reel Repair','category' => '5','status' => '1','added_by' => '1','created_at' => $currentTime, 'updated_at' => $currentTime],

		// ];	
		// DB::table('services')->delete();
		// foreach($serviceArr as $service){
		// 	DB::table('services')->insert($service);
		// }

    }
}
?>
