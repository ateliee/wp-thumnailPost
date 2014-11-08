{import file='admin.header.tpl'}
<div class="plugin-contener-main">
    <h1>サムネイル設定</h1>

    {if ( count($form._errors) ) }
        <div class="error fade"><p><strong>
        {foreach $form._errors as $k => $v }
            {$v}
        {/foreach}
        </strong></p></div>
    {/if}
    {if ( count($messages) ) }
        <div class="updated fade"><p><strong>
        {foreach $messages as $k => $v }
            {$v}
        {/foreach}
        </strong></p></div>
    {/if}
    <div class="form">
        <form action="" method="post">
        <div class="form-group">
            <div class="form-label">最小サイズ</div>
            <div class="form-inner">
                <label>幅</label>
                {nofilter($form.minWidth)} px以上
                <label>高さ</label>
                {nofilter($form.minHeight)} px以上
            </div>
        </div>
        <div class="form-group">
            <div class="form-label">サムネイル設定</div>
            <div class="form-inner">
                {nofilter($form.precedence)}
            </div>
        </div>
        <div class="form-group">
            <div class="form-label">記事文中画像</div>
            <div class="form-inner">
                {nofilter($form.number)}
            </div>
        </div>
        <div class="form-btn">
            {nofilter($form_NONCE)}
            <input type="submit" name="" value="更新" class="button button-primary">
        </div>
        </form>
    </div>
</div>
{import file='admin.footer.tpl'}
