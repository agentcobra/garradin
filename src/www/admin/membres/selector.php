<?php
namespace Garradin;

require_once __DIR__ . '/_inc.php';

header('X-Frame-Options: SAMEORIGIN', true);

$text_query = trim(qg('q'));

$tpl->assign('list', []);

// Recherche simple
if ($text_query !== '')
{
    $tpl->assign('list', (new Membres)->quickSearch($text_query));
}

$tpl->assign('query', $text_query);

$tpl->display('admin/membres/selector.tpl');
