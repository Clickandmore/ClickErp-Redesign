<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\FastNewAction as objAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\FastNewRequest as objRequest;
use App\Models\ingaz\FastNew as objModel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class FastNewController extends Controller
{

    public $folderPath = "admin.ingaz.fast_news";
    public $mainRoute = "fast-news";

    function __construct()
    {
        // $this->middleware('permission:عنا-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:عنا-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:عنا-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:عنا-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $rows = objModel::query()->latest();
            return DataTables::of($rows)
                ->addIndexColumn()
                ->addColumn('visible_flag', fn ($row) => (int) $row->is_visible)
                ->editColumn('is_visible', fn ($row) => $this->visibilityToggle($row))
                ->editColumn('title', function ($row) {
                    $limited = Str::limit($row?->title, 50, '...');
                    return "<span title='" . e($row->title) . "'>" . e($limited) . "</span>";
                })
                ->addColumn('image', function ($row) {
                    $url = showFile($row->image);
                    return $url
                        ? '<img src="' . e($url) . '" alt="" style="height:40px;max-width:80px;object-fit:contain;border-radius:6px">'
                        : '-';
                })
                ->addColumn('actions', function ($row) {
                    $editButton = '';
                    $deleteButton = '';
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->title);

                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = helperTrans('api.Fast news');
        $data["addButtonText"] = helperTrans('api.Fast news');
        $data["visibleCount"] = objModel::query()->where('is_visible', true)->count();
        $data["totalCount"] = objModel::query()->count();
        return view($this->folderPath . '.index', compact('data'));
    }


    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }


    public function store(objRequest $request, objAction $action)
    {
        $data = $request->validated();
        $data = $action->store($data);
        return jsonSuccess($data);

    }


    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = objModel::findorfail($id);
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(objRequest $request, objAction $action, $id)
    {
        $updatedData = $request->validated();
        $data = $action->updateFastNew($id, $updatedData);
        return jsonSuccess($data);
    }

    public function destroy($id, objAction $action)
    {
        $action->deleteWithFile($id, 'image');
        return jsonSuccess();
    }

    // يقلب ظهور الخبر من الجدول مباشرة (بدون إعادة تحميل الصفحة)
    public function toggleVisibility($id)
    {
        $news = objModel::findOrFail($id);
        $news->is_visible = ! $news->is_visible;
        $news->save();

        return jsonSuccess([
            'is_visible' => $news->is_visible,
            'label' => __($news->is_visible ? 'web.fast_news_visible' : 'web.fast_news_hidden'),
        ]);
    }

    private function visibilityToggle(objModel $row): string
    {
        $visible = (bool) $row->is_visible;

        return '<button type="button" class="fast-news-toggle ' . ($visible ? 'is-visible' : 'is-hidden') . '"'
            . ' data-route="' . e(route($this->mainRoute . '.toggle-visibility', $row->id)) . '"'
            . ' aria-pressed="' . ($visible ? 'true' : 'false') . '"'
            . ' title="' . e(__('web.fast_news_toggle_hint')) . '">'
            . '<span class="fast-news-toggle__track"><span class="fast-news-toggle__thumb"></span></span>'
            . '<span class="fast-news-toggle__label">' . e(__($visible ? 'web.fast_news_visible' : 'web.fast_news_hidden')) . '</span>'
            . '</button>';
    }
}
