<?php
namespace Drupal\s3fs;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Asset\AssetDumper;


/**
 * Overrides default LanguageManager to provide configured languages.
 */
class S3fsStreamOverrideManager extends AssetDumper implements S3fsStreamOverrideManagerInterface{
  /**
   * Dumps an (optimized) asset to persistent storage.
   *
   * @param string $data
   *   An (optimized) asset's contents.
   * @param string $file_extension
   *   The file extension of this asset.
   *
   * @return string
   *   An URI to access the dumped asset.
   */
  public function dump($data, $file_extension) {
    dsm('in dump');
    //http://localhost/projects/drupal-7.43/s3fs-css/css/css_YXBpU-PIcsLPZrrUcGv3oBTJ6bWjX3eTnFYuWNGa4NE.css
    //return "{$GLOBALS['base_url']}/s3fs-css/" . drupal_encode_path($s3_key);
    // Prefix filename to prevent blocking by firewalls which reject files
    // starting with "ad*".
    $filename = $file_extension. '_' . Crypt::hashBase64($data) . '.' . $file_extension;
    // Create the css/ or js/ path within the S3 bucket.
    if(\Drupal::config('s3fs.settings')->get('no_rewrite_cssjs')) {
      $path = 's3fs://' . $file_extension;
    }
    else {
      ///sites/default/files/css/css_DImuuvc9S8V88m4n2WP6xWYIYqktcP21urgDq7ksjK8.css?o8c8p0
      $path = 'public://' . $file_extension;
      //return "{$GLOBALS['base_url']}/s3fs-css/" . drupal_encode_path($s3_key);
      //return "{$GLOBALS['base_url']}/s3fs-js/" . drupal_encode_path($s3_key);
    }

    $uri = $path . '/' . $filename;
    // Create the CSS or JS file.
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    if (!file_exists($uri) && !file_unmanaged_save_data($data, $uri, FILE_EXISTS_REPLACE)) {
      return FALSE;
    }
    // If CSS/JS gzip compression is enabled and the zlib extension is available
    // then create a gzipped version of this file. This file is served
    // conditionally to browsers that accept gzip using .htaccess rules.
    // It's possible that the rewrite rules in .htaccess aren't working on this
    // server, but there's no harm (other than the time spent generating the
    // file) in generating the file anyway. Sites on servers where rewrite rules
    // aren't working can set css.gzip to FALSE in order to skip
    // generating a file that won't be used.
    if (extension_loaded('zlib') && \Drupal::config('system.performance')->get($file_extension . '.gzip')) {
      if (!file_exists($uri . '.gz') && !file_unmanaged_save_data(gzencode($data, 9, FORCE_GZIP), $uri . '.gz', FILE_EXISTS_REPLACE)) {
        return FALSE;
      }
    }
    return $uri;
  }

}
