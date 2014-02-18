wordpress-amazon-s3-plugin
==========================

### Previous
Unlike other plugins, this one deletes the original file after uploading.  Oh, and we hook into the thumbnail-creation to work our magic, so all of your assets (including custom sizes) are on S3.  That said, we'd love to get some feedback and make this thing better.

-The Sanborn Media Factory Crew

### Added by @israelshirk 6/5/2013
* Prevent local deletion as it happened before WP was able to generate thumbnails.  I'm thinking this is a result of the changes to the media uploader from 3.4 to 3.5
* Moved s3 class to s3.php to make things a little more readable; also wrapped them inside class_exists(...) to prevent plugin conflicts
* Removed add_filter declaration
* Settings can be defined in wp-config.php rather than in the admin, if desired.

```
define("S3_STREAM_BUCKET_NAME", 'bucket_name');
define("S3_STREAM_PATH", 'bucket_path');
define("S3_STREAM_ACCESS_KEY", 'access_key');
define("S3_STREAM_SECRET", 'secret_key');
```
