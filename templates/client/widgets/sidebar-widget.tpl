{* Business Hours - Sidebar Widget Template *}
<div class="bh-widget bh-sidebar" data-bh-widget="sidebar">
    <div class="bh-sidebar__header">
        <h4 class="bh-sidebar__title">
            <i class="fas fa-clock bh-sidebar__title-icon"></i>
            {$_lang.widget_support_hours|default:'Support Hours'}
        </h4>
        {if isset($status)}
            {if $status.is_open}
                <span class="bh-status-badge bh-status-badge--online" data-bh-status="all">
                    <span class="bh-dot bh-dot--online"></span>
                    <span class="bh-status-label">{$status.label|escape}</span>
                </span>
            {else}
                <span class="bh-status-badge bh-status-badge--offline" data-bh-status="all">
                    <span class="bh-dot bh-dot--offline"></span>
                    <span class="bh-status-label">{$status.label|escape}</span>
                </span>
            {/if}
        {/if}
    </div>
    <div class="bh-sidebar__body">
        {if isset($status)}
        <div class="bh-sidebar__row">
            <span class="bh-sidebar__label">{$_lang.widget_today_schedule|default:"Today's Schedule"}</span>
            <span class="bh-sidebar__value" data-bh-hours="all">{$status.today_hours|escape|default:'N/A'}</span>
        </div>
        {if $status.next_change}
        <div class="bh-sidebar__row">
            <span class="bh-sidebar__label">{if $status.is_open}{$_lang.widget_next_closing|default:'Next Closing'}{else}{$_lang.widget_next_opening|default:'Next Opening'}{/if}</span>
            <span class="bh-sidebar__value" data-bh-next="all">{$status.next_change|escape}</span>
        </div>
        {/if}
        {/if}

        {if isset($tomorrow_schedule) && $tomorrow_schedule}
        <div class="bh-sidebar__row">
            <span class="bh-sidebar__label">Tomorrow</span>
            <span class="bh-sidebar__value">{$tomorrow_schedule.display|escape|default:'N/A'}</span>
        </div>
        {/if}

        {if $show_timezone|default:false && $current_time|default:''}
        <div class="bh-timezone">{$current_time|escape} {$company_timezone|escape}</div>
        {/if}

        {if $show_response_times|default:false && isset($response_time.message)}
        <div class="bh-response-time">{$response_time.message|escape}</div>
        {/if}

        {if $show_holidays|default:false && isset($upcoming_holidays) && $upcoming_holidays}
        <div class="bh-holidays">
            <div class="bh-holidays__title">{$_lang.widget_upcoming_holidays|default:'Upcoming Holidays'}</div>
            {foreach from=$upcoming_holidays item=h}
            <div class="bh-holiday-item">
                <span class="bh-holiday-item__icon"><i class="fas fa-calendar-day"></i></span>
                <span class="bh-holiday-item__name">{$h.name|escape}</span>
                <span class="bh-holiday-item__date">{$h.start_date|escape}</span>
            </div>
            {/foreach}
        </div>
        {/if}

        <a href="index.php?m=business_hours" class="bh-view-full">{$_lang.widget_view_full_schedule|default:'View Full Schedule'} &rarr;</a>
    </div>
</div>
