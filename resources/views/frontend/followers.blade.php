<div class="card mb-3" >
    <div class="card-body">
            <!-- Vue Component for Followers -->
            <Get-Followers v-if="isFollowersVisible"></Get-Followers>
    </div>

    <div class="card-body">       
        <!-- Vue Component for Following -->
        <Get-Following v-if="isFollowingVisible"></Get-Following>
    </div>

</div>