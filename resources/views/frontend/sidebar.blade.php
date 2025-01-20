<!-- Sidebar -->
<div class="card card-primary" >
    <div class="card-header">
    <h6 class="card-title">Profile </h6>
    </div>
    <div class="card-body">
        <ul class="nav flex-column ">
            <h6 class="card-title" style="color: {{ $SystemSetting->dark_theme_color ?? '#008751'}} !important; font-size: small;">
                <a class="nav-link" href="{{route($profileData->access_level.'.profile')}}" >   <img class="profile-user-img img-fluid img-circle" src="{{(!empty($profileData->photo)) ? url('uploads/member_images/'.$profileData->photo) : url('uploads/no_image.jpg')}}" alt="profile" width="20%" height="20%">
              {{$profileData->firstname}} {{$profileData->lastname}} </a>
            </h6>
            <li><a class="dropdown-item" href="{{route($profileData->access_level.'.dashboard')}}"> <i class="fas fa-laptop nav-icon"></i> Dashboard</a></li>
            <li>
                <a class="dropdown-item" href="javascript:void(0)" @click.prevent="$root.$emit('show-followers')">
                    <i class="fas fa-users nav-icon"></i> Followers <span class=" end">{{$followersCount}}</span>
                </a>
            </li>
            
              <li>
                <a class="dropdown-item" href="javascript:void(0)"  @click.prevent="showFollowing">
                  <i class="fas fa-biking nav-icon"></i> Following <span class=" end">{{$followingCount}}</span>
                </a>
              </li>               
            <hr>
                     
            <h6 class="card-title" style="color: {{ $SystemSetting->dark_theme_color ?? '#008751'}} !important; font-size: small;"> <i class="fas fa-landmark"> </i> Location</h6>
            <hr/>
            @if(($SystemSetting) && ($SystemSetting->package == 'national'))
            <li class="nav-item small">
                <div class="d-flex align-items-center">
                    <a class="nav-link flex-grow-1" href="#">{{ ucwords(strtolower($profileData->country->name)) }}</a>
                </div>
            </li>
            @endif
            @if(($SystemSetting) && ($SystemSetting->package == 'national') || ($SystemSetting->package == 'regional') )            
            <li class="nav-item small">
                <div class="d-flex align-items-center">
                    <a class="nav-link flex-grow-1" href="#">{{ ucwords(strtolower($profileData->region->name)) }}</a>
                </div>
            </li>
            @endif
            <li class="nav-item small">
                <div class="d-flex align-items-center">
                    <a class="nav-link flex-grow-1" href="#">{{ ucwords(strtolower($profileData->state->name)) }}</a>
                </div>
            </li>

            <li class="nav-item small">
                <div class="d-flex align-items-center">
                    <a class="nav-link flex-grow-1" href="#">{{ ucwords(strtolower($profileData->lga->name)) }}</a>
                </div>
            </li>
            <li class="nav-item small">
                <div class="d-flex align-items-center">
                    <a class="nav-link flex-grow-1" href="#"> Ward {{ ucwords(strtolower($profileData->ward->name)) }}</a>
                </div>
            </li>
            <li class="nav-item small">
                <div class="d-flex align-items-center">
                    <a class="nav-link flex-grow-1" href="#"> {{ ucwords(strtolower($profileData->pollingUnit->name))}}(PU) </a>
                </div>
            </li>
            <hr/>
            <h6 class="card-title" style="color: {{ $SystemSetting->dark_theme_color ?? '#008751'}} !important; font-size: small;"> <i class="fas fa-users"> </i> Volunteer Group(s)</h6>
            <hr/>
            @foreach($userSupportGroups as $group) 

            <li class="nav-item small">
                <div class="d-flex align-items-center">
                    <a class="nav-link flex-grow-1" href="#">{{ $group }}</a>
                    <a class="nav-link btn btn-sm text-sm" href="{{ route('select.support.group') }}">
                        <i class="fas fa-edit"></i>
                    </a>
                </div>
            </li>
            @endforeach
         </ul>


    </div>
</div>
