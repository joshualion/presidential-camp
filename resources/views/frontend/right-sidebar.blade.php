 @php
     use App\Models\Product;
     $donate = Product::where('category_id', 1)->first();
     $ads = Product::where('category_id', 6)->first();
 @endphp
@desktop()
<!-- Sidebar Widget  -->
<div class="card card-primary">
    <div class="card-header">
        <h6 class="card-title">Donate</h6>
        </div>
    <h6 class="card-title"></h6>
    <div class="card-body">
    <img src="{{asset('uploads/product_images/'.$donate->image)}}" class="img-thumbnail img-responsive"/>
    </div>
</div>

<hr/>
<!-- Sidebar Widget 2 -->
<div class="card card-primary">
    <div class="card-header">
        <h6 class="card-title">Sponsored</h6>
        </div>
    <h6 class="card-title"></h6>
    <div class="card-body">
        <img src="{{asset('uploads/product_images/'.$ads->image)}}" class="img-thumbnail img-responsive"/>
    
    </div>
</div>

<hr/>
<!-- Sidebar Widget 3 -->
<!--<div class="card card-primary mt-20 gap-10">
    <div class="card-header">
        <h6 class="card-title">Events</h6>
        </div>
    <h6 class="card-title"></h6>
    <div class="card-body">
    <p>This section can be used for announcements, links, or events.</p>
    </div>
</div>

<hr/>-->
<!-- Sidebar Widgit  3 -->
<div class="card card-primary pt-40 mt-20">
    <div class="card-header">
        <h6 class="card-title">Polls Watch</h6>
        </div>
    <h6 class="card-title"></h6>
    <div class="card-body">
    <p>Displays election updates and stats .</p>
    </div>
</div>
@enddesktop()