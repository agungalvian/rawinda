<!-- Custom JavaScript -->
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>

<!-- Notification Script -->
<script>
// Auto-hide notifications after 5 seconds
setTimeout(() => {
    const notifications = document.querySelectorAll('.fixed.top-4.right-4 > div');
    notifications.forEach(notification => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.5s ease';
        setTimeout(() => notification.remove(), 500);
    });
}, 5000);

// Form validation and calculations
function formatRupiah(angka) {
    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function calculateTotal() {
    const checkboxes = document.querySelectorAll('.bill-checkbox');
    let total = 0;
    checkboxes.forEach(cb => {
        if (cb.checked) {
            total += parseInt(cb.value) || 0;
        }
    });
    const display = document.getElementById('total-display');
    if(display) display.innerText = formatRupiah(total);
    return total;
}

function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.toggle('hidden');
        if (!modal.classList.contains('hidden')) {
            modal.style.display = 'flex';
        } else {
            modal.style.display = 'none';
        }
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    if (event.target.classList.contains('modal-backdrop')) {
        event.target.closest('.fixed').classList.add('hidden');
    }
});

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            const tooltipEl = document.createElement('div');
            tooltipEl.className = 'absolute z-50 px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-sm tooltip';
            tooltipEl.textContent = tooltipText;
            this.appendChild(tooltipEl);
        });
        tooltip.addEventListener('mouseleave', function() {
            const tooltipEl = this.querySelector('.tooltip');
            if (tooltipEl) tooltipEl.remove();
        });
    });
});
</script>
</body>
</html>