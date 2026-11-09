@extends('layouts.print')

@section('title', __('messages.password_slip'))

@section('content')
    @php
        $schoolName = \App\Models\Setting::get('school_name', config('app.name', 'schoolPoint'));
    @endphp

    <div class="dashboard-card mx-auto" style="max-width: 620px;">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h1 class="h4 mb-1">{{ $schoolName }}</h1>
                <p class="text-muted mb-0">{{ __('messages.password_slip') }}</p>
            </div>
            <span class="badge bg-primary">{{ __('messages.teacher') }}</span>
        </div>

        <p class="mb-3">{{ __('messages.password_slip_help') }}</p>

        <dl class="row">
            <dt class="col-4">{{ __('messages.teacher_name') }}</dt>
            <dd class="col-8">{{ $credentials['name'] }}</dd>
            <dt class="col-4">{{ __('messages.teacher_username') }}</dt>
            <dd class="col-8"><code>{{ $credentials['username'] }}</code></dd>
            <dt class="col-4">{{ __('messages.temporary_password') }}</dt>
            <dd class="col-8"><code class="recovery-code">{{ $credentials['password'] }}</code></dd>
        </dl>

        <div class="alert alert-warning py-2">{{ __('messages.first_login_note') }}</div>

        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-primary" type="button" onclick="window.print()"><i class="bi bi-printer me-1"></i>{{ __('messages.print') }}</button>
            <button class="btn btn-outline-primary" type="button" id="copy-slip" data-copy="{{ $credentials['username'] }} {{ $credentials['password'] }}">
                <i class="bi bi-clipboard me-1"></i>{{ __('messages.copy') }}
            </button>
            <a class="btn btn-outline-secondary" href="{{ route('teachers.index') }}">{{ __('messages.back') }}</a>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.getElementById('copy-slip')?.addEventListener('click', async function () {
        try {
            await navigator.clipboard.writeText(this.dataset.copy);
            this.textContent = @json(__('messages.copied'));
        } catch (error) {
            console.log(error);
        }
    });
</script>
@endpush
