// Main JavaScript for Rawinda Finance System

// Format Rupiah
function formatRupiah(angka) {
    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Calculate total from checkboxes
function calculateTotal(checkboxClass, displayId) {
    const checkboxes = document.querySelectorAll(`.${checkboxClass}`);
    let total = 0;
    checkboxes.forEach(cb => {
        if (cb.checked) {
            total += parseFloat(cb.value) || 0;
        }
    });
    const display = document.getElementById(displayId);
    if (display) {
        display.textContent = formatRupiah(total);
    }
    return total;
}

// Toggle modal visibility
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.toggle('hidden');
        modal.style.display = modal.classList.contains('hidden') ? 'none' : 'flex';
    }
}

// Confirm action
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// Format input as currency
function formatCurrency(input) {
    input.addEventListener('input', function() {
        let value = this.value.replace(/[^0-9]/g, '');
        if (value) {
            this.value = parseInt(value).toLocaleString('id-ID');
        }
    });
    
    input.addEventListener('focus', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
}

// Auto generate username from house number
function generateUsername(inputId, outputId) {
    const input = document.getElementById(inputId);
    const output = document.getElementById(outputId);
    if (input && output) {
        input.addEventListener('input', function() {
            const value = this.value.toLowerCase().replace(/[^a-z0-9]/g, '_');
            output.value = 'warga_' + value;
        });
    }
}

// Initialize date pickers
function initDatePickers() {
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = today;
        }
        
        // Set max date to today
        input.max = today;
    });
}

// File upload preview
function previewImage(inputId, previewId) {
    const input = document.getElementById(inputId);
    const preview = document.getElementById(previewId);
    
    if (input && preview) {
        input.addEventListener('change', function() {
            const file = this.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                preview.classList.add('hidden');
            }
        });
    }
}

// Show loading spinner
function showLoading(button) {
    const originalText = button.innerHTML;
    button.innerHTML = '<div class="spinner"></div>';
    button.disabled = true;
    return originalText;
}

// Hide loading spinner
function hideLoading(button, originalText) {
    button.innerHTML = originalText;
    button.disabled = false;
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showToast('Tersalin ke clipboard!');
    });
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-4 py-2 rounded-lg shadow-lg ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white z-50 alert-slide-in`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Validate file upload
function validateFile(input, maxSizeMB = 2, allowedTypes = ['image/jpeg', 'image/png', 'application/pdf']) {
    const file = input.files[0];
    if (!file) return true;
    
    // Check file size
    if (file.size > maxSizeMB * 1024 * 1024) {
        showToast(`Ukuran file maksimal ${maxSizeMB}MB`, 'error');
        input.value = '';
        return false;
    }
    
    // Check file type
    if (!allowedTypes.includes(file.type)) {
        showToast('Format file tidak didukung. Gunakan JPG, PNG, atau PDF.', 'error');
        input.value = '';
        return false;
    }
    
    return true;
}

// Search filter for tables
function filterTable(tableId, searchId) {
    const searchInput = document.getElementById(searchId);
    const table = document.getElementById(tableId);
    
    if (searchInput && table) {
        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });
    }
}

// Sort table by column
function sortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aText = a.children[columnIndex].textContent.trim();
        const bText = b.children[columnIndex].textContent.trim();
        
        // Try to parse as number
        const aNum = parseFloat(aText.replace(/[^0-9.-]+/g, ''));
        const bNum = parseFloat(bText.replace(/[^0-9.-]+/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return aNum - bNum;
        }
        
        // Otherwise sort as text
        return aText.localeCompare(bText);
    });
    
    // Clear and re-append sorted rows
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
}

// Export data to CSV
function exportToCSV(data, filename) {
    const csv = data.map(row => row.join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Print specific element
function printElement(elementId) {
    const printContent = document.getElementById(elementId);
    const originalContent = document.body.innerHTML;
    document.body.innerHTML = printContent.innerHTML;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    initDatePickers();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.fixed.top-4.right-4 > div');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(el => {
        el.addEventListener('mouseenter', showTooltip);
        el.addEventListener('mouseleave', hideTooltip);
    });
    
    // Initialize currency inputs
    const currencyInputs = document.querySelectorAll('.currency-input');
    currencyInputs.forEach(formatCurrency);
    
    // Add confirmation to delete links
    const deleteLinks = document.querySelectorAll('a[href*="delete"]');
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (!confirm('Yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });
});

// Tooltip functions
function showTooltip(e) {
    const tooltipText = this.getAttribute('data-tooltip');
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute z-50 px-2 py-1 text-xs text-white bg-gray-900 rounded shadow-lg';
    tooltip.textContent = tooltipText;
    tooltip.style.top = (this.getBoundingClientRect().top - 30) + 'px';
    tooltip.style.left = (this.getBoundingClientRect().left + this.offsetWidth / 2) + 'px';
    tooltip.style.transform = 'translateX(-50%)';
    tooltip.id = 'tooltip-' + Date.now();
    document.body.appendChild(tooltip);
}

function hideTooltip() {
    const tooltips = document.querySelectorAll('[id^="tooltip-"]');
    tooltips.forEach(tooltip => tooltip.remove());
}

// Calculate total iuran in settings
function calculateTotalIuran() {
    let total = 0;
    const inputs = document.querySelectorAll('input[name^="biaya_iuran"]');
    
    inputs.forEach(input => {
        const value = input.value.replace(/[^0-9]/g, '');
        total += parseInt(value) || 0;
    });
    
    const totalInput = document.getElementById('total_iuran');
    if (totalInput) {
        totalInput.value = total.toLocaleString('id-ID');
    }
}

// Export table to CSV
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const rowData = [];
        const cells = row.querySelectorAll('th, td');
        
        cells.forEach(cell => {
            // Remove buttons and icons
            const clone = cell.cloneNode(true);
            const buttons = clone.querySelectorAll('button, a, i');
            buttons.forEach(btn => btn.remove());
            
            rowData.push(clone.textContent.trim().replace(/,/g, ''));
        });
        
        csv.push(rowData);
    });
    
    exportToCSV(csv, filename);
}

// Generate PDF report (simplified)
function generatePDF() {
    showToast('Fitur PDF akan segera tersedia!');
}

// Auto logout after inactivity
function initAutoLogout(minutes = 30) {
    let timeout;
    
    function resetTimer() {
        clearTimeout(timeout);
        timeout = setTimeout(logout, minutes * 60 * 1000);
    }
    
    function logout() {
        if (confirm('Sesi Anda akan berakhir karena tidak ada aktivitas. Lanjutkan?')) {
            resetTimer();
        } else {
            window.location.href = 'logout.php';
        }
    }
    
    // Reset timer on user activity
    ['click', 'mousemove', 'keypress', 'scroll'].forEach(event => {
        document.addEventListener(event, resetTimer);
    });
    
    resetTimer();
}