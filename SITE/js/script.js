/* here goes jQuery */

var BASE_URL ='/Sites/Refresh/SITE/';
var ACTIONS_URL = 'ajax.php';

$(document).ready(function() {

	$('.feed-settings').click(function() {
	  $('.tab_form_close').toggle('slow', function() {
	    // Animation complete.
	  });
	  return false;
	});

// to extract parameters from URLs

function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}


// post edit/delete 
$('.adminutil').click(function() {

	var url = $(this).attr('href');
	var mod = $(this).parent().parent().append('<div class="modal" id="myModal"><div class="modal-header"><button class="close" data-dismiss="modal">×</button><h3>Modal header</h3></div><div class="modal-body"></div><div class="modal-footer"></div></div>');
	
	$(".modal .modal-body", mod).load(url);

	return false;

});


// add file popup
$('#modalFile').modal({
  show: false
});


/* display comments */
	$('.speccom').click(function() {
	
	  url = ACTIONS_URL + $(this).attr("href");
	
	console.log(url);
	// show that is working
	var box = $('.commentbox', $(this).parent().parent());
	
	box.html('Loading posts from : ' + url);
	console.log(box)
	// extract thread id
	var thread_id =  $(this).attr("rel");
	
	// load posts
	$.ajax(
		{
			url: url,
			type: 'POST',
			data: { thread_id: thread_id },
			success: function(rep)
			{
				console.log(box)
				box.prev('.loader').remove();
				
					var display = '<ul>';
				var callback = jQuery.parseJSON(rep);
				$.each(callback.DATA, function(key,comment) {
					  display = display + '<li>' + comment.text + ' - ' + comment.possibly_name + ' - ' + comment.date + '</li>';
				   });
				display = display + '</ul>';
				box.html(display);
			}
		});
		
	return false;
			
	});
	



/* Post a thread with AJAX */

	$('#shareidea').ajaxForm({ 
		dataType:  'json', 
		success:   processJson 
		});

		function processJson(data) { 
			console.log(data.SUCCESSES[0]);	
		}



}) // end document ready
