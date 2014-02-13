<?php

class UFeed extends eZPersistentObject
{
    function __construct($row)
    {
        parent::__construct($row);
    }

    static function definition()
    {
        // Configuration
        $conf = UFeedTools::getConfiguration();

        return array(
                'fields'    => array(
                                     'id' => array(
                                                   'name' => 'id',
                                                   'datatype' => 'string',
                                                   'default' => '',
                                                   'required' => true),
                                     'title' => array(
                                                   'name' => 'title',
                                                   'datatype' => 'string',
                                                   'default' => '',
                                                   'required' => true),
                                     'url' => array(
                                                   'name' => 'url',
                                                   'datatype' => 'string',
                                                   'default' => '',
                                                   'required' => true),
                                     'status' => array(
                                                   'name' => 'status',
                                                   'datatype' => 'string',
                                                   'default' => '',
                                                   'required' => true)
                                 ),
                'keys'       => array('id'),
                'increment_key' => 'id',
                'sort'       => array('url' => 'asc'),
                'class_name' => 'UFeed',
                'name'       => $conf['table_name'] // Table name
        );
    }

    function attribute($attr, $noFunction=false)
    {
        return eZPersistentObject::attribute($attr, $noFunction);
    }

    /**
     * Récupère tous les flux en base de données.
     *
     * @param boolean $asObject
     * @return array|null Tableau d'objets ou de tableaux, ou null en cas d'erreur
     */
    static function fetchAllFeeds($asObject=true)
    {
        $feeds = array();
        $feedsList = eZPersistentObject::fetchObjectList(
                                                   self::definition(),
                                                   $field_filters = null,
                                                   $conds = array(),
                                                   $sorts = array('id' => 'asc'),
                                                   $limit = null,
                                                   $asObject
                                                  );
        foreach ($feedsList as $feed)
        {
            $key = $asObject ? $feed->id : $feed['id'];
            $feeds[$key] = $feed;
        }

        return $feeds;

    }

    /**
     * Récupère en base de données tous les flux ayant le statut 'disabled'.
     *
     * @param boolean $asObject
     * @return array|null Tableau d'objets ou de tableaux, ou null en cas d'erreur
     */
    static function fetchDisabledFeeds($asObject=true)
    {
        return eZPersistentObject::fetchObjectList(
                                                   self::definition(),
                                                   $field_filters = null,
                                                   $conds = array('status' => 'disabled'),
                                                   $sorts = null,
                                                   $limit = null,
                                                   $asObject
                                                  );
    }

    /**
     * Récupère un flux en base de données, depuis son identifiant (clé primaire SQL).
     *
     * @param int $id
     * @param boolean $asObject
     * @return eZPersistentObject|false
     */
    static function getFeedFromID($id, $asObject=true)
    {
        $feed = eZPersistentObject::fetchObject(
                                                self::definition(),
                                                $field_filters = null,
                                                $conds = array('id' => $id),
                                                $asObject,
                                                $grouping = null,
                                                $customFields = null
                                               );
        return $feed;
    }

    /**
     * Récupère un flux en base de données, depuis son URL
     *
     * @param string $url
     * @param boolean $asObject
     * @return eZPersistentObject|false
     */
    static function getFeedFromURL($url, $asObject=true)
    {
        $feed = eZPersistentObject::fetchObject(
                                                self::definition(),
                                                $field_filters = null,
                                                $conds = array('url' => $url),
                                                $asObject,
                                                $grouping = null,
                                                $customFields = null
                                               );
        return $feed;
    }

    /**
     * Crée un flux en base de données, ou le met à jour s'il existe déjà (vérification basée sur son ID).
     *
     * @param array $feed Tableau associatif contenant les clés 'id', 'title', 'url' et 'status'. La liste des statuts disponibles est définie dans ufeed.ini
     * @param boolean $asObject Type de retour
     * @return object|array|false
     */
    static function createOrUpdateFeed($feed, $returnAsObject=true)
    {
        // Return value;
        $row = false;

        // On vérifie que le flux n'existe pas déjà
        $existingFeed = self::getFeedFromID($feed['id']);

        // Si le flux n'existe pas
        if (!$existingFeed)
        {
            $row = new UFeed(
                             array(
                                   'id' => $feed['id'],
                                   'title' => $feed['title'],
                                   'url' => $feed['url'],
                                   'status' => $feed['status']
                                  )
                            );
            $row->store();
        }
        else // Mise à jour
        {
            $updated = self::updateFeed($feed);

            if (!$updated)
            {
                UFeedTools::logError("Feed with URL ".$feed['url']." has not been updated");
            }
        }

        $feed = self::getFeedFromID($feed['id'], $returnAsObject);

        if (!$feed)
        {
            UFeedTools::logError("Feed with URL ".$feed['url']." has not been created");
        }

        return $feed;
    }

    /**
     * Met à jour un flux en base de données.
     *
     * @param array $newFeed Tableau associatif contenant les clés 'id', 'title', 'url' et 'status'. La liste des statuts disponibles est définie dans ufeed.ini
     * @return boolean
     */
    static function updateFeed($newFeed)
    {
        // Return value
        $updated = false;

        eZPersistentObject::updateObjectList(
                                             array(
                                                   'definition' => self::definition(),
                                                   'update_fields' => array(
                                                                            'url' => $newFeed['url'],
                                                                            'title' => $newFeed['title'],
                                                                            'status' => $newFeed['status']
                                                                           ),
                                                   'conditions' =>    array(
                                                                            'id' => $newFeed['id']
                                                                           )
                                                  )
                                            );

        $feed = self::getFeedFromID($newFeed['id'], false);

        if ($feed && $feed['url'] == $newFeed['url'] && $feed['title'] == $newFeed['title'] && $feed['status'] == $newFeed['status'])
        {
            $updated = true;
        }
        else
        {
            UFeedTools::logError("Feed with id #".$newFeed['id']." has not been updated");
        }

        return $updated;
    }

    /**
     * Supprime un flux en base de données.
     *
     * @param int $id Identifiant (clé primaire SQL) de la ligne à supprimer
     * @return boolean
     */
    static function removeFeed($id)
    {
        // Return value
        $removed = false;

        eZPersistentObject::removeObject(
                                         self::definition(),
                                         array('id' => $id)
                                        );

        $feed = self::getFeedFromID($id);

        if (!$feed)
        {
            $removed = true;
        }
        else
        {
            UFeedTools::logError("Feed with id #$id has not been deleted");
        }

        return $removed;
    }
}
