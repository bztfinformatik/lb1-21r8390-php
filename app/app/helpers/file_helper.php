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
    $zipFile = new \PhpZip\ZipFile();
    try {
        $zipFile
            ->addDirRecursive($sourcePath) // add files from the directory
            ->saveAsFile($outZipPath) // save the archive to a file
            ->close(); // close archive

        return true;
    } catch (\PhpZip\Exception\ZipException $e) {
        // handle exception
        throw $e;
    } finally {
        $zipFile->close();
    }
}
