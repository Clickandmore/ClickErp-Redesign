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
    </style>
@endsection

@section('content')
    <main>
        <!-- page header -->
        <section class="page_header" style="background-image: url({{ asset('site/assets/images/bg-pattern-1.png') }})">
            <h1>{{ helperTrans('web.dashboard') }}</h1>
            <h6><a href="{{ route('site.home.index') }}">{{ __('web.Home') }}</a> / {{ __('web.dashboard') }}</h6>
        </section>

        <!-- dashboard section -->
        <section class="setting_setion">
            <div class="container">
                <div class="row">
                    @include('site.pages.profile.includes.profile_aside')

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
                                            {{ now()->format('h:i A') }}
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
                            <div class="col-md-3 mb-3">
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
                                    <a href="{{ route('site.last-orders.index') }}" class="stat_link">
                                        <i class="fa-light fa-arrow-left"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Contracts Statistics -->
                            <div class="col-md-3 mb-3">
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
                                    <a href="{{ route('site.contracts.index') }}" class="stat_link">
                                        <i class="fa-light fa-arrow-left"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Complaints Statistics -->
                            <div class="col-md-3 mb-3">
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
                                            <span class="stat_label">{{ __('ingaz.pending') }}</span>
                                            <span
                                                class="stat_value pending_value">{{ $stats['complaints']['pending'] }}</span>
                                        </div>
                                        <div class="stat_item">
                                            <span class="stat_label">{{ __('ingaz.resolved') }}</span>
                                            <span
                                                class="stat_value resolved_value">{{ $stats['complaints']['resolved'] }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('site.complaints.index') }}" class="stat_link">
                                        <i class="fa-light fa-arrow-left"></i>
                                    </a>
                                </div>
                            </div>

                            <!-- Sponsorship Transfers Statistics -->
                            <div class="col-md-3 mb-3">
                                <div class="dashboard_stat_card">
                                    <div class="stat_header">
                                        <div class="stat_icon transfers_icon">
                                            <i class="fa-regular fa-exchange-alt"></i>
                                        </div>
                                        <div class="stat_title">
                                            <h4>{{ $stats['sponsorship_transfers']['total'] ?? 0 }}</h4>
                                            <p>{{ __('ingaz.total_sponsorship_transfers') }}</p>
                                        </div>
                                    </div>
                                    <div class="stat_footer">
                                        <div class="stat_item">
                                            <span class="stat_label">{{ __('web.pending') }}</span>
                                            <span
                                                class="stat_value pending_value">{{ $stats['sponsorship_transfers']['pending'] ?? 0 }}</span>
                                        </div>
                                        <div class="stat_item">
                                            <span class="stat_label">{{ __('ingaz.transferred') }}</span>
                                            <span
                                                class="stat_value completed_value">{{ $stats['sponsorship_transfers']['transferred'] ?? 0 }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('site.sponsorship-transfers.index') }}" class="stat_link">
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
                                    <a href="{{ route('site.all-workers.index') }}" class="action_card">
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
                                    <a href="{{ route('site.complaints.index') }}" class="action_card">
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
                                    <a href="{{ route('site.contract-return-requests.index') }}" class="action_card">
                                        <div class="action_icon">
                                            <i class="fa-light fa-undo"></i>
                                        </div>
                                        <div class="action_content">
                                            <h6>{{ helperTrans('ingaz.contract_return_requests') }}</h6>
                                            <p>{{ helperTrans('ingaz.view_contract_return_requests') }}</p>
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
                                                <a href="{{ route('site.last-orders.index') }}" class="view_all_btn">
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
                                                                {{-- {{ $order->status }} --}}
                                                                {{ \App\Helpers\Constant::globalArray['orders_statuses'][$order->status][app()->getLocale()] }}
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
                                                <a href="{{ route('site.contracts.index') }}" class="view_all_btn">
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
                                                <a href="{{ route('site.complaints.index') }}" class="view_all_btn">
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
                                                    <h5>{{ __('ingaz.sponsorship_transfers') }}</h5>
                                                    <span
                                                        class="badge bg-info">{{ $sponsorshipTransfers->count() }}</span>
                                                </div>
                                                <a href="{{ route('site.sponsorship-transfers.index') }}"
                                                    class="view_all_btn">
                                                    {{ helperTrans('web.view_all') }}
                                                    <i class="fa-light fa-arrow-left"></i>
                                                </a>
                                            </div>
                                            <div class="activity_list">
                                                @foreach ($sponsorshipTransfers as $transfer)
                                                    <div class="activity_item">
                                                        <div class="activity_icon">
                                                            <i class="fa-regular fa-exchange-alt"></i>
                                                        </div>
                                                        <div class="activity_content">
                                                            <h6>{{ __('ingaz.sponsorship_transfer_request') }}
                                                                #{{ $transfer->id }}</h6>
                                                            <p>
                                                                @if ($transfer->old_sponsor_id == auth()->id())
                                                                    {{ __('ingaz.former_sponsor') }} →
                                                                    {{ $transfer->newSponsor ? $transfer->newSponsor->name : __('ingaz.not_specified') }}
                                                                @else
                                                                    {{ $transfer->oldSponsor ? $transfer->oldSponsor->name : __('ingaz.not_specified') }}
                                                                    → {{ __('ingaz.new_sponsor') }}
                                                                @endif
                                                            </p>
                                                            <small>{{ $transfer->created_at->diffForHumans() }}</small>
                                                            @if ($transfer->trial_days)
                                                                <br><small class="text-warning">
                                                                    <i class="fa-regular fa-clock me-1"></i>
                                                                    {{ __('ingaz.trial_days') }}:
                                                                    {{ $transfer->trial_days }} {{ __('ingaz.days') }}
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
                                            <a href="{{ route('site.all-workers.index') }}" class="btn btn-primary">
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
@section('js')
@endsection
