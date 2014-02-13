<?php

    $Module = array(
                    'name' => 'UFeed news module',
                    'variable_params' => true
                   );

    $ViewList = array();

    // Views *******************************************************************

    $ViewList['feeds'] = array(
                              'script' => 'feeds.php',
                              'functions' => array('feeds'),
                              'params' => array(),
                              'unordered_params' => array(),
                              'single_post_actions' => array(
                                                             'PublishButton' => 'Publish',
                                                             'CancelButton' => 'Cancel',
                                                             'CustomActionButton' => 'CustomAction'
                                                            ),
                              'post_action_parameters' => array(),
                              'default_navigation_part' => 'ufeednavigationpart'
    );

    $ViewList['list'] = array(
                              'script' => 'list.php',
                              'functions' => array('list'),
                              'params' => array('type'),
                              'unordered_params' => array(),
                              'single_post_actions' => array(
                                                             'PublishButton' => 'Publish',
                                                             'CancelButton' => 'Cancel',
                                                             'CustomActionButton' => 'CustomAction'
                                                            ),
                              'post_action_parameters' => array(),
                              'default_navigation_part' => 'ufeednavigationpart'
    );

    // Fonctions (droits d'accès) **********************************************

    $FunctionList = array();
    $FunctionList['feeds'] = array();
    $FunctionList['list'] = array();

?>
