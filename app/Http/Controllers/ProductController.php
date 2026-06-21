<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductOrder;
use App\Services\ModuleGateService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmation;
use App\Models\PaymentGateway;
use App\Models\SystemSetting;
use App\Models\Invoice;
use Carbon\Carbon;

class ProductController extends Controller
{
    public function __construct()
    {

        $pageTitle = 'Store';
        View::share('pageTitle', $pageTitle);
    }



    

    // Function to get the profile data
    private function getProfileData()
    {
        $id = Auth::user()->id;
        return User::find($id);
    }


      // GET ALL PRODUCT CATEGORIES
      public function allCategories(){
          $this->requireCampaignStoreModule();

          $profileData = $this->getProfileData();
          $categories = ProductCategory::all();
          $authors = User::wherein('id', $categories->pluck('user_id'))->get();
          return view('backend.'.$profileData->access_level.'.product.categories', compact('profileData', 'categories', 'authors'));
      }


      public function addCategory(){
          $this->requireCampaignStoreModule();

          $profileData = $this->getProfileData();
          return view('backend.'.$profileData->access_level.'.product.add-category', compact('profileData'));
      }


      public function storeCategory(Request $request){
         $this->requireCampaignStoreModule();

         // Validate the input data
            $request->validate([
                'name' => 'required|unique:product_categories|max:255',
                'slug' => 'string|unique:product_categories|regex:/^[a-z0-9-]+$/|max:255',
                'description' => 'nullable',
            ]);

            
          $profileData = $this->getProfileData();

            // If validation passes, store the category
            $category = new ProductCategory();
            $category->name = $request->name;
            $category->slug = Str::slug($request->name);
            $category->description = $request->description;
            $category->save();

          $notification = array(
              'message' => 'Category added successfully',
              'alert-type' => 'success'
          );

          return redirect()->route($profileData->access_level.'.product.categories')->with($notification);
      }

      public function editCategory($id){
          $this->requireCampaignStoreModule();

          $profileData = $this->getProfileData();
          $category = ProductCategory::find($id);
          return view('backend.'.$profileData->access_level.'.product.edit-category', compact('profileData', 'category'));
      }

      public function updateCategory(Request $request, $id){
          $this->requireCampaignStoreModule();

          $profileData = $this->getProfileData();
          $category = ProductCategory::find($id);
          $category->update([
              'name' => $request->name,
              'description' => $request->description,
              'slug' => $request->slug,
            ]);

          $notification = array(
              'message' => 'Category updated successfully',
              'alert-type' => 'success'
          );

          return redirect()->route($profileData->access_level.'.product.categories')->with($notification);
      }


      public function deleteCategory($id){
          $this->requireCampaignStoreModule();

          $profileData = $this->getProfileData();
          $category = ProductCategory::find($id);
          $category->delete();

          $notification = array(
              'message' => 'Category deleted successfully',
              'alert-type' => 'success'
          );

          return redirect()->route($profileData->access_level.'.product.categories')->with($notification);
      }


/* END OF PRODUCT CATEGORY MANAGEMENT FUNCTIONS */



    /* START PRODUCT MANAGEMENT FUNCTIONS */
    public function allProducts(){
        $this->requireCampaignStoreModule();

        $profileData = $this->getProfileData();
        $products = Product::all();        
        $systemSettings = SystemSetting::all();
        return view('backend.'.$profileData->access_level.'.product.products', compact('profileData', 'products', 'systemSettings'));
    }



    public function addProduct(){
        $this->requireCampaignStoreModule();

        $profileData = $this->getProfileData();
        $categories  = ProductCategory::all();
        $systemSettings = SystemSetting::all();
        return view('backend.'.$profileData->access_level.'.product.add_product', compact('profileData', 'categories','systemSettings'));
    }


