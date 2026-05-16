<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Religion;
use App\Models\RecruitmentOffices;
use App\Models\office\Office;
use App\Enums\ingaz\ContractStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $officeIds = $this->agentOfficeIds();
        $offices = Office::query()
            ->with('nationality')
            ->whereIn('id', $officeIds->all())
            ->orderBy('id')
            ->get();

        $maidsQuery = $this->maidsQuery($officeIds);
        $contractsQuery = $this->contractsQuery($officeIds);

        $stats = [
            'maids_count' => (clone $maidsQuery)->count(),
            'available_maids_count' => (clone $maidsQuery)
                ->where(function (Builder $query) {
                    $query->where('available', true)
                        ->orWhere('status', 'available');
                })
                ->count(),
            'contracts_count' => (clone $contractsQuery)->count(),
            'active_contracts_count' => (clone $contractsQuery)
                ->whereNotIn('status', ['completed', 'closed', 'back'])
                ->count(),
        ];

        $latestMaids = (clone $maidsQuery)
            ->with(['nationality', 'jobTypes'])
            ->latest('id')
            ->take(6)
            ->get();

        $latestContracts = (clone $contractsQuery)
            ->with(['user', 'maid.nationality', 'maid.office', 'info'])
            ->latest('id')
            ->take(6)
            ->get();

        return view('agent.dashboard', compact('offices', 'stats', 'latestMaids', 'latestContracts'));
    }

    public function maids(Request $request)
    {
        $query = $this->maidsQuery()
            ->with(['nationality', 'jobTypes'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $innerQuery) use ($search) {
                $innerQuery->where('name', 'like', '%' . $search . '%')
                    ->orWhere('passport_num', 'like', '%' . $search . '%')
                    ->orWhere('id_number', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $maids = $query->paginate(15)->appends($request->query());

        return view('agent.maids.index', compact('maids'));
    }

    public function createMaid()
    {
        $nationalities = Nationality::all();
        $religions = Religion::all();
        $offices = $this->agentOffices();
        $jobTypes = JobType::all();

        return view('agent.maids.create', compact('nationalities', 'religions', 'offices', 'jobTypes'));
    }

    public function extractCv(Request $request)
    {
        $text = (string) $request->input('text');
        
        // Basic extraction logic using regex
        $data = [
            'name' => null,
            'passport_num' => null,
            'nationality_id' => null,
            'age' => null,
            'religion_id' => null,
            'experience_years' => null,
        ];

        // Name extraction (very basic mock)
        if (preg_match('/Name[:\s]+([a-zA-Z\s]+)/i', $text, $matches)) {
            $data['name'] = trim($matches[1]);
        }

        // Passport extraction
        if (preg_match('/Passport[:\s#]+([A-Z0-9]+)/i', $text, $matches)) {
            $data['passport_num'] = trim($matches[1]);
        }

        // Age extraction
        if (preg_match('/Age[:\s]+(\d+)/i', $text, $matches)) {
            $data['age'] = (int) $matches[1];
        }

        // Nationality extraction (matching by title)
        $nationalities = Nationality::all();
        foreach ($nationalities as $nationality) {
            if (stripos($text, $nationality->title) !== false || stripos($text, $nationality->sub_title) !== false) {
                $data['nationality_id'] = $nationality->id;
                break;
            }
        }

        return response()->json($data);
    }

    public function storeMaid(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'passport_num' => 'required|string|max:255',
            'nationality_id' => 'required|exists:nationalities,id',
            'office_id' => 'required|exists:recruitment_offices,id',
        ]);

        $officeIds = $this->agentOfficeIds();
        if (!$officeIds->contains($request->office_id)) {
            abort(403, 'Unauthorized office');
        }

        $maid = Maid::create([
            'name' => $request->name,
            'passport_num' => $request->passport_num,
            'nationality_id' => $request->nationality_id,
            'office_id' => $request->office_id,
            'religion_id' => $request->religion_id,
            'age' => $request->age,
            'experience_years' => $request->experience_years,
            'status' => 'available',
            'available' => 1,
            'is_show' => 0, // Admin must review
            'added_by' => auth()->id(),
        ]);

        if ($request->has('job_type_ids')) {
            $maid->jobTypes()->sync($request->job_type_ids);
        }

        return redirect()->route('agent.maids.index')->with('success', 'CV added successfully and pending review.');
    }

    public function contracts(Request $request)
    {
        $baseQuery = $this->contractsQuery();
        $query = (clone $baseQuery)
            ->with(['user', 'maid.nationality', 'maid.office', 'info'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $innerQuery) use ($search) {
                $innerQuery->where('id', $search)
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%')
                            ->orWhere('national_num', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('maid', function (Builder $maidQuery) use ($search) {
                        $maidQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('passport_num', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('info', function (Builder $infoQuery) use ($search) {
                        $infoQuery->where('visa_number', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $statuses = (clone $baseQuery)
            ->select('status')
            ->whereNotNull('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status');

        $contracts = $query->paginate(15)->appends($request->query());

        return view('agent.contracts.index', compact('contracts', 'statuses'));
    }

    public function dispatches(Request $request)
    {
        $officeIds = $this->agentOfficeIds();
        $currentStatuses = $this->currentRecruitmentStatuses();

        $baseQuery = Contract::query()
            ->where(function (Builder $query) use ($officeIds) {
                $query->whereHas('maid', function (Builder $maidQuery) use ($officeIds) {
                    $maidQuery->whereIn('office_id', $officeIds->all());
                });

                if (Schema::hasTable('contract_infos') && Schema::hasColumn('contract_infos', 'manual_maid_office_id')) {
                    $query->orWhereHas('info', function (Builder $infoQuery) use ($officeIds) {
                        $infoQuery->whereIn('manual_maid_office_id', $officeIds->all());
                    });
                }
            })
            ->whereIn('type', ['recruitment', 'maid'])
            ->where(function ($inner) use ($currentStatuses) {
                $inner->whereIn('status', $currentStatuses)
                    ->orWhereHas('externalOfficeDispatch');
            });

        $query = (clone $baseQuery)
            ->with([
                'user',
                'maid.religion',
                'maid.office',
                'maid.recruitmentOffice',
                'info',
                'info.manualMaidOffice',
                'externalOfficeDispatch',
            ])
            ->latest('id');

        $dispatchFilter = $request->get('dispatch_filter', 'sent');

        if ($dispatchFilter === 'not_sent') {
            $query->whereIn('status', $currentStatuses)
                ->whereDoesntHave('externalOfficeDispatch');
        } elseif ($dispatchFilter === 'sent') {
            $query->whereHas('externalOfficeDispatch');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $innerQuery) use ($search) {
                $innerQuery->where('id', 'like', '%' . $search . '%')
                    ->orWhereHas('info', function ($infoQuery) use ($search) {
                        $infoQuery->where('visa_number', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('national_num', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('maid', function ($maidQuery) use ($search) {
                        $maidQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('passport_num', 'like', '%' . $search . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $contracts = $query->paginate(15)->appends($request->query());

        $stats = [
            'all' => (clone $baseQuery)->count(),
            'sent' => (clone $baseQuery)->whereHas('externalOfficeDispatch')->count(),
            'not_sent' => (clone $baseQuery)
                ->whereIn('status', $currentStatuses)
                ->whereDoesntHave('externalOfficeDispatch')
                ->count(),
        ];

        return view('agent.dispatches.index', compact('contracts', 'stats', 'dispatchFilter'));
    }

    private function currentRecruitmentStatuses(): array
    {
        return [
            ContractStatusEnum::PendingMaidSelection->value,
            ContractStatusEnum::New->value,
            ContractStatusEnum::Msaned->value,
            ContractStatusEnum::Tafwed->value,
            ContractStatusEnum::Taffez->value,
            ContractStatusEnum::Tazker->value,
        ];
    }

    public function invoices(Request $request)
    {
        $officeIds = $this->agentOfficeIds();

        $query = Contract::query()
            ->where(function (Builder $query) use ($officeIds) {
                $query->whereHas('maid', function (Builder $maidQuery) use ($officeIds) {
                    $maidQuery->whereIn('office_id', $officeIds->all());
                });

                if (Schema::hasTable('contract_infos') && Schema::hasColumn('contract_infos', 'manual_maid_office_id')) {
                    $query->orWhereHas('info', function (Builder $infoQuery) use ($officeIds) {
                        $infoQuery->whereIn('manual_maid_office_id', $officeIds->all());
                    });
                }
            })
            ->whereHas('invoice')
            ->with(['user', 'maid.nationality', 'invoice'])
            ->latest('id');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $innerQuery) use ($search) {
                $innerQuery->where('id', $search)
                    ->orWhereHas('user', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%' . $search . '%')
                            ->orWhere('phone', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('invoice', function (Builder $invQuery) use ($search) {
                        $invQuery->where('id', $search);
                    });
            });
        }

        if ($request->filled('is_paid')) {
            $isPaid = $request->input('is_paid') === '1';
            $query->whereHas('invoice', function (Builder $invQuery) use ($isPaid) {
                $invQuery->where('is_paid', $isPaid);
            });
        }

        $contracts = $query->paginate(15)->appends($request->query());

        return view('agent.invoices.index', compact('contracts'));
    }

    public function showContract(Contract $contract)
    {
        $contract = $this->contractsQuery()
            ->with([
                'user',
                'maid.nationality',
                'maid.religion',
                'maid.jobTypes',
                'maid.office',
                'info.manualMaidOffice',
                'externalOfficeDispatch',
                'invoice',
            ])
            ->whereKey($contract->id)
            ->firstOrFail();

        return view('agent.contracts.show', compact('contract'));
    }

    private function agentOffices(): Collection
    {
        $officeIds = $this->agentOfficeIds();
        return RecruitmentOffices::whereIn('id', $officeIds->all())->get();
    }

    private function agentOfficeIds(): Collection
    {
        $impersonatedOfficeId = session('_agent_impersonate_office_id');

        if ($impersonatedOfficeId && Auth::guard('admin')->check()) {
            return collect([(int) $impersonatedOfficeId]);
        }

        return Auth::guard('agent')
            ->user()
            ->agentOfficeLinks()
            ->pluck('office_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function maidsQuery(?Collection $officeIds = null): Builder
    {
        $officeIds = $officeIds ?: $this->agentOfficeIds();

        return Maid::query()->whereIn('office_id', $officeIds->all());
    }

    private function contractsQuery(?Collection $officeIds = null): Builder
    {
        $officeIds = $officeIds ?: $this->agentOfficeIds();

        return Contract::query()
            ->where(function (Builder $query) use ($officeIds) {
                $query->whereHas('maid', function (Builder $maidQuery) use ($officeIds) {
                    $maidQuery->whereIn('office_id', $officeIds->all());
                });

                if (Schema::hasTable('contract_infos') && Schema::hasColumn('contract_infos', 'manual_maid_office_id')) {
                    $query->orWhereHas('info', function (Builder $infoQuery) use ($officeIds) {
                        $infoQuery->whereIn('manual_maid_office_id', $officeIds->all());
                    });
                }
            });
    }
}
