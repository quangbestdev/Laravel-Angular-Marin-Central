<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Auth;
use DB;
use App\Service;
use App\Category;
use App\Subcategory;
use App\Dictionary;
use Illuminate\Support\Facades\Hash;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Validator;
class ServiceController extends Controller
{
    public $successStatus = 200;

    public function __construct(Request $request) {
        /*$value = $request->bearerToken();
	    if(!empty($value)) {
	     	$id= (new Parser())->parse($value)->getHeader('jti');
	     	$authid = DB::table('oauth_access_tokens')->where('id', '=', $id)->where('revoked', '=', false)->first()->user_id;
	     	if(!empty($authid) && $authid > 0) {
                $usertype = Auth::where('id', '=', $authid)->where('status' ,'=','active')->first()->usertype;
                if(empty($usertype) || $usertype != 'admin') {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
	     	} else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }*/
    }
  // get all services //
    public function index(Request $request) {
        $id = request('id');
        if(!empty($id) && (int)$id) {
            $servicedata = DB::table('services as sv')->select('sv.id as serviceid','subcat.subcategory_name','sv.service as itemName','sv.category','sv.subcategory','sv.status')->leftJoin('subcategory as subcat','sv.subcategory','=','subcat.id')->where('sv.category','=',$id)
            ->when($id, function($query) use ($id){
                if($id != 11) {
                     return $query->where('sv.status', '1');
                }
            })
            ->orderBy('sv.service','ASC')->get();
            if(!empty($servicedata)) {
                return response()->json(['success' => 'success','data' => $servicedata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    // get all services //
    public function getAllservice(Request $request) {
        $servicedata = Service::where('status','=','1')->select('id', 'service as itemName')->orderBy('id', 'ASC')->get();
        if(!empty($servicedata)) {
            return response()->json(['success' => 'success','data' => $servicedata], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    // add services //
    public function addService(Request $request) {
        $validate = Validator::make($request->all(), [
            'categoryid' => 'required',
            'service' => 'required',
            'subcatid' => 'required'
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $service    = new Service; 
        $authid = 0;
        $service->service = request('service');
        $service->category = request('categoryid');
        if (!empty(request('subcatid')) && (int)request('subcatid')) {
            $service->subcategory = request('subcatid');
        } else {
            $service->subcategory = 0;
        }
        $service->status = '1';
        if($service->save()) {
            $DictionaryData = new Dictionary;
            $DictionaryData->word = request('service');
            if($DictionaryData->save()) {
            }
            $serviceid = $service->id;
            $categoryid = request('categoryid');
            $allCategory = DB::select("SELECT id,subcategory_name,category_id from subcategory where status = '1' and category_id=".$categoryid."ORDER BY subcategory_name ASC");
            $allServices = DB::select("SELECT id,COALESCE('service') as type,service as name,subcategory from services where status = '1' AND category=".$categoryid." ORDER BY subcategory ASC");
            // Service::select('id','')->where('category',$id)->get();
            if(!empty($allCategory)) {
                $category = [];
                $finalArr = [];
                $count = 0;
                foreach ($allCategory as $key => $value) {
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
                            $finalArr[$count]['subcategory'] = $sval->subcategory;  
                            $count++;
                        }
                    }       
                }
                $category = Category::find($categoryid);
                $category->serviceslist = json_encode($finalArr);
                $category->save();
            } else {
                $category = Category::find($categoryid);
                $category->serviceslist = json_encode($allServices);
                $category->save(); 
            }
            return response()->json(['success' => true], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    // change service status //
    public function changeStatus(Request $request) {
        $status = request('status');
        $serviceid = (int)request('id');
        if(!empty($authid) && $authid > 0) {
            if($status == '1') {
                $updated = Service::where('id', '=', $serviceid)->update(['status' => '0']);
                if($updated) {
                    return response()->json(['success' => 'success','serviceid' => $serviceid], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            } else {
                $updated = Service::where('id', '=', $serviceid)->update(['status' => '1']);
                if($updated) {
                    return response()->json(['success' => 'success','serviceid' => $serviceid], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            }
        }
    }

    // edit servicer //
    public function editService(Request $request) {
        $validate = Validator::make($request->all(), [
            'service' => 'required',
            'id' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $service    = array(); 
        $updated = 0;
        $serviceId = (int)request('id');
        $service['service'] = request('service');
        if (!empty(request('subcatid')) && (int)request('subcatid')) {
            $service['subcategory'] = request('subcatid');
        } else {
            $service['subcategory'] = 0;
        }
        $updated =  Service::where('id', '=',$serviceId)->update($service);
        if($updated) {
            $checkIfExist = Dictionary::whereRAw("LOWER(word) ='".strtolower(request('service'))."'")->count();
            if(!$checkIfExist) { 
                $DictionaryData = new Dictionary;
                $DictionaryData->word = request('service');
                if($DictionaryData->save()) {
                }   
            }
            $category = Service::select('category')->where('id', '=',$serviceId)->first(); 
            $categoryid = $category->category;
            $allCategory = DB::select("SELECT id,subcategory_name,category_id from subcategory where status = '1' and category_id=".$categoryid."ORDER BY subcategory_name ASC");
            $allServices = DB::select("SELECT id,COALESCE('service') as type,service as name,subcategory from services where status = '1' AND category=".$categoryid." ORDER BY subcategory ASC");
            // Service::select('id','')->where('category',$id)->get();
            if(!empty($allCategory)) {
                $category = [];
                $finalArr = [];
                $count = 0;
                foreach ($allCategory as $key => $value) {
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
                            $finalArr[$count]['subcategory'] = $sval->subcategory;  
                            $count++;
                        }
                    }       
                }
                $category = Category::find($categoryid);
                $category->serviceslist = json_encode($finalArr);
                $category->save();
            } else {
                $category = Category::find($categoryid);
                $category->serviceslist = json_encode($allServices);
                $category->save(); 
            }
            return response()->json(['success' => true], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);    
        }
    }

    // get service details //
    public function getServiceDetail(Request $request) {
        $serviceid = request('id');
        if(!empty($serviceid) && $serviceid > 0) {
            $servicedata = Service::where('id', '=',$serviceId)->first();
            if(!empty($servicedata)) {
                return response()->json(['success' => 'success','data' => $servicedata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }
    // get service details //
    public function getAllServiceCategory() {
        $category = Category::select('id','categoryname as itemName','subcategory','status','created_at')->where('status','=','1')->orderBy('itemName', 'ASC')->get();
        if(!empty($category)) {
            return response()->json(['success' => 'success','data' => $category], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    //get Services by Id 
    public function getAllServiceByCategoryId(Request $request) {
        $categoryId = request('id');
        if(!empty($categoryId) && $categoryId > 0) {
            $servicedata = Service::where('category', '=',$categoryId)->get();
            if(!empty($servicedata)) {
                return response()->json(['success' => 'success','data' => $servicedata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }    
    }
    // Add category//
    public function addCategory(Request $request) {
        $validate = Validator::make($request->all(), [
            'category' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }        
        $category = new Category;
        $category->categoryname = request('category');
        if (request('subcategorycheck') == 'true') {
            $category->subcategory = '1';    
        } else {
            $category->subcategory = '0';
        }
        
        $category->status = '1';
        if($category->save()) {
            $category = $category->id;
            return response()->json(['success' => 'success','categoryId' => $category], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);    
        }
    }  
    // Edit category//
    public function editCategory(Request $request) {
        $validate = Validator::make($request->all(), [
            'categoryname' => 'required',
            'id'       => 'required',
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }        
        $id = request('id');
        if(!empty($id) && (int)$id) {
            $category = Category::find($id);
            $category->categoryname = request('categoryname');
            if($category->save()) {
                $category = $category->id;
                return response()->json(['success' => 'success','categoryId' => $category], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
        } else {
                return response()->json(['error'=>'networkerror'], 401);
        }
    }  

    //Get Category detail//
    public function getCategoryDetail(Request $request) {    
        $id = request('id');
        if(!empty($id) && (int)$id) {
            $category = Category::find($id);
            if($category) {
                return response()->json(['success' => 'success','data' => $category], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
        } else {
                return response()->json(['error'=>'networkerror'], 401);
        }
    }
    //Delete Category//
    public function deleteCategory() {
       $id = request('id'); 
       if(!empty($id) && (int)$id) {
            $category = Category::find($id);
            if(!empty($category)) {
                $disableCatStaus = Category::where('id',$id)->update(['status'=>0]);
                $disable = Service::where('category','=',$id)->update(['status' => 0]);
                $subCatExist = SubCategory::where('category_id','=',$id);
                if ($subCatExist) {
                    $updateSubCat = SubCategory::select('id')->where('category_id','=',$id)->update(['status'=> 0]);
                } 
                return response()->json(['success' => 'success'], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }
 
    //Delete sevices//
    public function deleteService(Request $request) {
       $serviceId = request('id'); 
       if(!empty($serviceId) && (int)$serviceId) {
            $service = Service::find($serviceId);
            if(!empty($service) && $service->update(['status' => 0])) {
                return response()->json(['success' => 'success','data' => $service], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
        } else {
                return response()->json(['error'=>'networkerror'], 401);
        }
    }

    //Get service data//
    public function getServiceData(Request $request) {
        $serviceId = request('id');
        if(!empty($serviceId) && (int)$serviceId) {
            $service = Service::find($serviceId);
            if(!empty($service)) {
                return response()->json(['success' => 'success','data' => $service], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
        }
    }

    // get all services //
    public function getAllDistinctservice(Request $request) {
        $servicedata = DB::table('category')->where('status','=','1')->select('categoryname as itemName','id')->where('id','!=','11')->get()->toArray(); 
        foreach ($servicedata as $key => $value) {
            $servicedata[$key]->checked = FALSE;
        }
        if(!empty($servicedata)) {
            return response()->json(['success' => 'success','data' => $servicedata], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    // get all services //
    public function getAllservicewithid(Request $request) {
        $servicedata = DB::table('services')->where('status','=','1')->select('service','id')->orderBy('service', 'ASC')->get()->toArray(); 
        $serviceArr = [];
        if(!empty($servicedata)) {
            foreach ($servicedata as $value) {
                $serviceArr[$value->id] = $value->service;
            }
        }
        if(!empty($serviceArr)) {
            return response()->json(['success' => 'success','data' => $serviceArr], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    //Get categories and ids
    public function getallcategoriesId() {
        $services = Category::where('status','1')->get();
        if(!empty($services)) {
            $service = [];
            foreach ($services as $key => $value) {
                $service[][$value->categoryname] = $value->id;
            }
            return response()->json(['success' => 'success','data' => $service], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    //get Services by category name 
    public function getServiceByCategoryName(Request $request) {
        $categoryName = request('category');
        if(!empty($categoryName) && $categoryName != '') { 
            $servicedata = DB::table('category as cat')->select('sv.service')->Join('services as sv','sv.category','=','cat.id')->where('cat.categoryname','ILIKE',"%".$categoryName."%")->where('cat.status', '=','1')->where('sv.status', '=','1')->groupBy('service')->get();
            if(!empty($servicedata)) {
                return response()->json(['success' => 'success','data' => $servicedata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }    
    }
    // get sub-category list by category id
    public function getallsubcategorybyid(Request $request) {
        $categoryid = request('categoryid');
        if (!empty($categoryid) && $categoryid != '') {
            $subCategoryData = DB::table('subcategory')->select('id','subcategory_name','status','created_at')->where(['category_id'=>$categoryid,'status'=>'1'])->orderBy('subcategory_name','ASC')->get();
            if(!empty($subCategoryData)) {
                return response()->json(['success' => 'success','data' => $subCategoryData], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }   
    }

    // delete subcategory
    public function deletesubcategory(Request $request) {
        $subcatId = request('subcatid'); 
        $catId = request('catid'); 
        if(!empty($subcatId) && (int)$subcatId) {
            $result = Subcategory::where(['id'=>$subcatId,'category_id' => $catId])->update(['status' => 0]);
            if(!empty($result)) {
                return response()->json(['success' => 'success'], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
        } else {
                return response()->json(['error'=>'networkerror'], 401);
        }
    }

    // get sub-category detail or edit
    public function getsubcategorydetailbyid(Request $request) {
        $subcategoryid = request('subcatid');
        if(!empty($subcategoryid) && (int)$subcategoryid) {
            $subcategory = Subcategory::find($subcategoryid);
            if($subcategory) {
                return response()->json(['success' => 'success','data' => $subcategory,'catid'=> $subcategoryid], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
        } else {
                return response()->json(['error'=>'networkerror'], 401);
        }
    }

    // edit sub-category
    public function editsubcategory(Request $request) {
        $validate = Validator::make($request->all(), [
            'subcatid' => 'required',
            'subcategoryname' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $service    = array(); 
        $updated = 0;
        $serviceId = (int)request('subcatid');
        $service['subcategory_name'] = request('subcategoryname');
        $updated =  Subcategory::where('id', '=',$serviceId)->update($service);
        if($updated) {
            return response()->json(['success' => 'success','service' => $serviceId], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    // add sub-category
    public function addsubcategory(Request $request) {
        $validate = Validator::make($request->all(), [
            'categoryid' => 'required',
            'subcategory' => 'required'
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $subcategory    = new SubCategory;
        $subcategory->subcategory_name = request('subcategory');
        $subcategory->category_id = request('categoryid');
        $subcategory->status = '1';
        if($subcategory->save()) {
            $serviceid = $subcategory->id;
            return response()->json(['success' => 'success','serviceid' => $serviceid], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    // get service for sub-category
    public function getservicesforSubcat() {
        $catid = request('catid');
        $subcatid = request('subcatid');
        if(!empty($catid) && (int)$catid) {
            $servicedata = Service::select('id', 'service as itemName','status')
                ->orderBy('id', 'ASC')
                ->where('category','=',$catid)
                ->where('subcategory','=',$subcatid)
                ->where('status','=','1')
                ->get();
            if(!empty($servicedata)) {
                return response()->json(['success' => 'success','data' => $servicedata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    // get sub category list from category id when adding service
    public function getSubCatListFromCatId(Request $request) {
        $catid = request('catid');
        if(!empty($catid) && (int)$catid) {
            $servicedata = SubCategory::select('id', 'subcategory_name as viewValue')
                ->orderBy('id', 'ASC')
                ->where('category_id','=',$catid)
                ->get();
            if(!empty($servicedata)) {
                return response()->json(['success' => 'success','data' => $servicedata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }

    }

     // get sub-category list by service id
    public function getSubCatByServiceId(Request $request) {
        $servid = request('servid');
        if(!empty($servid) && (int)$servid) {
            $serviceid = Service::select('category')->where('id','=',$servid)->first();
            if (!empty($serviceid)) {
                $subcatlist = Subcategory::select('subcategory_name','id as subcatid')->where('category_id','=',$serviceid->category)->get();
                if(!empty($subcatlist)) {
                    return response()->json(['success' => 'success','data' => $subcatlist], $this->successStatus);
                } else {
                    return response()->json(['error'=>'networkerror'], 401); 
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }



}

