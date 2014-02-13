<?php

$http = eZHTTPTool::instance();
$Module = $Params[ 'Module' ];
$Result = array();
$tpl = eZTemplate::factory();

// Configuration ***************************************************************
$ini = eZINI::instance('ufeed.ini');



// Messages ********************************************************************
$errors = array();
$warnings = array();
$messages = array();



// Récupération des statuts à mettre à jour ************************************

// Arrays of node IDs
$newsToPublish   = array();
$newsToUnPublish = array();
$newsToDelete    = array();

/* $_POST['newsToUpdate'] peut-être :
     - soit un tableau de node ID d'actualités (from the list) ;
     - soit un unique node ID d'actualité (preview)
*/
if ($http->hasPostVariable('newsToUpdate'))
{
    $newsToUpdate = $http->postVariable('newsToUpdate');

    // Actualités à publier
    if ($http->hasPostVariable('publishNews')) // From the list
    {
        $newsToPublish = $newsToUpdate; // Array of items
    }
    elseif ($http->hasPostVariable('publishNewsItem')) // From the preview
    {
        $newsToPublish = array($newsToUpdate); // Single item
    }

    // Actualités à dépublier : jamais utilisé car non implémenté dans les templates
    if ($http->hasPostVariable('unPublishNews')) // From the list
    {
        $newsToUnPublish = $newsToUpdate; // Array of items
    }
    elseif ($http->hasPostVariable('unPublishNewsItem')) // From the preview
    {
        $newsToUnPublish = array($newsToUpdate); // Single item
    }

    // Actualités à rejeter
    if ($http->hasPostVariable('deleteNews')) // From the list
    {
        $newsToDelete = $newsToUpdate; // Array of items
    }
    elseif ($http->hasPostVariable('deleteNewsItem')) // From the preview
    {
        $newsToDelete = array($newsToUpdate); // Single item
    }
}

// Mise à jour des statuts *****************************************************

// Validation des actus
foreach ($newsToPublish as $newsItemNodeID)
{
    UFeedTools::publishNewsItem($newsItemNodeID);
}

// Rejet des actus
foreach ($newsToUnPublish as $newsItemNodeID)
{
    UFeedTools::unPublishNewsItem($newsItemNodeID);
}

// Suppression (masquage) des actus
foreach ($newsToDelete as $newsItemNodeID)
{
    UFeedTools::deleteNewsItem($newsItemNodeID);
}

// Purge des actualités ********************************************************
if ($http->hasPostVariable('purgeNews'))
{
    // Purge de toutes les actualités rejetées antérieures à # jours (paramétrable en fichier INI)
    $purgedNewsCount = UFeedTools::purgeNews();

    // Age maximal des actualités
    $maxAgeInDays    = $ini->variable('Feeds', 'PurgeRejectedNewsItemsOlderThan');
    
    if ($purgedNewsCount>1)
    {
        if ($maxAgeInDays==0)
        {
            $messages[] = ezpI18n::tr( 'extension/ufeed/news', "%count actualités ont été supprimées", null, array('%count' => $purgedNewsCount) );
        }
        else
        {
            $messages[] = ezpI18n::tr( 'extension/ufeed/news', "%count actualités de plus de %days jours ont été supprimées", null, array('%count' => $purgedNewsCount, '%days' => $maxAgeInDays) );
        }
    }
    elseif ($purgedNewsCount==1)
    {
        if ($maxAgeInDays==0)
        {
            $messages[] = ezpI18n::tr( 'extension/ufeed/news', "1 actualité a été supprimée" );
        }
        else
        {
            $messages[] = ezpI18n::tr( 'extension/ufeed/news', "1 actualité de plus de %days jours a été supprimée", null, array('%days' => $maxAgeInDays) );
        }
    }
    else
    {
        if ($maxAgeInDays==0)
        {
            $messages[] = ezpI18n::tr( 'extension/ufeed/news', "Aucune actualité à supprimer" );
        }
        else
        {
            $messages[] = ezpI18n::tr( 'extension/ufeed/news', "Aucune actualité n'a plus de %days jours", null, array('%days' => $maxAgeInDays) );
        }
    }
}


// Récupération des actualités *************************************************

$conf = UFeedTools::getConfiguration();

// Pagination : offset et limite
$offset = isset($Params['UserParameters']['offset']) ? $Params['UserParameters']['offset'] : 0;
$limit  = $conf['pagination_limit'];

$news = array(
              'pending' => array(
                                 'items' => UFeedTools::fetchPendingNews(true, $offset, $limit), // Quelque soit le type d'actualité demandé, on fetche toujours les actualités en attente pour afficher le compteur
                                 'count' => count(UFeedTools::fetchPendingNews())
                                )
);
switch ($Params['type'])
{
    case 'rejected':
        $type = 'rejected';
        $title = ezpI18n::tr( 'extension/ufeed/news', "Actualités rejetées" );
        $news[$type]['items'] = UFeedTools::fetchRejectedNews(true, $offset, $limit);
        $news[$type]['count'] = count(UFeedTools::fetchRejectedNews());
        break;

    case 'pending':
        $type = 'pending';
        $title = ezpI18n::tr( 'extension/ufeed/news', "Actualités en attente" );
        // $news[$type] has already been fetched
        break;

    default:
        $type = 'published';
        $title = ezpI18n::tr( 'extension/ufeed/news', "Actualités publiées" );
        $news[$type]['items'] = UFeedTools::fetchPublishedNews(true, $offset, $limit);
        $news[$type]['count'] = count(UFeedTools::fetchPublishedNews());
        break;
}

// Flux classés par actualité
$feeds = array();

foreach ($news as $type => $newsItems)
{
    if (!empty($newsItems['items']))
    {
        foreach ($newsItems['items'] as $newsItemNode)
        {
            $feed = UFeedTools::getFeedFromNewsItem($newsItemNode);
            $feeds[$newsItemNode->NodeID] = $feed ? $feed : false;
        }
    }
}


// Variables du template *******************************************************
$tpl->setVariable( 'type', $type );
$tpl->setVariable( 'title', $title );
$tpl->setVariable( 'news', $news );
$tpl->setVariable( 'feeds', $feeds );
$tpl->setVariable( 'view_parameters', $Params['UserParameters'] ); // Pour la pagination

// URL pour les classes 'active' du menu
$tpl->setVariable( 'uri', UFeedTools::getCurrentURI($Module) );

// Messages
if (isset($errors)) $tpl->setVariable( 'errors', $errors );
if (isset($warnings)) $tpl->setVariable( 'warnings', $warnings );
if (isset($messages)) $tpl->setVariable( 'messages', $messages );


$Result[ 'path' ] = array(
                          array (
                                 'url' => false,
                                 'text' => ezpI18n::tr( 'extension/ufeed/news', "Actualités" )
                                ),
                          array (
                                 'url' => false,
                                 'text' => $title
                                )
                         );
$Result[ 'left_menu' ] = 'design:ufeed/parts/leftmenu.tpl';
$Result[ 'content' ] = $tpl->fetch( 'design:ufeed/news/list.tpl' );
