@php
    $statusMap = [
        'TCISS_VERIFIED' => 'primary',
        'DAFAC_INTAKE_COMPLETED' => 'info',
        'DUPLICATE_CHECKED' => 'warning',
        'VALIDATED' => 'success',
        'PAYROLL_READY' => 'success',
        'PAYOUT_SCHEDULED' => 'primary',
        'ASSISTANCE_RELEASED' => 'success',
        'REQUIREMENTS_COMPLETED' => 'dark',
        'NEEDS_REVIEW' => 'warning',
        'NEEDS_CORRECTION' => 'warning',
        'DUPLICATE' => 'danger',
        'REJECTED' => 'danger',
    ];

    $variant = $statusMap[$status ?? 'NEEDS_REVIEW'] ?? 'secondary';
    $label = str_replace('_', ' ', $status ?? 'NEEDS_REVIEW');
@endphp

<span class="badge badge-light-{{ $variant }} fw-bold">{{ $label }}</span>
