<?php

$http = eZHTTPTool::instance();
$Module = $Params[ 'Module' ];
$Result = array();
$tpl = eZTemplate::factory();



// Messages ********************************************************************
$errors = array();
$warnings = array();
$messages = array();



// Configuration ***************************************************************
$conf = UFeedTools::getConfiguration();


// Suppression des flux RSS ****************************************************
if ($http->hasPostVariable('deleteFeed'))
{
    $feedsToDelete = $http->postVariable('deleteFeed');

    foreach ($feedsToDelete as $feedID => $submitLabel)
    {
        $feed = UFeed::getFeedFromID($feedID);

        if ($feed)
        {
            $deleted = UFeed::removeFeed($feedID);

            if ($deleted)
            {
                if (!empty($feed['title']))
                {
                    $messages[] = ezpI18n::tr( 'extension/ufeed/news', "Le flux %title a été supprimé.", null, array('%title' => '<strong>'.$feed['title'].'</strong>'));
                }
                else
                {
                    $messages[] = ezpI18n::tr( 'extension/ufeed/news', "Le flux %url a été supprimé.", null, array('%url' => '<a href="'.$feed['url'].'" target="_blank">'.$feed['url'].'</a>'));
                }
            }
            else
            {
                UFeedTools::logError("Could not delete feed #$feedID : ".$feed['url']);

                if (!empty($feed['title']))
                {
                    $errors[] = ezpI18n::tr( 'extension/ufeed/news', "Impossible de supprimer le flux %title.", null, array('%title' => '<strong>'.$feed['title'].'</strong>'));
                }
                else
                {
                    $errors[] = ezpI18n::tr('extension/ufeed/news', "Impossible de supprimer le flux %url.", null, array('%url' => '<a href="'.$feed['url'].'" target="_blank">'.$feed['url'].'</a>'));
                }
            }
        }
    }
}


