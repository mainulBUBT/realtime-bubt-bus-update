import './bootstrap';
import toastr from 'toastr';
import 'toastr/build/toastr.min.css';

// Import Bootstrap JS
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

// Configure Toastr
toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": false,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "5000",
    "extendedTimeOut": "1000",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
};

// Make toastr globally available
window.toastr = toastr;

// Listen for Livewire toastr events
document.addEventListener('livewire:init', () => {
    Livewire.on('toastr:success', (message) => {
        toastr.success(message);
    });
    
    Livewire.on('toastr:error', (message) => {
        toastr.error(message);
    });
    
    Livewire.on('toastr:info', (message) => {
        toastr.info(message);
    });
    
    Livewire.on('toastr:warning', (message) => {
        toastr.warning(message);
    });
});
