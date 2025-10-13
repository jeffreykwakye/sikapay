<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Controllers\Controller;

class HomeController extends Controller
{
    public function index(): void
    {
        if ($this->auth->check()) {
            // If authenticated, go to the dashboard
            $this->redirect('/dashboard');
        } else {
            // If not authenticated, redirect to the login page
            $this->redirect('/login');
        }
    }
}