// Récupération des flux RSS ***************************************************
if ($http->hasPostVariable('storeFeeds') && $http->hasPostVariable('feeds'))
{

    // La table existe-t-elle ?
    if (UFeedTools::isUFeedTableInstalled())
    {
        // La classe de contenu "News item" existe-t-elle ?
        if (UFeedTools::isNewsItemClassInstalled())
        {

            // Purge des flux en base de données
            $feeds = UFeed::fetchAllFeeds();
            foreach ($feeds as $feed)
            {
                UFeed::removeFeed($feed->id); // TODO : ne pas supprimer les flux, faire une mise à jour
            }

            // Récupération des valeurs postées
            $feeds = $http->postVariable('feeds');

            // Nombre d'actualités importées
            $newsCount = 0;

            // Création d'un tableau de flux propres, à partir des valeurs postées
            foreach ($feeds as $key => $feed)
            {
                if (!empty($feed['url']))
                {
                    // Flag pour savoir si on doit bloquer ou non l'import après les vérifications portant sur la validité de l'URL et du statut
                    $doImport = true;

                    // On s'assure que le flux est bien au format XML **********
                    
                    // Certains flux RSS sont produits à partir de PHP, on ne peut plus se contenter d'utiliser simplexml_load_file qui fonctionne uniquement pour des fichiers réels.

                    // Récupération du contenu de l'URL, avec CURL
                    $opts = array(
                        'http' => array(
                            'timeout' => 4,
                            'user_agent' => 'UFeed by UmanIT'
                        )
                    );
                    $responseString = UFeedTools::loadUrl($feed['url'], $opts);
                    
                    // Si c'est bien du XML, on garde le flux
                    $feedContent = simplexml_load_string($responseString);
                    
                    if ($feedContent!==false)
                    {

                        // Statut du flux **************************************
                        if (!in_array($feed['status'], $conf['available_status']))
                        {
                            $warnings[] = ezpI18n::tr( 'extension/ufeed/news', "Le statut %status du flux %url n'est pas valide et a été remplacé par %default_status. Les actualités de ce flux n'ont pas été importées. Vous pouvez corriger le statut et relancer l'import.", null, array(
                                                                                                                                                                                                                                                                                                  '%status' => "<strong>".$feed['status']."</strong>",
                                                                                                                                                                                                                                                                                                  '%default_status' => "<strong>".$conf['default_status']."</strong>",
                                                                                                                                                                                                                                                                                                  '%url' => '<a href="'.$feed['url'].'" target="_blank">'.$feed['url'].'</a>'
                                                                                                                                                                                                                                                                                                 )
                                                    );
                            $feed['status'] = $conf['default_status'];

                            // On n'importera pas ce flux
                            $doImport = false;
                        }

                        $feeds[$key] = array(
                                             'id' => $key,
                                             'title' => $feed['title'],
                                             'url' => $feed['url'],
                                             'status' => $feed['status'],
                                             'do_import' => $doImport
                                            );
                    }
                    else
                    {
                        UFeedTools::logWarning("URL ".$feed['url']." is not valid XML, do not save it");

                        if (!empty($feed['title']))
                        {
                            $errors[] = ezpI18n::tr( 'extension/ufeed/news', "Le flux %title n'est pas valide et a été ignoré.", null, array('%title' => '<strong>'.$feed['title'].'</strong>'));
                        }
                        else
                        {
                            $errors[] = ezpI18n::tr( 'extension/ufeed/news', "Le flux %url n'est pas valide et a été ignoré", null, array('%url' => '<a href="'.$feed['url'].'" target="_blank">'.$feed['url'].'</a>'));
                        }

                        // Si ce n'est pas du XML, on supprime le flux
                        unset($feeds[$key]);
                    }
                }
                else
                {
                    // Le flux est vide, on le supprime
                    unset($feeds[$key]);
                }
            }

            // Création des flux
            foreach ($feeds as $key => $feed)
            {
                // On vérifie qu'un flux n'existe pas déjà pour cette URL
                $existingFeed = UFeed::getFeedFromURL($feed['url']);

                if (!$existingFeed)
                {
                    // Création du flux
                    $createdFeed = UFeed::createOrUpdateFeed($feed);

                    if (!$createdFeed)
                    {
                        if (!empty($feed['title']))
                        {
                            $errors[] = ezpI18n::tr( 'extension/ufeed/news', "Impossible de créer le flux %title.", null, array('%title' => '<strong>'.$feed['title'].'</strong>'));
                        }
                        else
                        {
                            $errors[] = ezpI18n::tr('extension/ufeed/news', "Impossible de créer le flux %url.", null, array('%url' => '<a href="'.$feed['url'].'" target="_blank">'.$feed['url'].'</a>'));
                        }
                    }
                    elseif ($feeds[$key]['do_import'])
                    {
                        // Plus besoin du flag do_import
                        unset($feeds[$key]['do_import']);

                        // Import de ses actualités
                        $news = UFeedTools::importNewsFromFeed($feed);
                        $newsCount += count($news);
                    }
                }
                else
                {
                    if (!empty($existingFeed['title']))
                    {
                        $warnings[] = ezpI18n::tr( 'extension/ufeed/news', "Le flux %url existe déjà sous le nom %title", null, array('%url' => '<a href="'.$feed['url'].'" target="_blank">'.$feed['url'].'</a>', '%title' => '<strong>'.$existingFeed['title'].'</strong>'));
                    }
                    else
                    {
                        $warnings[] = ezpI18n::tr('extension/ufeed/news', "Le flux %url existe déjà", null, array('%url' => '<a href="'.$feed['url'].'" target="_blank">'.$feed['url'].'</a>'));
                    }
                }
            }

            if ($newsCount > 1)
            {
                $messages[] = ezpI18n::tr( 'extension/ufeed/news', "%count actualités importées.", null, array('%count' => $newsCount));
            }
            elseif ($newsCount > 0)
            {
                $messages[] = ezpI18n::tr( 'extension/ufeed/news', "1 actualité importée." );
            }
            else
            {
                $messages[] = ezpI18n::tr( 'extension/ufeed/news', "Aucune nouvelle actualité importée." );
            }
        }
        else
        {
            $errors[] = ezpI18n::tr( 'extension/ufeed/news', "La classe actualité n'est pas installée. Veuillez importer le package %package_name.", null, array('%package_name' => "<strong>".$conf['package_name']."</strong>") );
        }
    }
    else
    {
        $errors[] = ezpI18n::tr( 'extension/ufeed/news', "La table %table_name n'existe pas.", null, array('%table_name' => "<strong>".$conf['table_name']."</strong>") );
    }
}


// Récupération des flux depuis la base de données  ****************************
$feeds = UFeed::fetchAllFeeds(false);


// Ajout de nouvelles lignes en cas de JS désactivé ****************************

// Nombre de lignes
$feedsCount = count($feeds);
$lines = $feedsCount + 1; // Toujours une ligne en plus

if ($http->hasPostVariable('addLines'))
{
    $lines = $http->postVariable('lines');
    $lines = $lines + 2; // Ajout de deux lignes à chaque fois
}

// Ajout des lignes
for ($i=1; $i<=$lines-$feedsCount; $i++)
{
    if (!empty($feeds))
    {
        $feeds[] = array('id' => max(array_keys($feeds))+$i); // Le plus grand ID + la valeur de $i
    }
    else
    {
        $feeds[] = array('id' => $i);
    }
}


// Variables du template *******************************************************
$tpl->setVariable( 'feeds', $feeds );
$tpl->setVariable( 'lines', $lines );

// URL pour les classes 'active' du menu
$tpl->setVariable( 'uri', UFeedTools::getCurrentURI($Module) );

// Messages
if (isset($errors)) $tpl->setVariable( 'errors', $errors );
if (isset($warnings)) $tpl->setVariable( 'warnings', $warnings );
if (isset($messages)) $tpl->setVariable( 'messages', $messages );


$Result[ 'path' ] = array(
                          array (
                                 'url' => false,
                                 'text' => ezpI18n::tr( 'extension/ufeed/news', "Gestion des flux" )
                                )
                         );
$Result[ 'left_menu' ] = 'design:ufeed/parts/leftmenu.tpl';
$Result[ 'content' ] = $tpl->fetch( 'design:ufeed/news/feeds.tpl' );
