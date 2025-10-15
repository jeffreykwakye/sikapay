<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;

class LoginController extends Controller
{
    /**
     * Displays the login form.
     */
    public function show(): void
    {
        if ($this->auth->check()) {
            // If already logged in, redirect to the dashboard (we'll implement this later)
            $this->redirect('/dashboard');
        }
        
        // Pass any session error message to the view
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']); // Clear message after reading

        $this->view('auth/login', ['error' => $error]);
    }

    /**
     * Handles the login form submission.
     */
    public function attempt(): void
    {
        if ($this->auth->check()) {
            $this->redirect('/dashboard');
        }

        // Basic input validation (more robust validation should be added here)
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Email and password are required.';
            $this->redirect('/login');
        }

        if ($this->auth->login($email, $password)) {
            // Success: Redirect to a safe zone
            $this->redirect('/dashboard');
        } else {
            // Failure: Set error and redirect back to form
            $_SESSION['login_error'] = 'Invalid credentials or account is inactive.';
            $this->redirect('/login');
        }
    }
    
    /**
     * Handles the user logout request.
     */
    public function logout(): void
    {
        $this->auth->logout();
        $this->redirect('/login');
    }


    /**
     * Displays the login form (main entry point for /login).
     */
    public function index(): void // Renamed from 'show' to the correct entry point
    {
        $this->preventCache(); // Good practice to prevent caching

        if ($this->auth->check()) {
            $this->redirect('/dashboard');
            return;
        }
        
        // 1. Get error from session (use the 'login_error' key as per your attempt() method)
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']); // Clear message after reading

        // 2. 🛑 CRITICAL FIX: Use viewLogin() and the correct view path 🛑
        // Note: The previous call was $this->viewLogin('login/index'). We must ensure consistency.
        $this->viewLogin('auth/login', [
            'error' => $error,
            // You can add the flash_error for consistency if needed, but login_error is primary
            // 'flash_error' => $_SESSION['flash_error'] ?? null, 
        ]);
        // Note: No need to unset $_SESSION['flash_error'] here if it's not the primary error key.
    }

}