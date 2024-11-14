<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;

use App\Models\Admin\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Storage;


class ProductController extends Controller
{
    public function index()
    {
        $result['data']=Product::all();
        return view('admin/product',$result);
    }

    
    public function manage_product(Request $request,$id='')
    {
        if($id>0){
            $arr=Product::where(['id'=>$id])->get(); 

            $result['category_id']=$arr['0']->category_id;
            $result['name']=$arr['0']->name;
            $result['image']=$arr['0']->image;
            $result['slug']=$arr['0']->slug;
            $result['brand']=$arr['0']->brand;
            $result['model']=$arr['0']->model;
            $result['short_desc']=$arr['0']->short_desc;
            $result['desc']=$arr['0']->desc;
            $result['keywords']=$arr['0']->keywords;
            $result['technical_specification']=$arr['0']->technical_specification;
            $result['uses']=$arr['0']->uses;
            $result['warranty']=$arr['0']->warranty;
            $result['lead_time']=$arr['0']->lead_time;
            $result['tax_id']=$arr['0']->tax_id;
            $result['is_promo']=$arr['0']->is_promo;
            $result['is_featured']=$arr['0']->is_featured;
            $result['is_discounted']=$arr['0']->is_discounted;
            $result['is_tranding']=$arr['0']->is_tranding;
            $result['status']=$arr['0']->status;
            $result['id']=$arr['0']->id;

            $result['productAttrArr']=DB::table('products_attr')->where(['products_id'=>$id])->get();

            $productImagesArr=DB::table('product_images')->where(['products_id'=>$id])->get();
            
            if(!isset($productImagesArr[0])){
                $result['productImagesArr']['0']['id']='';
                $result['productImagesArr']['0']['images']='';
            }else{
                $result['productImagesArr']=$productImagesArr;
            }
            //$result['productImagesArr']
        }else{
            $result['category_id']='';
            $result['name']='';
            $result['slug']='';
            $result['image']='';
            $result['brand']='';
            $result['model']='';
            $result['short_desc']='';
            $result['desc']='';
            $result['keywords']='';
            $result['technical_specification']='';
            $result['uses']='';
            $result['warranty']='';
            $result['lead_time']='';
            $result['tax_id']='';
            $result['is_promo']='';
            $result['is_featured']='';
            $result['is_discounted']='';
            $result['is_tranding']='';
            $result['status']='';
            $result['id']=0;

            $result['productAttrArr'][0]['id']='';
            $result['productAttrArr'][0]['products_id']='';
            $result['productAttrArr'][0]['sku']='';
            $result['productAttrArr'][0]['attr_image']='';
            $result['productAttrArr'][0]['mrp']='';
            $result['productAttrArr'][0]['price']='';
            $result['productAttrArr'][0]['qty']='';
            $result['productAttrArr'][0]['size_id']='';
            $result['productAttrArr'][0]['color_id']='';

            $result['productImagesArr']['0']['id']='';
            $result['productImagesArr']['0']['images']='';
            /*echo '<pre>';
            print_r( $result['productAttrArr']);
            die();*/
        }
        /*echo '<pre>';
        print_r( $result);
        die();*/
        $result['category']=DB::table('categories')->where(['status'=>1])->get();

        $result['sizes']=DB::table('sizes')->where(['status'=>1])->get();

        $result['colors']=DB::table('colors')->where(['status'=>1])->get();

        $result['brands']=DB::table('brands')->where(['status'=>1])->get();

        $result['taxs']=DB::table('taxs')->where(['status'=>1])->get();
        return view('admin/manage_product',$result);
    }

