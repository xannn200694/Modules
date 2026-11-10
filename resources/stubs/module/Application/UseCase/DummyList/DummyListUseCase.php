<?php

namespace DummyNamespace\Application\UseCase\DummyList;

use DummyNamespace\Domain\Contracts\DummyBasenameRepository;

class DummyListUseCase
{
    public function __construct(private DummyBasenameRepository $repository)
    {
    }

    public function handle(DummyListRequest $request): DummyListResult
    {
        $page = max(1, $request->page);
        $perPage = max(1, $request->perPage);
        $listed = $this->repository->list($page, $perPage);
        $items = [];

        foreach ($listed['items'] as $entity) {
            $items[] = [
                'name' => $entity->name,
                'email' => $entity->email,
            ];
        }

        return new DummyListResult($items, $listed['total']);
    }
}
