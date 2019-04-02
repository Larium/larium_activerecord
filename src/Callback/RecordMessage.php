<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Callback;

use Larium\Executor\Message;

class RecordMessage extends Message
{
    protected $record;

    /**
     * Gets record.
     *
     * @access public
     * @return ActiveRecord\Record
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * Sets record.
     *
     * @param ActiveRecord\Record $record the value to set.
     * @access public
     * @return void
     */
    public function setRecord($record)
    {
        $this->record = $record;
    }
}
