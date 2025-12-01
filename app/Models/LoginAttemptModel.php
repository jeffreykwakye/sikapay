<?php
declare(strict_types=1);

namespace Jeffrey\Sikapay\Models;

use Jeffrey\Sikapay\Core\Model;
use Jeffrey\Sikapay\Core\Log;
use \DateTime;

class LoginAttemptModel extends Model
{
    protected string $table = 'login_attempts';
    private const MAX_ATTEMPTS = 3;
    private const LOCKOUT_TIME = 15; // In minutes

    public function __construct()
    {
        $this->noTenantScope = true; // This table is not scoped by tenant
        parent::__construct($this->table);
    }

    public function recordFailedAttempt(string $email, string $ipAddress): void
    {
        $sql = "INSERT INTO {$this->table} (email, ip_address, attempts, last_attempt_at)
                VALUES (:email, :ip_address, 1, NOW())
                ON DUPLICATE KEY UPDATE attempts = attempts + 1, last_attempt_at = NOW()";
        
        $this->db->prepare($sql)->execute(['email' => $email, 'ip_address' => $ipAddress]);
    }

    public function clearAttempts(string $email): void
    {
        $sql = "DELETE FROM {$this->table} WHERE email = :email";
        $this->db->prepare($sql)->execute(['email' => $email]);
    }

    public function isLockedOut(string $email): bool
    {
        $sql = "SELECT attempts, locked_until FROM {$this->table} WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result) {
            if ($result['locked_until'] && strtotime($result['locked_until']) > time()) {
                return true; // Still locked out
            }

            if ($result['attempts'] >= self::MAX_ATTEMPTS) {
                $this->lockAccount($email);
                return true;
            }
        }

        return false;
    }

    private function lockAccount(string $email): void
    {
        $lockUntil = (new DateTime())->modify('+' . self::LOCKOUT_TIME . ' minutes')->format('Y-m-d H:i:s');
        $sql = "UPDATE {$this->table} SET locked_until = :locked_until WHERE email = :email";
        $this->db->prepare($sql)->execute(['locked_until' => $lockUntil, 'email' => $email]);
        Log::warning("Account locked for email: {$email}");
    }
}
