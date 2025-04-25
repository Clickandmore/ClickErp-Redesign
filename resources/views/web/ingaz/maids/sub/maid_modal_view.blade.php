@push('css')

@endpush

<!-- Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            {{-- <div class="modal-header">
                <h5 class="modal-title" id="detailsModalLabel">{{ helperTrans('web.info') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div> --}}
            <div class="modal-body">
                <div class="details">

                    <div class="maid-cv-template">
                        <img class="maid-cv-logo" src="{{ asset('web/assets/images/logoh.svg') }}" alt="" height="80px">
                        {{-- Top section --}}
                        <div class="row">
                            <div class="col-md-3">
                                <img src="{{ asset('/') }}" alt="cv1" id="maidImgTarget"
                                    style="height: 150px; width: 150px; border-radius: 50%; object-fit: cover;">
                            </div>
                            <div class="col-md-9">
                                <div class="row d-flex">
                                    <p class="col-md-6 cv-prop">@lang('web.age')</p>
                                    <p class="col-md-6"></p>
                                </div>
                                <div class="row d-flex">
                                    <p class="col-md-6 cv-prop">@lang('web.nationality')</p>
                                    <p class="col-md-6" id="nationality-trgt"></p>
                                </div>
                                <div class="row d-flex">
                                    <p class="col-md-6 cv-prop">@lang('web.religion')</p>
                                    <p class="col-md-6" id="religion-trgt"></p>
                                </div>
                                <div class="row d-flex">
                                    <p class="col-md-6 cv-prop">@lang('web.passport_num')</p>
                                    <p class="col-md-6" id="passport_number-trgt"></p>
                                </div>
                                <div class="row d-flex">
                                    <p class="col-md-6 cv-prop">@lang('web.recruitment_price')</p>
                                    <p class="col-md-6" id="recruitment_price-trgt"></p>
                                </div>
                            </div>
                        </div>
                        {{-- details section --}}
                        <div class="divider"></div>
                        <div class="row">
                            <div class="row d-flex">
                                <p class="col-md-4 cv-prop">@lang('web.marital_status')</p>
                                <p class="col-md-8" id="marital_status"></p>
                            </div>
                            <div class="row d-flex">
                                <p class="col-md-4 cv-prop">@lang('web.height')</p>
                                <p class="col-md-8" id="height-trgt"></p>
                            </div>
                            <div class="row d-flex">
                                <p class="col-md-4 cv-prop">@lang('web.weight')</p>
                                <p class="col-md-8" id="weight-trgt"></p>
                            </div>
                        </div>
                        {{-- download cv --}}
                        <div class="divider"></div>
                        <div class="row d-flex justify-content-center">
                            <div class="col-md-12 text-center">
                                <a class="btn btn-primary" id="maidCVTarget" href="#" target="_blank" data-passport="">
                                    @lang('web.download_cv')
                                </a>
                            </div>
                        </div>
                    </div>

                    {{-- <div class="" style="background-color: #edf4fa; border-radius: 3px">
                        <div class="row">
                            <div class="col-md-12 text-center justify-content-center align-content-center">
                                <b class="">@lang('web.cv')</b>
                            </div>
                            <div class="col-md-6 d-flex justify-content-center my-2 img-fluid">
                                <img src="{{ asset('/') }}" alt="cv1" id="maidImgTarget"
                                    style="height: 150px; width: 150px; border-radius: 50%; object-fit: cover;">
                            </div>
                            <div class="col-md-6 d-flex justify-content-center my-2 img-fluid">
                                <img src="{{ asset('/') }}" alt="cv1" id="maidCVTarget"
                                    style="height: 150px; width: 150px; border-radius: 50%; object-fit: cover;">
                            </div>
                        </div>
                    </div> --}}

                    {{-- <div class="row mt-2 text-center">
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="fs-6"><b>{{ helperTrans('web.salary') }}</b></div>
                                    <b id="salary-trgt">{{ helperTrans('web.none') }}</b>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="fs-6"><b>{{ helperTrans('web.recruitment_price') }}</b></div>
                                    <b id="recruitment_price-trgt">{{ helperTrans('web.none') }}</b>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="fs-6"><b>{{ helperTrans('web.experience_years') }}</b></div>
                                    <b id="experience_years-trgt">{{ helperTrans('web.none') }}</b>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <div class="fs-6"><b>{{ helperTrans('web.passport_number') }}</b></div>
                                    <b id="passport_number-trgt">{{ helperTrans('web.none') }}</b>
                                </div>
                            </div>
                        </div>
                    </div> --}}


                    {{-- <div class="cv mt-2 card card-body">
                        <div>
                            <b>{{ helperTrans('web.personal_info') }}</b>
                        </div>
                        <div class="">
                            <div class="info text-center">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div>{{ helperTrans('web.nationality') }}</div>
                                        <b id="nationality-trgt">{{ helperTrans('web.none') }}</b>
                                    </div>
                                    <div class="col-md-6">
                                        <div>{{ helperTrans('web.religion') }}</div>
                                        <b id="religion-trgt">{{ helperTrans('web.none') }}</b>
                                    </div>
                                    <div class="col-md-6">
                                        <div>{{ helperTrans('web.height') }}</div>
                                        <b id="height-trgt">{{ helperTrans('web.none') }}</b>
                                    </div>
                                    <div class="col-md-6">
                                        <div>{{ helperTrans('web.weight') }}</div>
                                        <b id="weight-trgt">{{ helperTrans('web.none') }}</b>
                                    </div>
                                    @if(@$type->title)
                                    <div class="col-md-6">
                                        <div>{{ helperTrans('web.occupation') }}</div>
                                        <b id="occupation-trgt">{{ @$type->title }}</b>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div> --}}


                </div>
            </div>
        </div>
    </div>
</div>


@push('js')
    <script>
        $(document).ready(function () {
            // Use event delegation to handle clicks on dynamically added elements
            $(document).on('click', '.maidViewData', function (e) {
                $('#detailsModal').modal('show');
                e.preventDefault();
                var maidId = $(this).data('id');
                $.ajax({
                    url: '{{ route('is.getMaidDetails', ['maidId' => ':maidId']) }}'.replace(':maidId', maidId),
                    method: 'GET',
                    success: function (data) {
                        // Update modal content with fetched data
                        if (data.image) {
                            $('#maidImgTarget').attr('src', data.image);
                        }
                        if (data.cv_file) {
                            $('#maidCVTarget').attr('href', data.cv_file).attr('data-passport', data.passport_num);
                        }
                        if (data.salary) {
                            $('#salary-trgt').text(data.salary + ' ر.س');
                        }
                        if (data.price) {
                            $('#recruitment_price-trgt').text(data.price + ' ر.س');
                        }
                        if (data.experience_years) {
                            $('#experience_years-trgt').text(data.experience_years);
                        }
                        if (data.passport_num) {
                            $('#passport_number-trgt').text(data.passport_num);
                        }
                        if (data.nationality && data.nationality.title) {
                            $('#nationality-trgt').text(data.nationality.title);
                        }
                        if (data.religion_title) {
                            $('#religion-trgt').text(data.religion_title);
                        }
                        if (data.height) {
                            $('#height-trgt').text(data.height);
                        }
                        if (data.weight) {
                            $('#weight-trgt').text(data.weight);
                        }
                        if (data.marital_status) {
                            $('#marital_status').text('@lang('web.marital_status_single')');
                        }
                    },
                    error: function (error) {
                        console.log('Error fetching data:', error);
                    }
                });
            });
            // 
            $('#maidCVTarget').on('click', function (event) {
                event.preventDefault(); // Prevent the default link behavior

                const imageUrl = $(this).attr('href'); // Replace with your actual image URL
                const maidPassport = $(this).data('passport');
                const link = $('<a>')
                    .attr('href', imageUrl)
                    .attr('download', maidPassport + '.jpg') // Customize the downloaded file name
                    .appendTo('body');

                link[0].click(); // Trigger the download
                link.remove(); // Clean up
            });
            // 
        });
    </script>
@endpush