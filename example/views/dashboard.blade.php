@extends('layouts.app')

@section('title', 'SMS Dashboard')
@section('page-title', 'Dashboard')

@section('page-actions')
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-primary btn-custom" onclick="refreshDashboard()">
            <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <a href="{{ route('sms.campaigns.create') }}" class="btn btn-success btn-custom">
            <i class="fas fa-plus"></i> New Campaign
        </a>
    </div>
@endsection

@section('content')
<div class="row">
    <!-- Statistics Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total Messages</div>
                        <div class="h5 mb-0 font-weight-bold" id="total-messages">{{ $analytics['overview']['total_messages'] ?? 0 }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-sms fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Sent Messages</div>
                        <div class="h5 mb-0 font-weight-bold" id="sent-messages">{{ $analytics['overview']['sent_messages'] ?? 0 }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Failed Messages</div>
                        <div class="h5 mb-0 font-weight-bold" id="failed-messages">{{ $analytics['overview']['failed_messages'] ?? 0 }}</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card stat-card" style="background: linear-gradient(135deg, #feca57 0%, #ff9ff3 100%);">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Success Rate</div>
                        <div class="h5 mb-0 font-weight-bold" id="success-rate">{{ $analytics['overview']['overall_success_rate'] ?? 0 }}%</div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-percentage fa-2x text-white-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Charts Section -->
    <div class="col-xl-8 col-lg-7">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-area me-1"></i>
                Daily SMS Statistics (Last 7 Days)
            </div>
            <div class="card-body">
                <canvas id="dailyChart" width="100%" height="40"></canvas>
            </div>
        </div>
    </div>

    <!-- Driver Performance -->
    <div class="col-xl-4 col-lg-5">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-chart-pie me-1"></i>
                Driver Performance
            </div>
            <div class="card-body">
                <canvas id="driverChart" width="100%" height="50"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Campaigns -->
    <div class="col-xl-6 col-lg-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-bullhorn me-1"></i> Recent Campaigns</span>
                <a href="{{ route('sms.campaigns.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Status</th>
                                <th>Recipients</th>
                                <th>Success Rate</th>
                            </tr>
                        </thead>
                        <tbody id="recent-campaigns">
                            @forelse($recentCampaigns ?? [] as $campaign)
                            <tr>
                                <td>
                                    <strong>{{ $campaign['name'] }}</strong><br>
                                    <small class="text-muted">{{ $campaign['created_at'] }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-status 
                                        @if($campaign['status'] === 'completed') bg-success
                                        @elseif($campaign['status'] === 'running') bg-primary
                                        @elseif($campaign['status'] === 'scheduled') bg-warning
                                        @else bg-danger
                                        @endif">
                                        {{ ucfirst($campaign['status']) }}
                                    </span>
                                </td>
                                <td>{{ $campaign['total_recipients'] }}</td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: {{ $campaign['success_rate'] }}%" 
                                             aria-valuenow="{{ $campaign['success_rate'] }}" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            {{ $campaign['success_rate'] }}%
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    No campaigns found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent SMS Logs -->
    <div class="col-xl-6 col-lg-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list me-1"></i> Recent SMS Activity</span>
                <a href="{{ route('sms.logs.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Recipient</th>
                                <th>Driver</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody id="recent-logs">
                            @forelse($recentLogs ?? [] as $log)
                            <tr>
                                <td>
                                    <strong>{{ $log['to'] }}</strong><br>
                                    <small class="text-muted">{{ Str::limit($log['body'] ?? '', 30) }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ ucfirst($log['driver']) }}</span>
                                </td>
                                <td>
                                    <span class="badge badge-status 
                                        @if($log['status'] === 'sent') bg-success
                                        @elseif($log['status'] === 'pending') bg-warning
                                        @else bg-danger
                                        @endif">
                                        {{ ucfirst($log['status']) }}
                                    </span>
                                </td>
                                <td>
                                    <small>{{ $log['created_at'] }}</small>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    No SMS logs found
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bolt me-1"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <button class="btn btn-primary btn-custom w-100" onclick="sendTestSMS()">
                            <i class="fas fa-paper-plane"></i><br>
                            Send Test SMS
                        </button>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('sms.templates.create') }}" class="btn btn-success btn-custom w-100">
                            <i class="fas fa-file-alt"></i><br>
                            Create Template
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <button class="btn btn-info btn-custom w-100" onclick="runSeeder()">
                            <i class="fas fa-database"></i><br>
                            Run Test Seeder
                        </button>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="{{ route('sms.analytics') }}" class="btn btn-warning btn-custom w-100">
                            <i class="fas fa-chart-bar"></i><br>
                            View Analytics
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Dashboard JavaScript Functions

// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    initializeDailyChart();
    initializeDriverChart();
});

// Daily Statistics Chart
function initializeDailyChart() {
    const ctx = document.getElementById('dailyChart').getContext('2d');
    const dailyData = @json($analytics['daily_statistics'] ?? []);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(item => item.date),
            datasets: [{
                label: 'Total Messages',
                data: dailyData.map(item => item.total),
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'Sent Messages',
                data: dailyData.map(item => item.sent),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Daily SMS Statistics'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Driver Performance Chart
function initializeDriverChart() {
    const ctx = document.getElementById('driverChart').getContext('2d');
    const driverData = @json($analytics['driver_statistics'] ?? []);
    
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: driverData.map(item => item.driver.toUpperCase()),
            datasets: [{
                data: driverData.map(item => item.total),
                backgroundColor: [
                    '#FF6384',
                    '#36A2EB',
                    '#FFCE56',
                    '#4BC0C0',
                    '#9966FF',
                    '#FF9F40'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

// Refresh Dashboard Data
function refreshDashboard() {
    showLoading('body');
    
    $.get('/sms-test/logs/analytics')
        .done(function(response) {
            if (response.status === 'success') {
                updateDashboardStats(response.analytics);
                showAlert('success', 'Dashboard refreshed successfully!');
            }
        })
        .fail(function() {
            showAlert('error', 'Failed to refresh dashboard data.');
        })
        .always(function() {
            hideLoading('body');
        });
}

// Update Dashboard Statistics
function updateDashboardStats(analytics) {
    $('#total-messages').text(analytics.overview.total_messages);
    $('#sent-messages').text(analytics.overview.sent_messages);
    $('#failed-messages').text(analytics.overview.failed_messages);
    $('#success-rate').text(analytics.overview.overall_success_rate + '%');
}

// Send Test SMS
function sendTestSMS() {
    const phoneNumber = prompt('Enter phone number for test SMS:');
    if (!phoneNumber) return;
    
    showLoading('body');
    
    $.get('/test-sms')
        .done(function(response) {
            if (response.status === 'success') {
                showAlert('success', 'Test SMS sent successfully!');
            } else {
                showAlert('error', 'Failed to send test SMS: ' + response.message);
            }
        })
        .fail(function() {
            showAlert('error', 'Failed to send test SMS.');
        })
        .always(function() {
            hideLoading('body');
        });
}

// Run Test Seeder
function runSeeder() {
    if (!confirm('This will populate the database with test data. Continue?')) return;
    
    showLoading('body');
    
    $.get('/sms-test/run-seeder')
        .done(function(response) {
            if (response.status === 'success') {
                showAlert('success', 'Test seeder executed successfully!');
                setTimeout(() => location.reload(), 2000);
            } else {
                showAlert('error', 'Failed to run seeder: ' + response.message);
            }
        })
        .fail(function() {
            showAlert('error', 'Failed to run test seeder.');
        })
        .always(function() {
            hideLoading('body');
        });
}
</script>
@endpush