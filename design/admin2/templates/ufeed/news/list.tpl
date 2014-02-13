{include uri="design:ufeed/parts/scripts_n_styles.tpl"}
<div class="float-break tab-block">
    <ul class="tabs">
        <li class="first{if or(eq($uri, '/news/list'), eq($uri, '/news/list/published'))} selected{/if}">
            <div>
                <a href="{'news/list/published'|ezurl('no')}">{"Publiées"|i18n('extension/ufeed/news')}</a>
            </div>
        </li>
        <li class="{if eq($uri, '/news/list/pending')}selected{/if}">
            <div>
                <a href="{'news/list/pending'|ezurl('no')}">{"En attente"|i18n('extension/ufeed/news')} ({$news.pending.count})</a>
            </div>
        </li>
        <li class="last{if eq($uri, '/news/list/rejected')} selected{/if}">
            <div>
                <a href="{'news/list/rejected'|ezurl('no')}">{"Rejetées"|i18n('extension/ufeed/news')}</a>
            </div>
        </li>
    </ul>
</div>
<div class="ufeed-tab-content">
    <div class="context-block">
        <div class="box-header">
            <div class="box-tc">
                <div class="box-ml">
                    <div class="box-mr">
                        <div class="box-tl">
                            <div class="box-tr">
                                {*Available submit buttons*}
                                {def $publishButton =   hash(
                                                             'submit_name', 'publishNews',
                                                             'submit_label', "Publier"|i18n('extension/ufeed/news'),
                                                             'button_class', 'green'
                                                            )
                                     $unPublishButton = hash(
                                                             'submit_name', 'unPublishNews',
                                                             'submit_label', "Mettre en attente"|i18n('extension/ufeed/news'),
                                                             'button_class', ''
                                                            )
                                     $deleteButton =    hash(
                                                             'submit_name', 'deleteNews',
                                                             'submit_label', "Rejeter"|i18n('extension/ufeed/news'),
                                                             'button_class', 'red'
                                                            )
                                }
                                {*Actions for each view*}
                                {def $publishedViewButtons = array($deleteButton, $unPublishButton)
                                     $pendingViewButtons   = array($publishButton, $deleteButton)
                                     $rejectedViewButtons  = array($publishButton, $unPublishButton)
                                }
                                {switch match=$type}
                                    {case match='published'}
                                        {def $submitButtons = $publishedViewButtons}
                                    {/case}
                                    {case match='pending'}
                                        {def $submitButtons = $pendingViewButtons}
                                    {/case}
                                    {case match='rejected'}
                                        {def $submitButtons = $rejectedViewButtons}
                                    {/case}
                                {/switch}
                                <h1 class="context-title">{$title|wash()}</h1>
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
                        {if $news.$type.items}
                            <form name="{$type}" action="{$uri|ezurl('no')}" method="post" id="newsform">
                                <div id="js-controls" class="yui-menu">
                                    <span id="btn-js-controls" class="yui-button yui-menu-button">
                                        <span class="first-child">
                                            <button type="button" id="ezbtn-items-button">{"Sélectionner"|i18n('extension/ufeed/news')}</button>
                                        </span>
                                    </span>
                                    <ul>
                                        <li id="ezopt-menu-check" class="yuimenuitem" title="{"Sélectionner tout"|i18n('extension/ufeed/news')}">
                                            <a class="yuimenuitemlabel" href="#">{"Sélectionner tout"|i18n('extension/ufeed/news')}</a>
                                        </li>
                                        <li id="ezopt-menu-uncheck" class="yuimenuitem" title="{"Tout désélectionner"|i18n('extension/ufeed/news')}">
                                            <a class="yuimenuitemlabel" href="#">{"Tout désélectionner"|i18n('extension/ufeed/news')}</a>
                                        </li>
                                        <li id="ezopt-menu-toggle" class="yuimenuitem" title="{"Inverser la sélection"|i18n('extension/ufeed/news')}">
                                            <a class="yuimenuitemlabel" href="#">{"Inverser la sélection"|i18n('extension/ufeed/news')}</a>
                                        </li>
                                    </ul>
                                    {*Uniquement sur la liste des actualités rejetées, purge de toutes les actualités antérieures à # jours (paramétrable en fichier INI)*}
                                    {if $type|eq('rejected')}
                                        <input type="submit" name="purgeNews" id="purgeNews" class="button" value="{"Purger"|i18n('extension/ufeed/news')}" title="{"Supprime les actualités de plus de %1 jours"|i18n('extension/ufeed/news',,hash('%1', ezini('Feeds', 'PurgeRejectedNewsItemsOlderThan', 'ufeed.ini')))}" />
                                    {/if}
                                </div>
                                <div id="{$type}" class="list yui-dt">
                                    <table>
                                        <thead>
                                            <tr>
                                                <th class="check yui-dt-first">
                                                    <div class="yui-dt-liner offscreen">
                                                        {"Publier"|i18n('extension/ufeed/news')}
                                                    </div>
                                                </th>
                                                <th class="edit">
                                                    <div class="yui-dt-liner offscreen">
                                                        {"Modifier"|i18n('extension/ufeed/news')}
                                                    </div>
                                                </th>
                                                <th class="title">
                                                    <div class="yui-dt-liner">
                                                        {"Nom"|i18n('extension/ufeed/news')}
                                                    </div>
                                                </th>
                                                <th class="date">
                                                    <div class="yui-dt-liner">
                                                        {"Date"|i18n('extension/ufeed/news')}
                                                    </div>
                                                </th>
                                                <th class="source">
                                                    <div class="yui-dt-liner">
                                                        {"Type"|i18n('extension/ufeed/news')}
                                                    </div>
                                                </th>
                                                <th class="feed yui-dt-last">
                                                    <div class="yui-dt-liner">
                                                        {"Flux d'origine"|i18n('extension/ufeed/news')}
                                                    </div>
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {foreach $news.$type.items as $index => $item}
                                                <tr id="{$item.node_id}" {if $index|eq(0)} class="yui-dt-first"{/if}>
                                                    <td class="check yui-dt-first">
                                                        <div class="yui-dt-liner">
                                                            <input type="checkbox" name="newsToUpdate[]" id="newsToUpdate_{$type}_{$item.node_id}" value="{$item.node_id}" />
                                                        </div>
                                                    </td>
                                                    <td class="edit">
                                                        <div class="yui-dt-liner">
                                                            <a class="edit" data-object-id="{$item.contentobject_id}" href="{concat('/content/edit/', $item.contentobject_id, '/f')|ezurl('no')}" title="{"Modifier"|i18n('extension/ufeed/news')}">
                                                                <span class="offscreen">{"Modifier"|i18n('extension/ufeed/news')}</span>
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td class="title">
                                                        <div class="yui-dt-liner">
                                                            <label for="newsToUpdate_{$type}_{$item.node_id}">
                                                                <a class="preview" data-node_id="{$item.node_id}" title="{"Prévisualiser"|i18n('extension/ufeed/news')}" href="{concat("content/view/full/", $item.node_id)|ezurl('no')}" target="_blank">{$item.name|wash()}</a>
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td class="date">
                                                        <div class="yui-dt-liner">
                                                            {$item.data_map.date.content.timestamp|l10n('shortdatetime')}
                                                        </div>
                                                    </td>
                                                    <td class="source">
                                                        <div class="yui-dt-liner">
                                                            {if $item.data_map.source.has_content}{"Actualité externe"|i18n('extension/ufeed/news')}{else}{"Actualité interne"|i18n('extension/ufeed/news')}{/if}
                                                        </div>
                                                    </td>
                                                    <td class="feed">
                                                        <div class="yui-dt-liner">
                                                            {if $feeds[$item.node_id]}
                                                                <a href="{$feeds[$item.node_id].url}" target="_blank">{$feeds[$item.node_id].title|wash()}</a>
                                                            {/if}
                                                        </div>
                                                    </td>
                                                </tr>
                                            {/foreach}
                                        </tbody>
                                        <tfoot>
                                            <tr class="yui-dt-last">
                                                <td colspan="6">
                                                    <div class="yui-dt-liner">
                                                        {foreach $submitButtons as $submitButton}
                                                            <input type="submit" name="{$submitButton.submit_name}" class="button {$submitButton.button_class}" value="{$submitButton.submit_label|wash()}" />
                                                        {/foreach}
                                                    </div>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>

                                    {include name = "Navigator"
                                             uri = 'design:navigator/google.tpl'
                                             page_uri = $uri
                                             view_parameters = $view_parameters
                                             item_count = $news.$type.count
                                             item_limit = ezini( 'Pagination', 'ItemsPerPage', 'ufeed.ini' )
                                    }
                                </div>
                                <div id="preview" class="grid_4">
                                    <!--Loaded by Ajax-->
                                </div>
                            </form>
                        {else}
                            <p>{"Aucune actualité."|i18n('extension/ufeed/news')}</p>
                        {/if}
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
</div>

{* Formulaire commun à tous les #newsform a.edit[data-object-id]. Workaround car on ne peut pas faire de formulaires imbriqués. *}
<form id="edit-form" method="post" action="{"content/action/"|ezurl('no')}">
    <input type="hidden" name="ContentObjectID" value="" />{*Rempli par JS*}
    <input type="hidden" name="RedirectURI" value="{$uri}" />
    <input type="hidden" name="RedirectURIAfterPublish" value="{$uri}" />
    <input type="hidden" name="RedirectIfDiscarded" value="{$uri}" />
    <input type="hidden" name="RedirectIfCancel" value="{$uri}" />
    <input type="hidden" name="EditButton" value="{"Éditer"|i18n('extension/ufeed/news')}" />
</form>

<script type="text/javascript">
    {literal}
    $(document).ready(function()
    {
        // Submit the hidden "content/edit" form when clicking the edit links
        $('#newsform a.edit[data-object-id]').live('click', function(e)
        {
            e.preventDefault();

            var newsContentObjectID = $(this).data('object-id');
            var form = $('#edit-form');

            $('input[name="ContentObjectID"]', form).val(newsContentObjectID);
            form.submit();
        });

        // The preview container
        var preview = $('#preview');

        // Ajax to preview the news item ***************************************
        var previewRows = $('a.preview').closest('td');
        previewRows.addClass('pointer');
        previewRows.click(function(e)
        {
            // Empty the preview container
            preview.html('');

            // Allowed news item status
            var availableStatus = ['published', 'pending', 'rejected'];

            // Type of the clicked item
            var status = $(this).closest('form').attr('name');

            // If the type we found as the fieldset ID is valid
            if ($.inArray(status, availableStatus)>=0)
            {

                // Item node ID
                var news_item_node_id = $(this).find('a.preview').attr('data-node_id');

                // Get the news item from database
                $.ez('ufeedtools::getNewsItem::'+news_item_node_id, {}, function(data)
                {
                    if (data.content)
                    {
                        // Title
                        var title = data.content.title.value;
                        if (!title) title = data.content.name.value;
                        preview.append('<h3>'+title+'</h3>');

                        // Image
                        if (data.content.image.value.is_valid && data.content.image.value.rss_preview.url)
                        {
                            preview.append('<img src="/'+data.content.image.value.rss_preview.url+'" alt="'+data.content.image.value.alternative_text+'" />');
                        }

                        // Text
                        if (data.content.text.value)
                        {
                            preview.append('<p>'+data.content.text.value+'</p>');
                        }

                        // Metadata container for date and source
                        preview.append('<div class="meta"></div>');

                        // Date
                        if (data.content.date.value)
                        {
                            preview.children('.meta').append('<strong>'+data.content.date.label+'&nbsp;:</strong> '+data.content.date.value+'<br/>');
                        }

                        // Source
                        if (data.content.source.value)
                        {
                            preview.children('.meta').append('<strong>'+data.content.source.label+'&nbsp;:</strong> <a href="'+data.content.source.value+'" target="_blank">'+data.content.source.value+'</a>');
                        }

                        // Buttons container
                        preview.append('<div class="action-buttons"></a>');
                        var actionButtonsContainer = preview.children('.action-buttons');
                        actionButtonsContainer.append('<input type="hidden" value="'+news_item_node_id+'" name="newsToUpdate" />');

                        // Display custom actions depending on the news item status
                        var submitButtons = [];

                        switch (status)
                        {
                            {/literal}
                            case 'published':
                                {foreach $publishedViewButtons as $submitButton}
                                    submitButtons.push({ldelim}
                                        'submit_name': "{$submitButton.submit_name}",
                                        'submit_label': "{$submitButton.submit_label}",
                                        'button_class': "{$submitButton.button_class}"
                                    {rdelim});
                                {/foreach}
                                break;
                            case 'pending':
                                {foreach $pendingViewButtons as $submitButton}
                                    submitButtons.push({ldelim}
                                        'submit_name': "{$submitButton.submit_name}",
                                        'submit_label': "{$submitButton.submit_label}",
                                        'button_class': "{$submitButton.button_class}"
                                    {rdelim});
                                {/foreach}
                                break;
                            case 'rejected':
                                {foreach $rejectedViewButtons as $submitButton}
                                    submitButtons.push({ldelim}
                                        'submit_name': "{$submitButton.submit_name}",
                                        'submit_label': "{$submitButton.submit_label}",
                                        'button_class': "{$submitButton.button_class}"
                                    {rdelim});
                                {/foreach}
                                break;
                            {literal}
                        }

                        // Add submit buttons
                        for (var i=0; i<submitButtons.length; i++)
                        {
                            actionButtonsContainer.append('<input type="submit" name="'+submitButtons[i].submit_name+'Item" class="button '+submitButtons[i].button_class+'" value="'+submitButtons[i].submit_label+'" />');
                        }

                        // Edit link
                        actionButtonsContainer.append('<a class="edit button" data-object-id="'+data.content.contentobject_id+'" href="{/literal}{'/content/edit/'|ezurl('no')}{literal}/'+data.content.contentobject_id+'/f" title="{/literal}{"Modifier"|i18n('extension/ufeed/news')}{literal}">{/literal}{"Modifier"|i18n('extension/ufeed/news')}{literal}</a>');
                    }
                });
            }

            e.preventDefault();
        });

        // Ajax on the preview submit buttons **********************************
        preview.find('input[type="submit"]').live('click', function(e)
        {

            var submitName       = $(this).attr('name');
            var ezJSCoreFunction = '';

            switch (submitName)
            {
                case 'publishNewsItem':
                    ezJSCoreFunction = 'publishNewsItem';
                    break;

                case 'unPublishNewsItem':
                    ezJSCoreFunction = 'unPublishNewsItem';
                    break;

                case 'deleteNewsItem':
                    ezJSCoreFunction = 'deleteNewsItem';
                    break;
            }

            // Item node ID
            var news_item_node_id = preview.find('input[name="newsToUpdate"]').val();

            // Delete the news item in database
            $.ez('ufeedtools::'+ezJSCoreFunction+'::'+news_item_node_id, {}, function(data)
            {
                if (data.content && data.content==true)
                {
                    // Empty the preview container
                    preview.html('');

                    // Remove the item in list
                    $('#newsform .yui-dt table tbody tr#'+news_item_node_id).remove();

                    // If the list is empty
                    if ($('#newsform .yui-dt table tbody').children().length == 0)
                    {
                        // Get the form container
                        var container = $('#newsform').parent();

                        // Remove the form
                        $('#newsform, #js-controls').remove();

                        // Add a message
                        container.append("<p>{/literal}{"Aucune actualité."|i18n('extension/ufeed/news')}{literal}</p>");
                    }
                }
            });

            e.preventDefault();
        });

        // Prevent submit if there are no checked news items *******************

        // Default behavior on form submit is to check that at least one news item is selected
        $('#newsform').addClass('validate');
        $('#purgeNews').click(function(e)
        {
            // Disable the validation
            $('#newsform').removeClass('validate');
        });

        $('#newsform.validate').live('submit', function(e)
        {

            var checkedboxes = $('#newsform .yui-dt table tbody td.check').find(':checked');

            if (checkedboxes.length == 0)
            {
                alert("{/literal}{"Veuillez choisir des actualités"|i18n('extension/ufeed/news')}{literal}");
                e.preventDefault();
            }
        });
    });
    {/literal}
</script>
<div class="break"></div>
