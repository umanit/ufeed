{if and(is_set($errors), count($errors)|gt(0))}
    <div class="message-error">
        <h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span> {'Erreur'|i18n('extension/ufeed/news')}</h2>
        <ul>
            {foreach $errors as $error}
                <li>{$error}</li>
            {/foreach}
        </ul>
    </div>
{/if}
{if and(is_set($warnings), count($warnings)|gt(0))}
    <div class="message-warning">
        <h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span> {'Avertissement'|i18n('extension/ufeed/news')}</h2>
        <ul>
            {foreach $warnings as $warning}
                <li>{$warning}</li>
            {/foreach}
        </ul>
    </div>
{/if}
{if and(is_set($messages), count($messages)|gt(0))}
    <div class="message-feedback">
        <h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span> {'Succès'|i18n('extension/ufeed/news')}</h2>
        <ul>
            {foreach $messages as $message}
                <li>{$message}</li>
            {/foreach}
        </ul>
    </div>
{/if}
