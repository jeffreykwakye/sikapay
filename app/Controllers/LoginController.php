<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Core\ErrorResponder;
use Jeffrey\Sikapay\Core\Validator;
use Jeffrey\Sikapay\Models\AuditModel;
use Jeffrey\Sikapay\Controllers\SubscriptionController; // NEW
use Jeffrey\Sikapay\Security\CsrfToken;
use \Throwable;

class LoginController extends Controller
{
    private AuditModel $auditModel;

    public function __construct()
    {
        parent::__construct();
        $this->auditModel = new AuditModel();
    }

    /**
     * Displays the login form (main entry point for /login).
     */
    public function index(): void
    {
        try {
            $this->preventCache();
            CsrfToken::init();
            
            // Check if already authenticated
            if ($this->auth->check()) {
                $this->redirect('/dashboard');
                return;
            }
            
            // Handle error messages from failed login attempts
            $error = $_SESSION['login_error'] ?? null;
            $flashError = $_SESSION['flash_error'] ?? null; 
            
            unset($_SESSION['login_error']); 
            unset($_SESSION['flash_error']); 

            // Use the dedicated viewLogin() method from the Base Controller
            $this->viewLogin('auth/login', [
                'error' => $error,
                'flash_error' => $flashError,
            ]);

        } catch (Throwable $e) {
            // Catch critical error during the auth check or view load
            Log::critical("Login Form Load Failed: " . $e->getMessage(), [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
            ]);

            // Fail-safe: Display a controlled 500 error page.
            ErrorResponder::respond(500, "A critical system error occurred while preparing the login page.");
        }
    }
    
    // The 'show' method is removed as 'index' now serves as the canonical entry point.


    /**
     * Handles the login form submission.
     */
    public function attempt(): void
    {
        // 1. Always prevent caching on POST actions
        $this->preventCache();
        
        try {
            if ($this->auth->check()) {
                $this->redirect('/dashboard');
            }

            // 2. HARDENED: Use Validator for Input Validation
            $validator = new Validator($_POST);
            
            $validator->validate([
                'email' => 'required|email|max:255',
                'password' => 'required|min:8', // Enforce minimum length for security
            ]);

            if ($validator->fails()) {
                // Return a generic error to prevent enumeration of validation rules
                $_SESSION['login_error'] = 'Please check your email and password format.';
                Log::warning("Login attempt failed due to invalid input.", [
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
                    'errors' => $validator->errors() // Log details internally
                ]);
                $this->redirect('/login');
                return;
            }

            // 3. Safely retrieve validated and sanitized input
            $email = $validator->get('email');
            $password = $validator->get('password');
            
            // Core authentication attempt, relies on Auth service
            if ($this->auth->login($email, $password)) {
                // Log the successful login
                $this->auditModel->log($this->auth->tenantId(), 'User logged in');

                // --- NEW: Trigger system-wide subscription check ---
                // This runs in the background after the user is authenticated but before redirection.
                // It's a good compromise between a cron job and per-user checks.
                try {
                    $subscriptionController = new SubscriptionController();
                    $subscriptionController->checkAllSubscriptions();
                } catch (Throwable $e) {
                    // Log the failure but don't block the user's login
                    Log::error("Post-login subscription check failed: " . $e->getMessage());
                }
                // --- End of subscription check ---

                // Success: Redirect to the intended page or dashboard
                $redirectTo = $_SESSION['redirect_back_to'] ?? '/dashboard';
                unset($_SESSION['redirect_back_to']); // Clear the redirect target
                $this->redirect($redirectTo);
            } else {
                // Failure: Set generic error to prevent account enumeration
                $_SESSION['login_error'] = 'Invalid credentials or account is inactive.';
                Log::warning("Login attempt failed (Auth Service rejected).", [
                    'email' => $email, // Log the attempted email internally
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
                ]);
                $this->redirect('/login');
            }
            
        } catch (Throwable $e) {
            // Catch critical error during login process (e.g., DB failure in Auth)
            $email = $validator->get('email') ?? 'N/A';
            
            Log::critical("Login Attempt CRITICAL FAILURE: " . $e->getMessage(), [
                'email' => $email,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'N/A'
            ]);
            
            // Fail-safe: Inform user of system error and redirect them back to login.
            $_SESSION['login_error'] = 'A system error prevented your login. Please try again.';
            $this->redirect('/login');
        }
    }
    
    /**
     * Handles the user logout request.
     */
    public function logout(): void
    {
        try {
            $userId = $this->auth->userId() ?? 'N/A'; // Capture ID before logout
            $this->auth->logout();
            
            Log::info("User successfully logged out.", [
                'user_id' => $userId
            ]);
            
            $this->redirect('/login');
        } catch (Throwable $e) {
            // Log the error, but still redirect the user away from sensitive area
            Log::error("Logout process failed: " . $e->getMessage(), [
                'user_id' => $this->auth->userId() ?? 'N/A'
            ]);
            // Still redirect to login, as the primary goal is clearing session/cookies (which may have partially succeeded)
            $this->redirect('/login');
        }
    }
}