# Ajax loader
## This extension allows to load TYPO3 content through ajax requests.

### Use cases:

* You have non-cache plugin on page, but you need TYPO3 to cache page and send cache headers.
* Content element that fetch content data from external sources and may slowdown loading of your page.

### How to install

* Install extension.
* Include static TypoScript.

### How to use

* Add plugin "Ajax loader" on page through "New content element" wizard. Quick access from "Plugins" tab.
* Save and close, no further configuration required.
* Now go to page module view. You can see that it's possible to add content inside plugin container.
* Add content you want to load with ajax.
* Clear page cache. Check frontend. 