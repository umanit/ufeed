<?php

/* STATUT DES ACTUALITES
 *                      _______________________________________________
 *                     |    Publiées   |   En attente  |    Rejetées   |
 *  ___________________|_______________|_______________|_______________|
 * | Attribut 'active' |       1       |       1       |       0       |
 * |___________________|_______________|_______________|_______________|
 * |    Noeud publié   |      oui      |      non      |      non      |
 * |___________________|_______________|_______________|_______________|
 */

class UFeedTools extends ezjscServerFunctionsJs
{

    /**
     * Teste l'existence de la classe de contenu "News item" dont l'identifiant est défini dans ufeed.ini.
     *
     * @return boolean
     */
    public static function isNewsItemClassInstalled()
    {
        // Configuration
        $conf = self::getConfiguration();

        $class = eZContentClass::fetchByIdentifier($conf['class_name']);

        return ($class) ? true : false;
    }

    /**
     * Teste l'existence de la table 'ufeed'.
     *
     * @return boolean
     */
    public static function isUFeedTableInstalled()
    {
        // Configuration
        $conf = self:: getConfiguration();

        // Database
        $database = eZDB::instance();

        // Recherche de la table
        $table  = $database->arrayQuery("SHOW TABLES LIKE '".$conf['table_name']."';");

        return (empty($table)) ? false : true;
    }

    /**
     * Ecrit un message dans les logs.
     *
     * @param string $message
     */
    public static function logNotice($message)
    {
        // Configuration
        $conf = self::getConfiguration();

        eZLog::write($message, 'notice.log', $conf['logs_dir']);
    }

    /**
     * Ecrit un message d'avertissement dans les logs.
     *
     * @param string $message
     */
    public static function logWarning($message)
    {
        // Configuration
        $conf = self::getConfiguration();

        eZLog::write($message, 'warning.log', $conf['logs_dir']);
    }

    /**
     * Ecrit un message d'erreur dans les logs.
     *
     * @param string $message
     */
    public static function logError($message)
    {
        // Configuration
        $conf = self::getConfiguration();

        eZLog::write($message, 'error.log', $conf['logs_dir']);
    }

    /**
     * Renvoie les paramètres de configuration, définis dans ufeed.ini.
     *
     * @param array $override Facultatif. Tableau associatif de clés à surcharger : 'class_name', 'container_node_id', 'available status', 'default_status', 'summary_max_length', 'deactivation_is_retroactive', 'date_format'
     * @return array Tableau associatif, de la même forme que le tableau d'override que la fonction accepte en paramètre
     */
    public static function getConfiguration($override = array())
    {
        // INI file
        $ini = eZINI::instance('ufeed.ini');

        // Si on a un PWD (mode cli/console), on l'utilise. Sinon, on utilise le DOCUMENT_ROOT
        $documentRoot = isset($_SERVER['PWD']) ? $_SERVER['PWD'] : $_SERVER['DOCUMENT_ROOT'];

        if (empty($documentRoot))
        {
            self::logError("Could not determine the document root");
        }

        // On s'assure que le document root se termine bien par un slash
        $documentRoot = substr($documentRoot, -1)=='/' ? $documentRoot : $documentRoot.'/';

        $conf = array(
                      'class_name' =>                   $ini->variable('Classes', 'NewsItem'),
                      'container_node_id' =>            $ini->variable('Containers', 'News'),
                      'available_status' =>             $ini->variable('Feeds', 'AvailableStatus'),
                      'default_status' =>               $ini->variable('Feeds', 'DefaultStatus'),
                      'summary_max_length' =>           (int) $ini->variable('Formats', 'SummaryMaxLength') > 0 ? $ini->variable('Formats', 'SummaryMaxLength') : 75,
                      'deactivation_is_retroactive' =>  $ini->variable('Feeds', 'DeactivationIsRetroactive'),
                      'date_format' =>                  $ini->variable('Formats', 'DateFormat'),
                      'pagination_limit' =>             $ini->variable('Pagination', 'ItemsPerPage'),
                      'document_root' =>                $documentRoot,
                      'logs_dir' =>                     $ini->variable('Path', 'LogDir'),
                      'images_dir' =>                   $ini->variable('Path', 'ImagesDir'),
                      'package_name' =>                 $ini->variable('Install', 'Package'),
                      'table_name' =>                   $ini->variable('Install', 'Table'),
                     );

        // Override INI settings
        foreach ($override as $key => $value)
        {
            if (isset($conf[$key]))
            {
                $conf[$key] = $value;
            }
        }

        return $conf;
    }

    /**
     * Teste si une actualité est de type interne.
     * La vérification est basée sur le non remplissage de l'attribut 'feed'.
     *
     * @param eZContentObjectTreeNode $newsItemNode
     * @return boolean
     */
    public static function isInternalNewsItem($newsItemNode)
    {
        $dataMap = $newsItemNode->dataMap();
        $isInternal = $dataMap['feed']->hasContent() < 1 ? true : false;

        return $isInternal;
    }

