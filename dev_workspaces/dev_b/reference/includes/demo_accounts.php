<?php
/**
 * iCareDonate — Demo Account Definitions
 * includes/demo_accounts.php
 *
 * DEVELOPMENT ONLY — Pre-Final Defense Milestone
 * These accounts are for classroom demonstration only.
 * Passwords are verified via PHP password_verify().
 */

$DEMO_ACCOUNTS = [
    [
        'email'    => 'admin@icaredonate.test',
        'hash'     => '$2y$10$8nMgfp151o8NUpjrhjG2HOISVMskIGf0y7Z2Mt13RkbZhix4P5Lee', // Admin@1234
        'role'     => 'admin',
        'name'     => 'Eleanor Vance',
        'initials' => 'EV',
    ],
    [
        'email'    => 'donor@icaredonate.test',
        'hash'     => '$2y$10$zwUKkqgMLytQ.keh6tBZue0Clr372ks1MVf2vxK4asGL2yR15yDxi', // Donor@1234
        'role'     => 'donor',
        'name'     => 'David Tan',
        'initials' => 'DT',
    ],
    [
        'email'    => 'requester@icaredonate.test',
        'hash'     => '$2y$10$iU/ACfPdUIj553x/DI6VHuOdwuJl4E1TMBFIKN3WYwsG95Ck11ZKO', // Requester@1234
        'role'     => 'requester',
        'name'     => 'Maria Santos',
        'initials' => 'MS',
    ],
    [
        'email'    => 'user@icaredonate.test',
        'hash'     => '$2y$10$WlDGHSpRCL0R.vxD8QuURedkmL0DgaN.EwIT6IecV02EGsZ9Vt3j6', // User@1234
        'role'     => 'donor',
        'name'     => 'Alex Rivera',
        'initials' => 'AR',
    ],
];

// Role-to-destination map — Option B gates
define('ICD_REDIRECT_ADMIN',     'admin/index.php');
define('ICD_REDIRECT_DONOR',     'user/index.php');
define('ICD_REDIRECT_REQUESTER', 'user/index.php');
define('ICD_REDIRECT_USER',      'user/index.php');
define('ICD_LOGIN_PAGE',         'login.php');
define('ICD_FORBIDDEN_PAGE',     'frontend/403.html');
