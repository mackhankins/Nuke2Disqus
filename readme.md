XML Creator for existing phpnuke comments for Disqus
====================================================

### Description

This will create an exported.xml file so you can import your existing php nuke comments into Disqus.  It requires the PDO extension be installed for php, but I will make a mysql version eventually.  I tested this on RavenNuke 2.51 and older versions.  Don't forget to follow the instructions below.

### Steps
1. Download, Open, and Edit PDO.php to match your current database credentials.
2. Upload to public_html folder and run in browser.
3. Using ftp, download exported.xml.
4. Delete PDO.php.
5. Use the Generic WXR option on the Disqus Import Page to upload exported.xml.

### Notes
1.  You can use http://validator.w3.org/ to validate before importing to Disqus