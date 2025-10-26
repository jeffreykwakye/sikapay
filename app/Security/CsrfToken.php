<?php 

namespace Jeffrey\Sikapay\Security; // Use a dedicated Security namespace

class CsrfToken
{
    // Keeping this private is correct for encapsulation
    private const SESSION_KEY = 'csrf_token'; 

    /**
     * Ensures the CSRF token is present in the session.
     */
    public static function init(): void
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            // Generate a secure, randomized token
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }
    }

    
    
    /**
     * Clears the CSRF token from the session without destroying the session itself.
     */
    public static function destroyToken(): void
    {
        if (isset($_SESSION[self::SESSION_KEY])) {
            unset($_SESSION[self::SESSION_KEY]);
        }
    }
    // -------------------------------------------------------------------
    
    /**
     * Generates and returns the HTML for the hidden CSRF token field.
     * @return string The hidden input field HTML.
     */
    public static function field(): string
    {
        self::init(); 
        $token = self::getToken();
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"{$token}\">";
    }

    /**
     * Retrieves the current CSRF token from the session.
     * @return string
     */
    public static function getToken(): string
    {
        return $_SESSION[self::SESSION_KEY] ?? '';
    }

    /**
     * Validates the request token against the session token.
     *
     * @param string $requestToken The token submitted via the form.
     * @return bool True if tokens match.
     */
    public static function validate(string $requestToken): bool
    {
        return !empty($requestToken) && hash_equals(self::getToken(), $requestToken);
    }
}