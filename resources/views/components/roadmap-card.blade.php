@props([
    'title' => '',
    'status' => '',
    'statusVariant' => 'primary',
    'expected' => null,
    'borderClass' => 'border-primary',
    'headerBg' => 'bg-light',
    'headerBorder' => '',
    'headerTextColor' => '',
    'footerBg' => '',
])

@php
    $statusVariants = [
        'success' => ['bg' => 'bg-success', 'text' => ''],
        'primary' => ['bg' => 'bg-primary', 'text' => 'text-dark'],
        'warning' => ['bg' => 'bg-warning', 'text' => 'text-dark'],
        'info' => ['bg' => 'bg-info', 'text' => 'text-dark'],
    ];
    $variant = $statusVariants[$statusVariant] ?? $statusVariants['primary'];
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
