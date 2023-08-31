<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\ProductController;
use App\Http\Controllers\api\CategoryController;
use App\Http\Controllers\api\AffiliateController;
use App\Http\Controllers\api\BusinessPartnerController;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::prefix("/user")->group( function (){

    Route::post("/login", "api\loginController@personalAccessLogin");
    Route::middleware("auth:api")->get("/admin/{id?}", "api\loginController@admin");
    // Route::middleware("auth:api")->get("/all", "api\studentController@index");
    Route::middleware("auth:api")->post("/register", "api\loginController@register");
    Route::middleware("auth:api")->put("/{id}", "api\loginController@updateAdmin");
});


Route::prefix("/student")->group( function (){

    Route::middleware("auth:api")->get("/all", "api\studentController@index");
    Route::middleware("auth:api")->get("/generate-csv", "api\studentController@generateCSV");
    Route::middleware("auth:api")->put("/{id}", "api\studentController@updateStudent");
    Route::middleware("auth:api")->get("/{id}", "api\studentController@studentById");    
    Route::middleware("auth:api")->get("/courses/{id}", "api\studentController@coursesByStudent"); //get all courses enrolled to student id
    Route::middleware("auth:api")->get("/modules/{courseId}/{id}", "api\studentController@modulePerCourses"); //get all module per courses to student id
    Route::middleware("auth:api")->put("/modules/update", "api\studentController@updateStudentModule");

    Route::middleware("auth:api")->put("/account/{id}", "api\studentController@activateDeactivate");
    Route::middleware("auth:api")->put("/change_password/{id}", "api\studentController@changePassword");
    Route::middleware("auth:api")->post("/email_password/{id}", "api\studentController@emailPassword");

    Route::middleware("auth:api")->put("/course/extend", "api\studentController@extendCourse");

    Route::middleware("auth:api")->post("/add", "api\studentController@addStudent");
    Route::middleware("api_token")->post("/payment/add", "api\studentController@addStudent"); // add student via api token
    Route::middleware("auth:api")->post("course/add", "api\studentController@addStudentCourse");
    Route::middleware("auth:api")->post("course/remove", "api\studentController@removeStudentCourse");

    Route::middleware("auth:api")->get("/payment/{id}", "api\studentController@getPayment");

    Route::middleware("auth:api")->post("/gift/{student_id}", "api\giftController@paymentCourses");
    Route::middleware("auth:api")->post("/transfer/gift/", "api\giftController@sendGift");

    Route::middleware("auth:api")->put("/payment/course/{item_id}", "api\giftController@updatePaymentItem");




});

// Route::prefix("/payment")->group( function (){
//     Route::middleware("auth:api")->get("/", "api\paymentController@index");
// });

Route::prefix("/courses")->group( function (){
    
    // get topic
    Route::middleware("auth:api")->get("/topic/{moduleId}/{id?}", "api\courseController@getTopic"); 
    // add topic
    Route::middleware("auth:api")->post("/topic/add", "api\courseController@addTopic"); 
    // update topic
    Route::middleware("auth:api")->put("/topic/{id}", "api\courseController@updateTopic"); 


    Route::middleware("auth:api")->get("/", "api\courseController@index"); 
    Route::middleware("auth:api")->post("/add", "api\courseController@addCourse"); 
    Route::middleware("auth:api")->put("/update", "api\courseController@updateCourse"); 
        
    // Route::middleware("auth:api")->put("/module/live", "api\courseController@liveModule"); // change live status to update module
    Route::middleware("auth:api")->post("/module/add", "api\courseController@addModule"); 
    Route::middleware("auth:api")->put("/module/{id}", "api\courseController@updateModule"); 
    Route::middleware("auth:api")->get("/module/{id}", "api\courseController@getModule"); 

    Route::middleware("auth:api")->get("/module/files/{module_id?}", "api\courseController@getFiles"); 
    Route::middleware("auth:api")->post("/module/files", "api\courseController@addFiles"); 
    Route::middleware("auth:api")->put("/module/files/{id}", "api\courseController@updateFiles"); 
    
    Route::middleware("auth:api")->put("/module/status/{id}", "api\courseController@updateModuleStatus"); 
    
    Route::middleware("auth:api")->get("/modules/{id}", "api\courseController@getModules"); 

    
    Route::middleware("auth:api")->get("/zoom/{module_id}/{id?}", "api\courseController@getZoom");
    Route::middleware("auth:api")->post("/zoom/add", "api\courseController@addZoom"); 
    Route::middleware("auth:api")->put("/zoom/{id}", "api\courseController@updateZoom");

    Route::middleware("auth:api")->get("/library/files/{library_id?}", "api\libraryController@getFiles"); 
    Route::middleware("auth:api")->post("/library/files", "api\libraryController@addFiles"); 
    Route::middleware("auth:api")->put("/library/files/{id}", "api\libraryController@updateFiles");

    Route::middleware("auth:api")->get("/module/streams/{module_id}", "api\courseController@getModuleStream");
    Route::middleware("auth:api")->post("/module/stream", "api\courseController@createModuleSteam");
    Route::middleware("auth:api")->put("/module/stream/{id?}", "api\courseController@updateModuleSteam");

    Route::middleware("auth:api")->get("/module/replays/{module_id}", "api\courseController@getReplayVideo");
    Route::middleware("auth:api")->post("/module/replay", "api\courseController@createReplayVideo");
    Route::middleware("auth:api")->put("/module/replay/{id?}", "api\courseController@updateReplayVideo");
    Route::middleware("auth:api")->delete("/module/replay/{id?}", "api\courseController@deleteReplayVideo");

    Route::middleware("auth:api")->get("/module/language/{module_id}", "api\courseController@getModuleLanguage");
    Route::middleware("auth:api")->post("/module/language", "api\courseController@createModuleLanguage");
    Route::middleware("auth:api")->put("/module/language/{id}", "api\courseController@updateModuleLanguage");


});


