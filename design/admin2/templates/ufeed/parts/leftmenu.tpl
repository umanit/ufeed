<div class="box-header">
    <div class="box-tc">
        <div class="box-ml">
            <div class="box-mr">
                <div class="box-tl">
                    <div class="box-tr">
                        <h4>{"UFeed"|i18n('extension/ufeed/news')}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="box-bc">
    <div class="box-ml">
        <div class="box-mr">
            <div class="box-bl">
                <div class="box-br">
                    <div class="box-content">
                        <ul class="leftmenu-items">
                            <li{if $uri|begins_with('/news/feeds')} class="current"{/if}>
                                <a href="{'/news/feeds'|ezurl('no')}">{"Flux"|i18n('extension/ufeed/news')}</a>
                            </li>
                            <li{if $uri|begins_with('/news/list')} class="current"{/if}>
                                <a href="{'/news/list'|ezurl('no')}">{"Actualités"|i18n('extension/ufeed/news')}</a>
                            </li>
                            <li>
                                {def $class_name = ezini('Classes', 'NewsItem', 'ufeed.ini')
                                     $news_container_node_id = ezini('Containers', 'News', 'ufeed.ini')
                                }
                                <form method="post" action="{"content/action/"|ezurl('no')}">
                                    <input type="hidden" name="NodeID" value="{$news_container_node_id}" />
                                    <input type="hidden" name="ClassIdentifier" value="{$class_name}" />
                                    <input type="hidden" name="RedirectURI" value="{$uri}" />
                                    <input type="hidden" name="RedirectURIAfterPublish" value="{$uri}" />
                                    <input type="hidden" name="RedirectIfDiscarded" value="{$uri}" />
                                    <input type="hidden" name="RedirectIfCancel" value="{$uri}" />
                                    <input type="submit" name="NewButton" value="{"Écrire une actualité"|i18n('extension/ufeed/news')}" class="fakeLink" />
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="widthcontrol-handler" class="hide">
    <div class="widthcontrol-grippy"></div>
</div>
