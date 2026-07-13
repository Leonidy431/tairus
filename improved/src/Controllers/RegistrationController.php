<?php
/**
 * Registration Controller
 *
 * Handles user registration form display and submission.
 * Includes input validation, security checks, and email verification flow.
 */

namespace App\Controllers;

use App\Repository\UserRepository;
use App\Security\Sanitizer;
use App\Security\CsrfToken;
use App\Mail\Mailer;

class RegistrationController
{
    private UserRepository $userRepository;
    private Mailer $mailer;
    private array $config;
    private array $errors = [];

    public function __construct(UserRepository $userRepository, Mailer $mailer, array $config)
    {
        $this->userRepository = $userRepository;
        $this->mailer = $mailer;
        $this->config = $config;
    }

    /**
     * Display registration form
     *
     * @return array Data for rendering
     */
    public function show(): array
    {
        return [
            'title' => 'Register',
            'csrf_token' => CsrfToken::generate(),
        ];
    }

    /**
     * Handle registration form submission
     *
     * @return array Data for rendering (errors or success message)
     */
    public function store(): array
    {
        $data = [
            'title' => 'Register',
            'csrf_token' => CsrfToken::generate(),
        ];

        // Validate CSRF token
        if (!CsrfToken::validateFromRequest()) {
            $data['error'] = 'Security token validation failed. Please try again.';
            return $data;
        }

        // Get and sanitize input
        $email = Sanitizer::email($_POST['email'] ?? '');
        $password = Sanitizer::string($_POST['password'] ?? '', false);
        $passwordConfirm = Sanitizer::string($_POST['password_confirmation'] ?? '', false);
        $name = Sanitizer::string($_POST['name'] ?? '');
        $phone = Sanitizer::phone($_POST['phone'] ?? '') ?? null;

        // Validate inputs
        if (!$this->validateInputs($email, $password, $passwordConfirm, $name)) {
            $data['errors'] = $this->errors;
            $data['old_input'] = [
                'email' => htmlspecialchars($email ?? ''),
                'name' => htmlspecialchars($name ?? ''),
                'phone' => htmlspecialchars($phone ?? ''),
            ];
            return $data;
        }

        // Attempt registration
        $userId = $this->userRepository->register($email, $password, $name, $phone);

        if ($userId === false) {
            $data['error'] = 'Registration failed. Please try again later.';
            return $data;
        }

        // Get user data for email
        $user = $this->userRepository->getById($userId);
        if (!$user) {
            $data['error'] = 'Failed to retrieve user data.';
            return $data;
        }

        // Send verification email
        if (!$this->sendVerificationEmail($user)) {
            $data['error'] = 'Registration successful but email delivery failed. Please request a new verification email.';
            $data['user_id'] = $userId;
            return $data;
        }

        $data['success'] = 'Registration successful! Please check your email to verify your account.';
        $data['user_id'] = $userId;
        return $data;
    }

    /**
     * Handle email verification via token
     *
     * @param string $token Verification token from URL
     * @return array Data for rendering
     */
    public function verify(string $token): array
    {
        $data = ['title' => 'Email Verification'];

        // Sanitize token
        $token = preg_replace('/[^a-f0-9]/', '', $token);

        if (empty($token)) {
            $data['error'] = 'Invalid verification link.';
            return $data;
        }

        // Attempt verification
        if (!$this->userRepository->verifyEmail($token)) {
            $data['error'] = 'Verification failed. Link may be expired or invalid.';
            return $data;
        }

        $data['success'] = 'Email verified successfully! You can now log in.';
        return $data;
    }

    /**
     * Validate all registration inputs
     *
     * @return bool True if all validations pass
     */
    private function validateInputs(?string $email, string $password, string $passwordConfirm, string $name): bool
    {
        $this->errors = [];

        // Email validation
        if (!$email) {
            $this->errors['email'] = 'Invalid email format.';
        } elseif ($this->userRepository->emailExists($email)) {
            $this->errors['email'] = 'This email is already registered.';
        }

        // Password validation
        if (strlen($password) < 8) {
            $this->errors['password'] = 'Password must be at least 8 characters.';
        } elseif (!$this->isPasswordComplex($password)) {
            $this->errors['password'] = 'Password must contain uppercase, lowercase, number, and special character.';
        }

        // Password confirmation
        if ($password !== $passwordConfirm) {
            $this->errors['password_confirmation'] = 'Passwords do not match.';
        }

        // Name validation
        if (strlen($name) < 2 || strlen($name) > 50) {
            $this->errors['name'] = 'Name must be between 2 and 50 characters.';
        }

        return empty($this->errors);
    }

    /**
     * Check password complexity requirements
     *
     * @param string $password Password to validate
     * @return bool True if password meets complexity requirements
     */
    private function isPasswordComplex(string $password): bool
    {
        $hasUpper = preg_match('/[A-Z]/', $password);
        $hasLower = preg_match('/[a-z]/', $password);
        $hasNumber = preg_match('/[0-9]/', $password);
        $hasSpecial = preg_match('/[!@#$%^&*()_\-+=\[\]{};:\'",.<>?\\\/]/', $password);

        return $hasUpper && $hasLower && $hasNumber && $hasSpecial;
    }

    /**
     * Send email verification link to user
     *
     * @param array $user User data
     * @return bool True if email sent successfully
     */
    private function sendVerificationEmail(array $user): bool
    {
        try {
            $verificationUrl = sprintf(
                '%s?action=verify-email&token=%s',
                rtrim($this->config['app']['url'], '/'),
                $user['email_verification_token']
            );

            $htmlBody = sprintf(
                '
                <h2>Verify Your Email</h2>
                <p>Hello %s,</p>
                <p>Thank you for registering! Please verify your email address by clicking the link below:</p>
                <p><a href="%s" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    Verify Email Address
                </a></p>
                <p>Or copy and paste this link in your browser:</p>
                <p>%s</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you did not create this account, you can ignore this email.</p>
                <p>Best regards,<br>%s</p>
                ',
                htmlspecialchars($user['name']),
                htmlspecialchars($verificationUrl),
                htmlspecialchars($verificationUrl),
                htmlspecialchars($this->config['app']['name'])
            );

            $textBody = sprintf(
                "Hello %s,\n\nThank you for registering! Please verify your email address by visiting:\n\n%s\n\nThis link will expire in 24 hours.\n\nIf you did not create this account, you can ignore this email.\n\nBest regards,\n%s",
                $user['name'],
                $verificationUrl,
                $this->config['app']['name']
            );

            $this->mailer
                ->to($user['email'], $user['name'])
                ->subject('Verify Your Email Address - ' . $this->config['app']['name'])
                ->body($textBody)
                ->htmlBody($htmlBody)
                ->send();

            return true;
        } catch (\Exception $e) {
            error_log('Failed to send verification email: ' . $e->getMessage());
            return false;
        }
    }
}
