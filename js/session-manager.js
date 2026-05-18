/**
 * Session Management & Auto-Logout Handler
 * Include this script in your page header for automatic session timeout management
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        SESSION_TIMEOUT: 30 * 60 * 1000,  // 30 minutes
        WARNING_TIME: 25 * 60 * 1000,     // Warn at 25 minutes
        CHECK_INTERVAL: 60 * 1000          // Check every 1 minute
    };

    let logoutTimer;
    let warningTimer;
    let checkInterval;

    /**
     * Check if user is still authenticated
     */
    function isUserAuthenticated() {
        // Check if sessionStorage has uid (client-side check)
        return !!sessionStorage.getItem('uid');
    }

    /**
     * Show session expiration warning
     */
    function showSessionWarning() {
        const warned = sessionStorage.getItem('sessionWarned');
        
        if (!warned) {
            sessionStorage.setItem('sessionWarned', 'true');
            
            // Show alert with countdown
            const alertHtml = `
                <div class="alert alert-warning alert-dismissible" id="sessionWarning" style="position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px;">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <strong>⚠️ Session Expiring Soon!</strong>
                    <p>Your session will expire in 5 minutes due to inactivity.</p>
                    <button type="button" class="btn btn-sm btn-primary" id="extendSession">Continue Working</button>
                    <button type="button" class="btn btn-sm btn-danger" id="logoutNow">Logout</button>
                </div>
            `;
            
            // Insert alert if not already present
            if (!document.getElementById('sessionWarning')) {
                document.body.insertAdjacentHTML('afterbegin', alertHtml);
                
                // Add event listeners
                const continueBtn = document.getElementById('extendSession');
                const logoutBtn = document.getElementById('logoutNow');
                
                if (continueBtn) {
                    continueBtn.addEventListener('click', resetActivityTimer);
                }
                if (logoutBtn) {
                    logoutBtn.addEventListener('click', performLogout);
                }
            }
        }
    }

    /**
     * Perform logout
     */
    function performLogout() {
        sessionStorage.clear();
        
        // Call logout API
        fetch('../api/logout.php', { method: 'POST' })
            .catch(err => console.error('Logout API error:', err))
            .finally(() => {
                // Redirect to login
                if (window.location.pathname.includes('/pages/')) {
                    location.href = '../index.php?timeout=1';
                } else {
                    location.href = '../index.php?timeout=1';
                }
            });
    }

    /**
     * Reset activity timer
     */
    function resetActivityTimer() {
        if (!isUserAuthenticated()) return;

        // Clear existing timers
        clearTimeout(logoutTimer);
        clearTimeout(warningTimer);
        
        // Remove warning alert
        const warning = document.getElementById('sessionWarning');
        if (warning) warning.remove();
        
        // Clear session warning flag
        sessionStorage.removeItem('sessionWarned');

        // Set new warning timer
        warningTimer = setTimeout(() => {
            showSessionWarning();
        }, CONFIG.WARNING_TIME);

        // Set new logout timer
        logoutTimer = setTimeout(() => {
            performLogout();
        }, CONFIG.SESSION_TIMEOUT);
    }

    /**
     * Periodic background check for session validity
     */
    function startBackgroundCheck() {
        checkInterval = setInterval(() => {
            if (isUserAuthenticated()) {
                // Optionally, you could make a server request here to verify session
                // fetch('../api/verify_session.php')
            } else {
                // User is not authenticated, logout
                performLogout();
            }
        }, CONFIG.CHECK_INTERVAL);
    }

    /**
     * Initialize session management
     */
    function init() {
        if (!isUserAuthenticated()) {
            // User not logged in, no need to set timers
            return;
        }

        // Start activity timers and background check
        resetActivityTimer();
        startBackgroundCheck();

        // Track user activity
        const activityEvents = ['mousemove', 'keypress', 'click', 'scroll', 'touchstart', 'mousedown'];
        activityEvents.forEach(event => {
            document.addEventListener(event, resetActivityTimer, true);
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            clearTimeout(logoutTimer);
            clearTimeout(warningTimer);
            clearInterval(checkInterval);
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
