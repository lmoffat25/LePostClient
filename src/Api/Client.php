<?php

namespace LePostClient\Api;

use LePostClient\Settings\Manager as SettingsManager;
use LePostClient\Exceptions\ApiException;
use LePostClient\Exceptions\ContentGenerationException;

class Client {

    private SettingsManager $settings_manager;
    private ?string $api_key = null;

    private string $api_base_url = 'https://agence-web-prism.fr/wp-json/le-post/v1'; 

    public function __construct(SettingsManager $settings_manager) {
        $this->settings_manager = $settings_manager;
    }

    private function get_api_key(): ?string {
        if ($this->api_key === null) {
            $this->api_key = $this->settings_manager->get_api_key();
        }
        return $this->api_key;
    }

    private function perform_api_request(string $endpoint, string $method = 'POST', array $body_params = [], ?string $override_api_key = null): ?array {
        try {
            $current_api_key = $override_api_key ?? $this->get_api_key();
            if (empty($current_api_key)) {
                throw ApiException::missingApiKey();
            }

            $api_url = rtrim($this->api_base_url, '/') . '/' . ltrim($endpoint, '/');
            
            $request_args = [
                'method'  => strtoupper($method),
                'headers' => [
                    'Authorization' => 'Bearer ' . $current_api_key,
                    'Content-Type'  => 'application/json; charset=utf-8',
                ],
                'timeout' => 300 // Increased timeout to 5 minutes (300 seconds)
            ];

            // Add api_key to body params for backward compatibility / API redundancy
            $body_params['api_key'] = $current_api_key;
            
            if (strtoupper($method) === 'POST' || strtoupper($method) === 'PUT') {
                $request_args['body'] = wp_json_encode($body_params);
            } elseif (!empty($body_params) && strtoupper($method) === 'GET') {
                $api_url = add_query_arg($body_params, $api_url);
            }
            
            // Log request details (without sensitive API key)
            $log_headers = $request_args['headers'];
            $log_headers['Authorization'] = 'Bearer [REDACTED]';
            $log_body = $body_params;
            $log_body['api_key'] = '[REDACTED]';
            error_log("LePostClient API Request: URL: {$api_url}, Method: {$request_args['method']}, Headers: " . wp_json_encode($log_headers));
            if (isset($request_args['body'])) {
                error_log("LePostClient API Request Body: " . wp_json_encode($log_body));
            }
            
            $response = wp_remote_request($api_url, $request_args);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                error_log("LePostClient API Request Failed: " . $error_message);
                throw ApiException::requestFailed($endpoint, $error_message);
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);
            $response_headers = wp_remote_retrieve_headers($response);
            error_log("LePostClient API Response: Code: {$response_code}, Headers: " . wp_json_encode($response_headers->getAll()));
            
            $data = json_decode($response_body, true);

            if ($response_code < 200 || $response_code >= 300 || ($response_body && $data === null && json_last_error() !== JSON_ERROR_NONE)) {
                error_log("LePostClient API Error ({$endpoint}): Code {$response_code}. Body: " . $response_body);
                return ['success' => false, 'message' => "API request failed with HTTP code {$response_code}.", 'http_code' => $response_code];
            }
            
            if ($data === null && ($response_code >= 200 && $response_code < 300)) {
                return ['success' => true, 'data' => []];
            }

            if (isset($data['success']) && $data['success'] === false) {
                $error_message = $data['message'] ?? 'Unknown API error with success=false';
                error_log("LePostClient API Error ({$endpoint}) - API Business Logic Error: {$error_message}");
                return $data;
            }

            return $data;
        } catch (ApiException $e) {
            error_log('LePostClient API Error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            error_log('LePostClient Unexpected Error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'An unexpected error occurred: ' . $e->getMessage()];
        }
    }

    /**
     * Validates an API key and retrieves account info including credits.
     * Uses /verify-api-key and /account/credits endpoints.
     *
     * @param string|null $apiKeyToValidate If null, uses the currently saved API key.
     * @return array|null An array with 'is_valid', 'credits', etc., or null on failure.
     */
    public function get_account_info(?string $apiKeyToValidate = null): ?array {
        try {
            $key_to_use = $apiKeyToValidate ?? $this->get_api_key();

            if (empty($key_to_use)) {
                if ($apiKeyToValidate !== null) { // Specific check for validating an empty key from settings page.
                    return ['is_valid' => false, 'message' => 'Cannot validate an empty API key.'];
                }
                // General case for when key is not set.
                return ['is_valid' => false, 'message' => 'API Key is not set. Please configure it in the settings page.'];
            }

            // Step 1: Verify API Key using the new endpoint
            $verification_response = $this->perform_api_request('verify-api-key', 'POST', [], $key_to_use);

            if ($verification_response === null) {
                return ['is_valid' => false, 'message' => 'Failed to communicate with the API to verify key. Check API URL and key.'];
            }

            if (!isset($verification_response['success']) || $verification_response['success'] !== true || !isset($verification_response['data']['is_valid']) || $verification_response['data']['is_valid'] !== true) {
                $error_message = $verification_response['message'] ?? 'API key is invalid or verification failed.';
                if (isset($verification_response['http_code']) && ($verification_response['http_code'] == 401 || $verification_response['http_code'] == 403)) {
                    $error_message = 'API key rejected or invalid. Please check your API key.';
                }
                return ['is_valid' => false, 'message' => $error_message, 'original_response' => $verification_response];
            }

            $account_info = $verification_response['data'];

            // Step 2: Get credit balance for applicable key types
            if (in_array($account_info['key_type'], ['direct', 'sub'])) {
                $credits_response = $this->perform_api_request('account/credits', 'GET', [], $key_to_use);

                if ($credits_response && isset($credits_response['success']) && $credits_response['success'] === true && isset($credits_response['data']['balance'])) {
                    $account_info['credits'] = $credits_response['data']['balance'];
                } else {
                    $account_info['credits'] = 'N/A';
                    $error_detail = isset($credits_response['message']) ? $credits_response['message'] : 'No details provided.';
                    error_log("LePostClient API: Key '{$account_info['key_type']}' was valid, but failed to fetch credit balance. API said: {$error_detail}");
                }
            } else {
                // For 'master' keys or other types, there's no credit balance.
                $account_info['credits'] = 'N/A';
            }

            return $account_info;
        } catch (ApiException $e) {
            error_log('LePostClient API Error during account info retrieval: ' . $e->getMessage());
            return ['is_valid' => false, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            error_log('LePostClient Unexpected Error during account info retrieval: ' . $e->getMessage());
            return ['is_valid' => false, 'message' => 'An unexpected error occurred during account info retrieval'];
        }
    }
    
    /**
     * Generates post content from a subject.
     *
     * @param string $subject The subject of the post.
     * @param string $subject_explanation The explanation or description of the subject.
     * @param string $company_info Company information to guide content generation.
     * @param string $writing_style The desired writing style, to be used as 'tone'.
     * @return array|null An array containing 'title' and 'content', or null on failure.
     */
    public function generate_content(string $subject, string $subject_explanation, string $company_info, string $writing_style): ?array {
        try {
            // Format the payload according to the API documentation
            $payload = [
                'subject'             => $subject,
                'subject_explanation' => $subject_explanation,
                'company_info'        => $company_info,
                'writing_style'       => [
                    'article' => $writing_style
                ],
                'publication_type'    => ['article'],
                'site_url'            => \get_site_url(), // Get the WordPress site URL
            ];

            error_log("LePostClient API: Sending generate-content request with payload: " . wp_json_encode($payload));
            $response = $this->perform_api_request('generate-content', 'POST', $payload);
            
            if ($response === null) {
                throw ContentGenerationException::failed('Failed to communicate with the API');
            }
            
            // Log the full response structure for debugging
            error_log("LePostClient API: Full generate-content response: " . wp_json_encode($response));
            
            // Handle the documented API response structure for multi-publication responses
            if (isset($response['success']) && $response['success'] === true && isset($response['data']['article']['content'])) {
                error_log("LePostClient API: Found standard response structure with article content");
                return $response['data']['article'];
            }
            
            // WORKAROUND: Handle the observed malformed API response where the data is nested in an empty key
            if (isset($response['success']) && $response['success'] === true && isset($response['data']) && is_array($response['data'])) {
                $first_item = reset($response['data']);
                if (is_array($first_item) && isset($first_item['content'])) {
                    error_log("LePostClient API WORKAROUND: Successfully parsed malformed 'generate-content' response.");
                    return $first_item;
                }
            }
            
            // WORKAROUND 2: Try a direct structure where content is at the top level
            if (isset($response['success']) && $response['success'] === true && isset($response['content'])) {
                error_log("LePostClient API WORKAROUND 2: Found content at the top level of response");
                return [
                    'title' => $response['title'] ?? $subject,
                    'content' => $response['content'],
                    'images' => $response['images'] ?? []
                ];
            }
            
            // WORKAROUND 3: Try if the response itself is the article content
            if (isset($response['title']) && isset($response['content'])) {
                error_log("LePostClient API WORKAROUND 3: Response itself appears to be the article content");
                return $response;
            }
            
            if (isset($response['success']) && $response['success'] === false) {
                $error_message = $response['message'] ?? 'Unknown API error';
                throw ContentGenerationException::failed($error_message);
            }
            
            error_log("LePostClient API Error: Response doesn't match any expected format: " . wp_json_encode($response));
            throw ContentGenerationException::missingContent();
        } catch (ContentGenerationException $e) {
            error_log('LePostClient Content Generation Error: ' . $e->getMessage());
            return null;
        } catch (ApiException $e) {
            error_log('LePostClient API Error during content generation: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            error_log('LePostClient Unexpected Error during content generation: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generates AI-powered post ideas based on a theme and count.
     *
     * @param string $theme The central theme for idea generation.
     * @param int    $count The number of ideas to generate (typically 1-10).
     * @return array|\WP_Error An associative array with 'ideas' and 'free_usage_info' on success, 
     *                         or a WP_Error object on failure or if the API returns an error.
     */
    public function generate_ai_ideas(string $theme, int $count): array|\WP_Error {
        try {
            if (empty($theme)) {
                return new \WP_Error('missing_theme', __('Theme cannot be empty for AI idea generation.', 'lepostclient'));
            }
            if ($count <= 0) {
                return new \WP_Error('invalid_count', __('Number of ideas must be positive.', 'lepostclient'));
            }
            
            $payload = [
                'theme' => $theme,
                'count' => $count,
            ];
            
            $response = $this->perform_api_request('generate-ideas', 'POST', $payload);

            if ($response === null) {
                throw ApiException::requestFailed('generate-ideas', 'Failed to communicate with the API');
            }

            if (isset($response['success']) && $response['success'] === true && isset($response['data']['ideas'])) {
                // Expecting 'ideas' and potentially 'free_usage_info' within 'data'
                return [
                    'ideas' => $response['data']['ideas'],
                    'free_usage_info' => $response['data']['free_usage_info'] ?? null 
                ];
            }
            
            // Handle API-specific errors or general failures
            $error_message = __('An unknown error occurred while generating AI ideas.', 'lepostclient');
            if (isset($response['message'])) {
                $error_message = $response['message'];
            } elseif (isset($response['data']['message'])) { // Sometimes error messages are nested in 'data'
                $error_message = $response['data']['message'];
            }

            $error_code = 'api_error';
            if(isset($response['code'])) { // API might return a specific error code
                $error_code = $response['code'];
            } elseif (isset($response['data']['code'])) {
                $error_code = $response['data']['code'];
            }

            return new \WP_Error($error_code, $error_message, $response);
        } catch (ApiException $e) {
            error_log('LePostClient API Error during idea generation: ' . $e->getMessage());
            return new \WP_Error('api_error', $e->getMessage());
        } catch (\Exception $e) {
            error_log('LePostClient Unexpected Error during idea generation: ' . $e->getMessage());
            return new \WP_Error('unexpected_error', __('An unexpected error occurred while generating ideas.', 'lepostclient'));
        }
    }
} 