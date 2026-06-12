{* Business Hours - Footer Widget *}
<div class="bh-widget bh-footer" data-bh-widget="footer">
    {if isset($footer_items) && $footer_items}
        {foreach from=$footer_items item=item}
        <div class="bh-footer__item">
            <span class="bh-dot {if $item.is_open}bh-dot--online{else}bh-dot--offline{/if}"></span>
            <span class="bh-footer__name">{$item.name|escape}</span>
            <span>{$item.hours|escape}</span>
        </div>
        {/foreach}
    {/if}
</div>
