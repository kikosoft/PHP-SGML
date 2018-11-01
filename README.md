# SGML
Generate valid SGML code with a simple PHP class. This class can be used to build a HTML or XML writer.

A simple PHP example would be:

```
$recipe = new SGML('recipe',['language' => 'English']);
$recipe->comment('Taken from https://www.bbcgoodfood.com/recipes/pina-colada')
       ->title('Piña colada');
$recipe->description('A tropical blend of rich coconut cream, white rum and tangy pineapple - serve with an umbrella for kitsch appeal');
$ingredients = $recipe->ingredients();
$ingredients->ingredient('120ml pineapple juice');
$ingredients->ingredient('60ml white rum');
$ingredients->ingredient('60ml coconut cream');
$ingredients->ingredient('wedge of pineapple, to garnish (optional)');
$recipe->method('Pulse all the ingredients along with a handful of ice in a blender until smooth. Pour into a tall glass and garnish as you like.');
$recipe->flush(FALSE);
```

This would output:

```
<recipe language="English">
  <!-- Taken from https://www.bbcgoodfood.com/recipes/pina-colada -->
  <title>Piña colada</title>
  <description>A tropical blend of rich coconut cream, white rum and tangy pineapple - serve with an umbrella for kitsch appeal</description>
  <ingredients>
    <ingredient>120ml pineapple juice</ingredient>
    <ingredient>60ml white rum</ingredient>
    <ingredient>60ml coconut cream</ingredient>
    <ingredient>wedge of pineapple, to garnish (optional)</ingredient>
  </ingredients>
  <method>Pulse all the ingredients along with a handful of ice in a blender until smooth. Pour into a tall glass and garnish as you like.</method>
</recipe>
```

