This is a PHP emulation of the [mod_autoindex](http://httpd.apache.org/docs/trunk/mod/mod_autoindex.html) directory listing of the Apache HTTP server.

If you have a webspace on a server where directory listings are disabled using `Options -Indexes`, and you have no control over that option but would still like to enable it, simply put the `index.php` file from this repository in your directory. It will display a directory listing that looks the same as the one by mod_autoindex.
