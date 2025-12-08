/**
 * Navigation JavaScript
 * Handles mobile menu toggle and responsive navigation
 */

(function() {
  'use strict';

  // Wait for DOM to be ready
  document.addEventListener('DOMContentLoaded', function() {
    initMobileMenu();
    initActiveNavLinks();
  });

  /**
   * Initialize mobile menu toggle functionality
   */
  function initMobileMenu() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (!menuToggle || !mobileMenu) {
      return;
    }

    menuToggle.addEventListener('click', function() {
      const isActive = menuToggle.classList.contains('active');
      
      if (isActive) {
        closeMobileMenu();
      } else {
        openMobileMenu();
      }
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
      const isClickInsideMenu = mobileMenu.contains(event.target);
      const isClickOnToggle = menuToggle.contains(event.target);
      
      if (!isClickInsideMenu && !isClickOnToggle && mobileMenu.classList.contains('active')) {
        closeMobileMenu();
      }
    });

    // Close mobile menu when window is resized to desktop size
    window.addEventListener('resize', function() {
      if (window.innerWidth >= 768 && mobileMenu.classList.contains('active')) {
        closeMobileMenu();
      }
    });

    // Close mobile menu when a link is clicked
    const mobileMenuLinks = mobileMenu.querySelectorAll('.mobile-menu-link');
    mobileMenuLinks.forEach(function(link) {
      link.addEventListener('click', function() {
        closeMobileMenu();
      });
    });
  }

  /**
   * Open mobile menu
   */
  function openMobileMenu() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    menuToggle.classList.add('active');
    mobileMenu.classList.add('active');
    menuToggle.setAttribute('aria-expanded', 'true');
    
    // Prevent body scroll when menu is open
    document.body.style.overflow = 'hidden';
  }

  /**
   * Close mobile menu
   */
  function closeMobileMenu() {
    const menuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    menuToggle.classList.remove('active');
    mobileMenu.classList.remove('active');
    menuToggle.setAttribute('aria-expanded', 'false');
    
    // Restore body scroll
    document.body.style.overflow = '';
  }

  /**
   * Set active class on current page navigation links
   */
  function initActiveNavLinks() {
    const currentPath = window.location.pathname;
    
    // Get all navigation links
    const navLinks = document.querySelectorAll('.main-nav-link, .mobile-menu-link');
    
    navLinks.forEach(function(link) {
      const linkPath = new URL(link.href).pathname;
      
      // Check if link matches current path
      if (linkPath === currentPath) {
        link.classList.add('active');
      } else {
        link.classList.remove('active');
      }
    });
  }

  /**
   * Smooth scroll to anchor links
   */
  function initSmoothScroll() {
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(function(link) {
      link.addEventListener('click', function(e) {
        const targetId = this.getAttribute('href');
        
        // Skip if it's just "#"
        if (targetId === '#') {
          return;
        }
        
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
          e.preventDefault();
          
          targetElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
          
          // Update URL without jumping
          history.pushState(null, null, targetId);
        }
      });
    });
  }

  // Initialize smooth scroll
  initSmoothScroll();

})();
