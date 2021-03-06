<?php

namespace Garradin;
require_once __DIR__ . '/_inc.php';

if (!empty($_SERVER['QUERY_STRING']))
{
    $page_uri = Wiki::transformTitleToURI(rawurldecode($_SERVER['QUERY_STRING']));
    $page = $wiki->getByURI($page_uri);
}
else
{
    $page = $wiki->getByURI($config->get('accueil_wiki'));
    $page_uri = '';
}

if (!$page)
{
    $tpl->assign('uri', $page_uri);
    $tpl->assign('can_edit', false);
    $tpl->assign('can_read', true);
}
else
{
    $membres = new Membres;
    $tpl->assign('can_read', $wiki->canReadPage($page->droit_lecture));
    $tpl->assign('can_edit', $wiki->canWritePage($page->droit_ecriture));
    $tpl->assign('children', $wiki->getList($page_uri == '' ? 0 : $page->id, true));
    $tpl->assign('has_public_children', $wiki->hasChildren($page_uri == '' ? 0 : $page->id, true));
    $tpl->assign('breadcrumbs', $wiki->listBackBreadCrumbs($page->id));
    $tpl->assign('auteur', $page->contenu ? $membres->getNom($page->contenu->id_auteur) : null);

    $images = Fichiers::listLinkedFiles(Fichiers::LIEN_WIKI, $page->id, true);

    if ($images && !empty($page->contenu->chiffrement))
    {
        $images = Fichiers::filterFilesUsedInText($images, $page->contenu->contenu);
    }

    $fichiers = Fichiers::listLinkedFiles(Fichiers::LIEN_WIKI, $page->id, false);

    if ($fichiers && !empty($page->contenu->chiffrement))
    {
        $fichiers = Fichiers::filterFilesUsedInText($fichiers, $page->contenu->contenu);
    }

    $tpl->assign('images', $images);
    $tpl->assign('fichiers', $fichiers);
}

$tpl->assign('page', $page);

$tpl->assign('custom_js', ['wiki_gallery.js']);

$tpl->display('admin/wiki/page.tpl');
