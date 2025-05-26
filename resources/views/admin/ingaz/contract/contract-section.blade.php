<div class="row">
    <div id="contract-section" class="row">
        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.contract date")}} <span class="text-danger">*</span></label>
            <input type="date" id="contract_date" name="contract_date" class="form-control"
                   data-validation="required">
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.msaned number")}} <span class="text-danger">*</span></label>
            <input type="text" id="msaned_number" name="msaned_number" class="form-control"
                   data-validation="required">
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.tafwed number")}} <small class="text-muted">(اختياري)</small></label>
            <input type="text" id="tafwed_number" name="tafwed_number" class="form-control">
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.visa number")}} <span class="text-danger">*</span></label>
            <input type="text" id="visa_number" name="visa_number" class="form-control"
                   data-validation="required">
        </div>
        {{-- <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.visa hijry date")}}</label>
            <input type="date" id="visa_milad_date" name="visa_milad_date" class="form-control"
                   data-validation="required">
        </div> --}}
        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.visa_date_is")}} <small class="text-muted">(اختياري)</small></label>
            <input type="date" id="visa_milad_date" name="visa_milad_date" class="form-control">
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.visa_hijry_date")}} <small class="text-muted">(اختياري)</small></label>
            <input type="date" id="visa_hijry_date" name="visa_hijry_date" class="form-control">
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.contract duration")}} <span class="text-danger">*</span></label>
            <input type="number" id="contract_duration" name="contract_duration" class="form-control"
                   value="2" readonly>
            <small class="text-muted">(مدة التعاقد ثابتة: سنتين)</small>
        </div>
    </div>
</div>
