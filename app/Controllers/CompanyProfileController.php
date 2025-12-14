<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Models\TenantProfileModel;
use Jeffrey\Sikapay\Models\PayrollSettingsModel;
use Jeffrey\Sikapay\Security\CsrfToken;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Helpers\Sanitizer; 
use Jeffrey\Sikapay\Helpers\FileUploader; 
use \Exception;
use \PDOException;

class CompanyProfileController extends Controller
{
    private TenantProfileModel $profileModel;
    private PayrollSettingsModel $payrollSettingsModel; // Added
    private const PERMISSION_MANAGE = 'tenant:manage_settings';

    public function __construct()
    {
        parent::__construct();
        
        if (!$this->auth->check() || $this->auth->isSuperAdmin()) {
            $this->redirect('/login'); 
        }
        
        $this->checkPermission(self::PERMISSION_MANAGE);
        
        $this->profileModel = new TenantProfileModel();
        $this->payrollSettingsModel = new PayrollSettingsModel(); // Instantiated
    }

    /**
     * Renders the Company Profile configuration page. (READ)
     */
    public function index(): void
    {
        $profile = $this->profileModel->findByTenantId($this->tenantId);
        $includeCoverLetters = $this->payrollSettingsModel->getSetting($this->tenantId, 'include_report_cover_letters', 'false') === 'true';

        // Define a complete set of keys with safe defaults
        $defaultProfile = [
            'legal_name' => '',
            'logo_path' => '',
            'phone_number' => '',
            'support_email' => '',
            'physical_address' => '',
            'ghana_revenue_authority_tin' => '',
            'bank_name' => '',
            'bank_branch' => '',
            'bank_address' => '',
            'ssnit_office_name' => '',
            'ssnit_office_address' => '',
            'gra_office_name' => '',
            'gra_office_address' => '',
        ];

        // Merge defaults with fetched data. This guarantees all keys exist.
        $profileData = array_merge($defaultProfile, $profile ?? []);

        $subscriptionModel = new \Jeffrey\Sikapay\Models\SubscriptionModel();
        $subscription = $subscriptionModel->getCurrentSubscription($this->tenantId);
        $planName = $subscription['plan_name'] ?? 'Standard';

        // Prepare the final data array for the view
        $data = [
            'profile' => $profileData, 
            'include_report_cover_letters' => $includeCoverLetters,
            'planName' => $planName,
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
            CsrfToken::destroyToken();
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token. Please try again.";
            $this->redirect('/company-profile');
        }

        // 1. Data Sanitization and Validation
        $legalName = Sanitizer::text($_POST['legal_name'] ?? '');
        $phoneNumber = Sanitizer::text($_POST['phone_number'] ?? '');
        $supportEmail = Sanitizer::email($_POST['support_email'] ?? '');
        $tin = Sanitizer::text($_POST['tin'] ?? '');
        $address = Sanitizer::textarea($_POST['physical_address'] ?? '');
        
        // New fields
        $bankName = Sanitizer::text($_POST['bank_name'] ?? '');
        $bankBranch = Sanitizer::text($_POST['bank_branch'] ?? '');
        $bankAddress = Sanitizer::textarea($_POST['bank_address'] ?? '');
        $ssnitOfficeName = Sanitizer::text($_POST['ssnit_office_name'] ?? '');
        $ssnitOfficeAddress = Sanitizer::textarea($_POST['ssnit_office_address'] ?? '');
        $graOfficeName = Sanitizer::text($_POST['gra_office_name'] ?? '');
        $graOfficeAddress = Sanitizer::textarea($_POST['gra_office_address'] ?? '');
        $includeCoverLetters = isset($_POST['include_report_cover_letters']) ? 'true' : 'false';

        // IMPORTANT: Fetch the current profile to retain the existing logo path
        $currentProfile = $this->profileModel->findByTenantId($this->tenantId);
        $logoPath = $currentProfile['logo_path'] ?? null; 

        if (empty($legalName) || empty($tin)) {
            $_SESSION['flash_error'] = "Company Legal Name and GRA TIN are required fields.";
            $this->redirect('/company-profile');
        }

        // 2. Prepare Data for Model
        $profileData = [
            'legal_name' => $legalName,
            'logo_path' => $logoPath,
            'phone_number' => $phoneNumber,
            'support_email' => $supportEmail,
            'physical_address' => $address,
            'ghana_revenue_authority_tin' => $tin,
            'bank_name' => $bankName,
            'bank_branch' => $bankBranch,
            'bank_address' => $bankAddress,
            'ssnit_office_name' => $ssnitOfficeName,
            'ssnit_office_address' => $ssnitOfficeAddress,
            'gra_office_name' => $graOfficeName,
            'gra_office_address' => $graOfficeAddress,
        ];

        // 3. Business Logic (UPSERT)
        $db = \Jeffrey\Sikapay\Core\Database::getInstance();
        try {
            $db->beginTransaction();
            $profileSuccess = $this->profileModel->save($this->tenantId, $profileData);
            $settingSuccess = $this->payrollSettingsModel->saveSetting($this->tenantId, 'include_report_cover_letters', $includeCoverLetters);

            if ($profileSuccess && $settingSuccess) {
                $db->commit();
                Log::info("Company Profile and settings updated by User {$this->userId} in Tenant {$this->tenantId}.");
                $_SESSION['flash_success'] = "Company Profile and settings updated successfully!";
            } else {
                $db->rollBack();
                 $_SESSION['flash_error'] = "Profile update failed due to an unknown issue or no changes made.";
            }
        } catch (PDOException $e) {
            $db->rollBack();
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
        // This method remains unchanged for now
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/company-profile');
        }

        if (!CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            CsrfToken::destroyToken();
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token. Please try again.";
            $this->redirect('/company-profile');
        }
        
        $currentProfile = $this->profileModel->findByTenantId($this->tenantId);

        if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash_error'] = "No valid file uploaded or an upload error occurred.";
            $this->redirect('/company-profile'); 
        }

        $oldLogoPath = $currentProfile['logo_path'] ?? null;
        try {
            $MAX_IMAGE_SIZE = 2 * 1024 * 1024;
            $newLogoPath = FileUploader::upload(
                $_FILES['logo_file'], 
                "assets/images/tenant_logos/{$this->tenantId}", 
                ['png', 'jpg', 'jpeg'], 
                $MAX_IMAGE_SIZE
            );

            $profileData = array_merge($currentProfile, ['logo_path' => $newLogoPath]);
            $success = $this->profileModel->save($this->tenantId, $profileData);

            if ($success) {
                if ($oldLogoPath && $oldLogoPath !== $newLogoPath) {
                    // FileUploader::delete($oldLogoPath); 
                }
                
                Log::info("Company Logo updated by User {$this->userId} in Tenant {$this->tenantId}.");
                $_SESSION['flash_success'] = "Company Logo updated successfully!";
            } else {
                $_SESSION['flash_error'] = "Logo update failed due to a database issue. Please try again.";
            }

        } catch (Exception $e) {
            Log::error("Logo upload failed: " . $e->getMessage());
            $_SESSION['flash_error'] = "Logo upload failed: " . $e->getMessage();
        }

        $this->redirect('/company-profile');
    }
}
