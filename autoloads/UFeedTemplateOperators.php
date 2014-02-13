<?php

/**
 * Cette classe liste les opérateurs de template disponibles pour l'extension UFeed.
 *
 */
class UFeedTemplateOperators
{

    var $Operators;

    function UFeedTemplateOperators()
    {
        $this->Operators = array(
                                 'fetch_published_news',
                                 'fetch_pending_news',
                                 'fetch_rejected_news',
                                 'get_news_item'
                                );
    }

    /**
     * Retourne les opérateurs de template.
     *
     * @return array Opérateurs de la classe
     */
    function &operatorList()
    {
        return $this->Operators;
    }

    /**
     * Retourne true pour avertir le moteur de template que la liste des paramètres existe. Voir fonction namedParameterList().
     *
     * @return boolean Renvoie toujours true
     */
    function namedParameterPerOperator()
    {
        return true;
    }

    /**
     * Définit les paramètres disponibles et leurs valeurs par défaut pour chacun des opérateurs de template.
     *
     * @return array Tableau associatif définissant les paramètres pour chaque opérateur de template
     */
    function namedParameterList()
    {
        // Default offset and limit
        $conf = UFeedTools::getConfiguration();
        $defaultOffset = -1;
        $defaultLimit = $conf['pagination_limit'];

        return array(
                     'get_news_item' => array(
                                              'node_id' => array( // Node ID de l'actualité à récupérer
                                                                 'type' => 'integer',
                                                                 'required' => true,
                                                                 'default' => ''
                                                                )
                                             ),
                     'fetch_published_news' => array(
                                              'offset' => array( // Offset pour la pagination
                                                                 'type' => 'integer',
                                                                 'required' => false,
                                                                 'default' => $defaultOffset
                                                                ),
                                              'limit' => array( // Limit pour la pagination
                                                                 'type' => 'integer',
                                                                 'required' => false,
                                                                 'default' => $defaultLimit
                                                                ),
                                              'number_internal_newsitems' => array( // Nombre d'actualités internes désirées
                                                                'type' => 'integer',
                                                                'required' => false,
                                                                'default' => 0
                                                               ),
                                              'number_external_newsitems' => array( // Nombre d'actualités externes désirées
                                                                'type' => 'integer',
                                                                'required' => false,
                                                                'default' => 0
                                                               ),
                                              'balance' => array( // S'il n'est pas possible de trouver assez d'actualités d'un type (externe ou interne), compenser avec des actualités de l'autre type pour toujours retourner (number_internal_newsitems + number_external_newsitems) actualités ?
                                                                'type' => 'boolean',
                                                                'required' => false,
                                                                'default' => true
                                                               ),
                                              'as_node' => array( // Renvoyer les actualités sous forme de noeuds complets ou de tableaux associatifs contenant une version simplifiée du datamap ? Attention, consommateur de ressources.
                                                                'type' => 'boolean',
                                                                'required' => false,
                                                                'default' => false
                                                               )
                                             ),
                      'fetch_pending_news' => array(
                                              'offset' => array( // Offset pour la pagination
                                                                 'type' => 'integer',
                                                                 'required' => false,
                                                                 'default' => $defaultOffset
                                                                ),
                                              'limit' => array( // Limit pour la pagination
                                                                 'type' => 'integer',
                                                                 'required' => false,
                                                                 'default' => $defaultLimit
                                                                ),
                                              'as_node' => array( // Renvoyer les actualités sous forme de noeuds complets ou de tableaux associatifs contenant une version simplifiée du datamap ?
                                                                'type' => 'boolean',
                                                                'required' => false,
                                                                'default' => false
                                                               )
                                             ),
                      'fetch_rejected_news' => array(
                                              'offset' => array( // Offset pour la pagination
                                                                 'type' => 'integer',
                                                                 'required' => false,
                                                                 'default' => $defaultOffset
                                                                ),
                                              'limit' => array( // Limit pour la pagination
                                                                 'type' => 'integer',
                                                                 'required' => false,
                                                                 'default' => $defaultLimit
                                                                ),
                                              'as_node' => array( // Renvoyer les actualités sous forme de noeuds complets ou de tableaux associatifs contenant une version simplifiée du datamap ?
                                                                'type' => 'boolean',
                                                                'required' => false,
                                                                'default' => false
                                                               )
                                             )
                    );
    }

    /**
     * Applique les opérateurs de template aux paramètres passés.
     *
     * @param type $tpl
     * @param type $operatorName
     * @param type $operatorParameters
     * @param type $rootNamespace
     * @param type $currentNamespace
     * @param type $operatorValue
     * @param type $namedParameters
     */
    function modify( &$tpl, &$operatorName, &$operatorParameters, &$rootNamespace, &$currentNamespace, &$operatorValue, &$namedParameters )
    {
        switch( $operatorName )
        {
            case 'fetch_published_news':
                $operatorValue = UFeedTools::fetchPublishedNews($namedParameters['as_node'], $namedParameters['offset'], $namedParameters['limit'], $namedParameters['number_internal_newsitems'], $namedParameters['number_external_newsitems'], $namedParameters['balance']);
                break;

            case 'fetch_pending_news':
                $operatorValue = UFeedTools::fetchPendingNews($namedParameters['as_node'], $namedParameters['offset'], $namedParameters['limit']);
                break;

            case 'fetch_rejected_news':
                $operatorValue = UFeedTools::fetchRejectedNews($namedParameters['as_node'], $namedParameters['offset'], $namedParameters['limit']);
                break;

            case 'get_news_item':
                $operatorValue = UFeedTools::getNewsItem($namedParameters['node_id']);
                break;
        }
    }
}
?>
