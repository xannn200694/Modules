<?php

namespace DummyNamespace\Application\UseCase\DummyList;

class DummyListResult
{
    /**
     * @param  list<array{name: string, email: string}>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}
