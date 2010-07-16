<?=$this->load->view('include/search_form', array(), TRUE)?>

<?php if($tags): ?>
	<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<th><?=lang('taggable_id')?></th>
			<th><?=lang('taggable_tag')?></th>
			<th><?=lang('taggable_tag_description')?></th>
			<th><?=lang('taggable_entries')?></th>
			<th><?=lang('taggable_delete')?></th>
		</tr>
	
		<?=form_open(TAGGABLE_PATH.AMP."method=delete_tags")?>
			<?php foreach($tags as $tag): ?>
				<tr class="<?=alternator('even', 'odd')?>">
					<td><?=$tag->id?></td>
					<td><a href="<?=TAGGABLE_URL.AMP."method=tag_entries".AMP."tag_id=".$tag->id?>"> <?=$tag->name?></a></td>
					<td><?=$tag->description?></td>
					<td><a href="<?=TAGGABLE_URL.AMP."method=tag_entries".AMP."tag_id=".$tag->id?>"> <?=$this->tags->tag_entries_count($tag->id)?></a></td>
					<td><?=form_checkbox('delete_tags[]', $tag->id)?></td>
				</tr>
			<?php endforeach; ?>
	</table>

	<?=form_submit('', lang('taggable_delete_marked_tags'), 'class="submit"')?>
	<?=form_close()?>
<?php else: ?>
	<p><?=lang('taggable_no_tags')?></p>
<?php endif; ?>

</div></div>

<div class="contents">
	<div style="width: 49%; float: left; margin-right: 1%">
		<div class="heading"><h2><?=lang('taggable_create_tag')?></h2></div>
		<div class="pageContents">
			<?=form_open(TAGGABLE_PATH.AMP."method=create_tag")?>
				<table width="100%" cellpadding="5">
					<?php if ($errors): ?><tr><td></td><td style="color:#FF0000"><?=lang('taggable_errors_tag_name')?></td></tr><?php endif; ?>
					<tr>
						<td><strong><?=lang('taggable_tag')?></strong></td>
						<td style="text-align: left"><?=form_input('tag[name]', "", 'style="width:142px"')?></td>
					</tr>
					<tr>
						<td><strong><?=lang('taggable_tag_description')?></strong></td>
						<td style="text-align: left"><?=form_textarea('tag[description]', "", "style='width: 90%; height: 60px'")?></td>
					</tr>
					<tr><td></td><td><?=form_submit('', lang('taggable_create_tag'), "class='submit'")?></td></tr>
				</table>
			<?=form_close()?>
		</div>
	</div>
	
	<div style="width: 49%; float: left; margin-left: 1%">
		<div class="heading"><h2><?=lang('taggable_browse_tags')?></h2></div>
		<div class="pageContents">
			<?php if($tags_alphabetically): ?>
				<?php foreach($tags_alphabetically as $letter => $count): ?>
					<a href="<?=TAGGABLE_URL.AMP."method=tags".AMP."order=tag_name".AMP."text_search_order=sw".AMP."text_search=".$letter?>" style="padding: 0 5px;"><?=ucwords($letter)?> (<?=$count?>)</a>
				<?php endforeach; ?>
			<?php else: ?>
				<p><?=lang('taggable_no_tags')?></p>
			<?php endif; ?>
		</div>
	</div>
</div>