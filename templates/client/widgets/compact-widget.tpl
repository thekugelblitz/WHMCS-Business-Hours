{* Business Hours - Compact Widget Template *}
<div class="bh-widget bh-status-indicator" data-bh-widget="compact" data-bh-status="all">
    {if isset($status)}
        <span class="bh-dot {if $status.is_open}bh-dot--online{else}bh-dot--offline{/if}"></span>
        <span class="bh-status-label">{$status.label|escape}</span>
        <span class="bh-status-indicator__time" data-bh-hours="all">{$status.today_hours|escape|default:'N/A'}</span>
        {if $show_timezone|default:false && $company_timezone|default:''}
            <span class="bh-status-indicator__time">{$company_timezone|escape}</span>
        {/if}
    {/if}
</div>
