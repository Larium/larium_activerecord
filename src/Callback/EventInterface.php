<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Callback;

class EventInterface
{
    const BEFORE_SAVE = 'before_save';

    const AFTER_SAVE = 'after_save';

    const BEFORE_CREATE = 'before_create';

    const AFTER_CREATE = 'after_create';

    const BEFORE_UPDATE = 'before_update';

    const AFTER_UPDATE = 'after_update';

    const BEFORE_DESTROY = 'before_destroy';

    const AFTER_DESTROY = 'after_destroy';
}
