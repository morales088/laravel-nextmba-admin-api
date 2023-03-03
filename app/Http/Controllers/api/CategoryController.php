<?php

namespace App\Http\Controllers\api;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CategoryController extends Controller
{
    public function __construct() {
        $this->middleware("auth:api");
    }

    public function getCategories() {

        $categories = Category::where('status', 1)
            ->orderBy('id', 'DESC')
            ->get();

        return response()->json(['categories' => $categories], 200);
    }

    public function addCategory(Request $request) {
        
        $request->validate([
            'name' => 'required|string|unique:categories',
            'color' => 'string|nullable|sometimes'
        ]);

        $category = Category::create([
            'name' => $request->name,
            'color' => $request->color
        ]);

        return response()->json(['category' => $category], 201);
    }

    public function updateCategory(Request $request, $id) {

        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'string|unique:categories,name,' .$category->id,
            'color' => 'string|nullable|sometimes',
            'status' => 'in:delete,active'
        ]);

        if ($request->status == "delete") {
           $status = 0;   
        } else if ($request->status == "active") {
           $status = 1;   
        }

        $category->update([
            'name' => $request->name,
            'color' => $request->color,
            'status' => $status ?? $category->status
        ]);

        return response()->json(['category' => $category], 200);
    }
}
