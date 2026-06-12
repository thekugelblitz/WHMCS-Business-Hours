<?php
/**
 * English Language File
 *
 * @package    BusinessHours
 */

$_LANG = [];

// Module
$_LANG['module_title'] = 'Business Hours & Support Availability';
$_LANG['module_description'] = 'Manage support schedules, holidays, and availability settings.';

// Dashboard
$_LANG['dashboard'] = 'Dashboard';
$_LANG['dashboard_subtitle'] = 'Manage your support schedules, holidays, and availability settings.';
$_LANG['active_departments'] = 'Active Departments';
$_LANG['currently_open'] = 'Currently Open';
$_LANG['upcoming_holidays'] = 'Upcoming Holidays';
$_LANG['server_time'] = 'Server Time';
$_LANG['department_status'] = 'Department Status';
$_LANG['quick_actions'] = 'Quick Actions';

// Navigation
$_LANG['nav_dashboard'] = 'Dashboard';
$_LANG['nav_departments'] = 'Departments';
$_LANG['nav_schedules'] = 'Schedules';
$_LANG['nav_holidays'] = 'Holidays';
$_LANG['nav_analytics'] = 'Analytics';
$_LANG['nav_settings'] = 'Settings';

// Departments
$_LANG['department'] = 'Department';
$_LANG['departments'] = 'Departments';
$_LANG['add_department'] = 'Add Department';
$_LANG['edit_department'] = 'Edit Department';
$_LANG['delete_department'] = 'Delete Department';
$_LANG['department_name'] = 'Department Name';
$_LANG['department_slug'] = 'Slug';
$_LANG['department_description'] = 'Description';
$_LANG['department_timezone'] = 'Timezone';
$_LANG['department_24x7'] = '24/7 Support';
$_LANG['department_color'] = 'Color';
$_LANG['department_icon'] = 'Icon';
$_LANG['department_sort_order'] = 'Sort Order';
$_LANG['department_status'] = 'Status';
$_LANG['department_saved'] = 'Department saved successfully.';
$_LANG['department_deleted'] = 'Department deleted successfully.';
$_LANG['department_not_found'] = 'Department not found.';
$_LANG['department_confirm_delete'] = 'Are you sure you want to delete this department? This will also delete all associated schedules.';

// Schedules
$_LANG['schedule'] = 'Schedule';
$_LANG['schedules'] = 'Schedules';
$_LANG['add_schedule'] = 'Add Schedule';
$_LANG['edit_schedule'] = 'Edit Schedule';
$_LANG['delete_schedule'] = 'Delete Schedule';
$_LANG['clone_schedule'] = 'Clone Schedule';
$_LANG['schedule_name'] = 'Schedule Name';
$_LANG['schedule_type'] = 'Schedule Type';
$_LANG['schedule_type_regular'] = 'Regular';
$_LANG['schedule_type_seasonal'] = 'Seasonal';
$_LANG['schedule_type_event'] = 'Special Event';
$_LANG['schedule_type_temporary'] = 'Temporary';
$_LANG['schedule_effective_from'] = 'Effective From';
$_LANG['schedule_effective_to'] = 'Effective To';
$_LANG['schedule_priority'] = 'Priority';
$_LANG['schedule_active'] = 'Active';
$_LANG['schedule_saved'] = 'Schedule saved successfully.';
$_LANG['schedule_deleted'] = 'Schedule deleted successfully.';
$_LANG['schedule_cloned'] = 'Schedule cloned successfully.';
$_LANG['schedule_not_found'] = 'Schedule not found.';
$_LANG['schedule_confirm_delete'] = 'Are you sure you want to delete this schedule and all its time slots?';

// Time Slots
$_LANG['time_slots'] = 'Time Slots';
$_LANG['add_slot'] = 'Add Time Slot';
$_LANG['day_of_week'] = 'Day';
$_LANG['open_time'] = 'Open Time';
$_LANG['close_time'] = 'Close Time';
$_LANG['slot_label'] = 'Label (Optional)';
$_LANG['no_slots'] = 'No time slots configured. This department will appear as closed.';

