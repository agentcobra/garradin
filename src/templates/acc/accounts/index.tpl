{include file="admin/_head.tpl" title="%s - Comptes utilisés couramment"|args:$chart.label current="acc/charts"}

<ul class="actions">
	<li class="current"><a href="{$admin_url}acc/accounts/">Comptes utilisés couramment</a></li>
	<li><a href="{$admin_url}acc/charts/">Gestion des plans comptables</a></li>
	<li><a href="{$admin_url}acc/charts/import.php">Importer un plan comptable</a></li>
</ul>

<form method="post" action="{$self_url_no_qs}">
	<fieldset>
		<legend>Ajouter un compte</legend>
		<dl>
			{input type="select" name="group" label="Type de compte" options=$accounts_types}
			{input type="text" name="code" label="Code" required=1 pattern="\w+" maxlength=10 help="Utilisé pour ordonner la liste des comptes."}
			{input type="text" name="label" label="Libellé" required=1}
			{input type="textarea" name="description" label="Description"}
		</dl>
		<p class="submit">
			<input type="submit" value="Créer &rarr;" />
		</p>
	</fieldset>
</form>

{foreach from=$accounts_grouped key="group_name" item="accounts"}
	<h2 class="ruler">{$group_name}</h2>

	<dl class="list">
	{foreach from=$accounts item="account"}
		<dt>{$account.label} <em>({$account.code})</em></dt>
		<dd class="desc">{$account.description}</dd>
		<dd class="actions">
			{button shape="menu" label="Journal" href="acc/transactions/journal.php?id=%d"|args:$account.id}
			{button shape="edit" label="Modifier" href="acc/accounts/edit.php?id=%d"|args:$account.id}
			{button shape="delete" label="Supprimer" href="acc/accounts/delete.php?id=%d"|args:$account.id}
		</dd>
	{/foreach}
	</dl>
{/foreach}


{include file="admin/_foot.tpl"}