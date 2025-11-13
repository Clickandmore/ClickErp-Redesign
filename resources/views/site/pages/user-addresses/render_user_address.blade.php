@foreach ($addresses as $address)
    <div class="col-6">
        <label for="l1{{ $address->id }}" class="location-card w-100"
               style="position: relative; display: block;">
            <div class="location" id="address-{{ $address->id }}"
                 style="position: relative; border: 1px solid #ddd; border-radius: 10px; padding: 15px; transition: 0.3s;">
                <h6>{{ $address->title }}</h6>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <li>
                        <i class="fa-light fa-user"></i> {{ $address->name }}
                    </li>
                    <li>
                        <i class="fa-light fa-phone"></i> {{ $address->phone }}
                    </li>
                    <li>
                        <i class="fa-light fa-location-dot"></i> {{ $address->address }}
                    </li>
                    <ul>
                        <li>
                            <i class="fa-light fa-user"></i> {{ $address->name }}
                        </li>
                        <li>
                            <i class="fa-light fa-phone"></i> {{ $address->phone }}
                        </li>
                        <li>
                            <i class="fa-light fa-location-dot"></i> {{ $address->address }}
                        </li>
                        <li>
                            <i class="fa-regular fa-note-sticky"></i> {{ $address->notes }}
                        </li>
                    </ul>
                    <!-- 🗑️ حذف-->
                    <button type="button" class="delete-btn"
                            data-id="{{ $address->id }}"
                            title="{{ __('web.delete_address') }}">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 24 24" width="18" height="18"
                             fill="red">
                            <path
                                d="M9 3v1H4v2h16V4h-5V3H9zm1 5v9h2V8h-2zm4 0v9h2V8h-2zM7 8v9h2V8H7z"/>
                        </svg>
                    </button>
                    <!-- ✏️ تعديل -->
                    <button type="button" class="edit-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#addLocation"
                            model-route="{{ route('site.user-addresses.edit', $address->id) }}">
                        <svg xmlns="http://www.w3.org/2000/svg"
                             viewBox="0 0 24 24" width="18" height="18"
                             fill="#007bff">
                            <path
                                d="M3 17.25V21h3.75l11-11.03-3.75-3.75L3 17.25zm14.71-10.04a1.003 1.003 0 0 0 0-1.42l-1.5-1.5a1.003 1.003 0 0 0-1.42 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>
                        </svg>
                    </button>
                </ul>
            </div>
            <input type="radio"
                   name="address_id"
                   value="{{ $address->id }}"
                   id="l1{{ $address->id }}"
                   data-address="{{ $address->id }}">
        </label>
    </div>
@endforeach
