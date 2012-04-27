/* here goes jQuery */

var BASE_URL ='/Sites/Refresh/SITE/';

$(document).ready(function() {


	$('.feed-settings').click(function() {
	  $('.tab_form_close').toggle('slow', function() {
	    // Animation complete.
	  });
	  return false;
	});



/* AJAX main form 
$('#shareidea').ajaxForm(function() { 
                console.log("Thank you for your comment!");
            });
*/

 var options = { 
        target:        '#output',   // target element(s) to be updated with server response 
        beforeSubmit:  showRequest,  // pre-submit callback 
        success:       showResponse  // post-submit callback 
 
        // other available options: 
        url : '/Sites/Refresh/SITE/'
        //type:      type        // 'get' or 'post', override for form's 'method' attribute 
        //dataType:  null        // 'xml', 'script', or 'json' (expected server response type) 
        //clearForm: true        // clear all form fields after successful submit 
        //resetForm: true        // reset the form after successful submit 
 
        // $.ajax options can be used here too, for example: 
        //timeout:   3000 
    }; 


	$('#shareidea').submit(function() { 
		$(this).ajaxSubmit(options); 
		return false; 
	    }); 

	// pre-submit callback 
	function showRequest(formData, jqForm, options) { 
	    
	    var queryString = $.param(formData); 
	    console.log('About to submit: \n\n' + queryString); 
	    return true; 
	} 
	 
	// post-submit callback 
	function showResponse(responseText, statusText, xhr, $form)  { 
	    alert('status: ' + statusText + '\n\nresponseText: \n' + responseText + 
		'\n\nThe output div should have already been updated with the responseText.'); 
	} 

	/* display comments */
	$('.speccom').click(function() {
	  var com = $(this).attr('href');
	  com = BASE_URL+'actions.php'+com;
	  console.log(com);
	  //$('.comments').load(com);
	  return false;
	});


}) // end document ready