    /**
     * Teste si une actualité est de type externe.
     * La vérification est basée sur le remplissage de l'attribut 'feed'.
     *
     * @param eZContentObjectTreeNode $newsItemNode
     * @return boolean
     */
    public static function isExternalNewsItem($newsItemNode)
    {
        $dataMap = $newsItemNode->dataMap();
        $isExternal = $dataMap['feed']->hasContent() > 0 ? true : false;

        return $isExternal;
    }

    /**
     * Supprime toutes les actualités du conteneur.
     * Non utilisé, sauf pour le développement.
     *
     * @param int $containerNodeID Facultatif
     * @param boolean $ignoreVisibility Facultatif
     */
    public static function removeAllNews($containerNodeID=false, $ignoreVisibility=true)
    {
        // Configuration
        $conf = self::getConfiguration();

        $containerNodeID = $containerNodeID ? $containerNodeID : $conf['container_node_id'];

        $params = array(
                        'parent_node_id' => $containerNodeID,
                        'class_filter_type' => 'include',
                        'class_filter_array' => array( $conf['class_name'] ),
                        'ignore_visibility' => $ignoreVisibility
                       );

        $news = eZFunctionHandler::execute('content', 'list', $params);

        foreach ($news as $item)
        {
            eZContentObjectTreeNode::removeNode($item->NodeID);
        }
    }

    /**
     * Récupère l'attribut 'date' de la classe actualité.
     * Cette fonction est utilisée dans les fetches, pour faire un sort_by.
     *
     * @return eZContentClassAttribute|false
     */
    public static function getDateAttributeFromNewsItemClass()
    {
        // Valeur de retour
        $dateAttribute = false;

        // Configuration
        $conf = self::getConfiguration();

        // Récupération de l'identifiant de l'attribut 'date' de la classe actualité, pour l'utiliser dans le sort_by du fetch
        $newsItemClassID = eZContentClass::fetchByIdentifier($conf['class_name'])->ID;
        $dateAttributes = eZContentClassAttribute::fetchFilteredList(array('identifier' => 'date'));

        // Pour tous les attributs de type date trouvés, quelque soit leur classe
        foreach ($dateAttributes as $dateAttribute)
        {
            // Si cet attribut est celui de notre classe
            if ($dateAttribute->ContentClassID == $newsItemClassID)
            {
                // Alors le dernier $dateAttribute instancié est le bon, on peut le renvoyer
                break;
            }
        }

        return $dateAttribute;
    }

    /**
     * Récupère les objets actualité qui se trouvent dans le conteneur des actualités.
     * Note : offset et limit ne sont pas gérés ici.
     *
     * @param array $attributeFilter Si laissé vide, aucun filtre n'est appliqué
     * @param boolean $ignoreVisibility False par défaut
     * @param array $additionalParams Paramètres de fetch additionnels
     * @return array Tableau de eZContentObjectTreeNode
     */
    public static function fetchNews($attributeFilter=array(), $ignoreVisibility=false, $additionalParams=array())
    {
        // Configuration
        $conf = self::getConfiguration();

        // Récupération de l'attribut 'date' de la classe actualité, pour l'utiliser dans le sort_by du fetch
        $dateAttribute = self::getDateAttributeFromNewsItemClass();

        // Get news items
        $params = array(
                        'parent_node_id' => $conf['container_node_id'],
                        'class_filter_type' => 'include',
                        'class_filter_array' => array( $conf['class_name'] ),
                        'ignore_visibility' => $ignoreVisibility,
                        'sort_by' => array('attribute', false, $dateAttribute->ID)
                       );

        // Filtres
        if (!empty($attributeFilter))
        {
            $params['attribute_filter'] = $attributeFilter;
        }
        if (!empty($additionalParams))
        {
            foreach ($additionalParams as $key => $additionalParam)
            {
                $params[$key] = $additionalParam;
            }
        }

        $news = eZFunctionHandler::execute('content', 'list', $params);

        if (!empty($news))
        {
            // Si la désactivation des flux est rétro-active, on va cacher les actualités qui proviennent des flux désactivés
            if ($conf['deactivation_is_retroactive'])
            {
                // Récupération des flux désactivés
                $disabledFeeds = UFeed::fetchDisabledFeeds(false);

                foreach ($disabledFeeds as $key => $disabledFeed)
                {
                    $disabledFeeds[$key] = $disabledFeed['url'];
                }

                // On les filtre à la main
                foreach ($news as $key => $item)
                {
                    $itemDatamap = $item->dataMap();
                    $feed = $itemDatamap['feed']->content();

                    if (in_array($feed, $disabledFeeds))
                    {
                        unset($news[$key]);
                    }
                }
            }
        }

        return $news;
    }

