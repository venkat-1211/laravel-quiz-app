@extends('layouts.app')

@section('title', 'Edit Profile - Quiz App')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2 mb-0">
                    <i class="bi bi-pencil-square me-2 text-primary"></i>
                    Edit Profile
                </h1>
                <a href="{{ route('profile.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>
                    Back to Profile
                </a>
            </div>

            <!-- Edit Profile Card -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Avatar Upload -->
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <div id="avatarPreview">
                                    @if(auth()->user()->avatar)
                                        <img src="{{ auth()->user()->avatar }}" 
                                             class="rounded-circle border border-3 border-primary" 
                                             width="120" 
                                             height="120" 
                                             style="object-fit: cover;"
                                             id="avatarImg"
                                             alt="{{ auth()->user()->name }}">
                                    @else
                                        <div class="avatar-circle-large bg-primary text-white mx-auto" id="avatarPlaceholder">
                                            {{ getInitials(auth()->user()->name) }}
                                        </div>
                                    @endif
                                </div>
                                <label for="avatar" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2" style="cursor: pointer;">
                                    <i class="bi bi-camera"></i>
                                </label>
                                <input type="file" class="d-none" id="avatar" name="avatar" accept="image/*" onchange="previewImage(this)">
                            </div>
                            <p class="text-muted small mt-2">
                                <i class="bi bi-info-circle"></i> Click the camera icon to upload a new avatar (Max 2MB)
                            </p>
                            @error('avatar')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Name -->
                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-person"></i>
                                </span>
                                <input type="text" 
                                       class="form-control border-start-0 @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', auth()->user()->name) }}" 
                                       required>
                            </div>
                            @error('name')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-envelope"></i>
                                </span>
                                <input type="email" 
                                       class="form-control border-start-0 @error('email') is-invalid @enderror" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', auth()->user()->email) }}" 
                                       required>
                            </div>
                            @error('email')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">
                                <i class="bi bi-info-circle"></i> Changing email will require re-verification
                            </small>
                        </div>

                        <!-- Bio -->
                        <div class="mb-4">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control @error('bio') is-invalid @enderror" 
                                      id="bio" 
                                      name="bio" 
                                      rows="3">{{ old('bio', auth()->user()->bio) }}</textarea>
                            @error('bio')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Tell us a little about yourself (max 500 characters)</small>
                        </div>

                        <hr>

                        <!-- Account Information -->
                        <h6 class="mb-3">Account Information</h6>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Member Since</strong>
                            </div>
                            <div class="col-sm-8">
                                <p class="mb-0">{{ auth()->user()->created_at->format('F d, Y') }}</p>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Email Status</strong>
                            </div>
                            <div class="col-sm-8">
                                @if(auth()->user()->email_verified_at)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle me-1"></i> Verified
                                    </span>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-triangle me-1"></i> Not Verified
                                    </span>
                                    <a href="{{ route('verification.notice') }}" class="btn btn-sm btn-link">Verify Now</a>
                                @endif
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-sm-4">
                                <strong>Account Type</strong>
                            </div>
                            <div class="col-sm-8">
                                @if(auth()->user()->social_type)
                                    <span class="badge bg-info">
                                        <i class="bi bi-{{ auth()->user()->social_type }} me-1"></i>{{ ucfirst(auth()->user()->social_type) }}
                                    </span>
                                @else
                                    <span class="badge bg-secondary">Standard</span>
                                @endif
                            </div>
                        </div>

                        <hr>

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                <i class="bi bi-trash me-2"></i>
                                Delete Account
                            </button>
                            <div>
                                <a href="{{ route('profile.index') }}" class="btn btn-secondary me-2">
                                    Cancel
                                </a>
                                <button type="submit" class="btn btn-primary" id="updateProfileBtn">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Save Changes
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    Delete Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-exclamation display-1 text-danger"></i>
                </div>
                <h5 class="text-center mb-3">Are you sure you want to delete your account?</h5>
                <p class="text-muted text-center mb-4">
                    This action cannot be undone. All your data, including quiz attempts and achievements, will be permanently deleted.
                </p>
                
                <form method="POST" action="{{ route('profile.delete') }}" id="deleteAccountForm">
                    @csrf
                    @method('DELETE')
                    
                    <div class="mb-3">
                        <label for="delete_password" class="form-label">Enter your password to confirm</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input type="password" 
                                   class="form-control border-start-0" 
                                   id="delete_password" 
                                   name="password" 
                                   required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-2"></i>Cancel
                </button>
                <button type="submit" form="deleteAccountForm" class="btn btn-danger px-4">
                    <i class="bi bi-trash me-2"></i>Delete Account
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .avatar-circle-large {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 48px;
        margin: 0 auto;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .input-group-text {
        border-radius: 10px 0 0 10px;
    }
    
    .input-group .form-control {
        border-radius: 0 10px 10px 0;
    }
    
    .card {
        border-radius: 15px;
    }
    
    .btn-outline-danger, .btn-danger {
        transition: all 0.3s;
    }
    
    .btn-outline-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220, 53, 69, 0.3);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }
    
    .modal-content {
        border-radius: 20px;
    }
    
    .modal-header {
        border-radius: 20px 20px 0 0;
        padding: 20px 25px;
    }
    
    .modal-body {
        padding: 25px;
    }
    
    .modal-footer {
        border-radius: 0 0 20px 20px;
        padding: 15px 25px;
    }
</style>
@endpush

@push('scripts')
<script>
    function previewImage(input) {
        const preview = document.getElementById('avatarPreview');
        const file = input.files[0];
        
        if (file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                // Remove placeholder if exists
                const placeholder = document.getElementById('avatarPlaceholder');
                if (placeholder) {
                    placeholder.remove();
                }
                
                // Check if image exists
                let img = document.getElementById('avatarImg');
                if (!img) {
                    img = document.createElement('img');
                    img.id = 'avatarImg';
                    img.className = 'rounded-circle border border-3 border-primary';
                    img.width = 120;
                    img.height = 120;
                    img.style.objectFit = 'cover';
                    preview.innerHTML = '';
                    preview.appendChild(img);
                }
                
                img.src = e.target.result;
            }
            
            reader.readAsDataURL(file);
        }
    }

    // Form submission with loading state
    document.getElementById('updateProfileBtn')?.addEventListener('click', function(e) {
        const button = this;
        button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
        button.disabled = true;
        document.querySelector('form').submit();
    });
</script>
@endpush