<?php

namespace DummyNamespace\Domain\Model;

class DummyBasename
{
    public function __construct(
        public string $name,
        public string $email,
    ) {
    }
}
