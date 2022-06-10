# SF Gov date

This provides custom formatters for the "smart date range" field to display date data in a more unified way.
You can use any of the existing formatters, or extend SfgovDateFormatterBase if you need a new display. The base
formatter makes it easier to determine if there is a date or time range and display data differently based on that information.
A new custom formatter only needs to extend the SetDateString and SetTimestring functions, which will then set values that are easy to call in the templates.

