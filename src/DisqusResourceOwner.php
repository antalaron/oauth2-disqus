<?php

/*
 * This file is part of Disqus Oaut2 client
 *
 * (c) EDIMA.email Kft. <admin24@edima.hu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Antalaron\DisqusOAuth2;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;

/**
 * @author Antal Áron <antalaron@antalaron.hu>
 */
class DisqusResourceOwner implements ResourceOwnerInterface
{
    use ArrayAccessorTrait;

    protected array $response;

    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    public function getId(): ?int
    {
        return $this->getValueByKey($this->response, 'response.id');
    }

    public function getName(): ?string
    {
        return $this->getValueByKey($this->response, 'response.name');
    }

    public function getUsername(): ?string
    {
        return $this->getValueByKey($this->response, 'response.username');
    }

    public function toArray(): array
    {
        return $this->response;
    }
}
