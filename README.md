# Interface Generator

Quickly generates interfaces with getters and setters for existing classes.

It looks at their `@property` items (phpdoc). Intend to also look at `private`, `protected` and `public` (which
shouldn't exist!) properties. This was bashed out when building some interfaces to work with the eWay PHP Rapid SDK so
it's geared towards that purpose.

This is rough and intended more to remove some of the monotonous tedium of creating interfaces than necessarily churn
out perfect code.

If you use PHPStorm, you're probably familiar with its ability to generator getters and setters for classes. However it
doesn't seem able to do so for interfaces. 

### Notes

It expects classes to be in a similar format to in the `input` directory.

It will generate interfaces in the form:

```
interface MyInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): self;
}
```

And classes:

```
class MyClass implements MyInterface
{
    /** @var string $name */
    protected $name;
    
    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): MyInterface
    {
        $this->name = $name;
        
        return $this;
    }
}
```

The specifics may vary depending on whether explicit argument types and return types are set to on.

Note that it uses 4 spaces as tabs (because if you do otherwise you're a monster! :stuck_out_tongue_winking_eye:), and it sets the typehinting return for
the setters to `$this` to allow for chaining (because if you do otherwise you're also a monster! :stuck_out_tongue_winking_eye:).


### Usage
For a simple demonstration:

`php run.php`

See `run.php` for how it works with the class.

### Configuration

The following properties offer some configuration at runtime:
```
protected $outputDir = '';
protected $overwriteFiles = false;
protected $generateClasses = false;
protected $useExplicitTypes = false;
protected $php7ReturnTypes = false;
```

Property|Description
---|---
`$outputDir` | Directory the output files should be written.
`$overwriteFiles` | Overwrite existing files. **This is destructure, be careful.**
`$generateClasses` | Generate accompanying classes that implement the interfaces. To save time essentially.
`$argumentTypes` | Explicitly state the argument type in the function. Will be typehinted in PHPDoc regardless.
`$returnTypes` | Generate functions with PHP7 return types. Will be typehinted in PHPDoc regardless.