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
    Route::get("/admin", "api\loginController@admin");
    // Route::middleware("auth:api")->get("/all", "api\studentController@index");
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

    Route::middleware("auth:api")->put("/course/extend", "api\studentController@extendCourse");

    Route::middleware("auth:api")->post("/add", "api\studentController@addStudent");
    Route::middleware("auth:api")->post("course/add", "api\studentController@addStudentCourse");
    

});


Route::prefix("/courses")->group( function (){

    Route::middleware("auth:api")->get("/", "api\courseController@index");
    Route::middleware("auth:api")->post("/module/add", "api\courseController@addModule");
    Route::middleware("auth:api")->put("/module/{id}", "api\courseController@updateModule");

    Route::middleware("auth:api")->post("/speaker/add", "api\courseController@addSpeaker");
    Route::middleware("auth:api")->put("/speaker/{id}", "api\courseController@updateSpeaker");
    
    Route::middleware("auth:api")->get("/{id}", "api\courseController@getModules");


});