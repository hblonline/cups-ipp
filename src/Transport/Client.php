<?php

namespace Smalot\Cups\Transport;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Smalot\Cups\CupsException;

class Client implements ClientInterface
{
    public const AUTHTYPE_BASIC = 'basic';
    public const AUTHTYPE_DIGEST = 'digest';

    protected ClientInterface $httpClient;

    protected string $authType = self::AUTHTYPE_BASIC;
    protected ?string $username;
    protected ?string $password;

    public function __construct(string $baseUri, ?string $username = null, ?string $password = null)
    {
        if (empty($baseUri)) {
            throw new CupsException('Remote socket is required');
        }

        if (!preg_match('/^https?/', $baseUri)) {
            throw new CupsException('Only HTTP(S) connections are supported.');
        }

        $this->setAuthentication($username, $password);

        $this->httpClient = new \GuzzleHttp\Client([
            'base_uri' => $baseUri,
        ]);
    }

    public function setAuthentication(?string $username, ?string $password): void
    {
        $this->username = $username;
        $this->password = $password;
    }

    public function setAuthType(string $authType): void
    {
        $this->authType = $authType;
    }

    /**
     * (@inheritdoc}
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->username || $this->password) {
            switch ($this->authType) {
                case self::AUTHTYPE_BASIC:
                    $pass = base64_encode($this->username . ':' . $this->password);
                    $authentication = 'Basic ' . $pass;
                    break;

                case self::AUTHTYPE_DIGEST:
                    throw new CupsException('Auth type not supported');

                default:
                    throw new CupsException('Unknown auth type');
            }

            $request = $request->withHeader('Authorization', $authentication);
        }

        return $this->httpClient->sendRequest($request);
    }
}
