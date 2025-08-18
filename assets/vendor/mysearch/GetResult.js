//
//  GetResult.js
//  This function is called to request that MySearch.php
//  return the results.
//

function getResult() {

  let resultsElement = document.getElementById("search_results")
  let searchElement = document.getElementById("search_input");

  let searchString = searchElement.value;
  let searchDir = '../../..'; // The root of the site relative to the location of the search html and search PHP
  let template = `<h5 class="search-title"><a target="_top" href="#{href}" class="search-link">#{title}</a></h5><p>...#{token}...</p><p class="match"><em>Terms matched: #{count}</em></p>`;


  let xhr = new XMLHttpRequest();
  let theURL = 'MySearch.php';
  let request = new URLSearchParams(
    { search_term: searchString,        //  key/value pairs are passed as key=value
      template: template,
      search_dir: searchDir});

  xhr.open( 'GET', theURL +'?'+ request.toString(), true );
  // Need to open before assigning events
  xhr.onreadystatechange = ( event ) => { // Respond to all changes
    if( xhr.readyState === XMLHttpRequest.DONE ) {  // Wait for completion
      if ( xhr.status === 200 ) { // status of 200 = OK  i.e. The resource has been fetched and transmitted in the message body.
        resultsElement.innerHTML = xhr.response;
      } else {  // Could be any number of errors
        resultsElement.innerHTML = "Server Error = " + xhr.status;
      }
    }
  };

  xhr.send();
}
