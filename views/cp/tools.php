<p>The tools section of Taggable allows you to access the Import and Export functionality and run diagnostics tests to aid in support requests.</p>
</div></div>

<div class="contents">
	<div style="width: 49%; float: left; margin-right: 1%">
		<div class="heading"><h2>Import and Export</h2></div>
		<div class="pageContents">
			<p>If you are moving servers, upgrading from a previous version of ExpressionEngine or are moving from a separate CMS, you can use Taggable's Import and Export functionality to export your tags and import from a variety of platforms.</p>
			
			<br />
			
			<h3>Import</h3>
			<?=form_open(TAGGABLE_PATH.AMP.'method=import')?>
			<table border="0" cellspacing="5" cellpadding="5">
				<tr><th>From:</th><td><?=form_dropdown('from', $import)?></td></tr>
				<tr><td></td><td><input type="submit" value="Import" class="submit" /></td></tr>
			</table>
			<?=form_close()?>
			
			<h3>Export</h3>
			<?=form_open(TAGGABLE_PATH.AMP.'method=export')?>
			
			<table>
				<tr><td></td><td><input type="submit" value="Export to JSON" class="submit" /></td></tr>
			</table>
			<?=form_close()?>
		</div>
	</div>

	<div style="width: 49%; float: left; margin-left: 1%">
		<div class="heading"><h2>Diagnostics</h2></div>
		<div class="pageContents">
			<p>The Diagnostics Tool runs a few tests on your install of Taggable, gathering important information about your ExpressionEngine environment. This can be extremely helpful in answering support requests. If you are having an issue with Taggable, run the diagnostics tool and download the results file, which you can then attach to a support ticket.</p>
			
			<br />
			
			<p><a class="submit" href="<?=TAGGABLE_URL.AMP.'method=diagnostics'?>">Run Diagnostics</a></p>
		</div>
	</div>
</div>