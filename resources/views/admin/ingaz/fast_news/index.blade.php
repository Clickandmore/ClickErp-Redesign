@extends('admin.layout.indexs.index')
@section("style")
    <style>
        .fast-news-page {
            background: #fff;
            border: 1px solid #e9edf3;
            border-radius: 14px;
            padding: 20px;
        }

        .fast-news-head {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 18px;
            margin-bottom: 18px;
            border-bottom: 1px solid #eef1f5;
        }

        .fast-news-head__title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .fast-news-head__icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: #eef4ff;
            color: #3b6fd8;
            font-size: 20px;
            flex-shrink: 0;
        }

        .fast-news-head h5 {
            margin: 0 0 4px;
            font-weight: 700;
        }

        .fast-news-head p {
            margin: 0;
            color: #6c757d;
            font-size: 13px;
            max-width: 560px;
        }

        .fast-news-head .addButton {
            margin: 0 !important;
        }

        .fast-news-head > div:last-child .d-flex {
            margin-bottom: 0 !important;
        }

        .fast-news-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 18px;
        }

        .fast-news-stat {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            background: #f7f9fc;
            border: 1px solid #eef1f5;
            min-width: 150px;
        }

        .fast-news-stat__dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .fast-news-stat__value {
            font-size: 18px;
            font-weight: 700;
            line-height: 1;
        }

        .fast-news-stat__label {
            font-size: 12px;
            color: #6c757d;
        }

        #dataTableObject td,
        #dataTableObject th {
            vertical-align: middle;
        }

        #dataTableObject th:not(:nth-child(2)),
        #dataTableObject td:not(:nth-child(2)) {
            text-align: center;
        }

        /* زر الظهور / الإخفاء */
        .fast-news-toggle {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid transparent;
            border-radius: 999px;
            padding: 4px 12px 4px 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, color .2s, border-color .2s, opacity .2s;
        }

        [dir="rtl"] .fast-news-toggle {
            padding: 4px 6px 4px 12px;
        }

        .fast-news-toggle:focus-visible {
            outline: 2px solid #3b6fd8;
            outline-offset: 2px;
        }

        .fast-news-toggle.is-loading {
            opacity: .55;
            pointer-events: none;
        }

        .fast-news-toggle__track {
            position: relative;
            width: 34px;
            height: 18px;
            border-radius: 999px;
            transition: background .2s;
        }

        .fast-news-toggle__thumb {
            position: absolute;
            top: 2px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #fff;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .2);
            transition: inset-inline-start .2s;
        }

        .fast-news-toggle.is-visible {
            background: #e8f7ee;
            color: #1e8e4e;
            border-color: #c6ebd4;
        }

        .fast-news-toggle.is-visible .fast-news-toggle__track {
            background: #28a745;
        }

        .fast-news-toggle.is-visible .fast-news-toggle__thumb {
            inset-inline-start: 18px;
        }

        .fast-news-toggle.is-hidden {
            background: #f3f4f6;
            color: #6b7280;
            border-color: #e5e7eb;
        }

        .fast-news-toggle.is-hidden .fast-news-toggle__track {
            background: #c4c9d1;
        }

        .fast-news-toggle.is-hidden .fast-news-toggle__thumb {
            inset-inline-start: 2px;
        }

        .fast-news-actions {
            display: inline-flex;
            gap: 6px;
        }
    </style>
@endsection
@section('page-title')
    {{helperTrans("web.fast_news")}}
