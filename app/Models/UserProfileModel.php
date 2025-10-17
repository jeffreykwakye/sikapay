<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log; 
use Jeffrey\Sikapay\Core\Auth; 
use \PDOException;


class UserProfileModel extends Model
{
    // Note: This table does NOT have a tenant_id column, so we must rely solely on user_id security.
    public function __construct()
    {
        // Must explicitly set noTenantScope to true if the table doesn't have the column, 
        // though the base model might infer this if the column check fails.
        // For models managing user-specific details, we rely on user_id in WHERE clauses.
        parent::__construct('user_profiles');
    }
    
    /**
     * Creates a new user profile record.
     */
    public function createProfile(array $data): bool
    {
        $sql = "INSERT INTO user_profiles (user_id, date_of_birth, nationality, marital_status, gender, home_address, ssnit_number, tin_number, id_card_type, id_card_number, emergency_contact_name, emergency_contact_phone) 
                  VALUES (:user_id, :date_of_birth, :nationality, :marital_status, :gender, :home_address, :ssnit_number, :tin_number, :id_card_type, :id_card_number, :emergency_contact_name, :emergency_contact_phone)";
        
        $defaults = [
            'home_address' => null, 
            'ssnit_number' => null, 
            'tin_number' => null, 
            'id_card_type' => 'Ghana Card', 
            'id_card_number' => null,
        ];
        $finalData = array_merge($defaults, $data);

        // Map data to execution parameters, ensuring no sensitive data is in the key names for logging
        $executeData = [
            ':user_id' => $finalData['user_id'],
            ':date_of_birth' => $finalData['date_of_birth'],
            ':nationality' => $finalData['nationality'],
            ':marital_status' => $finalData['marital_status'],
            ':gender' => $finalData['gender'],
            ':home_address' => $finalData['home_address'],
            ':ssnit_number' => $finalData['ssnit_number'], 
            ':tin_number' => $finalData['tin_number'], 
            ':id_card_type' => $finalData['id_card_type'],
            ':id_card_number' => $finalData['id_card_number'],
            ':emergency_contact_name' => $finalData['emergency_contact_name'],
            ':emergency_contact_phone' => $finalData['emergency_contact_phone'],
        ];

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($executeData);
        } catch (PDOException $e) {
            // Log failure to create profile (PII integrity)
            Log::error("PROFILE CREATE FAILED for User {$finalData['user_id']}. Error: " . $e->getMessage(), [
                'email_partial' => substr($finalData['email'] ?? 'N/A', 0, 10) . '...', // Sanitize PII
                'db_error' => $e->getMessage()
            ]);
            // Re-throw the exception: Profile creation is usually mandatory during user setup.
            throw $e;
        }
    }
    
    /**
     * Updates an existing user profile record by user ID.
     * @param int $userId The ID of the user whose profile to update.
     * @param array $data The data to update.
     * @return bool True on success (even if no rows were affected).
     */
    public function updateProfile(int $userId, array $data): bool
    {
        $setClauses = [];
        $bindParams = [':user_id' => $userId];
        
        foreach ($data as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
            $bindParams[":{$key}"] = $value;
        }
        
        if (empty($setClauses)) {
            return true;
        }
        
        // IMPORTANT SECURITY: Update must be secured by the logged-in user's tenant ID if possible, 
        // but since user_profiles has no tenant_id, we rely on application logic 
        // ensuring the acting user has permission to update the target $userId.
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($bindParams);
        } catch (PDOException $e) {
            // Log failure to update profile (PII integrity)
            Log::error("PROFILE UPDATE FAILED for target User {$userId}. Error: " . $e->getMessage(), [
                'updated_keys' => array_keys($data),
                'acting_user_id' => Auth::userId()
            ]);
            // Re-throw the exception: Data integrity is paramount for PII.
            throw $e;
        }
    }
}