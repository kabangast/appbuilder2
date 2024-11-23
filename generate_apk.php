<?php
function generateApk($appName, $logoPath) {
    // Enable error reporting
    if (PHP_OS_FAMILY === "Windows") {
        // Set error reporting
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
    }

    $javaDir = __DIR__ . '/java';
    $buildDir = __DIR__ . '/build';
    
    // Use Android SDK tools from C:\Android
    $androidPath = 'C:/Android';
    $aaptPath = $androidPath . '/build-tools/30.0.3/aapt.exe';
    $androidJarPath = $androidPath . '/platforms/android-34/android.jar';
    $zipAlignPath = $androidPath . '/build-tools/30.0.3/zipalign.exe';
    $apkSignerPath = $androidPath . '/build-tools/30.0.3/apksigner.bat';
    $dxJarPath = $androidPath . '/build-tools/30.0.3/lib/dx.jar';
    
    // Debug information
    $debug = [
        'Current Directory' => __DIR__,
        'Java Directory' => $javaDir,
        'Build Directory' => $buildDir,
        'Android Path' => $androidPath,
        'AAPT Path' => $aaptPath,
        'Android JAR Path' => $androidJarPath,
        'ZipAlign Path' => $zipAlignPath,
        'ApkSigner Path' => $apkSignerPath,
        'DX JAR Path' => $dxJarPath
    ];
    error_log("Debug paths: " . print_r($debug, true));
    
    // Check if required tools exist
    foreach ([$aaptPath, $androidJarPath, $zipAlignPath, $apkSignerPath, $dxJarPath] as $tool) {
        if (!file_exists($tool)) {
            return [
                'success' => false,
                'message' => "Required tool not found at: $tool"
            ];
        }
    }

    // Create build directory if it doesn't exist
    if (!file_exists($buildDir)) {
        mkdir($buildDir, 0777, true);
    }

    // Create necessary Android resource directories
    $resDir = $javaDir . '/res';
    $valuesDir = $resDir . '/values';
    $mipmapDir = $resDir . '/mipmap-xxxhdpi';
    
    foreach ([$resDir, $valuesDir, $mipmapDir] as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }

    // Update app name in strings.xml
    $stringsXml = '<?xml version="1.0" encoding="utf-8"?>
<resources>
    <string name="app_name">' . htmlspecialchars($appName) . '</string>
</resources>';
    file_put_contents($valuesDir . '/strings.xml', $stringsXml);

    // Copy and resize logo to mipmap directory
    $logoInfo = getimagesize($logoPath);
    if ($logoInfo === false) {
        return [
            'success' => false,
            'message' => 'Invalid image file'
        ];
    }

    // Create 192x192 icon for xxxhdpi
    $srcImage = imagecreatefromstring(file_get_contents($logoPath));
    $destImage = imagecreatetruecolor(192, 192);
    imagealphablending($destImage, false);
    imagesavealpha($destImage, true);
    $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
    imagefilledrectangle($destImage, 0, 0, 192, 192, $transparent);
    imagecopyresampled($destImage, $srcImage, 0, 0, 0, 0, 192, 192, $logoInfo[0], $logoInfo[1]);
    imagepng($destImage, $mipmapDir . '/ic_launcher.png');
    imagedestroy($srcImage);
    imagedestroy($destImage);

    // Create necessary directories
    $classesDir = $buildDir . '/classes';
    if (!file_exists($classesDir)) {
        mkdir($classesDir, 0777, true);
    }

    // Step 1: Generate R.java using aapt
    $genDir = $buildDir . '/gen';
    if (!file_exists($genDir)) {
        mkdir($genDir, 0777, true);
    }

    $aaptGenCommand = '"' . $aaptPath . '" package -f -m -J "' . $genDir . '" -M "' . $javaDir . '/AndroidManifest.xml" -S "' . $resDir . '" -I "' . $androidJarPath . '"';
    error_log("Executing AAPT gen command: " . $aaptGenCommand);
    exec($aaptGenCommand . ' 2>&1', $output, $returnVar);
    if ($returnVar !== 0) {
        return [
            'success' => false,
            'message' => 'Failed to generate R.java: ' . implode("\n", $output)
        ];
    }

    // Step 2: Compile Java files with Java 8 compatibility
    // Create package directory structure
    $packageDir = $classesDir . '/com/example/helloworld';
    if (!file_exists($packageDir)) {
        mkdir($packageDir, 0777, true);
    }

    // Copy MainActivity to correct package directory
    copy($javaDir . '/MainActivity.java', $packageDir . '/MainActivity.java');
    
    $javacCommand = 'javac -source 1.8 -target 1.8 -d "' . $classesDir . '" -classpath "' . $androidJarPath . '" -sourcepath "' . $javaDir . ';' . $genDir . '" "' . $packageDir . '/MainActivity.java" "' . $genDir . '/com/example/helloworld/R.java"';
    error_log("Executing javac command: " . $javacCommand);
    exec($javacCommand . ' 2>&1', $output, $returnVar);
    if ($returnVar !== 0) {
        return [
            'success' => false,
            'message' => 'Failed to compile Java files: ' . implode("\n", $output)
        ];
    }

    // Verify class files were created
    $expectedClasses = [
        $classesDir . '/com/example/helloworld/MainActivity.class',
        $classesDir . '/com/example/helloworld/R.class'
    ];
    foreach ($expectedClasses as $classFile) {
        if (!file_exists($classFile)) {
            return [
                'success' => false,
                'message' => 'Expected class file not found: ' . $classFile
            ];
        }
    }
    error_log("Class files successfully created");

    // Step 3: Convert class files to DEX using dx.jar
    $dexFile = $buildDir . '/classes.dex';
    
    // List all class files for debugging
    $classFiles = glob($classesDir . '/com/example/helloworld/*.class');
    error_log("Found class files: " . print_r($classFiles, true));
    
    if (empty($classFiles)) {
        return [
            'success' => false,
            'message' => 'No class files found in ' . $classesDir . '/com/example/helloworld/'
        ];
    }

    // Create a batch file to run dx.jar
    $batchFile = $buildDir . '/run_dx.bat';
    $dxContent = '@echo off' . "\r\n";
    $dxContent .= 'echo Current directory: %CD%' . "\r\n";
    $dxContent .= 'echo Class files directory: ' . $classesDir . "\r\n";
    $dxContent .= 'echo Output dex file: ' . $dexFile . "\r\n";
    $dxContent .= 'dir "' . $classesDir . '\com\example\helloworld\*.class"' . "\r\n";
    $dxContent .= 'java -jar "' . $dxJarPath . '" --dex --verbose --output="' . $dexFile . '" "' . $classesDir . '"' . "\r\n";
    $dxContent .= 'echo DX completed with exit code: %ERRORLEVEL%' . "\r\n";
    $dxContent .= 'if exist "' . $dexFile . '" (echo classes.dex was created) else (echo classes.dex was not created)' . "\r\n";
    file_put_contents($batchFile, $dxContent);
    
    error_log("Executing DX batch file: " . $batchFile);
    error_log("DX batch file contents: " . $dxContent);
    
    exec('"' . $batchFile . '" 2>&1', $output, $returnVar);
    error_log("DX output: " . implode("\n", $output));
    
    if ($returnVar !== 0) {
        return [
            'success' => false,
            'message' => 'Failed to create DEX file: ' . implode("\n", $output)
        ];
    }
    
    // Double check class files exist before running dx
    $mainActivityClass = $classesDir . '/com/example/helloworld/MainActivity.class';
    $rClass = $classesDir . '/com/example/helloworld/R.class';
    
    if (!file_exists($mainActivityClass)) {
        return [
            'success' => false,
            'message' => 'MainActivity.class not found at: ' . $mainActivityClass
        ];
    }
    
    if (!file_exists($rClass)) {
        return [
            'success' => false,
            'message' => 'R.class not found at: ' . $rClass
        ];
    }
    
    // Verify classes.dex was created
    if (!file_exists($dexFile)) {
        // Try to get more information about what went wrong
        $debugInfo = "Debug information:\n";
        $debugInfo .= "1. Current directory: " . getcwd() . "\n";
        $debugInfo .= "2. DX jar path exists: " . (file_exists($dxJarPath) ? "Yes" : "No") . "\n";
        $debugInfo .= "3. Class files found: " . implode(", ", $classFiles) . "\n";
        $debugInfo .= "4. Batch file contents:\n" . $dxContent . "\n";
        $debugInfo .= "5. Command output:\n" . implode("\n", $output) . "\n";
        
        return [
            'success' => false,
            'message' => 'classes.dex was not created by DX tool. ' . $debugInfo
        ];
    }

    // Step 4: Package the initial APK
    $unsignedApk = $buildDir . '/app-unsigned.apk';
    $alignedApk = $buildDir . '/app-aligned.apk';
    $finalApk = $buildDir . '/app-debug.apk';

    $packageCommand = '"' . $aaptPath . '" package -f -M "' . $javaDir . '/AndroidManifest.xml" -S "' . $resDir . '" -I "' . $androidJarPath . '" -F "' . $unsignedApk . '"';
    error_log("Executing package command: " . $packageCommand);
    exec($packageCommand . ' 2>&1', $output, $returnVar);
    if ($returnVar !== 0) {
        return [
            'success' => false,
            'message' => 'Failed to package APK: ' . implode("\n", $output)
        ];
    }

    // Step 5: Add DEX file to APK using full path to aapt
    $addDexCommand = '"' . $aaptPath . '" add "' . $unsignedApk . '" classes.dex';
    $currentDir = getcwd();
    chdir($buildDir); // Change to build directory where classes.dex is located
    error_log("Executing add DEX command: " . $addDexCommand);
    exec($addDexCommand . ' 2>&1', $output, $returnVar);
    chdir($currentDir); // Change back to original directory
    if ($returnVar !== 0) {
        return [
            'success' => false,
            'message' => 'Failed to add DEX to APK: ' . implode("\n", $output)
        ];
    }

    // Step 6: Align the APK
    $alignCommand = '"' . $zipAlignPath . '" -f 4 "' . $unsignedApk . '" "' . $alignedApk . '"';
    error_log("Executing align command: " . $alignCommand);
    exec($alignCommand . ' 2>&1', $output, $returnVar);
    if ($returnVar !== 0) {
        return [
            'success' => false,
            'message' => 'Failed to align APK: ' . implode("\n", $output)
        ];
    }

    // Step 7: Sign the APK
    $keystorePath = __DIR__ . '/debug.keystore';
    $signCommand = '"' . $apkSignerPath . '" sign --ks "' . $keystorePath . '" --ks-pass pass:android --key-pass pass:android --ks-key-alias androiddebugkey --in "' . $alignedApk . '" --out "' . $finalApk . '"';
    error_log("Executing sign command: " . $signCommand);
    exec($signCommand . ' 2>&1', $output, $returnVar);
    if ($returnVar !== 0) {
        return [
            'success' => false,
            'message' => 'Failed to sign APK: ' . implode("\n", $output)
        ];
    }

    // Clean up intermediate files
    if (file_exists($unsignedApk)) unlink($unsignedApk);
    if (file_exists($alignedApk)) unlink($alignedApk);

    return [
        'success' => true,
        'apk_path' => $finalApk,
        'message' => 'APK generated and signed successfully'
    ];
}
?>
