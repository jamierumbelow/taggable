<?=form_open(TAGGABLE_PATH.AMP.'method=update_tag')?>
	<?=form_hidden('tag_id', $tag->id)?>
	<table border="0" cellspacing="5" cellpadding="5">
		<?php if ($errors): ?><tr><td></td><td style="color:#FF0000"><?=lang('taggable_errors_tag_name')?></td></tr><?php endif; ?>
		<tr><td><strong><?=lang('taggable_tag_name')?></strong></td><td><?=form_input('tag[name]', $tag->name)?></td></tr>
		<tr><td><strong><?=lang('taggable_tag_description')?></strong></td><td><?=form_textarea('tag[description]', $tag->description)?></td></tr>
		<tr><td><?=form_submit('form_submit', lang('taggable_update_tag'), 'class="submit"')?></td></tr>
	</table>
<?=form_close()?>

</div>
</div>

<div class="contents">
	<div class="heading"><h2><?=lang('taggable_tags_entries_title')?></h2></div>
	<div class="pageContents">
		<?php if($entries): ?>
			<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<th><?=lang('taggable_id')?></th>
					<th><?=lang('taggable_entries_title')?></th>
					<th><?=lang('taggable_entries_url_title')?></th>
					<th><?=lang('taggable')?></th>
					<th><?=lang('taggable_edit')?></th>
				</tr>

				<?php foreach($entries as $entry): ?>
					<tr class="<?=alternator('even', 'odd')?>">
						<td><?=$entry->entry_id?></td>
						<td><a href="<?=BASE.AMP.'D=cp'.AMP.'C=content_publish'.AMP.'M=view_entry'.AMP."channel_id=$entry->channel_id".AMP."entry_id=$entry->entry_id"?>"><?=$entry->title?></a></td>
						<td><?=$entry->url_title?></td>
						<td><?=$this->tags->tags_list_entry($entry->entry_id)?></td>
							<td><a href="<?=BASE.AMP."D=cp".AMP."C=content_publish".AMP."M=entry_form".AMP."channel_id=$entry->channel_id".AMP."entry_id=$entry->entry_id"?>">Edit</a></td>
					</tr>
				<?php endforeach; ?>
			</table>
		<?php else: ?>
			<p><?=lang('taggable_no_entries')?></p>
		<?php endif; ?>
	</div>
</div>