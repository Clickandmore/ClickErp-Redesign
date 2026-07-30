<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\OfferRequest;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class OfferController extends Controller
{
    public $folderPath = 'admin.ingaz.offers';
    public $mainRoute = 'offers';

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $rows = Offer::query()
                ->with(['nationalities', 'maids'])
                ->withCount(['nationalities', 'maids'])
                ->when($request->filled('search_title'), function ($query) use ($request) {
                    $query->where('title', 'like', '%' . $request->search_title . '%');
                })
                ->orderBy('sort_order')
                ->latest();

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('image', function (Offer $row) {
                    return getImgTag($row->image, '90px', '70px');
                })
                ->addColumn('title_block', function (Offer $row) {
                    $dateRange = ($row->starts_at ? $row->starts_at->format('Y-m-d') : '-')
                        . ' / '
                        . ($row->ends_at ? $row->ends_at->format('Y-m-d') : '-');

                    return '<div class="d-flex flex-column gap-1">
                        <strong>' . e($row->title) . '</strong>
                        <span class="text-muted small">' . e(Str::limit(strip_tags($row->description), 120)) . '</span>
                        <span class="text-muted small"><i class="fa fa-calendar me-1"></i>' . e($dateRange) . '</span>
                    </div>';
                })
                ->addColumn('type_block', function (Offer $row) {
                    $parts = ['<span class="badge bg-info text-white">' . e($row->type_label) . '</span>'];

                    if ($row->nationalities_count > 0) {
                        $parts[] = '<span class="badge bg-light text-dark border">جنسيات: ' . $row->nationalities_count . '</span>';
                    }

                    if ($row->maids_count > 0) {
                        $parts[] = '<span class="badge bg-light text-dark border">سير: ' . $row->maids_count . '</span>';
                    }

                    return '<div class="d-flex flex-wrap gap-1">' . implode('', $parts) . '</div>';
                })
                ->addColumn('prices', function (Offer $row) {
                    $before = $row->price_before_discount !== null ? number_format((float) $row->price_before_discount, 2) : '-';
                    $after = $row->price_after_discount !== null ? number_format((float) $row->price_after_discount, 2) : '-';
                    $discount = $row->discount_percentage !== null ? number_format((float) $row->discount_percentage, 2) . '%' : '-';

                    return '<div class="small">
                        <div>قبل الخصم: <strong>' . $before . '</strong></div>
                        <div>بعد الخصم: <strong>' . $after . '</strong></div>
                        <div>نسبة الخصم: <strong>' . $discount . '</strong></div>
                    </div>';
                })
                ->addColumn('status_toggle', function (Offer $row) {
                    $class = $row->is_active ? 'btn-outline-success' : 'btn-outline-secondary';
                    $label = $row->is_active ? 'نشط' : 'غير نشط';

                    return '<button type="button" class="btn btn-sm ' . $class . ' offer-toggle-status" data-route="'
                        . route('offers.toggle-status', $row->id) . '">' . $label . '</button>';
                })
                ->addColumn('actions', function (Offer $row) {
                    $actions = '';

                    if (@AdminGuardHasAnyPerm([\App\PermKeys::OFFERS_VIEW, \App\PermKeys::OFFERS_CONTROL])) {
                        $actions .= showButton(route($this->mainRoute . '.show', $row->id), $row->title);
                    }

                    if (@AdminGuardHasAnyPerm([\App\PermKeys::OFFERS_EDIT, \App\PermKeys::OFFERS_CONTROL])) {
                        $actions .= ' ' . editButton(route($this->mainRoute . '.edit', $row->id), $row->title);
                    }

                    if (@AdminGuardHasAnyPerm([\App\PermKeys::OFFERS_DELETE, \App\PermKeys::OFFERS_CONTROL])) {
                        $actions .= ' ' . deleteButton(route($this->mainRoute . '.destroy', $row->id));
                    }

                    return $actions;
                })
                ->rawColumns(['image', 'title_block', 'type_block', 'prices', 'status_toggle', 'actions'])
                ->make(true);
        }

        return view($this->folderPath . '.index', [
            'createRoute' => route($this->mainRoute . '.create'),
            'dataTableRoute' => route($this->mainRoute . '.index'),
            'bladeTitle' => 'العروض',
            'addButtonText' => 'عرض',
            'modalType' => 'modal-xl',
        ]);
    }

    public function create(Request $request)
    {
        if ($request->ajax()) {
            return jsonSuccess(['html' => view($this->folderPath . '.create', $this->formData())->render()]);
        }
    }

    public function store(OfferRequest $request)
    {
        $newFiles = [];

        try {
            DB::transaction(function () use ($request, &$newFiles) {
                $offer = Offer::create($this->validatedData($request, null, $newFiles));
                $this->syncRelations($offer, $request);
            });
        } catch (\Throwable $exception) {
            $this->deleteFiles($newFiles);
            throw $exception;
        }

        return jsonSuccess();
    }

    public function show(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = Offer::with(['nationalities', 'maids.nationality'])->findOrFail($id);

            return jsonSuccess([
                'html' => view($this->folderPath . '.show', compact('obj'))->render(),
            ]);
        }
    }

    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = Offer::with(['nationalities', 'maids'])->findOrFail($id);

            return jsonSuccess([
                'html' => view($this->folderPath . '.edit', $this->formData($obj))->render(),
            ]);
        }
    }

    public function update(OfferRequest $request, $id)
    {
        $offer = Offer::findOrFail($id);
        $oldFiles = [];
        $newFiles = [];

        try {
            DB::transaction(function () use ($request, $offer, &$oldFiles, &$newFiles) {
                if ($request->hasFile('image') && $offer->image) {
                    $oldFiles[] = $offer->image;
                }

                $offer->update($this->validatedData($request, $offer, $newFiles));
                $this->syncRelations($offer, $request);
            });
        } catch (\Throwable $exception) {
            $this->deleteFiles($newFiles);
            throw $exception;
        }

        $this->deleteFiles($oldFiles);

        return jsonSuccess();
    }

    public function destroy($id)
    {
        $offer = Offer::findOrFail($id);

        $image = $offer->image;
        $offer->delete();
        $this->deleteFiles([$image]);

        return jsonSuccess();
    }

    public function toggleStatus($id)
    {
        $offer = Offer::findOrFail($id);
        $offer->is_active = ! $offer->is_active;
        $offer->save();

        return jsonSuccess(['is_active' => $offer->is_active]);
    }

    private function formData(?Offer $offer = null): array
    {
        return [
            'obj' => $offer,
            'types' => Offer::types(),
            'nationalities' => Nationality::query()->orderByTranslation('sub_title')->get(),
            'maids' => Maid::query()
                ->select(['id', 'name', 'cv_code', 'passport_num', 'nationality_id'])
                ->with('nationality')
                ->orderByDesc('id')
                ->limit(500)
                ->get(),
        ];
    }

    private function validatedData(
        OfferRequest $request,
        ?Offer $offer,
        array &$newFiles
    ): array
    {
        $data = $request->validated();
        unset($data['nationality_ids'], $data['maid_ids']);

        $data['is_active'] = $request->has('is_active')
            ? $request->boolean('is_active')
            : (bool) ($offer->is_active ?? true);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['discount_percentage'] = $this->resolveDiscountPercentage($data);

        if ($request->hasFile('image')) {
            $data['image'] = uploadFile($request->file('image'), 'images/offers/', 1);
            $newFiles[] = $data['image'];
        } else {
            unset($data['image']);
        }

        return $data;
    }

    private function deleteFiles(array $paths): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            deleteFile($path);
        }
    }

    private function resolveDiscountPercentage(array $data): ?float
    {
        if (isset($data['discount_percentage']) && $data['discount_percentage'] !== null && $data['discount_percentage'] !== '') {
            return (float) $data['discount_percentage'];
        }

        $before = (float) ($data['price_before_discount'] ?? 0);
        $after = (float) ($data['price_after_discount'] ?? 0);

        if ($before > 0 && $after >= 0 && $after < $before) {
            return round((($before - $after) / $before) * 100, 2);
        }

        return null;
    }

    private function syncRelations(Offer $offer, OfferRequest $request): void
    {
        $type = $request->input('type');

        $offer->nationalities()->sync(
            in_array($type, [Offer::TYPE_NATIONALITY, Offer::TYPE_NATIONALITY_MAID], true)
                ? $request->input('nationality_ids', [])
                : []
        );

        $offer->maids()->sync(
            in_array($type, [Offer::TYPE_MAID, Offer::TYPE_NATIONALITY_MAID], true)
                ? $request->input('maid_ids', [])
                : []
        );
    }
}
