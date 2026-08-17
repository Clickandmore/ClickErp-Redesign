<div class="row">
    <div id="new-maid-section" class="row mt-4">
        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.maid_name")}}</label>
            <input type="text" id="maid_name" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.maid_passport")}}</label>
            <input type="text" id="maid_passport" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.religion")}}</label>
            <input type="text" id="maid_religion" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.maid_job")}}</label>
            <input type="text" id="maid_job" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.nationality")}}</label>
            <input type="text" id="maid_nationality" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.salary")}}</label>
            <input type="text" id="maid_salary" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.contract duration")}}</label>
            <input type="number" id="contract_duration" name="contract_duration" class="form-control" value="2" readonly>
            <small class="text-muted">(مدة التعاقد ثابتة: سنتين)</small>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.experience_years")}}</label>
            <input type="text" id="experience_years" class="form-control" disabled>
        </div>

        <div class="col-6">
            <label class="form-label">{{helperTrans("ingaz.age")}}</label>
            <input type="text" id="age" class="form-control" disabled>
        </div>
    </div>
</div>

<style>
.select2-result-maid {
    padding: 6px;
}
.select2-result-maid__name {
    font-weight: bold;
    margin-bottom: 4px;
}
.select2-result-maid__details {
    font-size: 0.9em;
    color: #666;
}
</style>
