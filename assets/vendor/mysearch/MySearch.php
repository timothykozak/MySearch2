<?php

// MySearch.php
//
// This code is used to look through server side html files
// for a search term.  It looks through all content in the body
// and ignores scripts.  All html tags and their attributes
// are stripped out but all of the content remains.  This means
// that even the text of a button will be part of the search.
// It will return a list of files that contain the search term.
// The template will be used to give information about each
// file.
//
// This template...
//      `<h5 class="search-title"><a target="_top" href="#{href}" class="search-link">#{title}</a></h5>
//       <p>...#{token}...</p>
//       <p class="match"><em>Terms matched: #{count} - URL: #{href}</em></p>`
//
// ... with a search for Quail will generate this result.
//       <div class="search-results">
//         <ol class="search-list">
//           <li class="search-list-item">
//             <h5 class="search-title"><a target="_top" href="../MySearch/QTest.html" class="search-link">Words With Q</a></h5>
//             <p>... Words with Q <span class="search">Quail</span> Quartz Quaker ...</p>
//             <p class="match"><em>Terms matched: 1 - URL: ../MySearch/QTest.html</em></p>
//           </li>
//         </ol>
//       </div>
//
// The required parameters passed are:
//      s - the text to be searched for
//      template - a string of text that will be updated
//          with information found during the search.
//          #{href} is replaced with the file location
//          #{title} is replaced with the title of the html page
//          #{token} is replaced with the first string of
//                   text that contains the found search term
//          #{count} is replaced with the number of times
//                   that the search term was found in the file
// Optional parameters are:
//      search_dir - the directory in which to begin the search,
//          please note that this is relative to the location
//          of this file.  Subdirectories are searched.
//      filter - file types to search, typically *.html
//
//  In the returned html the following classes are automatically
//  inserted without being defined:
//      search
//      search-results
//      search-quick-result
//      search-list-item
//      search-list-item-all
//      search-error
//
// This code was originally part of a download of the Charia template
// from monsterone.com.

// TODO search term of nothing matches everything

define('SIDE_CHARS', 15);
$file_count = 0;    // The number of files found
$final_result = array();

[$search_term, $search_term_length, $search_dir, $search_filter, $search_template] = sanitize_GET();

$files = list_files($search_dir);

foreach ($files as $file) {

    [$found, $clean_content] = process_contents($file, $file_count, $search_term, $final_result);

    if ($found && !empty($found)) {
      highlight_matches($found, $file_count, $search_term, $search_term_length, $clean_content, $final_result);
    } else {
      $final_result[$file_count]['search_result'][] = '';
    }
    $file_count++;
}

if ($file_count > 0) {

//Sort final result
    foreach ($final_result as $key => $row) {
        $search_result[$key] = $row['search_result'];
    }
    array_multisort($search_result, SORT_DESC, $final_result);
}

?>

<div class="search-results">

    <ol class="search-list">
        <?php
        $sum_of_results = 0;
        $match_count = 0;
        for ($i = 0; $i < count($final_result); $i++) {
            if (!empty($final_result[$i]['search_result'][0]) || $final_result[$i]['search_result'][0] !== '') {
                $match_count++;
                $sum_of_results += count($final_result[$i]['search_result']);
                    ?>
                    <li class="search-list-item">

                        <?php
                        $replacement = [$final_result[$i]['page_title'][0],
                            $final_result[$i]['file_name'][0],
                            $final_result[$i]['search_result'][0],
                            count($final_result[$i]['search_result'])
                        ];
                        $template = preg_replace(["/#{title}/","/#{href}/","/#{token}/","/#{count}/"],$replacement, $search_template);

                        echo $template; ?>
                    </li>
                    <?php
                }
        }

        if ($match_count == 0) {
            echo '<li><div class="search-error">No results found for "<span class="search">' . $search_term . '</span>"<div/></li>';
        }
        ?>
    </ol>
</div>

<?php

function sanitize_GET()
{ // Obtain the GET variables and sanitize as necessary.
    if (!isset($_GET['s'])) {
        die('You must define a search term!');
    }

    $search_dir = '../..';  // Starting directory, might be overridden by a passed parameter
    $search_term = mb_strtolower($_GET['s'], 'UTF-8');

    if (isset($_GET['search_dir'])) {
        $search_dir = $_GET['search_dir'];
    }

    $search_term = preg_replace('/^\/$/', '"/"', $search_term);
    $search_term = preg_replace('/\+/', ' ', $search_term);
    $search_term_length = strlen($search_term);


    $search_filter_init = $_GET['filter'];
    $search_filter = preg_replace("/\*/", ".*", $search_filter_init);
    $search_template = preg_replace('/\+/', ' ', $_GET['template']);

    return([$search_term, $search_term_length, $search_dir, $search_filter, $search_template]);
}

function list_files($dir)
{   // Returns an array of all the files in $dir and its sub-directories that
    // have the extension htm or html

    $result = array();
    if (is_dir($dir)) {
        if ($dh = opendir($dir)) {
            while (($file = readdir($dh)) !== false) {
                if (!($file == '.' || $file == '..')) { // Not the current or parent directory
                    $file = $dir . '/' . $file;
                    if (is_dir($file) && $file != './.' && $file != './..') { // Recursively walk the subdirectories
                        $result = array_merge($result, list_files($file));
                    } else if (!is_dir($file)) {
                        if (preg_match('/\.htm[l]?$/', $file) && (0 < filesize($file))) {
                            $result[] = $file;
                        }
                    }
                }
            }
        }
    }
    return $result;
}

function strpos_recursive($haystack, $needle, $offset = 0, &$results = array())
{
    $offset = stripos($haystack, $needle, $offset);
    if ($offset === false) {
        return $results;
    } else {
        $pattern = '/' . $needle . '/ui';
        preg_match_all($pattern, $haystack, $results, PREG_OFFSET_CAPTURE);
        return $results;
    }
}

function process_contents($file, $file_count, $search_term, &$final_result)
{ // Search through the contents for the any matches and return the cleaned content of the file.
    $contents = file_get_contents($file);

    if (preg_match("/<body.*>(.*)<\/body>/si", $contents, $body_content)) { //getting content only between <body></body> tags
        $body_content = preg_replace("/<script.*>.*<\/script>/si", '', $body_content);  // Remove any scripts
        $clean_content = strip_tags($body_content[0]); //remove html tags
        $clean_content = preg_replace('/\s+/', ' ', $clean_content); //remove duplicate whitespaces, carriage returns, tabs, etc

        $found = strpos_recursive(mb_strtolower($clean_content, 'UTF-8'), $search_term);

        preg_match("/<title>(.*)<\/title>/", $contents, $page_title); //getting page title
        $final_result[$file_count]['page_title'][] = $page_title[1];
        $final_result[$file_count]['file_name'][] = $file;
    }
    return[$found, $clean_content];
}

function highlight_matches($found, $file_count, $search_term, $search_term_length, $clean_content, &$final_result)
{
    for ($z = 0; $z < count($found[0]); $z++) {
        $pos = $found[0][$z][1];
        if ($pos < SIDE_CHARS) {
            $pos_start = $pos;
        } else {
            $pos_start = $pos - SIDE_CHARS;
        }
        $pos_end = SIDE_CHARS * 5 + $search_term_length;

        $str = substr($clean_content, $pos_start, $pos_end);
        $result = preg_replace('/' . $search_term . '/ui', '<span class="search">\0</span>', $str);
        $final_result[$file_count]['search_result'][] = $result;
    }
}

?>
