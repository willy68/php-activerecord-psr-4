<?php

/**
 * Array serializer.
 *
 * @package ActiveRecord
 */

namespace ActiveRecord\Serializers;


class ArraySerializer extends Serialization
{
    public static $include_root = false;

    public function to_s()
    {
        return self::$include_root ? array(strtolower(get_class($this->model)) => $this->to_a()) : $this->to_a();
    }
}
