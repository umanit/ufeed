$(document).ready(function()
{
    // JS controls for checkboxes **********************************************
    $('#js-controls').show();
    var jsControls = $('#js-controls ul');
    $('#btn-js-controls').toggle(function(e)
    {
        jsControls.show();
    },
    function(e)
    {
        jsControls.hide();
    });
    jsControls.children('li').click(function(e)
    {
        var checkboxes = $('#newsform .yui-dt table').find(':checkbox');

        switch ($(this).attr('id'))
        {
            case 'ezopt-menu-check':
                checkboxes.prop('checked', true);
                break;

            case 'ezopt-menu-uncheck':
                checkboxes.prop('checked', false);
                break;

            case 'ezopt-menu-toggle':
                checkboxes.each(function()
                {
                    $(this).prop('checked', !$(this).prop('checked'));
                });
                break;
        }

        e.preventDefault();
    });

    // Add the sticky effect to the preview ************************************
    $('#preview').sticky();

    // The layout when JS is disabled uses a single column. With JS, make two columbs using grids
    $('#newsform div.yui-dt').addClass('grid_7');

    // Button to add lines on the feeds form ***********************************
    $('form#feeds input[name="addLines"]').click(function(e)
    {
        // Number of lines
        var lastTR = $('form#feeds table tbody tr').last(),
            line = parseInt(lastTR.attr('data-feedid')) + 1,
            lines = $('form#feeds table tbody tr').length + 1;
            
        $('form#feeds input[name="lines"]').val(lines);

        // Clone the last one
        var tr = lastTR.clone();
        tr.attr('data-feedid', line);

        var fields = ['url', 'title', 'status'];

        for (var i=0; i<fields.length; i++)
        {
            tr.find('td.'+fields[i]+' label').attr('for', 'feed_'+fields[i]+'_'+line);
            tr.find('td.'+fields[i]+' input').attr('id', 'feed_'+fields[i]+'_'+line).attr('name', 'feeds['+line+']['+fields[i]+']').val('');
            tr.find('td.'+fields[i]+' select').attr('id', 'feed_'+fields[i]+'_'+line).attr('name', 'feeds['+line+']['+fields[i]+']').val('');
        }

        $('form#feeds table tbody').append(tr);

        e.preventDefault();
    });

    // Link to open feed *******************************************************
    $('form#feeds .link a').live('click', function(e)
    {
        e.preventDefault();
        
        var url = $(this).closest('tr').find('.url input[type="text"]').val();
        
        if (url)
        {
            window.open(url, '_blank');
        }
    });

    // Ajax button to delete feeds *********************************************
    $('form#feeds input[name^="deleteFeed"]').live('click', function(e) // Tous les inputs dont le nom commence par deleteFeed
    {
        var tr = $(this).closest('tr'),
            feedID = tr.attr('data-feedid');

        // Get the news item from database
        $.ez('ufeedtools::deleteFeeds::'+feedID, {}, function(data)
        {
            if (data.content == true)
            {
                // S'il ne reste plus qu'une ligne
                if ($('form#feeds table tbody tr').length==1)
                {
                    tr.find(':input[name^="feeds"]').val(''); // Tous les input dont le nom commence par feeds
                }
                else
                {
                    tr.remove();
                }

                // Number of lines
                var lines = $('form#feeds table tbody tr').length;
                $('form#feeds input[name="lines"]').val(lines);
            }
        });

        e.preventDefault();
    });
});
