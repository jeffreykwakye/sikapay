<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use \PDO;

class UserPermissionModel extends Model
{
    protected string $table = 'user_permissions';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Get all individual permissions for a specific user.
     *
     * @param int $userId The ID of the user.
     * @return array An array of permission records for the user.
     */
    public function getPermissionsForUser(int $userId): array
    {
        $stmt = $this->db->prepare("SELECT permission_id, is_allowed FROM {$this->table} WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        // Fetch all results and reformat into an associative array
        $permissions = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $permissions[$row['permission_id']] = (bool)$row['is_allowed'];
        }
        return $permissions;
    }

    /**
     * Delete all individual permissions for a specific user.
     *
     * @param int $userId The ID of the user.
     * @return bool True on success, false on failure.
     */
    public function deletePermissionsForUser(int $userId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = :user_id");
        return $stmt->execute([':user_id' => $userId]);
    }

    /**
     * Delete a specific individual permission for a user.
     *
     * @param int $userId The ID of the user.
     * @param int $permissionId The ID of the permission to delete.
     * @return bool True on success, false on failure.
     */
    public function deletePermissionForUser(int $userId, int $permissionId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE user_id = :user_id AND permission_id = :permission_id");
        return $stmt->execute([
            ':user_id' => $userId,
            ':permission_id' => $permissionId
        ]);
    }

    /**
     * Add or update an individual permission for a specific user.
     *
     * @param int $userId The ID of the user.
     * @param int $permissionId The ID of the permission.
     * @param bool $isAllowed Whether the permission is allowed (TRUE) or denied (FALSE).
     * @return bool True on success, false on failure.
     */
    public function addPermissionToUser(int $userId, int $permissionId, bool $isAllowed = true): bool
    {
        $sql = "INSERT INTO {$this->table} (user_id, permission_id, is_allowed) 
                VALUES (:user_id, :permission_id, :is_allowed)
                ON DUPLICATE KEY UPDATE is_allowed = :is_allowed";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':permission_id' => $permissionId,
            ':is_allowed' => (int)$isAllowed // PDO doesn't handle bool directly, cast to int
        ]);
    }
}
