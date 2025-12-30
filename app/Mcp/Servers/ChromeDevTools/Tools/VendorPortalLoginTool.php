<?php

namespace App\Mcp\Servers\ChromeDevTools\Tools;

use App\Mcp\Servers\ChromeDevTools\Services\BrowserService;
use App\Mcp\Servers\Tool;

class VendorPortalLoginTool extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'vendor-portal-login';

    /**
     * The tool's description.
     */
    protected string $description = 'Login to a vendor portal and save session cookies for reuse';

    /**
     * Define the tool's input schema.
     */
    protected function inputSchema(): array
    {
        return [
            'url' => [
                'type' => 'string',
                'description' => 'The URL of the vendor login page',
                'required' => true,
            ],
            'username' => [
                'type' => 'string',
                'description' => 'The username or email for login',
                'required' => true,
            ],
            'password' => [
                'type' => 'string',
                'description' => 'The password for login',
                'required' => true,
            ],
            'username_selector' => [
                'type' => 'string',
                'description' => 'CSS selector for username field (default: tries common patterns)',
                'required' => false,
            ],
            'password_selector' => [
                'type' => 'string',
                'description' => 'CSS selector for password field (default: tries common patterns)',
                'required' => false,
            ],
            'submit_selector' => [
                'type' => 'string',
                'description' => 'CSS selector for submit button (default: tries common patterns)',
                'required' => false,
            ],
            'timeout' => [
                'type' => 'integer',
                'description' => 'Timeout in milliseconds (default: 30000)',
                'required' => false,
            ],
        ];
    }

    /**
     * Execute the tool.
     */
    public function execute(array $inputs): string
    {
        $url = $inputs['url'];
        $username = $inputs['username'];
        $password = $inputs['password'];
        $usernameSelector = $inputs['username_selector'] ?? null;
        $passwordSelector = $inputs['password_selector'] ?? null;
        $submitSelector = $inputs['submit_selector'] ?? null;
        $timeout = $inputs['timeout'] ?? 30000;

        try {
            // Use shared browser service
            $page = BrowserService::getPage();

            // Navigate to login page
            $page->navigate($url)->waitForNavigation('networkIdle', $timeout);

            // Build JavaScript to find and fill the login form
            $loginScript = $this->buildLoginScript(
                $username,
                $password,
                $usernameSelector,
                $passwordSelector,
                $submitSelector
            );

            // Execute the login script
            $loginResult = $page->evaluate($loginScript)->getReturnValue();

            if (! $loginResult['success']) {
                return json_encode([
                    'success' => false,
                    'error' => $loginResult['error'] ?? 'Failed to find login form elements',
                    'url' => $url,
                ], JSON_PRETTY_PRINT);
            }

            // Wait for navigation after form submission (indicates login attempt completed)
            try {
                $page->waitForNavigation('networkIdle', $timeout);
            } catch (\Exception $e) {
                // Some sites don't navigate, they just update the page
                sleep(2); // Give it a moment to process
            }

            // Get the current URL to check if login was successful
            $currentUrl = $page->evaluate('window.location.href')->getReturnValue();

            // Get cookies from the page
            $cookies = $this->getCookies($page);

            // Save session to State directory
            $sessionPath = $this->saveSession($url, $username, $cookies, $currentUrl);

            return json_encode([
                'success' => true,
                'message' => 'Successfully logged in and saved session',
                'login_url' => $url,
                'current_url' => $currentUrl,
                'username' => $username,
                'session_file' => $sessionPath,
                'cookies_count' => count($cookies),
            ], JSON_PRETTY_PRINT);
        } catch (\Exception $e) {
            return json_encode([
                'success' => false,
                'error' => $e->getMessage(),
                'url' => $url,
            ], JSON_PRETTY_PRINT);
        }
    }

    /**
     * Build JavaScript code to find and fill the login form.
     */
    protected function buildLoginScript(
        string $username,
        string $password,
        ?string $usernameSelector = null,
        ?string $passwordSelector = null,
        ?string $submitSelector = null
    ): string {
        $usernameEscaped = addslashes($username);
        $passwordEscaped = addslashes($password);

        // Build selectors - try custom first, then common patterns
        $usernameSelectorsList = $usernameSelector
            ? "['".addslashes($usernameSelector)."']"
            : "['input[name=\"username\"]', 'input[name=\"email\"]', 'input[type=\"email\"]', 'input[id*=\"username\" i]', 'input[id*=\"email\" i]', 'input[placeholder*=\"username\" i]', 'input[placeholder*=\"email\" i]']";

        $passwordSelectorsList = $passwordSelector
            ? "['".addslashes($passwordSelector)."']"
            : "['input[name=\"password\"]', 'input[type=\"password\"]', 'input[id*=\"password\" i]']";

        $submitSelectorsList = $submitSelector
            ? "['".addslashes($submitSelector)."']"
            : "['button[type=\"submit\"]', 'input[type=\"submit\"]', 'button:contains(\"Log in\")', 'button:contains(\"Sign in\")', 'button:contains(\"Login\")']";

        return <<<JAVASCRIPT
(function() {
    try {
        // Find username field
        const usernameSelectors = {$usernameSelectorsList};
        let usernameField = null;
        for (const selector of usernameSelectors) {
            usernameField = document.querySelector(selector);
            if (usernameField) break;
        }

        if (!usernameField) {
            return { success: false, error: 'Could not find username/email field' };
        }

        // Find password field
        const passwordSelectors = {$passwordSelectorsList};
        let passwordField = null;
        for (const selector of passwordSelectors) {
            passwordField = document.querySelector(selector);
            if (passwordField) break;
        }

        if (!passwordField) {
            return { success: false, error: 'Could not find password field' };
        }

        // Fill in credentials
        usernameField.value = '{$usernameEscaped}';
        passwordField.value = '{$passwordEscaped}';

        // Trigger input events (some forms require this)
        usernameField.dispatchEvent(new Event('input', { bubbles: true }));
        usernameField.dispatchEvent(new Event('change', { bubbles: true }));
        passwordField.dispatchEvent(new Event('input', { bubbles: true }));
        passwordField.dispatchEvent(new Event('change', { bubbles: true }));

        // Find and click submit button
        const submitSelectors = {$submitSelectorsList};
        let submitButton = null;
        for (const selector of submitSelectors) {
            submitButton = document.querySelector(selector);
            if (submitButton) break;
        }

        // Try to find submit button within the form if not found globally
        if (!submitButton) {
            const form = usernameField.closest('form');
            if (form) {
                submitButton = form.querySelector('button[type="submit"]') ||
                              form.querySelector('input[type="submit"]') ||
                              form.querySelector('button');
            }
        }

        if (submitButton) {
            submitButton.click();
        } else if (passwordField.form) {
            // If no button found, try submitting the form directly
            passwordField.form.submit();
        } else {
            return { success: false, error: 'Could not find submit button or form' };
        }

        return {
            success: true,
            message: 'Login form filled and submitted',
            usernameSelector: usernameSelectors.find(s => document.querySelector(s)),
            passwordSelector: passwordSelectors.find(s => document.querySelector(s))
        };
    } catch (error) {
        return { success: false, error: error.message };
    }
})();
JAVASCRIPT;
    }

    /**
     * Get cookies from the current page.
     */
    protected function getCookies($page): array
    {
        try {
            $cookiesJson = $page->evaluate('JSON.stringify(document.cookie)')->getReturnValue();
            $cookieString = json_decode($cookiesJson);

            $cookies = [];
            if (! empty($cookieString)) {
                $cookiePairs = explode('; ', $cookieString);
                foreach ($cookiePairs as $pair) {
                    [$name, $value] = explode('=', $pair, 2);
                    $cookies[$name] = $value;
                }
            }

            return $cookies;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Save session data to State directory.
     */
    protected function saveSession(string $url, string $username, array $cookies, string $currentUrl): string
    {
        $stateDir = __DIR__.'/../State';
        $sessionFile = $stateDir.'/session.json';

        // Ensure State directory exists
        if (! file_exists($stateDir)) {
            mkdir($stateDir, 0755, true);
        }

        $sessionData = [
            'login_url' => $url,
            'current_url' => $currentUrl,
            'username' => $username,
            'cookies' => $cookies,
            'logged_in_at' => now()->toIso8601String(),
            'timestamp' => time(),
        ];

        file_put_contents($sessionFile, json_encode($sessionData, JSON_PRETTY_PRINT));

        return $sessionFile;
    }
}
