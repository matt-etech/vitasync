<div class="mb-4 d-flex flex-column flex-md-row gap-3 align-items-md-end justify-content-md-between">
    <div>
        <h1 class="h2 fw-semibold mb-0">{{ $title }}</h1>
        @isset($description)
            <p class="mt-2 mb-0 text-secondary">{{ $description }}</p>
        @endisset
    </div>
    @isset($action)
        <div>{{ $action }}</div>
    @endisset
</div>
