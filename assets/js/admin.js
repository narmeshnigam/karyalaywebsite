/**
 * Admin Panel JavaScript
 * Handles admin-specific interactions
 */

document.addEventListener('DOMContentLoaded', function() {
  // Sidebar toggle for mobile
  const sidebarToggle = document.querySelector('.admin-sidebar-toggle');
  const sidebar = document.querySelector('.admin-sidebar');
  const adminWrapper = document.querySelector('.admin-wrapper');

  if (sidebarToggle && sidebar) {
    sidebarToggle.addEventListener('click', function() {
      sidebar.classList.toggle('active');
      
      // Update aria-expanded
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

  // User menu dropdown
  const userButton = document.querySelector('.admin-user-button');
  const userDropdown = document.querySelector('.admin-user-dropdown');

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

  // Auto-hide alerts after 5 seconds
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(function(alert) {
    setTimeout(function() {
      alert.style.transition = 'opacity 0.3s ease-out';
      alert.style.opacity = '0';
      setTimeout(function() {
        alert.remove();
      }, 300);
    }, 5000);
  });

  // Confirm delete actions
  const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
  deleteButtons.forEach(function(button) {
    button.addEventListener('click', function(event) {
      const message = button.getAttribute('data-confirm-delete') || 'Are you sure you want to delete this item?';
      if (!confirm(message)) {
        event.preventDefault();
      }
    });
  });

  // Table row click to navigate
  const clickableRows = document.querySelectorAll('[data-href]');
  clickableRows.forEach(function(row) {
    row.style.cursor = 'pointer';
    row.addEventListener('click', function(event) {
      // Don't navigate if clicking on a link or button
      if (event.target.tagName === 'A' || event.target.tagName === 'BUTTON' || event.target.closest('a') || event.target.closest('button')) {
        return;
      }
      window.location.href = row.getAttribute('data-href');
    });
  });
});
