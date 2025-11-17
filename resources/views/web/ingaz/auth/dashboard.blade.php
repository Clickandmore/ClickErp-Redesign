@extends('web.layout.app')

@section('content')
    <main>
        <!-- page header -->
        <section class="page_header">
            <h1>{{ helperTrans('web.dashboard') }}</h1>
            <h6><a href="{{ route('web.home') }}">{{ helperTrans('web.Home') }}</a> / {{ helperTrans('web.dashboard') }}</h6>
        </section>

        <!-- dashboard section -->
        <section class="setting_setion">
            <div class="container">
                <div class="row">
                    @include('web.layout.inc.profile_aside')

                    <div class="col-lg-8 col-12">
                        <!-- Welcome Section -->
                        <div class="dashboard_welcome mb-4">
                            <div class="welcome_card">
                                <div class="welcome_content">
                                    <h3>{{ helperTrans('web.welcome_back') }}, {{ getGuardedUser('name') }}!</h3>
                                    <p>{{ helperTrans('web.dashboard_welcome_message') }}</p>
                                    <div class="welcome_stats">
                                        <span class="stat_item">
                                            <i class="fa-regular fa-calendar-check"></i>
                                            {{ now()->format('Y-m-d') }}
                                        </span>
                                        <span class="stat_item">
                                            <i class="fa-regular fa-clock"></i>
                                            {{ now()->format('H:i') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="welcome_icon">
                                    <i class="fa-light fa-user-circle"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row mb-4">
                            <!-- Orders Statistics -->
                            <div class="col-md-2 mb-3">
                                <div class="dashboard_stat_card">
                                    <div class="stat_header">
                                        <div class="stat_icon orders_icon">
                                            <i class="fa-regular fa-list-check"></i>
                                        </div>
                                        <div class="stat_title">
                                            <h4>{{ $stats['orders']['total'] }}</h4>
                                            <p>{{ helperTrans('web.total_orders') }}</p>
                                        </div>
                                    </div>
                                    <div class="stat_footer">
                                        <div class="stat_item">
                                            <span class="stat_label">{{ helperTrans('web.active') }}</span>
                                            <span class="stat_value active_value">{{ $stats['orders']['active'] }}</span>
                                        </div>
                                        <div class="stat_item">
                                            <span class="stat_label">{{ helperTrans('web.completed') }}</span>
                                            <span
                                                class="stat_value completed_value">{{ $stats['orders']['completed'] }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('web.orders.index') }}" class="stat_link">
                                        <i class="fa-light fa-arrow-left"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Contracts Statistics -->
                            <div class="col-md-2 mb-3">
                                <div class="dashboard_stat_card">
                                    <div class="stat_header">
                                        <div class="stat_icon contracts_icon">
                                            <i class="fa-regular fa-file-contract"></i>
                                        </div>
                                        <div class="stat_title">
                                            <h4>{{ $stats['contracts']['total'] }}</h4>
                                            <p>{{ helperTrans('web.total_contracts') }}</p>
                                        </div>
                                    </div>
                                    <div class="stat_footer">
                                        <div class="stat_item">
                                            <span class="stat_label">{{ helperTrans('web.active') }}</span>
                                            <span
                                                class="stat_value active_value">{{ $stats['contracts']['active'] }}</span>
                                        </div>
                                        <div class="stat_item">
                                            <span class="stat_label">{{ helperTrans('web.completed') }}</span>
                                            <span
                                                class="stat_value completed_value">{{ $stats['contracts']['completed'] }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('web.contracts.index') }}" class="stat_link">
                                        <i class="fa-light fa-arrow-left"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Complaints Statistics -->
                            <div class="col-md-2 mb-3">
                                <div class="dashboard_stat_card">
                                    <div class="stat_header">
                                        <div class="stat_icon complaints_icon">
                                            <i class="fa-light fa-triangle-exclamation"></i>
                                        </div>
                                        <div class="stat_title">
                                            <h4>{{ $stats['complaints']['total'] }}</h4>
                                            <p>{{ helperTrans('web.total_complaints') }}</p>
                                        </div>
                                    </div>
                                    <div class="stat_footer">
                                        <div class="stat_item">
                                            <span class="stat_label">{{ helperTrans('web.pending') }}</span>
                                            <span
                                                class="stat_value pending_value">{{ $stats['complaints']['pending'] }}</span>
                                        </div>
                                        <div class="stat_item">
                                            <span class="stat_label">{{ helperTrans('web.resolved') }}</span>
                                            <span
                                                class="stat_value resolved_value">{{ $stats['complaints']['resolved'] }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('web.complaints.index') }}" class="stat_link">
                                        <i class="fa-light fa-arrow-left"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Sponsorship Transfers Statistics -->
                            <div class="col-md-2 mb-3">
                                <div class="dashboard_stat_card">
                                    <div class="stat_header">
                                        <div class="stat_icon transfers_icon">
                                            <i class="fa-regular fa-exchange-alt"></i>
                                        </div>
                                        <div class="stat_title">
                                            <h4>{{ $stats['sponsorship_transfers']['total'] ?? 0 }}</h4>
                                            <p>طلبات نقل الكفالة</p>
                                        </div>
                                    </div>
                                    <div class="stat_footer">
                                        <div class="stat_item">
                                            <span class="stat_label">في الانتظار</span>
                                            <span
                                                class="stat_value pending_value">{{ $stats['sponsorship_transfers']['pending'] ?? 0 }}</span>
                                        </div>
                                        <div class="stat_item">
                                            <span class="stat_label">تم النقل</span>
                                            <span
                                                class="stat_value completed_value">{{ $stats['sponsorship_transfers']['transferred'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                    <a href="#" class="stat_link" onclick="scrollToTransfers()">
                                        <i class="fa-light fa-arrow-left"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="quick_actions mb-4">
                            <div class="section_title">
                                <h5>{{ helperTrans('web.quick_actions') }}</h5>
                                <p>{{ helperTrans('web.quick_actions_description') }}</p>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('web.maids.index') }}" class="action_card">
                                        <div class="action_icon">
                                            <i class="fa-light fa-users"></i>
                                        </div>
                                        <div class="action_content">
                                            <h6>{{ helperTrans('web.browse_maids') }}</h6>
                                            <p>{{ helperTrans('web.find_perfect_maid') }}</p>
                                        </div>
                                        <i class="fa-light fa-arrow-left action_arrow"></i>
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('web.complaints.index') }}" class="action_card">
                                        <div class="action_icon">
                                            <i class="fa-light fa-message-plus"></i>
                                        </div>
                                        <div class="action_content">
                                            <h6>{{ helperTrans('web.submit_complaint') }}</h6>
                                            <p>{{ helperTrans('web.get_help_support') }}</p>
                                        </div>
                                        <i class="fa-light fa-arrow-left action_arrow"></i>
                                    </a>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <a href="{{ route('web.contract-return-requests.index') }}" class="action_card">
                                        <div class="action_icon">
                                            <i class="fa-light fa-undo"></i>
                                        </div>
                                        <div class="action_content">
                                            <h6>طلبات الاسترجاع</h6>
                                            <p>عرض طلبات استرجاع العقود</p>
                                        </div>
                                        <i class="fa-light fa-arrow-left action_arrow"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activities -->
                        <div class="recent_activities">
                            <div class="row">
                                <!-- Recent Orders -->
                                @if ($recentOrders->count() > 0)
                                    <div class="col-12 mb-4">
                                        <div class="activity_section">
                                            <div class="section_header">
                                                <div class="header_content">
                                                    <h5>{{ helperTrans('web.recent_orders') }}</h5>
                                                    <span class="badge bg-primary">{{ $recentOrders->count() }}</span>
                                                </div>
                                                <a href="{{ route('web.orders.index') }}" class="view_all_btn">
                                                    {{ helperTrans('web.view_all') }}
                                                    <i class="fa-light fa-arrow-left"></i>
                                                </a>
                                            </div>
                                            <div class="activity_list">
                                                @foreach ($recentOrders as $order)
                                                    <div class="activity_item">
                                                        <div class="activity_icon">
                                                            <i class="fa-regular fa-list-check"></i>
                                                        </div>
                                                        <div class="activity_content">
                                                            <h6>{{ helperTrans('web.order') }} #{{ $order->id }}</h6>
                                                            <p>{{ $order->maid->name ?? helperTrans('web.maid_not_assigned') }}
                                                                - {{ $order->jobType->title ?? '' }}</p>
                                                            <small>{{ $order->created_at->diffForHumans() }}</small>
                                                        </div>
                                                        <div class="activity_status">
                                                            <span class="status_badge status_{{ $order->status }}">
                                                                {{ $order->status }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Recent Contracts -->
                                @if ($recentContracts->count() > 0)
                                    <div class="col-12 mb-4">
                                        <div class="activity_section">
                                            <div class="section_header">
                                                <div class="header_content">
                                                    <h5>{{ helperTrans('web.recent_contracts') }}</h5>
                                                    <span class="badge bg-success">{{ $recentContracts->count() }}</span>
                                                </div>
                                                <a href="{{ route('web.contracts.index') }}" class="view_all_btn">
                                                    {{ helperTrans('web.view_all') }}
                                                    <i class="fa-light fa-arrow-left"></i>
                                                </a>
                                            </div>
                                            <div class="activity_list">
                                                @foreach ($recentContracts as $contract)
                                                    <div class="activity_item">
                                                        <div class="activity_icon">
                                                            <i class="fa-regular fa-file-contract"></i>
                                                        </div>
                                                        <div class="activity_content">
                                                            <h6>{{ helperTrans('web.contract') }} #{{ $contract->id }}
                                                            </h6>
                                                            <p>{{ $contract->maid->name ?? helperTrans('web.maid_not_assigned') }}
                                                            </p>
                                                            <small>{{ $contract->created_at->diffForHumans() }}</small>
                                                        </div>
                                                        <div class="activity_status">
                                                            <span class="status_badge status_{{ $contract->status }}">
                                                                {{ $contract->status }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Recent Complaints -->
                                @if ($recentComplaints->count() > 0)
                                    <div class="col-12 mb-4">
                                        <div class="activity_section">
                                            <div class="section_header">
                                                <div class="header_content">
                                                    <h5>{{ helperTrans('web.recent_complaints') }}</h5>
                                                    <span class="badge bg-warning">{{ $recentComplaints->count() }}</span>
                                                </div>
                                                <a href="{{ route('web.complaints.index') }}" class="view_all_btn">
                                                    {{ helperTrans('web.view_all') }}
                                                    <i class="fa-light fa-arrow-left"></i>
                                                </a>
                                            </div>
                                            <div class="activity_list">
                                                @foreach ($recentComplaints as $complaint)
                                                    <div class="activity_item">
                                                        <div class="activity_icon">
                                                            <i class="fa-light fa-triangle-exclamation"></i>
                                                        </div>
                                                        <div class="activity_content">
                                                            <h6>{{ $complaint->complaintType->title ?? helperTrans('web.complaint') }}
                                                            </h6>
                                                            <p>{{ Str::limit($complaint->message, 50) }}</p>
                                                            <small>{{ $complaint->created_at->diffForHumans() }}</small>
                                                        </div>
                                                        <div class="activity_status">
                                                            <span class="status_badge status_{{ $complaint->status }}">
                                                                {{ $complaint->status }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- Sponsorship Transfers -->
                                @if ($sponsorshipTransfers->count() > 0)
                                    <div class="col-12 mb-4">
                                        <div class="activity_section">
                                            <div class="section_header">
                                                <div class="header_content">
                                                    <h5>طلبات نقل الكفالة</h5>
                                                    <span
                                                        class="badge bg-info">{{ $sponsorshipTransfers->count() }}</span>
                                                </div>
                                            </div>
                                            <div class="activity_list">
                                                @foreach ($sponsorshipTransfers as $transfer)
                                                    <div class="activity_item">
                                                        <div class="activity_icon">
                                                            <i class="fa-regular fa-exchange-alt"></i>
                                                        </div>
                                                        <div class="activity_content">
                                                            <h6>طلب نقل كفالة #{{ $transfer->id }}</h6>
                                                            <p>
                                                                @if ($transfer->old_sponsor_id == auth()->id())
                                                                    كفيل سابق →
                                                                    {{ $transfer->newSponsor ? $transfer->newSponsor->name : 'غير محدد' }}
                                                                @else
                                                                    {{ $transfer->oldSponsor ? $transfer->oldSponsor->name : 'غير محدد' }}
                                                                    → كفيل جديد
                                                                @endif
                                                            </p>
                                                            <small>{{ $transfer->created_at->diffForHumans() }}</small>
                                                            @if ($transfer->trial_days)
                                                                <br><small class="text-warning">
                                                                    <i class="fa-regular fa-clock me-1"></i>
                                                                    فترة التجربة: {{ $transfer->trial_days }} أيام
                                                                </small>
                                                            @endif
                                                        </div>
                                                        <div class="activity_status">
                                                            <span class="status_badge status_{{ $transfer->status }}">
                                                                {{ $transfer->status_text }}
                                                            </span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                @if (
                                    $recentOrders->count() == 0 &&
                                        $recentContracts->count() == 0 &&
                                        $recentComplaints->count() == 0 &&
                                        $sponsorshipTransfers->count() == 0)
                                    <div class="col-12">
                                        <div class="no_activities">
                                            <div class="no_activities_icon">
                                                <i class="fa-light fa-inbox"></i>
                                            </div>
                                            <h5>{{ helperTrans('web.no_recent_activities') }}</h5>
                                            <p>{{ helperTrans('web.start_by_browsing_maids') }}</p>
                                            <a href="{{ route('web.maids.index') }}" class="btn btn-primary">
                                                <i class="fa-light fa-users me-2"></i>
                                                {{ helperTrans('web.browse_maids') }}
                                            </a>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Edit Profile Modal -->
        <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editProfileModalLabel">
                            <i class="fa-light fa-user-pen me-2"></i>
                            {{ helperTrans('web.edit_profile') }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('web.update-profile') }}" id="update-profile" method="post"
                            class="auth_form">
                            @csrf
                            <div class="input_filed">
                                <label for="name">
                                    <i class="fa-light fa-user"></i>
                                    {{ helperTrans('web.Full_name') }}
                                </label>
                                <input type="text" id="name" name="name"
                                    value="{{ getGuardedUser('name') }}" placeholder="{{ helperTrans('web.Name') }}"
                                    required>
                            </div>
                            <div class="input_filed">
                                <label for="email">
                                    <i class="fa-light fa-envelope"></i>
                                    {{ helperTrans('web.Email') }}
                                </label>
                                <input type="email" id="email" name="email"
                                    value="{{ getGuardedUser('email') }}" placeholder="{{ helperTrans('web.Email') }}"
                                    required>
                            </div>
                            <div class="form_actions">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    {{ helperTrans('web.cancel') }}
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa-light fa-save me-2"></i>
                                    {{ helperTrans('web.update_profile') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
@endsection

@section('scripts')
    <script>
        function showEditProfileForm() {
            $('#editProfileModal').modal('show');
        }

        // Handle profile update form submission
        $(document).ready(function() {
            $('#update-profile').on('submit', function(e) {
                e.preventDefault();

                // Show loading state
                const submitBtn = $(this).find('button[type="submit"]');
                const originalText = submitBtn.html();
                submitBtn.html(
                    '<i class="fa-light fa-spinner fa-spin me-2"></i>{{ helperTrans('web.updating') }}...'
                );
                submitBtn.prop('disabled', true);

                $.ajax({
                    url: $(this).attr('action'),
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.success) {
                            // Show success message
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: '{{ helperTrans('web.success') }}',
                                    text: response.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => {
                                    $('#editProfileModal').modal('hide');
                                    location.reload(); // Reload to show updated data
                                });
                            } else {
                                alert(response.message);
                                $('#editProfileModal').modal('hide');
                                location.reload();
                            }
                        } else {
                            alert(response.message ||
                                '{{ helperTrans('web.error_updating_profile') }}');
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = '{{ helperTrans('web.error_updating_profile') }}';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: '{{ helperTrans('web.error') }}',
                                text: errorMessage
                            });
                        } else {
                            alert(errorMessage);
                        }
                    },
                    complete: function() {
                        // Reset button state
                        submitBtn.html(originalText);
                        submitBtn.prop('disabled', false);
                    }
                });
            });
        });
    </script>
@endsection

@section('styles')
    <style>

    </style>
@endsection
