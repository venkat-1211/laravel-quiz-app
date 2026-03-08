@extends('layouts.admin')

@section('title', 'System Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>System Settings</h2>
        <button type="button" class="btn btn-danger" onclick="clearAllCache()">
            <i class="bi bi-trash me-2"></i>Clear All Cache
        </button>
    </div>

    <div class="row">
        <!-- General Settings -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-gear me-2 text-primary"></i>
                        General Settings
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="app_name" class="form-label">Application Name</label>
                            <input type="text" class="form-control" id="app_name" name="app_name" 
                                   value="{{ $settings['app_name'] ?? config('app.name') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="app_env" class="form-label">Environment</label>
                            <select class="form-select" id="app_env" name="app_env" disabled>
                                <option value="local" {{ (($settings['app_env'] ?? config('app.env')) == 'local') ? 'selected' : '' }}>Local</option>
                                <option value="development" {{ (($settings['app_env'] ?? config('app.env')) == 'development') ? 'selected' : '' }}>Development</option>
                                <option value="staging" {{ (($settings['app_env'] ?? config('app.env')) == 'staging') ? 'selected' : '' }}>Staging</option>
                                <option value="production" {{ (($settings['app_env'] ?? config('app.env')) == 'production') ? 'selected' : '' }}>Production</option>
                            </select>
                            <small class="text-muted">Environment cannot be changed via UI for security reasons</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="app_debug" 
                                       name="app_debug" value="1" {{ ($settings['app_debug'] ?? config('app.debug')) ? 'checked' : '' }}>
                                <label class="form-check-label" for="app_debug">Debug Mode</label>
                            </div>
                            <small class="text-muted">Enable detailed error messages (disable in production)</small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Cache Settings -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-database me-2 text-primary"></i>
                        Cache Settings
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <label class="form-label">Current Cache Driver</label>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-info fs-6 p-2">{{ $settings['cache_driver'] ?? config('cache.default') }}</span>
                            <span class="ms-2 text-muted">(File driver is being used)</span>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Session Driver</label>
                        <div class="d-flex align-items-center">
                            <span class="badge bg-info fs-6 p-2">{{ $settings['session_driver'] ?? config('session.driver') }}</span>
                        </div>
                    </div>

                    <h6 class="mt-4 mb-3">Cache Statistics</h6>
                    
                    <div class="list-group">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Quiz List Cache
                            <span class="badge {{ isset($cacheStats['quiz_list']) && $cacheStats['quiz_list'] ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                {{ isset($cacheStats['quiz_list']) && $cacheStats['quiz_list'] ? 'Cached' : 'Not Cached' }}
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Categories Cache
                            <span class="badge {{ isset($cacheStats['categories']) && $cacheStats['categories'] ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                {{ isset($cacheStats['categories']) && $cacheStats['categories'] ? 'Cached' : 'Not Cached' }}
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            Leaderboard Cache
                            <span class="badge {{ isset($cacheStats['leaderboard']) && $cacheStats['leaderboard'] ? 'bg-success' : 'bg-secondary' }} rounded-pill">
                                {{ isset($cacheStats['leaderboard']) && $cacheStats['leaderboard'] ? 'Cached' : 'Not Cached' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mail Settings -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-envelope me-2 text-primary"></i>
                        Mail Settings
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="mail_driver" class="form-label">Mail Driver</label>
                            <select class="form-select" id="mail_driver" name="mail_driver">
                                <option value="smtp" {{ (env('MAIL_MAILER') == 'smtp') ? 'selected' : '' }}>SMTP</option>
                                <option value="sendmail" {{ (env('MAIL_MAILER') == 'sendmail') ? 'selected' : '' }}>Sendmail</option>
                                <option value="mailgun" {{ (env('MAIL_MAILER') == 'mailgun') ? 'selected' : '' }}>Mailgun</option>
                                <option value="ses" {{ (env('MAIL_MAILER') == 'ses') ? 'selected' : '' }}>Amazon SES</option>
                                <option value="log" {{ (env('MAIL_MAILER') == 'log') ? 'selected' : '' }}>Log (Testing)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="mail_host" class="form-label">Mail Host</label>
                            <input type="text" class="form-control" id="mail_host" name="mail_host" 
                                   value="{{ env('MAIL_HOST', 'smtp.mailtrap.io') }}">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="mail_port" class="form-label">Port</label>
                                <input type="number" class="form-control" id="mail_port" name="mail_port" 
                                       value="{{ env('MAIL_PORT', '2525') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="mail_encryption" class="form-label">Encryption</label>
                                <select class="form-select" id="mail_encryption" name="mail_encryption">
                                    <option value="tls" {{ env('MAIL_ENCRYPTION') == 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ env('MAIL_ENCRYPTION') == 'ssl' ? 'selected' : '' }}>SSL</option>
                                    <option value="" {{ env('MAIL_ENCRYPTION') == '' ? 'selected' : '' }}>None</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="mail_username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="mail_username" name="mail_username" 
                                   value="{{ env('MAIL_USERNAME') }}">
                        </div>

                        <div class="mb-3">
                            <label for="mail_password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="mail_password" name="mail_password" 
                                   value="{{ env('MAIL_PASSWORD') }}">
                        </div>

                        <div class="mb-3">
                            <label for="mail_from_address" class="form-label">From Address</label>
                            <input type="email" class="form-control" id="mail_from_address" name="mail_from_address" 
                                   value="{{ env('MAIL_FROM_ADDRESS', 'hello@example.com') }}">
                        </div>

                        <div class="mb-3">
                            <label for="mail_from_name" class="form-label">From Name</label>
                            <input type="text" class="form-control" id="mail_from_name" name="mail_from_name" 
                                   value="{{ env('MAIL_FROM_NAME', config('app.name')) }}">
                        </div>

                        <button type="button" class="btn btn-success me-2" onclick="testMailSettings()">
                            <i class="bi bi-send me-2"></i>Test Connection
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Save Mail Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Security Settings -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-lock me-2 text-primary"></i>
                        Security Settings
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.update') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="session_lifetime" class="form-label">Session Lifetime (minutes)</label>
                            <input type="number" class="form-control" id="session_lifetime" name="session_lifetime" 
                                   value="{{ config('session.lifetime', 120) }}" min="5" max="1440">
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="force_https" 
                                       name="force_https" value="1" {{ env('FORCE_HTTPS', false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="force_https">Force HTTPS</label>
                            </div>
                            <small class="text-muted">Redirect all HTTP requests to HTTPS</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="hsts" 
                                       name="hsts" value="1" {{ env('HSTS_ENABLED', false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="hsts">Enable HSTS</label>
                            </div>
                            <small class="text-muted">HTTP Strict Transport Security</small>
                        </div>

                        <div class="mb-3">
                            <label for="password_min_length" class="form-label">Minimum Password Length</label>
                            <input type="number" class="form-control" id="password_min_length" name="password_min_length" 
                                   value="{{ config('auth.password_min_length', 8) }}" min="6" max="20">
                        </div>

                        <div class="mb-3">
                            <label for="login_attempts" class="form-label">Max Login Attempts</label>
                            <input type="number" class="form-control" id="login_attempts" name="login_attempts" 
                                   value="{{ config('auth.login_attempts', 5) }}" min="3" max="10">
                        </div>

                        <div class="mb-3">
                            <label for="lockout_time" class="form-label">Lockout Time (minutes)</label>
                            <input type="number" class="form-control" id="lockout_time" name="lockout_time" 
                                   value="{{ config('auth.lockout_time', 15) }}" min="5" max="60">
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Save Security Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- System Information -->
        <div class="col-12 mb-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        System Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">Laravel Version</label>
                                <p>{{ app()->version() }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">PHP Version</label>
                                <p>{{ phpversion() }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">Database</label>
                                <p>{{ config('database.default') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">Timezone</label>
                                <p>{{ config('app.timezone') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">Server Software</label>
                                <p>{{ $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">Memory Limit</label>
                                <p>{{ ini_get('memory_limit') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">Max Upload Size</label>
                                <p>{{ ini_get('upload_max_filesize') }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-3">
                                <label class="fw-bold">Max Execution Time</label>
                                <p>{{ ini_get('max_execution_time') }}s</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Test Mail Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="test_email" class="form-label">Send Test Email To</label>
                    <input type="email" class="form-control" id="test_email" value="{{ auth()->user()->email }}">
                </div>
                <div id="testResult" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestEmail()">
                    <i class="bi bi-send me-2"></i>Send Test
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function clearAllCache() {
        if (confirm('Are you sure you want to clear all cache? This may temporarily slow down the application.')) {
            fetch('{{ route("admin.settings.clear-cache") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    toastr.success('Cache cleared successfully');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    toastr.error('Failed to clear cache');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                toastr.error('Failed to clear cache');
            });
        }
    }

    function testMailSettings() {
        new bootstrap.Modal(document.getElementById('testEmailModal')).show();
    }

    function sendTestEmail() {
        const email = document.getElementById('test_email').value;
        const button = event.target;
        const originalText = button.innerHTML;
        
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
        button.disabled = true;

        fetch('/admin/settings/test-mail', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ email: email })
        })
        .then(response => response.json())
        .then(data => {
            const resultDiv = document.getElementById('testResult');
            resultDiv.style.display = 'block';
            
            if (data.success) {
                resultDiv.className = 'alert alert-success';
                resultDiv.innerHTML = '<i class="bi bi-check-circle me-2"></i>' + data.message;
            } else {
                resultDiv.className = 'alert alert-danger';
                resultDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>' + data.message;
            }
            
            button.innerHTML = originalText;
            button.disabled = false;
        })
        .catch(error => {
            const resultDiv = document.getElementById('testResult');
            resultDiv.style.display = 'block';
            resultDiv.className = 'alert alert-danger';
            resultDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Failed to send test email';
            
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }

    // Toastr configuration
    toastr.options = {
        positionClass: 'toast-top-right',
        progressBar: true,
        timeOut: 3000
    };
</script>
@endpush

@push('styles')
<style>
    .list-group-item .badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }
    
    .form-switch {
        padding-left: 2.5em;
    }
    
    .form-switch .form-check-input {
        width: 3em;
        height: 1.5em;
        margin-left: -2.5em;
    }
</style>
@endpush