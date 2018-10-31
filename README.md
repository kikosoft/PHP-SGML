# sgml
Generate valid SGML code with a simple PHP class. This class can be used to build a HTML or XML writer.

A simple PHP example would be:

```
$outside = new SGML('outside');
$inside  = new SGML('inside','Hello world!');
$outside->attach($inside)
        ->setAttrs(['size' => 7,
                    'loop' => 'forever')
        ->flush(FALSE);
```

This would output:

```
<outside size="7" loop="forever">
  <inside>Hello world!</inside>
</outside>
```

