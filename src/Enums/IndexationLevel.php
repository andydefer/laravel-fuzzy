<?php

declare(strict_types=1);

namespace Fuzzy\Enums;

/**
 * Defines which lifecycle events trigger automatic indexing.
 *
 * This enum allows models to control which events (create, update, delete)
 * will automatically update the search index. By default, all three events
 * are enabled, but models can override this to optimize performance or
 * handle special cases.
 */
enum IndexationLevel: string
{
    /**
     * Never index automatically - manual indexing only.
     */
    case NONE = 'none';

    /**
     * Index on create events only.
     */
    case CREATE_ONLY = 'create';

    /**
     * Index on update events only.
     */
    case UPDATE_ONLY = 'update';

    /**
     * Index on delete events only.
     */
    case DELETE_ONLY = 'delete';

    /**
     * Index on create and update events, but not delete.
     */
    case CREATE_AND_UPDATE = 'create_update';

    /**
     * Index on create and delete events, but not update.
     */
    case CREATE_AND_DELETE = 'create_delete';

    /**
     * Index on update and delete events, but not create.
     */
    case UPDATE_AND_DELETE = 'update_delete';

    /**
     * Index on all events (create, update, delete).
     */
    case ALL = 'all';

    /**
     * Convert the enum to an array of enabled events.
     *
     * @return array<string> Array of event names ('create', 'update', 'delete')
     */
    public function toEventsArray(): array
    {
        return match ($this) {
            self::NONE => [],
            self::CREATE_ONLY => ['create'],
            self::UPDATE_ONLY => ['update'],
            self::DELETE_ONLY => ['delete'],
            self::CREATE_AND_UPDATE => ['create', 'update'],
            self::CREATE_AND_DELETE => ['create', 'delete'],
            self::UPDATE_AND_DELETE => ['update', 'delete'],
            self::ALL => ['create', 'update', 'delete'],
        };
    }

    /**
     * Check if a specific event is enabled.
     *
     * @param string $event The event name ('create', 'update', or 'delete')
     * @return bool True if the event is enabled
     */
    public function hasEvent(string $event): bool
    {
        return in_array($event, $this->toEventsArray(), true);
    }
}
