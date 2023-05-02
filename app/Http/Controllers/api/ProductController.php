<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ProductItem;
use App\Models\Product;
use Validator;
use DB;

class ProductController extends Controller
{
    public function __construct() {
        $this->middleware("auth:api");
    }

    public function getProducts(Request $request){
        $product = Product::where('status', '<>', 0)->with('product_items')->get();

        return response(["product_courses" => $product], 200);
    }
    
    public function addProduct(Request $request){

        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|min:4|unique:products',
            // 'product_items' => 'required|string',
            'pro_access' => 'in:0,1',
            'library_access' => 'in:0,1',
            'status' => 'in:1,2',
        ]);

        // dd($request->all());

        // $product_items = json_decode($request->product_items);

        // if (!json_last_error() === JSON_ERROR_NONE) {
        //     return response(["message" => "wrong format of order_reviews"], 400);
        // }

        $product_courses = DB::transaction(function () use ($request) {
            
            $product = new Product;
            $product->name = $request->name;
            $product->code = $request->code;
            $product->pro_access = $request->pro_access ?? 0;
            $product->library_access = $request->library_access ?? 0;
            $product->status = $request->status ?? 1;
            $product->save();
            
            // foreach ($product_items as $key => $value) {
            //     // dd($value);
            //     $product_item = new ProductItem;
            //     $product_item->product_id = $product->id;
            //     $product_item->course_id = $value->course_id;
            //     $product_item->quantity = $value->quantity ?? 1;
            //     $product_item->status = 1;
            //     $product_item->save();
            // }

            return $product;
        });

        // $product = Product::where('id', $product_courses->id)->with('product_items')->get();

        return response(["product_courses" => $product_courses], 200);

    }

    public function updateProduct(Request $request, $id){

        $request->query->add(['product_id' => $id]);
        

        $request->validate([
            'product_id' => 'required|string|exists:products,id',
            'name' => 'string',
            'code' => 'string|min:4|unique:products',
            // 'product_items' => 'string',
            'pro_access' => 'in:0,1',
            'library_access' => 'in:0,1',
            'status' => 'in:0,1,2',
        ]);

        // if(isset($request->product_items)){
        //     $product_items = json_decode($request->product_items);

        //     if (!json_last_error() === JSON_ERROR_NONE) {
        //         return response(["message" => "wrong format of order_reviews"], 400);
        //     }

        //     $product_courses = DB::transaction(function () use ($request, $product_items) {
        //         // dd($product_items);
        //         foreach ($product_items as $key => $value) {
        //             $product_item = ProductItem::find($value->id);
        //             // $product_items->product_id = $product->id;
        //             $product_item->course_id = $value->course_id;
        //             $product_item->quantity = $value->quantity;
        //             $product_item->status = $value->status;
        //             $product_item->save();
        //         }

        //         return $product_items;
        //     });
        // }

        $product = Product::find($id);
        $product->update($request->only('name', 'code', 'pro_access', 'library_access' ,'status') +
                    [ 'updated_at' => now()]
                    );
        
        return response(["product_courses" => $product], 200);
    }

    public function addItem(Request $request){

        $request->validate([
            'product_id' => 'required|numeric|exists:products,id',
            'course_id' => 'required|numeric|exists:courses,id',
            'quantity' => 'required|numeric|min:1',
        ]);
        
        $product_item = new ProductItem;
        $product_item->product_id = $request->product_id;
        $product_item->course_id = $request->course_id;
        $product_item->quantity = $request->quantity ?? 1;
        $product_item->status = 1;
        $product_item->save();

        return response(["product_item" => $product_item], 200);
    }

    public function updateItem(Request $request, $id){

        $request->query->add(['id' => $id]);

        $request->validate([
            'id' => 'required|numeric|exists:products',
            'quantity' => 'numeric|min:1',
            'status' => 'in:0,1',
        ]);
        
        $product_item = ProductItem::find($id);
        $product_item->update($request->only('quantity', 'status') +
                    [ 'updated_at' => now()]
                    );

        return response(["product_item" => $product_item], 200);
    }
}