    /**
     * Récupère les news qui ont la source donnée.
     *
     * @param string $source
     * @param int $offset Si -1, aucun offset n'est appliqué
     * @param int $limit Si -1, aucune limite n'est appliquée
     * @return array Tableau de eZContentObjectTreeNode
     */
    public static function fetchNewsFromSource($source, $offset=-1, $limit=-1)
    {
        // Configuration
        $conf = self::getConfiguration();

        $attributeFilter = array( array( $conf['class_name'].'/source', '=', $source ) );
        $news = self::fetchNews($attributeFilter, true);

        // On applique offset et limite, en dernier
        $news = self::applyOffsetAndLimit($news, $offset, $limit);

        return $news;
    }

    /**
     * Récupère les actualités publiées.
     * Actualités publiées, attribut active à 0 ou 1.
     *
     * @param boolean $asNode Doit on renvoyer des noeuds complets ou simplement une version simplifiée des datamaps ?
     * @param int $offset Si -1, aucun offset n'est appliqué
     * @param int $limit Si -1, aucune limite n'est appliquée
     * @param int $nbInternalNewsItems Nombre d'actualités internes désirées.
     * @param int $nbExternalNewsItems Nombre d'actualités externes désirées.
     * @param boolean $balance S'il n'est pas possible de trouver assez d'actualités d'un type (externe ou interne), compenser avec des actualités de l'autre type pour toujours retourner ($nbInternalNewsItems + $nbExternalNewsItems) actualités ?
     * @return array Tableau de eZContentObjectTreeNode ou tableau associatif des datamaps simplifiés, selon la valeur du paramètre $asNode
     */
    public static function fetchPublishedNews($asNode=true, $offset=-1, $limit=-1, $nbInternalNewsItems=0, $nbExternalNewsItems=0, $balance=true)
    {
        // Return value
        $news = array();

        // Configuration
        $conf = self::getConfiguration();

        // Filtre sous toutes les actualités actives
        $attributeFilter = array(array( $conf['class_name'].'/active', '=', '1' ));

        // Si on doit filtrer sur le nombre d'actualités internes ou externes et qu'on doit équilibrer le résultat, $limit doit être mis à -1 (aucune limite)
        if ($nbInternalNewsItems > 0 && $nbExternalNewsItems > 0 && $balance)
        {
            $limit = -1;
        }

        // Récupération des news
        $unFilteredNews = self::fetchNews($attributeFilter, false);
        $unFilteredNews = self::filterNewsByVisibility($unFilteredNews);

        // Si on doit filtrer sur le nombre d'actualités internes ou externes
        if ($nbInternalNewsItems > 0 || $nbExternalNewsItems > 0)
        {
            foreach ($unFilteredNews as $key => $item)
            {
                // Clé du tableau pour faciliter le tri
                $keyForSorting = $item->ContentObject->Published.'_'.$item->ContentObjectID; // La date de publication seule ne suffit par car plusieurs actualités ont parfois la même

                // S'il s'agit d'une actualité externe
                if (self::isExternalNewsItem($item))
                {
                    // Tant qu'on a pas encore le compte
                    if ($nbExternalNewsItems > 0)
                    {
                        $news[$keyForSorting] = $item;
                        unset($unFilteredNews[$key]); // Actualité traitée, on la supprime
                        $nbExternalNewsItems--;
                    }
                }
                else // Actualité interne
                {
                    // Tant qu'on a pas encore le compte
                    if ($nbInternalNewsItems > 0)
                    {
                        $news[$keyForSorting] = $item;
                        unset($unFilteredNews[$key]); // Actualité traitée, on la supprime
                        $nbInternalNewsItems--;
                    }
                }
            }

            // Si on a toujours pas le compte d'actualités externes ou internes, et qu'on doit équilibrer
            if ($balance && ($nbInternalNewsItems > 0 || $nbExternalNewsItems > 0))
            {
                // Nombre d'actualités restant à récupérer
                $remainder = $nbInternalNewsItems + $nbExternalNewsItems;

                // On refait une boucle pour compenser avec des actus de l'autre type, de façon à avoir le total désiré
                foreach ($unFilteredNews as $key => $item)
                {
                    // Clé du tableau pour faciliter le tri
                    $keyForSorting = $item->ContentObject->Published.'_'.$item->ContentObjectID; // La date de publication seule ne suffit par car plusieurs actualités ont parfois la même

                    if ($remainder > 0)
                    {
                        $news[$keyForSorting] = $item;
                        unset($unFilteredNews[$key]); // Actualité traitée, on la supprime
                        $remainder--;
                    }
                }
            }

            // Tri du tableau par ordre décroissant
            krsort($news);

            // On redonne aux clés leurs valeurs numériques
            $news = array_values($news);
        }
        else
        {
            $news = $unFilteredNews;
        }

        // On applique offset et limite, en dernier
        $news = self::applyOffsetAndLimit($news, $offset, $limit);

        // Doit on renvoyer des noeuds complets ou simplement une version simplifiée des datamaps ?
        if (!$asNode)
        {
            // Cette méthode consomme beaucoup de ressources
            $news = self::getNewsItems($news);
        }

        return $news;
    }

