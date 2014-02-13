<?php
$eZTemplateOperatorArray = array();

$eZTemplateOperatorArray[] = array(
                                   'script' => 'extension/ufeed/autoloads/UFeedTemplateOperators.php',
                                   'class' => 'UFeedTemplateOperators',
                                   'operator_names' => array(
                                                             'fetch_published_news',
                                                             'fetch_pending_news',
                                                             'fetch_rejected_news',
                                                             'get_news_item'
                                                            )
                                  );
?>
