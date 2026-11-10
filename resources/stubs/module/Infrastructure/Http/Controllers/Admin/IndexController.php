<?php

namespace DummyNamespace\Infrastructure\Http\Controllers\Admin;

use App\Modules\Admin\Http\Controllers\Admin;
use DummyNamespace\Infrastructure\Persistence\Eloquent\DummyBasenameModel;

class IndexController extends Admin
{
    /**
     * Slug модуля для выделения страницы в меню.
     *
     * @var string
     */
    public $module = 'DummySlug';

    /**
     * Slug группы для меню.
     *
     * @var string
     */
    public $pageGroup = 'modules';

    /**
     * Отображать кнопку «Страница записи» на странице редактирования.
     *
     * @var bool
     */
    protected $frontLink = false;

    /**
     * @return DummyBasenameModel
     */
    public function getModel()
    {
        return new DummyBasenameModel();
    }

    /**
     * @param  mixed  $request
     * @param  mixed  $id
     * @return array<string, mixed>
     */
    public function getRules($request, $id = false)
    {
        return [];
    }
}
