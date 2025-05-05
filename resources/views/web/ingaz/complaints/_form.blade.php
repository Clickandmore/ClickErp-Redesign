@php
    $complaintTypes = \App\Models\ingaz\ComplaintType::all();
@endphp

<form class="form-wrap from-submit-global" action="{{route('web.complaints.store')}}" method="post" id="complaintForm">
    @csrf
    <div class="row">
        <div class="col-12 p-2">
            <div class="form-group">
                <label for="complaint_type_id">
                    <i class="fa-light fa-tag"></i>
                    {{helperTrans('web.complaint_type')}}
                </label>
                <select name="complaint_type_id" id="complaint_type_id" class="form-control" required>
                    <option value="">{{helperTrans('web.select_complaint_type')}}</option>
                    @foreach($complaintTypes as $type)
                        <option value="{{$type->id}}">{{$type->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-12 p-2">
            <div class="form-group">
                <label for="locationName">
                    <i class="fa-light fa-clipboard"></i>
                    {{helperTrans('web.complaint_title')}}
                </label>
                <input id="address" name="address" type="text" placeholder="{{helperTrans('web.house_example')}}">
            </div>
        </div>
        <div class="col-lg-12 p-2">
            <div class="form-group">
                <label for="details">
                    <i class="fa-regular fa-comment-dots"></i>
                    {{helperTrans('web.complaint_details')}}
                </label>
                <textarea name="message" id="message" placeholder="{{helperTrans('web.complaint_details')}} "></textarea>
            </div>
        </div>
        <div class="col-lg-12 p-2">
            <div class="form-group mb-0">
                <button type="submit">
                    {{helperTrans('web.save')}}
                </button>
            </div>
        </div>
    </div>
</form>
