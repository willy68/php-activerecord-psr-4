<?php

namespace ActiveRecord\Relationships;

use ActiveRecord\Model;

/**
 * Interface for a table relationship.
 *
 * @package ActiveRecord\relationships
 */
interface InterfaceRelationship
{
    public function __construct($options = array());
    public function build_association(Model $model, $attributes = array(), $guard_attributes = true);
    public function create_association(Model $model, $attributes = array(), $guard_attributes = true);
}
