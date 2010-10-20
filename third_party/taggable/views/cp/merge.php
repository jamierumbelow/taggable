<?php if($tags): ?>
	<?=form_open(TAGGABLE_PATH.AMP."method=process_merge_tags")?>
	<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<th style="width:20%"><?=lang('taggable_master_tag')?></th>
			<th><?=lang('taggable_tag')?></th>
			<th><?=lang('taggable_entries')?></th>
			<th><?=lang('taggable_merge_tags')?></th>
		</tr>
	
		<?php foreach($tags as $tag): ?>
			<tr class="<?=alternator('even', 'odd')?>">
				<td><?=form_radio('master_tag', $tag->id)?></td>
				<td><?=$tag->name?></td>
				<td><?=$this->tags->tag_entries_count($tag->id)?></td>
				<td><?=form_checkbox('merge_tags[]', $tag->id)?></td>
			</tr>
		<?php endforeach; ?>
	</table>

	<?=form_submit('', lang('taggable_merge_tags'), 'class="submit"')?>
	<?=form_close()?>

	<script type="text/javascript" charset="utf-8">
		$(".mainTable input:radio").change(function(){
			$(this).parent().parent().parent().find('input:disabled').removeAttr('disabled');
			$(this).parent().parent().find('input:checkbox').attr('disabled', true);
		});
	</script>
<?php endif; ?>