    /**
     * Récupère les actualités en attente de validation.
     * Actualités cachées, attribut 'active' à 1.
     *
     * @param boolean $asNode Doit on renvoyer des noeuds complets ou simplement une version simplifiée des datamaps ?
     * @param int $offset Si -1, aucun offset n'est appliqué
     * @param int $limit Si -1, aucune limite n'est appliquée
     * @return array Tableau de eZContentObjectTreeNode
     */
    public static function fetchPendingNews($asNode=true, $offset=-1, $limit=-1)
    {
        // Configuration
        $conf = self::getConfiguration();

        // Les actualtiés en attente de validation ont l'attribut 'active' à 1
        $attributeFilter = array(array( $conf['class_name'].'/active', '=', '1' ));

        // Et sont cachées
        $news = self::fetchHiddenNews($attributeFilter, $asNode, $offset, $limit);

        return $news;
    }

    /**
     * Récupère les actualités dont la validation a été rejetée.
     * Actualités cachées, attribut 'active' à 0.
     *
     * @param boolean $asNode Doit on renvoyer des noeuds complets ou simplement une version simplifiée des datamaps ?
     * @param int $offset Si -1, aucun offset n'est appliqué
     * @param int $limit Si -1, aucune limite n'est appliquée
     * @return array Tableau de eZContentObjectTreeNode
     */
    public static function fetchRejectedNews($asNode=true, $offset=-1, $limit=-1)
    {
        // Configuration
        $conf = self::getConfiguration();

        // Les actualités rejetées ont l'attribut 'active' à 0
        $attributeFilter = array(array( $conf['class_name'].'/active', '=', '0' ));

        // Et sont cachées
        $news = self::fetchHiddenNews($attributeFilter, $asNode, $offset, $limit);

        return $news;
    }

    /**
     * Récupère les actualités qui sont cachées : en attente de validation et rejetées.
     *
     * @param array $attributeFilter Si laissé vide, aucun filtre n'est appliqué
     * @param boolean $asNode Doit on renvoyer des noeuds complets ou simplement une version simplifiée des datamaps ?
     * @param int $offset Si -1, aucun offset n'est appliqué
     * @param int $limit Si -1, aucune limite n'est appliquée
     * @return array Tableau de eZContentObjectTreeNode
     */
    public static function fetchHiddenNews($attributeFilter=array(), $asNode=true, $offset=-1, $limit=-1)
    {
        $news = self::fetchNews($attributeFilter, true);

        // On ne garde que les news cachées
        $news = self::filterNewsByVisibility($news, false);

        // On applique offset et limite, en dernier
        $news = self::applyOffsetAndLimit($news, $offset, $limit);

        // Doit on renvoyer des noeuds complets ou simplement une version simplifiée des datamaps ?
        if (!$asNode)
        {
            $news = self::getNewsItems($news);
        }

        return $news;
    }

    /**
     * Filtre une liste de noeuds, selon leur visibilité : visibles ou cachés.
     *
     * @param array $news Tableau de noeuds
     * @param boolean $visible Si true, ne renvoie que les noeuds visibles. Sinon, ne renvoie que les noeuds cachés
     * @return array Tableau de eZContentObjectTreeNode
     */
    public static function filterNewsByVisibility($news, $visible=true)
    {
        /* Filtre des actualités cachées et issues de flux désactivés
         * On est en admin_site, le paramètre "ignore_visibility" n'a pas d'effet
         * car il n'est pas possible de mettre "ShowHiddenNodes" à false dans site.ini, car cela prendrait effet dans tout le siteaccess.
         */
        foreach ($news as $key => $item)
        {
            // Si on ne veut garder que les actualités visibles
            if ($visible)
            {
                if ($item->IsHidden > 0) // On supprime celles qui sont cachées
                {
                    unset($news[$key]);
                }
            }
            else // Sinon, on ne veut garder que les actualités cachées
            {
                if ($item->IsHidden < 1) // On supprime celles qui ne sont pas cachées
                {
                    unset($news[$key]);
                }
            }
        }

        return $news;
    }

