@if (session('error'))
    <div class="alert alert-danger mt-5">
        {{ session('error') }}
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success mt-5">
        {{ session('success') }}
    </div>
@endif

@if (session('status'))
    <div class="alert alert-warning mt-5">
        {{ session('status') }}
    </div>
@endif
