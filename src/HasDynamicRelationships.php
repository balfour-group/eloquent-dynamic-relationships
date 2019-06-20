<?php

namespace Balfour\EloquentDynamicRelationships;

use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;
use UnexpectedValueException;

trait HasDynamicRelationships
{
    /**
     * @var array
     */
    protected static $bonds = [];

    /**
     * @param string $related
     * @param callable $resolver
     */
    public static function bond($related, callable $resolver)
    {
        static::$bonds[get_called_class()][$related] = $resolver;
    }

    /**
     * @param string $related
     */
    public static function breakup($related)
    {
        if (static::isBondedWith($related)) {
            unset(static::$bonds[get_called_class()][$related]);
        }
    }

    /**
     * @return array
     */
    public static function getBonds()
    {
        return static::$bonds[get_called_class()] ?? [];
    }

    /**
     * @param string $related
     * @return bool
     */
    public static function isBondedWith($related)
    {
        $bonds = static::getBonds();

        return isset($bonds[$related]);
    }

    /**
     * @param string $related
     * @return callable
     */
    public static function getBondResolver($related)
    {
        if (!static::isBondedWith($related)) {
            throw new UnexpectedValueException(sprintf(
                'The relationship "%s" is not bonded with this entity.',
                $related
            ));
        }

        return static::$bonds[get_called_class()][$related];
    }

    /**
     * Get a relationship.
     *
     * @param string $key
     * @return mixed
     */
    public function getRelationValue($key)
    {
        $value = parent::getRelationValue($key);

        if ($value !== null) {
            return $value;
        }

        if (static::isBondedWith($key)) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    /**
     * Get a relationship value from a method.
     *
     * @param string $method
     * @return mixed
     * @throws \LogicException
     */
    protected function getRelationshipFromMethod($method)
    {
        if (static::isBondedWith($method)) {
            $resolver = static::getBondResolver($method);
            $resolver = \Closure::bind($resolver, $this, static::class);
            $relation = call_user_func($resolver);
        } else {
            $relation = $this->$method();
        }

        if (! $relation instanceof Relation) {
            throw new LogicException(sprintf('%s::%s must return a relationship instance.', static::class, $method));
        }

        return tap($relation->getResults(), function ($results) use ($method) {
            $this->setRelation($method, $results);
        });
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::isBondedWith($method)) {
            $resolver = static::getBondResolver($method);
            $resolver = \Closure::bind($resolver, $this, static::class);
            return call_user_func_array($resolver, $parameters);
        }

        return parent::__call($method, $parameters);
    }
}
