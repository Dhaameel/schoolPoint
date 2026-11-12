@extends('layouts.app')

@section('title', __('messages.settings'))

@section('content')
    <div class="dashboard-heading">
        <div>
            <h1>{{ __('messages.settings') }}</h1>
            <p>{{ __('messages.school_identity_help') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="dashboard-card h-100">
                    <div class="dashboard-card-head"><h2>{{ __('messages.school_identity') }}</h2></div>

                    <div class="mb-3">
                        <label class="form-label" for="school_name">{{ __('messages.school_name') }}</label>
                        <input class="form-control @error('school_name') is-invalid @enderror" id="school_name"
                               name="school_name" value="{{ old('school_name', $settings['school_name'] ?? '') }}" required>
                        @error('school_name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="logo">{{ __('messages.school_logo') }}</label>
                        @if(!empty($settings['school_logo_path']))
                            <div class="mb-2">
                                <img src="{{ asset('storage/'.$settings['school_logo_path']) }}" alt="{{ __('messages.school_logo') }}" style="max-height: 64px; max-width: 180px; object-fit: contain;">
                            </div>
                        @else
                            <p class="text-muted small">{{ __('messages.no_logo') }}</p>
                        @endif
                        <input class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" type="file" accept="image/*">
                        <div class="form-text">{{ __('messages.logo_help') }}</div>
                        @error('logo')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="dashboard-card h-100">
                    <div class="dashboard-card-head"><h2>{{ __('messages.numbering') }}</h2></div>

                    <div class="mb-3">
                        <label class="form-label" for="student_id_prefix">{{ __('messages.student_id_prefix') }}</label>
                        <input class="form-control @error('student_id_prefix') is-invalid @enderror" id="student_id_prefix"
                               name="student_id_prefix" value="{{ old('student_id_prefix', $settings['student_id_prefix'] ?? 'SP') }}" required>
                        @error('student_id_prefix')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="receipt_prefix">{{ __('messages.receipt_prefix') }}</label>
                        <input class="form-control @error('receipt_prefix') is-invalid @enderror" id="receipt_prefix"
                               name="receipt_prefix" value="{{ old('receipt_prefix', $settings['receipt_prefix'] ?? 'RCPT') }}" required>
                        @error('receipt_prefix')<span class="invalid-feedback">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="dashboard-card h-100">
                    <div class="dashboard-card-head"><h2>{{ __('messages.currency') }}</h2></div>
                    <select class="form-select" id="currency" name="currency" required>
                        @foreach(['USD', 'KES', 'UGX', 'EUR', 'SOS', 'ETB', 'TZS'] as $currency)
                            <option value="{{ $currency }}" @selected(old('currency', $settings['currency'] ?? 'USD') === $currency)>{{ $currency }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="dashboard-card h-100">
                    <div class="dashboard-card-head"><h2>{{ __('messages.regional') }}</h2></div>
                    <label class="form-label" for="timezone">{{ __('messages.timezone') }}</label>
                    <input class="form-control @error('timezone') is-invalid @enderror" id="timezone"
                           name="timezone" value="{{ old('timezone', $settings['timezone'] ?? 'Africa/Mogadishu') }}" required>
                    <div class="form-text">{{ __('messages.regional_help') }}</div>
                    @error('timezone')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="col-12">
                <div class="dashboard-card">
                    <div class="dashboard-card-head"><h2>{{ __('messages.school_days') }}</h2></div>
                    <p class="text-muted small">{{ __('messages.school_days_help') }}</p>
                    <div class="d-flex flex-wrap gap-3">
                        @foreach([1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 7 => 'sunday'] as $day => $key)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="school_days[]" id="school_day_{{ $day }}" value="{{ $day }}" {{ in_array($day, old('school_days', $schoolDays), true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="school_day_{{ $day }}">{{ __('messages.'.$key) }}</label>
                            </div>
                        @endforeach
                    </div>
                    @error('school_days')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="col-12">
                <button class="btn btn-primary" type="submit">{{ __('messages.save_settings') }}</button>
            </div>
        </div>
    </form>
@endsection
