# Pending bookings

## Updating

A high level overview follows:

- Create the new pending booking plugin class
- Create an action helper to fetch and manipulate the data
- Create a view helper to customize rendering of the data (if necessary)
- Update the local configuration to point to the class you created in the first step

### Creating the pending booking plugin class

It’s easiest to copy an existing class, usually. Check TillikumX\\Booking for
other classes to get an idea of what this entails.

This is where you set things like the name of the plugin, its description, and
point the way to the action and view helpers so Tillikum can render it.

### Creating an action helper class

Check TillikumX\\Controller\\Action\\Helper for existing classes.

I usually keep 1 of the previous classes around so that I can refer to it the
next time around.

This is where the meat of your changes will happen. You’ll need to talk to the
customer about the dates and so forth, as well as any updates that may need to
happen to the table. Things change from year to year.

### Creating a view helper class

Check TillikumX\\View\\Helper for existing classes.

I usually keep 1 of the previous classes around so that I can refer to it the
next time around.

You can usually just copy another DataTablePending class, and change it a little
bit.

If you need to add or update a view script, the class points the way to the view
script, and update that accordingly.

### Update the local configuration

After you’ve made your changes, you’ll need to add the pending booking class to
the local configuration before it will show up in the list.

If you’re doing this in development, you will want to add this earlier in the
process. If you’re doing it on a production server, you want to make the change
*after* you’ve deployed your changes above, this way you don’t refer to a class
that doesn’t exist and break the pending bookings page in the interim.
