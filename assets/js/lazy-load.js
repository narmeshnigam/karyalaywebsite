/**
 * Lazy Loading Implementation
 * 
 * This script provides lazy loading functionality for images
 * with fallback support for browsers that don't support native lazy loading.
 */

(function() {
    'use strict';

    /**
     * Check if browser supports native lazy loading
     */
    const supportsNativeLazyLoading = 'loading' in HTMLImageElement.prototype;

    /**
     * Lazy load images using Intersection Observer API
     */
    function lazyLoadImages() {
        const lazyImages = document.querySelectorAll('img.lazy-load');

        if (lazyImages.length === 0) {
            return;
        }

        // If browser supports native lazy loading, just swap data-src to src
        if (supportsNativeLazyLoading) {
            lazyImages.forEach(function(img) {
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                }
                if (img.dataset.srcset) {
                    img.srcset = img.dataset.srcset;
                }
                img.classList.remove('lazy-load');
                img.classList.add('lazy-loaded');
            });
            return;
        }

        // Fallback: Use Intersection Observer for older browsers
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        
                        // Load the image
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                        }
                        if (img.dataset.srcset) {
                            img.srcset = img.dataset.srcset;
                        }
                        
                        // Add loaded class
                        img.classList.remove('lazy-load');
                        img.classList.add('lazy-loaded');
                        
                        // Stop observing this image
                        observer.unobserve(img);
                    }
                });
            }, {
                // Load images 50px before they enter viewport
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            lazyImages.forEach(function(img) {
                imageObserver.observe(img);
            });
        } else {
            // Fallback for very old browsers: load all images immediately
            lazyImages.forEach(function(img) {
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                }
                if (img.dataset.srcset) {
                    img.srcset = img.dataset.srcset;
                }
                img.classList.remove('lazy-load');
                img.classList.add('lazy-loaded');
            });
        }
    }

    /**
     * Initialize lazy loading when DOM is ready
     */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', lazyLoadImages);
    } else {
        lazyLoadImages();
    }

    /**
     * Re-initialize lazy loading for dynamically added images
     */
    window.lazyLoadImages = lazyLoadImages;

})();
