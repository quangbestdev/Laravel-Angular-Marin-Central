<?php

use Illuminate\Database\Seeder;

class ServicesList extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {	
    	$allcategory = DB::select("SELECT id,categoryname from category where categoryname != 'Others'");
        $showFinalArr = [];
        if(!empty($allcategory)) {
        	foreach ($allcategory as $ckey => $cval) {
        		$id = $cval->id;
        		$allSubCategory = DB::select("SELECT id,subcategory_name,category_id from subcategory where status = '1' and category_id=".$id."ORDER BY subcategory_name ASC");
				$allServices = DB::select("SELECT id,COALESCE('service') as type,COALESCE(false) as checked,service as name,subcategory,category from services where status = '1' AND category=".$id." ORDER BY subcategory ASC");
				if(!empty($allSubCategory)) {
					$finalArr = [];
					$count = 0;
					foreach ($allSubCategory as $key => $value) {
						// if($id == '2'){print_r($value);die;}
						$finalArr[$count]['id'] = $value->id;
						$finalArr[$count]['type'] = 'subcategory';
						$finalArr[$count]['name'] = $value->subcategory_name;
						$count++;
						foreach ($allServices as $skey => $sval) {
							if($sval->subcategory == $value->id){
								$finalArr[$count]['id'] = $sval->id;
								$finalArr[$count]['type'] = 'service';
								$finalArr[$count]['name'] = $sval->name;
								$finalArr[$count]['checked'] = false;
								$finalArr[$count]['disable'] = false;	
								$count++;
							}
						}
						$category = Category::find($id);
				        $category->serviceslist = json_encode($finalArr);
				        $category->save();
					}
        		} else {
        			$category = Category::find($id);
			        $category->serviceslist = json_encode($allServices);
			        $category->save();	
        		}
        	}
        }
    }
}
