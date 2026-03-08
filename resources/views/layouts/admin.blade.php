<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - Quiz App')</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Admin Custom CSS -->
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, #2c3e50 0%, #3498db 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,.8);
            padding: 1rem;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover {
            color: white;
            background: rgba(255,255,255,.1);
            padding-left: 1.5rem;
        }
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,.2);
            border-left: 4px solid #f1c40f;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
            width: 20px;
        }
        .content-wrapper {
            padding: 2rem;
            background: #f8f9fa;
            min-height: 100vh;
        }
        .stat-card {
            transition: transform 0.3s;
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="text-center py-4">
                        <i class="bi bi-trophy-fill text-warning" style="font-size: 3rem;"></i>
                        <h5 class="text-white mt-2">Quiz Admin</h5>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" 
                           href="{{ route('admin.dashboard') }}">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.quizzes.*') ? 'active' : '' }}" 
                           href="{{ route('admin.quizzes.index') }}">
                            <i class="bi bi-question-circle"></i> Quizzes
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" 
                           href="{{ route('admin.categories.index') }}">
                            <i class="bi bi-folder"></i> Categories
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" 
                           href="{{ route('admin.reports.index') }}">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" 
                        href="{{ route('admin.users.index') }}">
                            <i class="bi bi-people"></i> Users
                        </a>
                        
                        <a class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}" 
                           href="{{ route('admin.settings') }}">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                        
                        <div class="border-top my-3"></div>
                        
                        <a class="nav-link" href="{{ route('dashboard') }}">
                            <i class="bi bi-house"></i> Back to Site
                        </a>
                        
                        <form method="POST" action="{{ route('logout') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-0">
                <div class="content-wrapper">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    
                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    @stack('scripts')
</body>
</html>