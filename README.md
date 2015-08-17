# ajax_search

This can be installed as a WordPress plugin.
The data.csv file is loaded into a the database and a table is created.
As there are nearly 50 000 records in the data.csv file it is advisable to manually load these entries in the correct format into the database if there are limitations on your server's php execution time.

## Usage Instructions ##

* Once the plugin is activated go to any regular page on the site (one that produces content).
* Append /location-search/ to the end of the url e.g localhost/wordpress_site/sample-page/location-search/
* The search bar will be appended to the end of the content.
* Once 3 or more characters are entered, the ajax search will run and display the appropriate results.
* The results page can be loaded via selecting an option from the dropdown list or hitting the "Enter" key.
