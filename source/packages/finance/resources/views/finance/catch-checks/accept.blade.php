<form class="row g-3 from-submit-update-global" method="post" action="{{$route}}" enctype="multipart/form-data">
    @method("POST")
    <input type="hidden" name="id" value="{{$obj->id}}">
    <div class="col-12 text-center">
        <p class="fw-bold">{{ __('finance::finance.sure to mark collect') }} </p>
        <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
        <dotlottie-player
            src="{{asset('/default/money.json')}}"
            background="transparent" speed="1" style="width: 180px; height: 180px;margin: auto" loop
            autoplay></dotlottie-player>
    </div>
    <div class="col-12 modal-footer">
         {!! submitButton( $route ) !!}
        {!! closeButton() !!}
    </div>
</form>

