# Simple APK Generator

A lightweight PHP web application for dynamically generating custom Android APKs with personalized names and logos.

## Features

- Generate basic Android APKs with custom app names
- Customize app icon/logo
- Simple web interface for APK generation
- Supports Android API level 21+ (Android 5.0 and above)

## Requirements

- PHP 7.0+ with GD library
- Java Development Kit (JDK) 8
- Android SDK with:
  - Build Tools version 30.0.3
  - Platform API 34
  - Platform Tools

## Project Structure

```
/appbuilder/
├── index.php           # Web interface
├── generate_apk.php    # APK generation script
├── debug.keystore      # Debug signing key
├── android/            # Android SDK location
│   └── Sdk/
├── java/              # Android app template
│   ├── MainActivity.java
│   └── AndroidManifest.xml
├── build/             # Generated APK output
└── uploads/           # Uploaded logo storage
```

## Setup

1. Install required software:
   - PHP 7.0 or higher
   - Java Development Kit 8
   - Android SDK

2. Configure Android SDK:
   - Install Build Tools 30.0.3
   - Install Platform API 34
   - Install Platform Tools

3. Place the Android SDK in the `android/Sdk` directory or update paths in `generate_apk.php`

4. Ensure PHP has write permissions to:
   - `build/` directory
   - `uploads/` directory

## Usage

1. Access the web interface through your PHP server
2. Enter desired app name
3. Upload an app icon (will be resized to 192x192 pixels)
4. Click generate to create the APK
5. Download and install the generated APK

## Security Notes

- This tool uses a debug keystore for signing APKs
- Intended for development/testing purposes only
- Implement proper security measures before using in production

## License

MIT License - See LICENSE file for details
