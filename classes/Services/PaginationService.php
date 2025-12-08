<?php

namespace Karyalay\Services;

/**
 * Pagination Service
 * 
 * Provides pagination functionality for large data sets.
 */
class PaginationService
{
    private int $currentPage;
    private int $perPage;
    private int $totalItems;
    private int $totalPages;
    private string $baseUrl;
    private array $queryParams;

    /**
     * Constructor
     * 
     * @param int $totalItems Total number of items
     * @param int $perPage Items per page (default: 20)
     * @param int|null $currentPage Current page number (default: from $_GET['page'])
     * @param string|null $baseUrl Base URL for pagination links (default: current URL)
     */
    public function __construct(
        int $totalItems,
        int $perPage = 20,
        ?int $currentPage = null,
        ?string $baseUrl = null
    ) {
        $this->totalItems = max(0, $totalItems);
        $this->perPage = max(1, $perPage);
        $this->currentPage = $currentPage ?? $this->getCurrentPageFromRequest();
        $this->totalPages = (int) ceil($this->totalItems / $this->perPage);
        
        // Ensure current page is within valid range
        $this->currentPage = max(1, min($this->currentPage, max(1, $this->totalPages)));
        
        // Set base URL
        $this->baseUrl = $baseUrl ?? $this->getCurrentUrl();
        
        // Parse existing query parameters
        $this->queryParams = $_GET ?? [];
        unset($this->queryParams['page']); // Remove page param, we'll add it back
    }

    /**
     * Get current page number from request
     * 
     * @return int
     */
    private function getCurrentPageFromRequest(): int
    {
        $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
        return max(1, $page);
    }

