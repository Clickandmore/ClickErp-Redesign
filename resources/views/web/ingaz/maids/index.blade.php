@extends('web.layout.app')

@section('content')
    <main>
        <!-- page header -->

        <section class="page_header">
            <h1>{{ helperTrans('web.browse_employees') }}</h1>
            <h6> <a href="{{ route('web.home') }}">{{ __('web.Home') }}</a>/ {{ $type->title ?? helperTrans('web.maids') }}
            </h6>
        </section>
        <!-- terms_conditions section -->
        <!-- *** explore Cvs *** -->
        <section class="exploreCVs">
            <div class="container">
                <div class="row">
                    @include('web.ingaz.maids.filter_bar')

                    <div class="col-lg-8 col-12 maid_div">

                        @include('web.ingaz.maids.maid_div', $type)

                    </div>

                </div>
            </div>
        </section>
    </main>
@endsection

@section('js')
    <script>
        $('#age').change(function (e) {
            var selectedOption = $(this).find(':selected');

            // Retrieve data attributes from the selected option
            var ageFrom = selectedOption.data('age-from');
            var ageTo = selectedOption.data('age-to');

            // Set the values of hidden inputs
            $('#age_from').val(ageFrom);
            $('#age_to').val(ageTo);

        });


        $(document).ready(function () {
            $('#filter-submit-global').submit(function (event) {
                event.preventDefault();
                var formData = $(this).serialize(); // Serialize the form data

                $.ajax({
                    type: 'get',
                    url: '{{ route('web.maids.index') }}',
                    data: formData,
                    beforeSend: function () {
                        $(".preloader").css("background-color", "rgba(255, 255, 255, 0.5)").show();
                    },
                    complete: function () {
                        $(".preloader").fadeOut(300);
                    },
                    success: function (response) {
                        $('.maid_div').html(response);
                    },
                    error: function (error) {
                        console.error(error);
                    }
                });
            });

            // Handle pagination links
            $(document).on('click', '.pagination a', function (event) {
                event.preventDefault();
                var url = $(this).attr('href');

                $.ajax({
                    url: url,
                    beforeSend: function () {
                        $(".preloader").css("background-color", "rgba(255, 255, 255, 0.5)").show();
                    },
                    complete: function () {
                        $(".preloader").fadeOut(300);
                    },
                    success: function (response) {
                        $('.maid_div').html(response);
                    },
                    error: function (error) {
                        console.error(error);
                    }
                });
            });
        });
    </script>

    </script>
    <script>
        $(document).ready(function () {
            $('.submit-global').submit(function (event) {
                event.preventDefault();
                var formData = $(this).serialize(); // Serialize the form data

                $.ajax({
                    type: 'post',
                    url: '{{ route('web.orders.store') }}',
                    data: formData,
                    beforeSend: function () {
                        $(".preloader").css("background-color", "rgba(255, 255, 255, 0.5)")
                            .show();
                    },
                    complete: function () {
                        $(".preloader").fadeOut(300);
                    },
                    success: function (response) {
                        var data = response.data;
                        console.log(data.order.user.name);
                        $("#customer_name").text(data.order.user.name);
                        $("#confirmModal").modal('show');
                        // $('.maid_div').html('' + response);
                    },
                    error: function (error) {
                        console.error(error);
                    }
                });
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('filter-submit-global');
            form.addEventListener('change', function (event) {
                var target = event.target;
                if (target.tagName === 'INPUT' || target.tagName === 'SELECT') {
                    var applyButton = document.getElementById('closeFilter');
                    applyButton.click();
                }
            });
        });
    </script>
@endsection