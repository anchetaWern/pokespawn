<?php
$app->get('/', 'HomeController:index');
$app->get('/search', 'HomeController:search');
$app->post('/save-location', 'HomeController:saveLocation');
$app->post('/fetch', 'HomeController:fetch');
