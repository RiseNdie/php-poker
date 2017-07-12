<?php


/**
 * Generated with RoutingCacheManager
 *
 * on 2017-04-24 11:52:35
 */

$app = Yee\Yee::getInstance();

$app->map("/test", "DaController::___index")->via("GET")->name("test.index");
$app->map("/web", "DaController::___server")->via("GET")->name("web.index");

