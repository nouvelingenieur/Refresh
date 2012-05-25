<header id="branding">
	<div class="school">
		<a href="?action=go_home">
		 <h1><?=NOM_ECOLE?> Refresh</h1>
		</a>
	</div>

	<div class="refresh">
	 <p>REFRESH <span>L'innovation en marche</span></p>
	</div>
	<div class="clear"></div>
</header>

<form id ="shareidea" class="well shareidea" name="shareidea" action="ajax.php?action=post" method="post">

	<label for=""><?php echo _('Title')?></label>
	<input class="span7" type="text" name="title" value="<?php echo _('A nice title..')?>" onFocus="this.value=''; return false;"/>
	
	<label for="field_two"><?php echo _('Message')?></label>
	<textarea class="span7" name="message" rows="5" onFocus="this.value=''; return false;"><?php echo _('Share something...')?></textarea>

	<span class="help-block">Example block-level help text here.</span>
	
	<label for=""><?php echo _('Category')?></label>

		<?php 
		 include_once("actions.php");
		 
		$tail="";
		
		$result= @mysql_query("SELECT category_id,category_name FROM thread_category");

		if ($result)
			{
	            while($row=mysql_fetch_assoc($result))
	            {
					if ($row["category_id"] == 0)
					{
						$tail.='<option value="'.htmlentities($row["category_id"]).'" selected="selected">'.htmlentities($row["category_name"]).'</option>';
					}
					else
					{
						$tail.='<option value="'.htmlentities($row["category_id"]).'">'.htmlentities($row["category_name"]).'</option>';
					}
	            }
				@mysql_free_result($result);
	        }
			if (empty($tail))
			{
				$tail='<option value="0">Defaut</option>';
				}
		?>

<select name="category"> <?php echo($tail) ?> </select>


	<a class="btn btn-primary" data-toggle="modal" href="#modalFile">
		<i class="icon-picture  icon-white"></i> 
	Add file 
	</a>

	<div class="modal hide" id="modalFile">
		<div class="modal-header">
		    <button class="close" data-dismiss="modal">×</button>
		    <h3>Add a file</h3>
		</div>
		<div class="modal-body">
		    <p>Let's add a file'…</p>
		    <input type="file" name="datafile" size="40">
		</div>
		<div class="modal-footer">
		    <a href="#" class="btn">Close</a>
		    <a href="#" class="btn btn-primary">Save changes</a>
		</div>
	</div>
	
	<label class="checkbox">
		<input type="checkbox" name="anonymization" /><?php echo _('Anonymize')?>
	</label>
	<input type="submit" class="btn" value="submit" id="submit" name="submit" />
	
</form>
<div id="output"></div>

<div id="feed">

<h4 id="feed_title" tabindex="1">
  <?php echo _('Feed')?>
  <span class="label"><a href="#" class="feed-settings"><?php echo _('Settings')?></a></span>
</h4>

<?php display_post() ?>

</div>




