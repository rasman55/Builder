$(document).ready(function() {
    // Smooth scrolling for navigation links
    $('.nav-link').on('click', function(e) {
        if (this.hash !== "") {
            e.preventDefault();
            var hash = this.hash;
            
            $('html, body').animate({
                scrollTop: $(hash).offset().top - 80
            }, 800);
            
            // Update active nav link
            $('.nav-link').removeClass('active');
            $(this).addClass('active');
        }
    });

    // Form validation
    function validateForm(formSelector) {
        let isValid = true;
        $(formSelector + ' .form-control[required]').each(function() {
            if ($(this).val().trim() === '') {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        return isValid;
    }

    // Login form handling
    $('#loginForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm('#loginForm')) {
            showAlert('Please fill in all required fields.', 'error');
            return;
        }

        const formData = {
            user_type: $('#user_type').val(),
            id_number: $('#id_number').val(),
            password: $('#password').val(),
            action: 'login'
        };

        $.ajax({
            url: 'includes/auth.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            beforeSend: function() {
                showSpinner();
            },
            success: function(response) {
                hideSpinner();
                if (response.success) {
                    showAlert(response.message, 'success');
                    setTimeout(() => {
                        window.location.href = response.redirect;
                    }, 1500);
                } else {
                    showAlert(response.message, 'error');
                }
            },
            error: function() {
                hideSpinner();
                showAlert('An error occurred. Please try again.', 'error');
            }
        });
    });

    // Registration form handling
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm('#registerForm')) {
            showAlert('Please fill in all required fields.', 'error');
            return;
        }

        // Password confirmation check
        if ($('#password').val() !== $('#confirm_password').val()) {
            showAlert('Passwords do not match.', 'error');
            return;
        }

        const formData = new FormData(this);
        formData.append('action', 'register');

        $.ajax({
            url: 'includes/auth.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function() {
                showSpinner();
            },
            success: function(response) {
                hideSpinner();
                if (response.success) {
                    showAlert(response.message, 'success');
                    $('#registerForm')[0].reset();
                } else {
                    showAlert(response.message, 'error');
                }
            },
            error: function() {
                hideSpinner();
                showAlert('An error occurred. Please try again.', 'error');
            }
        });
    });

    // Medical record form handling
    $('#medicalRecordForm').on('submit', function(e) {
        e.preventDefault();
        
        if (!validateForm('#medicalRecordForm')) {
            showAlert('Please fill in all required fields.', 'error');
            return;
        }

        const formData = new FormData(this);
        formData.append('action', 'add_record');

        $.ajax({
            url: 'includes/records.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            beforeSend: function() {
                showSpinner();
            },
            success: function(response) {
                hideSpinner();
                if (response.success) {
                    showAlert(response.message, 'success');
                    $('#medicalRecordForm')[0].reset();
                    loadPatientRecords();
                } else {
                    showAlert(response.message, 'error');
                }
            },
            error: function() {
                hideSpinner();
                showAlert('An error occurred. Please try again.', 'error');
            }
        });
    });

    // Search functionality
    $('#searchForm').on('submit', function(e) {
        e.preventDefault();
        performSearch();
    });

    $('#searchInput').on('keyup', debounce(function() {
        if ($(this).val().length > 2) {
            performSearch();
        }
    }, 300));

    function performSearch() {
        const searchData = {
            query: $('#searchInput').val(),
            search_type: $('#searchType').val(),
            date_from: $('#dateFrom').val(),
            date_to: $('#dateTo').val(),
            action: 'search'
        };

        $.ajax({
            url: 'includes/search.php',
            type: 'POST',
            data: searchData,
            dataType: 'json',
            beforeSend: function() {
                $('#searchResults').html('<div class="spinner"></div>');
            },
            success: function(response) {
                if (response.success) {
                    displaySearchResults(response.data);
                } else {
                    $('#searchResults').html('<p>No results found.</p>');
                }
            },
            error: function() {
                $('#searchResults').html('<p>Error performing search.</p>');
            }
        });
    }

    function displaySearchResults(data) {
        let html = '<div class="search-results">';
        
        if (data.length === 0) {
            html += '<p>No results found.</p>';
        } else {
            html += '<table class="table">';
            html += '<thead><tr><th>Date</th><th>Type</th><th>Doctor</th><th>Patient</th><th>Reference</th><th>Actions</th></tr></thead>';
            html += '<tbody>';
            
            data.forEach(function(record) {
                html += `<tr>
                    <td>${record.date}</td>
                    <td>${record.type}</td>
                    <td>${record.doctor_name}</td>
                    <td>${record.patient_name}</td>
                    <td>${record.reference_number}</td>
                    <td>
                        <button class="btn btn-primary btn-sm view-record" data-id="${record.id}">View</button>
                    </td>
                </tr>`;
            });
            
            html += '</tbody></table>';
        }
        
        html += '</div>';
        $('#searchResults').html(html);
    }

    // View record modal
    $(document).on('click', '.view-record', function() {
        const recordId = $(this).data('id');
        loadRecordDetails(recordId);
    });

    function loadRecordDetails(recordId) {
        $.ajax({
            url: 'includes/records.php',
            type: 'POST',
            data: { action: 'get_record', record_id: recordId },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showRecordModal(response.data);
                } else {
                    showAlert('Error loading record details.', 'error');
                }
            },
            error: function() {
                showAlert('Error loading record details.', 'error');
            }
        });
    }

    function showRecordModal(record) {
        const modalHtml = `
            <div class="modal-overlay" id="recordModal">
                <div class="modal">
                    <div class="modal-header">
                        <h3>Medical Record Details</h3>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="record-details">
                            <p><strong>Date:</strong> ${record.date}</p>
                            <p><strong>Type:</strong> ${record.type}</p>
                            <p><strong>Doctor:</strong> ${record.doctor_name}</p>
                            <p><strong>Patient:</strong> ${record.patient_name}</p>
                            <p><strong>Reference:</strong> ${record.reference_number}</p>
                            <p><strong>Complaints:</strong> ${record.complaints}</p>
                            <p><strong>Diagnosis:</strong> ${record.diagnosis}</p>
                            <p><strong>Treatment:</strong> ${record.treatment}</p>
                            <p><strong>Notes:</strong> ${record.notes}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        $('#recordModal').fadeIn();
    }

    // Close modal
    $(document).on('click', '.modal-close, .modal-overlay', function(e) {
        if (e.target === this) {
            $('.modal-overlay').fadeOut(function() {
                $(this).remove();
            });
        }
    });

    // File upload preview
    $('.file-input').on('change', function() {
        const file = this.files[0];
        const preview = $(this).siblings('.file-preview');
        
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (file.type.startsWith('image/')) {
                    preview.html(`<img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 200px;">`);
                } else {
                    preview.html(`<p>File selected: ${file.name}</p>`);
                }
            };
            reader.readAsDataURL(file);
        }
    });

    // Dashboard navigation tabs
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        const target = $(this).attr('href');
        
        $('.nav-tab').removeClass('active');
        $(this).addClass('active');
        
        $('.tab-content').hide();
        $(target).show();
    });

    // Load dashboard data
    function loadDashboardData() {
        if ($('.dashboard').length > 0) {
            $.ajax({
                url: 'includes/dashboard.php',
                type: 'POST',
                data: { action: 'load_data' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        updateDashboard(response.data);
                    }
                }
            });
        }
    }

    function updateDashboard(data) {
        if (data.stats) {
            $('.total-patients').text(data.stats.total_patients);
            $('.total-doctors').text(data.stats.total_doctors);
            $('.total-records').text(data.stats.total_records);
            $('.recent-activities').text(data.stats.recent_activities);
        }
    }

    // Utility functions
    function showAlert(message, type) {
        const alertHtml = `<div class="alert alert-${type}">${message}</div>`;
        $('.alert').remove();
        if ($('.form-container').length > 0) {
            $('.form-container').prepend(alertHtml);
        } else {
            $('.container').first().prepend(alertHtml);
        }
        
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }

    function showSpinner() {
        if ($('.spinner').length === 0) {
            $('body').append('<div class="spinner-overlay"><div class="spinner"></div></div>');
        }
    }

    function hideSpinner() {
        $('.spinner-overlay').remove();
    }

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

    // Date picker initialization
    if ($('.date-picker').length > 0) {
        $('.date-picker').each(function() {
            $(this).attr('type', 'date');
        });
    }

    // Initialize dashboard on page load
    loadDashboardData();
    
    // Auto-refresh dashboard data every 30 seconds
    setInterval(loadDashboardData, 30000);

    // Form field validation styling
    $('.form-control').on('blur', function() {
        if ($(this).attr('required') && $(this).val().trim() === '') {
            $(this).addClass('error');
        } else {
            $(this).removeClass('error');
        }
    });

    // Password strength indicator
    $('#password').on('keyup', function() {
        const password = $(this).val();
        const strength = checkPasswordStrength(password);
        updatePasswordStrength(strength);
    });

    function checkPasswordStrength(password) {
        let score = 0;
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        
        return score;
    }

    function updatePasswordStrength(score) {
        const indicator = $('.password-strength');
        if (indicator.length === 0) return;
        
        const levels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
        const colors = ['#e74c3c', '#e67e22', '#f39c12', '#2ecc71', '#27ae60'];
        
        indicator.text(levels[score - 1] || 'Very Weak');
        indicator.css('color', colors[score - 1] || colors[0]);
    }
});