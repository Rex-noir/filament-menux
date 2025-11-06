<?php

namespace AceREx\FilamentMenux\Support;

class DeferredConfiguration
{
    private $value;

    private $resolver;

    public function __construct($valueOrResolver)
    {
        if (is_callable($valueOrResolver)) {
            $this->resolver = $valueOrResolver;
        } else {
            $this->value = $valueOrResolver;
        }
    }

    public function resolve()
    {
        if (isset($this->resolver)) {
            $this->value = call_user_func($this->resolver);
            $this->resolver = null;
        }

        return $this->value;
    }

    public function isDeferred(): bool
    {
        return isset($this->resolver);
    }
}
