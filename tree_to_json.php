<?php
/**
 * PHP implementation to fetch tree.txt from URL and convert to JSON
 * Based on the tree-parse Node.js library logic
 */

class TreeParser {
    
    /**
     * Calculates the directory level/indent for a line from a tree output.
     *
     * @param string $line The line from the tree command output to process.
     * @return int The directory level or how indented the line is in the tree.
     */
    private function getIndent($line) {
        preg_match_all('/[│├└]|[ \x{00A0}]{4}/u', $line, $matches);
        return count($matches[0]);
    }
    
    /**
     * Strips all tree ascii decoration from a line to get just the file/directory name.
     *
     * @param string $line The line from the tree command output to process.
     * @return string The name of the file/directory on the line, stripped of any decoration or whitespace.
     */
    private function getName($line) {
        return trim(preg_replace('/^[│├└─\s]*/', '', $line));
    }
    
    /**
     * Stores a new child in the tree, based on a given stack of parents.
     *
     * @param array $tree The parsed tree object.
     * @param array $parents The parents of the new child to store.
     * @param string $item The new child file/directory to add to the tree.
     */
    private function store(&$tree, $parents, $item) {
        $parentObj = &$tree;
        foreach ($parents as $parent) {
            $parentObj = &$parentObj[$parent];
        }
        $parentObj[$item] = [];
    }
    
    /**
     * Parse a string output from the tree command into an array.
     *
     * @param string $input The output of the tree command to parse.
     * @return array The parsed tree, as an associative array.
     */
    public function parse($input) {
        $lines = explode("\n", trim($input));
        $tree = []; // The final tree
        $parents = []; // A stack used to track the parents and current child
        $lastIndent = -1; // By starting at -1, the first if statement will handle adding the base dir
        
        foreach ($lines as $line) {
            $thisIndent = $this->getIndent($line);
            $thisName = $this->getName($line);
            
            // If the line is empty, ignore
            if (empty($thisName)) continue;
            
            // If we're indented more than previous, we're inside the last parent
            if ($thisIndent > $lastIndent) {
                $this->store($tree, $parents, $thisName); // Store this in the tree
                $parents[] = $thisName; // Store this as the last parent/child
                
                $lastIndent = $thisIndent; // Update indent
                continue; // Done
            }
            
            // If we're less indented than previous, we're above the last parent
            if ($thisIndent < $lastIndent) {
                // If we're at zero, we've reached the end of tree
                if ($thisIndent === 0) continue;
                
                array_pop($parents); // Remove the last child of the parent we're not in
                $indentChange = $lastIndent - $thisIndent;
                $parents = array_slice($parents, 0, count($parents) - $indentChange); // Remove the parents we're not in
                $this->store($tree, $parents, $thisName); // Store this in the tree
                $parents[] = $thisName; // Store this as the last parent/child
                
                $lastIndent = $thisIndent; // Update indent
                continue; // Done
            }
            
            // We're on the same level as last time
            array_pop($parents); // Remove the last child
            $this->store($tree, $parents, $thisName); // Store this in the tree
            $parents[] = $thisName; // Store this as the last child
            
            $lastIndent = $thisIndent; // Update indent
        }
        
        return $tree;
    }
}

/**
 * Main function to fetch tree data from URL and convert to JSON
 */
function treeToJson() {
    // URL to fetch tree.txt from
    $url = 'https://diagmindtw.com/sql_read_api/persist.php?getFile=252c6ce43b391d18a04551a73428cb7bf89ffabe2b2ab6720f662d3535d4e95e';
    
    // Fetch the content from URL
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]
    ]);
    
    $content = file_get_contents($url, false, $context);
    
    if ($content === false) {
        return ['error' => 'Failed to fetch content from URL'];
    }
    
    // Convert CP950 to UTF-8 if needed (most modern systems use UTF-8 by default)
    // You might need to detect encoding or assume it's already UTF-8
    if (function_exists('mb_detect_encoding')) {
        $encoding = mb_detect_encoding($content, ['UTF-8', 'CP950', 'Big5'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
    }
    
    // Replace Windows ASCII tree characters with Unicode equivalents
    $content = str_replace(['+---', '\\---', '|   '], ['├──', '└──', '│   '], $content);
    
    // Parse the tree structure
    $parser = new TreeParser();
    $treeObject = $parser->parse($content);
    
    return $treeObject;
}

// Set JSON response header
header('Content-Type: application/json; charset=utf-8');

try {
    // Get the parsed tree as JSON
    $result = treeToJson();
    
    // Output as formatted JSON
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Handle any errors
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
