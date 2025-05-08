<?php
/**
 * This is a sample class.
 */
class MyClass
{
    /**
     * This is a sample property.
     *
     * @var string
     */
    public $name = "Test";

    /**
     * This is a sample method.
     *
     * @param string $greeting The greeting message.
     * @return string The full greeting.
     */
    public function greet(string $greeting): string
    {
        return $greeting . ' ' . $this->name;
    }
}

/**
 * This is a sample function.
 *
 * @param int $a The first number.
 * @param int $b The second number.
 * @return int The sum of the two numbers.
 */
function add(int $a, int $b): int
{
    return $a + $b;
}
?>