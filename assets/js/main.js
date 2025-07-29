/**
 * Goba Hospital Patient Record Management System
 * Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initAnimations();
    initFormValidation();
    initAudioRecorder();
    initFileUpload();
    initSearchFunctionality();
    initPaymentProcessing();
    initResponsiveMenu();
    initTooltips();
    initModals();
});

/**
 * Initialize smooth scrolling and animations
 */
function initAnimations() {
    // Smooth scrolling for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Intersection Observer for fade-in animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, observerOptions);

    // Observe elements for animation
    document.querySelectorAll('.portal-card, .service-card, .feature-box').forEach(el => {
        observer.observe(el);
    });
}

/**
 * Initialize form validation
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                showAlert('Please correct the errors in the form.', 'danger');
            }
        });

        // Real-time validation
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
        });
    });
}

/**
 * Validate a single form field
 */
function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    const pattern = field.getAttribute('pattern');
    const minLength = field.getAttribute('minlength');
    const maxLength = field.getAttribute('maxlength');

    // Remove existing error styling
    field.classList.remove('is-invalid');
    const errorElement = field.parentNode.querySelector('.invalid-feedback');
    if (errorElement) {
        errorElement.remove();
    }

    // Required field validation
    if (required && !value) {
        showFieldError(field, 'This field is required.');
        return false;
    }

    // Email validation
    if (type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            showFieldError(field, 'Please enter a valid email address.');
            return false;
        }
    }

    // Phone validation
    if (type === 'tel' && value) {
        const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
        if (!phoneRegex.test(value.replace(/\s/g, ''))) {
            showFieldError(field, 'Please enter a valid phone number.');
            return false;
        }
    }

    // Pattern validation
    if (pattern && value) {
        const regex = new RegExp(pattern);
        if (!regex.test(value)) {
            showFieldError(field, field.getAttribute('data-error') || 'Invalid format.');
            return false;
        }
    }

    // Length validation
    if (minLength && value.length < parseInt(minLength)) {
        showFieldError(field, `Minimum ${minLength} characters required.`);
        return false;
    }

    if (maxLength && value.length > parseInt(maxLength)) {
        showFieldError(field, `Maximum ${maxLength} characters allowed.`);
        return false;
    }

    // SSN/NID validation
    if (field.name === 'ssn' && value) {
        if (value.length < 5 || value.length > 50) {
            showFieldError(field, 'SSN/NID must be between 5 and 50 characters.');
            return false;
        }
    }

    return true;
}

/**
 * Show field error
 */
function showFieldError(field, message) {
    field.classList.add('is-invalid');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

/**
 * Validate entire form
 */
function validateForm(form) {
    const fields = form.querySelectorAll('input, select, textarea');
    let isValid = true;

    fields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });

    return isValid;
}

/**
 * Initialize audio recorder for consultations
 */
function initAudioRecorder() {
    const recordButton = document.getElementById('recordButton');
    const audioPlayer = document.getElementById('audioPlayer');
    const audioFileInput = document.getElementById('audioFile');

    if (recordButton) {
        let mediaRecorder;
        let audioChunks = [];

        recordButton.addEventListener('click', async function() {
            if (recordButton.textContent.includes('Start')) {
                // Start recording
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    mediaRecorder = new MediaRecorder(stream);
                    
                    mediaRecorder.ondataavailable = (event) => {
                        audioChunks.push(event.data);
                    };

                    mediaRecorder.onstop = () => {
                        const audioBlob = new Blob(audioChunks, { type: 'audio/wav' });
                        const audioUrl = URL.createObjectURL(audioBlob);
                        
                        if (audioPlayer) {
                            audioPlayer.src = audioUrl;
                            audioPlayer.style.display = 'block';
                        }

                        // Create file input for form submission
                        if (audioFileInput) {
                            const file = new File([audioBlob], 'consultation_audio.wav', { type: 'audio/wav' });
                            const dataTransfer = new DataTransfer();
                            dataTransfer.items.add(file);
                            audioFileInput.files = dataTransfer.files;
                        }
                    };

                    mediaRecorder.start();
                    recordButton.innerHTML = '<i class="fas fa-stop"></i> Stop Recording';
                    recordButton.classList.remove('btn-primary');
                    recordButton.classList.add('btn-danger');
                } catch (error) {
                    showAlert('Error accessing microphone: ' + error.message, 'danger');
                }
            } else {
                // Stop recording
                mediaRecorder.stop();
                recordButton.innerHTML = '<i class="fas fa-microphone"></i> Start Recording';
                recordButton.classList.remove('btn-danger');
                recordButton.classList.add('btn-primary');
            }
        });
    }
}

/**
 * Initialize file upload functionality
 */
