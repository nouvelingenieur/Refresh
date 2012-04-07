<?php

/*
	This examples illustrates the unroll comments function
	sources: 
	- http://www.9lessons.info/2009/12/display-collapsed-comments-like.html
	- http://jsfiddle.net/clemsos/HFqST/22/
*/

?>

<html> 
<head> 
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script> 
	
<style media="screen" type="text/css">
.newsformcomment {
    padding:10px;
    border:solid 1px #ccc;
    margin: 30px 5px;
    width: 300px
}
</style>
	
</head> 


<body>

<a class='speccom' href="?action=unrollcomment&amp;order=0&amp;thread_id=27#a27">
                    <span class="newslinkcomment_roll">2 comments</span>
                </a>


<div class='loader'></div>

<div class="newsformcomment">
  <p>Here goes the comments</p>
</div>


    <script type="text/javascript">
		// var BASE URL = 'http://refresh.org';
		var ACTIONS_URL = '../ajax.php';

		$('.speccom').click(function() {
			
			var url = ACTIONS_URL + $(this).attr("href");
			
			// show that is working
			$('.loader').html('Loading posts from : ' + url);
			
			// load posts
			$.ajax(
				{
					url: url,
					success: function(rep)
					{
						$('.newsformcomment').html(rep);
					}
				});

		 
				
			return false;
			
		});
    </script> 

</body>
</html>