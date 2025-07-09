<?php
/**
 * Status Badge Helper Functions
 * 
 * This file provides consistent status display functions and CSS classes
 * for booking statuses throughout the entire system.
 */

/**
 * Generate a status badge with consistent styling
 * 
 * @param string $status - The booking status (Booked, Completed, Cancelled)
 * @param string $size - Size of the badge (small, medium, large)
 * @return string - HTML for the status badge
 */
function getStatusBadge($status, $size = 'medium') {
    $status = ucfirst(strtolower(trim($status)));
    
    // Define status configurations
    $statusConfig = [
        'Booked' => [
            'class' => 'status-booked',
            'icon' => 'fa-check-circle',
            'color' => '#28a745',
            'bg_color' => '#d4edda',
            'text' => 'Booked'
        ],
        'Completed' => [
            'class' => 'status-completed',
            'icon' => 'fa-flag-checkered',
            'color' => '#007bff',
            'bg_color' => '#d1ecf1',
            'text' => 'Completed'
        ],
        'Cancelled' => [
            'class' => 'status-cancelled',
            'icon' => 'fa-times-circle',
            'color' => '#dc3545',
            'bg_color' => '#f8d7da',
            'text' => 'Cancelled'
        ]
    ];
    
    // Default for unknown status
    if (!isset($statusConfig[$status])) {
        $statusConfig[$status] = [
            'class' => 'status-unknown',
            'icon' => 'fa-question-circle',
            'color' => '#6c757d',
            'bg_color' => '#e2e3e5',
            'text' => $status
        ];
    }
    
    $config = $statusConfig[$status];
    
    // Size configurations
    $sizeConfig = [
        'small' => [
            'padding' => '4px 8px',
            'font_size' => '0.75rem',
            'icon_size' => '0.7rem'
        ],
        'medium' => [
            'padding' => '6px 12px',
            'font_size' => '0.85rem',
            'icon_size' => '0.8rem'
        ],
        'large' => [
            'padding' => '8px 16px',
            'font_size' => '0.9rem',
            'icon_size' => '0.9rem'
        ]
    ];
    
    $sizeStyle = $sizeConfig[$size] ?? $sizeConfig['medium'];
    
    return sprintf(
        '<span class="status-badge %s" style="
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: %s;
            font-size: %s;
            font-weight: 600;
            color: %s;
            background-color: %s;
            border: 1px solid %s;
            border-radius: 6px;
            white-space: nowrap;
        ">
            <i class="fa %s" style="font-size: %s;"></i>
            %s
        </span>',
        $config['class'],
        $sizeStyle['padding'],
        $sizeStyle['font_size'],
        $config['color'],
        $config['bg_color'],
        $config['color'],
        $config['icon'],
        $sizeStyle['icon_size'],
        $config['text']
    );
}

/**
 * Get status color for inline styling
 * 
 * @param string $status - The booking status
 * @return string - Color hex code
 */
function getStatusColor($status) {
    $status = ucfirst(strtolower(trim($status)));
    
    $colors = [
        'Booked' => '#28a745',
        'Completed' => '#007bff',
        'Cancelled' => '#dc3545'
    ];
    
    return $colors[$status] ?? '#6c757d';
}

/**
 * Get status background color for table rows
 * 
 * @param string $status - The booking status
 * @return string - Background color style
 */
function getStatusRowStyle($status) {
    $status = ucfirst(strtolower(trim($status)));
    
    if ($status === 'Cancelled') {
        return 'opacity: 0.7; background-color: #f8f9fa;';
    }
    
    return '';
}

/**
 * Generate CSS for status badges (to be included in header)
 * 
 * @return string - CSS styles
 */
function getStatusBadgeCSS() {
    return '
    <style>
    .status-badge {
        /* Static styling - no animations or hover effects */
    }
    </style>';
}
?>
