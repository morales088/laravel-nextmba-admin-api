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
    
    public function getProducts(Request $request){
        $products = Product::where('status', '<>', 0)
            ->with('product_items.course')
            ->get();

        $products->transform(function ($product) {
            $totalAmount = 0; // reset total amount for each product
            $product->product_items->transform(function ($productItem) use (&$totalAmount) {
                $productItem->product_total = $productItem->course->price * $productItem->quantity;
                $totalAmount += $productItem->product_total;
                return $productItem;
            });
    
            $product->total_amount_of_items = $totalAmount;
    
            return $product;
        });

        return response(["product_courses" => $products], 200);
    }
    
    public function addProduct(Request $request){

        $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|min:4|unique:products',
            'price' => 'required',
            'pro_access' => 'in:0,1',
            'library_access' => 'in:0,1',
            'status' => 'in:1,2',
        ]);

        $product_courses = DB::transaction(function () use ($request) {
            
            $product = new Product;
            $product->name = $request->name;
            $product->code = $request->code;
            $product->price = $request->price;
            $product->pro_access = $request->pro_access ?? 0;
            $product->library_access = $request->library_access ?? 0;
            $product->status = $request->status ?? 1;
            $product->save();            

            return $product;
        });

        return response(["product_courses" => $product_courses], 200);

    }

    public function updateProduct(Request $request, $id){

        $request->query->add(['product_id' => $id]);
        
        $request->validate([
            'product_id' => 'required|string|exists:products,id',
            'name' => 'string',
            'code' => 'string|min:4|unique:products',
            'price' => 'string',
            'pro_access' => 'in:0,1',
            'library_access' => 'in:0,1',
            'status' => 'in:0,1,2',
        ]);

        $product = Product::find($id);
        $product->update($request->only('name', 'code', 'price', 'pro_access', 'library_access' ,'status') +
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

        $check = ProductItem::WHERE('product_id', $request->product_id)
                            ->WHERE('course_id', $request->course_id)
                            ->WHERE('status', 1)
                            ->get();

        if(!$check->isEmpty()) return response(["message" => "Duplicate data."], 409);
        
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
