<!-- New Post Section -->
<div class="col-md-6">    


    <!-- Include VUE ROW-->
    <div class="row" id= "app" >
        <div class="col-md-12">
            <div class="card mb-3">                              
        
                <div class="card-body">
                    
                        <!-- Vue Component for Followers -->
                    <get-followers v-if="isFollowersVisible" :></get-followers>
                
                    <!-- Vue Component for Following -->
                    <get-following v-if="isFollowingVisible"></get-following>

                    
                </div>
            </div>
            
        </div>

    </div>
    <!-- Include VUE ROW-->  

</div>

