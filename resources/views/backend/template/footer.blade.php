

</div><!-- /.container-fluid -->
</section>
    <!-- /.Main content -->

</div>  <!-- /.content-wrapper -->


  <footer class="main-footer">

        @if($SystemSetting->copyright)
           {!! $SystemSetting->copyright !!}
        @else
            Copyright &copy; {{ date('Y') }} <a href="#" target="_blank">Govware Solutions Limited</a> - All rights reserved.
        @endif

        <div class="float-right d-none d-sm-inline-block">
            <em>{!! $SystemSetting->campaign_slogan !!}</em>
        </div>


  </footer>


</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="{{asset('assets/plugins/jquery/jquery.min.js')}}"></script>
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

<!-- Toaster Js -->
<script type="text/javascript" src="{{asset('assets/plugins/toastr/toastr.min.js')}}"></script>


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

 @livewireScripts
</body>
</html>