    /**
     * Applique manuellement limite et offset sur une liste d'actualités.
     * On le fait en dehors du fetch de départ, car cela nous évite d'avoir
     * à coder des extended attribute filters.
     *
     * @param array $news
     * @param int $offset Si -1, aucun offset n'est appliqué
     * @param int $limit Si -1, aucune limite n'est appliquée
     */
    public static function applyOffsetAndLimit($news, $offset=-1, $limit=-1)
    {
        // Si on a un offset
        if ($offset > -1)
        {
            $i = 0;
            foreach ($news as $key => $newsItem)
            {
                if ($i < $offset)
                {
                    unset($news[$key]);
                }
                else
                {
                    // $i est devenu égal à l'offset, on peut sortir
                    break;
                }
                $i++;
            }

            // On redonne aux clés leurs valeurs numériques
            $news = array_values($news);
        }

        // Si on a une limite
        if ($limit > -1)
        {
            $i = 0;
            foreach ($news as $key => $newsItem)
            {
                if ($i >= $limit)
                {
                    unset($news[$key]);
                }
                $i++;
            }

            // On redonne aux clés leurs valeurs numériques
            $news = array_values($news);
        }

        return $news;
    }

    /**
     * Récupère une version simplifié du datamap d'une actualité.
     * Fonction appelée pour les opérateurs de template et par Ajax dans list.tpl
     *
     * @param int $newsItemNodeID
     * @return array Le datamap de l'actu, sous forme de tableau associatif
     */
    public static function getNewsItem($newsItemNodeID)
    {
        // Configuration
        $conf = self::getConfiguration();
        $locale = eZLocale::currentLocaleCode();

        $newsItemNode = eZContentObjectTreeNode::fetch($newsItemNodeID);
        $newsItemDatamap = $newsItemNode->dataMap();
        $newsItemClassAttributes = eZContentClass::fetchByIdentifier($conf['class_name'])->dataMap();

        $newsItem = array();

        foreach ($newsItemDatamap as $attributeName => $attributeValue)
        {
            $newsItem[$attributeName]['label'] = $newsItemClassAttributes[$attributeName]->NameList->NameList[$locale];

            $attributeContent = $attributeValue->content();

            switch ($attributeValue->DataTypeString)
            {
                case 'ezxmltext':
                    $newsItem[$attributeName]['value'] = $attributeContent->attribute('output')->outputText();
                    break;

                case 'ezdatetime':
                    $newsItem[$attributeName]['value'] = date($conf['date_format'], $attributeContent->DateTime);
                    break;

                case 'ezimage':

                    $imageAliases = $attributeContent->attributes();

                    foreach ($imageAliases as $imageAlias)
                    {
                        $newsItem[$attributeName]['value'][$imageAlias] = $attributeContent->attribute($imageAlias);
                    }

                    break;

                default:

                    if (is_string($attributeContent))
                    {
                        $newsItem[$attributeName]['value'] = $attributeContent;
                    }
                    break;

            }

            $newsItem['url_alias'] = array(
                                           'label' => 'Permalien',
                                           'value' => $newsItemNode->urlAlias()
                                          );
            $newsItem['contentobject_id'] = $newsItemNode->ContentObject->ID;
        }

        return $newsItem;
    }

    /**
     * Récupère une actu depuis son node ID.
     * Fonction appelée pour les opérateurs de template.
     *
     * @param array $news Tableau de noeuds
     * @return array Tableau contenant le datamap des actus, sous forme de tableaux associatifs
     */
    public static function getNewsItems($news)
    {
        foreach ($news as $key => $item)
        {
            $news[$key] = self::getNewsItem($item->NodeID);
        }

        return $news;
    }

    /**
     * Récupère un flux depuis le noeud d'une actualité.
     *
     * @param eZContentObjectTreeNode $newsItemNode
     * @param boolean $asObject
     * @return array|object
     */
    public static function getFeedFromNewsItem($newsItemNode, $asObject=false)
    {
        $datamap = $newsItemNode->dataMap();
        $feedURL = $datamap['feed']->content();
        $feed = UFeed::getFeedFromURL($feedURL, $asObject);

        return $feed;
    }

    /**
     * Purge (masque et désactive) une actu depuis son node ID.
     *
     * @param int $newsItemNodeID
     */
    public static function purgeNewsItem($newsItemNodeID)
    {
        self::logNotice("Purging news item with node ID #$newsItemNodeID");

        eZContentObjectTreeNode::removeNode($newsItemNodeID);
    }

    /**
     * Supprime des flux.
     *
     * @param array $ids Identifiants (clé primaire SQL) des lignes à supprimer
     * @return boolean
     */
    public static function deleteFeeds($ids)
    {
        $oneOrMoreErrorsOccured = false;

        foreach ($ids as $id)
        {
            $deleted = UFeed::removeFeed($id);

            if (!$deleted)
            {
                $oneOrMoreErrorsOccured = true;
            }
        }

        return $oneOrMoreErrorsOccured ? false : true;
    }

