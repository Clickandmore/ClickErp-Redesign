@php
    $maidDetailsRoute = Route::has('maids.details')
        ? route('maids.details', ['id' => '__MAID_ID__'])
        : route('is.getMaidDetails', ['maidId' => '__MAID_ID__']);
@endphp

<div class="modal fade" id="maidDetailsModal" tabindex="-1" aria-labelledby="maidDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="maidDetailsModalLabel">{{ helperTrans('ingaz.show_maid') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="maid-details-loading text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">{{ helperTrans('ingaz.loading') }}</span>
                    </div>
                </div>

                <div class="maid-details-error alert alert-danger d-none mb-0">
                    {{ helperTrans('ingaz.error') }}
                </div>

                <div class="maid-details-content d-none">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <a href="#" class="maid-details-file-link" target="_blank">
                                <img src="" alt="maid-preview" class="maid-details-image border rounded w-100"
                                    style="height:360px;object-fit:cover;">
                            </a>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <a href="#" class="btn btn-sm btn-outline-primary maid-details-file-link" target="_blank">
                                    {{ helperTrans('ingaz.cv_file') }}
                                </a>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered align-middle mb-0">
                                    <tbody>
                                        <tr>
                                            <th>{{ helperTrans('ingaz.name') }}</th>
                                            <td data-maid-field="name">-</td>
                                        </tr>
                                        <tr>
                                            <th>{{ helperTrans('ingaz.passport_num') }}</th>
                                            <td data-maid-field="passport_num">-</td>
                                        </tr>
                                        <tr>
                                            <th>{{ helperTrans('ingaz.nationality_id') }}</th>
                                            <td data-maid-field="nationality">-</td>
                                        </tr>
                                        <tr>
                                            <th>{{ helperTrans('ingaz.job_type') }}</th>
                                            <td data-maid-field="job_title">-</td>
                                        </tr>
                                        <tr>
                                            <th>{{ helperTrans('ingaz.religion_id') }}</th>
                                            <td data-maid-field="religion">-</td>
                                        </tr>
                                        <tr>
                                            <th>{{ helperTrans('ingaz.salary') }}</th>
                                            <td data-maid-field="salary">-</td>
                                        </tr>
                                        <tr>
                                            <th>{{ helperTrans('ingaz.price') }}</th>
                                            <td data-maid-field="price">-</td>
                                        </tr>
                                        <tr>
                                            <th>{{ helperTrans('ingaz.age') }}</th>
                                            <td data-maid-field="age">-</td>
                                        </tr>
                                        <tr>
                                            <th>{{ helperTrans('ingaz.experience_years') }}</th>
                                            <td data-maid-field="experience_years">-</td>
                                        </tr>
                                        <tr>
                                            <th>{{ helperTrans('ingaz.request_type') }}</th>
                                            <td data-maid-field="request_type_label">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@once
    <script>
        $(document).on('click', '.maidViewData', function(event) {
            event.preventDefault();

            const maidId = $(this).data('id');
            if (!maidId) {
                return;
            }

            const modal = $('#maidDetailsModal');
            const content = modal.find('.maid-details-content');
            const loading = modal.find('.maid-details-loading');
            const error = modal.find('.maid-details-error');
            const detailsUrl = @json($maidDetailsRoute).replace('__MAID_ID__', maidId);
            const emptyValue = '-';

            content.addClass('d-none');
            error.addClass('d-none');
            loading.removeClass('d-none');
            modal.modal('show');

            $.get(detailsUrl)
                .done(function(maid) {
                    const imageUrl = maid.image || maid.cv_preview || maid.cv_file || @json(asset('admin/assets/img/avatar.png'));
                    const fileUrl = maid.cv_file || maid.cv_preview || imageUrl;
                    const fields = {
                        name: maid.name,
                        passport_num: maid.passport_num,
                        nationality: maid.nationality ? maid.nationality.title : null,
                        job_title: maid.job_title || maid.occupation,
                        religion: maid.religion_title || maid.religion,
                        salary: maid.salary,
                        price: maid.price,
                        age: maid.age,
                        experience_years: maid.experience_years,
                        request_type_label: maid.request_type_label || maid.request_type
                    };

                    modal.find('.maid-details-image').attr('src', imageUrl);
                    modal.find('.maid-details-file-link').attr('href', fileUrl);

                    Object.keys(fields).forEach(function(field) {
                        modal.find('[data-maid-field="' + field + '"]').text(fields[field] || emptyValue);
                    });

                    loading.addClass('d-none');
                    content.removeClass('d-none');
                })
                .fail(function() {
                    loading.addClass('d-none');
                    error.removeClass('d-none');
                });
        });
    </script>
@endonce
