/**
 * auth_guard.js
 * Handles session checking, navbar updates, and route protection.
 */

document.addEventListener('DOMContentLoaded', async () => {
    try {
        const response = await fetch('../backend/check_session.php');
        if (!response.ok) throw new Error(`HTTP Error ${response.status}`);

        const text = await response.text();
        let session;
        try {
            session = JSON.parse(text);
        } catch (err) {
            console.error('Core Auth Error: Invalid JSON from server', text);
            return; // Stop execution if auth is broken, don't redirect blindly
        }

        const isLoggedIn = session.logged_in;
        const role = session.role || '';

        // Expose session globally for other scripts (like booking logic)
        window.userSession = session;

        updateNavbar(isLoggedIn, role);
        handleRouteProtection(isLoggedIn, role);

    } catch (e) {
        console.error("Auth check failed:", e);
    }
});

function updateNavbar(isLoggedIn, role) {
    const authContainer = document.getElementById('nav-auth-links');
    if (!authContainer) return;

    if (isLoggedIn) {
        let dashboardPage = 'customer-dashboard.html';
        if (role === 'admin') dashboardPage = 'admin-dashboard.html';
        if (role === 'provider') dashboardPage = 'provider-dashboard.html';

        authContainer.innerHTML = `
            <a href="${dashboardPage}" class="${window.location.pathname.endsWith(dashboardPage) ? 'active' : ''}">My Dashboard</a>
            <a href="#" id="logoutBtn">Logout</a>`;

        document.getElementById('logoutBtn').addEventListener('click', async (e) => {
            e.preventDefault();
            await fetch('../backend/logout.php');
            window.location.href = 'index.html';
        });
    } else {
        // Guest mode - already has Login button in HTML, but consistent reset if needed
        authContainer.innerHTML = `<a href="login.html" id="navLoginBtn">Login</a>`;

        // Protect specific links for guests
        const protectedLinks = document.querySelectorAll('a[href*="services.html"], a[href*="listings.html"], a[href*="property-detail"]');
        protectedLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                if (confirm("You must login to continue. Go to Login?")) {
                    window.location.href = 'login.html';
                }
            });
        });
    }
}

function handleRouteProtection(isLoggedIn, role) {
    const path = window.location.pathname;
    const page = path.split("/").pop();

    const protectedPages = [
        'customer-dashboard.html',
        'provider-dashboard.html',
        'admin-dashboard.html',
        'property-upload.php' // if php directly accessed
        // Add others if needed
    ];

    if (protectedPages.includes(page) && !isLoggedIn) {
        alert("Access Denied. Please login.");
        window.location.href = 'login.html';
    }

    // Role protection
    if (isLoggedIn) {
        if (page === 'admin-dashboard.html' && role !== 'admin') {
            window.location.href = 'index.html';
        }
        if (page === 'provider-dashboard.html' && role !== 'provider') {
            window.location.href = 'index.html'; // Or customer dashboard
        }
        // etc
    }
}