    /**
     * Publie une actu depuis son node ID.
     * Actualité publiée : visible et attribut 'active' à 1.
     *
     * @param int $newsItemNodeID
     * @param boolean
     */
    public static function publishNewsItem($newsItemNodeID)
    {
        self::logNotice("Publishing news item with node ID #$newsItemNodeID");

        $newsItemNode = eZContentObjectTreeNode::fetch($newsItemNodeID);

        if ($newsItemNode)
        {
            $params = array(
                            'attributes' => array(
                                                  'active' => '1'
                                                 )
                           );
            eZContentFunctions::updateAndPublishObject( $newsItemNode->object(), $params );

            // Si le noeud est caché, on le révèle
            if ($newsItemNode->IsHidden > 0)
            {
                eZContentObjectTreeNode::unhideSubTree($newsItemNode);
            }

            // Mise à jour de la variable
            $newsItemNode = eZContentObjectTreeNode::fetch($newsItemNodeID);
        }

        return ($newsItemNode && $newsItemNode->IsHidden < 1) ? true : false;
    }

    /**
     * Dépublie une actu depuis son node ID, pour la mettre dans la liste des actualités en attente.
     * Actualité en attente : cachée et attribut 'active' à 1.
     *
     * @param int $newsItemNodeID
     * @param boolean
     */
    public static function unPublishNewsItem($newsItemNodeID)
    {
        self::logNotice("Unpublishing news item with node ID #$newsItemNodeID");

        $newsItemNode = eZContentObjectTreeNode::fetch($newsItemNodeID);

        if ($newsItemNode)
        {
            $params = array(
                            'attributes' => array(
                                                  'active'  => '1'
                                                 )
                           );
            eZContentFunctions::updateAndPublishObject( $newsItemNode->object(), $params );

            // Si le noeud est visible, on le cache
            if ($newsItemNode->IsHidden < 1)
            {
                eZContentObjectTreeNode::hideSubTree($newsItemNode);
            }

            // Mise à jour de la variable
            $newsItemNode = eZContentObjectTreeNode::fetch($newsItemNodeID);
        }

        return ($newsItemNode && $newsItemNode->IsHidden > 0) ? true : false;
    }

    /**
     * Rejete une actu depuis son node ID.
     *
     * @param int $newsItemNodeID
     * @return boolean
     */
    public static function deleteNewsItem($newsItemNodeID)
    {
        self::logNotice("Deleting (hiding) news item with node ID #$newsItemNodeID");

        // Récupération de l'actu
        $newsItemNode = eZContentObjectTreeNode::fetch($newsItemNodeID);

        if ($newsItemNode)
        {
            $params = array(
                            'attributes' => array(
                                                  'active'  => '0'
                                                 )
                           );
            eZContentFunctions::updateAndPublishObject( $newsItemNode->object(), $params );

            // Si le noeud est visible, on le cache
            if ($newsItemNode->IsHidden < 1)
            {
                eZContentObjectTreeNode::hideSubTree($newsItemNode);
            }

            // Mise à jour de la variable
            $newsItemNode = eZContentObjectTreeNode::fetch($newsItemNodeID);
        }

        return ($newsItemNode && $newsItemNode->IsHidden) > 0 ? true : false;
    }

    /**
     * Purge des actualités rejetées périmées, pour ne plus qu'elles apparaissent dans aucune des listes.
     *
     * @return int Nombre d'actualités purgées
     */
    public static function purgeNews()
    {
        $ini = eZINI::instance('ufeed.ini');
        $purgedNewsCount = 0;

        // Age maximal des actualités
        $maxAgeInDays    = $ini->variable('Feeds', 'PurgeRejectedNewsItemsOlderThan');
        $maxAgeInSeconds = $maxAgeInDays * 24 * 3600;

        $newsToPurge = self::fetchRejectedNews();

        foreach ($newsToPurge as $newsItem)
        {
            $ageInSeconds = time() - $newsItem->object()->Published;

            // Si l'âge maximal est atteint
            if ($ageInSeconds >= $maxAgeInSeconds)
            {
                // On "supprime" cette actualité
                self::purgeNewsItem($newsItem->NodeID);
                $purgedNewsCount++;
            }
        }

        return $purgedNewsCount;
    }
    
