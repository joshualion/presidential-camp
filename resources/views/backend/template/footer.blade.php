

</div><!-- /.container-fluid -->
</section>
    <!-- /.Main content -->

</div>  <!-- /.content-wrapper -->


  <footer class="main-footer">
    <strong>Copyright &copy; {{Date('Y')}} <a href="https://wa.link/wk31zi">Joshua Ekpe</a>.</strong>
    All rights reserved.
    <div class="float-right d-none d-sm-inline-block">
      <em>Promoting Good Governance</em>
    </div>
  </footer>


</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<!-- Handle photo upload -->
<script type="text/javascript">
    $(document).ready(function(){
      $('#image').change(function(e){
        var reader = new FileReader();
        reader.onload = function(e){
          $('#showimage').attr('src',e.target.result);
        }
        reader.readAsDataURL(e.target.files['0']);
      });
    });
  </script>

<script>
    // Function to update the label text when a file is selected
    function updateLabel(input) {
      const customLabel = document.getElementById('custom-label');
      if (input.files.length > 0) {
        customLabel.textContent = input.files[0].name;
      } else {
        customLabel.textContent = 'Custom Label for File Upload';
      }
    }
  </script>

  <!-- Handle Voter eligibility and toggles display of VIN input  -->
<script>
    var validVoterYes = document.getElementById('validvoter_yes');
    var vinInputBox = document.getElementById('vinInputBox');

    validVoterYes.addEventListener('click', function() {
        vinInputBox.style.display = 'block';
    });

    var validVoterNo = document.getElementById('validvoter_no');

    validVoterNo.addEventListener('click', function() {
        vinInputBox.style.display = 'none';
    });
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#phone').on('input', function() {
        var phoneNumber = $(this).val();
        var validPattern = /^\+\d{1,3}\d{5,15}$/;

        if (!validPattern.test(phoneNumber)) {
            $(this).addClass('invalid');
        } else {
            $(this).removeClass('invalid');
        }
    });
});
</script>


<!-- Handle Intellinput for Phone -->
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/intlTelInput.min.js"></script>
<script>
  const input = document.querySelector("#phone");
  const button = document.querySelector("#btn");
  const errorMsg = document.querySelector("#error-msg");
  const validMsg = document.querySelector("#valid-msg");

// here, the index maps to the error code returned from getValidationError - see readme
  const errorMap = ["Invalid number", "Invalid country code", "Too short", "Too long", "Invalid number"];

  window.intlTelInput(input, {
    initialCountry: "auto",
  geoIpLookup: callback => {
    fetch("https://ipapi.co/json")
      .then(res => res.json())
      .then(data => callback(data.country_code))
      .catch(() => callback("us"));
      
  },
     separateDialCode: true,
      hiddenInput: "full_phone",
    utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@18.1.1/build/js/utils.js?1690975972744",
  });

  const reset = () => {
  input.classList.remove("error");
  errorMsg.innerHTML = "";
  errorMsg.classList.add("hide");
  validMsg.classList.add("hide");
  };

   // on click button: validate and send to server
    button.addEventListener('click', () => {
        reset();
        if (input.value.trim()) {
            if (iti.isPossibleNumber()) {
                validMsg.classList.remove("hide");

                // Get the full formatted phone number, including the country code
                const hiddenInput = document.querySelector("#full_phone");
                const fullPhoneNumber = hiddenInput.value;

                // Add the phone number to a hidden field in the form
                const hiddenPhoneNumberInput = document.createElement("input");
                hiddenPhoneNumberInput.type = "hidden";
                hiddenPhoneNumberInput.name = "full_phone";
                hiddenPhoneNumberInput.value = fullPhoneNumber;
                document.getElementById("profileData").appendChild(hiddenPhoneNumberInput);

                // Submit the form
                document.getElementById("profileData").submit();
            } else {
                input.classList.add("error");
                const errorCode = iti.getValidationError();
                errorMsg.innerHTML = errorMap[errorCode];
                errorMsg.classList.remove("hide");
            }
        }
    });

// on keyup / change flag: reset
input.addEventListener('change', reset);
input.addEventListener('keyup', reset);
</script>


