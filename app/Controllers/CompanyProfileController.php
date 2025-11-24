<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Models\TenantProfileModel;
use Jeffrey\Sikapay\Security\CsrfToken;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Helpers\Sanitizer; 
use Jeffrey\Sikapay\Helpers\FileUploader; 
use \Exception;
use \PDOException;

class CompanyProfileController extends Controller
{
    private TenantProfileModel $profileModel;
    private const PERMISSION_MANAGE = 'tenant:manage_settings';

    public function __construct()
    {
        parent::__construct();
        
        if (!$this->auth->check() || $this->auth->isSuperAdmin()) {
            $this->redirect('/login'); 
        }
        
        $this->checkPermission(self::PERMISSION_MANAGE);
        
        $this->profileModel = new TenantProfileModel();
    }

    /**
     * Renders the Company Profile configuration page. (READ)
     */
    public function index(): void
    {
        $profile = $this->profileModel->findByTenantId($this->tenantId);

        // Define a complete set of keys with safe defaults
        $defaultProfile = [
            'legal_name' => '',
            'logo_path' => '',
            'phone_number' => '',
            'support_email' => '',
            'physical_address' => '',
            'ghana_revenue_authority_tin' => '' // New field name for TIN
        ];

        // Merge defaults with fetched data. This guarantees all keys exist.
        $profileData = array_merge($defaultProfile, $profile ?? []);

        // Prepare the final data array for the view
        $data = [
            'profile' => $profileData, 
            'successMessage' => $_SESSION['flash_success'] ?? null,
            'errorMessage' => $_SESSION['flash_error'] ?? null,
            'flashWarning' => $_SESSION['flash_warning'] ?? null, 
        ];

        $this->view('tenant/company_profile', $data);
        
        unset($_SESSION['flash_success'], $_SESSION['flash_error'], $_SESSION['flash_warning']);
    }

    /**
     * Handles POST request to update the general company profile details (excluding logo).
     */
    public function save(): void
    {
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/company-profile');
        }

        // CSRF check
        if (!CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            CsrfToken::destroyToken(); // Invalidate token on failure
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token. Please try again.";
            $this->redirect('/company-profile');
        }
        // Token validation passed, proceed with rotation (optional, but good practice)
        // If your CsrfToken::validate() does not rotate, you should call CsrfToken::rotate() here
        // Assuming rotation is handled by the middleware or a dedicated rotation call after successful validation.

        // 1. Data Sanitization and Validation
        $legalName = Sanitizer::text($_POST['legal_name'] ?? '');
        $phoneNumber = Sanitizer::text($_POST['phone_number'] ?? '');
        $supportEmail = Sanitizer::email($_POST['support_email'] ?? '');
        $tin = Sanitizer::text($_POST['tin'] ?? ''); // Maps to ghana_revenue_authority_tin in DB
        $address = Sanitizer::textarea($_POST['physical_address'] ?? ''); // Maps to physical_address in DB
        
        // IMPORTANT: Fetch the current profile to retain the existing logo path and other fields
        $currentProfile = $this->profileModel->findByTenantId($this->tenantId);
        $logoPath = $currentProfile['logo_path'] ?? null; 

        if (empty($legalName) || empty($tin)) {
            $_SESSION['flash_error'] = "Company Legal Name and GRA TIN are required fields.";
            $this->redirect('/company-profile');
        }

        // 2. Prepare Data for Model (using database column names)
        $profileData = [
            'legal_name' => $legalName,
            'logo_path' => $logoPath, // Retain the existing logo path
            'phone_number' => $phoneNumber,
            'support_email' => $supportEmail,
            'physical_address' => $address,
            'ghana_revenue_authority_tin' => $tin,
        ];

        // 3. Business Logic (UPSERT)
        try {
            $success = $this->profileModel->save($this->tenantId, $profileData);
            
            if ($success) {
                Log::info("General Company Profile updated by User {$this->userId} in Tenant {$this->tenantId}.");
                $_SESSION['flash_success'] = "Company Profile updated successfully!";
            } else {
                 $_SESSION['flash_error'] = "Profile update failed due to an unknown database issue or no changes made.";
            }
        } catch (PDOException $e) {
            Log::critical("DB Error updating Company Profile: " . $e->getMessage());
            $_SESSION['flash_error'] = "Database error: Could not save company profile.";
        }

        $this->redirect('/company-profile');
    }

    /**
     * Handles POST request to update ONLY the company logo.
     */
    public function uploadLogo(): void
    {
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/company-profile');
        }

        // CSRF check
        if (!CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            CsrfToken::destroyToken(); // Invalidate token on failure
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token. Please try again.";
            $this->redirect('/company-profile');
        }
        
        // 1. Get current profile data (needed for UPSERT)
        $currentProfile = $this->profileModel->findByTenantId($this->tenantId);

        // 2. Logo Upload Logic
        if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = "No valid file uploaded or an upload error occurred.";
            $this->redirect('/company-profile'); 
        }

        $oldLogoPath = $currentProfile['logo_path'] ?? null;
        try {
            // Use the FileUploader helper
            // Set image limit to 2MB (2 * 1024 * 1024 bytes)
            $MAX_IMAGE_SIZE = 2 * 1024 * 1024;
            
            $newLogoPath = FileUploader::upload(
                $_FILES['logo_file'], 
                "assets/images/tenant_logos/{$this->tenantId}", 
                ['png', 'jpg', 'jpeg'], 
                $MAX_IMAGE_SIZE // Using the 2MB limit
            );

            // 3. Merge the new path with the existing profile data for a full UPSERT
            $profileData = array_merge($currentProfile, ['logo_path' => $newLogoPath]);
            $success = $this->profileModel->save($this->tenantId, $profileData);

            if ($success) {
                // SUCCESS: Delete the old file if it exists and is different from the new one
                if ($oldLogoPath && $oldLogoPath !== $newLogoPath) {
                    // Assuming FileUploader has a delete method, or a path resolver for deletion
                    // FileUploader::delete($oldLogoPath); 
                }
                
                Log::info("Company Logo updated by User {$this->userId} in Tenant {$this->tenantId}.");
                $_SESSION['flash_success'] = "Company Logo updated successfully!";
            } else {
                // If DB save failed, you might want to delete the newly uploaded file as well
                $_SESSION['flash_error'] = "Logo update failed due to a database issue. Please try again.";
            }

        } catch (Exception $e) {
            Log::error("Logo upload failed: " . $e->getMessage());
            $_SESSION['flash_error'] = "Logo upload failed: " . $e->getMessage();
        }

        $this->redirect('/company-profile');
    }
}