<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\FunctionalTestingFramework\DataGenerator\Persist\Curl;

use Magento\FunctionalTestingFramework\Util\Protocol\CurlInterface;
use Magento\FunctionalTestingFramework\Util\Protocol\CurlTransport;

/**
 * Curl executor for Magento Web Api requests.
 */
class WebapiExecutor extends AbstractExecutor implements CurlInterface
{
    /**
     * Curl transport protocol.
     *
     * @var CurlTransport
     */
    private $transport;

    /**
     * Api headers.
     *
     * @var array
     */
    private $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    /**
     * Response data.
     *
     * @var string
     */
    private $response;

    /**
     *  Admin authentication url.
     */
    const ADMIN_AUTH_URL = '/V1/integration/admin/token';

    /**
     * Store code in api request.
     *
     * @var string
     */
    private $storeCode;

    /**
     * WebapiExecutor Constructor.
     *
     * @param string $storeCode
     */
    public function __construct($storeCode = 'default')
    {
        if (!isset(parent::$baseUrl)) {
            parent::resolveBaseUrl();
        }
        $this->storeCode = $storeCode;
        $this->transport = new CurlTransport();
        $this->authorize();
    }

    /**
     * Returns the authorization token needed for some requests via REST call.
     *
     * @return void
     */
    protected function authorize()
    {
        $authUrl = parent::$baseUrl . 'rest/' . $this->storeCode . self::ADMIN_AUTH_URL;
        $authCreds = [
            'username' => getenv('MAGENTO_ADMIN_USERNAME'),
            'password' => getenv('MAGENTO_ADMIN_PASSWORD')
        ];

        $this->transport->write($authUrl, json_encode($authCreds), CurlInterface::POST, $this->headers);
        $this->headers = array_merge(
            ['Authorization: Bearer ' . str_replace('"', "", $this->read())],
            $this->headers
        );
    }

    /**
     * Send request to the remote server.
     *
     * @param string $url
     * @param array $data
     * @param string $method
     * @param array $headers
     * @return void
     */
    public function write($url, $data = [], $method = CurlInterface::POST, $headers = [])
    {
        $this->transport->write(
            parent::$baseUrl . 'rest/' . $this->storeCode . '/' . trim($url, '/'),
            json_encode($data, JSON_PRETTY_PRINT),
            $method,
            array_unique(array_merge($headers, $this->headers))
        );
    }

    /**
     * Read response from server.
     *
     * @param string $successRegex
     * @param string $returnRegex
     * @return string
     */
    public function read($successRegex = null, $returnRegex = null)
    {
        $this->response = $this->transport->read();
        return $this->response;
    }

    /**
     * Add additional option to cURL.
     *
     * @param  int $option the CURLOPT_* constants
     * @param  int|string|bool|array $value
     * @return void
     */
    public function addOption($option, $value)
    {
        $this->transport->addOption($option, $value);
    }

    /**
     * Close the connection to the server.
     *
     * @return void
     */
    public function close()
    {
        $this->transport->close();
    }
}
