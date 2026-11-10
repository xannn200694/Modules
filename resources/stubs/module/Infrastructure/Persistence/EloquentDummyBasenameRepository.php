<?php

namespace DummyNamespace\Infrastructure\Persistence;

use DummyNamespace\Domain\Contracts\DummyBasenameRepository;
use DummyNamespace\Domain\Model\DummyBasename;
use DummyNamespace\Infrastructure\Persistence\Eloquent\DummyBasenameModel;

class EloquentDummyBasenameRepository implements DummyBasenameRepository
{
    /**
     * @return array{items: list<DummyBasename>, total: int}
     */
    public function list(int $page, int $perPage): array
    {
        $query = DummyBasenameModel::query()->orderBy('name');
        $total = (clone $query)->count();
        $items = [];

        foreach ($query->forPage($page, $perPage)->get() as $row) {
            $items[] = new DummyBasename((string) $row->name, (string) $row->email);
        }

        return [
            'items' => $items,
            'total' => $total,
        ];
    }
}
