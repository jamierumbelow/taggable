<p>The Taggable importer allows you to import tags from another install of Taggable. It's very easy to do, all you need to do is upload the export file and run the importer engine, and Taggable will import all your tags into the system.</p>

<?=form_open_multipart(TAGGABLE_PATH.AMP.'method=import_taggable')?>
<table border="0" cellspacing="0" cellpadding="5" style="margin:25px">
	<tr><td><strong>File:</strong></td><td><input type="file" name="file" value="" /></td></tr>
	<tr><td></td><td><?=form_submit('', "Upload", 'class="submit"')?></td></tr>
</table>
<?=form_close()?>