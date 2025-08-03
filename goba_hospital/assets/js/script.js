// Custom JavaScript for Goba Hospital Patient Record Management System

document.addEventListener('DOMContentLoaded', function() {
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

    // File upload drag and drop functionality
    const fileUploads = document.querySelectorAll('.file-upload');
    fileUploads.forEach(function(upload) {
        const input = upload.querySelector('input[type="file"]');
        
        upload.addEventListener('dragover', function(e) {
            e.preventDefault();
            upload.classList.add('dragover');
        });
        
        upload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            upload.classList.remove('dragover');
        });
        
        upload.addEventListener('drop', function(e) {
            e.preventDefault();
            upload.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                input.files = files;
                updateFileList(input);
            }
        });
        
        input.addEventListener('change', function() {
            updateFileList(this);
        });
    });

    // Update file list display
    function updateFileList(input) {
        const fileList = input.parentElement.querySelector('.file-list');
        if (fileList) {
            fileList.innerHTML = '';
            Array.from(input.files).forEach(function(file) {
                const fileItem = document.createElement('div');
                fileItem.className = 'file-item d-flex align-items-center p-2 border rounded mb-2';
                fileItem.innerHTML = `
                    <i class="fas fa-file me-2"></i>
                    <span class="flex-grow-1">${file.name}</span>
                    <small class="text-muted">${formatFileSize(file.size)}</small>
                `;
                fileList.appendChild(fileItem);
            });
        }
    }

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Search functionality
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = this.closest('.card').querySelector('table');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(function(row) {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    });

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Print functionality
    const printButtons = document.querySelectorAll('.btn-print');
    printButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            window.print();
        });
    });

    // Export to PDF functionality
    const exportButtons = document.querySelectorAll('.btn-export');
    exportButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const table = this.closest('.card').querySelector('table');
            exportTableToPDF(table);
        });
    });

    // Export table to PDF
    function exportTableToPDF(table) {
        // This would require a PDF library like jsPDF
        // For now, we'll just show an alert
        alert('PDF export functionality would be implemented here with a PDF library.');
    }

    // Audio recording functionality
    const recordButtons = document.querySelectorAll('.btn-record');
    recordButtons.forEach(function(button) {
        let mediaRecorder;
        let audioChunks = [];
        
        button.addEventListener('click', function() {
            if (this.classList.contains('recording')) {
                // Stop recording
                mediaRecorder.stop();
                this.classList.remove('recording');
                this.innerHTML = '<i class="fas fa-microphone"></i> Start Recording';
            } else {
                // Start recording
                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(function(stream) {
                        mediaRecorder = new MediaRecorder(stream);
                        audioChunks = [];
                        
                        mediaRecorder.addEventListener('dataavailable', function(event) {
                            audioChunks.push(event.data);
                        });
                        
                        mediaRecorder.addEventListener('stop', function() {
                            const audioBlob = new Blob(audioChunks);
                            const audioUrl = URL.createObjectURL(audioBlob);
                            
                            // Create audio player
                            const audioPlayer = document.createElement('div');
                            audioPlayer.className = 'audio-player mt-3';
                            audioPlayer.innerHTML = `
                                <audio controls>
                                    <source src="${audioUrl}" type="audio/wav">
                                    Your browser does not support the audio element.
                                </audio>
                                <button class="btn btn-sm btn-danger mt-2" onclick="this.parentElement.remove()">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            `;
                            
                            button.parentElement.appendChild(audioPlayer);
                        });
                        
                        mediaRecorder.start();
                        button.classList.add('recording');
                        button.innerHTML = '<i class="fas fa-stop"></i> Stop Recording';
                    })
                    .catch(function(error) {
                        alert('Error accessing microphone: ' + error.message);
                    });
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Auto-save form data
    const autoSaveForms = document.querySelectorAll('.auto-save');
    autoSaveForms.forEach(function(form) {
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                saveFormData(form);
            });
        });
    });

    // Save form data to localStorage
    function saveFormData(form) {
        const formData = new FormData(form);
        const data = {};
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        localStorage.setItem('form_' + form.id, JSON.stringify(data));
    }

    // Load saved form data
    function loadFormData(form) {
        const saved = localStorage.getItem('form_' + form.id);
        if (saved) {
            const data = JSON.parse(saved);
            for (let key in data) {
                const input = form.querySelector('[name="' + key + '"]');
                if (input) {
                    input.value = data[key];
                }
            }
        }
    }

    // Load saved data for auto-save forms
    autoSaveForms.forEach(function(form) {
        loadFormData(form);
    });

    // Real-time search with debouncing
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    const realTimeSearchInputs = document.querySelectorAll('.real-time-search');
    realTimeSearchInputs.forEach(function(input) {
        const debouncedSearch = debounce(function(searchTerm) {
            // Perform AJAX search here
            console.log('Searching for:', searchTerm);
        }, 300);
        
        input.addEventListener('input', function() {
            debouncedSearch(this.value);
        });
    });

    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    // Global notification function
    window.showNotification = showNotification;

    // Loading states
    const loadingButtons = document.querySelectorAll('.btn-loading');
    loadingButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
            this.disabled = true;
            
            // Re-enable after 2 seconds (for demo purposes)
            setTimeout(function() {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        });
    });

    // Chart initialization (if Chart.js is available)
    if (typeof Chart !== 'undefined') {
        const chartCanvases = document.querySelectorAll('canvas[data-chart]');
        chartCanvases.forEach(function(canvas) {
            const chartType = canvas.dataset.chart;
            const chartData = JSON.parse(canvas.dataset.chartData || '{}');
            
            new Chart(canvas, {
                type: chartType,
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
    }

    // Sidebar toggle for mobile
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });

    // Smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
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

    // Initialize all components
    console.log('Goba Hospital Management System initialized successfully');
});