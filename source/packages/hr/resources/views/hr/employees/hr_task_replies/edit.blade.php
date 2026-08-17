<form class="row g-3 from-submit-global" method="post" action="{{ $updateRoute }}"
      enctype="multipart/form-data">
    @method('PUT')


    <div class="col-12">
        <label for="TextInput" class="form-label">{{ __("crm.status") }}</label>
        <select class="form-control show-tick ms select2" id="status" name="status" data-validation="required">
            {!! getEnumData(\Nami\Crm\Database\Seeders\Enums\TaskStatus::cases() ) !!}
        </select>
    </div>
    <div class="col-12 modal-footer">
        {!! submitButton($updateRoute) !!}
        {!! closeButton() !!}
    </div>

</form>
