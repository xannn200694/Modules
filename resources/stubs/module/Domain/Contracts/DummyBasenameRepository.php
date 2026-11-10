<?php

namespace DummyNamespace\Domain\Contracts;

use DummyNamespace\Domain\Model\DummyBasename;

interface DummyBasenameRepository
{
    /**
     * @return array{items: list<DummyBasename>, total: int}
     */
    public function list(int $page, int $perPage): array;
}
