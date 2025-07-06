<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Multi-SMS Dashboard')</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js for Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* Custom CSS for SMS Dashboard */
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-custom {
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 500;
        }
        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
        }
        .badge-status {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8em;
        }
        .loading {
            display: none;
        }
        .alert-custom {
            border-radius: 15px;
            border: none;
        }
    </style>
    
    @stack('styles')
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white"><i class="fas fa-sms"></i> Multi-SMS</h4>
                        <small class="text-white-50">SMS Marketing Dashboard</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('sms.dashboard') ? 'active' : '' }}" href="{{ route('sms.dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('sms.templates.*') ? 'active' : '' }}" href="{{ route('sms.templates.index') }}">
                                <i class="fas fa-file-alt me-2"></i> Templates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('sms.campaigns.*') ? 'active' : '' }}" href="{{ route('sms.campaigns.index') }}">
                                <i class="fas fa-bullhorn me-2"></i> Campaigns
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('sms.autoresponders.*') ? 'active' : '' }}" href="{{ route('sms.autoresponders.index') }}">
                                <i class="fas fa-robot me-2"></i> Autoresponders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('sms.logs.*') ? 'active' : '' }}" href="{{ route('sms.logs.index') }}">
                                <i class="fas fa-list me-2"></i> SMS Logs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('sms.analytics') ? 'active' : '' }}" href="{{ route('sms.analytics') }}">
                                <i class="fas fa-chart-bar me-2"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs('sms.settings') ? 'active' : '' }}" href="{{ route('sms.settings') }}">
                                <i class="fas fa-cog me-2"></i> Settings
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">@yield('page-title', 'Dashboard')</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        @yield('page-actions')
                    </div>
                </div>

                <!-- Alert Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-custom alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-custom alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Page Content -->
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Common JavaScript Functions -->
    <script>
        // CSRF Token Setup for AJAX
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Common Functions
        function showLoading(element) {
            $(element).find('.loading').show();
            $(element).find('.btn').prop('disabled', true);
        }

        function hideLoading(element) {
            $(element).find('.loading').hide();
            $(element).find('.btn').prop('disabled', false);
        }

        function showAlert(type, message) {
            const alertHtml = `
                <div class="alert alert-${type} alert-custom alert-dismissible fade show" role="alert">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            $('.alert').remove();
            $('main').prepend(alertHtml);
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    </script>
    
    @stack('scripts')
</body>
</html>