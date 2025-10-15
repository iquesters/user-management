@php
    $layout = class_exists(\Iquesters\UserInterface\UserInterfaceServiceProvider::class)
        ? 'userinterface::layouts.app'
        : config('usermanagement.layout_app');
@endphp

@extends($layout)

@section('content')
    <div>
        <!-- Welcome Message -->
        <div class="mb-4">
            <h5 class="text-muted fs-6">Welcome, {{ $user->name }}!</h5>
        </div>

        
        <!-- Organisation Section -->
        @if (config('usermanagement.organisation_needed'))
            <div>
                <h5 class="fs-6 text-muted">
                    <i class="fas fa-building me-2"></i>Organisation
                </h5>
                <div>
                    @if($hasOrganisation)
                        {{ $user->organisations?->toJson() }}
                        <!-- Show when user has organisations -->
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            You are associated with an organisation.
                        </div>
                    @else
                        <!-- Show when user has no organisations -->
                        <div>
                            <p>
                                <span class="text-warning fw-semibold">No Organisation Found.</span>
                                You need to create or join an organisation to get started.
                            </p>
                            
                            <!-- Create Organisation Form -->
                            <div class="justify-content-center">
                                <div class="col-md-6">
                                    <form action="{{ route('dashboard.create-organisation') }}" method="POST">
                                        @csrf
                                        <div class="mb-3">
                                            <label for="name" class="form-label">Organisation Name</label>
                                            <input type="text" 
                                                    class="form-control @error('name') is-invalid @enderror" 
                                                    id="name" 
                                                    name="name" 
                                                    value="{{ old('name') }}" 
                                                    placeholder="Enter your organisation name" 
                                                    required>
                                            @error('name')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description (Optional)</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                                        id="description" 
                                                        name="description" 
                                                        rows="3" 
                                                        placeholder="Brief description of your organisation">{{ old('description') }}</textarea>
                                            @error('description')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        
                                        <div class="d-flex align-items-center justify-content-end">
                                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                                Create Organisation
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
@endsection