@extends('layouts.admin')

@section('title', 'Reports & Analytics')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Reports & Analytics</h2>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-download me-2"></i>Export Report
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" id="exportPDF"><i class="bi bi-file-pdf me-2 text-danger"></i>PDF</a></li>
                <li><a class="dropdown-item" href="#" id="exportExcel"><i class="bi bi-file-excel me-2 text-success"></i>Excel</a></li>
                <li><a class="dropdown-item" href="#" id="exportCSV"><i class="bi bi-file-text me-2 text-info"></i>CSV</a></li>
            </ul>
        </div>
    </div>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="reportFilterForm" class="row g-3">
                <div class="col-md-4">
                    <label for="dateRange" class="form-label">Date Range</label>
                    <select class="form-select" id="dateRange" name="dateRange">
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="last7days" selected>Last 7 Days</option>
                        <option value="last30days">Last 30 Days</option>
                        <option value="thisMonth">This Month</option>
                        <option value="lastMonth">Last Month</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-md-3 custom-date" style="display: none;">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="date" class="form-control" id="startDate" name="startDate">
                </div>
                <div class="col-md-3 custom-date" style="display: none;">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="date" class="form-control" id="endDate" name="endDate">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-filter me-2"></i>Apply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Overview Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card stat-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Total Attempts</h6>
                            <h2 class="mb-0" id="totalAttempts">0</h2>
                        </div>
                        <div>
                            <i class="bi bi-pencil-square" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Completion Rate</h6>
                            <h2 class="mb-0" id="completionRate">0%</h2>
                        </div>
                        <div>
                            <i class="bi bi-check-circle" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Average Score</h6>
                            <h2 class="mb-0" id="averageScore">0%</h2>
                        </div>
                        <div>
                            <i class="bi bi-star" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card stat-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-2">Unique Users</h6>
                            <h2 class="mb-0" id="uniqueUsers">0</h2>
                        </div>
                        <div>
                            <i class="bi bi-people" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-bar-chart-line me-2 text-primary"></i>
                        Attempts Over Time
                    </h5>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary chart-period active" data-period="daily">Daily</button>
                        <button type="button" class="btn btn-outline-secondary chart-period" data-period="weekly">Weekly</button>
                        <button type="button" class="btn btn-outline-secondary chart-period" data-period="monthly">Monthly</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="attemptsChart" height="100"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-pie-chart me-2 text-primary"></i>
                        Score Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="scoreDistributionChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-trophy me-2 text-primary"></i>
                        Top Performing Quizzes
                    </h5>
                    <a href="{{ route('admin.reports.quiz-analytics') }}" class="btn btn-sm btn-outline-primary">View Details</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="topQuizzesTable">
                            <thead>
                                <tr>
                                    <th>Quiz</th>
                                    <th>Attempts</th>
                                    <th>Avg Score</th>
                                    <th>Completion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                        Loading data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people me-2 text-primary"></i>
                        Top Performing Users
                    </h5>
                    <a href="{{ route('admin.reports.user-performance') }}" class="btn btn-sm btn-outline-primary">View Details</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="topUsersTable">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Attempts</th>
                                    <th>Avg Score</th>
                                    <th>Points</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="4" class="text-center py-3">
                                        <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                        Loading data...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Performance -->
    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="bi bi-folder me-2 text-primary"></i>
                Category Performance
            </h5>
        </div>
        <div class="card-body">
            <canvas id="categoryChart" height="80"></canvas>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">
                <i class="bi bi-clock-history me-2 text-primary"></i>
                Recent Activity
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="recentActivityTable">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>User</th>
                            <th>Quiz</th>
                            <th>Score</th>
                            <th>Time Taken</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center py-3">
                                <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                                Loading activity...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .stat-card {
        transition: transform 0.3s, box-shadow 0.3s;
        border: none;
        border-radius: 15px;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    }
    
    .chart-period.active {
        background-color: #667eea;
        color: white;
        border-color: #667eea;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let attemptsChart, scoreChart, categoryChart;
    
    document.addEventListener('DOMContentLoaded', function() {
        initializeCharts();
        loadReportData();
        
        // Date range toggle
        document.getElementById('dateRange').addEventListener('change', function() {
            const customDates = document.querySelectorAll('.custom-date');
            if (this.value === 'custom') {
                customDates.forEach(el => el.style.display = 'block');
            } else {
                customDates.forEach(el => el.style.display = 'none');
                loadReportData();
            }
        });
        
        // Form submit
        document.getElementById('reportFilterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            loadReportData();
        });
        
        // Chart period toggle
        document.querySelectorAll('.chart-period').forEach(button => {
            button.addEventListener('click', function() {
                document.querySelectorAll('.chart-period').forEach(btn => {
                    btn.classList.remove('active');
                });
                this.classList.add('active');
                updateAttemptsChart(this.dataset.period);
            });
        });
        
        // Export buttons
        document.getElementById('exportPDF').addEventListener('click', function(e) {
            e.preventDefault();
            exportReport('pdf');
        });
        
        document.getElementById('exportExcel').addEventListener('click', function(e) {
            e.preventDefault();
            exportReport('excel');
        });
        
        document.getElementById('exportCSV').addEventListener('click', function(e) {
            e.preventDefault();
            exportReport('csv');
        });
    });
    
    function initializeCharts() {
        // Attempts Chart
        const attemptsCtx = document.getElementById('attemptsChart').getContext('2d');
        attemptsChart = new Chart(attemptsCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Attempts',
                    data: [],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // Score Distribution Chart
        const scoreCtx = document.getElementById('scoreDistributionChart').getContext('2d');
        scoreChart = new Chart(scoreCtx, {
            type: 'doughnut',
            data: {
                labels: ['0-20%', '21-40%', '41-60%', '61-80%', '81-100%'],
                datasets: [{
                    data: [0, 0, 0, 0, 0],
                    backgroundColor: [
                        '#dc3545',
                        '#fd7e14',
                        '#ffc107',
                        '#20c997',
                        '#28a745'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        categoryChart = new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Average Score (%)',
                    data: [],
                    backgroundColor: '#667eea',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
    
    function loadReportData() {
        const formData = new FormData(document.getElementById('reportFilterForm'));
        const params = new URLSearchParams(formData).toString();
        
        fetch(`{{ route("admin.reports.chart-data") }}?${params}&type=overview`)
            .then(response => response.json())
            .then(data => {
                updateOverviewStats(data);
            });
        
        fetch(`{{ route("admin.reports.chart-data") }}?${params}&type=attempts`)
            .then(response => response.json())
            .then(data => {
                updateAttemptsChart('daily', data);
            });
        
        fetch(`{{ route("admin.reports.chart-data") }}?${params}&type=scores`)
            .then(response => response.json())
            .then(data => {
                updateScoreDistribution(data);
            });
        
        fetch(`{{ route("admin.reports.chart-data") }}?${params}&type=categories`)
            .then(response => response.json())
            .then(data => {
                updateCategoryChart(data);
            });
        
        loadTopQuizzes();
        loadTopUsers();
        loadRecentActivity();
    }
    
    function updateOverviewStats(data) {
        document.getElementById('totalAttempts').textContent = data.total_attempts || 0;
        document.getElementById('completionRate').textContent = (data.completion_rate || 0) + '%';
        document.getElementById('averageScore').textContent = (data.average_score || 0) + '%';
        document.getElementById('uniqueUsers').textContent = data.unique_users || 0;
    }
    
    function updateAttemptsChart(period, data = null) {
        if (!data) {
            const formData = new FormData(document.getElementById('reportFilterForm'));
            const params = new URLSearchParams(formData).toString();
            
            fetch(`{{ route("admin.reports.chart-data") }}?${params}&type=attempts&period=${period}`)
                .then(response => response.json())
                .then(data => {
                    attemptsChart.data.labels = data.labels;
                    attemptsChart.data.datasets[0].data = data.data;
                    attemptsChart.update();
                });
        } else {
            attemptsChart.data.labels = data.labels;
            attemptsChart.data.datasets[0].data = data.data;
            attemptsChart.update();
        }
    }
    
    function updateScoreDistribution(data) {
        scoreChart.data.datasets[0].data = [
            data['0-20'] || 0,
            data['21-40'] || 0,
            data['41-60'] || 0,
            data['61-80'] || 0,
            data['81-100'] || 0
        ];
        scoreChart.update();
    }
    
    function updateCategoryChart(data) {
        categoryChart.data.labels = data.labels || [];
        categoryChart.data.datasets[0].data = data.data || [];
        categoryChart.update();
    }
    
    function loadTopQuizzes() {
        const formData = new FormData(document.getElementById('reportFilterForm'));
        const params = new URLSearchParams(formData).toString();
        
        fetch(`{{ route("admin.reports.chart-data") }}?${params}&type=top_quizzes`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#topQuizzesTable tbody');
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3">No data available</td></tr>';
                    return;
                }
                
                let html = '';
                data.forEach(quiz => {
                    html += `
                        <tr>
                            <td>${quiz.title}</td>
                            <td>${quiz.attempts}</td>
                            <td>${quiz.avg_score}%</td>
                            <td>${quiz.completion_rate}%</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            });
    }
    
    function loadTopUsers() {
        const formData = new FormData(document.getElementById('reportFilterForm'));
        const params = new URLSearchParams(formData).toString();
        
        fetch(`{{ route("admin.reports.chart-data") }}?${params}&type=top_users`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#topUsersTable tbody');
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3">No data available</td></tr>';
                    return;
                }
                
                let html = '';
                data.forEach(user => {
                    html += `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    ${user.avatar ? 
                                        `<img src="${user.avatar}" class="rounded-circle me-2" width="32" height="32">` : 
                                        `<div class="avatar-circle-sm bg-primary text-white me-2">${user.initials}</div>`
                                    }
                                    ${user.name}
                                </div>
                            </td>
                            <td>${user.attempts}</td>
                            <td>${user.avg_score}%</td>
                            <td>${user.points}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            });
    }
    
    function loadRecentActivity() {
        const formData = new FormData(document.getElementById('reportFilterForm'));
        const params = new URLSearchParams(formData).toString();
        
        fetch(`{{ route("admin.reports.chart-data") }}?${params}&type=recent_activity`)
            .then(response => response.json())
            .then(data => {
                const tbody = document.querySelector('#recentActivityTable tbody');
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3">No recent activity</td></tr>';
                    return;
                }
                
                let html = '';
                data.forEach(activity => {
                    const timeTaken = Math.floor(activity.time_taken / 60) + ':' + 
                                    String(activity.time_taken % 60).padStart(2, '0');
                    
                    html += `
                        <tr>
                            <td>${activity.time}</td>
                            <td>${activity.user_name}</td>
                            <td>${activity.quiz_title}</td>
                            <td>
                                <span class="badge ${activity.score >= 70 ? 'bg-success' : 'bg-danger'}">
                                    ${activity.score}%
                                </span>
                            </td>
                            <td>${timeTaken}</td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            });
    }
    
    function exportReport(format) {
        const formData = new FormData(document.getElementById('reportFilterForm'));
        const params = new URLSearchParams(formData).toString();
        
        window.location.href = `{{ route("admin.reports.export-user") }}?${params}&format=${format}`;
    }
</script>
@endpush