    public function manage_product_process(Request $request)
{
    // Validation
    if($request->post('id') > 0){
        $image_validation = "mimes:jpeg,jpg,png";
    } else {
        $image_validation = "required|mimes:jpeg,jpg,png";
    }

    $request->validate([
        'name' => 'required',
        'image' => $image_validation,
        'slug' => 'required|unique:products,slug,' . $request->post('id'), 
        'attr_image.*' => 'mimes:jpg,jpeg,png',
        'images.*' => 'mimes:jpg,jpeg,png'   
    ]);

    // Arrays for attributes and other product details
    $paidArr = $request->post('paid');
    $skuArr = $request->post('sku');
    $mrpArr = $request->post('mrp');
    $priceArr = $request->post('price');
    $qtyArr = $request->post('qty');
    $size_idArr = $request->post('size_id');
    $color_idArr = $request->post('color_id');
    
    // Check for duplicate SKUs
    foreach ($skuArr as $key => $val) {
        $check = DB::table('products_attr')
                  ->where('sku', '=', $skuArr[$key])
                  ->where('id', '!=', $paidArr[$key])
                  ->get();

        if (isset($check[0])) {
            $request->session()->flash('sku_error', $skuArr[$key] . ' SKU already used');
            return redirect(request()->headers->get('referer'));
        }
    }

    // Determine if we are updating an existing product or inserting a new one
    if ($request->post('id') > 0) {
        $model = Product::find($request->post('id'));
        $msg = "Product updated";
    } else {
        $model = new Product();
        $msg = "Product inserted";
    }

    // Handle product image upload
    if ($request->hasFile('image')) {
        // Delete old image if updating
        if ($request->post('id') > 0) {
            $arrImage = DB::table('products')->where(['id' => $request->post('id')])->get();
            if (Storage::exists('/public/media/' . $arrImage[0]->image)) {
                Storage::delete('/public/media/' . $arrImage[0]->image);
            }
        }

        // Handle the file upload and move it
        $image = $request->file('image');
        $image_name = time() . '.' . $image->getClientOriginalExtension();
        $destinationPath = public_path('storage/media');  // Define the destination path
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);  // Create directory if it doesn't exist
        }
        $image->move($destinationPath, $image_name);  // Move the file to the destination
        $model->image = $image_name;
    }

    // Fill product fields from the form data
    $model->category_id = $request->post('category_id');
    $model->name = $request->post('name');
    $model->slug = $request->post('slug');
    $model->brand = $request->post('brand');
    $model->model = $request->post('model');
    $model->short_desc = $request->post('short_desc');
    $model->desc = $request->post('desc');
    $model->keywords = $request->post('keywords');
    $model->technical_specification = $request->post('technical_specification');
    $model->uses = $request->post('uses');
    $model->warranty = $request->post('warranty');
    $model->lead_time = $request->post('lead_time');
    $model->tax_id = $request->post('tax_id');
    $model->is_promo = $request->post('is_promo');
    $model->is_featured = $request->post('is_featured');
    $model->is_discounted = $request->post('is_discounted');
    $model->is_tranding = $request->post('is_tranding');
    $model->status = 1;
    $model->save();
    
    $pid = $model->id;  // Save product ID for later use in attributes and images

    /* Product Attributes */
    foreach ($skuArr as $key => $val) {
        $productAttrArr = [
            'products_id' => $pid,
            'sku' => $skuArr[$key],
            'mrp' => (int)$mrpArr[$key],
            'price' => (int)$priceArr[$key],
            'qty' => (int)$qtyArr[$key],
            'size_id' => $size_idArr[$key] ?? 0,
            'color_id' => $color_idArr[$key] ?? 0
        ];

        // Handle attribute image upload
        if ($request->hasFile("attr_image.$key")) {
            if ($paidArr[$key] != '') {
                // Delete old attribute image if updating
                $arrImage = DB::table('products_attr')->where(['id' => $paidArr[$key]])->get();
                if (Storage::exists('/public/media/' . $arrImage[0]->attr_image)) {
                    Storage::delete('/public/media/' . $arrImage[0]->attr_image);
                }
            }

            // Handle the file upload for attribute image
            $attr_image = $request->file("attr_image.$key");
            $attr_image_name = rand(111111111, 999999999) . '.' . $attr_image->getClientOriginalExtension();
            $attr_image->move(public_path('storage/media'), $attr_image_name);
            $productAttrArr['attr_image'] = $attr_image_name;
        }

        if ($paidArr[$key] != '') {
            DB::table('products_attr')->where(['id' => $paidArr[$key]])->update($productAttrArr);
        } else {
            DB::table('products_attr')->insert($productAttrArr);
        }
    }

    /* Product Images */
    $piidArr = $request->post('piid');
    foreach ($piidArr as $key => $val) {
        $productImageArr = ['products_id' => $pid];

        if ($request->hasFile("images.$key")) {
            if ($piidArr[$key] != '') {
                // Delete old product image if updating
                $arrImage = DB::table('product_images')->where(['id' => $piidArr[$key]])->get();
                if (Storage::exists('/public/media/' . $arrImage[0]->images)) {
                    Storage::delete('/public/media/' . $arrImage[0]->images);
                }
            }

            // Handle the file upload for product image
            $images = $request->file("images.$key");
            $image_name = rand(111111111, 999999999) . '.' . $images->getClientOriginalExtension();
            $images->move(public_path('storage/media'), $image_name);
            $productImageArr['images'] = $image_name;

            if ($piidArr[$key] != '') {
                DB::table('product_images')->where(['id' => $piidArr[$key]])->update($productImageArr);
            } else {
                DB::table('product_images')->insert($productImageArr);
            }
        }
    }

    // Success message and redirection
    $request->session()->flash('message', $msg);
    return redirect('admin/product');
}

    public function delete(Request $request,$id){
        $model=Product::find($id);
        $model->delete();
        $request->session()->flash('message','Product deleted');
        return redirect('admin/product');
    }

    public function product_attr_delete(Request $request,$paid,$pid){
        $arrImage=DB::table('products_attr')->where(['id'=>$paid])->get();
        if(Storage::exists('/public/media/'.$arrImage[0]->attr_image)){
            Storage::delete('/public/media/'.$arrImage[0]->attr_image);
        }
        DB::table('products_attr')->where(['id'=>$paid])->delete();
        return redirect('admin/product/manage_product/'.$pid);
    }

    public function product_images_delete(Request $request,$paid,$pid){
        $arrImage=DB::table('product_images')->where(['id'=>$paid])->get();
        if(Storage::exists('/public/media/'.$arrImage[0]->images)){
            Storage::delete('/public/media/'.$arrImage[0]->images);
        }
        DB::table('product_images')->where(['id'=>$paid])->delete();
        return redirect('admin/product/manage_product/'.$pid);
    }
    
    public function status(Request $request,$status,$id){
        $model=Product::find($id);
        $model->status=$status;
        $model->save();
        $request->session()->flash('message','Product status updated');
        return redirect('admin/product');
    }
}
