@extends('layouts.app')

@section('title', __('messages.move_preview'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.move_preview') }}</h1>
            <p>{{ $class->name }} → {{ $targetClass->name }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('classes.show', $class) }}">{{ __('messages.back') }}</a>
    </div>

    <div class="dashboard-card">
        <p class="text-muted">{{ __('messages.move_preview_help') }}</p>

        <div class="table-responsive mb-3">
            <table class="table align-middle data-table">
                <thead><tr><th>{{ __('messages.student_id') }}</th><th>{{ __('messages.student_name') }}</th><th>{{ __('messages.status') }}</th></tr></thead>
                <tbody>
                @foreach($students as $student)
                    <tr>
                        <td data-label="{{ __('messages.student_id') }}">{{ $student->student_id }}</td>
                        <td data-label="{{ __('messages.student_name') }}">{{ $student->name }}</td>
                        <td data-label="{{ __('messages.status') }}">{{ __('messages.'.$student->status) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('classes.move', $class) }}">
            @csrf
            <input type="hidden" name="target_class_id" value="{{ $targetClass->id }}">
            @foreach($students as $student)
                <input type="hidden" name="student_ids[]" value="{{ $student->id }}">
            @endforeach
            <button class="btn btn-primary" type="submit"><i class="bi bi-check2 me-1"></i>{{ __('messages.confirm_move') }}</button>
            <a class="btn btn-outline-secondary" href="{{ route('classes.show', $class) }}">{{ __('messages.cancel') }}</a>
        </form>
    </div>
@endsection
