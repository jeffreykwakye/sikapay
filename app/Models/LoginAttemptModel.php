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

    public const NOT_LOCKED = 0;
    public const JUST_LOCKED = 1;
    public const ALREADY_LOCKED = 2;

    public function getLockoutStatus(string $email): int
    {
        $sql = "SELECT attempts, locked_until FROM {$this->table} WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$result) {
            return self::NOT_LOCKED; // No attempts recorded, not locked out.
        }

        // Case 1: There is an active lockout period. Re-apply the lock to extend the penalty.
        if ($result['locked_until'] && strtotime($result['locked_until']) > time()) {
            $this->lockAccount($email); // Re-locks the account, effectively extending the lockout from NOW.
            Log::warning("Lockout extended for email: {$email}");
            return self::ALREADY_LOCKED;
        }

        // Case 2: The lockout period has expired. Reset attempts and allow login.
        if ($result['locked_until'] && strtotime($result['locked_until']) <= time()) {
            $this->clearAttempts($email);
            return self::NOT_LOCKED; // Lockout expired, let them try again.
        }

        // Case 3: No active lockout, but attempts have reached the limit. Trigger a new lockout.
        if ($result['attempts'] >= self::MAX_ATTEMPTS) {
            $this->lockAccount($email);
            return self::JUST_LOCKED; // Account is now locked for the first time.
        }

        return self::NOT_LOCKED; // Not locked out.
    }

    private function lockAccount(string $email): void
    {
        $lockUntil = (new DateTime())->modify('+' . self::LOCKOUT_TIME . ' minutes')->format('Y-m-d H:i:s');
        $sql = "UPDATE {$this->table} SET locked_until = :locked_until WHERE email = :email";
        $this->db->prepare($sql)->execute(['locked_until' => $lockUntil, 'email' => $email]);
        Log::warning("Account locked for email: {$email}");
    }
}
