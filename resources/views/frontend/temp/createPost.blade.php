<!-- New Post Section -->
<div class="container mt-4">
    <div class="row">

              <!-- Include Left Sidebar  -->
            <div class="col-md-3 sidebar">
                <?php include('sidebar.blade.php'); ?>
            </div>

            <div class="col-md-6">

                <div class="card mb-3">
                    <div class="card-body">
                        <textarea class="form-control" rows="3" placeholder="What's on your mind?"></textarea>
                        <button class="btn btn-success mt-2" onclick="createPost()">Post</button>
                    </div>
                </div>


                <!-- Include Newsfeed -->
                <?php include('newsfeed.blade.php'); ?>
            </div>

               <!-- Include Right Sidebar -->
            <div class="col-md-3 sidebar">
              <?php include('right-sidebar.blade.php'); ?>
            </div>

    </div>
</div>

<script>
    function createPost() {
        // Logic to handle posting can be implemented here
        alert("Post created!"); // Placeholder alert for demonstration
    }
</script>
