<?php 
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;

class PositionModel extends Model
{
    public function __construct()
    {
        parent::__construct('positions');
    }
    
    // ... inherited methods ...
}