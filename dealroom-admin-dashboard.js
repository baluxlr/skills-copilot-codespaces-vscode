// DealRoom Admin Dashboard Scripts

jQuery(document).ready(function($) {
    // Initialize charts if we're on the analytics page
    if ($('#activity-chart').length) {
        initializeAnalytics();
    }

    function initializeAnalytics() {
        // Set up Chart.js defaults
        Chart.defaults.font.family = '-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif';
        Chart.defaults.font.size = 12;
        Chart.defaults.plugins.tooltip.padding = 10;
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0,0,0,0.8)';
        Chart.defaults.plugins.tooltip.titleFont.size = 13;
        Chart.defaults.plugins.tooltip.titleFont.weight = '600';

        // Load initial data
        loadAnalyticsData();

        // Set up period selector
        $('#analytics-period').on('change', function() {
            loadAnalyticsData();
        });
    }

    function loadAnalyticsData() {
        const period = $('#analytics-period').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'dealroom_get_stats',
                nonce: dealroomAdmin.nonce,
                period: period
            },
            beforeSend: function() {
                // Show loading state
                $('.chart-container').addClass('loading');
            },
            success: function(response) {
                if (response.success) {
                    updateCharts(response.data);
                } else {
                    console.error('Error loading analytics:', response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Ajax error:', error);
            },
            complete: function() {
                // Hide loading state
                $('.chart-container').removeClass('loading');
            }
        });
    }

    function updateCharts(data) {
        // Activity Chart
        const activityCtx = document.getElementById('activity-chart').getContext('2d');
        if (window.activityChart) {
            window.activityChart.destroy();
        }
        window.activityChart = new Chart(activityCtx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Deal Views',
                        data: data.views,
                        borderColor: dealroomAdmin.colors.primary,
                        backgroundColor: 'rgba(0,115,170,0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'New Deals',
                        data: data.deals,
                        borderColor: dealroomAdmin.colors.secondary,
                        backgroundColor: 'rgba(70,180,80,0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'New Users',
                        data: data.users,
                        borderColor: dealroomAdmin.colors.tertiary,
                        backgroundColor: 'rgba(255,186,0,0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });

        // Update overview stats
        $('#total-deals').text(data.total_deals || '—');
        $('#active-deals').text(data.active_deals || '—');
        $('#total-users').text(data.total_users || '—');
        $('#total-views').text(data.total_views || '—');

        // Update funding stats
        if (data.funding) {
            $('#total-funding').text('$' + formatNumber(data.funding.total));
            $('#avg-funding').text('$' + formatNumber(data.funding.average));

            // Funding Chart
            const fundingCtx = document.getElementById('funding-chart').getContext('2d');
            if (window.fundingChart) {
                window.fundingChart.destroy();
            }
            window.fundingChart = new Chart(fundingCtx, {
                type: 'bar',
                data: {
                    labels: data.funding.stages,
                    datasets: [{
                        label: 'Funding by Stage',
                        data: data.funding.amounts,
                        backgroundColor: dealroomAdmin.colors.primary
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
                            ticks: {
                                callback: function(value) {
                                    return '$' + formatNumber(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        // Sector Chart
        if (data.sectors) {
            const sectorCtx = document.getElementById('sector-chart').getContext('2d');
            if (window.sectorChart) {
                window.sectorChart.destroy();
            }
            window.sectorChart = new Chart(sectorCtx, {
                type: 'doughnut',
                data: {
                    labels: data.sectors.labels,
                    datasets: [{
                        data: data.sectors.data,
                        backgroundColor: [
                            dealroomAdmin.colors.primary,
                            dealroomAdmin.colors.secondary,
                            dealroomAdmin.colors.tertiary,
                            dealroomAdmin.colors.quaternary,
                            dealroomAdmin.colors.quinary
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
        }

        // User Growth Chart
        if (data.user_growth) {
            const userGrowthCtx = document.getElementById('user-growth-chart').getContext('2d');
            if (window.userGrowthChart) {
                window.userGrowthChart.destroy();
            }
            window.userGrowthChart = new Chart(userGrowthCtx, {
                type: 'line',
                data: {
                    labels: data.user_growth.labels,
                    datasets: [
                        {
                            label: 'Entrepreneurs',
                            data: data.user_growth.entrepreneurs,
                            borderColor: dealroomAdmin.colors.primary,
                            fill: false
                        },
                        {
                            label: 'Investors',
                            data: data.user_growth.investors,
                            borderColor: dealroomAdmin.colors.secondary,
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        // Pipeline Chart
        if (data.pipeline) {
            const pipelineCtx = document.getElementById('pipeline-chart').getContext('2d');
            if (window.pipelineChart) {
                window.pipelineChart.destroy();
            }
            window.pipelineChart = new Chart(pipelineCtx, {
                type: 'bar',
                data: {
                    labels: data.pipeline.stages,
                    datasets: [{
                        label: 'Deals',
                        data: data.pipeline.counts,
                        backgroundColor: dealroomAdmin.colors.primary
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
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }
    }

    function formatNumber(num) {
        if (!num) return '0';
        return new Intl.NumberFormat().format(num);
    }
});