@endsection
@section('content')
    <div class="fast-news-page">
        <div class="fast-news-head">
            <div class="fast-news-head__title">
                <span class="fast-news-head__icon"><i class="fa fa-newspaper-o"></i></span>
                <div>
                    <h5>{{ helperTrans("web.fast_news") }}</h5>
                    <p>{{ __('web.fast_news_page_hint') }}</p>
                </div>
            </div>
            <div>
                {!! addButton(route("fast-news.create") ,helperTrans("web.fast_news")) !!}
            </div>
        </div>

        <div class="fast-news-stats">
            <div class="fast-news-stat">
                <span class="fast-news-stat__dot" style="background:#3b6fd8"></span>
                <div>
                    <div class="fast-news-stat__value" id="fast-news-total">{{ $data['totalCount'] }}</div>
                    <div class="fast-news-stat__label">{{ __('web.fast_news_total') }}</div>
                </div>
            </div>
            <div class="fast-news-stat">
                <span class="fast-news-stat__dot" style="background:#28a745"></span>
                <div>
                    <div class="fast-news-stat__value" id="fast-news-visible">{{ $data['visibleCount'] }}</div>
                    <div class="fast-news-stat__label">{{ __('web.fast_news_visible') }}</div>
                </div>
            </div>
            <div class="fast-news-stat">
                <span class="fast-news-stat__dot" style="background:#c4c9d1"></span>
                <div>
                    <div class="fast-news-stat__value" id="fast-news-hidden">{{ $data['totalCount'] - $data['visibleCount'] }}</div>
                    <div class="fast-news-stat__label">{{ __('web.fast_news_hidden') }}</div>
                </div>
            </div>
        </div>

        <table id="dataTableObject" class="table display dataTable table-hover" style="width:100%">
            <thead>
            <tr>
                <th style="width:60px">#</th>
                <th>{{helperTrans("ingaz.title")}}</th>
                <th style="width:120px">{{helperTrans("ingaz.image")}}</th>
                <th style="width:160px">{{ __('web.fast_news_visibility') }}</th>
                <th style="width:170px">{{helperTrans("ingaz.actions")}}</th>
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function () {
            let visibleCount = {{ (int) $data['visibleCount'] }};
            let totalCount = {{ (int) $data['totalCount'] }};

            function renderStats() {
                $('#fast-news-total').text(totalCount);
                $('#fast-news-visible').text(visibleCount);
                $('#fast-news-hidden').text(totalCount - visibleCount);
            }

            // تحديث العدادات بعد كل تحميل للجدول (إضافة / حذف / تعديل)
            $('#dataTableObject').on('xhr.dt', function (e, settings, json) {
                if (!json || !Array.isArray(json.data)) return;
                totalCount = json.data.length;
                visibleCount = json.data.filter(row => Number(row.visible_flag) === 1).length;
                renderStats();
            });

            let columns = [
                {"data": 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                {"data": "title", orderable: true, searchable: true},
                {"data": "image", orderable: false, searchable: false},
                {"data": "is_visible", orderable: false, searchable: false},
                {
                    "data": "actions", orderable: false, searchable: false,
                    render: data => '<div class="fast-news-actions">' + data + '</div>'
                }
            ];
            showDataTable("{{route("fast-news.index")}}", columns);

            const labels = {
                visible: @json(__('web.fast_news_visible')),
                hidden: @json(__('web.fast_news_hidden')),
            };

            function paint(button, visible) {
                button.toggleClass('is-visible', visible)
                    .toggleClass('is-hidden', !visible)
                    .attr('aria-pressed', visible ? 'true' : 'false')
                    .find('.fast-news-toggle__label').text(visible ? labels.visible : labels.hidden);
            }

            // الظهور / الإخفاء بدون إعادة تحميل الصفحة أو الجدول
            $(document).on('click', '.fast-news-toggle', function () {
                const button = $(this);
                const wasVisible = button.hasClass('is-visible');

                paint(button, !wasVisible);
                button.addClass('is-loading');

                $.ajax({
                    url: button.data('route'),
                    type: 'PATCH',
                    data: {_token: "{{ csrf_token() }}"},
                    success: function (response) {
                        const visible = !!(response.data && response.data.is_visible);
                        paint(button, visible);
                        if (visible !== wasVisible) {
                            visibleCount += visible ? 1 : -1;
                            renderStats();
                        }
                        if (window.Swal) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: @json(__('web.fast_news_visibility_updated')),
                                showConfirmButton: false,
                                timer: 1800,
                            });
                        }
                    },
                    error: function (errorObj) {
                        paint(button, wasVisible);
                        handleErrorResponce(errorObj);
                    },
                    complete: function () {
                        button.removeClass('is-loading');
                    }
                });
            });
        });
    </script>
@endsection
