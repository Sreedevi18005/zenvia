// Basic Interactivity for Zenvia

// Basic Interactivity for Zenvia
// Global Toast System
window.showToast = function (message, type = 'info') {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = 'position: fixed; bottom: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 10px;';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    const color = type === 'error' ? '#e74c3c' : (type === 'success' ? '#2ecc71' : '#333');
    toast.style.cssText = `background: #ffffff; color: var(--text-dark); border-left: 4px solid ${color}; padding: 15px 25px; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); font-family: 'Inter', sans-serif; animation: fadeIn 0.3s ease-out; font-weight: 500;`;
    toast.innerText = message;

    toastContainer.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
};

// Add FadeIn animation style
const style = document.createElement('style');
style.innerHTML = `@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }`;
document.head.appendChild(style);

document.addEventListener('DOMContentLoaded', () => {
    // Mobile Menu Toggle
    const mobileBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');

    if (mobileBtn && navLinks) {
        mobileBtn.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Search Tab Switching
    const searchTabs = document.querySelectorAll('.search-tab-btn');
    const searchForms = document.querySelectorAll('.search-form-content');

    searchTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active class from all tabs
            searchTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            tab.classList.add('active');

            // Hide all forms AND sections
            searchForms.forEach(form => form.style.display = 'none');

            // Show target form
            const targetId = tab.getAttribute('data-target');
            const targetForm = document.getElementById(targetId);
            if (targetForm) {
                targetForm.style.display = 'block';
            }

            // Toggle Sections logic
            const propertiesSection = document.getElementById('properties-section');
            const servicesSection = document.getElementById('services-section');

            if (targetId === 'search-real-estate') {
                if (propertiesSection) propertiesSection.style.display = 'block';
                if (servicesSection) servicesSection.style.display = 'none';
            } else if (targetId === 'search-services') {
                if (propertiesSection) propertiesSection.style.display = 'none';
                if (servicesSection) servicesSection.style.display = 'block';
            }
        });
    });
});