    /**
     * Charge une URL avec CURL.
     * 
     * @param string $url L'URL à charger
     * @param array $opts Les options à passer à CURL, facultatives. Sont gérées $opts['http']['timeout'] et $opts['http']['user_agent']
     * @return string
     */
    public function loadUrl($url, $opts = array())
    {
        $ch = curl_init($url);
        
        if (!empty($opts))
        {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $opts['http']['timeout']);
            curl_setopt($ch, CURLOPT_TIMEOUT, $opts['http']['timeout']);
            curl_setopt($ch, CURLOPT_USERAGENT, $opts['http']['user_agent']);
        }
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);

        $output = self::curl_exec_follow($ch);

        curl_close($ch);
        
        return $output;
    }

    /**
     * Exécute une ressource CURL tout en suivant les redirections HTTP.
     * 
     * @param curl resource $ch
     * @param int $redirects Nombre maximal de redirections HTTP avant abandon
     * @param boolean $curloptHeader Paramètre CURLOPT_HEADER de CURL
     * @return type
     */
    public function curl_exec_follow(&$ch, $redirects = 20, $curloptHeader = false)
    {
        $data = '';
        
        if ((!ini_get('open_basedir') && !ini_get('safe_mode')) || $redirects < 1)
        {
            curl_setopt($ch, CURLOPT_HEADER, $curloptHeader);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $redirects > 0);
            curl_setopt($ch, CURLOPT_MAXREDIRS, $redirects);
            
            return curl_exec($ch);
        }
        else
        {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, false);

            do
            {
                $data = curl_exec($ch);
                
                if (curl_errno($ch))
                {
                    break;
                }
                
                $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

                // 301 Moved Permanently, 302 Found, 303 See Other, 307 Temporary Redirect
                if ($code != 301 && $code != 302 && $code!=303 && $code!=307)
                {
                    break;
                }
                
                $header_start = strpos($data, "\r\n")+2;
                $headers = substr($data, $header_start, strpos($data, "\r\n\r\n", $header_start)+2-$header_start);
                
                if (!preg_match("!\r\n(?:Location|location|URI): *(.*?) *\r\n!", $headers, $matches))
                {
                    break;
                }
                
                curl_setopt($ch, CURLOPT_URL, $matches[1]);
                
            } while (--$redirects);
            
            if (!$redirects)
            {
                self::logError("Too many redirects. When following redirects, libcurl hit the maximum amount.");
            }
            
            if (!$curloptHeader)
            {
                $data = substr($data, strpos($data, "\r\n\r\n")+4);
            }
        }
        
        return $data;
    }

    /**
     * Publie des actualités à partir d'un flux RSS.
     *
     * @param array $feed Tableau associatif contenant les clés 'status' et 'url'
     * @param eZCli $cli Instance d'eZCli, à définir uniquement lors d'un appel depuis une console (cronjob)
     * @return array Actualités créées, dans un tableau de eZContentObjectTreeNode
     */
    public static function importNewsFromFeed($feed, eZCli $cli=null)
    {
        $news = array();

        // Log message
        $log = '';

        // Si le flux n'est pas inactif, on crée les actualités ****************
        if ($feed['status']!='disabled')
        {
            $log = "Importing items from feed [".$feed['url']."]";
            if ($cli) $cli->notice($log);

            // Certains flux RSS sont produits à partir de PHP, on ne peut plus se contenter d'utiliser simplexml_load_file qui fonctionne uniquement pour des fichiers réels.
            $opts = array(
                'http' => array(
                    'timeout' => 4,
                    'user_agent' => 'UFeed by UmanIT'
                )
            );
            
            // Récupération du contenu de l'URL, avec CURL
            $responseString = self::loadUrl($feed['url'], $opts);
            
            // On s'assure à nouveau qu'il s'agit bien de XML
            $rss = simplexml_load_string($responseString);
            
            if ($rss!==false)
            {
                $conf = self::getConfiguration();

                foreach ($rss->channel->item as $item)
                {
                    $source = $item->link->__toString();

                    // On vérifie avec l'URL source de l'actu qu'elle n'existe pas déjà dans eZ
                    $existingNews = self::fetchNewsFromSource($source);

                    // Si l'actu n'existe pas déjà
                    if (empty($existingNews))
                    {
                        // Récupération du contenu de la news
                        $text = $item->description->__toString();
                        $text = strip_tags($text); // Suppression du HTML

                        // Transformation en bloc de texte ezxml
                        $parser = new eZSimplifiedXMLInputParser( );
                        $parser->setParseLineBreaks( true );
                        $xmlDocument = $parser->process( $text );
                        $xhtml = eZXMLTextType::domString( $xmlDocument );

                        $summary = self::shorten($text, $conf['summary_max_length']);

                        $params = array();
                        $params['parent_node_id']   = $conf['container_node_id'];
                        $params['creator_id']       = false;
                        $params['class_identifier'] = $conf['class_name'];
                        $params['attributes']       = array(
                                                            'name'    => $item->title->__toString(),
                                                            'title'   => $item->title->__toString(),
                                                            'summary' => $summary,
                                                            'text'    => $xhtml,
                                                            'date'    => strtotime($item->pubDate->__toString()),
                                                            'source'  => $source,
                                                            'feed'    => $feed['url'], // Le remplissage de cet attribut détermine qu'il s'agit d'une actualité externe
                                                            'active'  => $feed['status']=='rejected' ? '0' : '1'
                                                           );

                        // Si l'item a une pièce jointe
                        if (isset($item->enclosure) && empty($item->enclosure))
                        {

                            // Type de la pièce jointe
                            $enclosureMIMEType = (string) $item->enclosure->attributes()->type;
                            $enclosureType = explode('/', $enclosureMIMEType);
                            $enclosureType = $enclosureType[0];

                            // S'il s'agit d'une image
                            if ($enclosureType=='image')
                            {
                                $imageImported = false;

                                // Enregistrement de l'image dans le var
                                $remoteImageURL = (string) $item->enclosure->attributes()->url;
                                $storageDir = $conf['document_root'].(substr($conf['images_dir'], -1)=='/' ? $conf['images_dir'] : $conf['images_dir'].'/'); // Ending slash is required
                                $params['storage_dir'] = $storageDir;

                                $imageFileName = basename($remoteImageURL);
                                $params['attributes']['image'] = $imageFileName;
                                $localImagePath = $storageDir.$imageFileName;

                                // Si l'image n'existe pas déjà
                                if (!file_exists($localImagePath))
                                {

                                    // Si on arrive à ouvrir l'image distante en mode lecture
                                    if (@fopen($remoteImageURL, 'r') )
                                    {
                                        $buffer = eZHTTPTool::sendHTTPRequest( $remoteImageURL, 80, false, "eZ Publish", false );
                                        $header = false;
                                        $body = false;

                                        if ( eZHTTPTool::parseHTTPResponse( $buffer, $header, $body ) ) // Modifie les trois variables (via les pointeurs)
                                        {
                                            $imageImported = eZFile::create($imageFileName, $storageDir, $body, $atomic=true);

                                            if (!$imageImported)
                                            {
                                                self::logNotice("Could not create image file : ".$storageDir.$imageFileName);
                                            }
                                        }
                                    }
                                    else
                                    {
                                        self::logNotice("Can't read remote image : ".$remoteImageURL);
                                    }
                                }
                                else
                                {
                                    self::logNotice("Image already exists : ".$localImagePath);
                                }

                                if (!$imageImported)
                                {
                                    $log = "Could not import image enclosure from item [$source]";
                                    if ($cli) $cli->error($log);
                                    self::logError($log);
                                }
                            }
                        }

                        $newsItem = eZContentFunctions::createAndPublishObject( $params );

                        if ($newsItem)
                        {

                            // Si le statut est manual ou disabled
                            if ($feed['status']!='auto')
                            {
                                $newsItemNode = $newsItem->mainNode();

                                // L'actualité doit être cachée
                                eZContentObjectTreeNode::hideSubTree($newsItemNode);

                                // Mise à jour de la variable
                                $newsItem = eZContentObject::fetchByNodeID($newsItemNode->NodeID);
                            }

                            $log = "Item [$source] successfully imported";

                            if ($cli) $cli->notice($log);
                            self::logNotice($log);

                            $news[] = $newsItem;
                        }
                        else
                        {
                            $log = "Item [$source] could not be imported";
                            if ($cli) $cli->error($log);
                            self::logError($log);
                        }
                    }
                    else
                    {
                        $log = "Item [$source] already exists, skipping";
                        if ($cli) $cli->notice($log);
                        self::logNotice($log);
                    }
                }
            }
            else
            {
                $log = "Feed [".$feed['url']."] is not valid XML, skipping";
                if ($cli) $cli->error($log);
                self::logError($log);
            }
        }
        else
        {
            $log = "Feed [".$feed['url']."] is disabled, skipping";
            if ($cli) $cli->notice($log);
            self::logNotice($log);
        }

        $log = count($news)." news items have been imported";
        if ($cli) $cli->notice($log);
        self::logNotice($log);

        return $news;
    }

    /**
     * Retourne l'URL courante du de la vue, de la forme : /module/vue/paramètres/.../...
     *
     * @param eZModule $module
     * @param boolean $withParameters Si true, renvoie les paramètres en plus du module et de la vue
     * @return string
     */
    public static function getCurrentURI(eZModule $module, $withParameters=true)
    {
        $currentURI = '/' . $module->currentModule() . '/' . $module->currentView();

        if ($withParameters)
        {
            foreach ($module->ViewParameters as $viewParameter)
            {
                $currentURI .= '/'.$viewParameter;
            }
        }

        return $currentURI;
    }

    /**
     * Tronque une chaîne de caractères, pour un résumé par exemple.
     * Ne tient pas compte des espaces.
     *
     * @param string $string La chaîne de caractères à tronquer
     * @param int $maxLength La longueur de la chaîne à produire
     * @param string $suffix Un suffixe à appliquer, des points de suspension par défaut
     * @return string
     */
    public static function shorten($string, $maxLength, $suffix='...')
    {
        $strlenFunc = function_exists( 'mb_strlen' ) ? 'mb_strlen' : 'strlen';
        $substrFunc = function_exists( 'mb_substr' ) ? 'mb_substr' : 'substr';

        if ( $strlenFunc( $string ) > $maxLength )
        {
            $length = $strlenFunc( $string );

            $chop = $maxLength - $strlenFunc( $suffix );
            $string = $substrFunc( $string, 0, $chop );
            $string = trim( $string );

            if ( $length > $chop )
            {
                $string = $string.$suffix;
            }
        }

        return $string;
    }
}

?>