<!-- Handle Ajax request to states, lgas, wards and PU dropdown select -->
<script>
    $(document).ready(function() { 
        $('#region-dropdown').on('change', function() {
            var region_id = this.value;
            console.log(region_id);
            $.ajax({
                url: "{{ route('getStates') }}",
                type: "POST",
                data: {
                    region_id: region_id,
                    _token: '{{csrf_token()}}'
                },
                cache: false,
                dataType: 'json',
                success: function(result){
                  $('#state-dropdown').html('<option value="">Select Voting State</option>');
                        $.each(result.states, function (key, value) {
                            $("#state-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        });

                    $('#lga-dropdown').html('<option value="">Select Voting LGA</option>'); 
                }
            });
        });

        $('#state-dropdown').on('change', function() {
            var state_id = this.value;
            console.log(state_id);
            $.ajax({
                url: "{{ route('getLgas') }}",
                type: "POST",
                data: {
                    state_id: state_id,
                    _token: '{{csrf_token()}}'
                },
                cache: false,
                dataType: 'json',
                success: function(result){
                  $('#lga-dropdown').html('<option value="">Select Voting LGA</option>');
                        $.each(result.lgas, function (key, value) {
                            $("#lga-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        });
                    $('#ward-dropdown').html('<option value="">Select Voting Ward</option>'); 
                }
            });
        });

        $('#lga-dropdown').on('change', function() {
            var lga_id = this.value;
            console.log(lga_id);
            $.ajax({
                url: "{{ route('getWards') }}",
                type: "POST",
                data: {
                    lga_id: lga_id,
                    _token: '{{csrf_token()}}'
                },
                cache: false,
                dataType: 'json',
                success: function(result){
                  $('#ward-dropdown').html('<option value="">Select Voting Ward</option>');
                        $.each(result.wards, function (key, value) {
                            $("#ward-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        });
                    $('#pu-dropdown').html('<option value="">Select Voting Polling Units</option>'); 
                }
            });
        });

        $('#ward-dropdown').on('change', function() {
            var ward_id = this.value;
            console.log(ward_id);
            $.ajax({
                url: "{{ route('getPollingUnits') }}",
                type: "POST",
                data: {
                    ward_id: ward_id,
                    _token: '{{csrf_token()}}'
                },
                cache: false,
                dataType: 'json',
                success: function(result){  
                    $('#pu-dropdown').html('<option value="">Select Voting Polling Units</option>');
                        $.each(result.pollingUnits, function (key, value) {
                            $("#pu-dropdown").append('<option value="' + value
                                .id + '">' + value.name + '</option>');
                        }); 
                }
            });
        });
    });
</script>
<!-- Toaster Js -->
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>


 @if(Session::has('message'))
 <script>
 var type = "{{ Session::get('alert-type','info') }}"
 switch(type){
    case 'info':
    toastr.info(" {{ Session::get('message') }} ");
    break;

    case 'success':
    toastr.success(" {{ Session::get('message') }} ");
    break;

    case 'warning':
    toastr.warning(" {{ Session::get('message') }} ");
    break;

    case 'error':
    toastr.error(" {{ Session::get('message') }} ");
    break; 
 }
 </script>
 @endif 




<!-- jQuery -->
<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>
<!-- jQuery UI 1.11.4 -->
<script src="{{asset('assets/plugins/jquery-ui/jquery-ui.min.js')}}"></script>
<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
<script>
  $.widget.bridge('uibutton', $.ui.button)
</script>
<!-- Bootstrap 4 -->
<script src="{{asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!-- ChartJS -->
<script src="{{asset('assets/plugins/chart.js/Chart.min.js')}}"></script>
<!-- Sparkline -->
<!-- <script src="{{asset('assets/plugins/sparklines/sparkline.js')}}"></script> -->
<!-- JQVMap -->
<script src="{{asset('assets/plugins/jqvmap/jquery.vmap.min.js')}}"></script>
<script src="{{asset('assets/plugins/jqvmap/maps/jquery.vmap.usa.js')}}"></script>
<!-- jQuery Knob Chart -->
<script src="{{asset('assets/plugins/jquery-knob/jquery.knob.min.js')}}"></script>
<!-- daterangepicker -->
<script src="{{asset('assets/plugins/moment/moment.min.js')}}"></script>
<script src="{{asset('assets/plugins/daterangepicker/daterangepicker.js')}}"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="{{asset('assets/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')}}"></script>
<!-- Summernote -->
<script src="{{asset('assets/plugins/summernote/summernote-bs4.min.js')}}"></script>
<!-- overlayScrollbars -->
<script src="{{asset('assets/plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js')}}"></script>
<!-- AdminLTE App -->
<script src="{{asset('assets/dist/js/adminlte.js')}}"></script>
<!-- AdminLTE for demo purposes -->
<script src="{{asset('assets/dist/js/pages/dashboard.js')}}"></script>

<!-- SweetAlerts -->
<script src="{{asset('assets/plugins/sweetalert2/sweetalert2.min.js')}}"></script>


<!-- DataTables  & Plugins -->
<script src="{{asset('assets/plugins/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{asset('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{asset('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
<script src="{{asset('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
<script src="{{asset('assets/plugins/datatables-buttons/js/dataTables.buttons.min.js')}}"></script>
<script src="{{asset('assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js')}}"></script>
<script src="{{asset('assets/plugins/jszip/jszip.min.js')}}"></script>
<script src="{{asset('assets/plugins/pdfmake/pdfmake.min.js')}}"></script>
<script src="{{asset('assets/plugins/pdfmake/vfs_fonts.js')}}"></script>
<script src="{{asset('assets/plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
<script src="{{asset('assets/plugins/datatables-buttons/js/buttons.print.min.js')}}"></script>
<script src="{{asset('assets/plugins/datatables-buttons/js/buttons.colVis.min.js')}}"></script>

</body>
</html>