Route::prefix("/speaker")->group( function (){

    Route::middleware("auth:api")->post("/add", "api\speakerController@addSpeaker"); 
    Route::middleware("auth:api")->put("/{id}", "api\speakerController@updateSpeaker");
    Route::middleware("auth:api")->get("/{id?}", "api\speakerController@getSpeaker");

});

Route::prefix("/payment")->group( function (){
    Route::middleware("api_token")->post("/create", "api\paymentController@payment"); 
    // Route::post("/create", "api\paymentController@createPayment"); 
    Route::post("/complete", "api\paymentController@completePayment"); 


    Route::middleware("auth:api")->get("/", "api\paymentController@index");
    Route::middleware("auth:api")->get("/{id}", "api\paymentController@getPayment");
    // Route::middleware("auth:api")->post("/refund", "api\paymentController@refund");
    Route::middleware("auth:api")->put("/status/{id}", "api\paymentController@updatePayment");
    Route::middleware("auth:api")->post("/manual/create", "api\paymentController@payment");
});

Route::prefix("/utility")->group( function (){
    Route::middleware("api_token")->get("/missing/student_modules", "api\utilityController@studentModules");
    Route::middleware("auth:api")->post("/upload", "api\utilityController@uploadImage"); 
    Route::get("/test", "api\utilityController@test"); 
    
});

Route::prefix("/stream")->group( function (){
    Route::middleware("auth:api")->get("/verify", "api\streamController@verify"); 
    Route::middleware("auth:api")->post("/live", "api\streamController@live"); 
    Route::middleware("api_token")->post("/watch", "api\streamController@watch"); 
    Route::middleware("auth:api")->post("/delete", "api\streamController@delete"); 
    Route::middleware("auth:api")->post("/replay", "api\streamController@watchReplay"); 
    
});

Route::prefix("/library")->group( function (){
    Route::middleware("auth:api")->get("/", "api\libraryController@index");    
    Route::middleware("auth:api")->get("/{id}", "api\libraryController@perlLibrary");    
    Route::middleware("auth:api")->post("/videos/{id?}", "api\libraryController@library");    
});

Route::prefix("/affiliate")->middleware("auth:api")
    ->controller(AffiliateController::class)->group(function () {
        Route::get("/applications", "getApplications");
        Route::get("/withdrawals", "getWithdrawals");
        Route::put("/update/{id}", "updateAffiliate");
        Route::put("/update-withdraw/{id}", "updateWithdraw");
});

Route::prefix("/categories")->middleware("auth:api")
    ->controller(CategoryController::class)->group(function () {
        Route::get("/", "getCategories");
        Route::post("/add", "addCategory");
        Route::put("/update/{id}", "updateCategory");
});

Route::prefix("/product")->middleware("auth:api")
    ->controller(ProductController::class)->group(function () {
        Route::get("/", "getProducts");
        Route::post("/", "addProduct");
        Route::put("/{id}", "updateProduct");
        
        Route::post("/item", "addItem");
        Route::put("/item/{id}", "updateItem");

        Route::get("partner/", "getProducts")
            ->middleware("api_token")
            ->withOutMiddleware("auth:api");
    });

Route::prefix("/partner")->middleware("auth:api")
    ->controller(BusinessPartnerController::class)->group(function () {
        Route::get("/", "index");
        Route::post("/create", "createPartnerAccount");
});