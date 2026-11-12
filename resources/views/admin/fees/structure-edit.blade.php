@extends('layouts.app')

@section('title', __('messages.edit_fee_structure'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.edit_fee_structure') }}</h1>
            <p>{{ $structure->name }} &middot; {{ $structure->schoolClass?->name ?? '-' }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('fees.structures.index') }}">{{ __('messages.back') }}</a>
    </div>

    <div class="dashboard-card" style="max-width: 760px;">
        @include('admin.fees._structure-form')
    </div>
@endsection