    public function storeProduct(Request $request){
        $this->requireCampaignStoreModule();

        $profileData = $this->getProfileData();

        $filename = null; // Initialize the variable
        // $zipfilename = null; // Initialize the variable

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/product_images/'), $filename);
        }

       

        Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'image' => $filename, // Use the variable $filename
            'category_id' => $request->category,
            'slug' => Str::slug($request->name),
            'price' => $request->price,
            //'zipfile' => $zipfilename, // Use the variable $zipfilename
        ]);

        $notification = array(
            'message' => 'Product added successfully',
            'alert-type' => 'success'
        );
        return redirect()->route($profileData->access_level.'.products')->with($notification);
    }


    public function editProduct($id){
        $this->requireCampaignStoreModule();

        $profileData = $this->getProfileData();
        $product = Product::find($id);
        $category = $product->category_id; // Assuming you have a relationship defined

        $categories  = ProductCategory::all();

        return view('backend.'.$profileData->access_level.'.product.edit-product', compact('profileData', 'product', 'category', 'categories'));
    }



    public function updateProduct(Request $request, $id){
        $this->requireCampaignStoreModule();

        $profileData = $this->getProfileData();
        // Step 1: Validation
        $request->validate([
            'name' => 'required',
            'description' => 'required',
            'price' => 'numeric',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Example validation for image uploads
            // 'zipfile' => 'file|mimes:zip,rar', // Example validation for zip file uploads
        ]);

        // Step 2: Retrieve the product
        $product = Product::find($id);

        // Step 3: Handle File Uploads for Image (if a new image is provided)
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = (string) Str::uuid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/product_images/'), $filename);

            // Remove the old image file if it exists
            if (!empty($product->image)) {
                // Replace 'product_image.png' with the default image file name
                if ($product->image !== 'product_image.png') {
                    @unlink(public_path('uploads/product_images/' . $product->image));
                }
            }

            // Update the product's image field with the new file name
            $product->image = $filename;
        }

       

        // Step 5: Update the product's data
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->slug = Str::slug($request->name);
        $product->discount = $request->discount;
        $product->category_id = $request->category; 

        // You may also want to update other fields as needed

        $product->save(); // Save the changes

        // Step 6: Redirect with a success message
        $notification = [
            'message' => 'Product updated successfully',
            'alert-type' => 'success',
        ];

        return redirect()->route($profileData->access_level.'.products')->with($notification);
    }


     public function deleteProduct($id){
         $this->requireCampaignStoreModule();

         $profileData = $this->getProfileData();
         $product = Product::find($id);
         $product->delete();

         $notification = array(
            'message' => 'Product deleted successfully',
            'alert-type' => 'success'
        );

        return redirect()->route($profileData->access_level.'.products')->with($notification);
     }







        //Frontend Product Management Routes
      public function storeFront(){
            $this->requireCampaignStoreModule();

            $pageTitle = 'Expert Advisors, Indicators & Scripts';
            $products = Product::where('category_id', 1)->get();
            $Categories = ProductCategory::withCount('products')->get();
             return view('frontend.shop', compact('Categories', 'products','pageTitle'));
         }




         public function productDetails($id){
            $this->requireCampaignStoreModule();

            $pageTitle = 'Product Details';
             $hashedId = decrypt($id);
             $product = Product::find($hashedId);
             return view('frontend.product', compact('product'));

         }


         public function addToCart($id){
            $this->requireCampaignStoreModule();

            $profileData = $this->getProfileData();
            $pageTitle = 'Cart';
            $memberId = $id;
             //switch membership
             if($memberId == 'membership-1'){
                $product = Product::where('category_id', 3)->where('slug', 'membership-1')->first();
             }elseif($memberId == 'membership-2'){
                $product = Product::where('category_id', 3)->where('slug', 'membership-2')->first();
             }elseif($memberId == 'membership-3'){
                $product = Product::where('category_id', 3)->where('slug', 'membership-3')->first();
             }else{
                $hashedId = decrypt($id);
                $product  = Product::find($hashedId);
                $memberId = $product->slug;
             }
            return view('clientarea.cart', compact('product', 'profileData', 'pageTitle', 'memberId'));
        }

         public function storeCart(Request $request){
            $this->requireCampaignStoreModule();

            $profileData = $this->getProfileData();
            $product_id     = $request->product_id;
            $product_category_id = Product::find($product_id)->category_id;
            $payment_method = $request->payment_method;
            $user_id        = $request->user_id;

            // store vps info
            if($product_category_id == 2){
            $start_date = $request->start_date;
            $billing_cycle = $request->billing_cycle;
            $expiry_date = $request->expiry_date;
            $payment_amount = $request->payment_amount;
            }else{
            $payment_amount = $request->payment_amount;
            }

            // store membrship info
            if($product_category_id == 3){
            $start_date = $request->start_date;
            $expiry_date = $request->expiry_date;
            $billing_cycle = $request->billing_cycle;
            $payment_amount = $request->payment_amount;
                if($billing_cycle == 1){
                $membership_plan = 'monthly';
                }elseif($billing_cycle == 12){
                $membership_plan = 'yearly';
                }else{
                $billing_cycle = 20;
                $membership_plan = 'lifetime';
                }


                // Get the current date
                $currentDate = Carbon::today()->toDateString();
              //check for active membership
                $activeMembership = ProductOrder::where('user_id', $profileData->id)
                //->where('product_id', $product_id)
                ->where('product_category_id', 3)
                ->where('payment_status', 1)
                ->where('expiry_date', '>', $currentDate) // Ensure expiry date is after today
                ->latest()
                ->first();


                if ($activeMembership != null) {
                    // Handle cases based on the active membership plan
                    if ($activeMembership->membership_plan == 'lifetime') {
                        // User has lifetime membership, cannot order new memberships
                        $notification = [
                            'message' => 'You have a Lifetime Membership. You cannot change or renew it.',
                            'alert-type' => 'error'
                        ];
                        return redirect()->route('user.dashboard')->with($notification);
                    } elseif ($activeMembership->membership_plan == 'yearly' && $membership_plan != 'lifetime') {
                        // Yearly membership can only upgrade to lifetime
                        $notification = [
                            'message' => 'You already have a Yearly Membership. You can only upgrade to a Lifetime Membership.',
                            'alert-type' => 'error'
                        ];
                        return redirect()->route('user.dashboard')->with($notification);
                    } elseif ($activeMembership->membership_plan == 'monthly' && $membership_plan == 'monthly') {
                        // Monthly membership can only renew or upgrade
                        $notification = [
                            'message' => 'You already have a Monthly Membership. You can renew after it expires or upgrade to Yearly or Lifetime.',
                            'alert-type' => 'error'
                        ];
                        return redirect()->route('user.dashboard')->with($notification);
                    }
                }



            }

            $order = ProductOrder::where('user_id', $profileData->id)
                ->where('product_id', $product_id)
                ->where('payment_status', 0)
                ->latest()
                ->first();

            if ($order !== null) {
                    // Update existing order
                    $order->product_id = $product_id;
                    $order->product_category_id	= $product_category_id;
                    $order->payment_method = $payment_method;
                    $order->user_id = $user_id;
                    $order->payment_amount = $payment_amount;

                    //update vps info
                    if($product_category_id == 2){
                        $order->start_date = $start_date;
                        $order->vps_billing_cycle = $billing_cycle;
                        $order->expiry_date = $expiry_date;
                    }
                    //update membership info
                    if($product_category_id == 3){
                        $order->start_date = $start_date;
                        $order->vps_billing_cycle = $billing_cycle;
                        $order->membership_plan = $membership_plan;
                        $order->expiry_date = $expiry_date;
                    }

                    $order->save();

                }else{
                        // Create Order if it doesn't exist and product_category_id == 2

                        if($product_category_id == 2){
                        ProductOrder::create([
                            'product_id' => $product_id,
                            'product_category_id' => $product_category_id,
                            'payment_method' => $payment_method,
                            'user_id' => $user_id,
                            'payment_amount' => $payment_amount,
                            'start_date' => $start_date,
                            'vps_billing_cycle' => $billing_cycle,
                            'expiry_date' => $expiry_date

                        ]);
                    }elseif($product_category_id == 3){
                        ProductOrder::create([
                            'product_id' => $product_id,
                            'product_category_id' => $product_category_id,
                            'payment_method' => $payment_method,
                            'user_id' => $user_id,
                            'payment_amount' => $payment_amount,
                            'start_date' => $start_date,
                            'vps_billing_cycle' => $billing_cycle,
                            'membership_plan' => $membership_plan,
                            'expiry_date' => $expiry_date
                            ]);
                    }else{
                        ProductOrder::create([
                            'product_id' => $product_id,
                            'product_category_id' => $product_category_id,
                            'payment_method' => $payment_method,
                            'user_id' => $user_id,
                            'payment_amount' => $payment_amount

                        ]);
                    }
                }



             $product = Product::find($product_id);
             $pageTitle = 'Order Checkout';
             if($product_category_id == 2){
                return view('clientarea.checkout', compact( 'profileData', 'product', 'pageTitle','order', 'billing_cycle'));
             }elseif($product_category_id == 3){
                return view('clientarea.checkout', compact( 'profileData', 'product', 'pageTitle','order', 'membership_plan', 'billing_cycle'));
             }else{
             return view('clientarea.checkout', compact( 'profileData', 'product', 'pageTitle','order'));
             }

        }

         public function checkOut(Request $request)
        {
            $this->requireCampaignStoreModule();

            $profileData = $this->getProfileData();

            $pageTitle = 'Client Orders';

            $order_id       = $request->order_id;
            $product_id     = $request->product_id;
            $paymentMethod  = $request->payment_method;
            $user_id        = $request->user_id;
            $product_name   = $request->product_title;
            $firstname      = $request->firstname;
            $lastname       = $request->lastname;
            $email          = $request->email;
            $phone          = $request->phone;
            $tx_ref         = $request->tx_ref;
            $billing_cycle  = $request->billing_cycle;
            $product_price  = $request->product_price;

            if($profileData->country == "Nigeria"){
                    $paymentcurrency = "NGN";
            }else{
                    $paymentcurrency = SystemSetting::first()->system_currency;
            }



            //Generate activation code
            $timeDigits = str_split(substr(time(), -10), 2); // Extract and split the digits into pairs
            $randomStrings = collect($timeDigits)->map(function ($pair) {
                return $pair . Str::random(2);
            })->implode('');

            $activationCode = Str::random(2) . $user_id . Str::random(3) . $product_id . Str::random(1) . $randomStrings . Str::random(2);

                // Find the specific order by its ID
            $order = ProductOrder::find($order_id);

            // Check if the order is found
            if ($order) {
                // Check if the product is free and update accordingly
                if ($product_price == 0.00) {
                    // Update the order in the database
                        $order->update([
                        'user_id' => $user_id,
                        'product_id'=> $product_id,
                        'payment_amount' => $product_price,
                        'tx_ref' => $tx_ref,
                        'payment_method' => $paymentMethod,
                        'payment_status'=>1,
                        'activation_code'=> $activationCode,
                    ]);

                //if Product is not free Send Email Order details to the customer
                $user = User::find($user_id);
                $email = $user->email;
                $name = $user->firstname . ' ' . $user->lastname;
                $product = Product::find($product_id);
                $product_title = $product->name;
                $product_price = $order->payment_amount;
                $product_category_id = $product->category_id;
                $billing_cycle = $order->vps_billing_cycle;
                $member_plan =   $order->membership_plan;
                $tx_ref = $tx_ref;
                $activationCode = $activationCode;

                $this->sendOrderConfirmationEmail($email, $name, $product_title, $product_price, $product_category_id, $billing_cycle, $tx_ref, $member_plan, $activationCode);

                //update user table if membership order
                if($product_category_id == 3){
                    $user->update([
                        'member' => 'Yes',
                    ]);
                }

                $notification = array(
                    'message' => 'Order Placed and Processed Successfully, Please Check Your Email for the product Activation Code',
                    'alert-type' => 'success',
                    );

                return redirect()->route('user.orders')->with($notification);

            }elseif($product_price > 0.00) {
                // Handle the case where the product is not free

                // Update the order in the database
                $order->update([
                    'user_id' => $user_id,
                    'product_id'=> $product_id,
                    'payment_amount' => $product_price,
                    'tx_ref' => $tx_ref,
                    'payment_method' => $paymentMethod,
                ]);

                if($profileData->country == "Nigeria" && $paymentMethod == 'flutterwave'){
                    $product_price  = $request->product_price * SystemSetting::first()->exchange_rate;
                    }else{
                            $product_price  = $request->product_price;
                    }

                //Get Payment gateways
                $paymentGateways = PaymentGateway::all();

                        if($paymentMethod == 'flutterwave'){
                            $paymentMethod = 'Flutterwave';
                            $paymentGateway = $paymentGateways->where('name', $paymentMethod)->first();
                        }

                        if($paymentMethod=="usdt-direct"){
                            $paymentMethod ='USDT-Direct';
                            $paymentGateway = $paymentGateways->where('name', $paymentMethod)->last();
                        }



                if($paymentGateway->name == 'Flutterwave') {
                    // redirect to flutterwave
                        $redirect_url = route('flutterwave.redirect', ['id' => $tx_ref]); // Define the URL.
                        if($paymentGateway->sandbox_mode == 2){
                        $api_key = $paymentGateway->flutterwave_live_api_key;
                        }else{
                            $api_key = $paymentGateway->flutterwave_test_api_key;
                        }

                        $name         = $firstname." ".$lastname;
                        $email        = $email;
                        $phone_number = $phone;
                        $amount       = $product_price;
                        $product_name = $product_name;
                        $tx_ref       = $tx_ref;
                        $currency     = $paymentcurrency;
                        //$system_name  = SystemSetting::first()->system_name;
                        $system_name  = "Diginet Tech Africa Ltd flw";
                        $system_logo  = "https://ultimatepipsfrx.com/uploads/logo/favicon.png";
                    //Integrate Rave pament
                        $endpoint = "https://api.flutterwave.com/v3/payments";


                        //Required Data
                        $postdata = array(
                            "tx_ref" => $tx_ref,
                            "currency" => $currency,
                            "amount" => $amount,
                            "customer" =>array(
                                "name" => $name,
                                "email" => $email,
                                "phone_number" => $phone_number
                            ),
                            "customizations" =>array(
                                "title" => $system_name,
                                "logo" => $system_logo,
                            ),

                            "meta" =>array(
                                "reason" => "Payment for ".$product_id,
                                "address" => "Payment from"." ".$name
                            ),

                            "redirect_url" => $redirect_url
                        );

                        //Init cURL handler
                        $ch = curl_init();

                        //Turn of SSL checking
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

                        //Set the endpoint
                        curl_setopt($ch, CURLOPT_URL, $endpoint);

                        //Turn on the cURL post method
                        curl_setopt($ch, CURLOPT_POST, 1);

                        //Encode the post field
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postdata));

                        //Make it reurn data
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                        //Set the waiting timeout
                        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 200);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 200);

                        //Set the headers from endpoint
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        "Authorization: Bearer ". $api_key,
                        "Content-Type: Application/json",
                        "Cache-Control: no-cahe"
                        ));

                        //Execute the cURL session
                        $request = curl_exec($ch);

                        $result = json_decode($request);
                        curl_close($ch);

                        return redirect($result->data->link);
                        // header("Location: ".$result->data->link);
                        //var_dump($result);
                        //Close the cURL session


                    }elseif($paymentGateway->name == 'USDT-Direct'){


                       $price = encrypt($product_price);

                       $productPrice = Product::where('id', $request->product_id)->first();
                       $product_price = $productPrice->price * $billing_cycle;
                            // Update the order in the database
                        $order->update(([
                            'user_id' => $user_id,
                            'product_id'=> $product_id,
                            'payment_amount' => $product_price,
                            'payment_method' => $paymentMethod,
                        ]));

                        $notification =  array(
                            'message' => 'Order Placed Successfully, please proceed to make payment',
                            'alert-type' => 'success',
                        );

                        return redirect()->route('usdt.direct', compact('price', 'tx_ref'))->with($notification);

                    }

                }



        } else {
                    // Handle the case where the order is not found
                    // You might want to display an error message or redirect to an error page.
                }



    }




        public function flutterwaveredirect(Request $request, $id){
            $this->requireCampaignStoreModule();

            $profileData = $this->getProfileData();
            $user_id = $profileData->id;

              //check for a valid  ID, through GET or POST:
                   $tx_ref = $request->tx_ref;

                   $trans_status = $request->status;

                   $transaction_id = $request->transaction_id;

            //check if its invoice payment
            $invoice = Invoice::where('tx_ref', $tx_ref)->first();
            if($invoice  && $transaction_id  && ($trans_status == 'successful' || $trans_status == 'completed' ))
            {

                $invoice->update([
                    'is_paid' =>   1,
                ]);


                $updateOrder = ProductOrder::where('invoice_number',  $invoice->invoice_no)->first();
                    if($updateOrder != NULL){
                        $updateOrder->update([
                            'invoice_number' =>   " ",
                            'invoice_is_paid' =>   1,
                        ]);
                    }

                    //send invoice payment notification email in the future

                    $notification = array(
                        'message' => 'Invoice Payment is '.$trans_status.', Thank you!',
                        'alert-type' => 'success',
                        );

                    return redirect()->route('user.invoices')->with($notification);

            }elseif($invoice && $trans_status == 'cancelled'){
                $notification = array(
                    'message' => 'Invoice Payment is '.$trans_status.', Please try again!',
                    'alert-type' => 'error',
                    );
                return redirect()->route('user.invoices')->with($notification);
            }else{

                //check if its order payment
            //Generate activation code
            $timeDigits = str_split(substr(time(), -10), 2); // Extract and split the digits into pairs
            $randomStrings = collect($timeDigits)->map(function ($pair) {
                return $pair . Str::random(2);
            })->implode('');

            $product_id = ProductOrder::where('tx_ref', $id)->first()->product_id;

            $activationCode = Str::random(2) . $user_id . Str::random(3) . $product_id . Str::random(1) . $randomStrings . Str::random(2);


            $Order = ProductOrder::where('tx_ref', $tx_ref)->first();

             if( $transaction_id != NULL   /* && ($trans_status == 'successful') */ ){
                $payment_status = 1;
                }else{
                $payment_status = 0;
                }

            $Order->update([
                'payment_status' =>   $payment_status,
                'tx_ref' => $request->tx_ref,
                'activation_code' => $activationCode,
            ]);


            if($payment_status == 1){
                // Email Order details and activation code to the customer
                $user = User::find($user_id);
                $email = $user->email;
                $name = $user->firstname . ' ' . $user->lastname;
                $product = Product::find($product_id);
                $product_title = $product->name;
                $product_price = ProductOrder::where('tx_ref', $tx_ref)->where('product_id', $product_id)->first()->payment_amount;
                $tx_ref = $tx_ref;
                $product_category_id = $product->category_id;
                $billing_cycle = $Order->billing_cycle;
                $member_plan =  $Order->membership_plan;
                $activationCode = $activationCode;

                $this->sendOrderConfirmationEmail($email, $name, $product_title, $product_price, $product_category_id, $billing_cycle, $tx_ref, $member_plan, $activationCode);

                if($product_category_id == 3){
                    //update member column in users table

                    $user->update([
                        'member' => 'Yes',
                    ]);

                }

                $notification = array(
                    'message' => 'Order Placed and '.$trans_status.', Please Check Your Email for Order Details',
                    'alert-type' => 'success',
                    );

                return redirect()->route('user.orders')->with($notification);
            }else{
                //Flash Failed Transaction Notification
                $notification = array(
                    'message' => 'Transaction Failed, Please Try Again',
                    'alert-type' => 'error',
                    );
                    return redirect()->route('user.order.process')->with($notification);
                }


        }
    }



        public function usdtDirect(Request $request){
            $this->requireCampaignStoreModule();

            $pageTitle = 'USDT TRC20 ';
            $profileData = $this->getProfileData();
            $user_id = $profileData->id;
            $product_price = $request->price;
            $tx_ref = $request->tx_ref;



            return view('clientarea.dirusdt', compact('product_price', 'tx_ref',   'user_id', 'profileData', 'pageTitle'));
        }


        public function vpsStoreFront(){
             $this->requireCampaignStoreModule();

             $pageTitle = "Trading Servers";
             $plans = Product::where('category_id', 2)->get();
             return view('frontend.vps', compact('plans', 'pageTitle'));
        }

        private function requireCampaignStoreModule(): void
        {
            abort_unless(
                app(ModuleGateService::class)->enabled('campaign_store'),
                403,
                'Campaign store module is not available for this release.'
            );
        }

}
