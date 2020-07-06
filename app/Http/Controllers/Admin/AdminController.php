<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Auth;
use App\Service;
use DB;
use App\Companydetail;
use App\Geolocation;
use App\Contactus;
use App\Talentdetail;
use App\Jobtitles;
use App\Advertisement;
use App\Dictionary;
use App\dummy_registration;
use App\ServiceRequestReviews;
use App\Jobs;
use App\Contacted_Talent;
use App\Quoterequests;
use Illuminate\Support\Facades\Hash;
use Lcobucci\JWT\Parser;
use Illuminate\Support\Facades\Validator;
use Stripe\Error\Card;
use Cartalyst\Stripe\Stripe;
use Exception;
use App\User;
use App\Blogs;
use App\Category;
use Carbon;
use App\Http\Traits\LocationTrait;
use App\User_request_services;
use App\Messages;
use App\Yachtdetail;
use Braintree_Subscription;
use App\Http\Traits\ZapierTrait;
class AdminController extends Controller
{
    public $successStatus = 200;
    use LocationTrait;
    use ZapierTrait;
    public function __construct(Request $request) {
        $value = $request->bearerToken();
	    if(!empty($value) && $value != 'statics') {
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
           return response()->json(['error'=>'Unauthorised'], 401); 
        }
    }
    // get all regular users //
    public function manageContactUs(Request $request) {

        $searchString = request('searchString');
        $statusFilter = request('statusFilter');
        $page = request('page');
        $orderBy = request('order');
        $reverse = request('reverse');
        $order = ($reverse == 'false')?'ASC':'DESC';
        if($page == 0 || $page == null || !(int)$page) {
            $page = 0;
        }
        $limit = 30;
        $offset = 0;
        if($page > 0) {
            $offset = ($page - 1)*$limit;
        }

        $whereQuery = '';  
        if($searchString != '') {
            $whereQuery = "email LIKE '%".$searchString."%'"; 
        }

        $cQuery = Contactus::where('status','1')
        ->where(function($contact) use ($statusFilter)  {
            if(!empty($statusFilter) && $statusFilter != 'all') {
                $status = ($statusFilter == 'read')?'1':'0';
                $contact->where('is_read',$status);    
            }
         });
        if($whereQuery != '') {
           $cQuery =  $cQuery->whereRaw($whereQuery);
        }
        $totalrecords = $cQuery->count();
        $query = Contactus::where('status','1')
        ->where(function($contact) use ($statusFilter)  {
            if(!empty($statusFilter) && $statusFilter != 'all') {
                $status = ($statusFilter == 'read')?'1':'0';
                $contact->where('is_read',$status);    
            }
         });
        if($whereQuery != '') {
           $query =  $query->whereRaw($whereQuery);
        }
        $contact = $query->skip($offset)
           ->take($limit)
           ->get();
        if(!empty($contact)) {
            return response()->json(['success' => true,'data' => $contact,'totalrecords' => $totalrecords], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    //Get contact us detail
    public function getContactusDetail(Request $request) {
        $id = request('id');
        if(!empty($id) && (int)$id) {
            $contact = Contactus::where('id',$id)->first();
            if(!empty($contact)) {
                if(isset($contact->is_read) && $contact->is_read == '0') {
                    Contactus::where('id',$id)->update(['is_read' => '1']);
                }
                return response()->json(['success' => true,'data' => $contact], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }    
        } else {
                return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    //Change status contactus
    public function deletContactus(Request $request) {
        $id = request('id');
        if(!empty($id) && (int)$id) {
            $contact = Contactus::where('id',$id)->update(['status' => '0']);
            if(!empty($contact)) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }    
        } else {
                return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    //Get all jobtitles
    public function getAllJobTitles() {
        $titles = Jobtitles::select('id','title','status','created_at')->where('status','1')->orderBy('created_at','DESC')->get();
        if(!empty($titles)) {
            return response()->json(['success' => true,'data' => $titles], $this->successStatus); 
        } else {
            return response()->json(['success' => false], $this->successStatus);
        }
    } 
    //Get JobTitle details
    public function getJobTitlesDetail(Request $request) {
        $id = request('id');
        if(!empty($id) && (int)$id) {
            $titles = Jobtitles::select('id','title','status')->where('status','1')->where('id',$id)->first();
            if(!empty($titles)) {
                return response()->json(['success' => true,'data' => $titles], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    //Change jobtitle status
    public function changeStatus(Request $request) {
        $id = request('id');
        if(!empty($id) && (int)$id) {
            $titles = Jobtitles::where('id',$id)->update(['status' => '0']);
            if(!empty($titles)) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    //Add and edit job titles
    public function addJobTitle(Request $request) {
        $validate = Validator::make($request->all(), [
            'jobtitle' => 'required'
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $id = request('id');
        if(!empty($id)) {
            $jobtitle = Jobtitles::find($id);
            $checkIfExist = Dictionary::whereRAw("LOWER(word) ='".strtolower(request('jobtitle'))."'")->count();
            if(!$checkIfExist) { 
                $DictionaryData = new Dictionary;
                $DictionaryData->word = request('jobtitle');
                $DictionaryData->save();
            }
        } else {
            $jobtitle = new Jobtitles;
            $jobtitle->status = '1';
            $DictionaryData = new Dictionary;
            $DictionaryData->word = request('jobtitle');
            $DictionaryData->save();
        }
        $jobtitle->title = request('jobtitle');
        if($jobtitle->save()) {

            return response()->json(['success' => true], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }   
    }


    //Get All others Titles
    public function getAllOtherTitle(Request $request) {
        $titles = Talentdetail::select('authid','otherjobtitle')->where('jobtitleid','1')->where('status','active')->orderBy('created_at','DESC')->get();
        if(!empty($titles)) {
            return response()->json(['success' => true,'data' => $titles], $this->successStatus); 
        } else {
            return response()->json(['success' => false], $this->successStatus);
        }
    }
    //Assign title to existing jobtitle
    public function assignJobtitle(Request $request) {
        $assignId =  request('assignId');    
        $othertitle = request('othertitleId');
        if(!empty($assignId) && !empty($othertitle)) {
            $othertitle = Talentdetail::where('authid',$othertitle)->first();
            if(!empty($othertitle)) {
                $other = $othertitle->otherjobtitle;
                $titles = Talentdetail::where('jobtitleid','1')->where('otherjobtitle','=',$other)->update(['jobtitleid' => $assignId]);
                if(!empty($titles)) {
                    return response()->json(['success' => true,'data' => $titles], $this->successStatus); 
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

    //Approve Other titles 
    public function approveOtherTitle(Request $request) {
        $othertitle = request('title');
        if(!empty($othertitle)) {
            $jobtitles = new Jobtitles;
            $jobtitles->title = $othertitle;
            $jobtitles->status = '1';
            if($jobtitles->save()) {
                $insertId = $jobtitles->id;
                if($insertId) {
                    $titles = Talentdetail::where('jobtitleid','1')->where('otherjobtitle','=',$othertitle)->update(['jobtitleid' => $insertId]);
                    if(!empty($titles)) {
                        return response()->json(['success' => true,'data' => $titles], $this->successStatus);            
                    } else {
                        return response()->json(['error'=>'networkerror'], 401);    
                    }
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
    //Get all Advertisements
    public function getallAds(Request $request) {
        $data = [];
        $data = DB::table('advertisement')->where('status','1')->orderBy('created_at','DESC')->get();
        if(!empty($data)) {
            return response()->json(['success' => true,'data' => $data], $this->successStatus);
        } else {
            return response()->json(['success' => false,'data' => []], $this->successStatus);
        }
    } 
    //Delete Advertisement 
    public  function deleteAdvertisement(Request $request) {
        $id = request('id');
        if(!empty($id) && (int)$id) {
            $delete = DB::table('advertisement')->where('id',$id)->update(['status'=>'0']);
            if($delete) {
                return response()->json(['success' => true], $this->successStatus);
            }  else {
                return response()->json(['error'=>'networkerror'], 401);
            } 
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }


    //add Ads
    public  function addAdvertisement(Request $request) {
        $validate = Validator::make($request->all(), [
            'name' => 'required',
            'link' => 'required',
            'listingPages' => 'required',
            'selectedCity' => 'required',
            'selectedState' => 'required',
            'selectedZipcode' => 'required',
            // 'state' => 'required',
            // 'city' => 'required',
            // 'zipcode' => 'required',
            // 'country' => 'required',
        ]);
        // echo strtotime('Sat Feb 23 2019 23:55:35 GMT+0530');
        $time = request('time');
        
        if(!empty($time)) {
            $timeArr = explode(',', $time);
            $start = trim(substr($timeArr[0], 0, strpos($timeArr[0], 'GMT')));
            $end = trim(substr($timeArr[1], 0, strpos($timeArr[1], 'GMT')));
            $start_time = date('Y-m-d H:i:s',strtotime($start));
            $end_time = date('Y-m-d H:i:s',strtotime($end));
        } else {
            $start_time = NULL;
            $end_time = NULL;
        }
        $selectedState = request('selectedState');

        $selectedCity = request('selectedCity');
        $selectedZipcode = request('selectedZipcode');
        $keyword = request('keyword');
        if ($validate->fails()) {
            print_r($validate->messages());
           return response()->json(['error'=>'validationError'], 401); 
        }   
        $horImage = request('horImage');
        $horImage_two = request('horImage2');
        $verImage = request('verImage'); 
        $verImage_two = request('verImage2'); 
        if(empty($horImage) && empty($verImage) && empty($verImage_two) && empty($horImage_two)) {
           return response()->json(['validationError'=>'Please uplaod aleast one advertisement.'], 401);    
        }
        //get Location
        // $address = request('address');
        // $locAddress = ((isset($address) && $address !='') ? request('address').' ': '');    
        // $location = $locAddress.request('city').' '.request('zipcode').' '.request('state').' ,'.request('country');

        // $output = $this->getGeoLocation($location); //Get Location from location Trait
        $longitude = 0;
        $latitude = 0;

        $accesspages = json_decode(request('listingPages'));
        $advertisement = new Advertisement;
        $advertisement->name = request('name');
        $advertisement->link = request('link');
        $advertisement->vertical_image = request('verImage');
        $advertisement->horizontal_image = request('horImage');
        $advertisement->horizontal_image_bottom = request('horImage2');
        $advertisement->vertical_image_bottom = request('verImage2');
        $advertisement->zipcode = NULL;
        $advertisement->state = NULL;
        $advertisement->city = NULL;
        $advertisement->country = 'United States';
        $advertisement->address = NULL;
        $advertisement->longitude = $longitude;
        $advertisement->latitude = $latitude;
        $advertisement->pages = json_encode($accesspages,JSON_UNESCAPED_SLASHES);
        $advertisement->selectedstate = $selectedState;
        $advertisement->selectedcity = $selectedCity;
        $advertisement->selectedzipcode = $selectedZipcode;
        $advertisement->keywords = request('keyword');
        $advertisement->start_time = $start_time;
        $advertisement->end_time = $end_time;
        $advertisement->status = '1';
        if($advertisement->save()) {
            return response()->json(['success' => true], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    //add Ads
    public  function editAdvertisement(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            'link' => 'required',
            'listingPages' => 'required' 
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }    
        $time = request('time');
        if(!empty($time)) {
            $timeArr = explode(',', $time);
            $start = trim(substr($timeArr[0], 0, strpos($timeArr[0], 'GMT')));
            $end = trim(substr($timeArr[1], 0, strpos($timeArr[1], 'GMT')));
            $start_time = date('Y-m-d H:i:s',strtotime($start));
            $end_time = date('Y-m-d H:i:s',strtotime($end));
        } else {
            $start_time = NULL;
            $end_time = NULL;
        }
        $selectedState = request('selectedState');
        
        $selectedCity = request('selectedCity');
        $selectedZipcode = request('selectedZipcode');
        $keyword = request('keyword');
        $horImage = request('horImage');
        $horImage_two = request('horImage2');
        $verImage = request('verImage'); 
        $verImage_two = request('verImage2'); 
        
        if(empty($horImage) && empty($verImage) && empty($verImage_two) && empty($horImage_two)) {
           return response()->json(['validationError'=>'Please uplaod aleast one advertisement.'], 401);    
        }
        $id = (int)request('id');
        $accesspages = json_decode(request('listingPages'));
        $advertisement = Advertisement::find($id);
        $advertisement->name = request('name');
        $advertisement->link = request('link');
        $advertisement->vertical_image = request('verImage');
        $advertisement->horizontal_image = request('horImage');
        $advertisement->horizontal_image_bottom = request('horImage2');
        $advertisement->pages = json_encode($accesspages,JSON_UNESCAPED_SLASHES);
        $advertisement->zipcode = NULL;
        $advertisement->state = NULL;
        $advertisement->city = NULL;
        $advertisement->country = 'United States';
        $advertisement->address = NULL;
        $advertisement->selectedstate = $selectedState;
        $advertisement->selectedcity = $selectedCity;
        $advertisement->selectedzipcode = $selectedZipcode;
        $advertisement->keywords = request('keyword');
        if($start_time && $end_time) {
            $advertisement->start_time = $start_time;
            $advertisement->end_time = $end_time;
        }
        $advertisement->status = '1';
        if($advertisement->save()) {
            return response()->json(['success' => true], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    // get advertisment details //
    public function getAdvertisementDetail(Request $request) {
        $id = (int)request('id');
        if(!empty($id) && $id > 0) {
            $adversdata =Advertisement::where('id','=',$id)->where('status','=','1')->first();
            if(!empty($adversdata)) {
                return response()->json(['success' => true,'data' => $adversdata], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }

    public function addAdmin(Request $request) {
        $validate = Validator::make($request->all(), [
            'email' => 'bail|required|E-mail',
            'password' => 'required|confirmed',
            'admin_privilege' => 'required',
            'firstname' => 'required',
            'lastname' => 'required'
        ]);
        if ($validate->fails()) {
           return response()->json(['error'=>'validationError'], 401); 
        }
        $passoword = Hash::make(request('password'));
        $auth = new Auth;
        $auth->email = strtolower(request('email'));
        $auth->usertype = 'admin';
        $auth->adminsubtype = 'admin';
        $auth->status = 'active';
        $auth->ipaddress = $this->getIp();
        $auth->stepscompleted = '3';
        $auth->is_activated = '1';
        $auth->password = $passoword;
        $auth->accounttype = 'real';
        $auth->admin_privilege = request('admin_privilege');
        $auth->firstname_admin = request('firstname');
        $auth->lastname_admin = request('lastname');
        $auth->contact_email = request('contactemail');
        if($auth->save()) {
            return response()->json(['success' => true], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }
    //get all admin except super admin
    public function getAllAdmin() {
        $id = request('authid');
        if((int)$id) {
            $get_admin = Auth::select('id','email','admin_privilege','status','created_at')->where('usertype','=','admin')->where('status','active')->where('adminsubtype','admin')->where('id','!=',$id)->orderBy('created_at','DESC')->get();
            if(!empty($get_admin)) {
                return response()->json(['success' => true,'data' => $get_admin], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);         
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    //Change admin status
    public function deleteAdmin(Request $request){
        $id = request('id');
        if(!empty($id) && (int)$id) {
            $update = Auth::where('id',$id)->update(['status'=>'suspended']);
            if($update) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);
            }    
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }   
    //Get admin permissions
    public function getAdminDetails(Request $request) {
        $id = request('id');
        if(!empty($id) && (int)$id) {
            $data = Auth::select('email','admin_privilege','adminsubtype','firstname_admin','lastname_admin','contact_email')->where('id',$id)->first();
            if(!empty($data)) {
                $data->admin_privilege = json_decode($data->admin_privilege);
                return response()->json(['success' => true,'data' => $data], $this->successStatus);
            } else {
                 return response()->json(['error'=>'networkerror'], 401);  
            }
         
        } else {
           return response()->json(['error'=>'networkerror'], 401);  
        }
    }
    //edit Admin Permission and email
    public function editAdmin(Request $request){
        $id = request('userid');
        $email = request('email');
        $firstname = request('firstname');
        $lastname = request('lastname');
        $admin_privilege = request('admin_privilege');
        $contactemail = request('contactemail');
        if(!empty($id) && (int)$id && !empty($email) && !empty($admin_privilege) && !empty($firstname) && !empty($lastname)) {
            $data = Auth::where('id',$id)->update(['email'=>$email,'admin_privilege' => $admin_privilege,'firstname_admin' => $firstname, 'lastname_admin' => $lastname,'contact_email' => $contactemail]);
            if(!empty($data)) {
                return response()->json(['success' => true,'data' => $data], $this->successStatus);
            } else {
                 return response()->json(['error'=>'networkerror'], 401);  
            }            
        } else {
            return response()->json(['error'=>'networkerror'], 401);  
        }
    }
    //Get Blogs
    public function getBlogs(Request $request) {
        $blogs = Blogs::select('id','title','created_at','status')
            ->orderBy('created_at','DESC')
            ->where('status','!=','deleted')
            ->get();
        if(!empty($blogs)) {
            return response()->json(['success' => true,'data' => $blogs], $this->successStatus); 
        } else {
            return response()->json(['success' => false], $this->successStatus);
        }
    }
    // Add Blog
    public function addBlog(Request $request) {
        $validate = Validator::make($request->all(), [
            'title' => 'required',
            'description' => 'required',
            'blogimage' => 'required',
            'saveOrPublish' => 'required'
        ]);
        if ($validate->fails()) {
           // print_r($validate);
            return response()->json(['error'=>'validationError'], 401); 
        }

        $blogdetail = new Blogs; 
        $blogdetail->title  = request('title');
        $blogdetail->description   = request('description');
        $blogdetail->blogimage       = (request('blogimage'))? request('blogimage'):NULL;
        $blogdetail->videourl      = request('videourl') ? request('videourl'):NULL;
        if (request('saveOrPublish') == 'false') {
            $blogdetail->status     = 'created';
        } else {
            $blogdetail->status     = 'publish';
        }
        if($blogdetail->save()) {
            return response()->json(['success' => true], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    
    public function editBlog(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required',
            'title' => 'required',
            'description' => 'required',
            'blogimage' => 'required',
            'saveOrPublish' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $blogid = request('id');
        if (!empty($blogid) && $blogid > 0) {
            $detailArr['title'] = request('title');
            $detailArr['description'] = request('description');
            $detailArr['blogimage'] = request('blogimage') ? request('blogimage'): NULL;
            $detailArr['videourl'] = request('videourl')? request('videourl'): NULL;
            if (request('saveOrPublish') == 'false') {
                $detailArr['status']     = 'created';
            } else {
                $detailArr['status']   = 'publish';
            }
            $detailUpdate =  Blogs::where('id', '=', (int)$blogid)->update($detailArr);
            if($detailUpdate) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    public function deleteBlog(Request $request) {
        $blogid = (int)request('id');
        $updated = 0;
        if(!empty($blogid) && $blogid > 0 ) {
            $updated = Blogs::where('id', '=', $blogid)->update(['status' => 'deleted']);
            if($updated) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }
    }
    
    public function getBlogById(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $blogid = request('id');
        if(!empty($blogid) && $blogid > 0) {
            $blogs = Blogs::where('id',(int)$blogid)
                ->get();
            if(!empty($blogs)) {
                return response()->json(['success' => true,'data' => $blogs], $this->successStatus); 
            } else {
                return response()->json(['error' => 'networkerror'], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }
    public function checkAdminPrivilage(Request $request) {
       $validate = Validator::make($request->all(), [
            'authid' => 'required',
            'privilage' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }     
        $id = request('authid');
        $privilage = request('privilage');
        $checkSuperadmin = Auth::where('id',$id)->where('adminsubtype','superadmin')->first();
        if($checkSuperadmin) {
             return response()->json(['success' => true,'data' => ['allowed' => true,'superadmin' => true]], $this->successStatus);  
        } else {
            if($privilage == 'dashboard') {
              $data =   Auth::select('adminsubtype','admin_privilege')->where('id',$id)->first();
              if(!empty($data)) {
                return response()->json(['success' => true,'data' => $data], $this->successStatus);
              } else {
                return response()->json(['error'=>'networkerror'], 401);
              }
            } else {
               $jsonArr[] = $privilage;
               $data =   DB::select("SELECT id,admin_privilege from auths  where id = ".$id." and admin_privilege::jsonb @> '".json_encode($jsonArr)."'"); 
               if(!empty($data)) {
                    if(isset($data[0])) {
                        $admin_privilege = $data[0]->admin_privilege;
                        return response()->json(['success' => true,'data' => ['allowed' => true,'superadmin' => false],'permission' => $admin_privilege], $this->successStatus);
                    } else {
                        return response()->json(['error'=>'networkerror'], 401);    
                    }
               } else {
                return response()->json(['success' => true,'data' => ['allowed' => false,'superadmin' => false]], $this->successStatus);
               }
            }
        }        
    }
 
  //manage payment
  public function managePayment(Request $request) {
        $searchString = request('searchString');
        $page = request('page');
        $orderBy = request('order');
        $reverse = request('reverse');
        $order = ($reverse == 'false')?'ASC':'DESC';

        if($page == 0 || $page == null || !(int)$page) {
            $page = 0;
        }
        $limit = 30;
        $offset = 0;
        if($page > 0) {
            $offset = ($page - 1)*$limit;
        }
        $whereCompany = '';
        if(!empty($searchString)) {
            $searchString = strtolower($searchString);
            $whereCompany = "LOWER(companydetails.name) LIKE '%".$searchString."%'";         
        }
        $cQuery = DB::table('companydetails')
            ->Join('paymenthistory', 'paymenthistory.companyid', '=', 'companydetails.authid')
            ->where('companydetails.accounttype','!=','dummy')
            ->select('companydetails.name','paymenthistory.transactionfor','paymenthistory.amount','paymenthistory.status');
        if($whereCompany != '') {
            $cQuery = $cQuery->whereRaw($whereCompany);
        }
        $totalrecords = $cQuery->orderBy('paymenthistory.created_at', 'DESC')
            ->count();

        $query = DB::table('companydetails')
            ->Join('paymenthistory', 'paymenthistory.companyid', '=', 'companydetails.authid')
            ->where('companydetails.accounttype','!=','dummy')
            ->select('companydetails.name','paymenthistory.transactionfor','paymenthistory.amount','paymenthistory.status');

        if($orderBy == 'data.created_at') {
            $query = $query->orderBy('companydetails.created_at', 'DESC');
        } else if($orderBy == 'name') {
            $query = $query->orderBy('companydetails.name', $order);
        }
        if($whereCompany != '') {
            $query = $query->whereRaw($whereCompany);
        }
        $usersdata = $query->orderBy('paymenthistory.created_at', 'DESC')
            ->skip($offset)
            ->take($limit)
            ->get();
        $amount = DB::table('paymenthistory')->select(DB::raw('SUM(amount) as payment_amount'))->get();
        if(!empty($usersdata)) {
            return response()->json(['success' => true,'data' => $usersdata,'amountdata' => $amount,'totalrecords' => $totalrecords], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    // mange service request
    public function manageServiceRequest(Request $request) {
        $searchString = request('searchString');
        $filterStatus = request('filterStatus');
        $page = request('page');
        $orderBy = request('order');
        $reverse = request('reverse');
        $order = ($reverse == 'false')?'ASC':'DESC';

        if($page == 0 || $page == null || !(int)$page) {
            $page = 0;
        }
        $limit = 30;
        $offset = 0;
        if($page > 0) {
            $offset = ($page - 1)*$limit;
        }
        $whereQuery = '';
        if(!empty($searchString)) {
            $searchString = strtolower($searchString);
               $whereQuery = "WHERE LOWER(usr.title) LIKE '%".$searchString."%'";       
        }
        $order_by = '';
        if($orderBy == 'title') {
            $order_by = 'ORDER BY usr.title '.$order;
        } else {
            $order_by = 'ORDER BY usr.created_at DESC';
        }
        
        $status = '';
        if ($filterStatus == 'posted' || $filterStatus == 'completed' || $filterStatus == 'deleted') {
            if($whereQuery != '') {
                $whereQuery .= " AND usr.status = '".$filterStatus."'";
            } else {
                $whereQuery = " WHERE usr.status = '".$filterStatus."'";
            }
        }
         $totalrecords = DB::select("SELECT count(*) as count FROM (SELECT usr.id,usr.authid,usr.title,usr.description,usr.created_at,usr.status,
                COALESCE(NULLIF(ud.firstname,''), yd.firstname) as firstname,
                COALESCE(NULLIF(ud.lastname,''), yd.lastname) as lastname,
                yd.firstname as yacht_name,ud.firstname as user_name FROM users_service_requests as usr LEFT JOIN userdetails as ud on (usr.authid = ud.authid AND ud.status ='active') LEFT JOIN yachtdetail as yd ON (usr.authid = yd.authid AND yd.status = 'active') ".$whereQuery.") temp"); 
        
        $data = DB::select("SELECT usr.id,usr.authid,usr.title,usr.description,usr.created_at,usr.status,
                COALESCE(NULLIF(ud.firstname,''), yd.firstname) as firstname,
                COALESCE(NULLIF(ud.lastname,''), yd.lastname) as lastname,
                yd.firstname as yacht_name,ud.firstname as user_name FROM users_service_requests as usr LEFT JOIN userdetails as ud on (usr.authid = ud.authid AND ud.status ='active') LEFT JOIN yachtdetail as yd ON (usr.authid = yd.authid AND yd.status = 'active') ".$whereQuery." ".$order_by." LIMIT ".$limit." OFFSET ".$offset.""); 
        if(!empty($data) && isset($totalrecords[0])) {
            $totalrecords = $totalrecords[0]->count;
            return response()->json(['success' => true,'data' => $data,'totalrecords' => $totalrecords], $this->successStatus);
        } else {
            return response()->json(['success' => true,'data' => [],'totalrecords' => 0], $this->successStatus);
        }

    }

    // delete service request
    public function deleteServiceRequest(Request $request) {
        $servreqid = (int)request('id');
        $updated = 0;
        if(!empty($servreqid) && $servreqid > 0 ) {
            $updated = DB::table('users_service_requests')
                    ->where('id', '=', $servreqid)->update(['status' => 'deleted']);
            if($updated) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }
    }

    // get service rquest detail by id
    public function getServiceReqDetailById(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $reqid = request('id');
        if(!empty($reqid) && $reqid > 0) {
            $data = DB::table('users_service_requests')->where('id',(int)$reqid)
                ->first();
                if(!empty($data)) {
                    $table = '';
                    $type = '';
                    $authType = Auth::where('id',$data->authid)->first();
                    $userdetails = [];
                    if(isset($authType->usertype)) {
                        if($authType->usertype == 'regular') {
                            $table = 'userdetails';
                            $type = 'regular';       
                        } else {
                            $table = 'yachtdetail';
                            $type = 'yacht';
                        }
                    } else {
                        return response()->json(['error' => 'networkerror'], $this->successStatus);
                    }
                    if($table != '') {
                        $userdetails = DB::table($table)->where('authid',$data->authid)->first();    
                    }
                    $service = json_decode($data->services);
                    $allservices = Service::where('status','=','1')->whereOr('category','11')->select('id', 'service as itemName')->get()->toArray();
                    $newallservices = [];
                    foreach ($allservices as $val) {
                        $newallservices[$val['id']] = $val['itemName'];
                    }
                    $allCategory = Category::select('id','categoryname')->where('status','=','1')->get()->toArray();
                    $newallCategory = [];
                    foreach ($allCategory as $val) {
                        $newallCategory[$val['id']] = $val['categoryname'];
                    }
                    $newService = [];
                    $temCateArr = [];
                    foreach ($service as $catId => $SerIds) {
                        foreach ($SerIds as $sid => $sval) {
                            if(isset($newallservices[$sval])) {
                                $newService[] =  $newallservices[$sval];
                            }
                        }
                    }
                    $data->newservices =  $newService;
                    // $data->userdetails = $userdetails;
                    if($type == 'regular') {
                        $data->contact = $userdetails->country_code.$userdetails->mobile;

                    } else {
                        $data->contact = $userdetails->country_code.$userdetails->contact;  
                    }
                    $data->firstname = $userdetails->firstname;
                    $data->lastname = $userdetails->lastname;
                    $data->email = $authType->email;

                    if(!empty($data)) {
                        return response()->json(['success' => true,'data' => $data], $this->successStatus); 
                    } else {
                        return response()->json(['error' => 'networkerror'], $this->successStatus);
                    }
                } else {
                    return response()->json(['error' => 'networkerror'], $this->successStatus);
                }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    // get lead for service request by id
    public function showLeadPerRequest(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $servreqid = request('id');
        if(!empty($servreqid) && $servreqid > 0) {
            // $data = DB::table('users_service_requests as usr')
            //     ->select('rp.companyid','rp.status','cd.name','rp.id')
            //     ->leftJoin('request_proposals as rp', 'usr.id','=', 'rp.requestid')
            //     ->Join('companydetails as cd', 'rp.companyid','=', 'cd.id')
            //     ->where('cd.id','=','rp.companyid')
            //     ->where('usr.id','=',$servreqid)
            //     ->orderBy('rp.created_at', 'DESC')
            //     ->get();
            $data = DB::select("    SELECT rp.requestid, rp.companyid,rp.status,rp.id, cd.name FROM users_service_requests as usr LEFT JOIN request_proposals as rp ON usr.id = rp.requestid JOIN companydetails as cd ON rp.companyid = cd.id  WHERE  cd.id = rp.companyid AND usr.id = '".$servreqid."' AND rp.status != 'deleted'" );    
            if(!empty($data)) {
                return response()->json(['success' => true,'data' => $data], $this->successStatus); 
            } else {
                return response()->json(['error' => 'networkerror'], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }


    // delete business lead
    public function deleteAppliedBusinessLead() {
        $servreqid = (int)request('id');
        $updated = 0;
        if(!empty($servreqid) && $servreqid > 0 ) {
            $updated = DB::table('request_proposals')
                    ->where('id', '=', $servreqid)->update(['status' => 'deleted']);
            if($updated) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }
    }

    // get joblisting 
    public function ViewJoblist(Request $request) {
        $statusFilter = request('statusFilter');
        $searchString = request('searchString');
        $page = request('page');
        $orderBy = request('order');
        $reverse = request('reverse');
        $order = ($reverse == 'false')?'ASC':'DESC';
        if($page == 0 || $page == null || !(int)$page) {
            $page = 0;
        }
        $limit = 30;
        $offset = 0;
        if($page > 0) {
            $offset = ($page - 1)*$limit;
        }

        $whereQuery = '';    

        if(!empty($searchString)) {
            $searchString = strtolower($searchString);
            $whereQuery = "WHERE LOWER(jb.title) LIKE '%".$searchString."%'";
        }

        if ($statusFilter == 'active' || $statusFilter == 'expired' || $statusFilter == 'deleted') {
            if($whereQuery == '') {
                $whereQuery = "WHERE jb.status = '".$statusFilter."'";
            } else {
                $whereQuery .= " AND jb.status = '".$statusFilter."'";    
            }
        }
        $order_by = '';
        if($orderBy == 'title') {
            $order_by = 'ORDER BY jb.title '.$order;
        } else {
            $order_by = 'ORDER BY jb.created_at DESC';
        }
         
        $count = DB::select("SELECT count(*) FROM (SELECT jb.id,jb.authid,jb.title,jb.description,jb.created_at,jb.status,
                COALESCE(NULLIF(yd.lastname,''), yd.lastname) as lastname,
                yd.firstname as yacht_name,cd.name as company_name FROM jobs as jb LEFT JOIN companydetails as cd on jb.authid = cd.authid LEFT JOIN yachtdetail as yd ON jb.authid = yd.authid  ".$whereQuery." order by jb.created_at DESC) temp"); 

        $data = DB::select("SELECT jb.id,jb.authid,jb.title,jb.description,jb.created_at,jb.status,
                COALESCE(NULLIF(yd.lastname,''), yd.lastname) as lastname,
                yd.firstname as yacht_name,cd.name as company_name FROM jobs as jb LEFT JOIN companydetails as cd on jb.authid = cd.authid LEFT JOIN yachtdetail as yd ON jb.authid = yd.authid  ".$whereQuery." ".$order_by." LIMIT ".$limit." OFFSET ".$offset."");
        if(!empty($data)) {
            $totalrecords =  $count[0]->count;
            return response()->json(['success' => true,'data' => $data,'totalrecords' => $totalrecords], $this->successStatus);
        } else {
            return response()->json(['success' => true,'data' => [],'totalrecords' => 0], $this->successStatus);
        }
    }

    // delete jobs
    public function deleteJob(Request $request) {
        $jobid = (int)request('id');
        $updated = 0;
        if(!empty($jobid) && $jobid > 0 ) {
            $updated = DB::table('jobs')
                    ->where('id', '=', $jobid)->update(['status' => 'deleted']);
            if($updated) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }
    }

    public function getJobDetailById(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $reqid = request('id');
        if(!empty($reqid) && $reqid > 0) {
            $data = DB::table('jobs')
                ->where('id',(int)$reqid)
                ->first();
            if(!empty($data)) {
                $checkType = $data->addedby;
                if($checkType == 'company') {
                    $address = Companydetail::where('authid',$data->authid)->first();
                } else {
                    $address = Yachtdetail::where('authid',$data->authid)->first();
                }
                $data->address = null;
                $data->city = null;
                $data->state = null;
                $data->zipcode = null;
                if(isset($address->city)) {
                    $data->address = $address->address;
                    $data->city = $address->city;
                    $data->state = $address->state;
                    $data->zipcode = $address->zipcode;   
                }
                return response()->json(['success' => true,'data' => $data], $this->successStatus); 
            } else {
                return response()->json(['error' => 'networkerror'], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    // get professsinal applied for jobs by job id
    public function showProffByJob(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $servreqid = request('id');
        if(!empty($servreqid) && $servreqid > 0) {
            $data = DB::table('jobs as jb')
                ->Join('apply_jobs as apjob', 'apjob.jobid','=', 'jb.id')
                ->leftJoin('talentdetails as td', 'apjob.authid','=', 'td.authid')
                ->leftJoin('jobtitles as jt', 'jt.id','=', 'td.jobtitleid')
                ->select('td.firstname','td.lastname','apjob.created_at as applied_on','apjob.id','jt.title','jb.id  as jobid','td.authid as proffid')
                ->where('jb.id','=',$servreqid)
                ->where('apjob.status','!=','deleted')
                ->orderBy('apjob.created_at', 'DESC')
                ->get();
            if(!empty($data)) {
                return response()->json(['success' => true,'data' => $data], $this->successStatus); 
            } else {
                return response()->json(['error' => 'networkerror'], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }


    // change status for applied professinal
    public function deleteProffApplication(Request $request) {
        $servreqid = (int)request('id');
        $updated = 0;
        if(!empty($servreqid) && $servreqid > 0 ) {
            $updated = DB::table('apply_jobs')
                    ->where('id', '=', $servreqid)->update(['status' => 'deleted']);
            if($updated) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }
    }
    public function getMsgBtwLead(Request $request) {
        $companyid = (int)request('companyid');
        $leadbyid = (int)request('leadbyid');
        $requestId = (int)request('leadId');
        // $message_id = request('message_id');
        $currentDate = date("Y-m-d H:i:s");
        $message_type = 'lead';
        $allMessages = DB::table('messages as m')->select('m.id','m.message_from','m.message_to','m.subject','m.message','m.attachment','m.is_read','m.to_usertype','m.from_usertype','m.message_id','m.created_at','m.quote_email','m.quote_name','m.request_id','m.message_type','rp.status as request_status',DB::Raw("DATE_PART('day',m.created_at::timestamp - '".$currentDate."'::timestamp)"))
            ->leftJoin('request_proposals as rp','rp.requestid','=','m.request_id')
            ->whereRaw("(m.message_to=".$leadbyid." AND m.message_from = ".$companyid.") OR (m.message_to=".$companyid." AND m.message_from = ".$leadbyid.")")
            ->where('m.message_type','=',$message_type)
            ->where('m.request_id',$requestId)
            ->orderBy('m.created_at','ASC')->get();

        if(!empty($allMessages)) {
            $sender_id = 0;
            if(isset($allMessages[0])) {
                if($allMessages[0]->message_from == $companyid) {
                    $sender_type = $allMessages[0]->to_usertype;
                    $sender_id = $allMessages[0]->message_to;
                    $users_type = $allMessages[0]->from_usertype;
                } else {
                    $sender_type = $allMessages[0]->from_usertype;
                    $sender_id = $allMessages[0]->message_from;
                    $users_type = $allMessages[0]->to_usertype;
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            $requestData =  $sender_detail = $user_detail = [];
            if(isset($allMessages[0]->message_type) && $allMessages[0]->message_type == 'lead' && isset($allMessages[0]->request_id)) {
                $requestData = User_request_services::select('title','description')->where('id',$requestId)->first();
                if(!empty($requestData)) {
                    $requestData->message_type = 'lead';
                    $requestData->request_id = $allMessages[0]->request_id;
                }
            }

            if(!empty($sender_type) || !empty($users_type)) {
                if($sender_type == 'yacht') {
                    $select_sender = 'firstname,lastname,primaryimage as profile_image';
                    $table_sender = 'yachtdetail';
                } else if($sender_type == 'company') {
                    $select_sender = 'name as firstname,COALESCE(null,null) as lastname,primaryimage as profile_image,slug';
                    $table_sender = 'companydetails';
                } else if($sender_type == 'regular') {
                    $select_sender = 'firstname,lastname,profile_image';
                    $table_sender = 'userdetails';
                } else if($sender_type == 'professional') {
                    $select_sender = 'firstname,lastname,profile_image';
                    $table_sender = 'talentdetails';
                } 
                if($users_type == 'yacht') {
                    $select_user = 'firstname,lastname,primaryimage as profile_image';
                    $table_user = 'yachtdetail';
                } else if($users_type == 'company') {
                    $select_user = 'name as firstname,COALESCE(null,null) as lastname,primaryimage as profile_image,slug';
                    $table_user = 'companydetails';
                } else if($users_type == 'regular') {
                    $select_user = 'firstname,lastname,profile_image';
                    $table_user = 'userdetails';
                } else if($users_type == 'professional') {
                    $select_user = 'firstname,lastname,profile_image';
                    $table_user = 'talentdetails';
                }
               
                //echo $select_sender.'   '.$table_sender.'  '.$sender_id;
                if(!empty($sender_type)) {
                    $sender_detail = DB::table($table_sender)->select(DB::Raw($select_sender))->where('authid',$sender_id)->first();
                    if(!empty($sender_detail)) {
                        $sender_detail->user_type = $sender_type;
                        $sender_detail->authid = $sender_id;
                        $sender_detail->valid = TRUE;
                    } else {
                        $sender_detail = (object) ['valid' => FALSE];    
                    }  
                } else {
                    $sender_detail = (object) ['valid' => FALSE];
                }
                if(!empty($users_type)) {
                    $user_detail = DB::table($table_user)->select(DB::Raw($select_user))->where('authid',$companyid)->first();
                    if(!empty($user_detail)) {
                        $user_detail->user_type = $users_type;
                        $user_detail->authid = $companyid;
                    }
                } else {
                    $user_detail = (object) ['valid' => FALSE];
                }
                // if(!empty($user_detail) && !empty($sender_detail)) {
                    return response()->json(['success' => TRUE,'data' => $allMessages,'senderdetail' => $sender_detail , 'userdetail' => $user_detail,'requestdetail' => $requestData], $this->successStatus);
                // } else {
                    // return response()->json(['error'=>'networkerror'], 401);
                // }
            
            } else {
                return response()->json(['error'=>'networkerror'], 401);   
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);    
        }
    }
    public function getMsgbtnJob(Request $request) {
        $proffid = (int)request('proffid');
        $jobByid = (int)request('jobBy');
        $jobId = (int)request('jobId');
        $currentDate = date("Y-m-d H:i:s");
        $message_type = 'vacancy';

        $allMessages = Messages::select('id','message_from','message_to','subject','message','attachment','is_read','to_usertype','from_usertype','message_id','created_at','quote_email','quote_name','request_id','message_type',DB::Raw("DATE_PART('day',created_at::timestamp - '".$currentDate."'::timestamp)"))
            ->whereRaw("(message_to=".$jobByid." AND message_from = ".$proffid.") OR (message_to=".$proffid." AND message_from = ".$jobByid.")")
            ->where('message_type','=',$message_type)
            ->where('request_id','=',$jobId)
            ->orderBy('created_at','ASC')->get();

        if(!empty($allMessages)) {
            $sender_id = 0;
            if(isset($allMessages[0])) {
                if($allMessages[0]->message_from == $proffid) {
                    $sender_type = $allMessages[0]->to_usertype;
                    $sender_id = $allMessages[0]->message_to;
                    $users_type = $allMessages[0]->from_usertype;
                } else {
                    $sender_type = $allMessages[0]->from_usertype;
                    $sender_id = $allMessages[0]->message_from;
                    $users_type = $allMessages[0]->to_usertype;
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            $requestData =  $sender_detail = $user_detail = [];
            if(isset($allMessages[0]->message_type) && $allMessages[0]->message_type == 'vacancy' && isset($allMessages[0]->request_id)) {
                 $requestData = Jobs::select('title','description')->where('id',$jobId)->first();
                if(!empty($requestData)) {
                    $requestData->request_id = $allMessages[0]->request_id;
                    $requestData->message_type = 'vacancy';
                }
            } 

            if(!empty($sender_type) || !empty($users_type)) {
                if($sender_type == 'yacht') {
                    $select_sender = 'firstname,lastname,primaryimage as profile_image';
                    $table_sender = 'yachtdetail';
                } else if($sender_type == 'company') {
                    $select_sender = 'name as firstname,COALESCE(null,null) as lastname,primaryimage as profile_image,slug';
                    $table_sender = 'companydetails';
                } else if($sender_type == 'regular') {
                    $select_sender = 'firstname,lastname,profile_image';
                    $table_sender = 'userdetails';
                } else if($sender_type == 'professional') {
                    $select_sender = 'firstname,lastname,profile_image';
                    $table_sender = 'talentdetails';
                } 
                if($users_type == 'yacht') {
                    $select_user = 'firstname,lastname,primaryimage as profile_image';
                    $table_user = 'yachtdetail';
                } else if($users_type == 'company') {
                    $select_user = 'name as firstname,COALESCE(null,null) as lastname,primaryimage as profile_image';
                    $table_user = 'companydetails';
                } else if($users_type == 'regular') {
                    $select_user = 'firstname,lastname,profile_image';
                    $table_user = 'userdetails';
                } else if($users_type == 'professional') {
                    $select_user = 'firstname,lastname,profile_image';
                    $table_user = 'talentdetails';
                }
               
                if(!empty($sender_type)) {
                    $sender_detail = DB::table($table_sender)->select(DB::Raw($select_sender))->where('authid',$sender_id)->first();
                    if(!empty($sender_detail)) {
                        $sender_detail->user_type = $sender_type;
                        $sender_detail->authid = $sender_id;
                        $sender_detail->valid = TRUE;
                    } else {
                        $sender_detail = (object) ['valid' => FALSE];    
                    }  
                } else {
                    $sender_detail = (object) ['valid' => FALSE];
                }
                if(!empty($users_type)) {
                    $user_detail = DB::table($table_user)->select(DB::Raw($select_user))->where('authid',$proffid)->first();
                    if(!empty($user_detail)) {
                        $user_detail->user_type = $users_type;
                        $user_detail->authid = $proffid;
                    }
                } else {
                    $user_detail = (object) ['valid' => FALSE];
                }
                return response()->json(['success' => TRUE,'data' => $allMessages,'senderdetail' => $sender_detail , 'userdetail' => $user_detail,'requestdetail' => $requestData], $this->successStatus);            
            } else {
                return response()->json(['error'=>'networkerror'], 401);   
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);    
        }
    }


     public function ViewProffList(Request $request) {
        $searchString = request('searchString');
        $page = request('page');
        $orderBy = request('order');
        $reverse = request('reverse');
        $order = ($reverse == 'false')?'ASC':'DESC';
        if($page == 0 || $page == null || !(int)$page) {
            $page = 0;
        }
        $limit = 30;
        $offset = 0;
        if($page > 0) {
            $offset = ($page - 1)*$limit;
        }

        $whereQuery = '';
        if(!empty($searchString)) {
            $searchString = strtolower($searchString);
            $whereQuery = "WHERE LOWER(cd.name) LIKE '%".$searchString."%' OR LOWER(CONCAT(td.firstname, ' ', td.lastname)) LIKE '%".$searchString."%' OR LOWER(CONCAT(yd.firstname, ' ', yd.lastname)) LIKE '%".$searchString."%'";
        }

        $order_by = '';
        if($orderBy == 'company_name') {
            $order_by = 'ORDER BY employer_name '.$order;
        } else if($orderBy == 'talent_name'){
            $order_by = 'ORDER BY employer_name '.$order;            
        } else {
            $order_by = 'ORDER BY ct.created_at DESC';
        }
        $count = DB::select("SELECT count(*) FROM (SELECT ct.id,ct.companyid,ct.talentid,ct.created_at as contacted_on,ct.status, CONCAT(td.firstname,' ', td.lastname) as talent_name,
                COALESCE(NULLIF(yd.lastname,''), yd.lastname) as lastname,
                yd.firstname as yacht_name,cd.name as company_name FROM contacted_talent as ct LEFT JOIN companydetails as cd on ct.companyid = cd.authid LEFT JOIN yachtdetail as yd ON ct.companyid = yd.authid LEFT JOIN talentdetails  as td ON td.authid = ct.talentid ".$whereQuery.") temp");

        $data = DB::select("SELECT ct.id,ct.companyid,ct.talentid,ct.created_at as contacted_on,ct.status, CONCAT(td.firstname,' ', td.lastname) as talent_name,
                COALESCE(NULLIF(CONCAT(yd.firstname),''), cd.name) as employer_name,yd.lastname as lastname,
                yd.firstname as yacht_name,cd.name as company_name FROM contacted_talent as ct LEFT JOIN companydetails as cd on ct.companyid = cd.authid LEFT JOIN yachtdetail as yd ON ct.companyid = yd.authid LEFT JOIN talentdetails  as td ON td.authid = ct.talentid ".$whereQuery." ".$order_by." LIMIT ".$limit." OFFSET ".$offset."");  
        if(!empty($data)) {
            $totalrecords = $count[0]->count;
            return response()->json(['success' => true,'data' => $data,'totalrecords' => $totalrecords], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    public function getMessageContProff(Request $request) {
        $proffid = (int)request('proffid');
        $jobByid = (int)request('companyid');
        $currentDate = date("Y-m-d H:i:s");
        $message_type = 'contact_now';

        $allMessages = Messages::select('id','message_from','message_to','subject','message','attachment','is_read','to_usertype','from_usertype','message_id','created_at','message_type',DB::Raw("DATE_PART('day',created_at::timestamp - '".$currentDate."'::timestamp)"))
            ->whereRaw("(message_to=".$proffid." AND message_from = ".$jobByid.") OR (message_to=".$jobByid." AND message_from = ".$proffid.")")
            ->where('message_type','=',$message_type)
            ->orderBy('created_at','ASC')->get();

        if(!empty($allMessages)) {
            $sender_id = 0;
            if(isset($allMessages[0])) {
                if($allMessages[0]->message_from == $proffid) {
                    $sender_type = $allMessages[0]->to_usertype;
                    $sender_id = $allMessages[0]->message_to;
                    $users_type = $allMessages[0]->from_usertype;
                } else {
                    $sender_type = $allMessages[0]->from_usertype;
                    $sender_id = $allMessages[0]->message_from;
                    $users_type = $allMessages[0]->to_usertype;
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            $requestData =  $sender_detail = $user_detail = [];

            if(!empty($sender_type) || !empty($users_type)) {
                if($sender_type == 'yacht') {
                    $select_sender = 'firstname,lastname,primaryimage as profile_image';
                    $table_sender = 'yachtdetail';
                } else if($sender_type == 'company') {
                    $select_sender = 'name as firstname,COALESCE(null,null) as lastname,primaryimage as profile_image,slug';
                    $table_sender = 'companydetails';
                } else if($sender_type == 'regular') {
                    $select_sender = 'firstname,lastname,profile_image';
                    $table_sender = 'userdetails';
                } else if($sender_type == 'professional') {
                    $select_sender = 'firstname,lastname,profile_image';
                    $table_sender = 'talentdetails';
                } 
                if($users_type == 'yacht') {
                    $select_user = 'firstname,lastname,primaryimage as profile_image';
                    $table_user = 'yachtdetail';
                } else if($users_type == 'company') {
                    $select_user = 'name as firstname,COALESCE(null,null) as lastname,primaryimage as profile_image';
                    $table_user = 'companydetails';
                } else if($users_type == 'regular') {
                    $select_user = 'firstname,lastname,profile_image';
                    $table_user = 'userdetails';
                } else if($users_type == 'professional') {
                    $select_user = 'firstname,lastname,profile_image';
                    $table_user = 'talentdetails';
                }
               
                if(!empty($sender_type)) {
                    $sender_detail = DB::table($table_sender)->select(DB::Raw($select_sender))->where('authid',$sender_id)->first();
                    if(!empty($sender_detail)) {
                        $sender_detail->user_type = $sender_type;
                        $sender_detail->authid = $sender_id;
                        $sender_detail->valid = TRUE;
                    } else {
                        $sender_detail = (object) ['valid' => FALSE];    
                    }  
                } else {
                    $sender_detail = (object) ['valid' => FALSE];
                }
                if(!empty($users_type)) {
                    $user_detail = DB::table($table_user)->select(DB::Raw($select_user))->where('authid',$proffid)->first();
                    if(!empty($user_detail)) {
                        $user_detail->user_type = $users_type;
                        $user_detail->authid = $proffid;
                    }
                } else {
                    $user_detail = (object) ['valid' => FALSE];
                }
                return response()->json(['success' => TRUE,'data' => $allMessages,'senderdetail' => $sender_detail , 'userdetail' => $user_detail,'requestdetail' => $requestData], $this->successStatus);            
            } else {
                return response()->json(['error'=>'networkerror'], 401);   
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);    
        }
    }   


     // get list of quote
    public function viewQuote() {
        $searchString = request('searchString');
        $page = request('page');
        $orderBy = request('order');
        $reverse = request('reverse');
        $order = ($reverse == 'false')?'ASC':'DESC';
        if($page == 0 || $page == null || !(int)$page) {
            $page = 0;
        }
        $limit = 10;
        $offset = 0;
        if($page > 0) {
            $offset = ($page - 1)*$limit;
        }

        $whereQuery = '';    
        if(!empty($searchString)) {
            $searchString = strtolower($searchString);
            $whereQuery = "AND LOWER(qr.title) LIKE '%".$searchString."%'";
        }
        $order_by = '';
        if($orderBy == 'title') {
            $order_by = 'ORDER BY qr.title '.$order;
        } else {
            $order_by = 'ORDER BY qr.created_at DESC';
        }

        $count = DB::select("SELECT COUNT(*) FROM (SELECT qr.id,qr.title,qr.created_at,qr.status,qr.userid,qr.businessid,msg.message_id,
                COALESCE(NULLIF(ud.firstname,''), yd.firstname,pr.firstname) as firstname,
                COALESCE(NULLIF(ud.lastname,''), yd.lastname,pr.lastname) as lastname,
                yd.firstname as yacht_name,ud.firstname as user_name FROM quoterequests as qr LEFT JOIN userdetails as ud on qr.userid  = ud.authid LEFT JOIN yachtdetail as yd ON yd.authid = qr.userid LEFT JOIN talentdetails as pr ON pr.authid = qr.userid LEFT JOIN messages as msg ON msg.request_id = qr.id WHERE qr.status = '1' AND msg.message_type = 'request_quote' ".$whereQuery.") temp"); 
        $data = DB::select("SELECT qr.id,qr.title,qr.created_at,qr.status,qr.userid,qr.businessid,msg.message_id,
            COALESCE(NULLIF(ud.firstname,''), yd.firstname,pr.firstname) as firstname,
            COALESCE(NULLIF(ud.lastname,''), yd.lastname,pr.lastname) as lastname,
            yd.firstname as yacht_name,ud.firstname as user_name FROM quoterequests as qr LEFT JOIN userdetails as ud on qr.userid  = ud.authid LEFT JOIN yachtdetail as yd ON yd.authid = qr.userid LEFT JOIN talentdetails as pr ON pr.authid = qr.userid LEFT JOIN messages as msg ON msg.request_id = qr.id WHERE qr.status = '1' AND msg.message_type = 'request_quote' ".$whereQuery." ".$order_by." LIMIT ".$limit." OFFSET ".$offset."");
        if(!empty($data)) {
            $totalrecords = $count[0]->count;
            return response()->json(['success' => true,'data' => $data ,'totalrecords' => $totalrecords], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    // delete quote
    public function deleteQuote(Request $request) {
        $quoteId = (int)request('id');
        $updated = 0;
        if(!empty($quoteId) && $quoteId > 0 ) {
            $updated = DB::table('quoterequests')
                    ->where('id', '=', $quoteId)->update(['status' => 0]);
            if($updated) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }
    }

    // get quote detail by id
    public function getQuoteDetailById(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $quoteId = request('id');
        if(!empty($quoteId) && $quoteId > 0) {
            $data = DB::table('quoterequests')->where('id',(int)$quoteId)
                ->first();
            if(!empty($data)) {
                return response()->json(['success' => true,'data' => $data], $this->successStatus); 
            } else {
                return response()->json(['error' => 'networkerror'], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    // get message between quote given by and business
    public function getMsgBtwQuote(Request $request) {
        $proffid = (int)request('companyid');
        $jobByid = (int)request('quotebyid');
        $jobId = (int)request('messageId');
        $quoteId = (int)request('quoteId');
        $currentDate = date("Y-m-d H:i:s");
        $message_type = 'request_quote';
        $allMessages = Messages::select('id','message_from','message_to','subject','message','attachment','is_read','to_usertype','from_usertype','message_id','created_at','quote_email','quote_name','request_id','message_type',DB::Raw("DATE_PART('day',created_at::timestamp - '".$currentDate."'::timestamp)"))
            // ->where()
            ->whereRaw("message_type = '".$message_type."' AND message_id = ".$jobId." AND ((message_to=".$jobByid." AND message_from = ".$proffid.") OR (message_to=".$proffid." AND message_from = ".$jobByid."))")
            ->orderBy('created_at','ASC')->get();
            // print_r($allMessages);die;
        if(!empty($allMessages)) {
            $sender_id = 0;
            if(isset($allMessages[0])) {
                if($allMessages[0]->message_from == $proffid) {
                    $sender_type = $allMessages[0]->to_usertype;
                    $sender_id = $allMessages[0]->message_to;
                    $users_type = $allMessages[0]->from_usertype;
                } else {
                    $sender_type = $allMessages[0]->from_usertype;
                    $sender_id = $allMessages[0]->message_from;
                    $users_type = $allMessages[0]->to_usertype;
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
            $requestData =  $sender_detail = $user_detail = [];
            if(isset($allMessages[0]->message_type) && $allMessages[0]->message_type == 'request_quote') {
                $requestData = DB::table('quoterequests')->select('title','objective')->where('id',$quoteId)->first();
                if(!empty($requestData)) {
                    $requestData->message_type = 'request_quote';
                }
            } 

            if(!empty($sender_type) || !empty($users_type)) {
                if($sender_type == 'yacht') {
                    $select_sender = 'firstname,lastname,primaryimage as profile_image';
                    $table_sender = 'yachtdetail';
                } else if($sender_type == 'company') {
                    $select_sender = 'name as firstname,COALESCE(null,null) as lastname,primaryimage as profile_image,slug';
                    $table_sender = 'companydetails';
                } else if($sender_type == 'regular') {
                    $select_sender = 'firstname,lastname,profile_image';
                    $table_sender = 'userdetails';
                } else if($sender_type == 'professional') {
                    $select_sender = 'firstname,lastname,profile_image';
                    $table_sender = 'talentdetails';
                } 
                if($users_type == 'yacht') {
                    $select_user = 'firstname,lastname,primaryimage as profile_image';
                    $table_user = 'yachtdetail';
                } else if($users_type == 'company') {
                    $select_user = 'name as firstname,COALESCE(null,null) as lastname,primaryimage as profile_image';
                    $table_user = 'companydetails';
                } else if($users_type == 'regular') {
                    $select_user = 'firstname,lastname,profile_image';
                    $table_user = 'userdetails';
                } else if($users_type == 'professional') {
                    $select_user = 'firstname,lastname,profile_image';
                    $table_user = 'talentdetails';
                }
               
                if(!empty($sender_type)) {
                    $sender_detail = DB::table($table_sender)->select(DB::Raw($select_sender))->where('authid',$sender_id)->first();
                    if(!empty($sender_detail)) {
                        $sender_detail->user_type = $sender_type;
                        $sender_detail->authid = $sender_id;
                        $sender_detail->valid = TRUE;
                    } else {
                        $sender_detail = (object) ['valid' => FALSE];    
                    }  
                } else {
                    $sender_detail = (object) ['valid' => FALSE];
                }
                if(!empty($users_type)) {
                    $user_detail = DB::table($table_user)->select(DB::Raw($select_user))->where('authid',$proffid)->first();
                    if(!empty($user_detail)) {
                        $user_detail->user_type = $users_type;
                        $user_detail->authid = $proffid;
                    }
                } else {
                    $user_detail = (object) ['valid' => FALSE];
                }
                return response()->json(['success' => TRUE,'data' => $allMessages,'senderdetail' => $sender_detail , 'userdetail' => $user_detail,'requestdetail' => $requestData], $this->successStatus);            
            } else {
                return response()->json(['error'=>'networkerror'], 401);   
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);    
        }
    }

    // get review list
    /*
    public function ViewReveiwList() {
        $data = DB::select("SELECT srr.id,srr.created_at,srr.fromid,srr.toid,srr.from_usertype,cd.name as to_compayname,
                COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname
                from service_request_reviews as srr
                left join (
                    (select authid, firstname, lastname from userdetails)
                    union (select authid, firstname, lastname from yachtdetail)
                    union (select authid, firstname, lastname from talentdetails)
                    union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname from companydetails)
                ) unionSub1 on unionSub1.authid = srr.fromid LEFT JOIN companydetails as cd ON cd.authid = srr.toid WHERE srr.isdeleted = '0' AND srr.parent_id = '0' order by srr.created_at DESC");  
        if(!empty($data)) {
            return response()->json(['success' => true,'data' => $data], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }*/
     // get review list
    public function ViewReveiwList() {
        $searchString = request('searchString');
        $page = request('page');
        $orderBy = request('order');
        $reverse = request('reverse');
        $order = ($reverse == 'false')?'ASC':'DESC';
        if($page == 0 || $page == null || !(int)$page) {
            $page = 0;
        }
        $limit = 30;
        $offset = 0;
        if($page > 0) {
            $offset = ($page - 1)*$limit;
        }

        $whereQuery = '';
        if(!empty($searchString)) {
            $searchString = strtolower($searchString);
            $whereQuery = "AND LOWER(CONCAT(unionSub1.firstname,' ',unionSub1.lastname)) LIKE '%".$searchString."%' OR LOWER(cd.name) LIKE '%".$searchString."%'";
        }
        $order_by = '';
        if($orderBy == 'from_firstname') {
            $order_by = 'ORDER BY from_firstname '.$order;
        } else if($orderBy == 'to_compayname') {
            $order_by = 'ORDER BY to_compayname '.$order;
        } else {
            $order_by = 'order by srr.created_at DESC';
        }
        $count = DB::select("SELECT count(*) as count
                from service_request_reviews as srr
                left join (
                    (select authid, firstname, lastname from userdetails)
                    union (select authid, firstname, lastname from yachtdetail)
                    union (select authid, firstname, lastname from talentdetails)
                    union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname from companydetails)
                ) unionSub1 on unionSub1.authid = srr.fromid LEFT JOIN companydetails as cd ON cd.authid = srr.toid WHERE srr.isdeleted = '0' AND srr.parent_id = '0' ".$whereQuery."");  
        $data = DB::select("SELECT srr.id,srr.created_at,srr.fromid,srr.toid,srr.from_usertype,cd.name as to_compayname,
                COALESCE(unionSub1.firstname,NULL) as from_firstname, unionSub1.lastname as from_lastname
                from service_request_reviews as srr
                left join (
                    (select authid, firstname, lastname from userdetails)
                    union (select authid, firstname, lastname from yachtdetail)
                    union (select authid, firstname, lastname from talentdetails)
                    union (select authid, name as firstname, COALESCE(NULL,NULL) as lastname from companydetails)
                ) unionSub1 on unionSub1.authid = srr.fromid LEFT JOIN companydetails as cd ON cd.authid = srr.toid WHERE srr.isdeleted = '0' AND srr.parent_id = '0' ".$whereQuery." ".$order_by." LIMIT ".$limit." OFFSET ".$offset." ");  
        if(!empty($data)) {
            $totalrecords = $count[0]->count;
            return response()->json(['success' => true,'data' => $data,'totalrecords' => $totalrecords], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    // delete review
    public function deleteReview(Request $request) {
        $quoteId = (int)request('id');
        $updated = 0;
        if(!empty($quoteId) && $quoteId > 0 ) {
            $updated = DB::table('service_request_reviews')
                    ->where('id', '=', $quoteId)->update(['isdeleted' => 1]);
            if($updated) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }
    }

    // get review detail by id
    public function getReviewDetailById(Request $request) {
        $validate = Validator::make($request->all(), [
            'id' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }
        $reviewId = request('id');
        if(!empty($reviewId) && $reviewId > 0) {
            $data = DB::table('service_request_reviews')
                ->select('rating','comment','created_at','id')
                ->where('id',(int)$reviewId)
                ->first();
            if(!empty($data)) {
                $replydata = DB::table('service_request_reviews')
                ->select('comment as replyComment','created_at')
                ->where('parent_id',(int)$data->id)
                ->first();
                $data->replycomment = $replydata;
                return response()->json(['success' => true,'data' => $data], $this->successStatus); 
            } else {
                return response()->json(['error' => 'networkerror'], $this->successStatus);
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);
        }
    }

    

    // delte badword
    public function deleteBadword() {
        $wordId = (int)request('id');
        $updated = 0;
        if(!empty($wordId) && $wordId > 0 ) {
            $updated = DB::table('badword')
                    ->where('id', '=', $wordId)->update(['status' => 0]);
            if($updated) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401); 
            }
        }
    }

    // 
    public function addBadword(Request $request) {
        $validate = Validator::make($request->all(), [
            'word' => 'required'
        ]);
        if ($validate->fails()) {
            return response()->json(['error'=>'validationError'], 401); 
        }

        $badword = new Badword; 
        $badword->word  = request('word');
        if($badword->save()) {
            return response()->json(['success' => true], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    public function makeFreeAccount(){
        $duration = request('duration');
        $userid = request('id');
        if(((int)$duration || $duration == 'Unlimited') && (int)$userid) {
            $auth = Auth::where('id',$userid)->update(['status' => 'active','stepscompleted' => '3']);
            if($auth) {
                //Check if existing user have any active subscripion 
                $previousAccountType = '';
                $companyRecord = Companydetail::where('authid',$userid)->first();
                if(isset($companyRecord->subscription_id) && $companyRecord->subscription_id != '') {
                    try{
                        $trial = 0;
                        $stripe = Stripe::make(config()->get('services')['stripe']['secret']);
                        $subscription = $stripe->subscriptions()->cancel($companyRecord->customer_id, $companyRecord->subscription_id);
                        $changeStatus = Companydetail::where('authid','=',$userid)->update(['subscriptiontype'=>'manual','remaintrial' => $trial,'subscription_id'=> NULL]);
                    }   catch(\Cartalyst\Stripe\Exception\NotFoundException $e) {
                             
                    }   catch (Exception $e) {
                        return response()->json(['error'=>'networkerror'], 401);   
                    }
                } 
                if(isset($companyRecord->plansubtype) && ($companyRecord->plansubtype == 'paid' || $companyRecord->plansubtype == 'free') ){
                    $previousAccountType = 'paid';
                } else {
                    $previousAccountType = 'free';
                }

                if($duration == 'Unlimited') {
                    $update = ['free_subscription_period' => 'unlimited','account_type' => 'free','status'=> 'active','previous_account_type' => $previousAccountType];

                    $companydetails = Companydetail::where('authid',$userid)->update($update);
                    if($companydetails) {
                        $zaiperenv = env('ZAIPER_ENV','local');
                        if($zaiperenv == 'live') {
                            $this->companyCreateZapierbyID($userid,'FREE_ACCOUNT');
                        }
                        return response()->json(['success' => true], $this->successStatus);                
                    } else {
                        return response()->json(['error'=>'networkerror'], 401);    
                    }
                } else {
                    $today = date('Y-m-d 00:00:00');
                    $period = $duration;
                    $duration = $duration*30;
                    $endDate = date('Y-m-d 00:00:00', strtotime("+ ".$duration." days", strtotime(date('Y-m-d H:i:s'))));
                    $update['free_subscription_period'] = $period;
                    $update['account_type'] = 'free';
                    $update['free_subscription_start'] = $today;
                    $update['free_subscription_end'] = $endDate;
                    $update['previous_account_type'] = $previousAccountType;
                    // $update['nextpaymentdate'] = $endDate;

                    $update['status'] = 'active';
                    $companydetails = Companydetail::where('authid',$userid)->update($update);
                    if($companydetails) {
                        $zaiperenv = env('ZAIPER_ENV','local');
                        if($zaiperenv == 'live') {
                            $this->companyCreateZapierbyID($userid,'FREE_ACCOUNT');
                        }
                        return response()->json(['success' => true], $this->successStatus);                
                    } else {
                        return response()->json(['error'=>'networkerror'], 401);    
                    }
                }
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }
        } else {
            return response()->json(['error'=>'networkerror'], 401);            
        }
    }
    
    public function makePaidAccount(Request $request){
        $userid = request('id');
        if((int)$userid) {
            $companyRecord = Companydetail::where('authid',$userid)->update(['free_subscription_period' => null,'account_type' => 'paid','free_subscription_start' => null,'free_subscription_end' => null,'nextpaymentdate' => null,'previous_account_type' => 'free']);
            if($companyRecord) {
                return response()->json(['success' => true], $this->successStatus);
            } else {
                return response()->json(['error'=>'networkerror'], 401);    
            }     
        } else {
            return response()->json(['error'=>'networkerror'], 401);            
        }
    }

    public function newsletterusers(Request $request){
        $searchString = request('searchString');
        $statusFilter = request('statusFilter');
        $page = request('page');
        $reverse = request('reverse');
        $orderBy = request('order');
        $order = ($reverse == 'false')?'ASC':'DESC';

        if($page == 0 || $page == null || !(int)$page) {
            $page = 0;
        }
        $limit = 30;
        $offset = 0;
        if($page > 0) {
            $offset = ($page - 1)*$limit;
        }
        $whereCompany = '';
        if(!empty($statusFilter) && $statusFilter != 'all') {
            if($statusFilter == 'opted') {
               $whereCompany  = "newsletter = '1'";
            } else if($statusFilter == 'not-opted') {
                $whereCompany = "newsletter = '0'";
            }
        }
        if(!empty($searchString)) {
            $searchString = strtolower($searchString);
            if($whereCompany != '') {
                $whereCompany .= "AND LOWER(email) LIKE '%".$searchString."%'";
            } else {
                    $whereCompany = "LOWER(email) LIKE '%".$searchString."%'";    
            }
        }
        //Get total records 
        $cQuery = DB::table('auths')->select('email','usertype','newsletter')->where('status','active')->where('accounttype','real');
        if($whereCompany != '') {
            $cQuery = $cQuery->whereRaw($whereCompany);
        }
        $totalrecords = $cQuery->count();

        $query = DB::table('auths')->select('email','usertype','newsletter')->where('status','active')->where('accounttype','real');
        if($whereCompany != '') {
            $query = $query->whereRaw($whereCompany);
        }
        if($orderBy == 'email') {
            $query = $query->orderBy('email', $order);
        } else if($orderBy == 'name') {
            $query = $query->orderBy('created_at', 'DESC');
        }
        $allrecords = $query
            ->skip($offset)
            ->take($limit)
            ->get();

        if(!empty($allrecords)) {
            return response()->json(['success' => true,'data' => $allrecords,'totalrecords' => $totalrecords], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }

    public function exportNewsletter(Request $request){
        $searchString = request('searchString');
        $statusFilter = request('statusFilter');
        $page = request('page');
        $reverse = request('reverse');
        $orderBy = request('order');
        $limit = request('limit');
        $offset = request('offset');
        $order = ($reverse == 'false')?'ASC':'DESC';
        $whereCompany = '';
        if(!empty($statusFilter) && $statusFilter != 'all') {
            if($statusFilter == 'opted') {
               $whereCompany  = "newsletter = '1'";
            } else if($statusFilter == 'not-opted') {
                $whereCompany = "newsletter = '0'";
            }
        }
        if(!empty($searchString)) {
            $searchString = strtolower($searchString);
            if($whereCompany != '') {
                $whereCompany .= "AND LOWER(email) LIKE '%".$searchString."%'";
            } else {
                    $whereCompany = "LOWER(email) LIKE '%".$searchString."%'";    
            }
        }


        $query = DB::table('auths')->select('email',DB::Raw("CASE WHEN newsletter = '1' THEN 'Opted' WHEN newsletter='0' THEN 'Not Opted' END"))->where('status','active')->where('accounttype','real');
        if($whereCompany != '') {
            $query = $query->whereRaw($whereCompany);
        }
        if($orderBy == 'email') {
            $query = $query->orderBy('email', $order);
        } else if($orderBy == 'name') {
            $query = $query->orderBy('created_at', 'DESC');
        }
        $allrecords = $query
            ->skip($offset)
            ->take($limit)
            ->get();

        if(!empty($allrecords)) {
            return response()->json(['success' => true,'data' => $allrecords], $this->successStatus);
        } else {
            return response()->json(['error'=>'networkerror'], 401); 
        }
    }
    
    public function getanalyticsAdmin(Request $request) {
			$countwise = request('countwise');
			$whereDate = '';
			if($countwise == 'week') {
				$whereDate = Carbon\Carbon::now()->subdays('7');
			} else if ($countwise == 'month') {
				$whereDate = Carbon\Carbon::now()->subMonths('1');
			} else if ($countwise == 'year') {
				$whereDate = Carbon\Carbon::now()->subMonths('12');
			} else if($countwise == 'today') {
				$whereDate = date('Y-m-d H:i:s',(strtotime(date('Y-m-d 00:00:00'))-1));
			}
			$whereBetween = '';
			if($countwise == 'custom') {
				$from = request('fromdate');
				$fromdate = date('Y-m-d H:i:s',strtotime($from));
				$to = request('todate');
				$todate = date('Y-m-d H:i:s',strtotime($to));
				$whereBetween = 'custom';
				$betweenArr = array($fromdate,$todate);
				//$whereBetween = 
			}
			/////// dummy registration ////////
			$DummyRegistrationqry = Auth::where('accounttype','dummy')->where('status','!=','deleted');
			if($whereDate != '') {
				$DummyRegistrationqry = $DummyRegistrationqry->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$DummyRegistrationqry = $DummyRegistrationqry->whereBetween('created_at', $betweenArr);
			}
			$DummyRegistrationData = $DummyRegistrationqry->count();
			//echo $DummyRegistrationData;
			/////// total registration ///////
			$totalRegistrationqry = Auth::where('status','!=','deleted');
			if($whereDate != '') {
				$totalRegistrationqry = $totalRegistrationqry->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$totalRegistrationqry = $totalRegistrationqry->whereBetween('created_at', $betweenArr);
			}
			$totalRegistrationData = $totalRegistrationqry->count();
			
			///////// total request /////////
			$TotalServiceRequestQry = User_request_services::where('status' ,'!=','deleted');
			if($whereDate != '') {
				$TotalServiceRequestQry = $TotalServiceRequestQry->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalServiceRequestQry = $TotalServiceRequestQry->whereBetween('created_at', $betweenArr);
			}
			$TotalServiceRequest = $TotalServiceRequestQry->count();
			
			///////// total request completed /////////
			$TotalServiceRequestCmpQry = User_request_services::where('status' ,'completed');
			if($whereDate != '') {
				$TotalServiceRequestCmpQry = $TotalServiceRequestCmpQry->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalServiceRequestCmpQry = $TotalServiceRequestCmpQry->whereBetween('created_at', $betweenArr);
			}
			$TotalServiceRequestCmp = $TotalServiceRequestCmpQry->count();
			
			////////  claimed business approved ////////
			$TotalClaimedApprovedCmpQry = Companydetail::where('is_claimed','1')->where('status' ,'!=','deleted');
			if($whereDate != '') {
				$TotalClaimedApprovedCmpQry = $TotalClaimedApprovedCmpQry->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalClaimedApprovedCmpQry = $TotalClaimedApprovedCmpQry->whereBetween('created_at', $betweenArr);
			}
			$TotalClaimedApproved = $TotalClaimedApprovedCmpQry->count();
			
			////////  total claimed business ////////
			$TotalClaimedCmpQry = dummy_registration::where('is_claim_user','1')->where('status' ,'active');
			if($whereDate != '') {
				$TotalClaimedCmpQry = $TotalClaimedCmpQry->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalClaimedCmpQry = $TotalClaimedCmpQry->whereBetween('created_at', $betweenArr);
			}
			$TotalClaimedBusiness = $TotalClaimedCmpQry->count();
			$TotalClaimedBusiness += $TotalClaimedApproved;
			
			////////  total query ////////
			$TotalqueryCmpQry = Contactus::where('status','1');
			if($whereDate != '') {
				$TotalqueryCmpQry = $TotalqueryCmpQry->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalqueryCmpQry = $TotalqueryCmpQry->whereBetween('created_at', $betweenArr);
			}
			$TotalqueryData = $TotalqueryCmpQry->count();
			
			////////  read query ////////
			$TotalreadqueryCmpQry = Contactus::where('status','1')->where('is_read','0');
			if($whereDate != '') {
				$TotalreadqueryCmpQry = $TotalreadqueryCmpQry->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalreadqueryCmpQry = $TotalreadqueryCmpQry->whereBetween('created_at', $betweenArr);
			}
			$TotalreadqueryData = $TotalreadqueryCmpQry->count();
			////////// live data for analytics ///////
			
			/////////// requests /////////
			$TotalServiceRequestQryLive = User_request_services::where('status' ,'!=','deleted');
			if($whereDate != '') {
				$TotalServiceRequestQryLive = $TotalServiceRequestQryLive->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalServiceRequestQryLive = $TotalServiceRequestQryLive->whereBetween('created_at', $betweenArr);
			}
			$TotalServiceRequestLive = $TotalServiceRequestQryLive->count();
			
			//////////// jobs ////////////
			$TotalJobsQryLive = Jobs::where('status' ,'!=','deleted');
			if($whereDate != '') {
				$TotalJobsQryLive = $TotalJobsQryLive->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalJobsQryLive = $TotalJobsQryLive->whereBetween('created_at', $betweenArr);
			}
			$TotalJobsLive = $TotalJobsQryLive->count();
			
			/////////// contacted talent/////////
			$TotalContactTalentQryLive = Contacted_Talent::where('status' ,'1');
			if($whereDate != '') {
				$TotalContactTalentQryLive = $TotalContactTalentQryLive->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalContactTalentQryLive = $TotalContactTalentQryLive->whereBetween('created_at', $betweenArr);
			}
			$TotalContactTalentLive = $TotalContactTalentQryLive->count();
			
			/////////// request quotes talent/////////
			$TotalRequestQuotesQryLive = Quoterequests::where('status' ,'1');
			if($whereDate != '') {
				$TotalRequestQuotesQryLive = $TotalRequestQuotesQryLive->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalRequestQuotesQryLive = $TotalRequestQuotesQryLive->whereBetween('created_at', $betweenArr);
			}
			$TotalRequestQuotesLive = $TotalRequestQuotesQryLive->count();
			//////////////////////////////////////////
			
			///////////////// payment ///////////////
			$TotalPaymentLiveArr = array('total'=>0 ,'basic'=> 0,'advanced'=> 0 ,'pro' => 0,'lead' => 0);
			$TotalPaymentQryLive = DB::table('paymenthistory as ph')->select('sub.id','sub.planname',DB::raw('sum(ph.amount) as sumAmount'))->leftJoin('subscriptionplans as sub', 'sub.id','=', 'ph.payment_type')->where('ph.status','approved')->whereNotNull('ph.payment_type')->where('ph.transactionfor','registrationfee');
			if($whereDate != '') {
				$TotalPaymentQryLive = $TotalPaymentQryLive->where('ph.created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalPaymentQryLive = $TotalPaymentQryLive->whereBetween('ph.created_at', $betweenArr);
			}
			$TotalPaymentLiveData = $TotalPaymentQryLive->groupby('sub.id')->get();
			if(!empty($TotalPaymentLiveData) && count($TotalPaymentLiveData)) {
				foreach($TotalPaymentLiveData as $TotalPaymentLiveDatas) {
					if($TotalPaymentLiveDatas->planname == 'Advanced') {
						$TotalPaymentLiveArr['advanced'] += (int)$TotalPaymentLiveDatas->sumamount;
						$TotalPaymentLiveArr['total'] += (int)$TotalPaymentLiveDatas->sumamount;
					} else if($TotalPaymentLiveDatas->planname == 'Marine Pro') {
						$TotalPaymentLiveArr['pro'] += (int)$TotalPaymentLiveDatas->sumamount;
						$TotalPaymentLiveArr['total'] += (int)$TotalPaymentLiveDatas->sumamount;
					} else if($TotalPaymentLiveDatas->planname == 'Basic') {
						$TotalPaymentLiveArr['basic'] += (int)$TotalPaymentLiveDatas->sumamount;
						$TotalPaymentLiveArr['total'] += (int)$TotalPaymentLiveDatas->sumamount;
					} 
				}
			}
			
			$TotalPaymentQryLiveLead = DB::table('paymenthistory')->select(DB::raw('sum(amount) as sumAmount'))->where('status','approved')->where('transactionfor','leadfee');
			if($whereDate != '') {
				$TotalPaymentQryLiveLead = $TotalPaymentQryLiveLead->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalPaymentQryLiveLead = $TotalPaymentQryLiveLead->whereBetween('created_at', $betweenArr);
			}
			$TotalPaymentLiveLeadData = $TotalPaymentQryLiveLead->get();
			if(!empty($TotalPaymentLiveLeadData) && count($TotalPaymentLiveLeadData) > 0) {
				$TotalPaymentLiveArr['lead'] = (int)$TotalPaymentLiveLeadData[0]->sumamount;
				$TotalPaymentLiveArr['total'] += $TotalPaymentLiveLeadData[0]->sumamount;
			}
			////////////////////////////////////////
			///////////////////// service request section /////////
			$whereReqQuery = '';
			if($whereDate != '') {
				$whereReqQuery = " AND rq.created_at > '".$whereDate."' ";
			}
			if($whereBetween != '') {
				$whereReqQuery = " AND rq.created_at >= '".$betweenArr[0]."' AND rq.created_at <= '".$betweenArr[1]."' "; 
			}
			//~ $resultServiceRequestSecAll = DB::select("SELECT count(rq.*) as requestcount,rq.status  FROM users_service_requests as urq JOIN request_proposals as rq ON rq.requestid = urq.id where rq.status != 'deleted' ".$whereReqQuery." group by rq.status");
             
			$TotalServiceRequestSecCmpQry = DB::table('users_service_requests')->select('status',DB::raw('count(status) as statuscount'));
			if($whereDate != '') {
				$TotalServiceRequestSecCmpQry = $TotalServiceRequestSecCmpQry->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$TotalServiceRequestSecCmpQry = $TotalServiceRequestSecCmpQry->whereBetween('created_at', $betweenArr);
			}
			$TotalServiceRequestSec = $TotalServiceRequestSecCmpQry->groupBy('status')->get();
			
			$TotalServiceRequestSecArr = array('total'=>0,'active' => 0 ,'completed'=> 0 ,'deleted' => 0);
			if(!empty($TotalServiceRequestSec) && count($TotalServiceRequestSec) > 0) {
				foreach($TotalServiceRequestSec as $TotalServiceRequestSecs) {
					if($TotalServiceRequestSecs->status == 'posted' || $TotalServiceRequestSecs->status == 'received_leads' ) {
						$TotalServiceRequestSecArr['active'] += (int)$TotalServiceRequestSecs->statuscount;
						$TotalServiceRequestSecArr['total'] += (int)$TotalServiceRequestSecs->statuscount;
					} else if($TotalServiceRequestSecs->status == 'completed') {
						$TotalServiceRequestSecArr['completed'] += (int)$TotalServiceRequestSecs->statuscount;
						$TotalServiceRequestSecArr['total'] += (int)$TotalServiceRequestSecs->statuscount;
					} else if($TotalServiceRequestSecs->status == 'deleted') {
						$TotalServiceRequestSecArr['deleted'] += (int)$TotalServiceRequestSecs->statuscount;
						$TotalServiceRequestSecArr['total'] += (int)$TotalServiceRequestSecs->statuscount;
					}
				}
			}
			
			//////////////////////////////////////////////////////
			
			/////////////  review and rating sec ///////////////
			
			$ViewQueryRateReviewQuery = ServiceRequestReviews::select(DB::raw('count(*) as requestcount'),DB::raw('avg(rating) as avgRate'),DB::raw('sum(rating) as sumRate'))->where('isdeleted','0');
			if($whereDate != '') {
				$ViewQueryRateReviewQuery = $ViewQueryRateReviewQuery->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$ViewQueryRateReviewQuery = $ViewQueryRateReviewQuery->whereBetween('created_at', $betweenArr);
			}
			
			$ViewQueryRateReview = $ViewQueryRateReviewQuery->get();
			$AllReviewArr = array('total'=>0,'avg' => 0 , 'sum' => 0);
			
			if(!empty($ViewQueryRateReview) && count($ViewQueryRateReview) > 0) {
				$AllReviewArr['total'] = $ViewQueryRateReview[0]->requestcount;
				$AllReviewArr['avg'] = $ViewQueryRateReview[0]->avgrate;
				$AllReviewArr['sum'] = $ViewQueryRateReview[0]->sumrate;
			}
			/////////// top 8 reviews //////////
			$getTopReviewQry = ServiceRequestReviews::select('comment','rating','created_at')->where('isdeleted','0');
			if($whereDate != '') {
				$getTopReviewQry = $getTopReviewQry->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$getTopReviewQry = $getTopReviewQry->whereBetween('created_at', $betweenArr);
			}
			$getTopReviewData = $getTopReviewQry->orderBy('created_at','DESC')->limit(8)->get();
			
			////////////////////////////////////
			////////////////////////////////////////////////////
			
			/////////////   account type sec ///////////////
			
			$TotalAccountSecCmpQry = Auth::select('usertype',DB::raw('count(usertype) as accountcount'))->where('accounttype','real')->where('status','!=','deleted');
			if($whereDate != '') {
				$TotalAccountSecCmpQry = $TotalAccountSecCmpQry->where('created_at','>',$whereDate);
			}
			
			if($whereBetween != '') {
				$TotalAccountSecCmpQry = $TotalAccountSecCmpQry->whereBetween('created_at', $betweenArr);
			}
			$TotalAccountSec = $TotalAccountSecCmpQry->groupBy('usertype')->get();
			
			$TotalAccountSecArr = array('admin'=>0,'regular' => 0 ,'business'=> 0 ,'professional' => 0,'yacht' => 0);
			if(!empty($TotalAccountSec) && count($TotalAccountSec) > 0) {
				foreach($TotalAccountSec as $TotalAccountSecs) {
					if($TotalAccountSecs->usertype == 'admin') {
						$TotalAccountSecArr['admin'] += (int)$TotalAccountSecs->accountcount;
					} else if($TotalAccountSecs->usertype == 'regular') {
						$TotalAccountSecArr['regular'] += (int)$TotalAccountSecs->accountcount;
					} else if($TotalAccountSecs->usertype == 'company') {
						$TotalAccountSecArr['business'] += (int)$TotalAccountSecs->accountcount;
					} else if($TotalAccountSecs->usertype == 'professional') {
						$TotalAccountSecArr['professional'] += (int)$TotalAccountSecs->accountcount;
					} else if($TotalAccountSecs->usertype == 'yacht') {
						$TotalAccountSecArr['yacht'] += (int)$TotalAccountSecs->accountcount;
					}
				}
			}
			
			////////////////////////////////////////////////
			
			////////////// latest query sec ///////////////
			
			$getTopContactusQry = Contactus::select('name','subject','message','created_at')->where('status','1');
			if($whereDate != '') {
				$getTopContactusQry = $getTopContactusQry->where('created_at','>',$whereDate);
			}
			if($whereBetween != '') {
				$getTopContactusQry = $getTopContactusQry->whereBetween('created_at', $betweenArr);
			}
			$getTopContactusData = $getTopContactusQry->orderBy('created_at','DESC')->limit(8)->get();
			
			///////////////////////////////////////////////
			$analyticsData = array('countdata' => array('registration' => array('dummy' => $DummyRegistrationData ,'total' => $totalRegistrationData ),'servicerequest' => array('complete' => $TotalServiceRequestCmp,'total' => $TotalServiceRequest),'claim' => array('approved' => $TotalClaimedApproved ,'total' => $TotalClaimedBusiness) ,'query' => array('read' => $TotalreadqueryData , 'total' => $TotalqueryData)),'livedata' => array('servicerequest' => $TotalServiceRequestLive , 'jobs' => $TotalJobsLive , 'contacttalent' => $TotalContactTalentLive ,'requestquote' => $TotalRequestQuotesLive),'payment' => $TotalPaymentLiveArr , 'allservice' => $TotalServiceRequestSecArr,'review_rating' => array('count' => $AllReviewArr ,'topreview' => $getTopReviewData ) , 'account' => $TotalAccountSecArr ,'topcontact' => $getTopContactusData);
			return response()->json(['success' => true,'data' => $analyticsData], $this->successStatus);
		}
		
	public function getAdminType(Request $request) {
		$authid = request('authid');
		$isSuperAdmin = false;
		/////// dummy registration ////////
		$admintypeData = Auth::where('id',(int)$authid)->where('status','!=','deleted')->get();
		if(!empty($admintypeData) && count($admintypeData) > 0) {
			if($admintypeData[0]->adminsubtype == 'superadmin') {
				$isSuperAdmin = true;
			}
		}
		return response()->json(['success' => true,'superadmin' => $isSuperAdmin], $this->successStatus);
	}
    public function getIp(){
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            $clientIpAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $clientIpAddress = $_SERVER['REMOTE_ADDR'];
        }
        return $clientIpAddress;
    }
    
     //get all admin except super admin
    public function getAllAdminData() {
		$get_admin = Auth::select('id','email','status','created_at','firstname_admin','lastname_admin')->where('usertype','=','admin')->where('status','active')->where('adminsubtype','admin')->orderBy('created_at','DESC')->get();
		if(!empty($get_admin)) {
			return response()->json(['success' => true,'data' => $get_admin], $this->successStatus);
		} else {
			return response()->json(['error'=>'networkerror'], 401);         
		}
    }
     //get all admin except super admin
    public function getAllAdminNoteData() {
		$adminData = [];
		$get_admin = Auth::select('id','email','status','created_at','firstname_admin','lastname_admin')->where('usertype','=','admin')->where('status','active')->where('adminsubtype','admin')->orderBy('created_at','DESC')->get();
		if(!empty($get_admin)) {
			if(count($get_admin) > 0) {
				$i = 0;
				foreach( $get_admin as $get_admins) {
					$adminData[$i]['id'] = $get_admins->id;
					$adminData[$i]['viewValue'] = $get_admins->firstname_admin.' '.$get_admins->lastname_admin;
					$i++;
				}
			}
			return response()->json(['success' => true,'data' => $adminData], $this->successStatus);
		} else {
			return response()->json(['error'=>'networkerror'], 401);         
		}
    }

     public function showStateResult(){
        $text = request('text');
        $state = json_decode(request('selectedState'));

        $whereSer =  '';
        if(!empty($text) && $text != '') {
            $whereSer = "WHERE statename ILIKE '".trim($text)."%'";
        }
        $whereNotIn = '';
        if(count($state)) {
            $states = implode("','",$state);
            if($whereSer != '') {
                $whereNotIn = "AND statename NOT IN ('".$states."')";
            } else {
                $whereNotIn = "WHERE statename NOT IN ('".$states."')";
            }       
        }
        // echo $whereNotIn;die;
        $state = DB::select("SELECT statename as \"itemName\" from usareas  ".$whereSer." ".$whereNotIn." GROUP BY statename ORDER BY \"itemName\" ASC");
        
        if(!empty($state)) {
            return response()->json(['success' => true,'data' =>$state ], $this->successStatus);
        } else {
            return response()->json(['success' => false], $this->successStatus);
        }
    }
    public function getCitiesforStates(){
        $text = request('text');
        $state = request('state');
        $selectedCity = request('selectedCity');
        $whereNotIn = [];
        if(!empty($selectedCity)) {
            $whereNotIn = json_decode($selectedCity);    
        }
        $query = DB::table('usareas')->select('city as itemName','state')->whereIn('statename',json_decode($state))->where('status','1');
        if(count($whereNotIn)) {
            $query = $query->whereNotin('city',$whereNotIn);
        }
        if(!empty($text)) {
            $query = $query->whereRaw("city ILIKE '".trim($text)."%'");
        }
        $allCity = $query->groupby('city','state')->orderBy('city','ASC')->get();
        if(!empty($allCity)) {
            return response()->json(['success' => true,'data' =>$allCity ], $this->successStatus);
        } else {
            return response()->json(['success' => false], $this->successStatus);
        }
    }

    public function getZipcodeForSelectedCity(){
        $city = request('city');
        $text = request('text');
        $whereNotIn = [];
        $selectedZipcode = request('selectedZipcode');
        if(!empty($selectedZipcode)) {
            $whereNotIn = json_decode($selectedZipcode);    
        }
        $query = DB::table('usareas')->select('zipcode as itemName','city')->whereIn('city',json_decode($city));
        if(!empty($text)) {
            $query = $query->whereRaw("zipcode LIKE '".trim($text)."%'");
        }
        if(count($whereNotIn)) {
            $query = $query->whereNotin('zipcode',$whereNotIn);
        }
        $allZipcode = $query->where('status','1')->groupby('zipcode','city')->orderBy('zipcode','ASC')->get();
        if(!empty($allZipcode)) {
            return response()->json(['success' => true,'data' =>$allZipcode ], $this->successStatus);
        } else {
            return response()->json(['success' => false], $this->successStatus);
        } 
    }
}
?>
