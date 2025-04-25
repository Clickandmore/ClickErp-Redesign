<div id="step-1" class="step">

    <div class="row">
        <div class="col-12" id="order-selection">
            <label for="TextInput" class="form-label">{{helperTrans("ingaz.order_code")}}</label>
            <select name="order_id" id="order_id" class="form-select form-control select2">
                <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                @foreach($endedOrders as $order)
                    <option value="{{$order->id}}">{{$order->order_code. " ".$order->user->name}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <div class="form-check fs-6 mb-3 mt-3">
                <input class="form-check-input" name="new_client" type="checkbox" value="" id="showClientSection">
                <label class="form-check-label" for="flexCheckDefault">{{helperTrans("ingaz.new_user")}}</label>
            </div>
        </div>
    </div>

    <div id="new-client-section" class="row">
        <div class="col-6">
            <label for="client_name" class="form-label">{{helperTrans("ingaz.client_name")}}</label>
            <input type="text" id="client_name" name="client_name" class="form-control"
                   data-validation="required">
        </div>

        <div class="col-6">
            <label for="client_phone" class="form-label">{{helperTrans("ingaz.client_phone")}}</label>
            <input type="tel" id="client_phone" name="client_phone" class="form-control"
                   data-validation="required">
        </div>

        <div class="col-6">
            <label for="client_nationality_id"
                   class="form-label">{{helperTrans("ingaz.nationality_id")}}</label>
            <select id="client_nationality_id" name="client_nationality_id"
                    class="form-select form-control select2">
                <option disabled selected>{{helperTrans('ingaz.choose')}}</option>
                @foreach($countries as $country)
                    <option value="{{$country->id}}">{{$country->title}}</option>
                @endforeach
            </select>
        </div>

        <div class="col-6">
            <label for="client_email" class="form-label">{{helperTrans("ingaz.email")}}</label>
            <input type="email" id="client_email" name="client_email" class="form-control" required>
        </div>

        <div class="col-6">
            <label for="client_national_num" class="form-label">{{helperTrans("ingaz.national_num")}}</label>
            <input type="text" id="client_national_num" name="client_national_num" class="form-control">
        </div>

        <div class="col-6">
            <label for="client_birthdate" class="form-label">{{helperTrans("ingaz.birthdate")}}</label>
            <input type="date" id="client_birthdate" name="client_birthdate" class="form-control">
        </div>

        <div class="col-6">
            <label for="phone2" class="form-label">{{helperTrans("ingaz.phone2")}}</label>
            <input type="tel" id="phone2" name="phone2" class="form-control">
        </div>

        <div class="col-6">
            <label for="address" class="form-label">{{helperTrans("ingaz.address")}}</label>
            <input type="text" id="address" name="address" class="form-control">
        </div>

        <div class="col-6">
            <label for="city" class="form-label">{{helperTrans("ingaz.city of receive")}}</label>
            <input type="text" id="city" name="city" class="form-control">
        </div>

        <div class="col-6">
            <label for="beds_num" class="form-label">{{helperTrans("ingaz.beds number")}}</label>
            <input type="text" id="beds_num" name="beds_num" class="form-control">
        </div>

        <div class="col-6">
            <label for="family_num" class="form-label">{{helperTrans("ingaz.family_number")}}</label>
            <input type="text" id="family_num" name="family_num" class="form-control">
        </div>

        <div class="col-6">
            <label for="children" class="form-label">{{helperTrans("ingaz.children")}}</label>
            <input type="number" id="children" max="50" name="children" class="form-control">
        </div>

        <div class="col-6">
            <label for="job" class="form-label">{{helperTrans("ingaz.job")}}</label>
            <input type="text" id="job" name="job" class="form-control">
        </div>
    </div>
</div>

