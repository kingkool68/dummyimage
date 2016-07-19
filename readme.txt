Instructions for Setting Up A Dynamic Dummy Image Generator on your own Server - http://dummyimage.com

What are the requirements?
	You need to have a server than can run PHP code and supports the GD library. Since PHP 4.3 the GD library has been bundled in. For more info go to http://us2.php.net/manual/en/ref.image.php
	It will also help to have mod_rewrite enabled.
	
What do I do?
	Upload code.php, color.class.php, mplus-1c-medium.ttf, and the included .htaccess file to a directory on your server. If you want a copy of the documentation then upload index.php and if you want the documentation to look good upload /css which contains reset.css and dummyimage.css. If you can't pass the image dimensions from the URL (I.e. http://yourserver.com/yourdir/123x345 doesn't show you an image) then look at the .htaccess file.
	You can always pass the image dimension variables to the script by doing http://yourserver.com/yourdir/code.php?x=123x345

Sample .htaccess file
	If you can't see a .htaccess file (Windows hides it by default) copy htaccess.txt to your server and rename it to .htaccess 
	
Credit
	Code written by Russell Heimlich - http://www.russellheimlich.com/blog
	Contact: http://www.russellheimlich.com/contact.html
	
	Some code was written by Ruquay K Calloway http://ruquay.com/sandbox/imagettf/ to detect the text bounding box better (see comments in the PHP code.)