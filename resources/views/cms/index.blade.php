@extends('layouts.dashboard.main')

@section('content')
    @include('components.alert')

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title fw-bold">{{ $cms['page_title'] }}</h3>
            @if ($cms['resource'] != 'pet')
                @if ($cms['resource'] != 'team' && $cms['resource'] != 'violation')
                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#cmsCreateModal">
                        <i class="fas fa-plus me-1"></i> Add {{ ucfirst($cms['resource']) }}
                    </button>
                @else
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cmsCreateStepperModal">
                        <i class="fas fa-plus me-1"></i> Add {{ ucfirst($cms['resource']) }}
                    </button>
                @endif
            @endif
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table id="cms-table" class="table align-middle table-row-dashed fs-6 gy-5 text-center" style="width:100%">
                    <thead>
                        <tr class="text-start text-gray-700 fw-bold fs-7 text-uppercase gs-0 bg-danger">
                            @foreach ($cms['datatableColumns'] as $col)
                                <th class="text-center text-white">
                                    {{ $col['title'] ?? $col['data'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('modals')
    @if ($cms['resource'] != 'pet')
        @include('cms.partials.modals', ['cms' => $cms])
    @endif
@endpush

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/datatable.css') }}">
@endpush

@push('scripts')
    <script>
        $(function() {
            $('#cms-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: @json($cms['routes']['data']),
                pageLength: 10,
                searchDelay: 400,
                deferRender: true,
                responsive: true,
                order: [
                    [0, 'asc']
                ],
                columns: @json($cms['datatableColumns']),

                dom: "<'row mb-5'<'col-sm-12 col-md-6 d-flex align-items-center gap-3'l>" +
                    "<'col-sm-12 col-md-6 d-flex justify-content-end'f>>" +
                    "<'table-responsive'tr>" +
                    "<'row mt-5'<'col-sm-12 col-md-5 d-flex align-items-center'i>" +
                    "<'col-sm-12 col-md-7 d-flex justify-content-end'p>>",

                language: {
                    lengthMenu: "_MENU_",
                    search: "",
                    searchPlaceholder: "Search {{ ucfirst($cms['resource']) }}...",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                },

                pagingType: "simple_numbers",
                drawCallback: function() {
                    const $wrap = $('#cms-table').closest('.dataTables_wrapper');

                    $wrap.find('.dataTables_paginate ul.pagination li.page-item').each(function() {
                        const $li = $(this);
                        const $a = $li.find('a.page-link, span.page-link');

                        $a.removeClass('btn btn-sm btn-danger btn-light-danger mx-1 active disabled')
                            .addClass('btn btn-sm btn-light-danger mx-1');

                        if ($li.hasClass('active')) {
                            $a.removeClass('btn-light-danger').addClass('btn-danger active');
                        }

                        if ($li.hasClass('disabled')) {
                            $a.addClass('disabled');
                        }
                    });
                },
            });
        });

        window.CMS = @js($cms);
    </script>

    <script src="{{ asset('assets/js/cms/cms-crud.js') }}"></script>
    <script src="{{ asset('assets/js/cms/cms-stepper-crud.js') }}"></script>
    <script src="{{ asset('assets/js/cms/tool-tip.js') }}"></script>
@endpush
