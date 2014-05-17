<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

namespace Larium\ActiveRecord\Callback;

use Larium\Executor\Executor;
use Larium\ActiveRecord\Record;

class EventExecutor
{
    protected $executor;

    protected $record;

    protected $states = array(
        'before_save', 'after_save',
        'before_create', 'after_create',
        'before_update', 'after_update',
        'before_destroy', 'after_destroy'
    );

    public function __construct(Record $record)
    {
        $this->executor = new Executor();

        $this->record = $record;

        $this->register_events();
    }

    public function register($state, $command)
    {
        $this->executor->addCommand($state, $command);
    }

    public function execute($state, \Closure $block = null)
    {
        $message = new RecordMessage();
        $message->setRecord($this->record);

        $events = array();

        $result = $this->executor->execute('before_' . $state, $message);
        if ($result !== false && ($state == 'update' || $state == 'create')) {
            $result = $this->executor->execute('before_save', $message);
        }

        if ($result !== false ) {
            if (is_callable($block)) {
                $result = $block();
            }
        } else {
            $result = false;
        }

        if ($result !== false) {
            $result = $this->executor->execute('after_' . $state, $message);
            if ($result !== false && ($state == 'update' || $state == 'create')) {
                $result = $this->executor->execute('after_save', $message);
            }
        }

        return $result;
    }

    private function register_events()
    {
        foreach ($this->states as $state) {
            if (property_exists($this->record, $state)) {

                $prop = new \ReflectionProperty($this->record, $state);

                if ($prop->isPublic()) {
                    $methods = $this->record->$state;

                    foreach($methods as $method) {
                        $this->register($state, array($this->record, $method));
                    }
                }
            }
        }
    }
}