function initFileUpload() {
    const fileInputs = document.querySelectorAll('.file-upload');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const preview = this.parentNode.querySelector('.file-preview');
            const label = this.parentNode.querySelector('.file-label');
            
            if (file) {
                // Update label
                if (label) {
                    label.textContent = file.name;
                }

                // Show preview for images
                if (file.type.startsWith('image/') && preview) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`;
                    };
                    reader.readAsDataURL(file);
                } else if (preview) {
                    preview.innerHTML = `<i class="fas fa-file fa-3x text-primary"></i><br><small>${file.name}</small>`;
                }

                // Validate file size (max 10MB)
                if (file.size > 10 * 1024 * 1024) {
                    showAlert('File size must be less than 10MB.', 'warning');
                    this.value = '';
                    if (preview) preview.innerHTML = '';
                    if (label) label.textContent = 'Choose file';
                }
            }
        });
    });
}

/**
 * Initialize search functionality
 */
function initSearchFunctionality() {
    const searchInputs = document.querySelectorAll('.search-input');
    
    searchInputs.forEach(input => {
        let timeout;
        
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            const query = this.value.trim();
            
            if (query.length >= 2) {
                timeout = setTimeout(() => {
                    performSearch(query, this.dataset.searchType);
                }, 300);
            } else {
                clearSearchResults(this);
            }
        });
    });
}

/**
 * Perform search
 */
function performSearch(query, type) {
    const searchContainer = document.querySelector('.search-results');
    
    if (!searchContainer) return;

    // Show loading
    searchContainer.innerHTML = '<div class="text-center"><div class="spinner"></div></div>';

    // Simulate API call (replace with actual AJAX call)
    setTimeout(() => {
        // This would be replaced with actual search logic
        searchContainer.innerHTML = '<div class="alert alert-info">Search functionality will be implemented with backend integration.</div>';
    }, 1000);
}

/**
 * Clear search results
 */
function clearSearchResults(input) {
    const searchContainer = document.querySelector('.search-results');
    if (searchContainer) {
        searchContainer.innerHTML = '';
    }
}

/**
 * Initialize payment processing
 */
function initPaymentProcessing() {
    const paymentForms = document.querySelectorAll('.payment-form');
    
    paymentForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const paymentMethod = formData.get('payment_method');
            const amount = formData.get('amount');
            
            // Show payment processing modal
            showPaymentModal(paymentMethod, amount);
        });
    });
}

/**
 * Show payment modal
 */
function showPaymentModal(method, amount) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Processing Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <div class="spinner mb-3"></div>
                        <p>Processing payment of ${amount} via ${method}...</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Simulate payment processing
    setTimeout(() => {
        bsModal.hide();
        showAlert('Payment processed successfully!', 'success');
        modal.remove();
    }, 3000);
}

/**
 * Initialize responsive menu
 */
function initResponsiveMenu() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navbarToggler.contains(e.target) && !navbarCollapse.contains(e.target)) {
                if (navbarCollapse.classList.contains('show')) {
                    navbarToggler.click();
                }
            }
        });
    }
}

/**
 * Initialize tooltips
 */
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

/**
 * Initialize modals
 */
function initModals() {
    const modalTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="modal"]'));
    modalTriggerList.map(function (modalTriggerEl) {
        return new bootstrap.Modal(modalTriggerEl);
    });
}

/**
 * Show alert message
 */
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alertContainer') || createAlertContainer();
    
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.appendChild(alert);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alert.parentNode) {
            alert.remove();
        }
    }, 5000);
}

/**
 * Create alert container if it doesn't exist
 */
function createAlertContainer() {
    const container = document.createElement('div');
    container.id = 'alertContainer';
    container.className = 'position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-ET', {
        style: 'currency',
        currency: 'ETB'
    }).format(amount);
}

/**
 * Format date
 */
function formatDate(date) {
    return new Intl.DateTimeFormat('en-ET', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(new Date(date));
}

/**
 * Format datetime
 */
function formatDateTime(datetime) {
    return new Intl.DateTimeFormat('en-ET', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(datetime));
}

/**
 * Generate reference number
 */
function generateReferenceNumber() {
    const date = new Date();
    const timestamp = date.getTime().toString().slice(-6);
    const random = Math.random().toString(36).substr(2, 4).toUpperCase();
    return `REF-${date.getFullYear()}${(date.getMonth() + 1).toString().padStart(2, '0')}${date.getDate().toString().padStart(2, '0')}-${timestamp}-${random}`;
}

/**
 * Export table to CSV
 */
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const rows = table.querySelectorAll('tr');
    let csv = [];

    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = [];
        cols.forEach(col => {
            rowData.push('"' + col.textContent.replace(/"/g, '""') + '"');
        });
        csv.push(rowData.join(','));
    });

    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

/**
 * Print element
 */
function printElement(elementId) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Print</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @media print {
                        .no-print { display: none !important; }
                    }
                </style>
            </head>
            <body>
                ${element.outerHTML}
            </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

// Global utility functions
window.GobaHospital = {
    showAlert,
    formatCurrency,
    formatDate,
    formatDateTime,
    generateReferenceNumber,
    exportTableToCSV,
    printElement
};