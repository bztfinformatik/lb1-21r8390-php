<?php

/**
 * Copy a file, or recursively copy a folder and its contents
 *
 * @version     1.0.1
 * @link        http://aidanlister.com/2004/04/recursively-copying-directories-in-php/
 * @param       string   $source    Source path
 * @param       string   $dest      Destination path
 * @return      bool     Returns `true` on success, `false` on failure
 */
function copyr(string $source, string $dest): bool
{
    // Check for symlinks
    if (is_link($source)) {
        return symlink(readlink($source), $dest);
    }

    // Simple copy for a file
    if (is_file($source)) {
        return copy($source, $dest);
    }

    // Make destination directory
    if (!is_dir($dest)) {
        mkdir($dest);
    }

    // Loop through the folder
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories
        copyr("$source/$entry", "$dest/$entry");
    }

    // Clean up
    $dir->close();
    return true;
}

/**
 * Zip a folder (including itself).
 * 
 * @link  https://www.codexworld.com/create-zip-file-using-php/
 * @param string $sourcePath Relative path of directory to be zipped.
 * @param string $outZipPath Path of output zip file. 
 * @return bool Returns `true` on success, `false` on failure
 */
function zipDir(string $sourcePath, string $outZipPath): bool
{
    // Initialize archive object
    $z = new ZipArchive();
    if ($z->open($outZipPath, ZipArchive::CREATE) !== true) {
        return false;
    }

    // Add all files to the archive and trim the path
    dirToZip($sourcePath, $z, strlen("$sourcePath/"));

    // Close archive
    return $z->close();
}

/**
 * Add files and sub-directories in a folder to zip file.
 * 
 * @param string $folder Folder path that should be zipped.
 * @param ZipArchive $zipFile Zip file where files end up.
 * @param int $exclusiveLength Number of text to be excluded from the file path
 */
function dirToZip(string $folder, ZipArchive &$zipFile, int $exclusiveLength)
{
    // Open the folder
    $handle = opendir($folder);
    // Loop through the folder
    while (FALSE !== $f = readdir($handle)) {
        // Check for local/parent path or zipping file itself and skip
        if ($f != '.' && $f != '..' && $f != basename(__FILE__)) {
            $filePath = "$folder/$f";
            // Remove prefix from file path before add to zip
            $localPath = substr($filePath, $exclusiveLength);
            if (is_file($filePath)) {
                $zipFile->addFile($filePath, $localPath);
            } elseif (is_dir($filePath)) {
                // Add sub-directory
                $zipFile->addEmptyDir($localPath);
                dirToZip($filePath, $zipFile, $exclusiveLength);
            }
        }
    }
    closedir($handle);
}
