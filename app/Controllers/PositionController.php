<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Controllers;

use Jeffrey\Sikapay\Models\PositionModel;
use Jeffrey\Sikapay\Models\DepartmentModel;
use Jeffrey\Sikapay\Security\CsrfToken;
use Jeffrey\Sikapay\Core\Log;
use Jeffrey\Sikapay\Helpers\Sanitizer;
use Jeffrey\Sikapay\Core\ErrorResponder;

class PositionController extends Controller
{
    private PositionModel $positionModel;
    private DepartmentModel $departmentModel;
    private const PERMISSION_MANAGE = 'config:manage_positions'; 

    public function __construct()
    {
        parent::__construct();
        
        if (!$this->auth->check() || $this->auth->isSuperAdmin()) {
            $this->redirect('/login'); 
        }
        
        $this->positionModel = new PositionModel();
        $this->departmentModel = new DepartmentModel();
    }

    // ----------------------------------------------------------------
    // READ
    // ----------------------------------------------------------------

    public function index(): void
    {
        try {
            $positions = $this->positionModel->getAllByTenant();
            $departments = $this->departmentModel->getAllByTenant();

            // Add a "No Department" option (ID 0) for the dropdown
            array_unshift($departments, [
                'id' => 0, 
                'name' => '-- No Department (Optional) --', 
                'created_at' => null
            ]);
            
            $this->view('positions/index', [
                'title' => 'Positions', // Added title
                'positions' => $positions,
                'departments' => $departments, 
                'successMessage' => $_SESSION['flash_success'] ?? null,
                'errorMessage' => $_SESSION['flash_error'] ?? null,
            ]);
            
            unset($_SESSION['flash_success'], $_SESSION['flash_error']);
            
        } catch (\PDOException $e) {
            Log::critical("Position Index failed in Controller. Error: " . $e->getMessage());
            ErrorResponder::respond(500, "Could not load the list of positions due to a temporary server error.");
        } catch (\Exception $e) {
            ErrorResponder::respond(403, $e->getMessage());
        }
    }

    // ----------------------------------------------------------------
    // CREATE
    // ----------------------------------------------------------------

    public function store(): void
    {
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/positions');
        }
        if (!CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token.";
            $this->redirect('/positions');
        }

        // 1. Data Sanitization and Validation
        $title = Sanitizer::text($_POST['title'] ?? ''); 
        $departmentId = (int)Sanitizer::text($_POST['department_id'] ?? 0); 

        // VALIDATION: Only title is strictly required
        if (empty($title) || strlen($title) > 255) {
            $_SESSION['flash_error'] = "Position title is required and must be under 255 characters.";
            $this->redirect('/positions');
        }
        
        // Prepare departmentId for the model (0 becomes null for the database)
        $departmentIdForDB = ($departmentId === 0) ? null : $departmentId;

        // 2. Business Logic
        try {
            $newId = $this->positionModel->create($title, $departmentIdForDB);
            
            Log::info("Position created: ID {$newId} by User {$this->userId} in Tenant {$this->tenantId}.");
            $_SESSION['flash_success'] = "Position '{$title}' created successfully!";
            
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                 // Unique key is (tenant_id, title)
                 $_SESSION['flash_error'] = "Database error: A position with the title '{$title}' already exists in your organization.";
            } else {
                 $_SESSION['flash_error'] = "Database error: Could not create position. Invalid department selected?";
            }
            
        } catch (\Exception $e) {
             $_SESSION['flash_error'] = $e->getMessage();
        }

        $this->redirect('/positions');
    }

    // ----------------------------------------------------------------
    // UPDATE
    // ----------------------------------------------------------------

    public function update(string $id): void
    {
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/positions');
        }
        if (!CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token.";
            $this->redirect('/positions');
        }
        
        $positionId = (int)Sanitizer::text($id); 
        if ($positionId === 0) {
             $_SESSION['flash_error'] = "Invalid position ID provided.";
             $this->redirect('/positions');
        }

        // 1. Data Sanitization and Validation
        $title = Sanitizer::text($_POST['title'] ?? '');
        $departmentId = (int)Sanitizer::text($_POST['department_id'] ?? 0);

        if (empty($title) || strlen($title) > 255) {
            $_SESSION['flash_error'] = "Position title is required and must be under 255 characters.";
            $this->redirect('/positions');
        }
        
        // Prepare departmentId for the model (0 becomes null for the database)
        $departmentIdForDB = ($departmentId === 0) ? null : $departmentId;


        // 2. Business Logic
        try {
            $success = $this->positionModel->update($positionId, $title, $departmentIdForDB);

            if ($success) {
                Log::info("Position updated: ID {$positionId} to '{$title}' by User {$this->userId} in Tenant {$this->tenantId}.");
                $_SESSION['flash_success'] = "Position '{$title}' updated successfully!";
            } else {
                $_SESSION['flash_error'] = "Update failed: Position not found for this tenant or no changes were made.";
            }
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                 $_SESSION['flash_error'] = "Update failed: A position with the title '{$title}' already exists in your organization.";
            } else {
                 Log::critical("DB Error during Position update (ID: {$positionId}): " . $e->getMessage());
                 $_SESSION['flash_error'] = "Database error: Could not update position due to a server issue.";
            }
        } catch (\Exception $e) {
             $_SESSION['flash_error'] = $e->getMessage();
        }

        $this->redirect('/positions');
    }

    // ----------------------------------------------------------------
    // DELETE
    // ----------------------------------------------------------------

    public function delete(string $id): void
    {
        $this->checkActionIsAllowed();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/positions');
        }

        if (!CsrfToken::validate($_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = "Security error: Invalid CSRF token.";
            $this->redirect('/positions');
        }
        
        $positionId = (int)Sanitizer::text($id); 
        
        if ($positionId === 0) {
             $_SESSION['flash_error'] = "Invalid position ID provided for deletion.";
             $this->redirect('/positions');
        }
        
        try {
            // 1. Critical Pre-Deletion Check
            if ($this->positionModel->hasAssociatedEmployees($positionId)) {
                $_SESSION['flash_error'] = "Cannot delete: This position is currently assigned to one or more employees. Please reassign them first.";
                $this->redirect('/positions');
            }

            // 2. Business Logic
            $success = $this->positionModel->delete($positionId);

            if ($success) {
                Log::info("Position deleted: ID {$positionId} by User {$this->userId} in Tenant {$this->tenantId}.");
                $_SESSION['flash_success'] = "Position deleted successfully!";
            } else {
                $_SESSION['flash_error'] = "Deletion failed: Position not found or already deleted.";
            }
        } catch (\PDOException $e) {
            Log::critical("DB Error during Position delete (ID: {$positionId}): " . $e->getMessage());
            $_SESSION['flash_error'] = "Database error: Could not delete position.";
            
        } catch (\Exception $e) {
             $_SESSION['flash_error'] = $e->getMessage();
        }

        $this->redirect('/positions');
    }
}