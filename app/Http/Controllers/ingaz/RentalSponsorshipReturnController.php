<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\CompanyProfile;
use App\Models\RentalSponsorshipReturn;
use App\Models\ShelterPlaces;
use App\Models\SponsorshipTransfer;
use App\PermKeys;
use App\Services\ingaz\RentalSponsorshipReturnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RentalSponsorshipReturnController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless(auth('admin')->user()?->can(PermKeys::SPONSOR_TRANSFER_CONTROL), 403);
            return $next($request);
        });
    }

    public function show(int $id)
    {
        $source = SponsorshipTransfer::with(['contract.maid', 'newSponsor'])->findOrFail($id);
        $this->assertCanAccessBranchModel($source);
        $returns = RentalSponsorshipReturn::with(['company', 'shelter', 'currentSponsor'])
            ->where('source_transfer_id', $source->id)->latest('id')->get();
        $company = CompanyProfile::current();
        $shelters = ShelterPlaces::with('translations')->get();
        return view('admin.ingaz.SponsorshipTransfer.return-company', compact('source', 'returns', 'company', 'shelters'));
    }

    public function store(Request $request, int $id, RentalSponsorshipReturnService $service)
    {
        $source = SponsorshipTransfer::findOrFail($id);
        $this->assertCanAccessBranchModel($source);
        $data = $request->validate([
            'request_date' => 'required|date|before_or_equal:today',
            'shelter_id' => 'required|integer|exists:shelter_places,id',
            'notes' => 'nullable|string|max:2000',
        ]);
        $shelter = ShelterPlaces::findOrFail($data['shelter_id']);
        $this->assertCanAccessBranchModel($shelter);
        $service->create($source, $data, auth('admin')->id());
        return redirect()->route('SponsorshipTransfer.returnCompany', $source->id)->with('success', 'تم إنشاء طلب إعادة الكفالة للشركة.');
    }

    public function advance(Request $request, int $id, RentalSponsorshipReturnService $service)
    {
        $operation = RentalSponsorshipReturn::findOrFail($id);
        $this->assertCanAccessBranchModel($operation);
        $data = $request->validate([
            'status' => 'required|in:approved,contract_sent,contract_signed,received,transferred,cancelled',
            'reason' => 'required_if:status,cancelled|nullable|string|max:2000',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:8192',
            'ready_for_rental' => 'required_if:status,transferred|nullable|boolean',
        ]);
        $path = $request->hasFile('attachment')
            ? $request->file('attachment')->store('rental-sponsorship-returns/' . $operation->id, 'local') : null;
        try {
            $service->advance($operation, $data['status'], auth('admin')->id(), $data['reason'] ?? null, $path, (bool) ($data['ready_for_rental'] ?? false));
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            throw $e;
        }
        return redirect()->route('SponsorshipTransfer.returnCompany', $operation->source_transfer_id)->with('success', 'تم تحديث مرحلة الطلب.');
    }

    public function attachment(int $id, int $index)
    {
        $operation = RentalSponsorshipReturn::findOrFail($id);
        $this->assertCanAccessBranchModel($operation);
        $path = $operation->history[$index]['attachment'] ?? null;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->download($path);
    }
}
