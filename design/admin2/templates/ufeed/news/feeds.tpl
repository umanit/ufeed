{include uri="design:ufeed/parts/scripts_n_styles.tpl"}
<div class="context-block">
    <div class="box-header">
        <div class="box-tc">
            <div class="box-ml">
                <div class="box-mr">
                    <div class="box-tl">
                        <div class="box-tr">
                            <h1 class="context-title">{"Gestion des flux"|i18n('extension/ufeed/news')}</h1>
                            <div class="header-mainline"/>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{include uri="design:ufeed/parts/messages.tpl"}
<div class="box-ml">
    <div class="box-mr">
        <div class="box-content">
            <div class="context-toolbar">
                <div class="block">
                    <form id="feeds" action="{'/news/feeds'|ezurl('no')}" method="post">
                        <input type="submit" name="storeFeeds" id="storeFeeds" class="button" value="{"Enregistrer les flux"|i18n('extension/ufeed/news')}" />{*Submit placé au début pour que ce soit lui qui soit pris en compte lorsqu'on soumet le formulaire avec la touche entrée*}
                        <div class="yui-dt">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="url yui-dt-first">
                                            <div class="yui-dt-liner">
                                                <abbr title="Uniform Resource Locator" lang="en">URL</abbr>
                                            </div>
                                        </th>
                                        <th class="title">
                                            <div class="yui-dt-liner">
                                                {"Titre"|i18n('extension/ufeed/news')}
                                            </div>
                                        </th>
                                        <th class="status yui-dt-last">
                                            <div class="yui-dt-liner">
                                                {"Statut"|i18n('extension/ufeed/news')}
                                            </div>
                                        </th>
                                        <th>
                                            <div class="yui-dt-liner offscreen">
                                                {"Supprimer"|i18n('extension/ufeed/news')}
                                            </div>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {def $index = 0
                                         $id = 0
                                         $url = ''
                                         $title = ''
                                         $status = 'disabled'
                                    }
                                    {foreach $feeds as $feed}

                                        {*ID*}
                                        {if is_set($feed.id)}
                                            {set $id = $feed.id}
                                        {/if}

                                        {*URL*}
                                        {if is_set($feed.url)}
                                            {set $url = $feed.url}
                                        {/if}

                                        {*Title*}
                                        {if is_set($feed.title)}
                                            {set $title = $feed.title}
                                        {/if}

                                        {*Status*}
                                        {if is_set($feed.status)}
                                            {set $status = $feed.status}
                                        {/if}

                                        <tr data-feedid="{$id}"{if $index|eq(0)} class="yui-dt-first"{/if}>
                                            <td class="url yui-dt-first">
                                                <div class="yui-dt-liner">
                                                    <label for="feed_url_{$id}"><abbr title="Uniform Resource Locator" lang="en">URL</abbr></label>
                                                    <input type="text" name="feeds[{$id}][url]" id="feed_url_{$id}" value="{$url}" />
                                                </div>
                                            </td>
                                            <td class="title">
                                                <div class="yui-dt-liner">
                                                    <label for="feed_title_{$id}">{"Titre"|i18n('extension/ufeed/news')}</label>
                                                    <input type="text" name="feeds[{$id}][title]" id="feed_title_{$id}" value="{$title|wash()}" />
                                                </div>
                                            </td>
                                            <td class="status">
                                                <div class="yui-dt-liner">
                                                    <label for="feed_status_{$id}">{"Statut"|i18n('extension/ufeed/news')}</label>
                                                    <select name="feeds[{$id}][status]" id="feed_status_{$id}">
                                                        <option value="disabled"{if $status|eq("disabled")} selected="selected"{/if}>{"désactivé"|i18n('extension/ufeed/news')}</option>
                                                        <option value="manual"{if $status|eq("manual")} selected="selected"{/if}>{"manuel"|i18n('extension/ufeed/news')}</option>
                                                        <option value="auto"{if $status|eq("auto")} selected="selected"{/if}>{"automatique"|i18n('extension/ufeed/news')}</option>
                                                    </select>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="yui-dt-liner">
                                                    <input type="submit" name="deleteFeed[{$id}]" class="button red" value="{"Supprimer"|i18n('extension/ufeed/news')}" />
                                                </div>
                                            </td>
                                        </tr>

                                        {set $index = $index|inc()
                                             $id = 0
                                             $url = ''
                                             $title = ''
                                             $status = 'auto'
                                        }
                                    {/foreach}
                                </tbody>
                                <tfoot>
                                    <tr class="delete yui-dt-last">
                                        <td colspan="4">
                                            <div class="yui-dt-liner">
                                                <input type="hidden" name="lines" value="{$lines}" />
                                                <input type="submit" name="addLines" class="button" value="{"Ajouter des lignes"|i18n('extension/ufeed/news')}" />
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="controlbar">
    <div class="box-bc">
        <div class="box-ml">
            <div class="box-mr">
                <div class="box-tc">
                    <div class="box-bl">
                        <div class="box-br"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
