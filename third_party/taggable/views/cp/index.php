<img src="<?=TAGGABLE_THEME_URL.'images/taggable-small.png'?>" alt="Taggable" style="float:left;margin:5px;margin-right:15px" />
<p style="padding:10px"><?=lang('taggable_welcome_message')?></p>
<br />
</div></div>

<div class="contents">
	<div style="width: 49%; float: left; margin-right: 1%">
		<div class="heading"><h2><?=lang('taggable_stats_title')?></h2></div>
		<div class="pageContents">
			<table width="100%" cellspacing="5" cellpadding="5">
				<?php foreach($stats as $title => $stat): ?>
					<tr>
						<td><strong><?=$title?></strong></td>
						<td style="text-align: right"><strong><?=$stat?></strong></td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
	</div>

	<div style="width: 49%; float: left; margin-left: 1%">
		<div class="heading"><h2><?=lang('taggable_browse_tags')?></h2></div>
		<div class="pageContents">
			<?php if($tags_alphabetically): ?>
				<?php foreach($tags_alphabetically as $letter => $count): ?>
					<a href="<?=TAGGABLE_URL.AMP."method=tags".AMP."order=name".AMP."text_search_order=sw".AMP."text_search=".$letter?>" style="padding: 0 5px;"><?=ucwords($letter)?> (<?=$count?>)</a>
				<?php endforeach; ?>
			<?php else: ?>
				<p><?=lang('taggable_no_tags')?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
