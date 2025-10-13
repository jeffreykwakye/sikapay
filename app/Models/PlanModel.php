<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;

class PlanModel extends Model
{
    // Flag to bypass tenant scoping (Plans do not have a tenant_id column)
    protected bool $noTenantScope = true; 

    public function __construct()
    {
        parent::__construct('plans');
    }
    
    // The all() method is correctly inherited from the base Model.
}