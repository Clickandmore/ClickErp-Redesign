
<div class="modal fade" data-bs-backdrop="static" id="createModal" tabindex="-1" aria-labelledby="exampleModalLgLabel" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-scrollable {{(isset($modalType))? $modalType:"modal-xl"}}">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title h4" id="exampleModalLgLabel"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body custom_scroll"></div>
            {{--<div class="modal-footer">
               --}}{{-- <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__("buttons.close")}}</button>
                <button type="button" class="btn btn-primary">{{__("buttons.save")}}</button>--}}{{--
            </div>--}}
        </div>
    </div>
</div>
