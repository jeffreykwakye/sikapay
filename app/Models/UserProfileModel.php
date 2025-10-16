<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;

class UserProfileModel extends Model
{
    // Note: This table does NOT have a tenant_id column.
    public function __construct()
    {
        parent::__construct('user_profiles');
    }
    
    /**
     * Creates a new user profile record.
     */
    public function createProfile(array $data): bool
    {
        $sql = "INSERT INTO user_profiles (user_id, date_of_birth, nationality, marital_status, gender, home_address, ssnit_number, tin_number, id_card_type, id_card_number, emergency_contact_name, emergency_contact_phone) 
                 VALUES (:user_id, :date_of_birth, :nationality, :marital_status, :gender, :home_address, :ssnit_number, :tin_number, :id_card_type, :id_card_number, :emergency_contact_name, :emergency_contact_phone)";
        
        $stmt = $this->db->prepare($sql);
        
        $defaults = [
            'home_address' => null, 
            'ssnit_number' => null, 
            'tin_number' => null, 
            'id_card_type' => 'Ghana Card', 
            'id_card_number' => null,
        ];
        $finalData = array_merge($defaults, $data);

        // Correction: The SQL statement above has a repeated 'home_address' column. 
        // Assuming the correct order is as follows based on the VALUES list:
        $executeData = [
            ':user_id' => $finalData['user_id'],
            ':date_of_birth' => $finalData['date_of_birth'],
            ':nationality' => $finalData['nationality'],
            ':marital_status' => $finalData['marital_status'],
            ':gender' => $finalData['gender'],
            ':home_address' => $finalData['home_address'],
            ':ssnit_number' => $finalData['ssnit_number'], // Only one binding needed here
            ':tin_number' => $finalData['tin_number'], 
            ':id_card_type' => $finalData['id_card_type'],
            ':id_card_number' => $finalData['id_card_number'],
            ':emergency_contact_name' => $finalData['emergency_contact_name'],
            ':emergency_contact_phone' => $finalData['emergency_contact_phone'],
        ];

        return $stmt->execute($executeData);
    }
    
    /**
     * Updates an existing user profile record by user ID.
     * * @param int $userId The ID of the user whose profile to update.
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
        
        // Note: The primary key for this update is the user_id column
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClauses) . " WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($bindParams);
    }
}