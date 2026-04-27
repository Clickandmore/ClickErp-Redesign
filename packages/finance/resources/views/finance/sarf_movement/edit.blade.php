@extends('click.layout.indexs.index')
@csrf
    @method('PUT')
    <div class="alert alert-info text-center">
        {{__("finance::finance.to control of amounts go to sarf or add")}}
    </div>
    <div class="d-flex mb-3 justify-content-center">
        {!! addHyperButton(route('finance.stock-movement.create') , __("finance::finance.Additional Permit")) !!}
        {!! addHyperButton(route('finance.sarf-movement.create') , __("finance::finance.sarf Permit")) !!}
    </div>

    <div class="col-12 modal-footer">
        {!! closeButton() !!}
    </div>
</form>
