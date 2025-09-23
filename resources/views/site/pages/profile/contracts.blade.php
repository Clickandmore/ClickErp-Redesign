@extends('site.layouts.index')
@section('title')
@endsection

@section('style')
    <style>
        main .page_header {
            width: 100%;
            height: 40vh;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            flex-direction: column;
            background-repeat: no-repeat;
        }

        main .page_header h1 {
            color: #191936;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        main .page_header h6 {
            font-size: 14px;
            color: #191936;
        }

        main .page_header h6 a {
            color: #1d70b7;
        }

        main .setting_setion {
            padding: 60px 12px;
        }

        main .setting_setion .profile_side_bar {
            background: #fff;
            box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
            padding: 24px;
            border-radius: 8px;
            position: sticky;
            top: 100px;
        }

        main .setting_setion .profile_side_bar h4 {
            font-size: 22px;
            margin-bottom: 4px;
            font-weight: 700;
        }


        main .setting_setion .profile_side_bar .links {
            margin-top: 24px;
        }

        main .setting_setion .profile_side_bar .links li {
            padding: 8px 16px;
            margin-bottom: 8px;
            cursor: pointer;
            border-radius: 4px;
            transition: 0.4s ease-in-out;
        }

        main .setting_setion .profile_side_bar .links li a {
            color: #191936;
            transition: 0.4s ease-in-out;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        main .setting_setion .profile_side_bar .links li:last-child a {
            color: red;
        }

        main .setting_setion .profile_side_bar .links li:hover,
        main .setting_setion .profile_side_bar .links li.active {
            background: #1d70b7;
        }

        main .setting_setion .profile_side_bar .links li:hover a,
        main .setting_setion .profile_side_bar .links li.active a {
            color: #fff;
        }

        main .setting_setion .notification {
            width: 100%;
            padding: 12px 16px;
            background: #fff;
            box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
            margin-bottom: 20px;
            border-radius: 8px;
        }

        main .setting_setion .notification p {
            font-size: 16px;
            color: #191936;
            font-weight: 600;
        }

        main .setting_setion .orders .nav {
            background: #1d70b7;
            width: min(100%, 460px);
            padding: 8px;
            border-radius: 8px;
        }

        main .setting_setion .orders .nav li {
            width: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        main .setting_setion .orders .nav li button {
            width: 100%;
            color: #fff;
        }

        main .setting_setion .orders .nav li button:hover {
            color: #fff;
        }

        main .setting_setion .orders .nav li button.active {
            background: #fff;
            color: #1d70b7;
        }

        main .setting_setion .orders .nav li button.active:hover {
            color: #1d70b7;
        }

        main .setting_setion .orders .order_card {
            padding: 16px;
            background: #fff;
            box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;
            border-radius: 8px;
            height: 100%;
        }

        main .setting_setion .orders .order_card h4 {
            font-size: 20px;
            font-weight: 700;
        }

        main .setting_setion .orders .order_card .title {
            display: flex;
            justify-content: space-between;
        }

        main .setting_setion .orders .order_card .title p {
            color: #1d70b7;
            font-size: 18px;
            font-weight: 700;
        }

        main .setting_setion .orders .order_card .title a {
            color: #1d70b7;
            font-size: 14px;
        }

        main .setting_setion .orders .order_card .pro {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-bottom: 8px;
        }

        main .setting_setion .orders .order_card .pro .progress {
            height: 8px;
            background: #c8e1f6;
        }

        main .setting_setion .orders .order_card .pro .progress .progress-bar {
            background: #1d70b7;
        }

        main .setting_setion .orders .order_card .pro .staus p {
            margin: 0;
            color: #191936;
            font-size: 14px;
            color: #777777;
        }

        main .setting_setion .orders .order_card .time {
            color: #777777;
            font-size: 12px;
        }

        main .setting_setion .order_status {
            padding: 24px 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        main .setting_setion .order_status .progress_state {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        main .setting_setion .order_status .progress_state h4 {
            margin: 0;
            color: #191936;
            font-size: 18px;
            font-weight: 700;
        }

        main .setting_setion .order_status .progress_state h6 {
            font-size: 14px;
            margin: 0;
            color: #191936;
            font-weight: 700;
        }

        main .setting_setion .order_status .progress_state h6 span {
            font-size: 18px;
            color: #1d70b7;
        }

        main .setting_setion .order_status .progress_state .icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 24px;
            background: linear-gradient(12deg, #191936, #1d5eb7);
        }

        main .setting_setion .order_status .progress,
        main .setting_setion .order_status .progress-stacked {
            height: 8px;
            background: #c8e1f6;
        }

        main .setting_setion .order_status .progress .progress-bar,
        main .setting_setion .order_status .progress-stacked .progress-bar {
            background: #1d70b7;
        }

        .dashboard_welcome {
            margin-bottom: 2rem;
        }

        .welcome_card {
            background: linear-gradient(135deg, #667eea 0%, rgb(29, 94, 183) 100%);
            color: white;
            padding: 2.5rem;
            border-radius: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .welcome_card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .welcome_content {
            position: relative;
            z-index: 2;
        }

        .welcome_content h3 {
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
        }

        .welcome_content p {
            margin-bottom: 1rem;
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .welcome_stats {
            display: flex;
            gap: 1.5rem;
        }

        .stat_item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .welcome_icon {
            position: relative;
            z-index: 2;
        }

        .welcome_icon i {
            font-size: 5rem;
            opacity: 0.2;
        }

        /* Statistics Cards - Unified Design */
        .dashboard_stat_card {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 6px;
            border-left: 3px solid #1a73c0;
            margin: 0.25rem 0;
            font-size: 0.85rem;
            line-height: 1.3;
            display: flex;
            flex-direction: column;
        }

        .dashboard_stat_card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .stat_header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .stat_icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .orders_icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .contracts_icon {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }

        .complaints_icon {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .stat_title h4 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .stat_title p {
            color: #64748b;
            font-weight: 500;
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .stat_footer {
            padding: 1rem 1.5rem;
            background: #f8fafc;
        }

        .stat_item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .stat_item:last-child {
            margin-bottom: 0;
        }

        .stat_label {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .stat_value {
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .active_value {
            background: #dcfce7;
            color: #166534;
        }

        .completed_value {
            background: #dbeafe;
            color: #1e40af;
        }

        .pending_value {
            background: #fef3c7;
            color: #92400e;
        }

        .resolved_value {
            background: #dcfce7;
            color: #166534;
        }

        .stat_header {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .stat_icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .orders_icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .contracts_icon {
            background: linear-gradient(135deg, #f093fb, #f5576c);
        }

        .complaints_icon {
            background: linear-gradient(135deg, #4facfe, #00f2fe);
        }

        .stat_title h4 {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .stat_title p {
            color: #64748b;
            font-weight: 500;
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .stat_footer {
            padding: 1rem 1.5rem;
            background: #f8fafc;
        }

        .stat_item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .stat_item:last-child {
            margin-bottom: 0;
        }

        .stat_label {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .stat_value {
            font-weight: 600;
            font-size: 0.9rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        .active_value {
            background: #dcfce7;
            color: #166534;
        }

        .completed_value {
            background: #dbeafe;
            color: #1e40af;
        }

        .pending_value {
            background: #fef3c7;
            color: #92400e;
        }

        .resolved_value {
            background: #dcfce7;
            color: #166534;
        }

        .stat_link {
            position: absolute;
            top: 1rem;
            left: 1rem;
            color: #94a3b8;
            font-size: 1.25rem;
            transition: all 0.3s ease;
            opacity: 0.6;
        }

        .stat_link:hover {
            color: #475569;
            opacity: 1;
            transform: scale(1.1);
        }

        /* Quick Actions */
        .quick_actions {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }

        /* Recent Activities */
        .recent_activities {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .section_title {
            margin-bottom: 1.5rem;
        }

        .section_title h5 {
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .section_title p {
            color: #64748b;
            margin-bottom: 0;
            font-size: 0.95rem;
        }

        .action_card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid #f1f5f9;
            height: 100px;
        }

        .action_card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            color: inherit;
            text-decoration: none;
        }

        .action_card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 0;
            height: 100%;
            background: linear-gradient(135deg, #667eea, rgb(29, 94, 183));
            transition: width 0.3s ease;
            z-index: 0;
        }

        .action_card:hover::before {
            width: 4px;
        }

        .action_icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea, rgb(29, 94, 183));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 1rem;
            font-size: 1.25rem;
            color: white;
            position: relative;
            z-index: 1;
        }

        .action_content {
            flex: 1;
            position: relative;
            z-index: 1;
            margin-left: 1rem;
        }

        .action_content h6 {
            margin-bottom: 0.25rem;
            font-weight: 600;
            color: #1e293b;
            font-size: 1rem;
        }

        .action_content p {
            margin-bottom: 0;
            color: #64748b;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        .action_arrow {
            color: #94a3b8;
            font-size: 1.25rem;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .action_card:hover .action_arrow {
            transform: translateX(-5px);
            color: #667eea;
        }

        /* Recent Activities */
        .activity_section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .section_header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .header_content {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .header_content h5 {
            margin-bottom: 0;
            color: #1e293b;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .header_content .badge {
            font-size: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 15px;
        }

        .view_all_btn {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(102, 126, 234, 0.1);
            font-size: 0.85rem;
        }

        .view_all_btn:hover {
            color: #5a67d8;
            background: rgba(102, 126, 234, 0.15);
            text-decoration: none;
        }

        .activity_list {
            padding: 0;
        }

        .activity_item {
            display: flex;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        .activity_item:hover {
            background-color: #f8fafc;
            transform: translateX(3px);
        }

        .activity_item:last-child {
            border-bottom: none;
        }

        .activity_icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 1rem;
            color: white;
            font-size: 1.1rem;
        }

        .activity_content {
            flex: 1;
            margin-left: 1rem;
        }

        .activity_content h6 {
            margin-bottom: 0.25rem;
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .activity_content p {
            margin-bottom: 0.25rem;
            color: #64748b;
            font-size: 0.85rem;
        }

        .activity_content small {
            color: #94a3b8;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status_badge {
            padding: 0.375rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .status_new,
        .status_pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status_paid,
        .status_active {
            background: #dcfce7;
            color: #166534;
        }

        .status_done,
        .status_completed,
        .status_closed,
        .status_resolved {
            background: #dbeafe;
            color: #1e40af;
        }

        .status_refused,
        .status_cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        /* No Activities */
        .no_activities {
            text-align: center;
            padding: 3rem 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .no_activities_icon i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 1.5rem;
        }

        .no_activities h5 {
            color: #475569;
            margin-bottom: 0.75rem;
            font-weight: 700;
            font-size: 1.25rem;
        }

        .no_activities p {
            color: #64748b;
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .no_activities .btn {
            padding: 0.75rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        /* Profile Sidebar Styles */
        .profile_side_bar {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.05);
            overflow: hidden;
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .profile_header {
            background: linear-gradient(135deg, #667eea 0%, rgb(29, 94, 183) 100%);
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .profile_header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .profile_avatar {
            position: relative;
            z-index: 2;
            margin-bottom: 1rem;
            color: white;
        }

        .profile_avatar i {
            font-size: 4rem;
            opacity: 0.8;
        }

        .profile_info {
            position: relative;
            z-index: 2;
        }

        .profile_info h4,
        .profile_phone,
        .profile_email {
            margin-bottom: 0.5rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: white;
        }

        .profile_phone,
        .profile_email {
            display: block;
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.25rem;
        }

        .profile_nav {
            padding: 1.5rem 0;
        }

        .nav_links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .nav_item {
            margin-bottom: 0.5rem;
        }

        .nav_link {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            text-decoration: none;
            color: rgb(52, 56, 60);
            transition: all 0.3s ease;
            border-radius: 0;
            position: relative;
        }

        .nav_link:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            color: rgb(29, 94, 183);
            text-decoration: none;
            transform: translateX(5px);
        }

        .nav_item.active .nav_link {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            color: rgb(58, 58, 71);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        .nav_item.active .nav_link::before {
            content: '';
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: white;
            border-radius: 0 2px 2px 0;
        }

        .nav_icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(102, 126, 234, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 1rem;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .nav_item.active .nav_icon {
            background: rgba(255, 255, 255, 0.2);
        }

        .nav_content {
            flex: 1;
        }

        .nav_title {
            display: block;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.25rem;
        }

        .nav_desc {
            display: block;
            font-size: 0.8rem;
            opacity: 0.7;
            line-height: 1.3;
        }

        .profile_actions {
            padding: 1.5rem;
            border-top: 1px solid #e2e8f0;
            background: #f7fafc;
        }

        .action_divider {
            text-align: center;
            margin-bottom: 1rem;
            position: relative;
        }

        .action_divider::before {
            content: '';
            position: absolute;
            top: 50%;
            right: 0;
            left: 0;
            height: 1px;
            background: #e2e8f0;
            z-index: 1;
        }

        .action_divider span {
            background: #f7fafc;
            padding: 0 1rem;
            color: #718096;
            font-size: 0.8rem;
            font-weight: 600;
            position: relative;
            z-index: 2;
        }

        .action_links {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .action_item {
            margin-bottom: 0.5rem;
        }

        .action_link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: #4a5568;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .action_link:hover {
            text-decoration: none;
            transform: translateX(3px);
        }

        .logout_link:hover {
            background: rgba(239, 68, 68, 0.1);
            color: #e53e3e;
        }

        .delete_link:hover {
            background: rgba(245, 101, 101, 0.1);
            color: #f56565;
        }

        .action_icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-left: 0.75rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .logout_link:hover .action_icon {
            background: rgba(239, 68, 68, 0.2);
        }

        .delete_link:hover .action_icon {
            background: rgba(245, 101, 101, 0.2);
        }

        .edit_profile_card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .card_header {
            background: linear-gradient(135deg, #667eea 0%, rgb(29, 94, 183) 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card_header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .card_header h3 {
            position: relative;
            z-index: 2;
            margin-bottom: 0.5rem;
            font-size: 1.75rem;
            font-weight: 700;
            color: white;
        }

        .card_header p {
            position: relative;
            z-index: 2;
            margin-bottom: 0;
            opacity: 0.9;
            font-size: 1rem;
            color: white;
        }

        .card_body {
            padding: 2rem;
        }

        .profile_form {
            max-width: 600px;
            margin: 0 auto;
        }

        .form_group {
            margin-bottom: 1.5rem;
        }

        .form_group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: white;
            font-size: 0.95rem;
        }

        .form_group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form_group input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form_group input[readonly] {
            background: #f8fafc;
            color: #64748b;
            cursor: not-allowed;
        }

        .form_text {
            display: block;
            margin-top: 0.25rem;
            color: #64748b;
            font-size: 0.8rem;
            font-style: italic;
        }

        .form_actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 0.875rem 2rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            cursor: pointer;
        }

        .btn_primary {
            background: linear-gradient(135deg, #667eea, rgb(29, 94, 183));
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn_primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            color: white;
            text-decoration: none;
        }

        .btn_secondary {
            background: #f1f5f9;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }

        .btn_secondary:hover {
            background: #e2e8f0;
            color: #475569;
            text-decoration: none;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .card_header {
                padding: 1.5rem;
            }

            .card_header h3 {
                font-size: 1.5rem;
            }

            .card_body {
                padding: 1.5rem;
            }

            .form_actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <style>
        .title_actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .title_actions a {
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
        }

        .return_request_btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white !important;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.2);
        }

        .return_request_btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.4);
            color: white !important;
            text-decoration: none;
            background: linear-gradient(135deg, #dc2626, #b91c1c);
        }

        .return_request_btn i {
            font-size: 0.9rem;
        }

        .return_request_pending {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            opacity: 0.9;
            box-shadow: 0 2px 8px rgba(100, 116, 139, 0.2);
        }

        .return_request_pending i {
            font-size: 0.9rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .title_actions {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .return_request_btn,
            .return_request_pending {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
@endsection

@section('content')
<main>

    <!-- *** pages header *** -->
    <section class="page_header"  style="background-image: url({{ asset('site/assets/images/bg-pattern-1.png') }})">
        <h1>{{helperTrans('web.my_profile')}}</h1>
        <h6><a href="{{route('site.home.index')}}">{{helperTrans('web.Home')}}</a> / {{helperTrans('web.my_contracts')}}</h6>
    </section>
    <section class="setting_setion">
        <div class="container">
            <div class="row ">
                @include('site.pages.profile.includes.profile_aside')

                <div class="col-lg-8 col-12">
                    <div class="orders">
                        <ul class="nav saved_items_tabs nav-pills mb-3" id="pills-tab" role="tablist">
                            <!-- currentOrders tab btn -->
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="pills-currentOrders-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-currentOrders" type="button" role="tab"
                                    aria-controls="pills-currentOrders" aria-selected="true">
                                    {{ helperTrans('web.current') }}
                                </button>
                            </li>
                            <!-- previousOrders tab btn -->
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="pills-previousOrders-tab" data-bs-toggle="pill"
                                    data-bs-target="#pills-previousOrders" type="button" role="tab"
                                    aria-controls="pills-previousOrders" aria-selected="false">
                                    {{ helperTrans('web.previous_order') }}
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content" id="pills-tabContent">
                            <!-- currentOrders tab -->
                            <div class="tab-pane fade show active" id="pills-currentOrders" role="tabpanel"
                                aria-labelledby="pills-currentOrders-tab">
                                <div class="row">
                                    @foreach ($current as $current_contract)
                                        <div class="col-lg-6 col-md-6 col-12 p-2">
                                            <div class="order_card">
                                                <h4>{{ getContractStatus($current_contract->status) }}</h4>
                                                <div class="title">
                                                    <p>#{{ $current_contract->id }}</p>
                                                    <div class="title_actions">
                                                        <a
                                                            href="{{ route('site.contracts.show', $current_contract->id) }}">{{ helperTrans('web.details') }}</a>
                                                        @if (!$current_contract->hasPendingReturnRequest())
                                                            {{-- <a href="{{ route('web.contract-return-requests.create', $current_contract->id) }}"
                                                                class="return_request_btn">
                                                                <i class="fa-light fa-undo"></i>
                                                                {{__('ingaz.Refund request')}}
                                                            </a> --}}
                                                        @else
                                                            <span class="return_request_pending">
                                                                <i class="fa-light fa-clock"></i>
                                                                {{__('ingaz.Refund request pending')}}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="pro">
                                                    <div class="staus">
                                                        <p>@lang('web.current_contract')</p>
                                                    </div>
                                                </div>
                                                @if ($current_contract->maid && $current_contract->maid->passport_num)
                                                    <div class="pro">
                                                        <div class="staus">
                                                            <p>{{ helperTrans('web.passport_number') }}:
                                                                {{ $current_contract->maid->passport_num }}</p>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if ($current_contract->info && $current_contract->info->visa_number)
                                                    <div class="pro">
                                                        <div class="staus">
                                                            <p>{{ helperTrans('web.visa_number') }}:
                                                                {{ $current_contract->info->visa_number }}</p>
                                                        </div>
                                                    </div>
                                                @endif
                                                <span
                                                    class="time">{{ @$current_contract->created_at->translatedFormat('d F , Y h:i a') }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <!-- previous orders tab -->
                            <div class="tab-pane fade" id="pills-previousOrders" role="tabpanel"
                                aria-labelledby="pills-previousOrders-tab">
                                <div class="row">
                                    @foreach ($previous as $previous_contract)
                                        <div class="col-lg-6 col-md-6 col-12 p-2">
                                            <div class="order_card">
                                                <h4>{{ getContractStatus($previous_contract->status) }}</h4>
                                                <div class="title">
                                                    <p>#{{ $previous_contract->id }}</p>
                                                    <div class="title_actions">
                                                        <a
                                                            href="{{ route('site.contracts.show', $previous_contract->id) }}">{{ helperTrans('web.details') }}</a>
                                                        @if (!$previous_contract->hasPendingReturnRequest())
                                                            {{-- <a href="{{ route('web.contract-return-requests.create', $previous_contract->id) }}"
                                                                class="return_request_btn">
                                                                <i class="fa-light fa-undo"></i>
                                                                {{__('ingaz.Refund request')}}
                                                            </a> --}}
                                                        @else
                                                            <span class="return_request_pending">
                                                                <i class="fa-light fa-clock"></i>
                                                                {{__('ingaz.Refund request pending')}}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="pro">
                                                    <div class="staus">
                                                        <p>@lang('web.previous_contract')</p>
                                                    </div>
                                                </div>
                                                @if ($previous_contract->maid && $previous_contract->maid->passport_num)
                                                    <div class="pro">
                                                        <div class="staus">
                                                            <p>{{ helperTrans('web.passport_number') }}:
                                                                {{ $previous_contract->maid->passport_num }}</p>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if ($previous_contract->info && $previous_contract->info->visa_number)
                                                    <div class="pro">
                                                        <div class="staus">
                                                            <p>{{ helperTrans('web.visa_number') }}:
                                                                {{ $previous_contract->info->visa_number }}</p>
                                                        </div>
                                                    </div>
                                                @endif
                                                <span
                                                    class="time">{{ @$previous_contract->created_at->translatedFormat('d F , Y h:i a') }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

</main>
@endsection

@section('scripts')

@endsection
