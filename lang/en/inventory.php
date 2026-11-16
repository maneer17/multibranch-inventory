<?php

return [
    // Navigation groups
    'nav' => [
        'inventory' => 'Inventory',
        'transfers' => 'Transfers',
        'reports' => 'Reports',
        'settings' => 'Settings',
    ],

    // Resource labels
    'resource' => [
        'product' => 'Product',
        'products' => 'Products',
        'category' => 'Category',
        'categories' => 'Categories',
        'branch' => 'Branch',
        'branches' => 'Branches',
        'user' => 'User',
        'users' => 'Users',
        'stock_level' => 'Stock Level',
        'stock_levels' => 'Stock Levels',
        'stock_movement' => 'Stock Movement',
        'stock_movements' => 'Stock Movements',
        'transfer_order' => 'Transfer Order',
        'transfer_orders' => 'Transfer Orders',
    ],

    // Common column/field labels
    'field' => [
        'name' => 'Name',
        'code' => 'Code',
        'sku' => 'SKU',
        'city' => 'City',
        'address' => 'Address',
        'unit' => 'Unit',
        'active' => 'Active',
        'category' => 'Category',
        'quantity' => 'Quantity',
        'sale_price' => 'Sale Price',
        'purchase_price' => 'Purchase Price',
        'reorder_point' => 'Reorder Point',
        'reorder_qty' => 'Reorder Qty',
        'date' => 'Date',
        'branch' => 'Branch',
        'product' => 'Product',
        'type' => 'Type',
        'unit_price' => 'Unit Price',
        'recorded_by' => 'Recorded By',
        'transfer' => 'Transfer',
        'note' => 'Note',
        'status' => 'Status',
        'from_branch' => 'From Branch',
        'to_branch' => 'To Branch',
        'requested_by' => 'Requested By',
        'approved_by' => 'Approved By',
        'created' => 'Created',
        'products_so_far' => 'Products so far',
        'below_reorder_point' => 'Below Reorder Point',
        'from' => 'From',
        'until' => 'Until',
        'email' => 'Email',
        'password' => 'Password',
    ],

    // User management
    'user' => [
        'account' => 'Account',
        'access' => 'Access',
        'role' => 'Role',
        'assigned_branch' => 'Assigned Branch',
        'password_hint' => 'Leave blank to keep current password.',
        'branch_hint' => 'The branch this user operates from. Admins can see all branches.',
        'role_hint' => 'Controls what this user can see and do in the dashboard.',
        'all_branches' => 'All branches',
    ],

    // Units
    'unit' => [
        'kg' => 'Kilograms',
        'pcs' => 'Pieces',
        'liters' => 'Liters',
        'meters' => 'Meters',
    ],

    // Pages
    'page' => [
        'reorder_suggestions' => 'Reorder Suggestions',
        'revenue_by_branch' => 'Revenue by Branch',
        'dashboard' => 'Dashboard',
    ],

    // Reorder dashboard
    'reorder' => [
        'needs_stock' => 'Needs Stock',
        'source' => 'Source',
        'current_stock' => 'Current Stock',
        'below_reorder' => 'Below reorder point',
        'suggested_qty' => 'Suggested Qty',
        'create_transfer' => 'Create Transfer',
        'transfer_summary' => 'Transfer Summary',
        'quantity_to_transfer' => 'Quantity to transfer',
        'transfer_created' => 'Transfer request created',
        'well_stocked' => 'All branches are well stocked',
        'well_stocked_desc' => 'No products are below their reorder point right now.',
    ],

    // Revenue page
    'revenue' => [
        'branch' => 'Branch',
        'sales_count' => 'Sales Count',
        'total_revenue' => 'Total Revenue',
        'no_sales' => 'No sales recorded yet',
        'no_sales_desc' => 'Revenue data will appear here once sales movements are logged.',
    ],

    // Transfer order view
    'transfer' => [
        'details' => 'Transfer Details',
        'items' => 'Items',
        'requested' => 'Requested',
        'sent' => 'Sent',
        'received' => 'Received',
        'approve' => 'Approve',
        'reject' => 'Reject',
        'cancel' => 'Cancel',
        'ship' => 'Ship',
        'receive' => 'Receive',
        'approved' => 'Transfer approved',
        'rejected' => 'Transfer rejected',
        'cancelled' => 'Transfer cancelled',
        'shipped' => 'Transfer shipped',
        'received_notification' => 'Transfer received',
        'new_request' => 'New Transfer Request',
    ],

    // Relation managers
    'relation' => [
        'record_initial_stock' => 'Record Initial Stock',
    ],

    // Landing page
    'landing' => [
        'title' => 'MultiBranch Inventory',
        'subtitle' => 'Multi-Branch Inventory Management System',
        'description' => 'A comprehensive system for managing inventory across multiple branches — track stock levels, automate reorder suggestions, and streamline inter-branch transfers.',
        'login' => 'Log In',
        'features' => 'Features',
        'feature_stock_title' => 'Real-Time Stock Tracking',
        'feature_stock_desc' => 'Monitor inventory levels across all branches with live quantity updates, low-stock alerts, and detailed movement history.',
        'feature_transfer_title' => 'Inter-Branch Transfers',
        'feature_transfer_desc' => 'Request, approve, ship, and receive stock transfers between branches with a complete audit trail.',
        'feature_reorder_title' => 'Smart Reorder Suggestions',
        'feature_reorder_desc' => 'Automatic reorder point calculation based on sales velocity — the system suggests which branch to source from and how much to transfer.',
        'feature_reports_title' => 'Revenue Reports',
        'feature_reports_desc' => 'Track sales revenue by branch to identify top performers and optimize stock allocation.',
        'feature_roles_title' => 'Role-Based Access',
        'feature_roles_desc' => 'Admin, branch manager, and staff roles with fine-grained permissions for every operation.',
        'feature_movements_title' => 'Stock Movement Ledger',
        'feature_movements_desc' => 'Every stock change — purchases, sales, adjustments, transfers — is recorded with full traceability.',
        'built_with' => 'Built with',
        'switch_lang' => 'العربية',
    ],
];
