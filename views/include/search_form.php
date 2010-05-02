<?=form_open(TAGGABLE_PATH.AMP."method=tags", array('method' => 'GET'))?>
<?=form_hidden('D', 'cp')?>
<?=form_hidden('C', 'addons_modules')?>
<?=form_hidden('M', 'show_module_cp')?>
<?=form_hidden('module', 'taggable')?>
<?=form_hidden('method', 'tags')?>

<table width="100%">
	<tr>
		<td><label for="order"><?=lang('taggable_search_order')?></label></td>
		<td>
			<?=form_dropdown('order', array(
				'tag_name' => lang('taggable_search_order_alphabet'),
				'entries'  => lang('taggable_search_order_entries'),
				'tag_id'   => lang('taggable_search_order_id')
			), $order)?>
		</td>
	</tr>
	<tr>
		<td><label for="text_search_order"><?=lang('taggable_search_search_by')?></label></td>
		<td>	
			<?=form_dropdown('text_search_order', array(
				'sw' => lang('taggable_search_order_sw'),
				'co' => lang('taggable_search_order_co'),
				'ew' => lang('taggable_search_order_ew')
			), $text_search_order)?>
			<input type="text" name="text_search" value="<?=$text_search?>" style="width:142px" />
		</td>
	</tr>
	<tr>
		<td><label for="entry_count"><?=lang('taggable_search_with')?></label></td>
		<td>
			<?=form_dropdown('entry_count_order', array(
				'mt' => lang('taggable_search_with_mt'),
				'lt' => lang('taggable_search_with_lt'),
				'et' => lang('taggable_search_with_et')
			), $entry_count_order)?>
			<input type="text" name="entry_count" value="<?=$entry_count?>" style="width:142px" />
			<strong><?=lang('taggable_entries')?></strong>
		</td>
	</tr>
	<tr>
		<td><?=form_submit('', lang('taggable_search'), "class='submit'")?>
			<small><a class="submit" href="<?=TAGGABLE_URL.AMP."method=tags"?>"><?=lang('taggable_reset')?></a></small>
		</td>
	</tr>
</table>
<?=form_close()?>