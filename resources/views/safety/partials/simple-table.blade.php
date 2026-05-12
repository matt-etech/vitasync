<div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
            <tr>
                @foreach ($columns as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columns as $field => $label)
                        @php
                            $value = match ($field) {
                                'client' => $row->client?->fullName(),
                                'medication' => $row->medication?->name,
                                default => data_get($row, $field),
                            };
                            if ($value instanceof \DateTimeInterface) {
                                $value = $value->format('d/m/Y H:i');
                            }
                        @endphp
                        <td>
                            @if (in_array($field, ['status', 'risk_level', 'severity', 'outcome', 'decision', 'capacity_outcome'], true))
                                <span class="badge text-bg-secondary">{{ $value ?: 'Not set' }}</span>
                            @else
                                {{ filled($value) ? $value : 'Not recorded' }}
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) }}" class="text-secondary">No records yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
