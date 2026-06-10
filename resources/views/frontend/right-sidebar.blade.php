 @php
    use App\Models\Product;
    $donate = Product::where('category_id', 1)->first();
    $ads = Product::where('category_id', 6)->first();
    $fallbackImage = asset('uploads/no_image.jpg');
    $donateImage = ($donate && $donate->image) ? asset('uploads/product_images/'.$donate->image) : $fallbackImage;
    $adsImage = ($ads && $ads->image) ? asset('uploads/product_images/'.$ads->image) : $fallbackImage;
 @endphp
@desktop()
<!-- Polls Watch -->
<div class="card card-primary mb-3">
    <div class="card-header">
        <h6 class="card-title">Polls Watch</h6>
    </div>
    <div class="card-body">
        <p class="mb-0">Displays election updates and stats.</p>
    </div>
</div>

<!-- Donate -->
<div class="card card-primary mb-3">
    <div class="card-header">
        <h6 class="card-title">Donate</h6>
    </div>
    <div class="card-body text-center">
        <img src="{{ $donateImage }}" class="img-thumbnail img-responsive w-100" alt="Donate">
    </div>
</div>

<!-- Sponsored -->
<div class="card card-primary mb-3">
    <div class="card-header">
        <h6 class="card-title">Sponsored</h6>
    </div>
    <div class="card-body text-center">
        <img src="{{ $adsImage }}" class="img-thumbnail img-responsive w-100" alt="Sponsored">
    </div>
</div>
@enddesktop()
