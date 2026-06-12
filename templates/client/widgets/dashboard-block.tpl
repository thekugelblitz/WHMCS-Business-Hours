{* Business Hours - Dashboard Block Widget *}
<div class="bh-widget" data-bh-widget="dashboard">
    {if isset($cards) && $cards}
    <div class="bh-cards">
        {foreach from=$cards item=card}
        <div class="bh-card">
            {if $card.department->color}
            <div class="bh-card__accent" style="background: {$card.department->color|escape};"></div>
            {/if}
            <div class="bh-card__header">
                <div class="bh-card__name">
                    {if $card.department->icon}<i class="fas {$card.department->icon|escape} bh-card__icon" style="color:{$card.department->color|escape|default:'var(--bh-color-accent)'};"></i>{/if}
                    {$card.department->name|escape}
                </div>
                {if $card.status.is_open}
                    <span class="bh-status-badge bh-status-badge--online" data-bh-status="{$card.department->id}">
                        <span class="bh-dot bh-dot--online"></span>
                        <span class="bh-status-label">{$card.status.label|escape}</span>
                    </span>
                {else}
                    <span class="bh-status-badge bh-status-badge--offline" data-bh-status="{$card.department->id}">
                        <span class="bh-dot bh-dot--offline"></span>
                        <span class="bh-status-label">{$card.status.label|escape}</span>
                    </span>
                {/if}
            </div>
            <div class="bh-card__detail">
                <span class="bh-card__detail-label">Today</span>
                <span class="bh-card__detail-value" data-bh-hours="{$card.department->id}">{$card.today_schedule.display|escape|default:'N/A'}</span>
            </div>
        </div>
        {/foreach}
    </div>
    <div style="text-align: center; margin-top: 12px;">
        <a href="index.php?m=business_hours" class="bh-view-full" style="border: none;">View All Support Hours &rarr;</a>
    </div>
    {/if}
</div>
