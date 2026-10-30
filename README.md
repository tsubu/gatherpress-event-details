# GatherPress Event Details

A companion plugin for [GatherPress](https://github.com/GatherPress/gatherpress) that lets organizers add labeled details to an event, such as food and drinks, parking, childcare, or the language spoken, and shows them in a consistent place on the event.

It follows the direction suggested in [GatherPress#2236](https://github.com/GatherPress/gatherpress/issues/2236): rather than a dedicated food setting in core, a generic list of labeled rows that organizers define themselves, placed into the event layout through the Block Hooks API.

## What it does

- Adds an **Event details** list to the *Event settings* sidebar panel of every post type that declares `gatherpress-event-date` support.
- Adds an **Event Details** block (`gatherpress-event-details/event-details`) that prints the list as a description list (`<dl>`). Details need both a label and a description to be shown; the block prints nothing when there are none.
- Places the block after the Online Event block in GatherPress's *Event with RSVP* starter pattern and in the `gatherpress/event-template` Block Hooks anchor, so new events include it. Add the block by hand to existing events.

## Data

The details are stored in one post meta value, `gatherpress_event_details`, as an ordered list of `{ "label": string, "value": string }` objects. It is exposed through the REST API (`meta.gatherpress_event_details`), travels with WXR export and import, and is deleted on uninstall.

Limits: 20 details per event, 100 characters per label, 1,000 characters per description.

## Extending

```php
// Change the labels suggested while typing.
add_filter(
	'gatherpress_event_details_label_suggestions',
	static fn( array $labels ): array => array_merge( $labels, array( 'Wi-Fi' ) )
);
```

Theme authors can set `--gatherpress-event-details--row-gap` and `--gatherpress-event-details--label-font-weight`.

## Development

```bash
composer install
npm install
npm run build
npm run lint:php && npm run lint:phpstan && npm run lint:js && npm run test:unit:js
npm run wp-env start && npm run test:unit:php   # requires Docker
```

## Requirements

- WordPress 7.0 or later
- PHP 8.1 or later
- GatherPress 0.36.1 or later

## License

GPLv2 or later.
