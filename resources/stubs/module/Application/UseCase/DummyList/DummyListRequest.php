<?php

namespace DummyNamespace\Application\UseCase\DummyList;

class DummyListRequest
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 15,
    ) {
    }
}
