# Branding source assets

These source images feed the icon/splash generators (`flutter_launcher_icons`,
`flutter_native_splash`) configured in `pubspec.yaml`. They are **inputs to the
build tooling**, not runtime assets, so they are not listed under
`flutter: assets:`.

Add the following PNGs here (binary, provided by design), then run the
generators (see the root `RELEASE.md`):

| File                          | Size        | Purpose                              |
|-------------------------------|-------------|--------------------------------------|
| `app_icon.png`                | 1024×1024   | App launcher icon (all platforms)    |
| `app_icon_foreground.png`     | 1024×1024   | Android adaptive-icon foreground     |
| `splash_logo.png`             | ~1152×1152  | Native splash logo (centered)        |

Generate:

```bash
dart run flutter_launcher_icons
dart run flutter_native_splash:create
```
