<?php
/** Angie Radtke php update_joomla_namespaces.php
 * Script to update Joomla template files from legacy "J" prefixed class names
 * to modern namespaced class names with proper use statements.
 */

// Define the directory to process
$htmlDir = __DIR__ . '/templates/html';

// Define the mappings from old "J" prefixed classes to new namespaced classes
$classMappings = [
    'JFactory' => 'Factory',
    'JText' => 'Text',
    'JHtml' => 'HTMLHelper',
    'JLayoutHelper' => 'LayoutHelper',
    'JLayoutFile' => 'FileLayout',
    'JModuleHelper' => 'ModuleHelper',
    'JLanguageAssociations' => 'Associations',
    'JUri' => 'Uri',
    'JRoute' => 'Route',
    'JRegistry' => 'Registry',
    'JApplication' => 'Factory::getApplication()',
    'JUser' => 'Factory::getUser()',
    'JDatabase' => 'Factory::getContainer()->get("DatabaseDriver")',
];

// Define the required use statements
$useStatements = [
    'use Joomla\CMS\Factory;',
    'use Joomla\CMS\HTML\HTMLHelper;',
    'use Joomla\CMS\Language\Text;',
    'use Joomla\CMS\Layout\FileLayout;',
    'use Joomla\Registry\Registry;',
    'use Joomla\CMS\Helper\ModuleHelper;',
    'use Joomla\CMS\Language\Associations;',
    'use Joomla\CMS\Layout\LayoutHelper;',
    'use Joomla\CMS\Router\Route;',
    'use Joomla\CMS\Uri\Uri;',
    'use Joomla\Registry\Registry;'
];

/**
 * Recursively find all PHP files in a directory
 */
function findPhpFiles($dir) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    
    return $files;
}

/**
 * Check if file contains any "J" prefixed class calls
 */
function containsJPrefixedCalls($content) {
    $patterns = [
        '/\bJFactory\b/',
        '/\bJText\b/',
        '/\bJHtml\b/',
        '/\bJLayoutHelper\b/',
        '/\bJLayoutFile\b/',
        '/\bJModuleHelper\b/',
        '/\bJLanguageAssociations\b/',
        '/\bJUri\b/',
        '/\bJRoute\b/',
        '/jimport\s*\(\s*[\'"]/',
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Add use statements to a PHP file if they don't already exist
 */
function addUseStatements($content, $useStatements) {
    // Check if file already has use statements
    if (strpos($content, 'use Joomla\CMS') !== false) {
        return $content; // Already has modern use statements
    }
    
    // Find the position after the defined('_JEXEC') line
    $pattern = '/defined\s*\(\s*[\'"]_JEXEC[\'"]\s*\)\s*or\s*die\s*;?\s*\n/';
    if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        $insertPos = $matches[0][1] + strlen($matches[0][0]);
        
        // Insert use statements
        $useBlock = "\n" . implode("\n", $useStatements) . "\n";
        $content = substr_replace($content, $useBlock, $insertPos, 0);
    }
    
    return $content;
}

/**
 * Update "J" prefixed class calls to modern equivalents
 */
function updateClassCalls($content, $classMappings) {
    // Remove jimport statements
    $content = preg_replace('/jimport\s*\(\s*[\'"][^\'"]*[\'"]\s*\)\s*;\s*\n?/', '', $content);
    
    // Replace class calls
    foreach ($classMappings as $oldClass => $newClass) {
        // Handle special cases
        if ($oldClass === 'JApplication') {
            // Don't replace JApplication directly, it's more complex
            continue;
        }
        
        // Replace class calls (e.g., JFactory::method() -> Factory::method())
        $pattern = '/\b' . preg_quote($oldClass, '/') . '\b/';
        $content = preg_replace($pattern, $newClass, $content);
    }
    
    return $content;
}

/**
 * Process a single PHP file
 */
function processFile($filePath, $useStatements, $classMappings) {
    echo "Processing: $filePath\n";
    
    $content = file_get_contents($filePath);
    
    // Check if file needs updating
    if (!containsJPrefixedCalls($content)) {
        echo "  - No J-prefixed calls found, skipping\n";
        return false;
    }
    
    // Add use statements
    $content = addUseStatements($content, $useStatements);
    
    // Update class calls
    $content = updateClassCalls($content, $classMappings);
    
    // Write back to file
    file_put_contents($filePath, $content);
    echo "  - Updated successfully\n";
    
    return true;
}

// Main execution
try {
    if (!is_dir($htmlDir)) {
        throw new Exception("HTML directory not found: $htmlDir");
    }
    
    echo "Starting Joomla namespace update process...\n";
    echo "Processing directory: $htmlDir\n\n";
    
    $phpFiles = findPhpFiles($htmlDir);
    echo "Found " . count($phpFiles) . " PHP files\n\n";
    
    $updatedCount = 0;
    
    foreach ($phpFiles as $file) {
        if (processFile($file, $useStatements, $classMappings)) {
            $updatedCount++;
        }
    }
    
    echo "\nProcess completed!\n";
    echo "Updated $updatedCount files out of " . count($phpFiles) . " total PHP files.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
