@extends('layouts.app')

@section('title', __('messages.import_preview'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.import_preview') }}</h1>
            <p>{{ __('messages.import_help') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('students.import') }}">{{ __('messages.back') }}</a>
    </div>

    <div class="dashboard-card">
        <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge bg-success">{{ __('messages.valid_rows') }}: {{ $validCount }}</span>
            <span class="badge bg-danger">{{ __('messages.invalid_rows') }}: {{ $invalidCount }}</span>
        </div>

        <div class="table-responsive">
            <table class="table align-middle data-table">
                <thead><tr><th>{{ __('messages.row') }}</th><th>{{ __('messages.student_name') }}</th><th>{{ __('messages.class') }}</th><th>{{ __('messages.guardian_phone') }}</th><th>{{ __('messages.status') }}</th><th>{{ __('messages.errors') }}</th></tr></thead>
                <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td data-label="{{ __('messages.row') }}">{{ $row['row'] }}</td>
                        <td data-label="{{ __('messages.student_name') }}">{{ $row['data']['first_name'] }} {{ $row['data']['last_name'] }}</td>
                        <td data-label="{{ __('messages.class') }}">{{ $row['class_name'] }}</td>
                        <td data-label="{{ __('messages.guardian_phone') }}">{{ $row['data']['guardian_phone'] }}</td>
                        <td data-label="{{ __('messages.status') }}">{{ __('messages.'.$row['data']['status']) }}</td>
                        <td data-label="{{ __('messages.errors') }}">
                            @if(count($row['errors']) > 0)
                                <ul class="mb-0 ps-3 text-danger">
                                    @foreach($row['errors'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="text-success">OK</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('students.import.store') }}" class="mt-3">
            @csrf
            @if($invalidCount > 0)
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="skip_bad_rows" id="skip_bad_rows" value="1">
                    <label class="form-check-label" for="skip_bad_rows">{{ __('messages.skip_bad_rows') }}</label>
                </div>
            @endif
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('messages.confirm_import') }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('students.import') }}">{{ __('messages.cancel') }}</a>
        </form>
    </div>
@endsection
