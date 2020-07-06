<?php

use Illuminate\Http\Request;
Use App\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('v1/adminlogin', 'AuthController@adminlogin');
Route::post('v1/userlogin', 'AuthController@userlogin');
Route::post('v1/checkSocialUserExist', 'AuthController@checkSocialUserExist');
Route::group(['middleware' => 'auth:api'], function(){
	//
	Route::get('v1/adminlogout', 'AuthController@adminlogout');
	Route::get('v1/logout', 'AuthController@userlogout');
	
	//user routes //
	Route::post('v1/adduser', 'Admin\UserController@addUser');
	Route::post('v1/edituser', 'Admin\UserController@editUser');
	Route::post('v1/searchusers', 'Admin\UserController@searchUsers');
	Route::post('v1/getuserdetail', 'Admin\UserController@getUserDetail');
	Route::post('v1/changestatus', 'Admin\UserController@changeStatus');
	Route::get('v1/getusersdata', 'Admin\UserController@index');
	Route::post('v1/deleteuser', 'Admin\UserController@deleteuser');
	Route::get('v1/checkEmail', 'Admin\UserController@checkEmail');
	Route::get('v1/getAllCountries', 'Admin\UserController@getAllCountries');
	Route::get('v1/getAllStates', 'Admin\UserController@getAllStates');
	Route::get('v1/getAllCountriesData', 'Admin\UserController@getAllCountriesDetail');
	Route::get('v1/getAllStatesData', 'Admin\UserController@getAllStatesDetail');
	Route::post('v1/updateuseraccount', 'Admin\UserController@updateuseraccount');
	Route::post('v1/updateadminpassword', 'Admin\UserController@updateadminpassword');
	Route::post('v1/importboatusers', 'Admin\UserController@importBoatUsers');
	Route::get('v1/checkemailexistemailadmin', 'Admin\UserController@checkemailexistemailadmin');
	Route::get('v1/exportUserData', 'Admin\UserController@exportUserData');
	Route::post('v1/importUserData', 'Admin\UserController@importUserData');

	//company routes //
	Route::post('v1/addcompany', 'Admin\CompanyController@addCompany');
	Route::post('v1/editcompany', 'Admin\CompanyController@editCompany');
	Route::post('v1/searchcompany', 'Admin\CompanyController@searchCompany');
	Route::post('v1/getcompanydetail', 'Admin\CompanyController@getCompanyDetail');
	Route::post('v1/editAssignInfo', 'Admin\CompanyController@editAssignInfo');
	Route::post('v1/getassignadmindetail', 'Admin\CompanyController@getassignadmindetail');
	Route::post('v1/deleteNote', 'Admin\CompanyController@deleteNote');
	Route::post('v1/changecompanystatus', 'Admin\CompanyController@changeStatus');
	Route::post('v1/createCustomer', 'Admin\CompanyController@createCustomerAccount');
	Route::get('v1/getcompaniesdata', 'Admin\CompanyController@index');
	Route::post('v1/deletecompany', 'Admin\CompanyController@deleteCompany');
	Route::post('v1/addS3Images', 'Admin\CompanyController@addS3Images');
	Route::post('v1/setPrimaryImages', 'Admin\CompanyController@setPrimaryImages');
	Route::post('v1/deleteCompanyImage', 'Admin\CompanyController@deleteCompanyImage');
	Route::post('v1/updatecompanyaccount', 'Admin\CompanyController@updateaccount');
	Route::post('v1/getimagesdata', 'Admin\CompanyController@getImagesData');
	Route::get('v1/getUserPlanGeoLocation', 'Admin\CompanyController@getUserPlanGeoLocation');	
	// Route::post('v1/addGeoLocation', 'Admin\CompanyController@addGeolocation');
	Route::get('v1/getGeolocationDetail', 'Admin\CompanyController@getGeolocationsById');
	Route::post('v1/editGeolocation', 'Admin\CompanyController@editGeolocation');
	// Route::post('v1/deleteGeolocation', 'Admin\CompanyController@deleteGeolocation');
	Route::post('v1/trialPaymentPlan', 'Admin\CompanyController@trialpaymentplan');
	Route::get('v1/getCurrentPlan', 'Admin\CompanyController@getCurrentPlan');
	Route::post('v1/deletegeolocation', 'Admin\CompanyController@deleteGeolocation');
	Route::post('v1/changeAdsStatus', 'Admin\CompanyController@addAdsStatus');
	Route::get('v1/exportCompanyData', 'Admin\CompanyController@exportCompanyData');
	Route::get('v1/getanalyticsBiz', 'Admin\CompanyController@getanalyticsBiz');
	Route::post('v1/getBusinessDashboardLeadAdmin', 'Admin\CompanyController@getBusinessDashboardLead');
	//professional routes //
	Route::post('v1/addprofessional', 'Admin\TalentController@addProfessional');
	Route::post('v1/editprofessional', 'Admin\TalentController@editProfessional');
	Route::post('v1/getprofessionaldetail', 'Admin\TalentController@getProfessionalDetail');
	Route::post('v1/changeprofessionalstatus', 'Admin\TalentController@changeStatus');
	Route::get('v1/getprofessionalsdata', 'Admin\TalentController@index');
	Route::post('v1/deleteprofessional', 'Admin\TalentController@deleteProfessional');
	Route::get('v1/checkprofessionalemail', 'Admin\TalentController@checkEmail');
	Route::post('v1/updateprofessionalaccount', 'Admin\TalentController@updateaccount');
	Route::get('v1/exportProfessionalData', 'Admin\TalentController@exportProfessionalData');
	
	// service route //
	Route::post('v1/addService', 'ServiceController@addService');
	Route::post('v1/changeservicestatus', 'ServiceController@changeStatus');
	Route::post('v1/editservice', 'ServiceController@editService');
	Route::get('v1/getallcategory', 'ServiceController@getAllServiceCategory');
	Route::get('v1/getservicelist', 'ServiceController@getAllServiceByCategoryId');	
	Route::post('v1/addcategory', 'ServiceController@addCategory');
	Route::post('v1/editcategory', 'ServiceController@editCategory');		
	Route::get('v1/getcategorydetails', 'ServiceController@getCategoryDetail');	
	Route::post('v1/deletecategory', 'ServiceController@deleteCategory');	
	Route::post('v1/deleteservice', 'ServiceController@deleteService');	
	Route::post('v1/addservice', 'ServiceController@addService');
	Route::post('v1/getservicedata', 'ServiceController@getServiceData');

	//Yacht Route
	Route::post('v1/addYacht', 'Admin\YachtController@addYacht');
	Route::get('v1/getyachtdata', 'Admin\YachtController@getyachtdata');
	Route::post('v1/editYacht', 'Admin\YachtController@editYacht');
	Route::get('v1/getyachtdetailbyid', 'Admin\YachtController@getYachtDetailById');
	Route::post('v1/updateyachtaccount', 'Admin\YachtController@updateaccount');
	Route::post('v1/deleteYacht', 'Admin\YachtController@deleteYacht');
	Route::post('v1/changeyachtstatus', 'Admin\YachtController@changeStatus');
	Route::post('v1/addS3ImagesYacht', 'Admin\YachtController@addS3Images');
	Route::post('v1/setPrimaryImagesYacht', 'Admin\YachtController@setPrimaryImages');
	Route::post('v1/deleteImageYacht', 'Admin\YachtController@deleteCompanyImage');
	Route::post('v1/getimagesdataYacht', 'Admin\YachtController@getImagesData');
	Route::get('v1/exportYachtData', 'Admin\YachtController@exportYachtData');
	Route::get('v1/gettotaluserwithamount', 'Admin\CompanyController@getTotalUserwithamount');

	//Admin Controller
	Route::get('v1/managecontactus', 'Admin\AdminController@manageContactUs');
	Route::get('v1/getcontactusdetail', 'Admin\AdminController@getContactusDetail');
	Route::get('v1/deletecontactus', 'Admin\AdminController@deletContactus');
	Route::get('v1/getalljobtitles','Admin\AdminController@getAllJobTitles');
	Route::post('v1/deletejobtitle','Admin\AdminController@changeStatus');	
	Route::get('v1/getjobtitle','Admin\AdminController@getJobTitlesDetail');	
	Route::post('v1/addjobtitle','Admin\AdminController@addJobTitle');
	Route::post('v1/editjobtitle','Admin\AdminController@addJobTitle');
	Route::get('v1/getothertitles','Admin\AdminController@getAllOtherTitle');
	Route::post('v1/assignjobtitle','Admin\AdminController@assignJobtitle');
	Route::post('v1/approveothertitle','Admin\AdminController@approveOtherTitle');	
	//company routes //
	Route::post('v1/adddummycompany', 'Admin\DummyCompanyController@addCompany');
	Route::post('v1/editdummycompany', 'Admin\DummyCompanyController@editCompany');
	Route::post('v1/getdummycompanydetail', 'Admin\DummyCompanyController@getCompanyDetail');
	Route::post('v1/changedummycompanystatus', 'Admin\DummyCompanyController@changeStatus');
	Route::get('v1/getdummycompaniesdata', 'Admin\DummyCompanyController@index');
	Route::post('v1/deletedummycompany', 'Admin\DummyCompanyController@deleteCompany');
	Route::get('v1/getclaimedCompanies', 'Admin\DummyCompanyController@getclaimedCompanies');
	Route::post('v1/rejectclaimedCompany', 'Admin\DummyCompanyController@rejectclaimedCompanies');
	Route::post('v1/approvedclaimedCompany', 'Admin\DummyCompanyController@approvedclaimedCompanies');
	Route::get('v1/getclaimedCompanyData', 'Admin\DummyCompanyController@getclaimedCompanyData');
	
	Route::post('v1/editDummyAssignInfo', 'Admin\DummyCompanyController@editAssignInfo');
	Route::post('v1/getDummyassignadmindetail', 'Admin\DummyCompanyController@getassignadmindetail');
	Route::post('v1/deleteDummyNote', 'Admin\DummyCompanyController@deleteNote');
	Route::get('v1/getclaimedcompaniesdata', 'Admin\DummyCompanyController@getclaimedcompaniesdata');

	Route::post('v1/addclaimedS3Images', 'Admin\DummyCompanyController@addS3Images');
	Route::post('v1/setclaimedPrimaryImages', 'Admin\DummyCompanyController@setPrimaryImages');
	Route::post('v1/deleteclaimedCompanyImage', 'Admin\DummyCompanyController@deleteCompanyImage');
	Route::post('v1/updateclaimedcompanypassword', 'Admin\DummyCompanyController@updatepassword');
	Route::post('v1/getclaimedimagesdata', 'Admin\DummyCompanyController@getImagesData');
	Route::get('v1/exportDummyCompanyData', 'Admin\DummyCompanyController@exportDummyCompanyData');


	// Route::get('v1/getClaimedGeolocationDetail', 'Admin\DummyCompanyController@getGeolocationsById');
	// Route::post('v1/editClaimedGeolocation', 'Admin\DummyCompanyController@editGeolocation');
	// Route::post('v1/deleteClaimedGeolocation', 'Admin\DummyCompanyController@deleteGeolocation');
	// Route::get('v1/getClaimedUserPlanGeoLocation', 'Admin\DummyCompanyController@getUserPlanGeoLocation');	
	// Route::post('v1/additionalclaimedgeopayment', 'Admin\DummyCompanyController@addGeolocationpayment');
	Route::post('v1/getclaimedcompanydetail', 'Admin\DummyCompanyController@getClaimedCompanyDetail');
	Route::post('v1/editclaimedcompany', 'Admin\DummyCompanyController@editClaimedCompany');	

	// logged user route //
	Route::get('v1/getBusinessDetailById', 'LoggeduserController@getBusinessDetailBySlug');
	Route::post('v1/addBoatSlipReq', 'LoggeduserController@addBoatSlipReq');
	Route::get('v1/getBusinessContent', 'LoggeduserController@getBusinessContent');
	Route::get('v1/getProfessionalContent', 'LoggeduserController@getProfessionalContent');
	Route::get('v1/getYachtContent', 'LoggeduserController@getYachtContent');
	Route::get('v1/getUserContent', 'LoggeduserController@getUserContent');
	Route::post('v1/addS3Imagesbusiness', 'LoggeduserController@addS3Imagesbusiness');
	
	Route::post('v1/updateCompanyProfile', 'LoggeduserController@updateCompanyProfile');
	Route::post('v1/updateS3Images', 'LoggeduserController@updateS3Images');
	Route::post('v1/deleteCompanyImagePortfolio', 'LoggeduserController@deleteCompanyImage');
	Route::post('v1/changeBusinessPassword', 'LoggeduserController@changeBusinessPassword');
	Route::post('v1/changeRequestContent', 'LoggeduserController@changeRequestContent');
	Route::post('v1/changeQuoteContent', 'LoggeduserController@changeQuoteContent');
	Route::post('v1/changeContactContent', 'LoggeduserController@changeContactContent');
	Route::post('v1/changeJobContent', 'LoggeduserController@changeJobContent');
	Route::post('v1/getBusinessLead', 'LoggeduserController@getBusinessLead');
	Route::post('v1/getBusinessDashboardLead', 'LoggeduserController@getBusinessDashboardLead');
	Route::post('v1/getAllVacanciesByUserId', 'LoggeduserController@getAllVacanciesByUserId');
	Route::post('v1/getAllReceiveMessages', 'LoggeduserController@getAllReceiveMessages');
	Route::get('v1/sendmelead', 'LoggeduserController@sendMeLead');
	Route::get('v1/isLeadSend', 'LoggeduserController@issendLead');
	Route::get('v1/getinboxmessage', 'LoggeduserController@getinboxmessage');
	Route::get('v1/getMessageDetail', 'LoggeduserController@getMessageDetail');
	Route::get('v1/getBusinessLocationById', 'LoggeduserController@getBusinessLocationById');
	Route::post('v1/addVacancies', 'LoggeduserController@addVacancies');
	Route::post('v1/replytoMessage', 'LoggeduserController@replytoMessage');
	Route::post('v1/deleteMessages', 'LoggeduserController@deleteMessages');
	Route::post('v1/getYachtOwnerDetail', 'LoggeduserController@getYachtOwnerDetail');
	
	Route::get('v1/isContactNow', 'LoggeduserController@isContactNow');
	Route::post('v1/sendContactNow', 'LoggeduserController@sendContactNow');
	//new 
	// route for yacht after login
	Route::get('v1/getYachtProfileById', 'LoggeduserController@getYachtProfileById');
    Route::get('v1/getYachtDataWRating', 'LoggeduserController@getYachtOwnerDetailwithoutRating');
    Route::post('v1/addS3CoverYacht', 'LoggeduserController@addS3ImagesYacht');
    Route::post('v1/updateYachtPersonal', 'LoggeduserController@updateYachtPersonal');
    Route::post('v1/getyachtowneryachtdetail', 'LoggeduserController@getYachtOwnerYachtdetail');
    Route::post('v1/updateyachtowneryachtDetail', 'LoggeduserController@updateYachtOwnerYachtDetail');
    Route::post('v1/updateYachtS3Images', 'LoggeduserController@updateYachtS3Images');
    Route::post('v1/deleteYachtImagePortfolio', 'LoggeduserController@deleteYachtImage');
    Route::post('v1/changeYachtPassword', 'LoggeduserController@changeYachtPassword');
    Route::post('v1/getYachtServiceRequest', 'LoggeduserController@getYachtServiceRequest');
    Route::post('v1/addYachtServiceRequest', 'LoggeduserController@addYachtServiceRequest');
    Route::post('v1/getAllYachtVacanciesById', 'LoggeduserController@getAllYachtVacanciesById');
    Route::post('v1/addYachtVacancies', 'LoggeduserController@addYachtVacancies');
    Route::get('v1/getallAds', 'Admin\AdminController@getallAds');
   	Route::post('v1/deleteAdvertisement', 'Admin\AdminController@deleteAdvertisement');
   	Route::post('v1/addAdvertisement', 'Admin\AdminController@addAdvertisement');
   	Route::get('v1/getadvertiseData', 'Admin\AdminController@getAdvertisementDetail');
   	Route::post('v1/editAdvertisement', 'Admin\AdminController@editAdvertisement');
   	// routes for boat owner
   	Route::post('v1/getBoatOwnerDetailsById', 'LoggeduserController@getBoatOwnerDetailsById');
   	Route::post('v1/addS3Imagesboatowner', 'LoggeduserController@addS3Imagesboatowner');
   	Route::post('v1/getBoatOwnerProfileById', 'LoggeduserController@getBoatOwnerProfileById');
   	Route::post('v1/updateBoatOwnerProfile', 'LoggeduserController@updateBoatOwnerProfile');
   	Route::post('v1/changeBoatOwnerPassword', 'LoggeduserController@changeBoatOwnerPassword');
   	Route::post('v1/getBoatOwnerServiceRequest', 'LoggeduserController@getBoatOwnerServiceRequest');
    Route::post('v1/addBoatOwnerServiceRequest', 'LoggeduserController@addBoatOwnerServiceRequest');
    Route::post('v1/addInnerFishCharterReq', 'LoggeduserController@addFishCharterReq');
    Route::post('v1/addS3ImagesboatownerProfile', 'LoggeduserController@addS3ImagesboatownerProfile');

    Route::post('v1/addS3ProfileYacht', 'LoggeduserController@addS3ProfileImageYacht');
    Route::post('v1/changeProfileImagebusiness', 'LoggeduserController@changeProfileImagebusiness');
    Route::get('v1/getUserNotification', 'LoggeduserController@getUserNotification');
    Route::get('v1/getUnreadMessage', 'LoggeduserController@getMessageUnread');
    Route::get('v1/getNotificationData', 'LoggeduserController@getNotificationData');
    Route::get('v1/changeBookmarkStatus', 'LoggeduserController@changeBookmarkStatus');
    // routes for professional after login
    Route::post('v1/getProfessionalProfileById', 'LoggeduserController@getProfessionalProfileById');
    Route::post('v1/getProfessionalDetailsById', 'LoggeduserController@getProfessionalDetailsById');
    Route::post('v1/changeProfessionalPassword', 'LoggeduserController@changeProfessionalPassword');
    Route::post('v1/addS3CoverProfessional', 'LoggeduserController@addS3ImagesProfessional');
    Route::post('v1/addS3ProfileProfessional', 'LoggeduserController@addS3ProfileImageProfessional');
    Route::post('v1/updateProfessionalPersonal', 'LoggeduserController@updateProfessionalPersonal');
    Route::post('v1/updateProfessionalDetail', 'LoggeduserController@updateProfessionalDetail');
    Route::post('v1/getAllJobByProffId', 'LoggeduserController@getAllJobByProffId');
    Route::post('v1/addRemoveBookmarkProff', 'LoggeduserController@addRemoveBookmarkProff');
    Route::post('v1/getBookmarkJobsProff', 'LoggeduserController@getBookmarkJobsProff');
	Route::get('v1/applyForJob', 'LoggeduserController@applyForJob');
	Route::get('v1/changeJobStatus', 'LoggeduserController@changeJobStatus');
	Route::get('v1/changeserviceRequestStatus', 'LoggeduserController@changeserviceRequestStatus');
	Route::get('v1/changeRequestStatus', 'LoggeduserController@changeRequestStatus');
	Route::get('v1/changeBookmarkRequestStatus', 'LoggeduserController@changeBookmarkRequestStatus');
	Route::post('v1/getBookmarkLeadsCompany', 'LoggeduserController@getBookmarkLeadsCompany');
	Route::post('v1/addRemoveBookmarkLead', 'LoggeduserController@addRemoveBookmarkLead');
	Route::post('v1/sendReviewandRating', 'LoggeduserController@sendReviewandRating');
	Route::get('v1/askForReview', 'LoggeduserController@askForReview');
	Route::post('v1/sendReplyReviewRate', 'LoggeduserController@sendReplyReviewRate');
	Route::get('v1/checkValidReviewRequest', 'LoggeduserController@checkValidReviewRequest');
	Route::get('v1/getreviewThreadInfo', 'LoggeduserController@getreviewThreadInfo');
	Route::post('v1/replyReviewThread', 'LoggeduserController@replyReviewThread');
	Route::get('v1/deleteReviewThread', 'LoggeduserController@deleteReviewThread');
	Route::get('v1/checkReplyRequest', 'LoggeduserController@checkReplyRequest');
	Route::post('v1/sendReviewandRating', 'LoggeduserController@sendReviewandRating');
    Route::get('v1/askForReview', 'LoggeduserController@askForReview');
    Route::post('v1/addAdmin', 'Admin\AdminController@addAdmin');
    Route::get('v1/getPreviewBusinessDetail', 'Admin\CompanyController@getPreviewBusinessDetail');
    Route::get('v1/getAllAdmin', 'Admin\AdminController@getAllAdmin');
    Route::get('v1/getAllAdminData', 'Admin\AdminController@getAllAdminData');
    Route::get('v1/getAllAdminNoteData', 'Admin\AdminController@getAllAdminNoteData');
    Route::post('v1/deleteAdmin', 'Admin\AdminController@deleteAdmin');
    Route::post('v1/editAdmin', 'Admin\AdminController@editAdmin');
    Route::post('v1/getBlogs', 'Admin\AdminController@getBlogs');
	Route::post('v1/addBlog', 'Admin\AdminController@addBlog');
	Route::post('v1/editBlog', 'Admin\AdminController@editBlog');
	Route::post('v1/deleteBlog', 'Admin\AdminController@deleteBlog');
	Route::post('v1/getBlogById', 'Admin\AdminController@getBlogById');
	Route::get('v1/checkBusinessLeadLimit', 'LoggeduserController@checkBusinessSendLeadLimit');
	Route::get('v1/checkAdminPrivilage', 'Admin\AdminController@checkAdminPrivilage');
	Route::get('v1/getAllReviewAndComments', 'LoggeduserController@getAllReviewAndComments');
	Route::get('v1/getReviewInfo', 'HomeController@getReviewInfo');
	Route::get('v1/getAllNotification', 'LoggeduserController@getAllNotification');
	Route::get('v1/updateNotification', 'LoggeduserController@updateNotification');
	Route::get('v1/getanalytics', 'LoggeduserController@getanalytics');		
	Route::get('v1/managepayment', 'Admin\AdminController@managePayment');
	Route::post('v1/getallsubcategorybyid', 'ServiceController@getallsubcategorybyid');
	Route::post('v1/addsubcategory', 'ServiceController@addsubcategory');
	Route::post('v1/deletesubcategory','ServiceController@deletesubcategory');
    Route::post('v1/getsubcategorydetailbyid','ServiceController@getsubcategorydetailbyid');
    Route::post('v1/editsubcategory','ServiceController@editsubcategory');
    Route::get('v1/getservicesforSubcat','ServiceController@getservicesforSubcat');
    Route::get('v1/getSubCatListFromCatId','ServiceController@getSubCatListFromCatId');
	Route::get('v1/getSubCatByServiceId','ServiceController@getSubCatByServiceId');
	Route::get('v1/isloggedin', 'AuthController@checkUserLoggedIn');
	
	Route::get('v1/getCompanyProfileById', 'LoggeduserController@getCompanyProfileById');
	Route::post('v1/changeServiceLocations', 'LoggeduserController@changeServiceLocations');
	Route::post('v1/changeAdminServiceLocations', 'Admin\CompanyController@changeServiceLocations');
	Route::get('v1/getLocationInfoAdmin', 'Admin\CompanyController@getLocationInfo');
	Route::post('v1/changeEmailAddress', 'LoggeduserController@changeEmailAddress');

	//Manage service request
	Route::get('v1/manageServiceRequest','Admin\AdminController@manageServiceRequest');
	Route::get('v1/deleteServiceRequest','Admin\AdminController@deleteServiceRequest');
	Route::get('v1/getServiceReqDetailById','Admin\AdminController@getServiceReqDetailById');
	Route::get('v1/showLeadPerRequest','Admin\AdminController@showLeadPerRequest');
	Route::get('v1/deleteAppliedBusinessLead','Admin\AdminController@deleteAppliedBusinessLead');
	// View Jobs
    Route::get('v1/ViewJoblist','Admin\AdminController@ViewJoblist');
    Route::get('v1/deleteJob','Admin\AdminController@deleteJob');
    Route::get('v1/getJobDetailById','Admin\AdminController@getJobDetailById');
    Route::get('v1/showProffByJob','Admin\AdminController@showProffByJob');
    Route::get('v1/deleteProffApplication','Admin\AdminController@deleteProffApplication');
    Route::get('v1/getMsgBtwLead','Admin\AdminController@getMsgBtwLead');
    Route::get('v1/getMsgbtnJob','Admin\AdminController@getMsgbtnJob');
    Route::get('v1/getreviewThreadInfoProfile', 'LoggeduserController@getreviewThreadInfoProfile');
	Route::post('v1/replyReviewThreadProfile', 'LoggeduserController@replyReviewThreadProfile');
	Route::get('v1/deleteReviewReply', 'LoggeduserController@deleteReviewReply');
	Route::get('v1/deleteReviewReplyProfile', 'LoggeduserController@deleteReviewReplyProfile');
	Route::get('v1/getReviewInfoProfile', 'LoggeduserController@getReviewInfoProfile');
	Route::post('v1/getAllCards','LoggeduserController@getAllBusinessCards');
    Route::post('v1/deleteToken','LoggeduserController@deleteToken');

    Route::get('v1/ViewProffList','Admin\AdminController@ViewProffList');
    Route::get('v1/getMessageContProff','Admin\AdminController@getMessageContProff');
	// add payment method
	Route::post('v1/addPaymentMethod','LoggeduserController@addPaymentMethod');
	Route::post('v1/changeDefaultPaymentCard','LoggeduserController@changeDefaultPaymentCard');
	Route::get('v1/getClientTokenAdd','LoggeduserController@getBraintreeTokenUser');

	//add payment methond end

	// View Quotes routes
    Route::get('v1/viewQuote','Admin\AdminController@viewQuote');
    Route::get('v1/deleteQuote','Admin\AdminController@deleteQuote');
    Route::get('v1/getQuoteDetailById','Admin\AdminController@getQuoteDetailById');
    Route::get('v1/getMsgBtwQuote','Admin\AdminController@getMsgBtwQuote');
    
 	// View Review routes
    Route::get('v1/ViewReveiwList','Admin\AdminController@ViewReveiwList');
    Route::get('v1/deleteReview','Admin\AdminController@deleteReview');
    Route::get('v1/getReviewDetailById','Admin\AdminController@getReviewDetailById');

    // badwords route
    Route::post('v1/deleteBadword','Admin\AdminController@deleteBadword');
    Route::post('v1/addBadword', 'Admin\AdminController@addBadword');
    // get admin prfile details 
    Route::get('v1/getAdminDetailProf', 'Admin\UserController@getAdminDetailProf');
    // update admin profile
    Route::post('v1/updateadminprofile', 'Admin\UserController@updateadminprofile');
    //Create Free Account 
    Route::post('v1/makeFreeAccount', 'Admin\AdminController@makeFreeAccount');
    Route::post('v1/makePaidAccount', 'Admin\AdminController@makePaidAccount');
    Route::get('v1/exportCompanyDataFilter', 'Admin\CompanyController@exportCompanyDataFilter');
    Route::post('v1/importCompanyData', 'Admin\CompanyController@importCompanyData');
    Route::post('v1/importDummyCompanyData', 'Admin\DummyCompanyController@importDummyCompanyData');
    Route::post('v1/importYachtData', 'Admin\YachtController@importYachtData');
    Route::post('v1/importProfessionalData', 'Admin\TalentController@importProfessionalData');
    Route::get('v1/newsletterusers', 'Admin\AdminController@newsletterusers');
    Route::get('v1/exportNewsletter', 'Admin\AdminController@exportNewsletter');
    //website rating
    Route::post('v1/sendWebsiteReviewandRating', 'LoggeduserController@sendWebsiteReviewandRating');
	Route::post('v1/checkWebsiteReviewandRating', 'LoggeduserController@checkWebsiteReviewandRating');
	//Admin Ads
	Route::get('v1/showStateResult', 'Admin\AdminController@showStateResult');
	Route::get('v1/getCitiesforStates', 'Admin\AdminController@getCitiesforStates');
	Route::get('v1/getZipcodeForSelectedCity', 'Admin\AdminController@getZipcodeForSelectedCity');
	Route::get('v1/checkRequestQuoteLimit', 'LoggeduserController@checkBusinessSendQuoteLimit');
	 
});
	
	Route::get('v1/getAdminType','Admin\AdminController@getAdminType');
    Route::get('v1/getanalyticsAdmin','Admin\AdminController@getanalyticsAdmin');
	Route::get('v1/checkCompanyPament', 'Admin\CompanyController@checkcompanyPayment');	
	Route::post('v1/companyPayment', 'Admin\CompanyController@companyPayment');
	Route::post('v1/companyloggedpayment', 'LoggeduserController@companyloggedpayment');
	Route::post('v1/trialbusinesspaymentplan', 'LoggeduserController@trialbusinesspaymentplan');
	Route::post('v1/changeCompanyPaymentStatus', 'LoggeduserController@changeCompanyPaymentStatus');
	Route::get('v1/companyCurrentPlan', 'LoggeduserController@companyCurrentPlan');
	Route::post('v1/paymentHistory', 'LoggeduserController@paymentHistory');
	
    Route::get('v1/getAdminDetails', 'Admin\AdminController@getAdminDetails');
	// service routes //
	Route::get('v1/getallservice', 'ServiceController@getAllservice');
	Route::get('v1/getservices', 'ServiceController@index');
	Route::post('v1/getServiceDetail', 'ServiceController@getServiceDetail');
	Route::post('v1/registration', 'RegistrationController@registrationStage1');
	Route::post('v1/registrationStage2', 'RegistrationController@registrationStage2');
	Route::post('v1/discountRegistration', 'RegistrationController@discountRegistration');
	Route::get('v1/getallcountries', 'RegistrationController@getAllCountries');
	Route::get('v1/getallstates', 'RegistrationController@getAllStates');
	Route::get('v1/getallcounty', 'RegistrationController@getallCounty');
	Route::get('v1/checkemailexist', 'RegistrationController@checkEmail');
	Route::post('v1/companypayment', 'RegistrationController@companypayment');
	Route::post('v1/companypaymentDiscount', 'RegistrationController@companypaymentDiscount');
	Route::get('v1/getallusstates', 'RegistrationController@getallusstates');
	Route::get('v1/getallcityZip', 'RegistrationController@getallcityZip');
	Route::get('v1/getallZipcode', 'RegistrationController@getallZipcode');
	Route::post('v1/addprofessionadetail', 'RegistrationController@registerProfessionalStage3');
	Route::post('v1/addyachtdetail', 'RegistrationController@registerYachtStage3');
	Route::get('v1/checkSubPlan', 'RegistrationController@subscriptionplan');
	Route::get('v1/companypaymenthistory', 'RegistrationController@getBusinessDetail');
	Route::get('v1/getcategoryandservice', 'RegistrationController@getAllyacht');
	Route::get('v1/getallprofessionaldetail', 'RegistrationController@getProfessionalDataById');
	Route::get('v1/getTempallprofessionaldetail', 'RegistrationController@getTempProfessionalDataById');
	Route::get('v1/getallcompanydetail', 'RegistrationController@getCompanyDataById');
	Route::get('v1/getTempallcompanydetail', 'RegistrationController@getTempCompanyDataById');
	Route::get('v1/getallyachtdetail', 'RegistrationController@getYachtDataById');
	Route::get('v1/getTempallyachtdetail', 'RegistrationController@getTempYachtDataById');
	Route::get('v1/getTempalluserdetail', 'RegistrationController@getTempUserDataById');
	
	Route::get('v1/getallplans', 'RegistrationController@getAllplans');
	Route::get('v1/getsubscriptionInfo', 'RegistrationController@getsubscriptionInfo');
	Route::get('v1/getcurrentplan', 'RegistrationController@getCurrentPlan');
	// Route::get('v1/getuserplangeolocation', 'RegistrationController@getUserPlanGeoLocation');	
	Route::post('v1/trialpaymentplan', 'RegistrationController@trialpaymentplan');
	// Route::post('v1/addgeolocation', 'RegistrationController@addGeolocation');
	// Route::post('v1/additionalgeopayment', 'RegistrationController@addGeolocationpayment');	
	Route::get('v1/getalldistinctservice', 'ServiceController@getAllDistinctservice');
	Route::post('v1/contactus','HomeController@contactus');
	//Route::get('v1/gettest', 'HomeController@gettest');
	Route::get('v1/getTopRatedCompanies','HomeController@getHighestRatedCompanies');
	Route::get('v1/getServiceslist', 'HomeController@getAllBusinessesByLocation');
	Route::get('v1/getAllservicewithid', 'ServiceController@getAllservicewithid');	
	Route::get('v1/getAllVacanciesByLocation', 'HomeController@getAllVacanciesByLocation');	
	Route::get('v1/getAllJobsByLocation', 'HomeController@getAllJobsByLocation');
	Route::get('v1/getAllProfessionalByLocation', 'HomeController@getAllProfessionalByLocation');
	Route::get('v1/getallcategoriesid', 'ServiceController@getallcategoriesId');
	Route::get('v1/getAllJobTitles', 'HomeController@getAllJobTitles');
	Route::get('v1/getalljobtitle', 'HomeController@getAllJobsTitle');
	Route::get('v1/getAllUserServiceRequests', 'HomeController@getAllUserServiceRequests');
	Route::get('v1/getservicebycategory', 'ServiceController@getServiceByCategoryName');	
	Route::post('v1/saveQuoteRequest', 'HomeController@saveQuoteRequest');
	Route::get('v1/getUserRequestDetails', 'HomeController@getUserRequestDetails');
	Route::get('v1/getBusinessDetailBySlug', 'HomeController@getBusinessDetailBySlug');
	Route::get('v1/getLatestRatings', 'HomeController@getLatestRatings');
	Route::get('v1/getjobsDetailById', 'HomeController@getjobsDetailById');
	Route::get('v1/listingevent', 'HomeController@addListingClickEvent');	
	Route::get('v1/getProfessionaldetails', 'HomeController@getProfessionalDetailById');
	Route::get('v1/getboatownerdetail', 'HomeController@getBoatOwnerDetails');
	Route::get('v1/getyachtdetail', 'HomeController@getYachtDetail');
	Route::get('v1/getalldummycompanydetail', 'RegistrationController@getDummyCompanyDataById');
	Route::get('v1/getGeolocationData', 'RegistrationController@getGeolocationsById');
	Route::post('v1/trialdummypaymentplan', 'RegistrationController@trialdummypaymentplan');
	Route::post('v1/companydummypayment', 'RegistrationController@companydummypayment');
	Route::post('v1/adddummygeolocation', 'RegistrationController@addDummyGeolocation');
	// Route::post('v1/deletebusinessgeolocation', 'RegistrationController@deleteGeolocation');
	Route::post('v1/updateGeolocation', 'RegistrationController@editGeolocation');
	Route::get('v1/checkemailexistcompany', 'RegistrationController@checkEmailCompany');
	Route::get('v1/checkemailexisteditemail', 'RegistrationController@checkemailexisteditemail');
	Route::get('v1/getlocationcount', 'RegistrationController@getlocationcount');
	Route::get('v1/skipandComplete', 'RegistrationController@skipandComplete');
	Route::get('v1/getLocationInfo', 'RegistrationController@getLocationInfo');
	
	// Route::post('v1/additionalclaimgeopayment', 'RegistrationController@addclaimGeolocationpayment');	

	Route::post('v1/sendNotificationEmail', 'RegistrationController@generateOTP');
	Route::get('v1/getAllSentMessages', 'LoggeduserController@getAllSentMessages');
	Route::get('v1/sendMessage', 'LoggeduserController@sendMessage');
	Route::get('v1/getCompanyVacancyDetails', 'LoggeduserController@getCompanyVacancyDetails');
	Route::get('v1/checkBusinessLoggedIn', 'AuthController@checkBusinessLoggedIn');
	Route::get('v1/checkLoggedIn', 'AuthController@checkLoggedIn');
	Route::get('v1/getinboxmessage2', 'LoggeduserController@getinboxmessage2');
	// Route::get('v1/companyCurrentPlan', 'RegistrationController@companyCurrentPlan');
	Route::get('v1/activate', 'RegistrationController@activate');
	Route::get('v1/getclaimedBusinessData', 'RegistrationController@getClaimBusinessData');
	Route::get('v1/resendActivationLink', 'RegistrationController@resendActivationLink');

	Route::post('v1/forgetpassword', 'RegistrationController@forgetpassword');
	Route::post('v1/resetpassword', 'RegistrationController@resetpassword');
	Route::get('v1/validpasswordtoken', 'RegistrationController@checkValidpasswordhash');
	Route::get('v1/checkYachtLoggedIn', 'AuthController@checkYachtLoggedIn');
	Route::get('v1/checkdummyInfo', 'RegistrationController@checkandchangeEmailAddress');	
	Route::get('v1/changeEmailAddressDiscount', 'RegistrationController@changeEmailAddressDiscount');	
	Route::get('v1/getadvertisementdata', 'HomeController@getadvertisementdata');
	Route::get('v1/checkBoatOwnerLoggedIn', 'AuthController@checkBoatOwnerLoggedIn');
	Route::get('v1/checkProffLoggedIn', 'AuthController@checkProffLoggedIn');\
	Route::get('v1/getAllBlogs', 'HomeController@getBlogs');
	Route::get('v1/getBlogDetail', 'HomeController@getBlogById');
	Route::post('v1/registrationSocial', 'RegistrationController@registrationStage1Social');
	Route::get('v1/checkemailexistcompanyforget', 'RegistrationController@checkEmailCompanyForget');
	Route::post('v1/requestToken', 'RegistrationController@requestToken');
	Route::get('v1/getAllSubcategories', 'HomeController@getAllSubcategories');
	Route::get('v1/getAllcategoryData', 'HomeController@getAllcategoryData');
	Route::get('v1/getAllsubCategoryData', 'HomeController@getAllsubCategoryData');
	Route::get('v1/getAllcategoryDataDemo', 'HomeController@getAllcategoryDataDemo');
	Route::post('v1/requestToken', 'RegistrationController@requestToken');
	Route::post('v1/verify_credentials', 'RegistrationController@verify_credentials');
	Route::post('v1/generateToken', 'RegistrationController@generateToken');
	Route::get('v1/getAllCategory', 'HomeController@getAllCategory');
	Route::get('v1/getallcategories', 'HomeController@getallcategories');
	Route::get('v1/getAllSubcategory', 'HomeController@getAllSubcategory');
	Route::get('v1/getAllservices', 'HomeController@getAllservices');
	Route::get('v1/getAllServiceAndRepair', 'HomeController@getAllServiceAndRepair');
	//~ Route::get('v1/getClientToken', 'RegistrationController@getBraintreeToken');
	//~ Route::get('v1/transaction', 'RegistrationController@braintreeTransaction');
	Route::get('v1/getAllsuggestions', 'HomeController@allSuggestion');	
	Route::get('v1/getCountryCodes', 'RegistrationController@getCountryCodes');
	Route::get('v1/getClientTokenAdmin', 'RegistrationController@getBraintreeTokenAdmin');
	Route::get('v1/getClientTokenUser', 'RegistrationController@getBraintreeTokenUser');
	Route::get('v1/getClientToken', 'RegistrationController@getBraintreeToken');
	Route::get('v1/getClientTokenDiscount', 'RegistrationController@getClientTokenDiscount');
	Route::post('v1/transaction', 'RegistrationController@braintreeTransaction');
	Route::post('v1/transactionLead', 'RegistrationController@braintreeTransactionLead');
	Route::get('v1/getallcountdata', 'HomeController@getallcountdata');
	Route::get('v1/getLatestRatingsHome', 'HomeController@getLatestRatingsHome');
	Route::get('v1/activateEmail', 'RegistrationController@activateEmail');
    Route::get('v1/getAllsuggestionsHome', 'HomeController@allSuggestionHome');
    Route::get('v1/getAllCategoryServices', 'HomeController@getCategoryAndServices');
    Route::post('v1/transactionProfile', 'RegistrationController@braintreeTransactionProfile');
	Route::post('v1/transactionplan', 'RegistrationController@braintreeTransactionPlan');
	Route::post('v1/transactionplanDiscount', 'RegistrationController@braintreeTransactionPlanDiscount');
	Route::get('v1/allSuggestionHomeBiz', 'HomeController@allSuggestionHomeBiz');
	Route::get('v1/allSuggestionBiz', 'HomeController@allSuggestionBiz');
	Route::get('v1/geocontinent', 'RegistrationController@geocontinent');
	Route::get('v1/getBadword','HomeController@getBadword');
	Route::get('v1/sendSubscriptionEndAlert','RegistrationController@sendSubscriptionEndAlert');
	Route::get('v1/sendUnreadMessageAlert','RegistrationController@sendUnreadMessageAlert');
	Route::get('v1/getDummyDataScript','RegistrationController@getDummyDataScript');
	Route::get('v1/getFreePlan','RegistrationController@getFreePlan');
	Route::post('v1/payPerLeadPayment','RegistrationController@payPerLeadPayment');
    Route::get('v1/getLatestWebsiteRatings','HomeController@getLatestWebsiteRatings');
    Route::get('v1/getAllBoatandYacht','RegistrationController@getAllBoatandYacht');
	Route::get('v1/getAllEngines','RegistrationController@getAllEngines');
	Route::get('v1/getLatestWebsiteRatingsLimit','HomeController@getLatestWebsiteRatings');		
	Route::get('v1/getservicesuggestion','HomeController@getservicesuggestion');
	Route::get('v1/searchHomepage', 'HomeController@searchHomepage');
	Route::get('v1/searchBizpage', 'HomeController@searchBizpage');
	Route::get('v1/showBoatYachtResult', 'HomeController@showBoatYachtResult');
	Route::get('v1/showEngineResult', 'HomeController@showEngineResult');
	Route::get('v1/getSeachedCities', 'HomeController@getSeachedCities');
	Route::post('v1/addNewBoatSlipReq', 'RegistrationController@addBoatSlipReq');
	Route::post('v1/webhooksub', 'WebhookController@subcription_webhook');
	Route::get('v1/address', 'HomeController@extractPostalCodeFromAdd');
	Route::get('v1/checkisCompany', 'RegistrationController@checkisCompany');
	Route::post('v1/adduserandServiceRequest', 'RegistrationController@addBoatOwnerServiceRequest');
	Route::post('v1/addFishCharterReq', 'RegistrationController@addFishCharterReq');
    Route::post('v1/saveQuoteandAddUser', 'RegistrationController@saveQuoteRequest');
    Route::post('v1/companypay', 'RegistrationController@companywihtoutpayment');
    Route::post('v1/companypayDiscount', 'RegistrationController@companywithoutpaymentDiscount');
    Route::post('v1/sendleadpayment','RegistrationController@stripeTransactionLead');
    Route::post('v1/verifyOtp','RegistrationController@verifyOtp');
    Route::post('v1/activateEmailOtp','RegistrationController@activateEmailOtp');
    Route::get('v1/allPlans', 'RegistrationController@allUpdatedPlans'); 
    Route::post('v1/getS3Url', 'HomeController@generateS3Url');

    Route::get('v1/phpinfo', 'HomeController@extractPostalCodeFromAdd');