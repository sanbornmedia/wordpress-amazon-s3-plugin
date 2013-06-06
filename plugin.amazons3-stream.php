<?php
/*
Plugin Name: S3 Upload Hooks
Plugin URI: 
Description: 
Version: 0.2.4
Author: Jeff Hampton
Author URI: http://iamthefury.com/
Contributor: Israel Shirk
Contributor URI: http://www.github.com/israelshirk
License: GPL2
*/



/**
 * This filter handles file uploads and pushes the source file to S3
 * @param $file The file that has been uploaded to the server
 */
add_filter( 'wp_handle_upload', 's3_stream_handle_file_upload' );
function s3_stream_handle_file_upload($file) {
	// Get Upload directory
	
	// Split the path at the WP Upload Directory setting, we'll take the rest of the path
	$upload_path = get_option('upload_path');
	$upload_path = empty($upload_path) ? 'uploads' : $upload_path;
	$filePathSplit = split($upload_path, $file['file']);

    $s3 = new S3(S3_STREAM_ACCESS_KEY, S3_STREAM_SECRET);
    $s3Path = S3_STREAM_PATH . $filePathSplit[1];
    $uploadSuccess = $s3->putObject($s3->inputFile($file['file'], false), S3_STREAM_BUCKET_NAME, $s3Path, S3::ACL_PUBLIC_READ);

	$cdn_path = trailingslashit(S3_STREAM_URL) . S3_STREAM_PATH;

    if ($uploadSuccess) {
    	$file['url'] = $cdn_path . $filePathSplit[1];
    } else {
    	throw new Exception("Could not upload file to s3");
    }
    
	return $file;
}

add_filter('wp_generate_attachment_metadata','s3_stream_wp_generate_attachment_metadata');
add_filter('wp_update_attachment_metadata','s3_stream_wp_generate_attachment_metadata');
function s3_stream_wp_generate_attachment_metadata($metadata) {
	$upload_path = get_option('upload_path');
	$upload_path = empty($upload_path) ? 'uploads' : $upload_path;
	$filepath = trailingslashit(trailingslashit(WP_CONTENT_DIR) . $upload_path);
	// Parse the file on the filesystem
	$metaFileInfo = pathinfo($metadata['file']);
	// Now append the path and trailingslashit
	$filepath .= trailingslashit($metaFileInfo['dirname']);

	foreach ($metadata['sizes'] as $size => $info) {
		// Replace the raw filename with the 
		$finalFile = $filepath . $info['file'];
		s3_stream_handle_file_upload (array("file"=> $finalFile));
	}

	return $metadata;
}

// Always rewrite attachment URL's to S3
add_filter('wp_get_attachment_url', 's3_stream_get_attachment_url');
function s3_stream_get_attachment_url($url) {
	$upload_dir = get_option('upload_path');
	if (empty($upload_dir))
		$upload_dir = 'wp-content/uploads';
	if (strripos($url, "amazonaws.com") !== false)
		return $url;

	$newUrl = str_ireplace(get_site_url(), S3_STREAM_URL, $url);
	$newUrl = str_ireplace($upload_dir, S3_STREAM_PATH, $newUrl);
	return $newUrl;
}


// Allow MS admins to define the bucket in wp-config.php
if (! ( defined('S3_STREAM_ACCESS_KEY') ) ):
	$options = get_option('amazon_s3_stream_setting');

	define("S3_STREAM_BUCKET_NAME", $options['bucket']);
	define("S3_STREAM_PATH", $options['path']);
	define("S3_STREAM_ACCESS_KEY", $options['key']);
	define("S3_STREAM_SECRET", $options['secret']);

	$cdn_url = !empty($options['cdn_url']) ? $options['cdn_url'] : 'http://s3.amazonaws.com/' . S3_STREAM_BUCKET_NAME;
	define("S3_STREAM_URL", $cdn_url);

	//// Settings Page - AMD 10/2012 ////

	add_action('admin_init', 'amazon_s3_stream_options_init' );
	add_action('admin_menu', 'amazon_s3_stream_options_add_page');

	// Init plugin options to white list our options
	function amazon_s3_stream_options_init(){
	    register_setting( 'amazon_s3_stream_options_options', 'amazon_s3_stream_setting', 'amazon_s3_stream_options_validate' );
	}

	// create menu page
	function amazon_s3_stream_options_add_page() {
	    add_options_page('Amazon S3 Stream Settings', 'S3 Stream Settings', 'manage_options', 'amazon_s3_stream_options', 'amazon_s3_stream_options_do_page');
	}

	// draw the menu page
	function amazon_s3_stream_options_do_page() {
	    ?>
	    <div class="wrap">
	        <h2>Amazon S3 Stream Settings</h2>
	        <form method="post" action="options.php">
	            <?php settings_fields('amazon_s3_stream_options_options'); ?>
	            <?php $options = get_option('amazon_s3_stream_setting'); ?>
	            <table class="form-table">
	                <tr valign="top"><th scope="row">Bucket <em>my_awesome_bucket</em></th>
	                    <td><input type="text" name="amazon_s3_stream_setting[bucket]" value="<?php echo $options['bucket']; ?>" /></td>
	                </tr>
	                <tr valign="top"><th scope="row">CDN URL <em>http://laksdfjasdlfkj.cloudfront.net</em></th>
	                    <td><input type="text" name="amazon_s3_stream_setting[cdn_url]" value="<?php echo $options['cdn_url']; ?>" /></td>
	                </tr>
	                   <tr valign="top"><th scope="row">Path <em>cool/folder/for/pics</em></th>
	                    <td><input type="text" name="amazon_s3_stream_setting[path]" value="<?php echo $options['path']; ?>" /></td>
	                </tr>
	                   <tr valign="top"><th scope="row">Key</th>
	                    <td><input type="text" name="amazon_s3_stream_setting[key]" value="<?php echo $options['key']; ?>" /></td>
	                </tr>
	                   <tr valign="top"><th scope="row">Secret</th>
	                    <td><input type="text" name="amazon_s3_stream_setting[secret]" value="<?php echo $options['secret']; ?>" /></td>
	                </tr>
	            </table>
	            <p class="submit">
	            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	            </p>
	        </form>
	    </div>
	    <?php   
	}

	//validation
	function amazon_s3_stream_options_validate($input) {

	    //do it here
	    return $input;

	}
endif;

require_once('s3.php');