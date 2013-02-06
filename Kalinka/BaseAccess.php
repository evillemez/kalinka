<?php

namespace Kalinka;

class BaseAccess
{
    // TODO Raise exception if user tries to create an action or objclass
    // named "ANY" or a property named "DEFAULT".
    private $actions = ["create", "read", "update", "destroy"];

    protected function setupActions($actions)
    {
    }

    protected function setupObjectTypes($objectTypes)
    {
    }

    private $permissions = [];

    // TODO Raise exception if invalid action, object class, or property
    protected function allow($action, $object, $property = null, $func = true)
    {
        $property = is_null($property) ? "DEFAULT" : $property;
        $this->permissions[$action][$object][$property][] = $func;
    } 

    protected function allowEverything()
    {
        $this->allow("ANY", "ANY");
    }

    // TODO Raise exception if invalid action, object class, or property
    public function can($action, $object, $property = null)
    {
        $obj_class = is_string($object) ? $object : get_class($object);
        $property = is_null($property) ? "DEFAULT" : $property;

        $possible_paths = [
            [$action, $obj_class],
            ["ANY", $obj_class],
            [$action, "ANY"],
            ["ANY", "ANY"],
        ];
        foreach ($possible_paths as $path) {
            if (
                array_key_exists($path[0], $this->permissions) &&
                array_key_exists($path[1], $this->permissions[$path[0]])
            ) {
                $src = $this->permissions[$path[0]][$path[1]];
            } else {
                continue;
            }

            $funcs = null;
            if (array_key_exists($property, $src)) {
                $funcs = $src[$property];
            } elseif ($property != "DEFAULT" && array_key_exists("DEFAULT", $src)) {
                // Only use the permissions for property DEFAULT if there aren't
                // any permissions that are specifically for this property
                $funcs = $src["DEFAULT"];
            } else {
                continue;
            }

            if (is_string($object)) {
                // We only got the name of an object class, so let's check
                // if it's possible for any such objects to be permitted.
                return count($funcs) > 0;
            } else {
                foreach ($funcs as $f) {
                    if ($f === true) {
                        return true;
                    } elseif ($f($this, $object) === true) {
                        // TODO Complain if the function returns any non-bool
                        // e.g. to catch if they forgot the return statement
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
