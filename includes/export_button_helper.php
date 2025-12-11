<?php
/**
 * Export Button Helper
 * 
 * Provides a reusable function to render export to Excel buttons
 */

/**
 * Render export button with current filters
 * 
 * @param string $exportUrl The export endpoint URL
 * @param string $label Button label (default: "Export to Excel")
 * @return void
 */
function render_export_button(string $exportUrl, string $label = 'Export to Excel'): void
{
    // Get current query parameters to preserve filters
    $queryParams = $_GET;
    
    // Remove pagination from export
    unset($queryParams['page']);
    
    // Build export URL with filters
    $exportUrlWithParams = $exportUrl;
    if (!empty($queryParams)) {
        $exportUrlWithParams .= '?' . http_build_query($queryParams);
    }
    
    ?>
    <a href="<?php echo htmlspecialchars($exportUrlWithParams); ?>" 
       class="btn btn-success export-btn" 
       title="Export current view to Excel (CSV format)">
        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" style="margin-right: 6px; vertical-align: middle;">
            <path d="M14 11V14H2V11H0V14C0 15.1 0.9 16 2 16H14C15.1 16 16 15.1 16 14V11H14Z" fill="currentColor"/>
            <path d="M13 7L11.59 5.59L9 8.17V0H7V8.17L4.41 5.59L3 7L8 12L13 7Z" fill="currentColor"/>
        </svg>
        <?php echo htmlspecialchars($label); ?>
    </a>
    <?php
}

/**
 * Render export button styles (call once per page)
 */
function render_export_button_styles(): void
{
    ?>
    <style>
    .export-btn {
        display: inline-flex;
        align-items: center;
        padding: 0.625rem 1.25rem;
        background-color: #10b981;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9375rem;
        text-decoration: none;
        cursor: pointer;
        transition: background-color 0.2s, transform 0.1s;
    }
    
    .export-btn:hover {
        background-color: #059669;
        transform: translateY(-1px);
    }
    
    .export-btn:active {
        transform: translateY(0);
    }
    
    .admin-page-header-actions {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }
    
    @media (max-width: 768px) {
        .export-btn {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .export-btn svg {
            width: 14px;
            height: 14px;
        }
    }
    </style>
    <?php
}
