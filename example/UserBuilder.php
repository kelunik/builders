<?php

namespace Example;

class UserBuilder extends UserBuilderMethods
{
    public function verified(\DateTimeImmutable $verifyDate = null)
    {
        $verifyDate = $verifyDate ?? new \DateTimeImmutable;

        return $this->withStatus('verified')->withVerifyDate($verifyDate);
    }
}