<?php

namespace App\Services;

use RouterOS\Client;
use RouterOS\Query;
use RouterOS\Exceptions\ClientException;

class RouterOSService
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new PPPoE user.
     *
     * @param string $username
     * @param string $password
     * @param string $profile
     * @return bool
     */
    public function createPPPoEUser($username, $password, $profile)
    {
        try {
            $query = new Query('/ppp/secret/add');
            $query->equal('name', $username);
            $query->equal('password', $password);
            $query->equal('service', 'pppoe');
            $query->equal('profile', $profile);
            $query->equal('disabled', 'no');

            $response = $this->client->query($query)->read();

            return !empty($response);
        } catch (ClientException $e) {
            // Log the error
            \Log::error('Failed to create PPPoE user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Disable a PPPoE user.
     *
     * @param string $username
     * @return bool
     */
    public function disablePPPoEUser($username)
    {
        try {
            $query = new Query('/ppp/secret/set');
            $query->equal('numbers', $username);
            $query->equal('disabled', 'yes');

            $response = $this->client->query($query)->read();

            return !empty($response);
        } catch (ClientException $e) {
            // Log the error
            \Log::error('Failed to disable PPPoE user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Enable a PPPoE user.
     *
     * @param string $username
     * @return bool
     */
    public function enablePPPoEUser($username)
    {
        try {
            $query = new Query('/ppp/secret/set');
            $query->equal('numbers', $username);
            $query->equal('disabled', 'no');

            $response = $this->client->query($query)->read();

            return !empty($response);
        } catch (ClientException $e) {
            // Log the error
            \Log::error('Failed to enable PPPoE user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a PPPoE user.
     *
     * @param string $username
     * @return bool
     */
    public function deletePPPoEUser($username)
    {
        try {
            $query = new Query('/ppp/secret/remove');
            $query->equal('numbers', $username);

            $response = $this->client->query($query)->read();

            return !empty($response);
        } catch (ClientException $e) {
            // Log the error
            \Log::error('Failed to delete PPPoE user: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Change password for a PPPoE user.
     *
     * @param string $username
     * @param string $newPassword
     * @return bool
     */
    public function changePPPoEUserPassword($username, $newPassword)
    {
        try {
            $query = new Query('/ppp/secret/set');
            $query->equal('numbers', $username);
            $query->equal('password', $newPassword);

            $response = $this->client->query($query)->read();

            return !empty($response);
        } catch (ClientException $e) {
            // Log the error
            \Log::error('Failed to change PPPoE user password: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get active PPPoE sessions.
     *
     * @return array
     */
    public function getActiveSessions()
    {
        try {
            $query = new Query('/ppp/active/print');
            $response = $this->client->query($query)->read();

            return $response;
        } catch (ClientException $e) {
            // Log the error
            \Log::error('Failed to get active PPPoE sessions: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get PPPoE profiles.
     *
     * @return array
     */
    public function getProfiles()
    {
        try {
            $query = new Query('/ppp/profile/print');
            $response = $this->client->query($query)->read();

            return $response;
        } catch (ClientException $e) {
            // Log the error
            \Log::error('Failed to get PPPoE profiles: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Test connection to the router.
     *
     * @return bool
     */
    public function testConnection()
    {
        try {
            $query = new Query('/system/resource/print');
            $response = $this->client->query($query)->read();

            return !empty($response);
        } catch (ClientException $e) {
            // Log the error
            \Log::error('Failed to test connection: ' . $e->getMessage());
            return false;
        }
    }
}