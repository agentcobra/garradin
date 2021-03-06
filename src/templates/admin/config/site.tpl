{include file="admin/_head.tpl" title="Configuration — Site public" current="config"}

{form_errors}

{include file="admin/config/_menu.tpl" current="site"}

{if $config.desactiver_site}
    <div class="block alert">
        <h3>Site public désactivé</h3>
        <p>Le site public est désactivé, les visiteurs sont redirigés automatiquement vers la page de connexion.</p>
        <form method="post" action="{$self_url}">
            <p class="submit">
                {csrf_field key="config_site"}
                {button type="submit" name="activer_site" label="Réactiver le site public" shape="right" class="main"}
            </p>
        </form>
    </div>
{elseif isset($edit)}
    <form method="post" action="{$self_url}">
        <h3>Éditer un squelette</h3>

        {if $ok}
        <p class="block confirm">
            Modifications enregistrées.
        </p>
        {/if}

        <fieldset class="skelEdit">
            <legend>{$edit.file}</legend>
            <p>
                <textarea name="content" cols="90" rows="50" id="f_content">{form_field name=content data=$edit}</textarea>
            </p>
        </fieldset>

        <p class="submit">
            {csrf_field key=$csrf_key}
            {button type="submit" name="save" label="Enregistrer" shape="right" class="main"}
        </p>

    </form>

    <script type="text/javascript">
    var doc_url = "{$admin_url}doc/skel/";
    var skel_list = {$sources|escape:json};
    var skel_current = "{$edit.file|escape:'js'}";
    g.script("scripts/skel_editor.js");
    </script>
{else}

    <fieldset>
        <legend>Activation du site public</legend>
        <dl>
            <dt>
                <form method="post" action="{$self_url}">
                    <p class="submit">
                        {button type="submit" name="desactiver_site" label="Désactiver le site public" shape="right" class="main"}
                        {csrf_field key="config_site"}
                    </p>
                </form>
            </dt>
            <dd class="help">
                En désactivant le site public, les visiteurs seront automatiquement redirigés vers la page de connexion.<br />
                Cette option est utile si vous avez déjà un site web et ne souhaitez pas utiliser la fonctionnalité site web de Garradin.
            </dd>
        </dl>
    </fieldset>

    <fieldset>
        <legend>Gérer le contenu du site public</legend>
        <p class="help">
            Le contenu affiché sur le site est celui présent dans le wiki, il suffit de sélectionner «&nbsp;Cette page est visible sur le site de l'association&nbsp;» à l'édition d'une page wiki. Il est également possible de <a href="{$admin_url}wiki/creer.php?public">créer une nouvelle page publique sur le wiki</a>.
        </p>
    </fieldset>

    <form method="post" action="{$self_url}">
    <fieldset class="templatesList">
        <legend>Squelettes du site</legend>

        {if $reset_ok}
        <p class="block confirm">
            Réinitialisation effectuée. Les squelettes ont été remis à jour
        </p>
        {/if}

        <table class="list">
            <thead>
                <tr>
                    <td class="check"></td>
                    <th>Fichier</th>
                    <td>Dernière modification</td>
                    <td></td>
                </tr>
            </thead>
            <tbody>
            {foreach from=$sources key="source" item="local"}
                <tr>
                    <td>{if $local && $local.dist}<input type="checkbox" name="select[]" value="{$source}" id="f_source_{$iteration}" /><label for="f_source_{$iteration}"></label>{/if}</td>
                    <th><a href="{$admin_url}config/site.php?edit={$source|escape:'url'}" title="Éditer">{$source}</a></th>
                    <td>{if $local}{$local.mtime|date_fr:'d/m/Y à H:i:s'}{else}<em>(fichier non modifié)</em>{/if}</td>
                    <td class="actions">
                        {linkbutton shape="edit" label="Éditer" href="?edit=%s"|args:$source}
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>

        <p class="actions">
            Pour les squelettes sélectionnés&nbsp;:
            <input type="submit" name="reset" value="Réinitialiser" onclick="return confirm('Effacer toute modification locale et restaurer les squelettes d\'installation ?');" />
            {csrf_field key="squelettes"}
        </p>
    </fieldset>
    </form>
{/if}

{include file="admin/_foot.tpl"}