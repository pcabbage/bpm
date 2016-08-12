<?php

namespace Gini\Process\Engine;

class_exists('\Gini\Those');

class SJTU implements \Gini\Process\IEngine
{
    public function __construct()
    {
    }

    public function fetchProcessInstance($processName, $instancID)
    {
        $process = a('sjtu/bpm/process', ['name'=> $processName]);
        if (!$process->id) return false;

        $instance = a('sjtu/bpm/process/instance', $instancID);

        return $instance->id ? $instance : false;
    }

    public function startProcessInstance($processName, $data)
    {
        $process = those('sjtu/bpm/process')->whose('name')->is($processName)
                    ->orderBy('version', 'desc')->current();
        if (!$process->id) {
            throw new \Gini\Process\Engine\Exception();
        }

        $instance = a('sjtu/bpm/process/instance');
        $instance->process = $process;
        $instance->data = $data;
        if (!$instance->save()) {
            throw new \Gini\Process\Engine\Exception();
        }

        $instance->start();

        return $instance;
    }

    public function getTask($taskID)
    {
        return a('sjtu/bpm/process/task', $taskID);
    }

    public function those($name)
    {
        return those($name);
    }

    public function getProcessGroups($processName, $version=null)
    {
        $criteria = ['name'=> $processName];
        if ($version) {
            $criteria['version'] = $version;
        }
        $process = a('sjtu/bpm/process', $criteria);
        if (!$process->id) return [];
        return those('sjtu/bpm/process/group')->whose('process')->is($process);
    }

    public function getProcessGroup($processName, $groupName, $processVersion=null)
    {
        $criteria = ['name'=> $processName];
        if ($processVersion) {
            $criteria['version'] = $processVersion;
        }
        $process = a('sjtu/bpm/process', $criteria);
        if (!$process->id) return;

        $group = a('sjtu/bpm/process/group', [
            'process'=> $process,
            'name'=> $groupName
        ]);

        return $group->id ? $group : null;
    }
}
