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
     * @param array $where Optional WHERE clause conditions.
     * @return array
     */
    public function all(array $where = []): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        if (!empty($where)) {
            $sql .= " WHERE ";
            $conditions = [];
            foreach ($where as $key => $value) {
                $conditions[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
            $sql .= implode(' AND ', $conditions);
        }
        $sql .= " ORDER BY key_name ASC";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            Log::error("Failed to retrieve all features. Error: " . $e->getMessage());
            return [];
        }
    }
}
