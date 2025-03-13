<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitDontChange
{
    public static $files = array (
        'a4a119a56e50fbb293281d9a48007e0e' => __DIR__ . '/..' . '/symfony/polyfill-php80/bootstrap.php',
    );

    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Symfony\\Polyfill\\Php80\\' => 23,
        ),
        'P' => 
        array (
            'PrivateBin\\' => 11,
        ),
        'J' => 
        array (
            'Jdenticon\\' => 10,
        ),
        'I' => 
        array (
            'Identicon\\' => 10,
            'IPLib\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Symfony\\Polyfill\\Php80\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/polyfill-php80',
        ),
        'PrivateBin\\' => 
        array (
            0 => __DIR__ . '/../..' . '/lib',
        ),
        'Jdenticon\\' => 
        array (
            0 => __DIR__ . '/..' . '/jdenticon/jdenticon/src',
        ),
        'Identicon\\' => 
        array (
            0 => __DIR__ . '/..' . '/yzalis/identicon/src/Identicon',
        ),
        'IPLib\\' => 
        array (
            0 => __DIR__ . '/..' . '/mlocati/ip-lib/src',
        ),
    );

    public static $classMap = array (
        'Attribute' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/Attribute.php',
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'IPLib\\Address\\AddressInterface' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Address/AddressInterface.php',
        'IPLib\\Address\\AssignedRange' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Address/AssignedRange.php',
        'IPLib\\Address\\IPv4' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Address/IPv4.php',
        'IPLib\\Address\\IPv6' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Address/IPv6.php',
        'IPLib\\Address\\Type' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Address/Type.php',
        'IPLib\\Factory' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Factory.php',
        'IPLib\\ParseStringFlag' => __DIR__ . '/..' . '/mlocati/ip-lib/src/ParseStringFlag.php',
        'IPLib\\Range\\AbstractRange' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Range/AbstractRange.php',
        'IPLib\\Range\\Pattern' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Range/Pattern.php',
        'IPLib\\Range\\RangeInterface' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Range/RangeInterface.php',
        'IPLib\\Range\\Single' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Range/Single.php',
        'IPLib\\Range\\Subnet' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Range/Subnet.php',
        'IPLib\\Range\\Type' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Range/Type.php',
        'IPLib\\Service\\BinaryMath' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Service/BinaryMath.php',
        'IPLib\\Service\\RangesFromBoundaryCalculator' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Service/RangesFromBoundaryCalculator.php',
        'IPLib\\Service\\UnsignedIntegerMath' => __DIR__ . '/..' . '/mlocati/ip-lib/src/Service/UnsignedIntegerMath.php',
        'Identicon\\Generator\\BaseGenerator' => __DIR__ . '/..' . '/yzalis/identicon/src/Identicon/Generator/BaseGenerator.php',
        'Identicon\\Generator\\GdGenerator' => __DIR__ . '/..' . '/yzalis/identicon/src/Identicon/Generator/GdGenerator.php',
        'Identicon\\Generator\\GeneratorInterface' => __DIR__ . '/..' . '/yzalis/identicon/src/Identicon/Generator/GeneratorInterface.php',
        'Identicon\\Generator\\ImageMagickGenerator' => __DIR__ . '/..' . '/yzalis/identicon/src/Identicon/Generator/ImageMagickGenerator.php',
        'Identicon\\Generator\\SvgGenerator' => __DIR__ . '/..' . '/yzalis/identicon/src/Identicon/Generator/SvgGenerator.php',
        'Identicon\\Identicon' => __DIR__ . '/..' . '/yzalis/identicon/src/Identicon/Identicon.php',
        'Jdenticon\\Canvas\\Canvas' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Canvas.php',
        'Jdenticon\\Canvas\\CanvasContext' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/CanvasContext.php',
        'Jdenticon\\Canvas\\ColorUtils' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/ColorUtils.php',
        'Jdenticon\\Canvas\\Matrix' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Matrix.php',
        'Jdenticon\\Canvas\\Png\\PngBuffer' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Png/PngBuffer.php',
        'Jdenticon\\Canvas\\Png\\PngEncoder' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Png/PngEncoder.php',
        'Jdenticon\\Canvas\\Png\\PngPalette' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Png/PngPalette.php',
        'Jdenticon\\Canvas\\Point' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Point.php',
        'Jdenticon\\Canvas\\Rasterization\\Edge' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Rasterization/Edge.php',
        'Jdenticon\\Canvas\\Rasterization\\EdgeIntersection' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Rasterization/EdgeIntersection.php',
        'Jdenticon\\Canvas\\Rasterization\\EdgeSuperSampleIntersection' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Rasterization/EdgeSuperSampleIntersection.php',
        'Jdenticon\\Canvas\\Rasterization\\EdgeTable' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Rasterization/EdgeTable.php',
        'Jdenticon\\Canvas\\Rasterization\\Layer' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Rasterization/Layer.php',
        'Jdenticon\\Canvas\\Rasterization\\LayerManager' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Rasterization/LayerManager.php',
        'Jdenticon\\Canvas\\Rasterization\\Rasterizer' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Rasterization/Rasterizer.php',
        'Jdenticon\\Canvas\\Rasterization\\SuperSampleBuffer' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Rasterization/SuperSampleBuffer.php',
        'Jdenticon\\Canvas\\Rasterization\\SuperSampleRange' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Canvas/Rasterization/SuperSampleRange.php',
        'Jdenticon\\Color' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Color.php',
        'Jdenticon\\Identicon' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Identicon.php',
        'Jdenticon\\IdenticonStyle' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/IdenticonStyle.php',
        'Jdenticon\\Rendering\\AbstractRenderer' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/AbstractRenderer.php',
        'Jdenticon\\Rendering\\ColorTheme' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/ColorTheme.php',
        'Jdenticon\\Rendering\\IconGenerator' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/IconGenerator.php',
        'Jdenticon\\Rendering\\ImagickRenderer' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/ImagickRenderer.php',
        'Jdenticon\\Rendering\\InternalPngRenderer' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/InternalPngRenderer.php',
        'Jdenticon\\Rendering\\Point' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/Point.php',
        'Jdenticon\\Rendering\\Rectangle' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/Rectangle.php',
        'Jdenticon\\Rendering\\RendererInterface' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/RendererInterface.php',
        'Jdenticon\\Rendering\\SvgPath' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/SvgPath.php',
        'Jdenticon\\Rendering\\SvgRenderer' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/SvgRenderer.php',
        'Jdenticon\\Rendering\\Transform' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/Transform.php',
        'Jdenticon\\Rendering\\TriangleDirection' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Rendering/TriangleDirection.php',
        'Jdenticon\\Shapes\\Shape' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Shapes/Shape.php',
        'Jdenticon\\Shapes\\ShapeCategory' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Shapes/ShapeCategory.php',
        'Jdenticon\\Shapes\\ShapeDefinitions' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Shapes/ShapeDefinitions.php',
        'Jdenticon\\Shapes\\ShapePosition' => __DIR__ . '/..' . '/jdenticon/jdenticon/src/Shapes/ShapePosition.php',
        'PhpToken' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/PhpToken.php',
        'PrivateBin\\Configuration' => __DIR__ . '/../..' . '/lib/Configuration.php',
        'PrivateBin\\Controller' => __DIR__ . '/../..' . '/lib/Controller.php',
        'PrivateBin\\Data\\AbstractData' => __DIR__ . '/../..' . '/lib/Data/AbstractData.php',
        'PrivateBin\\Data\\Database' => __DIR__ . '/../..' . '/lib/Data/Database.php',
        'PrivateBin\\Data\\Filesystem' => __DIR__ . '/../..' . '/lib/Data/Filesystem.php',
        'PrivateBin\\Data\\GoogleCloudStorage' => __DIR__ . '/../..' . '/lib/Data/GoogleCloudStorage.php',
        'PrivateBin\\Data\\S3Storage' => __DIR__ . '/../..' . '/lib/Data/S3Storage.php',
        'PrivateBin\\Filter' => __DIR__ . '/../..' . '/lib/Filter.php',
        'PrivateBin\\FormatV2' => __DIR__ . '/../..' . '/lib/FormatV2.php',
        'PrivateBin\\I18n' => __DIR__ . '/../..' . '/lib/I18n.php',
        'PrivateBin\\Json' => __DIR__ . '/../..' . '/lib/Json.php',
        'PrivateBin\\Model' => __DIR__ . '/../..' . '/lib/Model.php',
        'PrivateBin\\Model\\AbstractModel' => __DIR__ . '/../..' . '/lib/Model/AbstractModel.php',
        'PrivateBin\\Model\\Comment' => __DIR__ . '/../..' . '/lib/Model/Comment.php',
        'PrivateBin\\Model\\Paste' => __DIR__ . '/../..' . '/lib/Model/Paste.php',
        'PrivateBin\\Persistence\\AbstractPersistence' => __DIR__ . '/../..' . '/lib/Persistence/AbstractPersistence.php',
        'PrivateBin\\Persistence\\PurgeLimiter' => __DIR__ . '/../..' . '/lib/Persistence/PurgeLimiter.php',
        'PrivateBin\\Persistence\\ServerSalt' => __DIR__ . '/../..' . '/lib/Persistence/ServerSalt.php',
        'PrivateBin\\Persistence\\TrafficLimiter' => __DIR__ . '/../..' . '/lib/Persistence/TrafficLimiter.php',
        'PrivateBin\\Request' => __DIR__ . '/../..' . '/lib/Request.php',
        'PrivateBin\\TemplateSwitcher' => __DIR__ . '/../..' . '/lib/TemplateSwitcher.php',
        'PrivateBin\\View' => __DIR__ . '/../..' . '/lib/View.php',
        'PrivateBin\\Vizhash16x16' => __DIR__ . '/../..' . '/lib/Vizhash16x16.php',
        'PrivateBin\\YourlsProxy' => __DIR__ . '/../..' . '/lib/YourlsProxy.php',
        'Stringable' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/Stringable.php',
        'Symfony\\Polyfill\\Php80\\Php80' => __DIR__ . '/..' . '/symfony/polyfill-php80/Php80.php',
        'Symfony\\Polyfill\\Php80\\PhpToken' => __DIR__ . '/..' . '/symfony/polyfill-php80/PhpToken.php',
        'UnhandledMatchError' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/UnhandledMatchError.php',
        'ValueError' => __DIR__ . '/..' . '/symfony/polyfill-php80/Resources/stubs/ValueError.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitDontChange::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitDontChange::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitDontChange::$classMap;

        }, null, ClassLoader::class);
    }
}