    /**
     * Get current URL without query string
     * 
     * @return string
     */
    private function getCurrentUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        return $protocol . '://' . $host . $path;
    }

    /**
     * Get current page number
     * 
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get items per page
     * 
     * @return int
     */
    public function getPerPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get total number of items
     * 
     * @return int
     */
    public function getTotalItems(): int
    {
        return $this->totalItems;
    }

    /**
     * Get total number of pages
     * 
     * @return int
     */
    public function getTotalPages(): int
    {
        return $this->totalPages;
    }

    /**
     * Get offset for database query
     * 
     * @return int
     */
    public function getOffset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    /**
     * Get limit for database query
     * 
     * @return int
     */
    public function getLimit(): int
    {
        return $this->perPage;
    }

    /**
     * Check if there is a previous page
     * 
     * @return bool
     */
    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Check if there is a next page
     * 
     * @return bool
     */
    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    /**
     * Get previous page number
     * 
     * @return int|null
     */
    public function getPreviousPage(): ?int
    {
        return $this->hasPreviousPage() ? $this->currentPage - 1 : null;
    }

    /**
     * Get next page number
     * 
     * @return int|null
     */
    public function getNextPage(): ?int
    {
        return $this->hasNextPage() ? $this->currentPage + 1 : null;
    }

    /**
     * Get page URL
     * 
     * @param int $page Page number
     * @return string
     */
    public function getPageUrl(int $page): string
    {
        $params = array_merge($this->queryParams, ['page' => $page]);
        $queryString = http_build_query($params);
        return $this->baseUrl . ($queryString ? '?' . $queryString : '');
    }

    /**
     * Get range of page numbers to display
     * 
     * @param int $range Number of pages to show on each side of current page
     * @return array
     */
    public function getPageRange(int $range = 2): array
    {
        $start = max(1, $this->currentPage - $range);
        $end = min($this->totalPages, $this->currentPage + $range);
        
        return range($start, $end);
    }

    /**
     * Get pagination info text (e.g., "Showing 1-20 of 100")
     * 
     * @return string
     */
    public function getInfoText(): string
    {
        if ($this->totalItems === 0) {
            return 'No items found';
        }
        
        $start = $this->getOffset() + 1;
        $end = min($this->getOffset() + $this->perPage, $this->totalItems);
        
        return sprintf('Showing %d-%d of %d', $start, $end, $this->totalItems);
    }

    /**
     * Render pagination HTML
     * 
     * @param array $options Rendering options
     *   - show_info: Show info text (default: true)
     *   - show_first_last: Show first/last page links (default: true)
     *   - range: Number of pages to show on each side (default: 2)
     *   - class: CSS class for pagination container (default: 'pagination')
     * @return string
     */
    public function render(array $options = []): string
    {
        $showInfo = $options['show_info'] ?? true;
        $showFirstLast = $options['show_first_last'] ?? true;
        $range = $options['range'] ?? 2;
        $class = $options['class'] ?? 'pagination';
        
        if ($this->totalPages <= 1) {
            return $showInfo ? '<div class="pagination-info">' . $this->getInfoText() . '</div>' : '';
        }
        
        $html = '<nav class="' . htmlspecialchars($class) . '" aria-label="Pagination">';
        
        // Info text
        if ($showInfo) {
            $html .= '<div class="pagination-info">' . $this->getInfoText() . '</div>';
        }
        
        $html .= '<ul class="pagination-list">';
        
        // Previous button
        if ($this->hasPreviousPage()) {
            $html .= '<li class="pagination-item">';
            $html .= '<a href="' . htmlspecialchars($this->getPageUrl($this->getPreviousPage())) . '" class="pagination-link" aria-label="Previous page">';
            $html .= '&laquo; Previous';
            $html .= '</a>';
            $html .= '</li>';
        } else {
            $html .= '<li class="pagination-item pagination-disabled">';
            $html .= '<span class="pagination-link" aria-disabled="true">&laquo; Previous</span>';
            $html .= '</li>';
        }
        
        // First page
        if ($showFirstLast && $this->currentPage > $range + 1) {
            $html .= '<li class="pagination-item">';
            $html .= '<a href="' . htmlspecialchars($this->getPageUrl(1)) . '" class="pagination-link">1</a>';
            $html .= '</li>';
            
            if ($this->currentPage > $range + 2) {
                $html .= '<li class="pagination-item pagination-ellipsis">';
                $html .= '<span class="pagination-link">...</span>';
                $html .= '</li>';
            }
        }
        
        // Page numbers
        foreach ($this->getPageRange($range) as $page) {
            if ($page === $this->currentPage) {
                $html .= '<li class="pagination-item pagination-active">';
                $html .= '<span class="pagination-link" aria-current="page">' . $page . '</span>';
                $html .= '</li>';
            } else {
                $html .= '<li class="pagination-item">';
                $html .= '<a href="' . htmlspecialchars($this->getPageUrl($page)) . '" class="pagination-link">' . $page . '</a>';
                $html .= '</li>';
            }
        }
        
        // Last page
        if ($showFirstLast && $this->currentPage < $this->totalPages - $range) {
            if ($this->currentPage < $this->totalPages - $range - 1) {
                $html .= '<li class="pagination-item pagination-ellipsis">';
                $html .= '<span class="pagination-link">...</span>';
                $html .= '</li>';
            }
            
            $html .= '<li class="pagination-item">';
            $html .= '<a href="' . htmlspecialchars($this->getPageUrl($this->totalPages)) . '" class="pagination-link">' . $this->totalPages . '</a>';
            $html .= '</li>';
        }
        
        // Next button
        if ($this->hasNextPage()) {
            $html .= '<li class="pagination-item">';
            $html .= '<a href="' . htmlspecialchars($this->getPageUrl($this->getNextPage())) . '" class="pagination-link" aria-label="Next page">';
            $html .= 'Next &raquo;';
            $html .= '</a>';
            $html .= '</li>';
        } else {
            $html .= '<li class="pagination-item pagination-disabled">';
            $html .= '<span class="pagination-link" aria-disabled="true">Next &raquo;</span>';
            $html .= '</li>';
        }
        
        $html .= '</ul>';
        $html .= '</nav>';
        
        return $html;
    }

    /**
     * Create pagination instance from query
     * 
     * @param string $countQuery SQL query to count total items
     * @param \PDO $db Database connection
     * @param int $perPage Items per page
     * @return self
     */
    public static function fromQuery(string $countQuery, \PDO $db, int $perPage = 20): self
    {
        $totalItems = (int) $db->query($countQuery)->fetchColumn();
        return new self($totalItems, $perPage);
    }
}
