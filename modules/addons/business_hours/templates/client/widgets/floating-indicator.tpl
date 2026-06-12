{* Business Hours - Floating Status Indicator *}
{if isset($status)}
<div class="bh-widget bh-floating" data-bh-widget="floating" data-bh-url="index.php?m=business_hours" data-bh-status="all">
    <span class="bh-dot {if $status.is_open}bh-dot--online{else}bh-dot--offline{/if}"></span>
    <span class="bh-status-label">{$status.label|escape}</span>
</div>
{/if}
