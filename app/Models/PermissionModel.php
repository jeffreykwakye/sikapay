<?php

declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use \PDO;

class PermissionModel extends Model
{
    protected string $table = 'permissions';

    public function __construct()
    {
        parent::__construct($this->table);
        $this->noTenantScope = true;
    }

    /**
     * Get all permissions.
     *
     * @param array $where Optional conditions to filter the permissions.
     * @return array An array of all permission records.
     */
    public function all(array $where = []): array
    {
        return parent::all($where);
    }
}
