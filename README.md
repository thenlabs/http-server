
# HttpServer

Implementación de un servidor HTTP escrito totalmente en PHP.

## Instalación.

    $ composer require thenlabs/http-server dev-main

## Uso.

Para ejecutar el servidor es necesario crear un archivo con el siguiente contenido.

>Usted deberá modificar dicho contenido de acuerdo a sus necesidades.

```php
<?php
// run-server.php

require __DIR__.'/vendor/autoload.php';

use ThenLabs\HttpServer\HttpServer;

$config = [
    'host' => '127.0.0.1',
    'port' => 8080,
    'document_root' => __DIR__.'/vendor/thenlabs/http-server/tests/document_root',
];

$server = new HttpServer($config);
$server->start();

while (true) {
    $server->run();
}
```

Seguidamente se deberá ejecutar el siguiente comando:

    $ php run-server.php

Una vez hecho esto podremos acceder a la URL que hayamos especificado en la configuración y podremos ver la respectiva página.

>Si usted ha especificado la opción de configuración `document_root` con el mismo valor que mostramos en el ejemplo anterior, usted verá la siguiente página que usamos para pruebas internas.

![](demo.png)

Es importante aclarar que por defecto se servirá el archivo de nombre `index.html` que se encuentre en el directorio raíz especificado en la configuración.

## Análisis de rendimiento.

Con el objetivo de medir el rendimiento del servidor, hemos realizado unas comparaciones con el servidor Apache y el servidor integrado de PHP, sirviendo una página de 939,46KB de tamaño y 7 recursos(imágenes, hojas de estilo, scripts).

Las pruebas fueron ejecutadas sobre el siguiente entorno:

<table>
    <tr><td>Versión de PHP</td><td>7.4.3</td></tr>
    <tr><td>Sistema Operativo</td><td>Ubuntu 20.04 (64 bits)</td></tr>
    <tr><td>Procesador</td><td>i7-8750H</td></tr>
    <tr><td>Memoria RAM</td><td>16GB</td></tr>
</table>

Sobre cada servidor se ejecutó la página unas 20 veces y se obtuvieron los siguientes resultados.

### Resultados:

<table style="text-align: center">
    <thead>
        <tr>
            <th></th>
            <th>HttpServer (ms)</th>
            <th>Built In Server (ms)</th>
            <th>Apache 2.4.41 (ms)</th>
        </tr>
    </thead>
    <tbody>
        <tr><td></td><td>106</td><td>148</td><td>141</td></tr>
        <tr><td></td><td>84</td><td>128</td><td>116</td></tr>
        <tr><td></td><td>98</td><td>95</td><td>107</td></tr>
        <tr><td></td><td>146</td><td>94</td><td>86</td></tr>
        <tr><td></td><td>137</td><td>116</td><td>97</td></tr>
        <tr><td></td><td>140</td><td>124</td><td>79</td></tr>
        <tr><td></td><td>112</td><td>82</td><td>105</td></tr>
        <tr><td></td><td>112</td><td>93</td><td>85</td></tr>
        <tr><td></td><td>94</td><td>98</td><td>73</td></tr>
        <tr><td></td><td>97</td><td>73</td><td>86</td></tr>
        <tr><td></td><td>106</td><td>85</td><td>53</td></tr>
        <tr><td></td><td>95</td><td>133</td><td>138</td></tr>
        <tr><td></td><td>99</td><td>139</td><td>139</td></tr>
        <tr><td></td><td>98</td><td>119</td><td>137</td></tr>
        <tr><td></td><td>135</td><td>107</td><td>134</td></tr>
        <tr><td></td><td>148</td><td>100</td><td>138</td></tr>
        <tr><td></td><td>125</td><td>97</td><td>142</td></tr>
        <tr><td></td><td>108</td><td>95</td><td>105</td></tr>
        <tr><td></td><td>97</td><td>108</td><td>81</td></tr>
        <tr><td></td><td>107</td><td>92</td><td>79</td></tr>
    </tbody>
    <tfoot>
        <tr>
            <th>Average</th>
            <th>112,2 ms</th>
            <th>106,3 ms</th>
            <th>106,05 ms</th>
        </tr>
    </tfoot>
</table>

