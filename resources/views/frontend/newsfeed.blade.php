<!-- Newsfeed Section -->
<div class="container mt-4">
      <div class="row">
        <div class="col-md-12">
            <h5>Newsfeed</h5>
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">User Name</h6>
                    <p class="card-text">This is an example of a newsfeed post. Users can share updates, news, or thoughts here.</p>
                    <div class="d-flex justify-content-between">
                        <div>
                            <button class="btn btn-success btn-sm">Like</button>
                            <span class="ms-2" id="likeCount1">0</span>
                        </div>
                        <div>
                            <button class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#commentSection1">Comment</button>
                            <span class="ms-2" id="commentCount1">0</span>
                        </div>
                    </div>
                    <div class="collapse" id="commentSection1">
                        <div class="mt-2">
                            <input type="text" class="form-control" placeholder="Write a comment...">
                            <button class="btn btn-success btn-sm mt-2" onclick="postComment(1)">Post Comment</button>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Repeat the card structure for more posts -->
            <div class="card mb-3">
                <div class="card-body">
                    <h6 class="card-title">User Name</h6>
                    <p class="card-text">Another example of a newsfeed post.</p>
                    <div class="d-flex justify-content-between">
                        <div>
                            <button class="btn btn-success btn-sm">Like</button>
                            <span class="ms-2" id="likeCount2">0</span>
                        </div>
                        <div>
                            <button class="btn btn-light btn-sm" data-bs-toggle="collapse" data-bs-target="#commentSection2">Comment</button>
                            <span class="ms-2" id="commentCount2">0</span>
                        </div>
                    </div>
                    <div class="collapse" id="commentSection2">
                        <div class="mt-2">
                            <input type="text" class="form-control" placeholder="Write a comment...">
                            <button class="btn btn-success btn-sm mt-2" onclick="postComment(2)">Post Comment</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    function postComment(postId) {
        const commentCountElement = document.getElementById(`commentCount${postId}`);
        let count = parseInt(commentCountElement.innerText);
        count++;
        commentCountElement.innerText = count.toString();
    }
</script>
