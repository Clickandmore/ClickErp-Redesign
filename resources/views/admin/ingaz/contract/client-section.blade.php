<div class="row">
    <div id="new-client-section" class="row g-3">
        <div class="col-md-6">
            <label for="client_name" class="form-label">{{helperTrans("ingaz.client_name")}} <span class="text-danger">*</span></label>
            <input type="text" id="client_name" name="client_name" class="form-control"
                   data-validation="required">
        </div>

        <div class="col-md-6">
            <label for="client_phone" class="form-label">{{helperTrans("ingaz.client_phone")}} <span class="text-danger">*</span></label>
            <input type="tel" id="client_phone" name="client_phone" class="form-control"
                   data-validation="required">
        </div>

        <div class="col-md-6">
            <label for="client_nationality_id"
                   class="form-label">{{helperTrans("ingaz.nationality_id")}} <span class="text-danger">*</span></label>
            <select id="client_nationality_id" name="client_nationality_id"
                    class="form-select form-control select2" data-validation="required">
                <option value="">{{helperTrans('ingaz.choose')}}</option>
                @foreach($countries as $country)
                    <option value="{{$country->id}}">{{$country->title}}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label for="client_email" class="form-label">{{helperTrans("ingaz.email")}} <small class="text-muted">(اختياري)</small></label>
            <input type="email" id="client_email" name="client_email" class="form-control">
        </div>

        <div class="col-md-6">
            <label for="client_national_num" class="form-label">{{helperTrans("ingaz.national_num")}} <span class="text-danger">*</span></label>
            <input type="text" id="client_national_num" name="client_national_num" class="form-control" data-validation="required">
        </div>

        <div class="col-md-6">
            <label for="client_birthdate" class="form-label">{{helperTrans("ingaz.birthdate")}} <span class="text-danger">*</span></label>
            <input type="date" id="client_birthdate" name="client_birthdate" class="form-control" data-validation="required">
        </div>

        <div class="col-md-6">
            <label for="phone2" class="form-label">{{helperTrans("ingaz.phone2")}} <span class="text-danger">*</span></label>
            <input type="tel" id="phone2" name="phone2" class="form-control" data-validation="required">
        </div>

        <div class="col-md-6">
            <label for="address" class="form-label">{{helperTrans("ingaz.address")}} <span class="text-danger">*</span></label>
            <input type="text" id="address" name="address" class="form-control" data-validation="required">
        </div>

        <div class="col-md-6">
            <label for="city" class="form-label">{{helperTrans("ingaz.city of receive")}} <span class="text-danger">*</span></label>
            <input type="text" id="city" name="city" class="form-control" data-validation="required">
        </div>

        <div class="col-md-6">
            <label for="beds_num" class="form-label">{{helperTrans("ingaz.beds number")}} <small class="text-muted">(اختياري)</small></label>
            <input type="text" id="beds_num" name="beds_num" class="form-control">
        </div>

        <div class="col-md-6">
            <label for="family_num" class="form-label">{{helperTrans("ingaz.family_number")}} <small class="text-muted">(اختياري)</small></label>
            <input type="text" id="family_num" name="family_num" class="form-control">
        </div>

        <div class="col-md-6">
            <label for="children" class="form-label">{{helperTrans("ingaz.children")}} <small class="text-muted">(اختياري - تحت سن 12)</small></label>
            <input type="number" id="children" max="50" name="children" class="form-control">
        </div>

        <div class="col-md-6">
            <label for="job" class="form-label">{{helperTrans("ingaz.job")}} <small class="text-muted">(اختياري)</small></label>
            <input type="text" id="job" name="job" class="form-control">
        </div>
    </div>
</div>