// Days of week
$_LANG['day_sunday'] = 'Sunday';
$_LANG['day_monday'] = 'Monday';
$_LANG['day_tuesday'] = 'Tuesday';
$_LANG['day_wednesday'] = 'Wednesday';
$_LANG['day_thursday'] = 'Thursday';
$_LANG['day_friday'] = 'Friday';
$_LANG['day_saturday'] = 'Saturday';

// Holidays
$_LANG['holiday'] = 'Holiday';
$_LANG['holidays'] = 'Holidays';
$_LANG['add_holiday'] = 'Add Holiday';
$_LANG['edit_holiday'] = 'Edit Holiday';
$_LANG['delete_holiday'] = 'Delete Holiday';
$_LANG['holiday_name'] = 'Holiday Name';
$_LANG['holiday_description'] = 'Description';
$_LANG['holiday_start_date'] = 'Start Date';
$_LANG['holiday_end_date'] = 'End Date';
$_LANG['holiday_recurring'] = 'Recurring Annually';
$_LANG['holiday_partial_day'] = 'Partial Day Closure';
$_LANG['holiday_partial_open'] = 'Partial Day Open Time';
$_LANG['holiday_partial_close'] = 'Partial Day Close Time';
$_LANG['holiday_type'] = 'Holiday Type';
$_LANG['holiday_type_company'] = 'Company Holiday';
$_LANG['holiday_type_regional'] = 'Regional Holiday';
$_LANG['holiday_type_global'] = 'Global Holiday';
$_LANG['holiday_type_emergency'] = 'Emergency Closure';
$_LANG['holiday_region'] = 'Region';
$_LANG['holiday_departments'] = 'Applies to Departments';
$_LANG['holiday_all_departments'] = 'All Departments';
$_LANG['holiday_reopen_message'] = 'Reopening Message';
$_LANG['holiday_saved'] = 'Holiday saved successfully.';
$_LANG['holiday_deleted'] = 'Holiday deleted successfully.';
$_LANG['holiday_not_found'] = 'Holiday not found.';
$_LANG['holiday_confirm_delete'] = 'Are you sure you want to delete this holiday?';

// Overrides
$_LANG['override'] = 'Override';
$_LANG['overrides'] = 'Schedule Overrides';
$_LANG['add_override'] = 'Add Override';
$_LANG['override_date'] = 'Date';
$_LANG['override_closed'] = 'Closed All Day';
$_LANG['override_reason'] = 'Reason';
$_LANG['override_saved'] = 'Override saved successfully.';
$_LANG['override_deleted'] = 'Override deleted successfully.';

// Status Labels
$_LANG['status_online'] = 'Online';
$_LANG['status_available'] = 'Available';
$_LANG['status_open'] = 'Open';
$_LANG['status_offline'] = 'Offline';
$_LANG['status_closed'] = 'Closed';
$_LANG['status_limited'] = 'Limited Support';
$_LANG['status_emergency'] = 'Emergency Support Only';
$_LANG['status_holiday'] = 'Holiday Hours';

// Widget Labels
$_LANG['widget_current_status'] = 'Current Status';
$_LANG['widget_current_time'] = 'Current Time';
$_LANG['widget_office_timezone'] = 'Office Timezone';
$_LANG['widget_today_schedule'] = "Today's Schedule";
$_LANG['widget_tomorrow_schedule'] = "Tomorrow's Schedule";
$_LANG['widget_next_opening'] = 'Next Opening';
$_LANG['widget_next_closing'] = 'Next Closing';
$_LANG['widget_upcoming_holidays'] = 'Upcoming Holidays';
$_LANG['widget_response_time'] = 'Expected Response Time';
$_LANG['widget_department_availability'] = 'Department Availability';
$_LANG['widget_support_hours'] = 'Support Hours';
$_LANG['widget_all_departments'] = 'All Departments';
$_LANG['widget_view_full_schedule'] = 'View Full Schedule';

// Countdown
$_LANG['countdown_opening_in'] = 'Opening in %s';
$_LANG['countdown_closing_in'] = 'Closing in %s';
$_LANG['countdown_returning_on'] = 'Returning on %s';
$_LANG['countdown_returning_after'] = 'Returning after %s';
$_LANG['countdown_hours'] = '%d hours';
$_LANG['countdown_minutes'] = '%d minutes';
$_LANG['countdown_hour'] = '1 hour';
$_LANG['countdown_minute'] = '1 minute';

