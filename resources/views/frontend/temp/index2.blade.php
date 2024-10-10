
<body class="d-flex flex-column min-vh-100">

    <!-- Include Header -->
    <?php include('header.blade.php'); ?>

    <!-- Include Navbar -->
    <?php include('topnav.blade.php'); ?>

    <div class="container-fluid flex-grow-3">
        <div class="row">

            <div class="col-lg-12">
                <!-- Include New Post -->
                <?php include('createPost.blade.php'); ?>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include('footer.blade.php'); ?>
