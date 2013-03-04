Nuke to Disqus
==============

* Creates an xml file of existing comments
* Tested on RavenNuke 2.51

#### Steps
1. Download, Open, and Edit PDO.php to match your current database credentials.
2. Upload to public_html folder and run in browser.
3. Using ftp, download exported.xml.
4. Delete PDO.php.
5. Use the Generic (WXR) option on the Disqus import to upload exported.xml.

#### Notes
* Requires PDO extension for PHP.  Mysql version soon I guess.
* You can use http://validator.w3.org/ to validate.
* You can check the percentage of your import status at http://import.disqus.com/.