<?php

    set_time_limit( 0 );
    $cli = eZCLI::instance();

    // Fetching feeds
    $feeds = UFeed::fetchAllFeeds(false);

    // For each feed
    foreach ($feeds as $feed)
    {
        // Import its items
        UFeedTools::importNewsFromFeed($feed, $cli);
    }

    // Memory peak usage
    $memoryPeak = memory_get_peak_usage(true);
    if ($memoryPeak>1000000000)
    {
        $div=1000000000;
        $suffix='Gb';
    }
    elseif ($memoryPeak>1000000)
    {
        $div=1000000;
        $suffix='Mb';
    }
    elseif ($memoryPeak>1000)
    {
        $div=1000;
        $suffix='Kb';
    }
    $memoryPeak = trim(sprintf("%10.2f", $memoryPeak/$div)).$suffix;

    // Logs
    $cli->notice("Memory peak usage : $memoryPeak");
    UFeedTools::logNotice("Memory peak usage : $memoryPeak");

?>
