<!-- Chuti Kobe Screen Component -->
<div id="chuti-screen" class="screen" style="display: none;">
    <!-- Modern Mobile Header -->
    <div class="mobile-header">
        <div class="header-top">
            <button class="menu-btn" id="chuti-menu-btn">
                <i class="bi bi-list"></i>
            </button>
            <div class="header-title">
                <h1>Chuti Kobe</h1>
                <span class="location-indicator">
                    <i class="bi bi-geo-alt"></i>
                    Dhaka, Bangladesh
                </span>
            </div>
            <button class="notification-btn" id="chuti-notification-btn">
                <i class="bi bi-bell"></i>
                <span class="notification-badge">3</span>
            </button>
        </div>

        <!-- Holiday Dropdown Selector -->
        <div class="bus-dropdown-container">
            <div class="bus-dropdown" id="holiday-dropdown">
                <div class="dropdown-header" id="holiday-dropdown-header">
                    <span>Select Holiday Type</span>
                    <i class="bi bi-chevron-down"></i>
                </div>
                <div class="dropdown-menu" id="holiday-dropdown-menu">
                    <div class="dropdown-item active" data-holiday-type="all">
                        <span>All Holidays</span>
                    </div>
                    <div class="dropdown-item" data-holiday-type="national">
                        <span>National Holidays</span>
                    </div>
                    <div class="dropdown-item" data-holiday-type="university">
                        <span>University Holidays</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="home-content">
        <div class="chuti-container">
            <div class="chuti-card">
                <div class="chuti-header">
                    <i class="bi bi-calendar-event"></i>
                    <h3>Upcoming Holidays</h3>
                </div>
                <div class="chuti-list">
                    <div class="chuti-item">
                        <div class="chuti-date">
                            <span class="day">25</span>
                            <span class="month">Dec</span>
                        </div>
                        <div class="chuti-info">
                            <h4>Christmas Day</h4>
                            <p>National Holiday</p>
                        </div>
                    </div>
                    <div class="chuti-item">
                        <div class="chuti-date">
                            <span class="day">31</span>
                            <span class="month">Dec</span>
                        </div>
                        <div class="chuti-info">
                            <h4>New Year's Eve</h4>
                            <p>University Closed</p>
                        </div>
                    </div>
                    <div class="chuti-item">
                        <div class="chuti-date">
                            <span class="day">21</span>
                            <span class="month">Feb</span>
                        </div>
                        <div class="chuti-info">
                            <h4>Language Day</h4>
                            <p>National Holiday</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Holiday screen specific styles */
.chuti-container {
    padding: 1rem;
}

.chuti-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.chuti-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.chuti-header i {
    font-size: 1.5rem;
}

.chuti-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.chuti-list {
    padding: 0;
}

.chuti-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s ease;
}

.chuti-item:last-child {
    border-bottom: none;
}

.chuti-item:hover {
    background-color: #f8f9fa;
}

.chuti-date {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 60px;
    height: 60px;
    background: #f8f9fa;
    border-radius: 8px;
    flex-shrink: 0;
}

.chuti-date .day {
    font-size: 1.5rem;
    font-weight: 700;
    color: #007bff;
    line-height: 1;
}

.chuti-date .month {
    font-size: 0.8rem;
    font-weight: 500;
    color: #6c757d;
    text-transform: uppercase;
}

.chuti-info h4 {
    margin: 0 0 0.25rem 0;
    font-size: 1rem;
    font-weight: 600;
    color: #212529;
}

.chuti-info p {
    margin: 0;
    font-size: 0.9rem;
    color: #6c757d;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .chuti-item {
        padding: 1rem;
    }
    
    .chuti-date {
        width: 50px;
        height: 50px;
    }
    
    .chuti-date .day {
        font-size: 1.25rem;
    }
    
    .chuti-info h4 {
        font-size: 0.9rem;
    }
    
    .chuti-info p {
        font-size: 0.8rem;
    }
}
</style>