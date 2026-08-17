<?php
// =============================================================
// config/constants.php  — Site-wide constants
// =============================================================

define('SITE_NAME', 'Smart Expense Tracker');
define('CURRENCY_SYMBOL', '₹');
define('ITEMS_PER_PAGE', 15);

// Income categories
define('INCOME_CATEGORIES', [
    'Salary',
    'Freelance',
    'Business',
    'Investment',
    'Rental',
    'Gift',
    'Refund',
    'Other',
]);

// Expense categories
define('EXPENSE_CATEGORIES', [
    'Food & Dining',
    'Groceries',
    'Transport',
    'Utilities',
    'Rent / EMI',
    'Health & Medical',
    'Education',
    'Entertainment',
    'Shopping',
    'Travel',
    'Insurance',
    'Personal Care',
    'Subscriptions',
    'Children',
    'Charity / Donation',
    'Taxes',
    'Other',
]);

// Payment methods
define('PAYMENT_METHODS', [
    'Cash',
    'Credit Card',
    'Debit Card',
    'UPI',
    'Net Banking',
    'Cheque',
    'Wallet',
    'EMI',
    'Other',
]);

// Max login attempts before temporary lockout
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_SECONDS', 300); // 5 minutes
