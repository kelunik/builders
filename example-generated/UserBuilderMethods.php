<?php

namespace Example;

use Example;

class UserBuilderMethods implements \Kelunik\Builders\Builder
{
    private $entity;

    public function __construct()
    {
        $this->entity = new Example\User;
    }

    final public function withId(?string $value)
    {
        $this->entity->setId($value);

        return $this;
    }

    final public function withName(?string $value)
    {
        $this->entity->setName($value);

        return $this;
    }

    final public function withPasswordHash(?string $value)
    {
        $this->entity->setPasswordHash($value);

        return $this;
    }

    final public function withStatus(?string $value)
    {
        $this->entity->setStatus($value);

        return $this;
    }

    final public function withVerifyDate(?\DateTimeImmutable $value)
    {
        $this->entity->setVerifyDate($value);

        return $this;
    }

    final public function build(): Example\User
    {
        return $this->entity;
    }
}
