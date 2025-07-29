// Goba Hospital Patient Record Management System - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initializeComponents();
    initializeEventListeners();
    initializeAnimations();
});

// Initialize all components
function initializeComponents() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Initialize date pickers
    initializeDatePickers();

    // Initialize file uploads
    initializeFileUploads();

    // Initialize audio recording
    initializeAudioRecording();

    // Initialize payment methods
    initializePaymentMethods();
}

// Initialize event listeners
function initializeEventListeners() {
    // Mobile sidebar toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    // Close sidebar when clicking outside
    document.addEventListener('click', function(e) {
        if (sidebar && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
            sidebar.classList.remove('show');
        }
    });

    // Form validation
    initializeFormValidation();

    // Search functionality
    initializeSearch();

    // Modal events
    initializeModals();
}

// Initialize animations
function initializeAnimations() {
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card, .portal-card, .service-card');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    });

    cards.forEach(card => observer.observe(card));
}

// Initialize date pickers
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"], .date-picker');
    
    dateInputs.forEach(input => {
        // Set default date to today if not specified
        if (!input.value) {
            const today = new Date().toISOString().split('T')[0];
            input.value = today;
        }
    });
}

// Initialize file uploads
function initializeFileUploads() {
    const fileUploads = document.querySelectorAll('.file-upload');
    
    fileUploads.forEach(upload => {
        const input = upload.querySelector('input[type="file"]');
        const label = upload.querySelector('.file-label');
        
        if (input && label) {
            input.addEventListener('change', function() {
                if (this.files.length > 0) {
                    label.textContent = this.files[0].name;
                    upload.classList.add('has-file');
                } else {
                    label.textContent = 'Choose file or drag here';
                    upload.classList.remove('has-file');
                }
            });

            // Drag and drop functionality
            upload.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            upload.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            upload.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                if (e.dataTransfer.files.length > 0) {
                    input.files = e.dataTransfer.files;
                    label.textContent = e.dataTransfer.files[0].name;
                    upload.classList.add('has-file');
                }
            });
        }
    });
}

// Initialize audio recording
function initializeAudioRecording() {
    const recordButtons = document.querySelectorAll('.record-audio');
    
    recordButtons.forEach(button => {
        let mediaRecorder;
        let audioChunks = [];
        
        button.addEventListener('click', function() {
            if (mediaRecorder && mediaRecorder.state === 'recording') {
                // Stop recording
                mediaRecorder.stop();
                button.innerHTML = '<i class="fas fa-microphone"></i> Start Recording';
                button.classList.remove('btn-danger');
                button.classList.add('btn-primary');
            } else {
                // Start recording
                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(stream => {
                        mediaRecorder = new MediaRecorder(stream);
                        audioChunks = [];
                        
                        mediaRecorder.ondataavailable = function(event) {
                            audioChunks.push(event.data);
                        };
                        
                        mediaRecorder.onstop = function() {
                            const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                            const audioUrl = URL.createObjectURL(audioBlob);
                            
                            // Create audio player
                            const audioPlayer = document.createElement('audio');
                            audioPlayer.controls = true;
                            audioPlayer.src = audioUrl;
                            audioPlayer.className = 'audio-player mt-3';
                            
                            // Add to form
                            const container = button.closest('.form-group');
                            const existingPlayer = container.querySelector('.audio-player');
                            if (existingPlayer) {
                                existingPlayer.remove();
                            }
                            container.appendChild(audioPlayer);
                            
                            // Add hidden input for form submission
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'audio_data';
                            input.value = audioUrl;
                            container.appendChild(input);
                        };
                        
                        mediaRecorder.start();
                        button.innerHTML = '<i class="fas fa-stop"></i> Stop Recording';
                        button.classList.remove('btn-primary');
                        button.classList.add('btn-danger');
                    })
                    .catch(error => {
                        console.error('Error accessing microphone:', error);
                        showAlert('Error accessing microphone. Please check permissions.', 'danger');
                    });
            }
        });
    });
}

// Initialize payment methods
function initializePaymentMethods() {
    const paymentMethods = document.querySelectorAll('.payment-method');
    
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            // Remove selected class from all methods
            paymentMethods.forEach(m => m.classList.remove('selected'));
            
            // Add selected class to clicked method
            this.classList.add('selected');
            
            // Update hidden input
            const input = document.querySelector('input[name="payment_method"]');
            if (input) {
                input.value = this.dataset.method;
            }
        });
    });
}

// Initialize form validation
function initializeFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
}

// Initialize search functionality
function initializeSearch() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = this.closest('.card').querySelector('table');
            
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            }
        });
    });
}

// Initialize modals
function initializeModals() {
    const modals = document.querySelectorAll('.modal');
    
    modals.forEach(modal => {
        modal.addEventListener('show.bs.modal', function() {
            // Add loading state if needed
            const loadingElement = this.querySelector('.loading');
            if (loadingElement) {
                loadingElement.style.display = 'block';
            }
        });
        
        modal.addEventListener('shown.bs.modal', function() {
            // Hide loading state
            const loadingElement = this.querySelector('.loading');
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }
        });
    });
}

// Utility functions
function showAlert(message, type = 'info') {
    const alertContainer = document.querySelector('.alert-container') || document.body;
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

function showLoading() {
    const spinner = document.createElement('div');
    spinner.className = 'spinner';
    spinner.id = 'loading-spinner';
    document.body.appendChild(spinner);
}

function hideLoading() {
    const spinner = document.getElementById('loading-spinner');
    if (spinner) {
        spinner.remove();
    }
}

function formatDate(date) {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

function formatDateTime(dateTime) {
    return new Date(dateTime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function generateReferenceNumber() {
    const timestamp = Date.now();
    const random = Math.floor(Math.random() * 1000);
    return `REF${timestamp}${random}`;
}

// AJAX utility functions
function makeRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } catch (e) {
                    resolve(xhr.responseText);
                }
            } else {
                reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
            }
        };
        
        xhr.onerror = function() {
            reject(new Error('Network error'));
        };
        
        if (data) {
            xhr.send(JSON.stringify(data));
        } else {
            xhr.send();
        }
    });
}

// Export functions for use in other scripts
window.HospitalSystem = {
    showAlert,
    showLoading,
    hideLoading,
    formatDate,
    formatDateTime,
    generateReferenceNumber,
    makeRequest
};