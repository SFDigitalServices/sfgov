Office Hours creates a Field, that you can add to any entity (like a location,
a restaurant or a user) to represent "office hours" or "opening hours".

== UPGRADE WARNING ==
You MUST run update.php, when you upgrade from version 1.1 (or lower)
to a -dev version or version 1.2 (when available).

== GENERAL FEATURES ==
The Drupal 7 version now provides the following features: 
- Feeds module support to import data. (See below for details.)

The widget provides:
- default weekly office hours (multi-value, per field instance).
- using 1, 2 or even more 'time slots' per day (thanks to jonhattan).
- 'allowed hours' restrictions;
- input validation;
- use of either a 24 or 12 hour clock;

The formatter provides o.a.:
- a 'Current status' indicator ('open now'/'closed now');
- options to show all/none/open/current days;
- options to group days (E.g., "Mon-Fri 12:00-22:00");
- customizable element separators to display the 'office hours' any way you want. (See below for details.)

You can configure the formatter as follows:
- Add the field to an entity/node;
- Select the 'Office hours' formatter;
- Set the formatter details at /admin/structure/types/manage/NODE_TYPE/display/VIEW_MODE;
or
- Add the field to a view;
- Select the 'Office hours' formatter;
- Check the formatter settings of the field;

== FORMATTING THE HOURS ==
Using the customizable separators in the formatter settings, you can format the hours any way you want. 
- The formatter is default set up to show a nice table.
- To export the data to a Google Places bulk upload file, you can create a view,
  and set the formatter to generate the following data (for a shop that opens from Monday to Friday): 
    2:10:00:18:00,3:10:00:18:00,4:10:00:18:00,5:10:00:18:00,6:10:00:18:00,7:12:00:20:00

== USING VIEWS - FIELDS ==
Add the Field to any Views display, as you are used to do.
- To show only 1 day per row in a Views display: 
  - add the field to your View,
  - open the MULTIPLE FIELD SETTINGS section,
  - UNcheck the option 'Display all values in the same row',
  - make also sure you display 'all' values. (only valid if you have upgraded from 1.1 version.)

== USING VIEWS - FILTER CRITERIA ==
Only default (out-of-the-box) Views functionality is provided.
- To show only the entities that have a office hours: 
  - add the filter criterion "Content: Office hours (field_office_hours:day)" to your View,
  - set the filter option 'Operator' to 'is not empty',
- To show only the entities that have office hours for e.g., Friday: 
  - add the filter criterion "Content: Office hours (field_office_hours:day)" to your View,
  - set the filter option 'Operator' to 'is equal to',
  - set the filter option 'Value' to '5', or leave 'Value' empty and set 'Expose operator' to YES.
- To show only the entities that are open NOW: 
  This is not possible, yet. 

== USING VIEWS - SORT CRITERIA ==
Only default (out-of-the-box) Views functionality is provided.
- To sort the times per day, add the 'day' sort criterion. 

== USING VIEWS - CREATE A BLOCK PER NODE/ENTITY ==
Suppose you want to show the Office hours on a node page, but NOT on the page itself, 
but rather in a separate block, follow these instructions:
(If you use non-Node Content types/Entities, you'll need to adapt some settings.)
1. First, create a new View for 'Content', and add a Block display;
 - Under FORMAT, set to an Unformatted list of Fields;
 - Under FIELDS, add the office_hours field and other fields you like;
 - Under FILTER CRITERIA, add the relevant Content type(s);
 - Under PAGER, show all items;
 - Now open the ADVANCED section;
 - Under CONTEXTUAL FILTERS, add 'Content: Nid';
 -- Set 'Provide default value' to 'Content ID from URL';
 -- Set 'Specify validation criteria' to the same Content type(s) as under FILTERS;
 -- Set 'Filter value format' according to your wishes;
 -- Set 'Action to take if filter value does not validate' to 'Hide View';
 - Tweak the other settings as you like.

2. Now, configure your new Block under /admin/structure/block/manage/ : 
 - Set the Block title, and the Region settings;
 - Under PAGES, set 'Show block on specific pages' to 'Only the listed pages' and 'node/*';
   You might want to add more pages, if you use other non-node entity types.
 - Tweak the other settings as you like.
 You'll need to tune the block for the following cases: 
 - A user accesses the node page, but 'Access denied';
 - A node is unpublised;

Now, test your node page. You'll see the Office hours in the page AND in the block. That's once too many.

3. So, modify the 'View mode' of your Content type under /admin/structure/types/manage/<MY_CONTENT_TYPE>/display
 - Select MANAGE DISPLAY;
 - Select the View mode. (Perhaps you need to create an extra view mode for other purposes.)
 - Select the Office_hours, and set the Format to 'Hidden';
 - Save the data, end enjoy the result!


== IMPORTING WITH FEEDS MODULE ==
To import data with the Feeds module, the following columns can be used:
- day;
- hours/morehours from;
- hours/morehours to;
- hours/morehours from-to.

The day should be stated in full English name, or a day number where sunday = 0, monday=1, etc.
The hours can be formatted as hh:mm or hh.mm

I suppose Feeds Tamper can help to format the times and/or day to the proper format.

Here's an example file:
nid;weekday;Hours_1;Hours_2
2345;monday;11:00 - 18:01;
2345;tuesday;10:00 - 12:00;13:15-17.45
2383;monday;11:00 - 18:01;
2383;tuesday;10:00 - 12:00;13:15-17.45
