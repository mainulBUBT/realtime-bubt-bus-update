@extends('layouts.app')

@section('title', 'BUBT Bus Tracker')

@section('content')
<!-- Home Screen -->
<div id="home-screen" class="screen active-screen">
    @livewire('bus-list')
</div>

<!-- Include Holiday Screen Component -->
@include('components.holiday-screen')

<!-- On This Bus Confirmation Modal -->
<div id="on-this-bus-modal" class="modal">
    <div class="confirmation-content">
        <div class="confirmation-header">
            <div class="bus-icon-large">
                <i class="bi bi-bus-front"></i>
            </div>
            <h3>Are you on this bus?</h3>
            <div class="confirmation-close">&times;</div>
        </div>
        <div class="confirmation-body">
            <div class="confirmation-bus-details">
                <div class="bus-badge-large"><span class="bus-id-badge">B1</span></div>
                <div class="bus-info-large">
                    <div class="bus-name-large"><span class="on-this-bus-bus-name">Buriganga</span></div>
                    <div class="bus-status-large"><span class="status-dot active"></span> On Route</div>
                </div>
            </div>
            <p class="confirmation-message">Confirming your presence on this bus will help other students track its
                location accurately and improve arrival time predictions.</p>
            <div class="confirmation-note">
                <i class="bi bi-info-circle"></i>
                <span>Your location will only be shared while you're on the bus</span>
            </div>
        </div>
        <div class="confirmation-buttons">
            <button class="confirmation-btn yes-btn on-this-bus-yes">
                <i class="bi bi-check-circle"></i>
                Yes, I'm on this bus
            </button>
            <button class="confirmation-btn no-btn on-this-bus-no">
                <i class="bi bi-x-circle"></i>
                No, cancel
            </button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Modal styles for "On This Bus" confirmation */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(0,0,0,0.5);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.confirmation-content {
    background: white;
    border-radius: 16px;
    max-width: 400px;
    width: 100%;
    overflow: hidden;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.confirmation-header {
    text-align: center;
    padding: 2rem 1.5rem 1rem;
    position: relative;
}

.bus-icon-large {
    font-size: 3rem;
    color: #007bff;
    margin-bottom: 1rem;
}

.confirmation-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: #212529;
}

.confirmation-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 1.5rem;
    cursor: pointer;
    color: #6c757d;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.confirmation-close:hover {
    background: #f8f9fa;
    color: #495057;
}

.confirmation-body {
    padding: 0 1.5rem 1rem;
}

.confirmation-bus-details {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.bus-badge-large {
    width: 50px;
    height: 50px;
    background: #007bff;
    color: white;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
}

.bus-info-large {
    flex: 1;
}

.bus-name-large {
    font-size: 1.1rem;
    font-weight: 600;
    color: #212529;
    margin-bottom: 0.25rem;
}

.bus-status-large {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #6c757d;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #28a745;
}

.confirmation-message {
    font-size: 0.95rem;
    color: #495057;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.confirmation-note {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: rgba(0,123,255,0.1);
    border-radius: 6px;
    font-size: 0.85rem;
    color: #0056b3;
}

.confirmation-buttons {
    padding: 1rem 1.5rem 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.confirmation-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.yes-btn {
    background: #28a745;
    color: white;
}

.yes-btn:hover {
    background: #218838;
}

.no-btn {
    background: #f8f9fa;
    color: #6c757d;
    border: 1px solid #dee2e6;
}

.no-btn:hover {
    background: #e9ecef;
    color: #495057;
}

/* Screen switching styles */
.screen {
    display: none;
}

.screen.active-screen {
    display: block;
}

/* Responsive modal */
@media (max-width: 576px) {
    .confirmation-content {
        margin: 1rem;
        max-width: none;
    }
    
    .confirmation-header {
        padding: 1.5rem 1rem 0.5rem;
    }
    
    .confirmation-body {
        padding: 0 1rem 0.5rem;
    }
    
    .confirmation-buttons {
        padding: 0.5rem 1rem 1rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
// Initialize screen switching functionality
document.addEventListener('DOMContentLoaded', function() {
    initScreenSwitching();
    initConfirmationModal();
});

document.addEventListener('livewire:navigated', function() {
    initScreenSwitching();
    initConfirmationModal();
});

function initScreenSwitching() {
    // Handle navigation items for screen switching
    const navItems = document.querySelectorAll('.nav-item[data-screen], .drawer-item[data-screen]');
    
    navItems.forEach(item => {
        item.addEventListener('click', function(e) {
            const targetScreen = this.getAttribute('data-screen');
            
            if (targetScreen) {
                e.preventDefault();
                
                // Hide all screens
                document.querySelectorAll('.screen').forEach(screen => {
                    screen.classList.remove('active-screen');
                });
                
                // Show target screen
                const screen = document.getElementById(targetScreen);
                if (screen) {
                    screen.classList.add('active-screen');
                }
                
                // Update active navigation
                navItems.forEach(navItem => {
                    navItem.classList.remove('active');
                });
                this.classList.add('active');
                
                // Close drawer if open
                if (window.livewireApp) {
                    window.livewireApp.closeDrawer();
                }
            }
        });
    });
}

function initConfirmationModal() {
    const modal = document.getElementById('on-this-bus-modal');
    const closeBtn = modal.querySelector('.confirmation-close');
    const yesBtn = modal.querySelector('.on-this-bus-yes');
    const noBtn = modal.querySelector('.on-this-bus-no');
    
    // Close modal handlers
    const closeModal = () => {
        modal.style.display = 'none';
    };
    
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (noBtn) noBtn.addEventListener('click', closeModal);
    
    // Close on overlay click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    // Handle yes button
    if (yesBtn) {
        yesBtn.addEventListener('click', function() {
            // Handle "I'm on this bus" confirmation
            const busId = modal.querySelector('.bus-id-badge').textContent;
            console.log('User confirmed they are on bus:', busId);
            
            // Store confirmation and navigate to tracking
            localStorage.setItem('trackingBusId', busId);
            localStorage.setItem('userOnBus', 'true');
            
            closeModal();
            
            // Navigate to tracking page
            window.location.href = `/track/${busId}`;
        });
    }
}

// Function to show confirmation modal
function showOnThisBusModal(busId, busName) {
    const modal = document.getElementById('on-this-bus-modal');
    const busIdBadge = modal.querySelector('.bus-id-badge');
    const busNameSpan = modal.querySelector('.on-this-bus-bus-name');
    
    if (busIdBadge) busIdBadge.textContent = busId;
    if (busNameSpan) busNameSpan.textContent = busName;
    
    modal.style.display = 'flex';
}

// Make function globally available
window.showOnThisBusModal = showOnThisBusModal;
</script>
@endpush