{* Business Hours - Full Schedule Table Widget *}
<div class="bh-widget" data-bh-widget="full-schedule">
    {if isset($schedules) && $schedules}
        {foreach from=$schedules item=sched}
        <div style="margin-bottom: 24px;">
            <h4 style="font-weight: 700; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                {if $sched.department->icon}<i class="fas {$sched.department->icon|escape}" style="color:{$sched.department->color|escape|default:'var(--bh-color-accent)'};"></i>{/if}
                {$sched.department->name|escape}
                {if $sched.status.is_open}
                    <span class="bh-status-badge bh-status-badge--online bh-status-badge--sm">
                        <span class="bh-dot bh-dot--online"></span> {$sched.status.label|escape}
                    </span>
                {else}
                    <span class="bh-status-badge bh-status-badge--offline bh-status-badge--sm">
                        <span class="bh-dot bh-dot--offline"></span> {$sched.status.label|escape}
                    </span>
                {/if}
            </h4>
            <table class="bh-schedule-table">
                <thead><tr><th>Day</th><th>Hours</th></tr></thead>
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
            {if $sched.timezone}<div class="bh-timezone">All times in {$sched.timezone|escape}</div>{/if}
        </div>
        {/foreach}
    {/if}
</div>
