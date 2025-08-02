    </main>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-hospital me-2"></i><?php echo SITE_NAME; ?></h5>
                    <p class="mb-0">Providing comprehensive healthcare record management solutions.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Goba Hospital. All rights reserved.</p>
                    <p class="mb-0">Contact: info@gobahospital.com | Phone: +251-123-456-789</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Custom JS -->
    <script src="../assets/js/script.js"></script>
    
    <script>
        // Initialize date pickers
        flatpickr(".date-picker", {
            dateFormat: "Y-m-d",
            allowInput: true
        });
        
        flatpickr(".datetime-picker", {
            dateFormat: "Y-m-d H:i",
            enableTime: true,
            allowInput: true
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>