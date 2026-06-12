{* Business Hours - Client Area Full Page Template *}

<div class="bh-widget" data-bh-widget="full-schedule">
    <h2 style="margin-bottom: 20px; font-weight: 700;">
        <i class="fas fa-clock" style="color: var(--bh-color-accent);"></i>
        {$_lang.widget_support_hours|default:'Support Hours'}
    </h2>

    {* Aggregate Status *}
    {if isset($status)}
    <div style="margin-bottom: 24px;">
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
        {if $status.next_change}
            <span style="margin-left: 12px; color: var(--bh-text-secondary); font-size: 14px;" data-bh-next="all">
                {$status.next_change|escape}
            </span>
        {/if}
    </div>
    {/if}

    {* Department Schedule Cards *}
    {if isset($schedules) && $schedules}
    <div class="bh-cards">
        {foreach from=$schedules item=sched}
        <div class="bh-card">
            {if $sched.department->color}
            <div class="bh-card__accent" style="background: {$sched.department->color|escape};"></div>
            {/if}

            <div class="bh-card__header">
                <div class="bh-card__name">
                    {if $sched.department->icon}
                    <i class="fas {$sched.department->icon|escape} bh-card__icon" style="color: {$sched.department->color|escape:'html':'UTF-8'|default:'var(--bh-color-accent)'};"></i>
                    {/if}
                    {$sched.department->name|escape}
                </div>
                {if $sched.status.is_open}
                    <span class="bh-status-badge bh-status-badge--online" data-bh-status="{$sched.department->id}">
                        <span class="bh-dot bh-dot--online"></span>
                        <span class="bh-status-label">{$sched.status.label|escape}</span>
                    </span>
                {else}
                    <span class="bh-status-badge bh-status-badge--offline" data-bh-status="{$sched.department->id}">
                        <span class="bh-dot bh-dot--offline"></span>
                        <span class="bh-status-label">{$sched.status.label|escape}</span>
                    </span>
                {/if}
            </div>

            {* Weekly Schedule Table *}
            <table class="bh-schedule-table" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th>Day</th>
                        <th>Hours</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$sched.weekly key=day item=dayData}
                    <tr{if $dayData.day_name == $smarty.now|date_format:"%A"} class="bh-today"{/if}>
                        <td><strong>{$dayData.day_name|escape}</strong></td>
                        <td>
                            {if $dayData.is_24x7|default:false}
                                <span style="color: var(--bh-color-online); font-weight: 600;">24/7</span>
                            {elseif $dayData.closed}
                                <span class="bh-closed">Closed</span>
                            {else}
                                {$dayData.display|escape}
                            {/if}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>

            {if $sched.timezone}
            <div class="bh-timezone" style="margin-top: 8px;">
                All times in {$sched.timezone|escape}
            </div>
            {/if}
        </div>
        {/foreach}
    </div>
    {/if}

    {* Upcoming Holidays *}
    {if isset($upcoming_holidays) && $upcoming_holidays}
    <div style="margin-top: 30px;">
        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 16px;">
            <i class="fas fa-calendar-day" style="color: var(--bh-color-holiday);"></i>
            Upcoming Holidays
        </h3>
        {foreach from=$upcoming_holidays item=h}
        <div class="bh-holiday-item">
            <span class="bh-holiday-item__icon"><i class="fas fa-calendar-day"></i></span>
            <span class="bh-holiday-item__name">{$h.name|escape}</span>
            <span class="bh-holiday-item__date">{$h.start_date|escape}{if $h.is_multi_day} &mdash; {$h.end_date|escape}{/if}</span>
        </div>
        {/foreach}
    </div>
    {/if}
</div>
