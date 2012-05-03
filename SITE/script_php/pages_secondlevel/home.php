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

<form id ="shareidea" class="well shareidea" name="shareidea" action="?action=post" method="post">

	<label for=""><?php echo _('Title')?></label>
	<input class="span7" type="text" name="title" value="<?php echo _('A nice title..')?>" onFocus="this.value=''; return false;"/>
	
	<label for="field_two"><?php echo _('Message')?></label>
	<textarea class="span7" name="message" rows="5" onFocus="this.value=''; return false;"><?php echo _('Share something...')?></textarea>

	<span class="help-block">Example block-level help text here.</span>
	
	<label for=""><?php echo _('Keywords')?></label>
	<input type="text" id="category" name="category" value="<?php echo _('Some tags')?>" size="50" onFocus="this.value=''; return false;" />

	<a class="btn btn-primary" href=""><i class="icon-picture  icon-white"></i> Add</a>
	<input type="submit" class="btn" value="submit" id="submit" name="submit" />
	<label class="checkbox">
		<input type="checkbox" name="anonymization" /><?php echo _('Anonymize')?>
	</label>

</form>
<div id="output"></div>

<div id="feed">

<h4 id="feed_title" tabindex="1">
  <?php echo _('Feed')?>
  <span class="label"><a href="#" class="feed-settings"><?php echo _('Settings')?></a></span>
</h4>
<?php display_post() ?>

<article class="feed_item row" id="1">

    <div class="span2">
	   <img src="img/n_modere.png" alt="Non mod&eacute;r&eacute;" class="imgtitlenews" />
	   <p><img src="img/placeholder_100x100.gif" alt="icon"/></p>
	   <a href="?action=vote_post&amp;order=1&amp;thread_id=1#1">
		<img src="img/pale_votepro.png" alt="+1" class="imgvote" />
	   </a>
	   <a href="?action=vote_post&amp;order=-1&amp;thread_id=1#1">
		<img src="img/pale_voteneg.png" alt="-1" class="imgvote" />
	   </a>
     </div>

     <div class="span6">
       <header class="ym-clearfix">
         <div class="ym-g20 ym-gl">
	  <div class="ym-gbox">
             <img src="img/placeholder_50x50.gif" alt="icon" class="avatar bordered"/>
          </div>
         </div>
         <div class="ym-g80 ym-gr">
          <div class="ym-gbox">
          <p class="meta">
           <small>
            <?php echo _('Posted by ')?><a href="#">John Doe</a>
            <?php echo _(' with tags : ')?><i><a href="#">Bla</a>, <a href="#">BlaBla</a>, <a href="#">BlaBlaBla</a></i>
            </small>
          </p>
          <h3>This is a title</h3>
          <section class="sns"><!-- AddThis Button BEGIN -->
		<div class="addthis_toolbox addthis_default_style ">
		<a class="addthis_button_preferred_1"></a>
		<a class="addthis_button_preferred_2"></a>
		<a class="addthis_button_preferred_3"></a>
		</div>
		<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-4f3e39a4223675c7"></script>
          </section><!-- AddThis Button END -->
         </div><!--gbox -->
        </div><!--ymg-80 -->
       </header>
       
       <div class="content">
         <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aliquam nec libero lectus. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Curabitur non nulla augue, eget molestie lectus. Ut bibendum quam vel dolor fringilla aliquam. </p>
       </div>
       
       <footer class="utils">
        <p>
        <small>
         <a href="?action=vote_post&amp;order=1&amp;thread_id=1#1"><?php echo _('Upvote')?></a>
          - 
         <a href="?action=vote_post&amp;order=-1&amp;thread_id=1#1"><?php echo _('Downvote')?></a>
          - 
         <a href="?action=unrollcomment&amp;order=1&amp;thread_id=1#1">0&nbsp;commentaires</a>
         -
         <time datetime="2009-11-13">13 th Fev, 2012</time>
         |
         <a href="?action=remove_post&amp;thread_id=1"><?php echo _('Delete')?></a>
          - 
         <a href="?action=edit_post&amp;thread_id=1"><?php echo _('Edit')?></a>
          - 
         <a href="?action=anonymization&amp;order=0&amp;thread_id=1#1"><?php echo _('Hide my name')?></a>
        </small>
       </p>
       </footer>
       
       <section class="comments">

	<article class="comment ym-clearfix">
	<div class="ym-g20 ym-gl">
	  <header class="ym-gbox">
	    <img src="img/placeholder_50x50.gif" alt="icon" class="avatar bordered"/>
	   </header>
	 </div>
	    
<div class="ym-g80 ym-gl">
 <div class="ym-gbox">

            <p>(comment text here)</p>
            <footer class="utils">
	       <p>
	         <small>
	       <a href="?action=vote_post&amp;order=1&amp;thread_id=1#1"><?php echo _('Upvote')?></a>
	          - 
	       <a href="?action=vote_post&amp;order=-1&amp;thread_id=1#1"><?php echo _('Downvote')?></a>
	          - 
	       <a href="?action=unrollcomment&amp;order=1&amp;thread_id=1#1">0&nbsp;<?php echo _('Comments')?></a>
	         -
	       <time pubdate datetime="YYYY-MM--DDTHH:MM:SS-05:00">(Time)</time>
	         |
	       <a href="?action=remove_post&amp;thread_id=1"><?php echo _('Delete')?></a>
	          - 
	       <a href="?action=edit_post&amp;thread_id=1"><?php echo _('Edit')?></a>
	          - 
	       <a href="?action=anonymization&amp;order=0&amp;thread_id=1#1"><?php echo _('Hide my name')?></a>
	        </small>
	    </footer>
</div>
</div>
	</article>
	

	<form action="?action=comment_post&amp;thread_id=1#1" method="post" class="ym-form">
          <div class="ym-fbox-text">
	     <textarea rows="2" name="message"></textarea>
	  </div>
	  <div class="ym-fbox-button">
	    <small>
	    <input type="hidden" value="create_comment" name="form_name">
	    <input type="submit" value="<?php echo _('Comment')?>">
	    <input type="checkbox" name="anonymization"><?php echo _('Anonymize')?>
	    </small>
	  </div>
	</form>
        
        
       </section><!-- end comments -->
  </div>
</article><!-- end feed item -->

</div>




