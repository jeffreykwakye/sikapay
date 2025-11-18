<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \PDOException;

class FeatureModel extends Model
{
    public function __construct()
    {
        parent::__construct('features');
    }

    /**
     * Retrieves all features.
     * @return array
     */
    public function all(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY key_name ASC";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve all features. Error: " . $e->getMessage());
            return [];
        }
    }
}
