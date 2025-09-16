@extends('admin.layout.indexs.index')
@section('page-title')
    {{__('countries.add_new_country')}}
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">{{__('countries.add_new_country')}}</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('countries.store') }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name_ar">{{__('countries.name_ar')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name_ar') is-invalid @enderror"
                                   id="name_ar" name="name_ar" value="{{ old('name_ar') }}" required>
                            @error('name_ar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="name_en">{{__('countries.name_en')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name_en') is-invalid @enderror"
                                   id="name_en" name="name_en" value="{{ old('name_en') }}" required>
                            @error('name_en')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                             <div class="col-md-6">
                        <div class="form-group">
                            <label for="description_ar">{{__('countries.description_ar')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('description_ar') is-invalid @enderror"
                                   id="description_ar" name="description_ar" value="{{ old('description_ar') }}" required>
                            @error('description_ar')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="description_en">{{__('countries.description_en')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('description_en') is-invalid @enderror"
                                   id="description_en" name="description_en" value="{{ old('description_en') }}" required>
                            @error('description_en')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="code">{{__('countries.code')}} <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror"
                                   id="code" name="code" value="{{ old('code') }}" maxlength="2" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone_code">{{__('countries.phone_code')}}</label>
                            <input type="text" class="form-control @error('phone_code') is-invalid @enderror"
                                   id="phone_code" name="phone_code" value="{{ old('phone_code') }}">
                            @error('phone_code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="currency">{{__('countries.currency')}}</label>
                            <input type="text" class="form-control @error('currency') is-invalid @enderror"
                                   id="currency" name="currency" value="{{ old('currency') }}" maxlength="3">
                            @error('currency')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="currency_symbol">{{__('countries.currency_symbol')}}</label>
                            <input type="text" class="form-control @error('currency_symbol') is-invalid @enderror"
                                   id="currency_symbol" name="currency_symbol" value="{{ old('currency_symbol') }}">
                            @error('currency_symbol')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="sort_order">{{__('countries.sort_order')}}</label>
                            <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                   id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}">
                            @error('sort_order')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="custom-control custom-switch mt-4">
                                <input type="checkbox" class="custom-control-input" id="is_active"
                                       name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_active">{{__('countries.is_active')}}</label>
                            </div>
                        </div>
                    </div>
                         <div class="mb-3 row">
                    <label class="col-sm-3 col-form-label">{{ __('ingaz.image') }}</label>
                    <div class="col-sm-5">
                        <input type="file" name="image" class="form-control" accept="image/*">
                       
                    </div>
                </div>
                </div>
                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">{{__('buttons.save')}}</button>
                    <a href="{{ route('countries.index') }}" class="btn btn-secondary">{{__('buttons.cancel')}}</a>
                </div>
            </form>
        </div>
    </div>
@endsection
