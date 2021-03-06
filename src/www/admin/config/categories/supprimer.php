<?php
namespace Garradin;

require_once __DIR__ . '/../_inc.php';

$cats = new Membres\Categories;

qv(['id' => 'required|numeric']);

$id = (int) qg('id');

$cat = $cats->get($id);

if (!$cat)
{
    throw new UserException("Cette catégorie n'existe pas.");
}

if ($cat->id == $user->id_categorie)
{
    throw new UserException("Vous ne pouvez pas supprimer votre catégorie.");
}

if (f('delete'))
{
    $form->check('delete_cat_' . $id);

    if (!$form->hasErrors())
    {
        try {
            $cats->remove($id);
            Utils::redirect(ADMIN_URL . 'config/categories/');
        }
        catch (UserException $e)
        {
            $form->addError($e->getMessage());
        }
    }
}

$tpl->assign('cat', $cat);

$tpl->display('admin/config/categories/supprimer.tpl');
