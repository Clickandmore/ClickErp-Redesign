@extends('admin.settings.sections.layout')

@section('settings-title', __('admin.settings_section_general'))

@section('settings-content')
    @php
        $storedQuickLinks = $settings->admin_quick_links ?? [];
        if (empty($storedQuickLinks)) {
            $storedQuickLinks = [
                [
                    'title' => $settings->header_link_1_title ?? __('admin.settings_default_musaned_pro'),
                    'url' => $settings->header_link_1_url ?? 'https://pros.musaned.com.sa/login',
                    'icon' => 'fa-external-link',
                    'is_visible' => true,
                ],
                [
                    'title' => $settings->header_link_2_title ?? __('admin.settings_default_musaned_tawtheeq'),
                    'url' => $settings->header_link_2_url ?? 'https://tawtheeq.musaned.com.sa/',
                    'icon' => 'fa-file-text-o',
                    'is_visible' => true,
                ],
                [
                    'title' => $settings->live_chat_title ?? __('admin.settings_default_live_chat'),
                    'url' => $settings->live_chat_url ?? 'https://salesiq.zoho.sa/platinumeastern/liveview',
                    'icon' => 'fa-comments',
                    'is_visible' => true,
                ],
                [
                    'title' => __('admin.settings_default_enjaz'),
                    'url' => 'https://visa.mofa.gov.sa/enjaz/getvisainformation/person',
                    'icon' => 'fa-id-card-o',
                    'is_visible' => true,
                ],
                [
                    'title' => __('admin.settings_default_tasheer'),
                    'url' => 'https://ksavisa.sa/',
                    'icon' => 'fa-plane',
                    'is_visible' => true,
                ],
            ];
        }
    @endphp

    <form class="row g-3 from-submit-global" method="post"
          action="{{ route('settings.update', $settings->id) }}"
          enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="admin_quick_links_empty" value="1">

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_site_data') }}</h6>
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_website_name') }} {{ getFieldLanguage($locale) }}</label>
                        <fieldset class="form-icon-group right-icon position-relative">
                            <div class="form-icon position-absolute">
                                <i class="fa fa-globe"></i>
                            </div>
                            <input type="text" name="website_name:{{ $locale }}"
                                   value="{{ $settings->translate($locale)->website_name ?? '' }}" class="form-control">
                        </fieldset>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <div class="row g-3">
                @foreach (config('translatable.locales') as $locale)
                    <div class="col-md-6">
                        <label class="form-label">{{ __('admin.settings_address') }} {{ getFieldLanguage($locale) }}</label>
                        <fieldset class="form-icon-group right-icon position-relative">
                            <div class="form-icon position-absolute">
                                <i class="fa fa-map-marker"></i>
                            </div>
                            <input type="text" name="location:{{ $locale }}"
                                   value="{{ $settings->translate($locale)->location ?? '' }}" class="form-control">
                        </fieldset>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <div class="alert alert-info mb-0">
                {{ __('admin.settings_forms_moved_notice_before') }}
                <a href="{{ route('form-settings.index') }}" class="alert-link">{{ __('admin.settings_forms_settings') }}</a>
                {{ __('admin.settings_forms_moved_notice_after') }}
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_contact_data') }}</h6>
            <div class="row g-3">
                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_email') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-envelope"></i>
                        </div>
                        <input type="email" name="email" class="form-control"
                               value="{{ $settings->email ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_phone') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-mobile"></i>
                        </div>
                        <input type="text" name="phone" class="form-control" value="{{ $settings->phone ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_other_phone') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-phone"></i>
                        </div>
                        <input type="text" name="other_phone" class="form-control"
                               value="{{ $settings->other_phone ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-md-6 col-xl-4">
                    <label class="form-label">{{ __('admin.settings_tax_number') }}</label>
                    <fieldset class="form-icon-group right-icon position-relative">
                        <div class="form-icon position-absolute">
                            <i class="fa fa-hashtag"></i>
                        </div>
                        <input type="text" name="tax_number" class="form-control"
                               value="{{ $settings->tax_number ?? '' }}">
                    </fieldset>
                </div>

                <div class="col-12">
                    @include('admin.settings.partials.map-link-field', ['settings' => $settings])
                </div>
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_quick_header_links') }}</h6>
            <p class="text-muted small mb-3">{{ __('admin.settings_quick_header_links_table_hint') }}</p>
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-2" id="adminQuickLinksTable">
                    <thead>
                    <tr>
                        <th style="width: 24%;">{{ __('admin.settings_quick_link_title') }}</th>
                        <th>{{ __('admin.settings_quick_link_url') }}</th>
                        <th style="width: 18%;">{{ __('admin.settings_quick_link_icon') }}</th>
                        <th style="width: 110px;">{{ __('admin.settings_quick_link_visible') }}</th>
                        <th style="width: 80px;"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($storedQuickLinks as $index => $link)
                        <tr class="admin-quick-link-row">
                            <td>
                                <input type="text" name="admin_quick_links[{{ $index }}][title]" class="form-control"
                                       value="{{ old("admin_quick_links.$index.title", $link['title'] ?? '') }}"
                                       placeholder="{{ __('admin.settings_example_enjaz') }}">
                            </td>
                            <td>
                                <input type="url" name="admin_quick_links[{{ $index }}][url]" class="form-control"
                                       value="{{ old("admin_quick_links.$index.url", $link['url'] ?? '') }}"
                                       placeholder="https://example.com">
                            </td>
                            <td>
                                <select name="admin_quick_links[{{ $index }}][icon]" class="form-select">
                                    @foreach (['fa-external-link' => __('admin.settings_icon_external_link'), 'fa-file-text-o' => __('admin.settings_icon_file'), 'fa-comments' => __('admin.settings_icon_chat'), 'fa-id-card-o' => __('admin.settings_icon_identity'), 'fa-plane' => __('admin.settings_icon_visa'), 'fa-globe' => __('admin.settings_icon_website')] as $icon => $label)
                                        <option value="{{ $icon }}" {{ old("admin_quick_links.$index.icon", $link['icon'] ?? 'fa-external-link') === $icon ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="hidden" name="admin_quick_links[{{ $index }}][is_visible]" value="0">
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="admin_quick_links[{{ $index }}][is_visible]"
                                           class="form-check-input" value="1"
                                           {{ old("admin_quick_links.$index.is_visible", $link['is_visible'] ?? true) ? 'checked' : '' }}>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger remove-admin-quick-link">
                                    {{ __('admin.settings_delete') }}
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addAdminQuickLink">
                {{ __('admin.settings_add_shortcut') }}
            </button>
            <template id="adminQuickLinkTemplate">
                <tr class="admin-quick-link-row">
                    <td>
                        <input type="text" data-name="title" class="form-control" placeholder="{{ __('admin.settings_example_tasheer') }}">
                    </td>
                    <td>
                        <input type="url" data-name="url" class="form-control" placeholder="https://example.com">
                    </td>
                    <td>
                        <select data-name="icon" class="form-select">
                            <option value="fa-external-link">{{ __('admin.settings_icon_external_link') }}</option>
                            <option value="fa-file-text-o">{{ __('admin.settings_icon_file') }}</option>
                            <option value="fa-comments">{{ __('admin.settings_icon_chat') }}</option>
                            <option value="fa-id-card-o">{{ __('admin.settings_icon_identity') }}</option>
                            <option value="fa-plane">{{ __('admin.settings_icon_visa') }}</option>
                            <option value="fa-globe">{{ __('admin.settings_icon_website') }}</option>
                        </select>
                    </td>
                    <td>
                        <input type="hidden" data-name="is_visible_hidden" value="0">
                        <div class="form-check form-switch">
                            <input type="checkbox" data-name="is_visible" class="form-check-input" value="1" checked>
                        </div>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-danger remove-admin-quick-link">{{ __('admin.settings_delete') }}</button>
                    </td>
                </tr>
            </template>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const tableBody = document.querySelector('#adminQuickLinksTable tbody');
                    const addButton = document.getElementById('addAdminQuickLink');
                    const template = document.getElementById('adminQuickLinkTemplate');

                    function reindexRows() {
                        tableBody.querySelectorAll('.admin-quick-link-row').forEach(function (row, index) {
                            row.querySelectorAll('[data-name], [name^="admin_quick_links"]').forEach(function (field) {
                                const fieldName = field.dataset.name || (field.name.match(/\]\[(.*?)\]/) || [])[1];
                                if (!fieldName) {
                                    return;
                                }
                                field.name = fieldName === 'is_visible_hidden'
                                    ? `admin_quick_links[${index}][is_visible]`
                                    : `admin_quick_links[${index}][${fieldName}]`;
                            });
                        });
                    }

                    addButton.addEventListener('click', function () {
                        tableBody.appendChild(template.content.cloneNode(true));
                        reindexRows();
                    });

                    tableBody.addEventListener('click', function (event) {
                        if (!event.target.classList.contains('remove-admin-quick-link')) {
                            return;
                        }
                        event.target.closest('tr').remove();
                        reindexRows();
                    });
                });
            </script>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_additional_data') }}</h6>
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label class="form-label">{{ __('admin.settings_years_experience') }}</label>
                    <input type="number" name="years" class="form-control" value="{{ $settings->years ?? '' }}">
                </div>

                <div class="col-md-6">
                    <label class="form-label d-block">{{ __('admin.settings_enable_customer_service_booking') }}</label>
                    <input type="hidden" name="show_customer_service_during_booking" value="0">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            name="show_customer_service_during_booking"
                            id="show_customer_service_during_booking"
                            class="form-check-input"
                            value="1"
                            {{ !empty($settings->show_customer_service_during_booking) ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_customer_service_during_booking">
                            {{ !empty($settings->show_customer_service_during_booking) ? __('admin.settings_enabled') : __('admin.settings_disabled') }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <hr>
        </div>

        <div class="col-12">
            <h6 class="text-muted mb-2">{{ __('admin.settings_public_site') }}</h6>
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label class="form-label d-block">{{ __('admin.settings_show_public_site_to_customer') }}</label>
                    <input type="hidden" name="show_public_site" value="0">
                    <div class="form-check form-switch">
                        <input
                            type="checkbox"
                            name="show_public_site"
                            id="show_public_site"
                            class="form-check-input"
                            value="1"
                            {{ (int)($settings->show_public_site ?? 1) === 1 ? 'checked' : '' }}>
                        <label class="form-check-label" for="show_public_site">
                            {{ (int)($settings->show_public_site ?? 1) === 1 ? __('admin.settings_enabled') : __('admin.settings_disabled') }}
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">{{ __('admin.settings_public_site_disabled_hint') }}</small>
                </div>
            </div>
        </div>

        <div class="col-12 text-end">
            @if (@AdminGuardHasPerm(\App\PermKeys::SETTINGS_CONTROL))
                <button class="btn btn-primary" type="submit">{{ __('buttons.save') }}</button>
            @endif
        </div>
    </form>
@endsection
