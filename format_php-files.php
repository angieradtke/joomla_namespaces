<?php
/**
 * Script to format all  php files in the HTML folder and remove unnecessary blank lines
 * Author: Angie Radtke
 */

// Define the directory to process
$htmlDir = __DIR__ . '/templates/html';

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
 * Format a file by removing excessive blank lines and cleaning up formatting
 */
function formatFile($filePath) {
    echo "Processing: $filePath\n";
    
    $content = file_get_contents($filePath);
    $originalContent = $content;
    
    // Split content into lines
    $lines = explode("\n", $content);
    $formattedLines = [];
    $consecutiveBlankLines = 0;
    $maxConsecutiveBlankLines = 2; // Allow maximum 2 consecutive blank lines
    
    foreach ($lines as $line) {
        // Remove trailing whitespace from each line
        $trimmedLine = rtrim($line);
        
        // Check if line is blank (empty or only whitespace)
        if (trim($line) === '') {
            $consecutiveBlankLines++;
            
            // Only add blank line if we haven't exceeded the maximum
            if ($consecutiveBlankLines <= $maxConsecutiveBlankLines) {
                $formattedLines[] = $trimmedLine;
            }
        } else {
            // Reset counter for non-blank lines
            $consecutiveBlankLines = 0;
            $formattedLines[] = $trimmedLine;
        }
    }
    
    // Join lines back together
    $formattedContent = implode("\n", $formattedLines);
    
    // Remove trailing blank lines at the end of file, but keep one newline
    $formattedContent = rtrim($formattedContent) . "\n";
    
    // Only write if content has changed
    if ($formattedContent !== $originalContent) {
        file_put_contents($filePath, $formattedContent);
        echo "  - Formatted successfully\n";
        return true;
    } else {
        echo "  - No changes needed\n";
        return false;
    }
}

/**
 * Get file statistics for reporting
 */
function getFileStats($filePath) {
    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $totalLines = count($lines);
    $blankLines = 0;
    $consecutiveBlankSections = 0;
    $maxConsecutiveBlank = 0;
    $currentConsecutiveBlank = 0;
    
    foreach ($lines as $line) {
        if (trim($line) === '') {
            $blankLines++;
            $currentConsecutiveBlank++;
            $maxConsecutiveBlank = max($maxConsecutiveBlank, $currentConsecutiveBlank);
        } else {
            if ($currentConsecutiveBlank > 2) {
                $consecutiveBlankSections++;
            }
            $currentConsecutiveBlank = 0;
        }
    }
    
    return [
        'totalLines' => $totalLines,
        'blankLines' => $blankLines,
        'maxConsecutiveBlank' => $maxConsecutiveBlank,
        'excessiveBlankSections' => $consecutiveBlankSections
    ];
}

// Main execution
try {
    if (!is_dir($htmlDir)) {
        throw new Exception("HTML directory not found: $htmlDir");
    }
    
    echo "Starting HTML file formatting process...\n";
    echo "Processing directory: $htmlDir\n\n";
    
    $phpFiles = findPhpFiles($htmlDir);
    echo "Found " . count($phpFiles) . " PHP files\n\n";
    
    $formattedCount = 0;
    $totalBlankLinesRemoved = 0;
    $totalExcessiveSections = 0;
    
    // First pass: collect statistics
    echo "=== ANALYZING FILES ===\n";
    foreach ($phpFiles as $file) {
        $stats = getFileStats($file);
        if ($stats['maxConsecutiveBlank'] > 2) {
            echo basename($file) . ": {$stats['totalLines']} lines, {$stats['blankLines']} blank, max consecutive: {$stats['maxConsecutiveBlank']}\n";
            $totalExcessiveSections += $stats['excessiveBlankSections'];
        }
    }
    
    echo "\n=== FORMATTING FILES ===\n";
    
    // Second pass: format files
    foreach ($phpFiles as $file) {
        $beforeStats = getFileStats($file);
        
        if (formatFile($file)) {
            $formattedCount++;
            $afterStats = getFileStats($file);
            $blankLinesRemoved = $beforeStats['blankLines'] - $afterStats['blankLines'];
            $totalBlankLinesRemoved += $blankLinesRemoved;
            
            if ($blankLinesRemoved > 0) {
                echo "  - Removed $blankLinesRemoved excessive blank lines\n";
            }
        }
    }
    
    echo "\n=== SUMMARY ===\n";
    echo "Process completed!\n";
    echo "Formatted $formattedCount files out of " . count($phpFiles) . " total PHP files.\n";
    echo "Total blank lines removed: $totalBlankLinesRemoved\n";
    echo "Files with excessive blank line sections before formatting: $totalExcessiveSections\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
