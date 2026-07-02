@if ($errors->any())
    <div class="alert alert-danger">
        <p class="fw-semibold mb-2">Please correct the fields marked below.</p>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
