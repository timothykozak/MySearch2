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
//          of this file.  Subdirectories are searched if
//          $recursive is true.
//      filter - file types to search, typically *.html
//      livesearch - set to nonNull if this is a constantly
//          updated (live) search
//      livecount - limit on number of files matched in a
//          live search
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

class MySearch {
    public $err_msg;
    public $search_term;    // A single word that contains only letters and numbers
    public $path_to_root;   // This file is probably in a subdirectory, so we need to know
                            // how to get to the root from here.

    function sanitizeInput() {
        // Make sure that all of necessary key/value pairs passed as part of the URL
        // are present and valid.  Indicate if there is a fatal error.
        if (!isset($_GET['s'])) {   // Is there a search term in the URL?
            $this->err_msg = "Internal Error: No Search Variable.";
            return(false);
        } else {
            $this->search_term = $_GET['s'];
            $this->search_term = preg_match('/\w*/', $this->search_term);   // Only letters and numbers and _
            if (strlen($this->search_term) == 0) {
                $this->err_msg = "Invalid search term.";
                return(false);
            }
        }

        if (isset($_GET['root_dir'])) {
            $this->path_to_root = $_GET['root_dir'];
        }
    }

}

