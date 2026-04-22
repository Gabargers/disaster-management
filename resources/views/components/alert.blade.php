@php
    $notices = [];

    if (session('success')) {
        $notices[] = [
            'type' => 'success',
            'title' => 'Success',
            'text' => session('success'),
        ];
    }

    if (session('failed')) {
        $notices[] = [
            'type' => 'danger',
            'title' => 'Error',
            'text' => session('failed'),
        ];
    }

    if ($errors->any()) {
        foreach (array_unique($errors->all()) as $err) {
            $notices[] = [
                'type' => 'danger',
                'title' => 'Validation Error',
                'text' => $err,
            ];
        }
    }
@endphp

@if (!empty($notices))
    <div class="position-fixed start-50 translate-middle-x w-100" style="top: 1rem; z-index: 1055; max-width: 480px;">
        @foreach ($notices as $i => $n)
            <div class="alert alert-dismissible d-flex align-items-start gap-4 p-5 mb-3 shadow
                        {{ $n['type'] === 'success' ? 'bg-light-success border border-success' : 'bg-light-danger border border-danger' }}"
                role="alert" style="margin-top: {{ $i ? $i * 0.5 . 'rem' : '0' }}">

                @if ($n['type'] === 'success')
                    <i class="ki-duotone ki-check-circle fs-2hx text-success"></i>
                @else
                    <i class="ki-duotone ki-information-2 fs-2hx text-danger"></i>
                @endif

                <div class="d-flex flex-column flex-grow-1">
                    <h4 class="fw-bold mb-1 {{ $n['type'] === 'success' ? 'text-success' : 'text-danger' }}">
                        {{ $n['title'] }}
                    </h4>
                    <div class="text-gray-800 fw-semibold">{!! nl2br(e($n['text'])) !!}</div>
                </div>

                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endforeach
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(el) {
                const bsAlert = bootstrap.Alert.getOrCreateInstance(el);
                setTimeout(function() {
                    bsAlert.close();
                }, 10000);
            });
        });
    </script>
@endif
