
markItUp!
------------------------------------

Version: 1.0
Author: Marcin Konicki (http://ahwayakchih.neoni.net)
Build Date: 9 March 2009
Requirements: Symphony version 2.0.2 or later.

[SYNOPSIS]

This extension ports jQuery based markItUp! markup editor 
( http://markitup.jaysalvat.com/ ) to Symphony.
It will add some basic buttons to every textarea field which is 
configured to use one of supported formatters.


[INSTALLATION]

1. Upload the 'markitup' folder in this archive to your Symphony 'extensions' 
   folder.

2. Enable it on System > Extensions page: select the "markItUp!",
   choose Enable from the "With selected..." menu, then click "Apply" button.

[CONFIGURATION]

Installation procedure will find all textareas which use one of supported 
formatters and configure them to use markItUp.

If you want to disable markItUp on some specific fields, or change 
markup setting, just go to section edit page and change settings for 
each field (there is new "markItUp!" configuration on each Textarea 
field panel).


[ADDITIONAL FORMATTERS]

markItUp extension comes with support for HTML and Markdown formatters.
If you want to use it with different formatter, e.g. Textile or BBCode, 
download markup set from http://markitup.jaysalvat.com/downloads/
and unzip to extensions/markitup/assets/markitup/sets/ directory.

