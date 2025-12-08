/**
 * Customer Portal JavaScript
 * Handles customer portal interactions
 */

(function() {
  'use strict';

  // Wait for DOM to be ready
  document.addEventListener('DOMContentLoaded', function() {
    
    // Sidebar Toggle for Mobile
    const sidebarToggle = document.querySelector('.customer-portal-sidebar-toggle');
    const sidebar = document.querySelector('.customer-portal-sidebar');
    
    if (sidebarToggle && sidebar) {
      sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('active');
        const isExpanded = sidebar.classList.contains('active');
        sidebarToggle.setAttribute('aria-expanded', isExpanded);
      });

      // Close sidebar when clicking outside on mobile
      document.addEventListener('click', function(event) {
        if (window.innerWidth <= 1024) {
          const isClickInsideSidebar = sidebar.contains(event.target);
          const isClickOnToggle = sidebarToggle.contains(event.target);
          
          if (!isClickInsideSidebar && !isClickOnToggle && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
            sidebarToggle.setAttribute('aria-expanded', 'false');
          }
        }
      });
    }

    // User Menu Dropdown
    const userButton = document.querySelector('.customer-portal-user-button');
    const userDropdown = document.querySelector('.customer-portal-user-dropdown');
    
    if (userButton && userDropdown) {
      userButton.addEventListener('click', function(event) {
        event.stopPropagation();
        const isExpanded = userButton.getAttribute('aria-expanded') === 'true';
        userButton.setAttribute('aria-expanded', !isExpanded);
        userDropdown.style.display = isExpanded ? 'none' : 'block';
      });

      // Close dropdown when clicking outside
      document.addEventListener('click', function(event) {
        if (!userButton.contains(event.target) && !userDropdown.contains(event.target)) {
          userButton.setAttribute('aria-expanded', 'false');
          userDropdown.style.display = 'none';
        }
      });
    }

    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
      setTimeout(function() {
        alert.style.transition = 'opacity 0.3s ease';
        alert.style.opacity = '0';
        setTimeout(function() {
          alert.remove();
        }, 300);
      }, 5000);
    });

    // Handle responsive sidebar on window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() {
        if (window.innerWidth > 1024 && sidebar) {
          sidebar.classList.remove('active');
          if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', 'false');
          }
        }
      }, 250);
    });

  });

})();
