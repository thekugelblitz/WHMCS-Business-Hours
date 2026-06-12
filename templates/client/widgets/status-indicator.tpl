{* Business Hours - Status Indicator Widget *}
<div class="bh-widget" data-bh-widget="status-indicator">
    {if isset($status)}
    <span class="bh-status-badge {if $status.is_open}bh-status-badge--online{elseif $status.source == 'holiday'}bh-status-badge--holiday{else}bh-status-badge--offline{/if}" data-bh-status="all">
        <span class="bh-dot {if $status.is_open}bh-dot--online{elseif $status.source == 'holiday'}bh-dot--holiday{else}bh-dot--offline{/if}"></span>
        <span class="bh-status-label">{$status.label|escape}</span>
    </span>
    {/if}
</div>
