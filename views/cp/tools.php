<p><?=lang('taggable_tools_message')?></p>
</div></div>

<div class="contents">
	<div style="width: 49%; float: left; margin-right: 1%">
		<div class="heading"><h2><?=lang('taggable_import_and_export')?></h2></div>
		<div class="pageContents">
			<p><?=lang('taggable_import_and_export_message')?></p>
			
			<br />
			
			<h3><?=lang('taggable_import')?></h3>
			<?=form_open(TAGGABLE_PATH.AMP.'method=import')?>
			<table border="0" cellspacing="5" cellpadding="5">
				<tr><th><?=lang('taggable_from')?>:</th><td><?=form_dropdown('from', $import)?></td></tr>
				<tr><td></td><td><input type="submit" value="<?=lang('taggable_import')?>" class="submit" /></td></tr>
			</table>
			<?=form_close()?>
			
			<h3><?=lang('taggable_export')?></h3>
			<?=form_open(TAGGABLE_PATH.AMP.'method=export')?>
			
			<table>
				<tr><td></td><td><input type="submit" value="<?=lang('taggable_export_to_json')?>" class="submit" /></td></tr>
			</table>
			<?=form_close()?>
		</div>
	</div>

	<div style="width: 49%; float: left; margin-left: 1%">
		<div class="heading"><h2><?=lang('taggable_diagnostics')?></h2></div>
		<div class="pageContents">
			<p><?=lang('taggable_diagnostics_message')?></p>
			
			<br />
			
			<p><a class="submit" href="<?=TAGGABLE_URL.AMP.'method=diagnostics'?>"><?=lang('taggable_run_diagnostics')?></a></p>
		</div>
	</div>
</div>