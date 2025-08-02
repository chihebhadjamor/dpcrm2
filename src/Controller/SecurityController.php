<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bundle\SecurityBundle\Security;

class SecurityController extends AbstractController
{
    #[Route("/login", name: "app_login")]
    public function login(AuthenticationUtils $authenticationUtils)
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route("/verify-2fa", name: "app_verify_2fa")]
    public function verify2fa(Request $request, Security $security, SessionInterface $session, CsrfTokenManagerInterface $csrfTokenManager, TokenStorageInterface $tokenStorage)
    {
        // Get the current user
        $user = $security->getUser();

        // If no user is logged in or user doesn't have 2FA enabled, redirect to login
        if (!$user instanceof User || !$user->isIs2faEnabled() || !$user->getSecret2fa()) {
            return $this->redirectToRoute('app_login');
        }

        // Check if 2FA is already verified for this session
        if ($session->get('2fa_verified') === true) {
            // 2FA already verified, redirect to the default target path
            return $this->redirectToRoute('app_accounts');
        }

        $error = null;

        // Handle form submission
        if ($request->isMethod('POST')) {
            // Validate CSRF token
            $token = new CsrfToken('authenticate_2fa', $request->request->get('_csrf_token'));
            if (!$csrfTokenManager->isTokenValid($token)) {
                $error = 'Invalid CSRF token.';
            } else {
                $code = $request->request->get('verification_code');

                // Verify the 2FA code
                if ($this->verifyTotpCode($user->getSecret2fa(), $code)) {
                    // Mark 2FA as verified for this session
                    $session->set('2fa_verified', true);

                    // Redirect to the default target path
                    return $this->redirectToRoute('app_accounts');
                } else {
                    $error = 'Invalid verification code. Please try again.';
                }
            }
        }

        return $this->render('security/verify_2fa.html.twig', [
            'error' => $error,
        ]);
    }

    /**
     * Verifies a TOTP code against a given secret
     *
     * @param string $secret The Base32 encoded secret
     * @param string $code The 6-digit code to verify
     * @param int $window Time window in 30-second units (default: 1 unit before and after)
     * @return bool Whether the code is valid
     */
    private function verifyTotpCode(string $secret, string $code, int $window = 1): bool
    {
        // Clean up the code (remove spaces, etc.)
        $code = preg_replace('/\s+/', '', $code);

        // Validate code format (must be 6 digits)
        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        // Get current timestamp
        $now = new \DateTimeImmutable();
        $timestamp = $now->getTimestamp();

        // Check codes in the time window
        for ($i = -$window; $i <= $window; $i++) {
            $checkTime = floor($timestamp / 30) + $i;
            $generatedCode = $this->generateTotpCode($secret, $checkTime);

            if (hash_equals($generatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates a TOTP code for a given secret and timestamp
     *
     * @param string $secret The Base32 encoded secret
     * @param int|null $timestamp The timestamp to use (null for current time)
     * @return string The 6-digit TOTP code
     */
    private function generateTotpCode(string $secret, ?int $timestamp = null): string
    {
        // Remove padding characters
        $secret = str_replace('=', '', $secret);

        // Convert Base32 secret to binary
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secretBinary = '';

        // Process 8 characters (40 bits) at a time
        for ($i = 0; $i < strlen($secret); $i += 8) {
            $chunk = substr($secret, $i, 8);
            $buffer = 0;
            $bitsLeft = 0;

            // Process each character in the chunk
            for ($j = 0; $j < strlen($chunk); $j++) {
                $buffer <<= 5;
                $buffer |= strpos($base32Chars, $chunk[$j]);
                $bitsLeft += 5;

                if ($bitsLeft >= 8) {
                    $bitsLeft -= 8;
                    $secretBinary .= chr(($buffer >> $bitsLeft) & 0xFF);
                }
            }
        }

        // Use current timestamp if none provided
        if ($timestamp === null) {
            $timestamp = floor(time() / 30);
        }

        // Create binary timestamp (big-endian)
        $timestampBinary = pack('N*', 0, $timestamp);

        // Generate HMAC-SHA1 hash
        $hash = hash_hmac('sha1', $timestampBinary, $secretBinary, true);

        // Get offset from last 4 bits of the hash
        $offset = ord($hash[19]) & 0x0F;

        // Get 4 bytes from the hash starting at offset
        $value = unpack('N', substr($hash, $offset, 4))[1];

        // Remove the most significant bit (RFC 4226)
        $value = $value & 0x7FFFFFFF;

        // Get 6 digits
        $modulo = pow(10, 6);
        $code = str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);

        return $code;
    }

    #[Route("/logout", name: "app_logout")]
    public function logout()
    {
        // This method can be empty - it will be intercepted by the logout key on your firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
