<?php

namespace Gini\ORM\SJTU\BPM\Process;

class Instance extends \Gini\ORM\Object implements \Gini\Process\IInstance
{
    public $process = 'object:sjtu/bpm/process';
    public $hash = 'string:32';
    public $data = 'array';
    public $status = 'int,default:0';

    const STATUS_END = '-1';

    public function getVariable($key)
    {
    }

    public function start()
    {
        if ($this->task) return;
        return $this->_execute();
    }

    public function next()
    {
        return $this->_execute();
    }

    private function _execute()
    {
        if ($this->status==self::STATUS_END) return;

        $task = those('sjtu/bpm/process/task')->whose('instance')->is($this)
                ->orderBy('ctime', 'desc')
                ->current();

        if ($task->id && $task->status == \Gini\ORM\SJTU\BPM\Process\Task::STATUS_PENDING) {
            $task->autorun();
            return false;
        }
 
        $info = $this->process->getNextTaskInfo($task->id ? $task : null);
        if (empty($info)) {
            $this->status = self::STATUS_END;
            $this->save();
            return;
        }

        list($position, $infoData) = $info;

        $task = $this->_fetchTask($position, (array)$infoData);

        if (!$task) return;

        $task->autorun();

        return true;
    }

    private function _fetchTask($position, array $info=[])
    {
        if (empty($info)) return;
        $task = a('sjtu/bpm/process/task');
        $task->process = $this->process;
        $task->instance = $this;
        $task->ctime = date('Y-m-d H:i:s');
        $task->position = $position;
        if (isset($info['callback'])) {
            $task->auto_callback = $info['callback'];
        } else if (isset($info['group'])) {
            $group = a('group', $info['group']);
            if (!$group->id) return false;
            $task->candidate_group = $group;
        }
        if (!$task->save()) return false;

        return $task;
    }
}

