@props([
    'title' => '',
    'status' => '',
    'expected' => null,
    'borderClass' => 'border-primary',
    'headerBg' => 'bg-light',
    'headerBorder' => '',
    'headerTextColor' => '',
    'footerBg' => '',
])

@php
    $statusMap = [
        'completed'   => ['bg' => 'bg-success', 'text' => ''],
        'in progress' => ['bg' => 'bg-primary', 'text' => 'text-dark'],
        'planned'     => ['bg' => 'bg-warning', 'text' => 'text-dark'],
        'exploring'   => ['bg' => 'bg-info', 'text' => 'text-dark'],
    ];
    $variant = $statusMap[strtolower($status)] ?? $statusMap['in progress'];
@endphp

<div class="card g-col-12 g-col-lg-6 {{ $borderClass }}" {{ $attributes }}>
    <div class="card-header {{ $headerBg }} {{ $headerBorder }} {{ $headerTextColor }} d-flex justify-content-between align-items-center">
        <h4 class="mb-0">{{ $title }}</h4>
        <span class="badge {{ $variant['bg'] }} {{ $variant['text'] }} fs-6 p-2">{{ $status }}</span>
    </div>
    <div class="card-body">
        {{ $slot }}
    </div>
    @if ($expected)
        <div class="card-footer {{ $footerBg }}">
            <small class="text-muted">Expected: {{ $expected }}</small>
        </div>
    @endif
</div>
