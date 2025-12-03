document.addEventListener('DOMContentLoaded', () => {
    console.log('SSJ Mukdahan App Loaded');

    // Example: Fetch dashboard data if on dashboard
    const dashboardGrid = document.querySelector('.dashboard-grid');
    if (dashboardGrid) {
        loadDashboardData();
    }
});

async function loadDashboardData() {
    try {
        // Fetch health check as a sample metric
        const healthRes = await fetch('/api/v1/health');
        const healthData = await healthRes.json();

        // Update UI
        const systemStatus = document.getElementById('system-status');
        if (systemStatus) {
            systemStatus.textContent = healthData.data.status === 'ok' ? 'Online' : 'Issues Detected';
            systemStatus.style.color = healthData.data.status === 'ok' ? '#4ade80' : '#f87171';
        }

        // We could fetch more data here, e.g., population stats
        // For now, we'll simulate some loading animation
    } catch (error) {
        console.error('Failed to load dashboard data:', error);
    }
}
