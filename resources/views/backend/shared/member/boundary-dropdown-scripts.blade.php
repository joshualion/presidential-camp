<script>
    $(document).ready(function() {
        function resetSenatorialDistricts() {
            $('#senatorial-district-dropdown').html('<option value="">Select Senatorial District</option>');
        }

        function resetFederalConstituencies() {
            $('#federal-constituency-dropdown').html('<option value="">Select Federal Constituency</option>');
        }

        $('#state-dropdown').on('change', function() {
            var state_id = this.value;

            resetSenatorialDistricts();
            resetFederalConstituencies();

            if (!state_id) {
                return;
            }

            $.ajax({
                url: "{{ route('getSenatorialDistricts') }}",
                type: "POST",
                data: {
                    state_id: state_id,
                    _token: '{{csrf_token()}}'
                },
                cache: false,
                dataType: 'json',
                success: function(result) {
                    $.each(result.senatorialDistricts, function(key, value) {
                        $('#senatorial-district-dropdown').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                }
            });

            $.ajax({
                url: "{{ route('getFederalConstituenciesByState') }}",
                type: "POST",
                data: {
                    state_id: state_id,
                    _token: '{{csrf_token()}}'
                },
                cache: false,
                dataType: 'json',
                success: function(result) {
                    $.each(result.federalConstituencies, function(key, value) {
                        $('#federal-constituency-dropdown').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                }
            });
        });

        $('#senatorial-district-dropdown').on('change', function() {
            var senatorial_district_id = this.value;

            resetFederalConstituencies();

            if (!senatorial_district_id) {
                return;
            }

            $.ajax({
                url: "{{ route('getFederalConstituenciesBySenatorialDistrict') }}",
                type: "POST",
                data: {
                    senatorial_district_id: senatorial_district_id,
                    _token: '{{csrf_token()}}'
                },
                cache: false,
                dataType: 'json',
                success: function(result) {
                    $.each(result.federalConstituencies, function(key, value) {
                        $('#federal-constituency-dropdown').append('<option value="' + value.id + '">' + value.name + '</option>');
                    });
                }
            });
        });
    });
</script>
