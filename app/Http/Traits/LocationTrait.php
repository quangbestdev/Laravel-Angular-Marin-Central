<?php 
namespace App\Http\Traits;
use Geocoder;
trait LocationTrait {
    public function getGeoLocation($location) {
        $output = app('geocoder')->geocode($location)->dump('geojson');
        if(empty($output->toArray())) {
            $longitude = 0;
            $latitude = 0;
        } else {
            $arrayCord = json_decode($output->toArray()[0]);
            if(!empty($arrayCord)) {
                $longitude = $arrayCord->geometry->coordinates[0];
                $latitude = $arrayCord->geometry->coordinates[1];
            } else {
                $longitude = 0;
                $latitude = 0;
            }
        }
        $data['longitude'] = $longitude;
        $data['latitude'] = $latitude;
    	return $data;
	}

    public function zip($location) {
        $locArr['city'] = $locArr['state'] = $locArr['zipcode'] = '';
        $output = app('geocoder')->geocode($location)->dump('geojson');
        if(!empty($output)) {
            $arrayCord = json_decode($output->toArray()[0]);
            // echo '<pre>';print_r($output);die;
            if(isset($arrayCord->properties) && isset($arrayCord->properties->adminLevels)) {
                foreach ($arrayCord->properties->adminLevels as $level) {
                    if($level->level == 1) {
                       $locArr['state'] =  $level->name;
                    }
                }
                if(isset($arrayCord->properties->locality)) {
                    $locArr['city'] = $arrayCord->properties->locality;
                }
                if(isset($arrayCord->properties->postalCode)){
                    $locArr['zipcode'] = $arrayCord->properties->postalCode;
                }
               
            } else {
                return $locArr;
            }
        }
        return $locArr;
    }
}
?>