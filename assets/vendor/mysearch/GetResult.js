//
//  GetResult.js
//  This function is called to request that MySearch.php
//  return the results.
//

function getResult() {

  let resultsElement = document.getElementById("search_results")
  let searchElement = document.getElementById("search_input");

  let searchString = searchElement.value;
  let filter = '*.html';  // Extensions to examine
  let searchDir = '../../../';
  let rootDir = '../../../';  // The site root relative to the location of the search html and search PHP
  let template = `<h5 class="search-title"><a target="_top" href="#{href}" class="search-link">#{title}</a></h5>
                            <p>...#{token}...</p>
                            <p class="match"><em>Terms matched: #{count} - URL: #{href}</em></p>`;


  let xhr = new XMLHttpRequest();
  let theURL = 'MySearch.php';
  let request = new URLSearchParams(
    { s: searchString,        //  key/value pairs are passed as key=value
      template: template,
      filter: filter,
      search_dir: searchDir,
      root_dir: rootDir});

  xhr.open( 'GET', theURL +'?'+ request.toString(), true );
  // Need to open before assigning events
  xhr.onreadystatechange = ( event ) => { // Respond to all changes
    if( xhr.readyState === 4 && xhr.status === 200 ) {  // Wait for completion
      // readyState of 4 = DONE
      // status of 200 = OK  i.e. The resource has been fetched and transmitted in the message body.
      resultsElement.innerHTML = xhr.response;
    }
  };

  xhr.send();
}
