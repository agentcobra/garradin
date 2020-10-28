{include file="admin/_head.tpl" title="Graphiques" current="acc"}

{include file="acc/reports/_header.tpl" current="graphs"}

<section class="year-infos">
	<section class="graphs">
		{foreach from=$graphs key="url" item="label"}
		<figure>
			<img src="{$url|args:$year.id}" alt="" />
			<figcaption>{$label}</figcaption>
		</figure>
		{/foreach}
	</section>
</section>

{include file="admin/_foot.tpl"}