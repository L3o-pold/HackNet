<?php
namespace Sumeko\Phalcon\Oauth2\Server\Storage;

use League\OAuth2\Server\AbstractServer;

trait AdapterTrait
{

    /**
     * Server
     * @var \League\OAuth2\Server\AbstractServer $server
     */
    protected $server;

    /**
     * Set the server
     * @param \League\OAuth2\Server\AbstractServer $server
     * @return $this
     */
    public function setServer(AbstractServer $server)
    {
        $this->server = $server;

        return $this;
    }

    /**
     * Return the server
     * @return \League\OAuth2\Server\AbstractServer
     */
    protected function getServer()
    {
        return $this->server;
    }
} 