@extends('layouts.app')

@section('title', __('messages.add_fee_structure'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.add_fee_structure') }}</h1>
            <p>{{ __('messages.fee_structure_help') }}</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('fees.structures.index') }}">{{ __('messages.back') }}</a>
    </div>

    <div class="dashboard-card" style="max-width: 760px;">
        @include('admin.fees._structure-form')
    </div>
@endsection
