<?php

declare(strict_types=1);

namespace Montala\ResourceSpace;

use ValueError;

class CommandPlaceholderArg
{
    /**
     * A placeholders' value representing ONE command argument value (highly contextual).
     */
    private string $value;

    /**
     * Constructor
     * 
     * @param string $value The actual placeholder value
     * @param null|callable $validator Use null for the default one, otherwise any callable where the value to be
     * tested is the first argument.
     */
    public function __construct(string $value, ?callable $validator)
    {
        $validator ??= [__CLASS__, 'defaultValidator'];
        if ($validator($value)) {
            $this->value = $value;
            return;
        }

        throw new ValueError('Invalid placeholder argument value!');
    }

    /**
     * Input validation helper function for determining if there are any blocked metacharacters which could be used to
     * exploit OS command injections.
     */
    public static function defaultValidator(string $val): bool
    {
        // ; & | $ > < ` \ ! ' " ( ) including white space
        return !preg_match('/[;&|\$><`\\!\'"()\s]/', $val);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
