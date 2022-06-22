<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::middleware("auth:api")->post("course/add", "api\studentController@addStudentCourse");

    Route::middleware("auth:api")->get("/payment/{id}", "api\studentController@getPayment");


    

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

});


Route::prefix("/speaker")->group( function (){

    Route::middleware("auth:api")->post("/add", "api\speakerController@addSpeaker"); 
    Route::middleware("auth:api")->put("/{id}", "api\speakerController@updateSpeaker");
    Route::middleware("auth:api")->get("/{id?}", "api\speakerController@getSpeaker");

});

Route::prefix("/payment")->group( function (){
    Route::middleware("auth:api")->get("/", "api\paymentController@index");
    Route::post("/create", "api\paymentController@createPayment"); 
    Route::post("/complete", "api\paymentController@completePayment"); 


    
    Route::middleware("auth:api")->get("/{id}", "api\paymentController@getPayment");
    // Route::middleware("auth:api")->post("/refund", "api\paymentController@refund");
    Route::middleware("auth:api")->put("/status/{id}", "api\paymentController@updatePayment");
});

Route::prefix("/utility")->group( function (){
    Route::middleware("api_token")->get("/missing/student_modules", "api\utilityController@studentModules");
    
});