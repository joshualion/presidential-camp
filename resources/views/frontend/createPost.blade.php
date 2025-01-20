<!-- New Post Section -->
<div class="container mt-4">
    <div class="row">

              <!-- Include Left Sidebar  -->
            @desktop()
            <div class="col-md-3 sidebar">
                @include('frontend.sidebar')
            </div>
            @enddesktop()

            <div class="col-md-6">

                <div class="card mb-3">
                    <div class="card-body">
                        <textarea class="form-control" rows="3" placeholder="What's on your mind?">
                            
                        </textarea>
                        <button class="btn btn-success mt-2" onclick="createPost()">Post</button>

                       
                        
                    </div>
                </div>


                <!-- Include Newsfeed -->
                @include('frontend.newsfeed')
            </div>

               <!-- Include Right Sidebar -->
            <div class="col-md-3 sidebar">
              @include('frontend.right-sidebar')
            </div>

    </div>
</div>

<script>
    function createPost() {
        // Logic to handle posting can be implemented here
        alert("Post created!"); // Placeholder alert for demonstration
    }
</script>
