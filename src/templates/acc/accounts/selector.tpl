{include file="admin/_head.tpl" title="Sélectionner un compte" body_id="popup" is_popup=true}

{if isset($grouped_accounts)}

	{foreach from=$grouped_accounts key="group_name" item="accounts"}
		<h2 class="ruler">{$group_name}</h2>

		<table class="list">
			<tbody>
			{foreach from=$accounts item="account"}
				<tr>
					<td>{$account.code}</td>
					<th>{$account.label}</th>
					<td class="desc">{$account.description}</td>
					<td class="actions">
						<button class="icn-btn" value="{$account.code}" data-label="&lt;b&gt;{$account.code}&lt;/b&gt; — {$account.label}" data-icon="&rarr;">Sélectionner</button>
					</td>
				</tr>
			{/foreach}
			</tbody>
		</table>
	{/foreach}

{else}

	<h2 class="ruler"><input type="text" placeholder="Recherche rapide" id="lookup" /></h2>

	<table class="accounts">
		<tbody>
		{foreach from=$accounts item="account"}
			<tr class="account-level-{$account.code|strlen}">
				<td>{$account.code}</td>
				<th>{$account.label}</th>
				<td class="actions">
					<button class="icn-btn" value="{$account.code}" data-label="{$account.code}" data-icon="&rarr;">Sélectionner</button>
				</td>
			</tr>
		{/foreach}
		</tbody>
	</table>

{/if}

{literal}
<script type="text/javascript">

RegExp.escape = function(string) {
  return string.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')
};

function normalizeString(str) {
	return str.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
}

var buttons = document.querySelectorAll('button');

buttons.forEach((e) => {
	e.onclick = () => {
		window.parent.inputListSelected(e.value, e.getAttribute('data-label'));
	};
});

buttons[0].focus();

var rows = document.querySelectorAll('table tr');

rows.forEach((e) => {
	e.classList.add('clickable');
	var l = e.querySelector('td').innerText + ' ' + e.querySelector('th').innerText;
	e.setAttribute('data-search-label', normalizeString(l));

	e.onclick = (evt) => {
		if (evt.target.tagName && evt.target.tagName == 'BUTTON') {
			return;
		}

		e.querySelector('button').click();
	};
});

var q = document.getElementById('lookup');

q.onkeyup = (e) => {
	var query = new RegExp(RegExp.escape(normalizeString(q.value)), 'i');

	rows.forEach((elm) => {
		if (elm.getAttribute('data-search-label').match(query)) {
			elm.style.display = null;
		}
		else {
			elm.style.display = 'none';
		}
	});

};

q.focus();

</script>
{/literal}

{include file="admin/_foot.tpl"}