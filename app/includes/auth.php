<?php
// Authentication helper functions

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'first_name' => $_SESSION['first_name'],
        'last_name' => $_SESSION['last_name'],
        'full_name' => $_SESSION['full_name'],
        'user_role' => $_SESSION['user_role']
    ];
}

function hasRole($role) {
    return isLoggedIn() && $_SESSION['user_role'] === $role;
}

function hasAnyRole($roles) {
    if (!isLoggedIn()) {
        return false;
    }
    
    return in_array($_SESSION['user_role'], $roles);
}

function isAdmin() {
    return hasRole('admin');
}

function canManageUsers() {
    return hasAnyRole(['admin', 'manager']);
}

function canApproveRequests() {
    return hasAnyRole(['admin', 'approver', 'manager']);
}
?>
