<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;

class DepartmentModel extends Model
{
    public function __construct()
    {
        // The constructor passes the table name to the parent Model
        parent::__construct('departments');
    }
    
    // All methods are inherited from Model.php (find, all) 
    // and will automatically apply tenant scoping.
}