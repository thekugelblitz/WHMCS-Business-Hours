{* Business Hours - Department Cards Widget *}
<div class="bh-widget" data-bh-widget="department-cards">
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
            {if $card.status.next_change}
            <div class="bh-card__detail">
                <span class="bh-card__detail-label">Next</span>
                <span class="bh-card__detail-value" data-bh-next="{$card.department->id}">{$card.status.next_change|escape}</span>
            </div>
            {/if}
            {if isset($card.response_time.message)}
            <div class="bh-response-time" style="margin-top: 10px; font-size: 12px;">
                {$card.response_time.message|escape}
            </div>
            {/if}
        </div>
        {/foreach}
    </div>
    {/if}
</div>
