<?php

namespace Example;

class User
{
    /** @var int|null */
    private $id;

    /** @var string|null */
    private $name;

    /** @var string|null */
    private $passwordHash;

    /** @var string|null */
    private $status;

    /** @var \DateTimeImmutable|null */
    private $verifyDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getPasswordHash(): ?string
    {
        return $this->passwordHash;
    }

    public function setPasswordHash(?string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    public function getVerifyDate(): ?\DateTimeImmutable
    {
        return $this->verifyDate;
    }

    public function setVerifyDate(?\DateTimeImmutable $verifyDate): void
    {
        $this->verifyDate = $verifyDate;
    }
}