{* Business Hours - Holiday Announcement Banner *}
{if $has_banner|default:false && isset($holiday)}
<div class="bh-widget bh-banner" data-bh-widget="banner">
    <span class="bh-banner__icon"><i class="fas fa-info-circle"></i></span>
    <span>
        <strong>{$holiday.name|escape}</strong>
        {if $holiday.is_partial|default:false}
            &mdash; Reduced hours today: {$holiday.partial_hours|escape}
        {else}
            &mdash; We are closed. Reopening {$holiday.reopen_date|escape}.
        {/if}
        {if $holiday.reopen_message}
            {$holiday.reopen_message|escape}
        {/if}
    </span>
    <button class="bh-banner__close" aria-label="Close">&times;</button>
</div>
{/if}
