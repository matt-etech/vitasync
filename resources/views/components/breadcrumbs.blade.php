<nav class="breadcrumb-shell" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        @foreach ($items as $item)
            @if (! empty($item['url']) && ! $loop->last)
                <li class="breadcrumb-item">
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                </li>
            @else
                <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
            @endif
        @endforeach
    </ol>
</nav>
