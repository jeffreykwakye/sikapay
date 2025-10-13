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
}