// Schedule Display
$_LANG['schedule_24x7'] = '24/7 Support';
$_LANG['schedule_closed_today'] = 'Closed Today';
$_LANG['schedule_no_schedule'] = 'No schedule available';
$_LANG['schedule_open_now'] = 'Open Now';
$_LANG['schedule_closed_now'] = 'Currently Closed';

// Settings
$_LANG['settings'] = 'Settings';
$_LANG['settings_general'] = 'General';
$_LANG['settings_display'] = 'Display';
$_LANG['settings_colors'] = 'Colors';
$_LANG['settings_labels'] = 'Labels';
$_LANG['settings_ajax'] = 'Live Updates';
$_LANG['settings_advanced'] = 'Advanced';
$_LANG['settings_saved'] = 'Settings saved successfully.';
$_LANG['settings_company_timezone'] = 'Company Timezone';
$_LANG['settings_time_format'] = 'Time Format';
$_LANG['settings_time_12h'] = '12-hour (1:00 PM)';
$_LANG['settings_time_24h'] = '24-hour (13:00)';
$_LANG['settings_date_format'] = 'Date Format';
$_LANG['settings_show_timezone'] = 'Show Timezone to Visitors';
$_LANG['settings_enable_client_tz'] = 'Allow Visitors to View in Their Timezone';
$_LANG['settings_ajax_enabled'] = 'Enable Live Status Updates';
$_LANG['settings_ajax_interval'] = 'Update Interval (seconds)';
$_LANG['settings_custom_css'] = 'Custom CSS';

// Display Settings
$_LANG['display_show_homepage'] = 'Show on Homepage';
$_LANG['display_show_tickets'] = 'Show on Ticket Pages';
$_LANG['display_show_knowledgebase'] = 'Show on Knowledgebase';
$_LANG['display_show_announcements'] = 'Show on Announcements';
$_LANG['display_show_contact'] = 'Show on Contact Page';
$_LANG['display_show_sidebar'] = 'Show Sidebar Widget';
$_LANG['display_show_floating'] = 'Show Floating Indicator';
$_LANG['display_show_footer'] = 'Show Footer Widget';
$_LANG['display_show_banner'] = 'Show Holiday Banner';
$_LANG['display_show_dashboard'] = 'Show Dashboard Block';
$_LANG['display_show_countdown'] = 'Show Countdown Timer';
$_LANG['display_show_response_times'] = 'Show Response Time Expectations';

// Analytics
$_LANG['analytics'] = 'Analytics';
$_LANG['analytics_overview'] = 'Analytics Overview';
$_LANG['analytics_widget_views'] = 'Widget Views';
$_LANG['analytics_department_views'] = 'Department Views';
$_LANG['analytics_holiday_views'] = 'Holiday Notice Views';
$_LANG['analytics_total_events'] = 'Total Events';
$_LANG['analytics_date_range'] = 'Date Range';
$_LANG['analytics_last_7_days'] = 'Last 7 Days';
$_LANG['analytics_last_30_days'] = 'Last 30 Days';
$_LANG['analytics_last_90_days'] = 'Last 90 Days';

// Common
$_LANG['save'] = 'Save';
$_LANG['cancel'] = 'Cancel';
$_LANG['delete'] = 'Delete';
$_LANG['edit'] = 'Edit';
$_LANG['add'] = 'Add';
$_LANG['back'] = 'Back';
$_LANG['actions'] = 'Actions';
$_LANG['status'] = 'Status';
$_LANG['active'] = 'Active';
$_LANG['disabled'] = 'Disabled';
$_LANG['archived'] = 'Archived';
$_LANG['yes'] = 'Yes';
$_LANG['no'] = 'No';
$_LANG['none'] = 'None';
$_LANG['all'] = 'All';
$_LANG['name'] = 'Name';
$_LANG['type'] = 'Type';
$_LANG['date'] = 'Date';
$_LANG['time'] = 'Time';
$_LANG['error'] = 'Error';
$_LANG['success'] = 'Success';
$_LANG['confirm'] = 'Confirm';
$_LANG['loading'] = 'Loading...';
$_LANG['no_results'] = 'No results found.';
