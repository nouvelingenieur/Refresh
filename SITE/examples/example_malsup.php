<?php

/*
	Plateforme web PPR - outil de crowdsourcing
	Copyright(C) 2011 Nicolas SEICHEPINE

	This file is part of PPR.
	
	PPR is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
	Contact : nicolas.seichepine.org/?action=contact
*/

?>

<html> 
<head> 
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7/jquery.js"></script> 
    <script src="http://malsup.github.com/jquery.form.js"></script> 
 
    <script> 
        // wait for the DOM to be loaded 
        $(document).ready(function() { 
            // bind 'myForm' and provide a simple callback function 
           $('#myForm').ajaxForm({ 
				// dataType identifies the expected content type of the server response 
				dataType:  'json', 
		 
				// success identifies the function to invoke when the server response 
				// has been received 
				success:   processJson 
			});  
        }); 
		
		function processJson(data) { 
			// 'data' is the json object returned from the server 
			alert(data.RESULT); 
		}
    </script> 
</head> 


<body>

<form id="myForm" action="ajax.php?action=post" method="post">
    Name: <input type="text" name="title" /> 
    Comment: <textarea name="message"></textarea> 
    <input type="submit" value="Submit Comment" /> 
</form>

</body>
</html>